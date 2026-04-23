@extends('layouts.app')
@section('page-title', 'Пользователи')

@php
  $roleClasses = [
    'admin' => 'role-admin',
    'manager' => 'role-manager',
    'clerk' => 'role-clerk',
    'employee' => 'role-employee',
  ];
@endphp

@section('topbar-actions')
  <button type="button" class="btn btn-primary" onclick="openModal('createUserModal')">+ Добавить пользователя</button>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <div class="card-title">Все пользователи</div>
    <div style="font-size:12px; color:var(--text-muted);">{{ $users->count() }} записей</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ФИО</th>
          <th>Логин</th>
          <th>Роль</th>
          <th>Отдел</th>
          <th>Статус</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $user)
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <div class="avatar" style="background: {{ avatarColor($user->name) }};">{{ $user->initials }}</div>
                <div>
                  <div style="font-weight:600;">{{ $user->name }}</div>
                  <div style="font-size:12px; color:var(--text-muted);">{{ $user->position ?: 'Без должности' }}</div>
                </div>
              </div>
            </td>
            <td><code>{{ $user->login }}</code></td>
            <td><span class="role-badge {{ $roleClasses[$user->role] ?? 'role-employee' }}">{{ $user->role_name }}</span></td>
            <td>{{ $user->department?->name ?? '—' }}</td>
            <td>
              <span class="status-pill {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                {{ $user->is_active ? 'Активен' : 'Неактивен' }}
              </span>
            </td>
            <td>
              <div style="display:flex; gap:6px; flex-wrap:wrap;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="openModal('editUserModal-{{ $user->id }}')">Редактировать</button>
                <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-warning' : 'btn-success' }}">
                    {{ $user->is_active ? 'Деактивировать' : 'Активировать' }}
                  </button>
                </form>
                <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Удалить пользователя?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="createUserModal" onclick="if(event.target===this)closeModal('createUserModal')">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Новый пользователь</div>
      <button class="modal-close" type="button" onclick="closeModal('createUserModal')">×</button>
    </div>
    <form method="POST" action="{{ route('users.store') }}">
      @csrf
      <div class="modal-body">
        @include('users.partials.form', ['userItem' => null, 'departments' => $departments, 'groups' => $groups])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('createUserModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Создать</button>
      </div>
    </form>
  </div>
</div>

@foreach($users as $user)
  <div class="modal-overlay" id="editUserModal-{{ $user->id }}" onclick="if(event.target===this)closeModal('editUserModal-{{ $user->id }}')">
    <div class="modal">
      <div class="modal-header">
        <div class="modal-title">Редактирование пользователя</div>
        <button class="modal-close" type="button" onclick="closeModal('editUserModal-{{ $user->id }}')">×</button>
      </div>
      <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PATCH')
        <div class="modal-body">
          @include('users.partials.form', ['userItem' => $user, 'departments' => $departments, 'groups' => $groups])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal-{{ $user->id }}')">Отмена</button>
          <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
      </form>
    </div>
  </div>
@endforeach
@endsection
