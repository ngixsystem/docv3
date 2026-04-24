<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        $path = $file->storeAs('uploads', $storedName, 'public');

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

        if ($file->isText() && Storage::disk('public')->exists($file->path)) {
            $textPreview = mb_strimwidth(Storage::disk('public')->get($file->path), 0, 40000, "\n...");
        }

        return view('documents.file-viewer', compact('file', 'inlineUrl', 'officeInlineUrl', 'textPreview'));
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
}
