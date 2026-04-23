@php
  $editing = isset($userItem) && $userItem;
@endphp

<div class="form-row">
  <div class="form-group">
    <label class="form-label">ФИО</label>
    <input class="form-control" type="text" name="name" value="{{ old('name', $userItem?->name) }}" required>
  </div>
  <div class="form-group">
    <label class="form-label">Логин</label>
    <input class="form-control" type="text" name="login" value="{{ old('login', $userItem?->login) }}" required>
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label class="form-label">{{ $editing ? 'Новый пароль' : 'Пароль' }}</label>
    <input class="form-control" type="password" name="password" {{ $editing ? '' : 'required' }}>
  </div>
  <div class="form-group">
    <label class="form-label">Роль</label>
    <select class="form-control" name="role" required>
      @foreach(\App\Models\User::$roleNames as $role => $label)
        <option value="{{ $role }}" @selected(old('role', $userItem?->role) === $role)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label class="form-label">Отдел</label>
    <select class="form-control" name="department_id" required>
      @foreach($departments as $department)
        <option value="{{ $department->id }}" @selected((string) old('department_id', $userItem?->department_id) === (string) $department->id)>{{ $department->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group">
    <label class="form-label">Должность</label>
    <input class="form-control" type="text" name="position" value="{{ old('position', $userItem?->position) }}">
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label class="form-label">Email</label>
    <input class="form-control" type="email" name="email" value="{{ old('email', $userItem?->email) }}">
  </div>
  <div class="form-group">
    <label class="form-label">Телефон</label>
    <input class="form-control" type="text" name="phone" value="{{ old('phone', $userItem?->phone) }}">
  </div>
</div>

<div class="form-group">
  <label class="form-label">Группы</label>
  <select class="form-control" name="group_ids[]" multiple size="4">
    @php
      $selectedGroups = old('group_ids', $editing ? $userItem->groups->pluck('id')->all() : []);
    @endphp
    @foreach($groups as $group)
      <option value="{{ $group->id }}" @selected(in_array($group->id, $selectedGroups))>{{ $group->name }}</option>
    @endforeach
  </select>
</div>

<div class="form-group" style="margin-bottom:0;">
  <label style="display:flex; align-items:center; gap:8px; font-size:13px;">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $editing ? $userItem->is_active : true) ? 'checked' : '' }}>
    Учетная запись активна
  </label>
</div>
