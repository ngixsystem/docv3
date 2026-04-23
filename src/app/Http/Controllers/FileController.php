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
            'file' => 'required|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
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

        return view('documents.file-viewer', compact('file'));
    }
}
