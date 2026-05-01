@extends('layouts.app')
@section('page-title', $document->number)

@php
  $isRecipientExperience = $currentUser->isDocumentRecipient($document) || $currentUser->isDocumentExecutor($document);
  $executorCompletion = $document->executorCompletionFor($currentUser);
  $canCompleteExecutorPart = $currentUser->isDocumentExecutor($document)
      && $document->status === 'review'
      && empty($executorCompletion?->completed_at);
  $executorNames = $document->executors->isNotEmpty()
      ? $document->executors->pluck('name')->join(', ')
      : ($document->executor?->name ?? '—');
  $allExecutorsCompleted = $document->allExecutorsCompleted();
  $availableStatuses = collect($document->next_statuses)
      ->filter(fn ($status) => $currentUser->canChangeDocumentStatus($document, $status))
      ->values();
@endphp

@push('styles')
<style>
@media print {
  .sidebar, .topbar, .topbar-actions, .btn, form, .modal-overlay,
  .flash, .task-show-action-row, .recipient-actions { display: none !important; }
  .main { margin-left: 0 !important; }
  .page-content { padding: 0 !important; }
  .document-layout { grid-template-columns: 1fr !important; }
  .card { box-shadow: none !important; border: 1px solid #ccc !important; break-inside: avoid; }
  body { background: #fff !important; color: #000 !important; }
}

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

.document-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 22px;
  align-items: start;
}

.document-layout.recipient-layout {
  grid-template-columns: minmax(0, 1fr) 320px;
}

.recipient-hero {
  padding: 24px;
  background:
    radial-gradient(circle at top right, color-mix(in srgb, var(--accent) 20%, transparent), transparent 34%),
    linear-gradient(180deg, color-mix(in srgb, var(--card-solid) 82%, transparent), color-mix(in srgb, var(--card) 92%, transparent));
}

.recipient-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.recipient-headline {
  margin: 14px 0 16px;
  font-size: 30px;
  line-height: 1.08;
  max-width: 720px;
}

.recipient-summary {
  color: var(--text-muted);
  font-size: 15px;
  line-height: 1.75;
  max-width: 760px;
}

.recipient-meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
  margin-top: 20px;
}

.recipient-meta-card {
  padding: 14px 16px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: color-mix(in srgb, var(--surface-soft) 88%, transparent);
}

.recipient-meta-label {
  margin-bottom: 8px;
  font-size: 11px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .08em;
}

.recipient-meta-value {
  font-size: 17px;
  font-weight: 700;
}

.recipient-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 22px;
}

.recipient-actions .btn {
  min-height: 44px;
  padding-inline: 18px;
  border-radius: 14px;
}

.recipient-actions form {
  display: inline-flex;
}

.recipient-people {
  display: grid;
  gap: 12px;
}

.recipient-person {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface-soft) 88%, transparent);
}

.recipient-person-copy {
  min-width: 0;
}

.recipient-person-label {
  margin-bottom: 4px;
  font-size: 11px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .08em;
}

.recipient-person-name {
  font-weight: 700;
}

