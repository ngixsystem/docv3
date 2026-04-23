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
  'new' => ['label' => 'Новая', 'dot' => '#6c757d', 'class' => 'kanban-col-new'],
  'in_progress' => ['label' => 'В работе', 'dot' => '#0d6efd', 'class' => 'kanban-col-progress'],
  'paused' => ['label' => 'Приостановлена', 'dot' => '#fd7e14', 'class' => 'kanban-col-paused'],
  'done' => ['label' => 'Выполнена', 'dot' => '#198754', 'class' => 'kanban-col-done'],
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

html[data-theme="dark"] .kanban-col-new {
  background: linear-gradient(180deg, rgba(26,37,57,.96) 0%, rgba(20,29,45,.96) 100%);
  border-color: #2a3952;
}

html[data-theme="dark"] .kanban-col-progress {
  background: linear-gradient(180deg, rgba(21,36,64,.96) 0%, rgba(17,29,50,.96) 100%);
  border-color: #29406a;
}

html[data-theme="dark"] .kanban-col-paused {
  background: linear-gradient(180deg, rgba(58,38,26,.96) 0%, rgba(42,29,20,.96) 100%);
  border-color: #5b3b23;
}

html[data-theme="dark"] .kanban-col-done {
  background: linear-gradient(180deg, rgba(24,43,35,.96) 0%, rgba(18,33,27,.96) 100%);
  border-color: #2e5a48;
}

html[data-theme="dark"] .kanban-col .kanban-header {
  border-bottom-color: rgba(255,255,255,.08);
}

html[data-theme="dark"] .kanban-col .kanban-count {
  background: rgba(255,255,255,.08);
  color: #d7e2f4;
}

html[data-theme="dark"] .task-card {
  background: rgba(15,25,43,.92);
  border-color: rgba(120,148,194,.18);
  box-shadow: 0 10px 28px rgba(0,0,0,.22);
}

html[data-theme="dark"] .task-card:hover {
  border-color: rgba(255,255,255,.18);
}

html[data-theme="dark"] .btn-secondary {
  background: rgba(255,255,255,.08);
  color: #eef4ff;
}

html[data-theme="dark"] .btn-secondary:hover {
  background: rgba(255,255,255,.12);
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
      <div class="kanban-cards">
        @foreach($columns[$status] as $task)
          <div class="task-card {{ $task->is_overdue ? 'overdue' : '' }}">
            <div class="task-title">{{ $task->title }}</div>
            <div class="task-meta">
              <span class="badge priority-{{ $task->priority }}">{{ $task->priority_name }}</span>
              @if($task->is_overdue)
                <span class="overdue-badge">Просрочена</span>
              @endif
            </div>
            @if($task->description)
              <div style="font-size:12px; color:var(--text-muted); margin-bottom:8px;">{{ Str::limit($task->description, 90) }}</div>
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
                    <button class="btn btn-sm btn-secondary" type="button" onclick="changeTaskStatus({{ $task->id }}, '{{ $target }}')">{{ $targetCol['label'] }}</button>
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
                  <option value="{{ $document->id }}">{{ $document->number }} — {{ Str::limit($document->subject, 40) }}</option>
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
@endsection
