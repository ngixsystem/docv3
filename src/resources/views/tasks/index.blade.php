@extends('layouts.app')
@section('page-title', 'Менеджер задач')

@section('topbar-actions')
  @can('create-tasks')
    <button class="btn btn-primary" type="button" onclick="openModal('createTaskModal')">+ Новая задача</button>
  @endcan
@endsection

@section('content')
@php
$cols = [
  'new' => ['label' => 'Новая', 'dot' => '#555555', 'class' => 'kanban-col-new'],
  'in_progress' => ['label' => 'В работе', 'dot' => '#9b1c1c', 'class' => 'kanban-col-progress'],
  'paused' => ['label' => 'Приостановлена', 'dot' => '#c2410c', 'class' => 'kanban-col-paused'],
  'done' => ['label' => 'Выполнена', 'dot' => '#15803d', 'class' => 'kanban-col-done'],
];
@endphp

@push('styles')
<style>
.kanban-col-new {
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f9 100%);
}

.kanban-col-progress {
  background: linear-gradient(180deg, #f4f8ff 0%, #eaf2ff 100%);
}

.kanban-col-paused {
  background: linear-gradient(180deg, #fff8f0 0%, #fff2e4 100%);
}

.kanban-col-done {
  background: linear-gradient(180deg, #f4fcf7 0%, #e8f8ed 100%);
}

.task-card-clickable {
  cursor: pointer;
  transition: transform var(--transition), border-color var(--transition), box-shadow var(--transition);
}

.task-card-clickable:hover {
  transform: translateY(-2px);
  border-color: color-mix(in srgb, var(--accent) 35%, var(--border));
}

.task-card-clickable:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

html[data-theme="dark"] .kanban-col-new {
  background: linear-gradient(180deg, #141414 0%, #111111 100%);
  border-color: #272727;
}

html[data-theme="dark"] .kanban-col-progress {
  background: linear-gradient(180deg, #161414 0%, #121111 100%);
  border-color: #2e2020;
}

html[data-theme="dark"] .kanban-col-paused {
  background: linear-gradient(180deg, #151414 0%, #111111 100%);
  border-color: #272727;
}

html[data-theme="dark"] .kanban-col-done {
  background: linear-gradient(180deg, #141414 0%, #111111 100%);
  border-color: #272727;
}

html[data-theme="dark"] .kanban-col .kanban-header {
  border-bottom-color: rgba(255,255,255,.07);
}

html[data-theme="dark"] .kanban-col .kanban-count {
  background: rgba(255,255,255,.07);
  color: #888888;
}

html[data-theme="dark"] .task-card {
  background: #1a1a1a;
  border-color: #2a2a2a;
  box-shadow: 0 4px 16px rgba(0,0,0,.4);
}

html[data-theme="dark"] .task-card:hover {
  border-color: rgba(185,28,28,.35);
  box-shadow: 0 6px 20px rgba(0,0,0,.5);
}

html[data-theme="dark"] .btn-secondary {
  background: rgba(255,255,255,.07);
  color: #cccccc;
}

html[data-theme="dark"] .btn-secondary:hover {
  background: rgba(255,255,255,.11);
}

.kanban-cards.drag-over {
  background: color-mix(in srgb, var(--accent) 8%, transparent);
  outline: 2px dashed var(--accent);
  outline-offset: -4px;
  border-radius: 10px;
}

.task-card.dragging {
  opacity: .4;
  cursor: grabbing;
}

.task-card[draggable="true"] {
  cursor: grab;
}
</style>
@endpush

<div class="kanban">
  @foreach($cols as $status => $col)
    <div class="kanban-col {{ $col['class'] }}">
      <div class="kanban-header">
        <div class="kanban-dot" style="background:{{ $col['dot'] }};"></div>
        <div class="kanban-title">{{ $col['label'] }}</div>
        <div class="kanban-count">{{ $columns[$status]->count() }}</div>
      </div>
      <div class="kanban-cards" data-status="{{ $status }}">
        @foreach($columns[$status] as $task)
          <div
            class="task-card task-card-clickable {{ $task->is_overdue ? 'overdue' : '' }}"
            role="link"
            tabindex="0"
            data-task-id="{{ $task->id }}"
            @if($currentUser->canChangeTask($task)) draggable="true" @endif
            onclick="openTask('{{ route('tasks.show', $task) }}')"
            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openTask('{{ route('tasks.show', $task) }}'); }"
          >
            <div class="task-title">{{ $task->title }}</div>
            <div class="task-meta">
              <span class="badge priority-{{ $task->priority }}">{{ $task->priority_name }}</span>
              @if($task->is_overdue)
                <span class="overdue-badge">Просрочена</span>
              @endif
            </div>
            @if($task->description)
              <div style="font-size:12px; color:var(--text-muted); margin-bottom:8px;">{{ \Illuminate\Support\Str::limit($task->description, 90) }}</div>
            @endif
            <div class="task-footer">
              <div class="assignee-chip">
                <div class="avatar avatar-sm" style="background: {{ avatarColor($task->assignee->name) }};">{{ $task->assignee->initials }}</div>
                {{ $task->assignee->short_name }}
              </div>
              @if($task->deadline)
                <div class="deadline-chip {{ $task->is_overdue ? 'late' : '' }}">{{ $task->deadline->format('d.m') }}</div>
              @endif
            </div>
            @if($task->document)
              <div style="font-size:11px; color:var(--text-muted); margin-top:6px;">{{ $task->document->number }}</div>
            @endif
            @if($currentUser->canChangeTask($task))
              <div class="task-actions">
                @foreach($cols as $target => $targetCol)
                  @if($target !== $status)
                    <button class="btn btn-sm btn-secondary" type="button" onclick="event.stopPropagation(); changeTaskStatus({{ $task->id }}, '{{ $target }}')">{{ $targetCol['label'] }}</button>
                  @endif
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  @endforeach
</div>

@can('create-tasks')
  <div class="modal-overlay" id="createTaskModal" onclick="if(event.target===this)closeModal('createTaskModal')">
    <div class="modal">
      <div class="modal-header">
        <div class="modal-title">Новая задача</div>
        <button class="modal-close" type="button" onclick="closeModal('createTaskModal')">×</button>
      </div>
      <form method="POST" action="{{ route('tasks.store') }}">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Название задачи</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Описание</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Исполнитель</label>
              <select name="assignee_id" class="form-control" required>
                @foreach($users as $taskUser)
                  <option value="{{ $taskUser->id }}">{{ $taskUser->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Приоритет</label>
              <select name="priority" class="form-control" required>
                <option value="low">Низкий</option>
                <option value="medium">Средний</option>
                <option value="high">Высокий</option>
                <option value="urgent">Срочный</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Срок</label>
              <input type="date" name="deadline" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Связанный документ</label>
              <select name="document_id" class="form-control">
                <option value="">—</option>
                @foreach($documents as $document)
                  <option value="{{ $document->id }}">{{ $document->number }} — {{ \Illuminate\Support\Str::limit($document->subject, 40) }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('createTaskModal')">Отмена</button>
          <button type="submit" class="btn btn-primary">Создать задачу</button>
        </div>
      </form>
    </div>
  </div>
@endcan

@push('scripts')
<script>
function openTask(url) {
  window.location.href = url;
}

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

// Drag & drop
let draggingCard = null;

document.querySelectorAll('.task-card[draggable="true"]').forEach(card => {
  card.addEventListener('dragstart', e => {
    draggingCard = card;
    card.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  card.addEventListener('dragend', () => {
    card.classList.remove('dragging');
    draggingCard = null;
    document.querySelectorAll('.kanban-cards').forEach(c => c.classList.remove('drag-over'));
  });
});

document.querySelectorAll('.kanban-cards').forEach(col => {
  col.addEventListener('dragover', e => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    col.classList.add('drag-over');
  });
  col.addEventListener('dragleave', e => {
    if (!col.contains(e.relatedTarget)) col.classList.remove('drag-over');
  });
  col.addEventListener('drop', e => {
    e.preventDefault();
    col.classList.remove('drag-over');
    if (!draggingCard) return;
    const targetStatus = col.dataset.status;
    const taskId = draggingCard.dataset.taskId;
    changeTaskStatus(taskId, targetStatus);
  });
});
</script>
@endpush
@endsection
