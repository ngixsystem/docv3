@extends('layouts.app')
@section('page-title', 'Мой профиль')

@section('content')
<div style="max-width:640px; margin:0 auto; display:flex; flex-direction:column; gap:20px;">

  <div class="card">
    <div class="card-header">
      <div class="card-title">Основные данные</div>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PUT')
        <div class="form-group">
          <label class="form-label">Полное имя</label>
          <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
          </div>
          <div class="form-group">
            <label class="form-label">Телефон</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Должность</label>
          <input type="text" name="position" class="form-control" value="{{ old('position', $user->position) }}">
        </div>
        <div style="display:flex; gap:10px; margin-top:4px;">
          <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Изменить пароль</div>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('profile.password') }}">
        @csrf
        @method('PUT')
        <div class="form-group">
          <label class="form-label">Текущий пароль</label>
          <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Новый пароль</label>
            <input type="password" name="password" class="form-control" required autocomplete="new-password">
          </div>
          <div class="form-group">
            <label class="form-label">Повторите пароль</label>
            <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Изменить пароль</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Информация об аккаунте</div></div>
    <div class="card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; font-size:13.5px;">
      <div>
        <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Логин</div>
        <code>{{ $user->login }}</code>
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Роль</div>
        <span class="role-badge role-{{ $user->role }}">{{ $user->role_name }}</span>
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Отдел</div>
        {{ $user->department?->name ?? '—' }}
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Аккаунт создан</div>
        {{ $user->created_at->format('d.m.Y') }}
      </div>
    </div>
  </div>

</div>
@endsection
