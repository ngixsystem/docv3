@extends('layouts.app')
@section('page-title', 'Новый документ')

@section('topbar-actions')
  <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm">Назад</a>
@endsection

@section('content')
<div style="max-width:860px; margin:0 auto;">
  <div class="card">
    <div class="card-header"><div class="card-title">Создание документа</div></div>
    <div class="card-body">
      <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" id="createForm">
        @csrf

        <div class="form-group">
          <label class="form-label">Тип документа</label>
          <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px;">
            @foreach($user->allowedDocumentTypes() as $type)
              <label style="cursor:pointer;">
                <input type="radio" name="type" value="{{ $type }}" {{ old('type', $user->allowedDocumentTypes()[0]) === $type ? 'checked' : '' }} style="display:none;" class="type-radio">
                <div class="type-opt" data-val="{{ $type }}" style="padding:14px; border:2px solid var(--border); border-radius:10px; text-align:center;">
                  <div style="font-size:13px; font-weight:600;">{{ \App\Models\Document::$typeNames[$type] }}</div>
                </div>
              </label>
            @endforeach
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Тема</label>
          <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Дата документа</label>
            <input type="date" name="doc_date" class="form-control" value="{{ old('doc_date', date('Y-m-d')) }}" required>
          </div>
          <div class="form-group">
            <label class="form-label">Срок исполнения</label>
            <input type="date" name="deadline" class="form-control" value="{{ old('deadline') }}">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Отправитель (сотрудник)</label>
            <select name="sender_id" class="form-control">
              <option value="">—</option>
              @foreach($users as $item)
                <option value="{{ $item->id }}" @selected(old('sender_id') == $item->id)>{{ $item->name }} ({{ $item->department?->name }})</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Отправитель (организация)</label>
            <input type="text" name="sender_org" class="form-control" value="{{ old('sender_org') }}">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Получатель (сотрудник)</label>
            <select name="recipient_id" class="form-control">
              <option value="">—</option>
              @foreach($users as $item)
                <option value="{{ $item->id }}" @selected(old('recipient_id') == $item->id)>{{ $item->name }} ({{ $item->department?->name }})</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Получатель (организация)</label>
            <input type="text" name="recipient_org" class="form-control" value="{{ old('recipient_org') }}">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Получатель (группа)</label>
            <select name="recipient_group_id" class="form-control">
              <option value="">—</option>
              @foreach($groups as $group)
                <option value="{{ $group->id }}" @selected(old('recipient_group_id') == $group->id)>{{ $group->name }} ({{ $group->users_count }})</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Исполнитель</label>
            <select name="executor_id" class="form-control">
              <option value="">—</option>
              @foreach($users as $item)
                <option value="{{ $item->id }}" @selected(old('executor_id') == $item->id)>{{ $item->name }} ({{ $item->department?->name }})</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Описание</label>
          <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Файлы</label>
          <div class="dropzone" id="dropzone" onclick="document.getElementById('fileInput').click()">
            Нажмите или перетащите файлы
          </div>
          <input type="file" id="fileInput" name="files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" style="display:none;">
          <div class="file-list" id="fileList"></div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
          <a href="{{ route('documents.index') }}" class="btn btn-secondary">Отмена</a>
          <button type="submit" class="btn btn-primary">Создать документ</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
const dz = document.getElementById('dropzone');
const fi = document.getElementById('fileInput');
const fl = document.getElementById('fileList');
let files = [];

dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
dz.addEventListener('drop', e => { e.preventDefault(); dz.classList.remove('drag-over'); addFiles(e.dataTransfer.files); });
fi.addEventListener('change', () => addFiles(fi.files));

function addFiles(fileList) {
  Array.from(fileList).forEach(f => files.push(f));
  renderFiles();
  updateInput();
}
function removeFile(i) {
  files.splice(i, 1);
  renderFiles();
  updateInput();
}
function renderFiles() {
  fl.innerHTML = files.map((f, i) => {
    const ext = f.name.split('.').pop().toLowerCase();
    const size = f.size < 1048576 ? (f.size / 1024).toFixed(1) + ' КБ' : (f.size / 1048576).toFixed(1) + ' МБ';
    return `<div class="file-item"><span class="file-ext ext-${ext}">${ext.toUpperCase()}</span><span class="file-name">${f.name}</span><span class="file-size">${size}</span><button class="file-remove" type="button" onclick="removeFile(${i})">×</button></div>`;
  }).join('');
}
function updateInput() {
  const dt = new DataTransfer();
  files.forEach(f => dt.items.add(f));
  fi.files = dt.files;
}
</script>
@endpush
@endsection
