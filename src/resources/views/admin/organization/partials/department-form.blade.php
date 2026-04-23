<div class="form-group">
  <label class="form-label">Название</label>
  <input class="form-control" type="text" name="name" value="{{ old('name', $departmentItem?->name) }}" required>
</div>

<div class="form-group">
  <label class="form-label">Описание</label>
  <textarea class="form-control" name="description">{{ old('description', $departmentItem?->description) }}</textarea>
</div>

<div class="form-row">
  <div class="form-group">
    <label class="form-label">Код</label>
    <input class="form-control" type="text" name="code" value="{{ old('code', $departmentItem?->code) }}">
  </div>
  <div class="form-group">
    <label class="form-label">Руководитель</label>
    <select class="form-control" name="head_id">
      <option value="">Не назначен</option>
      @foreach($users as $user)
        <option value="{{ $user->id }}" @selected((string) old('head_id', $departmentItem?->head_id) === (string) $user->id)>{{ $user->name }}</option>
      @endforeach
    </select>
  </div>
</div>
