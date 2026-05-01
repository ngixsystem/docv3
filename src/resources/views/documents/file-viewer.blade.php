@extends('layouts.app')
@section('page-title', $file->original_name)

@php
  $ext = strtolower($file->extension);
  $needsPptx  = $ext === 'pptx';
  $needsDocx  = $ext === 'docx';
  $needsXlsx  = in_array($ext, ['xlsx', 'xls']);
  $needsOld   = in_array($ext, ['doc', 'ppt']); // old binary formats → Office Online fallback
  $officeViewerUrl = $officeInlineUrl
    ? 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($officeInlineUrl)
    : null;
@endphp

@push('styles')
@if($needsPptx)
<link rel="stylesheet" href="{{ asset('vendor/laravel-file-viewer/officetohtml/PPTXjs/css/pptxjs.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/laravel-file-viewer/officetohtml/PPTXjs/css/nv.d3.min.css') }}">
@endif
<style>
.viewer-shell { display: grid; gap: 18px; }

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
  position: relative;
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

/* DOCX viewer */
#docx-container {
  background: #fff;
  color: #111;
  padding: 40px 60px;
  min-height: 70vh;
  overflow: auto;
  font-family: 'Segoe UI', Arial, sans-serif;
}
#docx-container .docx { max-width: 900px; margin: 0 auto; }

/* XLSX viewer */
#xlsx-tabs {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
  padding: 10px 14px 0;
  border-bottom: 1px solid var(--border);
  background: var(--card-solid);
}
.xlsx-tab {
  padding: 6px 14px;
  border-radius: 8px 8px 0 0;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid var(--border);
  border-bottom: none;
  background: var(--surface-soft);
  color: var(--text-muted);
  transition: background .15s, color .15s;
}
.xlsx-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); }
#xlsx-sheet-container {
  overflow: auto;
  max-height: 75vh;
  background: #fff;
}
#xlsx-sheet-container table {
  border-collapse: collapse;
  font-size: 12.5px;
  color: #111;
  min-width: 100%;
}
#xlsx-sheet-container th, #xlsx-sheet-container td {
  border: 1px solid #d0d0d0;
  padding: 4px 8px;
  white-space: nowrap;
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
}
#xlsx-sheet-container th { background: #f0f0f0; font-weight: 700; position: sticky; top: 0; z-index: 1; }

/* PPTX viewer */
#pptx-container {
  min-height: 70vh;
  background: #222;
  overflow: auto;
}
#pptx-container .slide { margin: 20px auto; display: block; }

/* Loading spinner */
.viewer-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
  gap: 16px;
  color: var(--text-muted);
  font-size: 13.5px;
}
.viewer-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.viewer-empty {
  padding: 40px 28px;
  color: var(--text-muted);
  line-height: 1.8;
  text-align: center;
}
</style>
@endpush

@section('topbar-actions')
  <a href="{{ route('files.download', $file) }}" class="btn btn-primary btn-sm">Скачать</a>
@endsection

@section('content')
<div class="viewer-shell">
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        Просмотр файла
        <span class="badge" style="font-size:11px; margin-left:8px; background:var(--accent-soft); color:var(--accent); padding:3px 8px; border-radius:6px;">{{ $file->extension }}</span>
      </div>
    </div>
    <div class="card-body">
      <div style="font-size:16px; font-weight:700; margin-bottom:8px;">{{ $file->original_name }}</div>
      <div class="viewer-meta">
        <span>{{ $file->formatted_size }}</span>
        <span>·</span>
        <span>{{ $file->mime_type }}</span>
        @if($file->uploader)
          <span>·</span>
          <span>Загрузил: {{ $file->uploader->name }}</span>
        @endif
      </div>
    </div>
  </div>

  <div class="viewer-stage" id="viewerStage">

    @if($file->isPdf())
      {{-- ── PDF ─────────────────────────────────── --}}
      <embed class="viewer-embed" src="{{ $inlineUrl }}#toolbar=1&navpanes=0" type="application/pdf">

    @elseif($file->isImage())
      {{-- ── Image ───────────────────────────────── --}}
      <img class="viewer-image" src="{{ $inlineUrl }}" alt="{{ $file->original_name }}">

    @elseif($file->isAudio())
      {{-- ── Audio ───────────────────────────────── --}}
      <div style="padding:40px 28px;">
        <audio controls preload="metadata" style="width:100%;">
          <source src="{{ $inlineUrl }}" type="{{ $file->mime_type }}">
        </audio>
      </div>

    @elseif($file->isVideo())
      {{-- ── Video ───────────────────────────────── --}}
      <video class="viewer-embed" controls preload="metadata">
        <source src="{{ $inlineUrl }}" type="{{ $file->mime_type }}">
      </video>

    @elseif($file->isText() && $textPreview !== null)
      {{-- ── Plain text ──────────────────────────── --}}
      <pre class="viewer-text">{{ $textPreview }}</pre>

    @elseif($needsDocx)
      {{-- ── DOCX — rendered client-side via docx-preview ── --}}
      <div class="viewer-loading" id="docxLoading">
        <div class="viewer-spinner"></div>
        <div>Загрузка документа...</div>
      </div>
      <div id="docx-container" style="display:none;"></div>

    @elseif($needsXlsx)
      {{-- ── XLSX — rendered server-side to avoid browser crashes ──────────── --}}
      @if(!empty($spreadsheetPreview['error']))
        <div class="viewer-empty">
          <div style="font-size:40px; margin-bottom:14px; opacity:.3;">📊</div>
          <div>{{ $spreadsheetPreview['error'] }}</div>
          <div style="margin-top:8px;">Скачайте файл, чтобы открыть его локально.</div>
        </div>
      @elseif(!empty($spreadsheetPreview['sheets']))
        <div id="xlsx-tabs">
          @foreach($spreadsheetPreview['sheets'] as $index => $sheet)
            <button type="button" class="xlsx-tab {{ $index === 0 ? 'active' : '' }}" data-sheet-tab="{{ $index }}">
              {{ $sheet['name'] }}
            </button>
          @endforeach
        </div>
        <div id="xlsx-sheet-container">
          @foreach($spreadsheetPreview['sheets'] as $index => $sheet)
            <div class="xlsx-sheet-panel" data-sheet-panel="{{ $index }}" style="{{ $index === 0 ? '' : 'display:none;' }}">
              @if(empty($sheet['rows']))
                <div style="padding:20px;color:#888;">Лист пуст</div>
              @else
                <table>
                  <tbody>
                    @foreach($sheet['rows'] as $rowIndex => $row)
                      <tr>
                        @foreach($row as $cell)
                          @if($rowIndex === 0)
                            <th>{{ $cell }}</th>
                          @else
                            <td>{{ $cell }}</td>
                          @endif
                        @endforeach
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              @endif
            </div>
          @endforeach
        </div>
      @else
        <div class="viewer-empty">
          <div style="font-size:40px; margin-bottom:14px; opacity:.3;">📊</div>
          <div>Не удалось подготовить предпросмотр таблицы.</div>
          <div style="margin-top:8px;">Скачайте файл, чтобы открыть его локально.</div>
        </div>
      @endif

    @elseif($needsPptx)
      {{-- ── PPTX — rendered via PPTXjs ─────────────────── --}}
      <div class="viewer-loading" id="pptxLoading">
        <div class="viewer-spinner"></div>
        <div>Загрузка презентации...</div>
      </div>
      <div id="pptx-container" style="display:none;"></div>

    @elseif($needsOld && $officeViewerUrl)
      {{-- ── DOC/PPT old binary — Office Online fallback ── --}}
      <iframe
        class="viewer-frame"
        src="{{ $officeViewerUrl }}"
        title="{{ $file->original_name }}"
        loading="lazy"
        referrerpolicy="no-referrer"
      ></iframe>

    @else
      <div class="viewer-empty">
        <div style="font-size:40px; margin-bottom:14px; opacity:.3;">📄</div>
        <div>Встроенный просмотр для формата <strong>{{ $file->extension }}</strong> не поддерживается.</div>
        <div style="margin-top:8px;">Скачайте файл, чтобы открыть его локально.</div>
      </div>
    @endif

  </div>
