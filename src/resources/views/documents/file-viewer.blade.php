@extends('layouts.app')
@section('page-title', $file->original_name)

@section('topbar-actions')
  <a href="{{ route('files.download', $file) }}" class="btn btn-primary btn-sm">Скачать</a>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <div class="card-title">Просмотр файла</div>
  </div>
  <div class="card-body">
    <div style="font-size:15px; font-weight:600; margin-bottom:8px;">{{ $file->original_name }}</div>
    <div style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">{{ $file->formatted_size }} · {{ $file->mime_type }}</div>
    <p style="line-height:1.7; color:var(--text-muted);">
      Встроенный предпросмотр для этого типа файла не реализован. Используйте кнопку скачивания, чтобы открыть документ локально.
    </p>
  </div>
</div>
@endsection