.recipient-side-stack {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.recipient-status-panel .card-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.recipient-status-panel .btn {
  width: 100%;
  min-height: 46px;
  border-radius: 14px;
}

.recipient-file-grid,
.recipient-task-grid,
.recipient-related-grid {
  display: grid;
  gap: 10px;
}

@media (max-width: 1100px) {
  .document-layout,
  .document-layout.recipient-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .recipient-hero {
    padding: 20px 18px;
  }

  .recipient-headline {
    font-size: 25px;
  }

  .recipient-actions {
    flex-direction: column;
  }

  .recipient-actions .btn,
  .recipient-actions form,
  .recipient-actions form button {
    width: 100%;
  }
}
</style>
@endpush

@section('topbar-actions')
  <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm">К реестру</a>
  <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨 Печать</button>
@endsection

@section('content')
<div class="document-layout {{ $isRecipientExperience ? 'recipient-layout' : '' }}">
  <div style="display:flex; flex-direction:column; gap:18px;">
    @if($isRecipientExperience)
      <div class="card recipient-hero">
        <div class="recipient-chip-row">
          <span class="badge type-{{ $document->type }}">{{ $document->type_name }}</span>
          <span class="badge status-{{ $document->status }}">{{ $document->status_name }}</span>
          @if($document->deadline && $document->deadline->isFuture())
            <span class="badge status-review">Срок до {{ $document->deadline->format('d.m.Y') }}</span>
          @elseif($document->deadline && $document->deadline->isPast() && $document->status !== 'archive')
            <span class="badge status-rejected">Просрочен с {{ $document->deadline->format('d.m.Y') }}</span>
          @endif
        </div>

        <h2 class="recipient-headline">{{ $document->subject }}</h2>

        <div class="recipient-summary">
          {{ $document->description ?: 'Откройте документ, проверьте маршрут и выполните следующее действие по статусу.' }}
        </div>

        <div class="recipient-meta-grid">
          <div class="recipient-meta-card">
            <div class="recipient-meta-label">Номер документа</div>
            <div class="recipient-meta-value">{{ $document->number }}</div>
          </div>
          <div class="recipient-meta-card">
            <div class="recipient-meta-label">Дата документа</div>
            <div class="recipient-meta-value">{{ $document->doc_date->format('d.m.Y') }}</div>
          </div>
          <div class="recipient-meta-card">
            <div class="recipient-meta-label">Срок исполнения</div>
            <div class="recipient-meta-value">{{ $document->deadline?->format('d.m.Y') ?? 'Не указан' }}</div>
          </div>
        </div>

        <div class="recipient-actions">
          @foreach($availableStatuses as $status)
            <button type="button" class="btn {{ $status === 'approved' ? 'btn-success' : 'btn-primary' }}" onclick="openStatusModal('{{ $status }}', '{{ \App\Models\Document::$statusNames[$status] }}')">
              → {{ \App\Models\Document::$statusNames[$status] }}
            </button>
          @endforeach
          @if($document->files->count())
            <a href="#document-files" class="btn btn-outline">Файлы</a>
          @endif
          <a href="#document-comments" class="btn btn-outline">Комментарии</a>
          @if($currentUser->canEditDocument($document))
            <a href="{{ route('documents.edit', $document) }}" class="btn btn-secondary">Редактировать</a>
          @endif
          @if($currentUser->canDeleteDocument($document))
            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Удалить документ {{ $document->number }}?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger">Удалить</button>
            </form>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title">Участники</div></div>
        <div class="card-body recipient-people">
          <div class="recipient-person">
            <div class="avatar" style="background: {{ avatarColor($document->createdBy?->name ?? 'Создатель') }};">{{ $document->createdBy?->initials ?? 'С' }}</div>
            <div class="recipient-person-copy">
              <div class="recipient-person-label">Постановщик</div>
              <div class="recipient-person-name">{{ $document->createdBy?->name ?? '—' }}</div>
            </div>
          </div>

          @forelse($document->executors as $executor)
            <div class="recipient-person">
              <div class="avatar" style="background: {{ avatarColor($executor->name) }};">{{ $executor->initials }}</div>
              <div class="recipient-person-copy">
                <div class="recipient-person-label">Исполнитель</div>
                <div class="recipient-person-name">
                  {{ $executor->name }}
                  @if($executor->pivot->completed_at)
                    <span class="badge status-approved" style="margin-left:6px;">Готово</span>
                  @else
                    <span class="badge status-review" style="margin-left:6px;">В работе</span>
                  @endif
                </div>
              </div>
            </div>
          @empty
            <div class="recipient-person">
              <div class="avatar" style="background: {{ avatarColor($document->executor?->name ?? 'Исполнитель') }};">{{ $document->executor?->initials ?? 'И' }}</div>
              <div class="recipient-person-copy">
                <div class="recipient-person-label">Исполнитель</div>
                <div class="recipient-person-name">{{ $document->executor?->name ?? '—' }}</div>
              </div>
            </div>
          @endforelse
          @foreach($document->recipients as $recip)
          <div class="recipient-person">
            <div class="avatar" style="background: {{ avatarColor($recip->name) }};">{{ $recip->initials }}</div>
            <div class="recipient-person-copy">
              <div class="recipient-person-label">Получатель</div>
              <div class="recipient-person-name">{{ $recip->name }}</div>
            </div>
          </div>
          @endforeach
          @if($document->recipientGroup && $document->recipients->isEmpty())
          <div class="recipient-person">
            <div class="avatar" style="background: {{ avatarColor($document->recipientGroup->name) }};">Г</div>
            <div class="recipient-person-copy">
              <div class="recipient-person-label">Группа-получатель</div>
              <div class="recipient-person-name">{{ $document->recipientGroup->name }}</div>
            </div>
          </div>
          @endif

          <div class="recipient-person">
            <div class="avatar" style="background: {{ avatarColor($document->sender?->name ?? ($document->sender_org ?? 'Составитель')) }};">{{ $document->sender?->initials ?? 'С' }}</div>
            <div class="recipient-person-copy">
              <div class="recipient-person-label">Составитель</div>
              <div class="recipient-person-name">{{ $document->sender?->name ?? $document->sender_org ?? '—' }}</div>
            </div>
          </div>
        </div>
      </div>
    @else
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
          @if($currentUser->canEditDocument($document) || $currentUser->canDeleteDocument($document))
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:14px;">
              @if($currentUser->canEditDocument($document))
                <a href="{{ route('documents.edit', $document) }}" class="btn btn-secondary">Редактировать</a>
              @endif
              @if($currentUser->canDeleteDocument($document))
                <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Удалить документ {{ $document->number }}?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
              @endif
            </div>
          @endif
        </div>
      </div>
    @endif

    <div class="card">
      <div class="card-header"><div class="card-title">Маршрут</div></div>
      <div class="card-body" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
        <div>
          <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Составитель</div>
          <div>{{ $document->sender?->name ?? $document->sender_org ?? '—' }}</div>
        </div>
        <div>
          <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Получатель</div>
          @if($document->recipients->isNotEmpty())
            <div>{{ $document->recipients->pluck('name')->join(', ') }}</div>
          @elseif($document->recipientGroup)
            <div>{{ $document->recipientGroup->name }}</div>
          @elseif(!empty($document->recipient_orgs))
            <div>{{ implode(', ', $document->recipient_orgs) }}</div>
          @else
            <div>—</div>
          @endif
        </div>
        <div>
          <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Исполнители</div>
          <div>{{ $executorNames }}</div>
        </div>
      </div>
    </div>

    @if($document->executors->isNotEmpty())
      <div class="card">
        <div class="card-header"><div class="card-title">Исполнение</div></div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:10px;">
          @foreach($document->executors as $executor)
            <div class="document-note" style="padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
              <div>
                <div style="font-weight:700;">{{ $executor->name }}</div>
                <div style="font-size:12px; color:var(--text-muted);">
                  @if($executor->pivot->completed_at)
                    Выполнено {{ \Illuminate\Support\Carbon::parse($executor->pivot->completed_at)->format('d.m.Y H:i') }}
                  @else
                    Ожидает выполнения
                  @endif
                </div>
                @if($executor->pivot->completion_comment)
                  <div style="font-size:12px; margin-top:4px;">{{ $executor->pivot->completion_comment }}</div>
                @endif
              </div>
              <span class="badge status-{{ $executor->pivot->completed_at ? 'approved' : 'review' }}">
                {{ $executor->pivot->completed_at ? 'Готово' : 'В работе' }}
              </span>
            </div>
          @endforeach

          @if($canCompleteExecutorPart)
            <button type="button" class="btn btn-success" onclick="openModal('executorCompleteModal')">Отметить мою часть выполненной</button>
          @endif

          @if(!$allExecutorsCompleted && $document->status === 'review')
            <div style="font-size:12px; color:var(--text-muted);">
              Статус “Выполнено” станет доступен после отметки всех исполнителей.
            </div>
          @endif
        </div>
      </div>
    @endif

    @if($relatedDocuments->count())
      <div class="card">
        <div class="card-header"><div class="card-title">Связанные документы</div></div>
        <div class="card-body {{ $isRecipientExperience ? 'recipient-related-grid' : '' }}" style="display:flex; flex-direction:column; gap:10px;">
          @foreach($relatedDocuments as $relatedDocument)
            <a href="{{ route('documents.show', $relatedDocument) }}" class="document-note" style="display:block; padding:12px 14px; text-decoration:none;">
              <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:6px;">
                <span class="badge type-{{ $relatedDocument->type }}">{{ $relatedDocument->type_name }}</span>
                <span class="badge status-{{ $relatedDocument->status }}">{{ $relatedDocument->status_name }}</span>
              </div>
              <div style="font-weight:700; color:var(--text); margin-bottom:4px;">{{ $relatedDocument->number }}</div>
              <div style="color:var(--text); margin-bottom:4px;">{{ $relatedDocument->subject }}</div>
              <div style="font-size:12px; color:var(--text-muted);">
                {{ $relatedDocument->doc_date?->format('d.m.Y') ?? '—' }}
              </div>
            </a>
          @endforeach
        </div>
      </div>
    @endif

    <div class="card">
      <div id="document-files"></div>
      <div class="card-header">
        <div class="card-title">Файлы</div>
        <button class="btn btn-sm btn-primary" type="button" onclick="openModal('uploadModal')">+ Загрузить</button>
      </div>
      <div class="card-body {{ $isRecipientExperience ? 'recipient-file-grid' : '' }}" style="padding:14px 18px;">
        @forelse($document->files as $file)
          <div class="file-item" style="margin-bottom:8px;">
            <span class="file-ext ext-{{ strtolower($file->extension) }}">{{ $file->extension }}</span>
            <span class="file-name">{{ $file->original_name }}</span>
            <span class="file-size">{{ $file->formatted_size }}</span>
            <a href="{{ route('files.view', $file) }}" class="btn btn-sm btn-primary">Открыть</a>
            <a href="{{ route('files.download', $file) }}" class="btn btn-sm btn-secondary">Скачать</a>
          </div>
        @empty
          <div style="color:var(--text-muted);">Файлы не прикреплены</div>
        @endforelse
      </div>
    </div>

    <div class="card">
      <div id="document-comments"></div>
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

  <div class="{{ $isRecipientExperience ? 'recipient-side-stack' : '' }}" style="display:flex; flex-direction:column; gap:18px;">
    <div class="card {{ $isRecipientExperience ? 'recipient-status-panel' : '' }}">
      <div class="card-header"><div class="card-title">Статус</div></div>
      <div class="card-body">
        <div style="text-align:center; margin-bottom:14px;">
          <span class="badge status-{{ $document->status }}" style="font-size:14px; padding:6px 16px;">{{ $document->status_name }}</span>
        </div>
        @foreach($availableStatuses as $status)
          <button type="button" class="btn {{ $status === 'approved' ? 'btn-success' : 'btn-primary' }}" style="width:100%; margin-bottom:8px;" onclick="openStatusModal('{{ $status }}', '{{ \App\Models\Document::$statusNames[$status] }}')">
            → {{ \App\Models\Document::$statusNames[$status] }}
          </button>
        @endforeach
        @if($document->status === 'review' && $document->executors->isNotEmpty() && !$allExecutorsCompleted)
          <div style="font-size:12px; color:var(--text-muted); line-height:1.5;">
            Документ нельзя перевести в “Выполнено”, пока все исполнители не отметили свою часть.
          </div>
        @endif
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
        <div class="card-body {{ $isRecipientExperience ? 'recipient-task-grid' : '' }}" style="display:flex; flex-direction:column; gap:8px;">
          @foreach($document->tasks as $task)
            <div class="document-note document-related-task">
              <div style="font-weight:600;">{{ $task->title }}</div>
              <div style="font-size:12px; color:var(--text-muted);">{{ $task->assignee->name }}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- Registry card --}}
    @if($currentUser->hasAnyRole(['admin', 'clerk', 'manager']) && $registryDepartments->isNotEmpty())
      <div class="card">
        <div class="card-header">
          <div class="card-title">Реестр отделов</div>
          <button type="button" class="btn btn-sm btn-primary" onclick="openModal('registryModal')">+ В реестр</button>
        </div>
        <div class="card-body">
          @if($registryEntries->isEmpty())
            <div style="color:var(--text-muted); font-size:13px;">Не добавлен ни в один реестр</div>
          @else
            @foreach($registryEntries as $re)
              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:6px 0; border-bottom:1px solid var(--border); font-size:13px;">
                <div>
                  <span style="font-weight:600;">{{ $re->department->name }}</span>
                  @if($re->pinned) <span title="Закреплён">⭐</span> @endif
                  @if($re->note)
                    <div style="font-size:12px; color:var(--text-muted);">{{ $re->note }}</div>
                  @endif
                </div>
                <a href="{{ route('registry.index', ['dept' => $re->department_id]) }}" class="btn btn-sm btn-secondary">Открыть</a>
              </div>
            @endforeach
          @endif
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
          <label class="form-label">Комментарий <span style="color:var(--accent);">*</span></label>
          <textarea name="comment" class="form-control" rows="3" required minlength="3" placeholder="Обязательно укажите причину изменения статуса..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Подтвердить</button>
      </div>
    </form>
  </div>