</div>
@endsection

@push('scripts')
@if($needsDocx)
{{-- DOCX-preview (no jQuery needed) --}}
<script src="{{ asset('vendor/laravel-file-viewer/docx-preview/docx-preview.min.js') }}"></script>
<script>
(async function () {
  const url       = @json($inlineUrl);
  const loading   = document.getElementById('docxLoading');
  const container = document.getElementById('docx-container');
  try {
    const res  = await fetch(url);
    const blob = await res.blob();
    await window.docx.renderAsync(blob, container, null, {
      experimental: true,
      inWrapper: true,
      ignoreWidth: false,
      ignoreHeight: false,
    });
    loading.style.display  = 'none';
    container.style.display = 'block';
  } catch (e) {
    loading.innerHTML = '<div style="color:var(--accent); padding:20px;">Ошибка загрузки документа. <a href="' + url + '" style="color:var(--accent);">Попробуйте скачать</a>.</div>';
    console.error(e);
  }
})();
</script>
@endif

@if($needsXlsx && !empty($spreadsheetPreview['sheets']))
<script>
(function () {
  document.querySelectorAll('[data-sheet-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () {
      const index = tab.dataset.sheetTab;
      document.querySelectorAll('[data-sheet-tab]').forEach(function (item) {
        item.classList.toggle('active', item.dataset.sheetTab === index);
      });
      document.querySelectorAll('[data-sheet-panel]').forEach(function (panel) {
        panel.style.display = panel.dataset.sheetPanel === index ? '' : 'none';
      });
    });
  });
})();
</script>
@endif

@if($needsPptx)
{{-- PPTXjs — requires jQuery --}}
<script src="{{ asset('vendor/laravel-file-viewer/officetohtml/jquery/jquery-3.3.1.min.js') }}"></script>
<script src="{{ asset('vendor/laravel-file-viewer/officetohtml/PPTXjs/js/jszip.min.js') }}"></script>
<script src="{{ asset('vendor/laravel-file-viewer/officetohtml/PPTXjs/js/filereader.js') }}"></script>
<script src="{{ asset('vendor/laravel-file-viewer/officetohtml/PPTXjs/js/d3.min.js') }}"></script>
<script src="{{ asset('vendor/laravel-file-viewer/officetohtml/PPTXjs/js/nv.d3.min.js') }}"></script>
<script src="{{ asset('vendor/laravel-file-viewer/officetohtml/PPTXjs/js/pptxjs.js') }}"></script>
<script>
$(function () {
  const url     = @json($inlineUrl);
  const loading = document.getElementById('pptxLoading');
  const cont    = document.getElementById('pptx-container');

  $('#pptx-container').pptxToHtml({
    pptxFileUrl: url,
    slideMode: false,
    keyBoardShortCut: false,
    mediaProcess: false,
    slidesScale: '75%',
    slideNoteDisplay: false,
    functionOnFinish: function () {
      loading.style.display = 'none';
      cont.style.display    = 'block';
    },
  });
});
</script>
@endif
@endpush
