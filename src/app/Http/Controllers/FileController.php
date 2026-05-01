<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use ZipArchive;

class FileController extends Controller
{
    public function upload(Request $request, Document $document)
    {
        abort_unless(Auth::user()->canViewDocument($document), 403);

        $request->validate([
            'file' => 'required|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,webp,svg,txt,csv,json,xml,log,md,mp3,wav,ogg,m4a,mp4,webm',
        ]);

        $file = $request->file('file');
        $storedName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        Storage::disk('public')->makeDirectory('uploads');
        $path = $file->storeAs('uploads', $storedName, 'public');

        if (!$path) {
            throw ValidationException::withMessages([
                'file' => 'Не удалось сохранить файл. Проверьте права на storage/app/public/uploads.',
            ]);
        }

        $document->files()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'path' => $path,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Файл загружен: ' . $file->getClientOriginalName());
    }

    public function download(DocumentFile $file)
    {
        abort_unless(Auth::user()->canViewDocument($file->document), 403);

        if (!Storage::disk('public')->exists($file->path)) {
            abort(404, 'Файл не найден.');
        }

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    public function view(DocumentFile $file)
    {
        abort_unless(Auth::user()->canViewDocument($file->document), 403);

        $inlineUrl = route('files.inline', $file);
        $officeInlineUrl = $file->isOfficeDocument()
            ? \URL::temporarySignedRoute('files.inline.shared', now()->addMinutes(10), ['file' => $file])
            : null;
        $textPreview = null;
        $spreadsheetPreview = null;

        if ($file->isText() && Storage::disk('public')->exists($file->path)) {
            $textPreview = mb_strimwidth(Storage::disk('public')->get($file->path), 0, 40000, "\n...");
        }

        if (strtolower($file->extension) === 'xlsx' && Storage::disk('public')->exists($file->path)) {
            $spreadsheetPreview = $this->buildXlsxPreview(Storage::disk('public')->path($file->path));
        }

        return view('documents.file-viewer', compact('file', 'inlineUrl', 'officeInlineUrl', 'textPreview', 'spreadsheetPreview'));
    }

    public function inline(Request $request, DocumentFile $file)
    {
        if (!$request->hasValidSignature()) {
            abort_unless(Auth::check() && Auth::user()->canViewDocument($file->document), 403);
        }

        if (!Storage::disk('public')->exists($file->path)) {
            abort(404, 'Файл не найден.');
        }

        $absolutePath = Storage::disk('public')->path($file->path);
        $mimeType = $file->mime_type ?: Storage::disk('public')->mimeType($file->path) ?: 'application/octet-stream';

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($file->original_name) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function buildXlsxPreview(string $absolutePath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            return ['error' => 'Не удалось открыть XLSX файл.'];
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetPaths = $this->readWorkbookSheetPaths($zip);

            if (empty($sheetPaths)) {
                return ['error' => 'В книге не найдены листы.'];
            }

            $sheets = [];
            foreach ($sheetPaths as $sheetName => $sheetPath) {
                $sheetXml = $zip->getFromName($sheetPath);
                if ($sheetXml === false) {
                    continue;
                }

                $sheets[] = [
                    'name' => $sheetName,
                    'rows' => $this->readSheetRows($sheetXml, $sharedStrings, 500),
                ];
            }

            return empty($sheets)
                ? ['error' => 'Не удалось прочитать листы XLSX файла.']
                : ['sheets' => $sheets];
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Не удалось подготовить предпросмотр таблицы.'];
        } finally {
            $zip->close();
        }
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sharedStrings = [];
        $root = simplexml_load_string($xml);
        if (!$root instanceof SimpleXMLElement) {
            return [];
        }

        $strings = $root->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($strings->si as $item) {
            $sharedStrings[] = $this->xlsxText($item);
        }

        return $sharedStrings;
    }

    private function readWorkbookSheetPaths(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return [];
        }

        $rels = [];
        $relsRoot = simplexml_load_string($relsXml);
        if ($relsRoot instanceof SimpleXMLElement) {
            $relationships = $relsRoot->children('http://schemas.openxmlformats.org/package/2006/relationships');
            foreach ($relationships->Relationship as $relationship) {
                $attrs = $relationship->attributes();
                $target = (string) ($attrs['Target'] ?? '');
                $rels[(string) ($attrs['Id'] ?? '')] = str_starts_with($target, '/')
                    ? ltrim($target, '/')
                    : 'xl/' . ltrim($target, '/');
            }
        }

        $workbook = simplexml_load_string($workbookXml);
        if (!$workbook instanceof SimpleXMLElement) {
            return [];
        }

        $workbookMain = $workbook->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $sheetPaths = [];
        foreach ($workbookMain->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes();
            $officeAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $name = (string) ($attrs['name'] ?? 'Лист');
            $relationId = (string) ($officeAttrs['id'] ?? '');

            if ($relationId && isset($rels[$relationId])) {
                $sheetPaths[$name] = $rels[$relationId];
            }
        }

        return $sheetPaths;
    }

    private function readSheetRows(string $sheetXml, array $sharedStrings, int $limit): array
    {
        $root = simplexml_load_string($sheetXml);
        if (!$root instanceof SimpleXMLElement) {
            return [];
        }

        $sheet = $root->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        if (!isset($sheet->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            if (count($rows) >= $limit) {
                break;
            }

            $cells = [];
            foreach ($row->c as $cell) {
                $attrs = $cell->attributes();
                $ref = (string) ($attrs['r'] ?? '');
                $column = $ref ? $this->xlsxColumnIndex($ref) : count($cells);
                $cells[$column] = $this->xlsxCellValue($cell, $sharedStrings);
            }

            if (!empty($cells)) {
                ksort($cells);
                $maxColumn = max(array_keys($cells));
                $normalized = [];
                for ($i = 0; $i <= $maxColumn; $i++) {
                    $normalized[] = $cells[$i] ?? '';
                }
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    private function xlsxCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell->attributes()['t'] ?? '');
        $cellMain = $cell->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        if ($type === 'inlineStr' && isset($cellMain->is)) {
            return $this->xlsxText($cellMain->is);
        }

        $value = (string) ($cellMain->v ?? '');

        return match ($type) {
            's' => $sharedStrings[(int) $value] ?? '',
            'b' => $value === '1' ? 'TRUE' : 'FALSE',
            default => $value,
        };
    }

    private function xlsxText(SimpleXMLElement $node): string
    {
        $main = $node->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        if (isset($main->t)) {
            return (string) $main->t;
        }

        $text = '';
        foreach ($main->r as $run) {
            $runMain = $run->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $text .= (string) ($runMain->t ?? '');
        }

        return $text;
    }

    private function xlsxColumnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }
}
