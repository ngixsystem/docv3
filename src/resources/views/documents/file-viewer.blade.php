@extends('layouts.app')
@section('page-title', $file->original_name)

@push('styles')
<style>
.viewer-shell {
  display: grid;
  gap: 18px;
}

.viewer-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  color: var(--text-muted);
  font-size: 13px;
}

.viewer-stage {
  background: color-mix(in srgb, var(--card-solid) 88%, transparent);
  border: 1px solid var(--border);
  border-radius: 18px;
  overflow: hidden;
  min-height: 70vh;
}

.viewer-embed,
.viewer-image,
.viewer-text,
.viewer-frame {
  width: 100%;
  min-height: 70vh;
  border: 0;
  display: block;
  background: var(--card-solid);
}

.viewer-image {
  object-fit: contain;
  max-height: 80vh;
}

.viewer-text {
  padding: 20px;
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  color: var(--text);
  font: 13px/1.7 Consolas, monospace;
}

.viewer-empty {
  padding: 28px;
  color: var(--text-muted);
  line-height: 1.7;
}
</style>
@endpush

@section('topbar-actions')
  <a href="{{ route('files.download', $file) }}" class="btn btn-primary btn-sm">Скачать</a>
@endsection

@section('content')
@php
  $officeViewerUrl = $officeInlineUrl
    ? 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($officeInlineUrl)
    : null;
@endphp

<div class="viewer-shell">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Просмотр файла</div>
    </div>
    <div class="card-body">
      <div style="font-size:16px; font-weight:700; margin-bottom:8px;">{{ $file->original_name }}</div>
      <div class="viewer-meta">
        <span>{{ $file->formatted_size }}</span>
        <span>·</span>
        <span>{{ $file->mime_type }}</span>
        <span>·</span>
        <span>{{ $file->extension }}</span>
      </div>
    </div>
  </div>

  <div class="viewer-stage">
    @if($file->isPdf())
      <embed class="viewer-embed" src="{{ $inlineUrl }}#toolbar=1&navpanes=0" type="application/pdf">
    @elseif($file->isImage())
      <img class="viewer-image" src="{{ $inlineUrl }}" alt="{{ $file->original_name }}">
    @elseif($file->isAudio())
      <div style="padding:28px;">
        <audio controls preload="metadata" style="width:100%;">
          <source src="{{ $inlineUrl }}" type="{{ $file->mime_type }}">
        </audio>
      </div>
    @elseif($file->isVideo())
      <video class="viewer-embed" controls preload="metadata">
        <source src="{{ $inlineUrl }}" type="{{ $file->mime_type }}">
      </video>
    @elseif($file->isText() && $textPreview !== null)
      <pre class="viewer-text">{{ $textPreview }}</pre>
    @elseif($file->isOfficeDocument() && $officeViewerUrl)
      <iframe
        class="viewer-frame"
        src="{{ $officeViewerUrl }}"
        title="{{ $file->original_name }}"
        loading="lazy"
        referrerpolicy="no-referrer"
      ></iframe>
    @else
      <div class="viewer-empty">
        Встроенный просмотр для этого типа файла не поддерживается в браузере напрямую.
        Используйте кнопку скачивания, чтобы открыть документ локально.
      </div>
    @endif
  </div>
</div>
@endsection
