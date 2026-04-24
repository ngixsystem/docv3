@extends('layouts.app')
@section('page-title', 'Задача')

@php
  $statusOptions = collect(\App\Models\Task::$statusNames)
      ->reject(fn ($label, $key) => $key === $task->status);
  $relatedFiles = $task->document?->files ?? collect();
  $canInteract = $currentUser->canChangeTask($task) && $task->status === 'in_progress';
@endphp

@push('styles')
<style>
.task-show-wrap {
  max-width: 760px;
  margin: 0 auto;
}

.task-show-shell {
  background:
    radial-gradient(circle at top right, color-mix(in srgb, var(--accent) 20%, transparent), transparent 34%),
    linear-gradient(180deg, color-mix(in srgb, var(--card-solid) 84%, transparent), color-mix(in srgb, var(--card) 92%, transparent));
  overflow: hidden;
}

.task-show-main {
  padding: 26px 24px 22px;
}

.task-show-title {
  font-size: 34px;
  line-height: 1.08;
  margin: 14px 0 16px;
}

.task-show-description {
  font-size: 16px;
  line-height: 1.8;
  color: var(--text-muted);
  white-space: pre-line;
}

.task-show-people,
.task-show-pills,
.task-show-files {
  display: grid;
  gap: 12px;
}

.task-show-person {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface-soft) 88%, transparent);
}

.task-show-person-copy {
  min-width: 0;
}

.task-show-label {
  margin-bottom: 4px;
  font-size: 11px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .08em;
}

.task-show-name {
  font-size: 15px;
  font-weight: 700;
}

.task-show-deadline {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 16px;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface-soft) 88%, transparent);
}

.task-show-deadline-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, #0d6efd 18%, transparent);
  color: #58a6ff;
  font-size: 21px;
  flex-shrink: 0;
}

.task-show-action-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 20px;
}

.task-show-action-row .btn {
  min-height: 46px;
  border-radius: 16px;
  padding-inline: 18px;
}

.task-show-status-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.task-show-status-row .btn {
  border-radius: 999px;
}

.task-show-file {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface-soft) 88%, transparent);
}

.task-show-file-copy {
  flex: 1;
  min-width: 0;
}

