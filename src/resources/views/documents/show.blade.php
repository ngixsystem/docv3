@extends('layouts.app')
@section('page-title', $document->number)

@push('styles')
<style>
.document-soft-block {
  margin-top: 16px;
  padding: 14px;
  background: var(--surface-soft);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  line-height: 1.7;
}

.document-note {
  background: var(--surface-soft);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
}

.document-related-task {
  padding: 10px;
}
</style>
@endpush

@section('topbar-actions')
  <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm">К реестру</a>
@endsection

@section('content')
<div style="display:grid; grid-template-columns:1fr 340px; gap:22px; align-items:start;">
  <div style="display:flex; flex-direction:column; gap:18px;">
    <div class="card">
      <div class="card-body">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px; flex-wrap:wrap;">
          <span class="badge type-{{ $document->type }}">{{ $document->type_name }}</span>
          <span class="badge status-{{ $document->status }}">{{ $document->status_name }}</span>
        </div>
        <h2 style="font-size:22px; margin-bottom:14px;">{{ $document->subject }}</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; font-size:13px;">
          <div><div style="font-size:11px; color:var(--text-muted);">Номер</div><code>{{ $document->number }}</code></div>
          <div><div style="font-size:11px; color:var(--text-muted);">Дата</div>{{ $document->doc_date->format('d.m.Y') }}</div>
          <div><div style="font-size:11px; color:var(--text-muted);">Срок</div>{{ $document->deadline?->format('d.m.Y') ?? '—' }}</div>
          <div><div style="font-size:11px; color:var(--text-muted);">Создан</div>{{ $document->createdBy?->name ?? '—' }}</div>
        </div>
        @if($document->description)
          <div class="document-soft-block">{{ $document->description }}</div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Маршрут</div></div>
      <div class="card-body" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
        <div>
          <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Отправитель</div>
          <div>{{ $document->sender?->name ?? $document->sender_org ?? '—' }}</div>
        </div>
        <div>
          <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Получатель</div>
          <div>{{ $document->recipient?->name ?? $document->recipientGroup?->name ?? $document->recipient_org ?? '—' }}</div>
        </div>
        <div>
          <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Исполнитель</div>
          <div>{{ $document->executor?->name ?? '—' }}</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Файлы</div>
        <button class="btn btn-sm btn-primary" type="button" onclick="openModal('uploadModal')">+ Загрузить</button>
      </div>
      <div class="card-body" style="padding:14px 18px;">
        @forelse($document->files as $file)
          <div class="file-item" style="margin-bottom:8px;">
            <span class="file-ext ext-{{ strtolower($file->extension) }}">{{ $file->extension }}</span>
            <span class="file-name">{{ $file->original_name }}</span>
            <span class="file-size">{{ $file->formatted_size }}</span>
            <a href="{{ route('files.download', $file) }}" class="btn btn-sm btn-secondary">Скачать</a>
          </div>
        @empty
          <div style="color:var(--text-muted);">Файлы не прикреплены</div>
        @endforelse
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Комментарии</div></div>
      <div class="card-body">
        @foreach($document->comments as $comment)
          <div style="display:flex; gap:10px; margin-bottom:16px;">
            <div class="avatar avatar-sm" style="background: {{ avatarColor($comment->user->name) }};">{{ $comment->user->initials }}</div>
            <div style="flex:1;">
              <div style="display:flex; gap:8px; margin-bottom:4px; align-items:center;">
                <strong>{{ $comment->user->name }}</strong>
                <span style="font-size:11px; color:var(--text-muted);">{{ $comment->created_at->format('d.m.Y H:i') }}</span>
              </div>
              <div class="document-note" style="padding:10px 12px;">{{ $comment->body }}</div>
            </div>
          </div>
        @endforeach
        <form method="POST" action="{{ route('documents.comment', $document) }}" style="display:flex; gap:8px; margin-top:8px;">
          @csrf
          <textarea name="body" class="form-control" rows="2" placeholder="Написать комментарий..." required style="flex:1; resize:none;"></textarea>
          <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Отправить</button>
        </form>
      </div>
    </div>
  </div>

  <div style="display:flex; flex-direction:column; gap:18px;">
    <div class="card">
      <div class="card-header"><div class="card-title">Статус</div></div>
      <div class="card-body">
        <div style="text-align:center; margin-bottom:14px;">
          <span class="badge status-{{ $document->status }}" style="font-size:14px; padding:6px 16px;">{{ $document->status_name }}</span>
        </div>
        @foreach($document->next_statuses as $status)
          @php
            $canShow = match ($status) {
              'registered' => $currentUser->canRegisterDocuments(),
              'approved', 'rejected' => $currentUser->canApproveDocuments(),
              default => $currentUser->hasAnyRole(['admin', 'manager', 'clerk']),
            };
          @endphp
          @if($canShow)
            <button type="button" class="btn btn-primary" style="width:100%; margin-bottom:8px;" onclick="openStatusModal('{{ $status }}', '{{ \App\Models\Document::$statusNames[$status] }}')">
              → {{ \App\Models\Document::$statusNames[$status] }}
            </button>
          @endif
        @endforeach
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">История статусов</div></div>
      <div class="card-body">
        @foreach($document->statusHistory as $history)
          <div style="margin-bottom:12px;">
            <div style="font-weight:600;">{{ \App\Models\Document::$statusNames[$history->to_status] ?? $history->to_status }}</div>
            <div style="font-size:11px; color:var(--text-muted);">{{ $history->user->name }} · {{ $history->created_at->format('d.m H:i') }}</div>
            @if($history->comment)
              <div style="font-size:12px; margin-top:3px;">{{ $history->comment }}</div>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    @if($document->tasks->count())
      <div class="card">
        <div class="card-header"><div class="card-title">Связанные задачи</div></div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:8px;">
          @foreach($document->tasks as $task)
            <div class="document-note document-related-task">
              <div style="font-weight:600;">{{ $task->title }}</div>
              <div style="font-size:12px; color:var(--text-muted);">{{ $task->assignee->name }}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>
</div>

<div class="modal-overlay" id="uploadModal" onclick="if(event.target===this)closeModal('uploadModal')">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <div class="modal-title">Загрузить файл</div>
      <button class="modal-close" type="button" onclick="closeModal('uploadModal')">×</button>
    </div>
    <form method="POST" action="{{ route('documents.upload', $document) }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Файл</label>
          <input type="file" name="file" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Загрузить</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="statusModal" onclick="if(event.target===this)closeModal('statusModal')">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <div class="modal-title">Изменить статус: <span id="statusModalName"></span></div>
      <button class="modal-close" type="button" onclick="closeModal('statusModal')">×</button>
    </div>
    <form method="POST" action="{{ route('documents.status', $document) }}">
      @csrf
      @method('PATCH')
      <input type="hidden" name="status" id="statusInput">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Комментарий</label>
          <textarea name="comment" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Подтвердить</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function openStatusModal(status, name) {
  document.getElementById('statusInput').value = status;
  document.getElementById('statusModalName').textContent = name;
  openModal('statusModal');
}
</script>
@endpush
@endsection