</div>

@if($canCompleteExecutorPart)
<div class="modal-overlay" id="executorCompleteModal" onclick="if(event.target===this)closeModal('executorCompleteModal')">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <div class="modal-title">Отметить выполнение</div>
      <button class="modal-close" type="button" onclick="closeModal('executorCompleteModal')">×</button>
    </div>
    <form method="POST" action="{{ route('documents.executor-complete', $document) }}">
      @csrf
      @method('PATCH')
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Комментарий <span style="font-weight:400; color:var(--text-muted);">(необязательно)</span></label>
          <textarea name="completion_comment" class="form-control" rows="3" placeholder="Что выполнено по вашей части"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('executorCompleteModal')">Отмена</button>
        <button type="submit" class="btn btn-success">Отметить выполненной</button>
      </div>
    </form>
  </div>
</div>
@endif

@if($currentUser->hasAnyRole(['admin', 'clerk', 'manager']) && $registryDepartments->isNotEmpty())
<div class="modal-overlay" id="registryModal" onclick="if(event.target===this)closeModal('registryModal')">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <div class="modal-title">Добавить в реестр отдела</div>
      <button class="modal-close" type="button" onclick="closeModal('registryModal')">×</button>
    </div>
    <form method="POST" action="{{ route('registry.store') }}">
      @csrf
      <input type="hidden" name="document_id" value="{{ $document->id }}">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Отдел</label>
          <select name="department_id" class="form-control" required>
            <option value="">— выберите отдел —</option>
            @foreach($registryDepartments as $dept)
              <option value="{{ $dept->id }}"
                {{ $registryEntries->contains('department_id', $dept->id) ? 'disabled' : '' }}>
                {{ $dept->name }}
                {{ $registryEntries->contains('department_id', $dept->id) ? '(уже добавлен)' : '' }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Примечание <span style="font-weight:400; color:var(--text-muted);">(необязательно)</span></label>
          <textarea name="note" class="form-control" rows="3" placeholder="Краткое описание или пометка..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('registryModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Добавить в реестр</button>
      </div>
    </form>
  </div>
</div>
@endif

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