.task-show-file-name {
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.task-show-file-meta {
  font-size: 12px;
  color: var(--text-muted);
}

@media (max-width: 640px) {
  .task-show-wrap {
    max-width: 100%;
  }

  .task-show-main {
    padding: 20px 18px 18px;
  }

  .task-show-title {
    font-size: 28px;
  }

  .task-show-action-row,
  .task-show-status-row {
    flex-direction: column;
  }

  .task-show-action-row .btn,
  .task-show-status-row .btn {
    width: 100%;
  }
}
</style>
@endpush

@section('topbar-actions')
  <a href="{{ route('tasks.index') }}" class="btn btn-secondary btn-sm">К задачам</a>
@endsection

@section('content')
<div class="task-show-wrap">
  <div class="card task-show-shell">
    <div class="task-show-main">
      <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <span class="badge priority-{{ $task->priority }}">{{ $task->priority_name }}</span>
        <span class="badge status-{{ $task->status === 'done' ? 'approved' : ($task->status === 'paused' ? 'review' : ($task->status === 'in_progress' ? 'registered' : 'draft')) }}">{{ $task->status_name }}</span>
        @if($task->is_overdue)
          <span class="overdue-badge">Просрочена</span>
        @endif
      </div>

      <h1 class="task-show-title">{{ $task->title }}</h1>

      @if($task->description)
        <div class="task-show-description">{{ $task->description }}</div>
      @else
        <div class="task-show-description">Описание для этой задачи пока не добавлено.</div>
      @endif

      <div class="task-show-people" style="margin-top:22px;">
        <div class="task-show-person">
          <div class="avatar" style="background: {{ avatarColor($task->creator->name) }};">{{ $task->creator->initials }}</div>
          <div class="task-show-person-copy">
            <div class="task-show-label">Постановщик</div>
            <div class="task-show-name">{{ $task->creator->name }}</div>
          </div>
        </div>

        <div class="task-show-person">
          <div class="avatar" style="background: {{ avatarColor($task->assignee->name) }};">{{ $task->assignee->initials }}</div>
          <div class="task-show-person-copy">
            <div class="task-show-label">Исполнитель</div>
            <div class="task-show-name">{{ $task->assignee->name }}</div>
          </div>
        </div>
      </div>

      <div class="task-show-deadline" style="margin-top:16px;">
        <div class="task-show-deadline-icon">⌚</div>
        <div>
          <div class="task-show-label">Срок</div>
          <div class="task-show-name">{{ $task->deadline?->format('d.m.Y') ?? 'Не указан' }}</div>
        </div>
      </div>

      <div class="task-show-action-row">
        @if($currentUser->canChangeTask($task))
          @if($task->status !== 'done')
            <button class="btn btn-primary" type="button" onclick="changeTaskStatus({{ $task->id }}, 'done')">Завершить</button>
          @endif
          @if($task->status !== 'in_progress')
            <button class="btn btn-outline" type="button" onclick="changeTaskStatus({{ $task->id }}, 'in_progress')">В работу</button>
          @endif
        @endif
        @if($task->document)
          <a href="{{ route('documents.show', $task->document) }}" class="btn btn-outline">Документ</a>
        @endif
        @if($relatedFiles->count())
          <a href="#task-files" class="btn btn-outline">Файлы</a>
        @endif
      </div>

      @if($currentUser->canChangeTask($task))
        <div class="task-show-status-row">
          @foreach($statusOptions as $statusKey => $statusLabel)
            <button class="btn btn-secondary" type="button" onclick="changeTaskStatus({{ $task->id }}, '{{ $statusKey }}')">{{ $statusLabel }}</button>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  @if($task->document)
    <div class="card" style="margin-top:18px;">
      <div class="card-header"><div class="card-title">Связанный документ</div></div>
      <div class="card-body">
        <a href="{{ route('documents.show', $task->document) }}" class="document-note" style="display:block; padding:14px 16px; text-decoration:none;">
          <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px;">
            <span class="badge status-{{ $task->document->status }}">{{ $task->document->status_name }}</span>
          </div>
          <div style="font-weight:700; color:var(--text); margin-bottom:4px;">{{ $task->document->number }}</div>
          <div style="color:var(--text); margin-bottom:4px;">{{ $task->document->subject }}</div>
          <div style="font-size:12px; color:var(--text-muted);">
            {{ $task->document->doc_date?->format('d.m.Y') ?? '—' }}
          </div>
        </a>
      </div>
    </div>
  @endif

  @if($relatedFiles->count())
    <div class="card" style="margin-top:18px;">
      <div id="task-files"></div>
      <div class="card-header"><div class="card-title">Файлы документа</div></div>
      <div class="card-body task-show-files">
        @foreach($relatedFiles as $file)
          <div class="task-show-file">
            <span class="file-ext ext-{{ strtolower($file->extension) }}">{{ $file->extension }}</span>
            <div class="task-show-file-copy">
              <div class="task-show-file-name">{{ $file->original_name }}</div>
              <div class="task-show-file-meta">{{ $file->formatted_size }}</div>
            </div>
            <a href="{{ route('files.view', $file) }}" class="btn btn-sm btn-primary">Открыть</a>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Файлы задачи --}}
  <div class="card" style="margin-top:18px;">
    <div id="task-own-files"></div>
    <div class="card-header">
      <div class="card-title">Вложения к задаче</div>
      @if($canInteract)
        <button class="btn btn-sm btn-primary" type="button" onclick="openModal('taskUploadModal')">+ Загрузить</button>
      @endif
    </div>
    <div class="card-body task-show-files">
      @forelse($task->taskFiles as $file)
        <div class="task-show-file">
          <span class="file-ext ext-{{ strtolower($file->extension) }}">{{ $file->extension }}</span>
          <div class="task-show-file-copy">
            <div class="task-show-file-name">{{ $file->original_name }}</div>
            <div class="task-show-file-meta">{{ $file->formatted_size }} · {{ $file->uploader->name }} · {{ $file->created_at->format('d.m.Y H:i') }}</div>
          </div>
          <a href="{{ route('task-files.download', $file) }}" class="btn btn-sm btn-secondary">Скачать</a>
        </div>
      @empty
        <div style="color:var(--text-muted);">Нет вложений</div>
      @endforelse
    </div>
  </div>

  {{-- История статусов --}}
  <div class="card" style="margin-top:18px;">
    <div class="card-header"><div class="card-title">История изменений</div></div>
    <div class="card-body">
      @foreach($task->statusHistory as $entry)
        <div style="display:flex; gap:10px; margin-bottom:14px; align-items:flex-start;">
          <div class="avatar avatar-sm" style="background: {{ avatarColor($entry->user->name) }}; flex-shrink:0;">{{ $entry->user->initials }}</div>
          <div style="flex:1;">
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:2px;">
              <strong>{{ $entry->user->name }}</strong>
              <span style="font-size:11px; color:var(--text-muted);">{{ $entry->created_at->format('d.m.Y H:i') }}</span>
            </div>
            <div style="font-size:13px; color:var(--text-muted);">
              @if($entry->from_status)
                <span class="badge status-{{ $entry->from_status === 'done' ? 'approved' : ($entry->from_status === 'paused' ? 'review' : ($entry->from_status === 'in_progress' ? 'registered' : 'draft')) }}">{{ \App\Models\Task::$statusNames[$entry->from_status] ?? $entry->from_status }}</span>
                →
              @endif
              <span class="badge status-{{ $entry->to_status === 'done' ? 'approved' : ($entry->to_status === 'paused' ? 'review' : ($entry->to_status === 'in_progress' ? 'registered' : 'draft')) }}">{{ \App\Models\Task::$statusNames[$entry->to_status] ?? $entry->to_status }}</span>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Комментарии к задаче --}}
  <div class="card" style="margin-top:18px;">
    <div class="card-header"><div class="card-title">Комментарии</div></div>
    <div class="card-body">
      @forelse($task->comments as $comment)
        <div style="display:flex; gap:10px; margin-bottom:16px;">
          <div class="avatar avatar-sm" style="background: {{ avatarColor($comment->user->name) }};">{{ $comment->user->initials }}</div>
          <div style="flex:1;">
            <div style="display:flex; gap:8px; margin-bottom:4px; align-items:center;">
              <strong>{{ $comment->user->name }}</strong>
              <span style="font-size:11px; color:var(--text-muted);">{{ $comment->created_at->format('d.m.Y H:i') }}</span>
            </div>
            <div style="padding:10px 12px; background:var(--surface-soft); border:1px solid var(--border); border-radius:8px;">{{ $comment->body }}</div>
          </div>
        </div>
      @empty
        <div style="color:var(--text-muted); margin-bottom:12px;">Комментариев пока нет.</div>
      @endforelse

      @if($canInteract)
        <form method="POST" action="{{ route('tasks.comment', $task) }}" style="display:flex; gap:8px; margin-top:8px;">
          @csrf
          <textarea name="body" class="form-control" rows="2" placeholder="Написать комментарий..." required style="flex:1; resize:none;"></textarea>
          <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Отправить</button>
        </form>
      @else
        <div style="font-size:13px; color:var(--text-muted);">Комментарии доступны, когда задача взята в работу.</div>
      @endif
    </div>
  </div>
</div>

{{-- Модал загрузки файла к задаче --}}
<div class="modal-overlay" id="taskUploadModal" onclick="if(event.target===this)closeModal('taskUploadModal')">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <div class="modal-title">Загрузить файл к задаче</div>
      <button class="modal-close" type="button" onclick="closeModal('taskUploadModal')">×</button>
    </div>
    <form method="POST" action="{{ route('tasks.upload', $task) }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Файл</label>
          <input type="file" name="file" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('taskUploadModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Загрузить</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function changeTaskStatus(taskId, status) {
  fetch('/tasks/' + taskId + '/status', {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
    },
    body: JSON.stringify({ status })
  }).then(() => location.reload());
}
</script>
@endpush
