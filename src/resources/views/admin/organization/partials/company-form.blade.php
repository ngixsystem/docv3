<div class="form-group">
  <label class="form-label">Название компании</label>
  <input class="form-control" type="text" name="name" value="{{ old('name', $companyItem?->name) }}" required>
</div>

<div class="form-group" style="margin-bottom:0;">
  <label class="form-label">Подробная информация</label>
  <textarea class="form-control" name="details" rows="5" placeholder="Например: адрес, контакты, ИНН, комментарии">{{ old('details', $companyItem?->details) }}</textarea>
</div>
