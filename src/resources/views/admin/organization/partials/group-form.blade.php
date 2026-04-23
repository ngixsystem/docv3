@php
  $selectedUsers = old('user_ids', isset($groupItem) && $groupItem ? $groupItem->users->pluck('id')->all() : []);
@endphp

<div class="form-group">
  <label class="form-label">Название</label>
  <input class="form-control" type="text" name="name" value="{{ old('name', $groupItem?->name) }}" required>
</div>

<div class="form-group">
  <label class="form-label">Описание</label>
  <textarea class="form-control" name="description">{{ old('description', $groupItem?->description) }}</textarea>
</div>

<div class="form-group" style="margin-bottom:0;">
  <label class="form-label">Участники</label>
  <select class="form-control" name="user_ids[]" multiple size="8">
    @foreach($users as $user)
      <option value="{{ $user->id }}" @selected(in_array($user->id, $selectedUsers))>{{ $user->name }}</option>
    @endforeach
  </select>
</div>
