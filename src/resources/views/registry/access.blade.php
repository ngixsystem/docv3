@extends('layouts.app')
@section('page-title', 'Доступ к реестрам')

@push('styles')
<style>
.access-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 18px;
}

.access-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}

.access-card-header {
  padding: 14px 18px 12px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.access-dept-name {
  font-weight: 700;
  font-size: 14.5px;
  color: var(--text);
}

.access-dept-code {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 7px;
  background: var(--accent-soft);
  color: var(--accent);
  border-radius: 6px;
  font-family: 'Fira Code', monospace;
}

.access-users-list {
  padding: 12px 18px;
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  min-height: 48px;
}

.access-user-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 6px 4px 10px;
  background: var(--chip-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  font-size: 12.5px;
  color: var(--text);
  white-space: nowrap;
}

.access-user-chip .chip-dept {
  font-size: 11px;
  color: var(--text-muted);
}

.access-user-chip button {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  font-size: 14px;
  line-height: 1;
  padding: 0 2px;
  border-radius: 50%;
  transition: color .15s, background .15s;
  display: flex;
  align-items: center;
}

.access-user-chip button:hover {
  color: var(--accent);
  background: var(--accent-soft);
}

.access-no-users {
  color: var(--text-muted);
  font-size: 12.5px;
  font-style: italic;
  padding: 4px 0;
}

.access-add-row {
  padding: 10px 18px 14px;
  border-top: 1px solid var(--border);
  display: flex;
  gap: 8px;
  align-items: stretch;
}

.access-add-row .user-select {
  flex: 1;
  min-width: 0;
  padding: 8px 12px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-size: 13px;
  background: var(--input-bg);
  color: var(--text);
}

.access-add-row .user-select:focus {
  outline: none;
  border-color: var(--accent);
}

.access-count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  background: var(--surface-soft);
  border: 1px solid var(--border);
  border-radius: 10px;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-muted);
}
</style>
@endpush

@section('topbar-actions')
  <a href="{{ route('registry.index') }}" class="btn btn-secondary">← Реестр</a>
@endsection

@section('content')

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:18px;">{{ session('success') }}</div>
@endif

<div style="margin-bottom:20px;">
  <p style="color:var(--text-muted); font-size:13.5px;">
    Управляйте тем, кто может просматривать реестр каждого отдела.
    Администраторы и делопроизводители имеют доступ ко всем реестрам по умолчанию.
  </p>
</div>

<div class="access-grid">
  @foreach($departments as $dept)
    @php
      $accesses = $dept->registryAccesses;
      $grantedIds = $accesses->pluck('user_id')->toArray();
      $available = $allUsers->whereNotIn('id', $grantedIds)->values();
    @endphp

    <div class="access-card">
      <div class="access-card-header">
        <div>
          <div class="access-dept-name">{{ $dept->name }}</div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          @if($dept->code)
            <span class="access-dept-code">{{ $dept->code }}</span>
          @endif
          <span class="access-count-badge">{{ $accesses->count() }}</span>
        </div>
      </div>

      <div class="access-users-list">
        @forelse($accesses as $access)
          <div class="access-user-chip">
            <div>
              <div>{{ $access->user->name }}</div>
              @if($access->user->department)
                <div class="chip-dept">{{ $access->user->department->name }}</div>
              @endif
            </div>
            <form method="POST" action="{{ route('registry.access.revoke', $access) }}" style="display:inline;">
              @csrf
              @method('DELETE')
              <button type="submit" title="Отозвать доступ">×</button>
            </form>
          </div>
        @empty
          <span class="access-no-users">Нет пользователей с доступом</span>
        @endforelse
      </div>

      @if($available->isNotEmpty())
        <div class="access-add-row">
          <form method="POST" action="{{ route('registry.access.grant') }}" style="display:contents;">
            @csrf
            <input type="hidden" name="department_id" value="{{ $dept->id }}">
            <select name="user_id" class="user-select" required>
              <option value="">— Выбрать пользователя —</option>
              @foreach($available as $u)
                <option value="{{ $u->id }}">
                  {{ $u->name }}{{ $u->department ? ' · ' . $u->department->name : '' }}
                </option>
              @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap; padding:8px 14px;">
              + Добавить
            </button>
          </form>
        </div>
      @else
        <div style="padding:10px 18px 14px; border-top:1px solid var(--border);">
          <span style="font-size:12px; color:var(--text-muted);">Все пользователи уже имеют доступ</span>
        </div>
      @endif
    </div>
  @endforeach
</div>

@endsection
