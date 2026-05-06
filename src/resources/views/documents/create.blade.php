@extends('layouts.app')
@section('page-title', 'Новый документ')

@section('topbar-actions')
  <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm">Назад</a>
@endsection

@push('styles')
<style>
.type-opt {
  transition: border-color var(--transition), background var(--transition), color var(--transition), box-shadow var(--transition), transform var(--transition);
}

.type-opt.is-active {
  border-color: var(--accent) !important;
  background: var(--accent-soft);
  color: var(--accent);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 30%, transparent);
  transform: translateY(-1px);
}

.multi-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px 3px 12px;
  background: var(--accent-soft);
  border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
  border-radius: 20px;
  font-size: 13px;
  color: var(--accent);
}

.multi-tag button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0 2px;
  line-height: 1;
  font-size: 16px;
  color: var(--accent);
  opacity: 0.6;
}

.multi-tag button:hover { opacity: 1; }
</style>
@endpush

@section('content')
@php
  $oldRelatedDocumentIds = collect(old('related_document_ids', []))->map(fn ($id) => (string) $id)->all();
@endphp
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
            <label class="form-label">Составитель</label>
            <div class="user-combobox">
              <input
                type="text"
                id="senderComboInput"
                class="form-control user-combobox-input"
                placeholder="Введите имя или выберите из списка"
                autocomplete="off"
                value="{{ old('sender_id') ? ($users->find(old('sender_id'))?->name ?? '') : '' }}"
              >
              <input type="hidden" name="sender_id" id="senderComboId" value="{{ old('sender_id') }}">
              <div class="user-combobox-dropdown" id="senderComboDropdown"></div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Отправитель (организация)</label>
            <input type="text" name="sender_org" class="form-control" value="{{ old('sender_org') }}" list="senderCompaniesList" placeholder="Выберите компанию из справочника или введите вручную">
            <datalist id="senderCompaniesList">
              @foreach($companies as $company)
                <option value="{{ $company->name }}">{{ $company->details ? \Illuminate\Support\Str::limit($company->details, 80) : '' }}</option>
              @endforeach
            </datalist>
            <div style="margin-top:6px; font-size:12px; color:var(--text-muted);">
              Справочник компаний-отправителей заполняет администратор в разделе организации.
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group memo-hide-recipient">
            <label class="form-label">Получатель (сотрудники)</label>
            <div id="recipientTagsWrap" style="display:flex; flex-wrap:wrap; gap:6px; min-height:28px; margin-bottom:8px;"></div>
            <div class="user-combobox">
              <input type="text" id="recipientSearchInput" class="form-control user-combobox-input"
                placeholder="Добавить получателя..." autocomplete="off">
              <div class="user-combobox-dropdown" id="recipientSearchDropdown"></div>
            </div>
          </div>
          <div class="form-group memo-hide-recipient">
            <label class="form-label">Получатель (организации)</label>
            <div id="recipientOrgTagsWrap" style="display:flex; flex-wrap:wrap; gap:6px; min-height:28px; margin-bottom:8px;"></div>
            <div style="display:flex; gap:6px;">
              <input type="text" id="recipientOrgInput" class="form-control" placeholder="Введите организацию и нажмите +" list="recipientCompaniesList" autocomplete="off">
              <button type="button" class="btn btn-secondary" onclick="addRecipientOrg()">+</button>
            </div>
            <datalist id="recipientCompaniesList">
              @foreach($companies as $company)
                <option value="{{ $company->name }}">
              @endforeach
            </datalist>
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
            <label class="form-label">Исполнители</label>
            <div id="executorTagsWrap" style="display:flex; flex-wrap:wrap; gap:6px; min-height:28px; margin-bottom:8px;"></div>
            <div class="user-combobox">
              <input type="text" id="executorSearchInput" class="form-control user-combobox-input"
                placeholder="Добавить исполнителя..." autocomplete="off">
              <div class="user-combobox-dropdown" id="executorSearchDropdown"></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Описание</label>
          <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Связанные документы</label>
          <input
            type="text"
            id="relatedDocumentSearch"
            class="form-control"
            placeholder="Поиск по номеру, теме или дате"
            style="margin-bottom:8px;"
          >
          <div class="doc-type-filters" style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px;">
            <button type="button" class="doc-type-filter active" data-type="">Все</button>
            <button type="button" class="doc-type-filter" data-type="incoming">Входящее</button>
            <button type="button" class="doc-type-filter" data-type="outgoing">Исходящее</button>
            <button type="button" class="doc-type-filter" data-type="memo">Служебная записка</button>
            <button type="button" class="doc-type-filter" data-type="internal">Внутренний</button>
          </div>
          <select
            name="related_document_ids[]"
            id="relatedDocumentsSelect"
            class="form-control"
            multiple
            size="{{ min(max($relatedDocuments->count(), 4), 8) }}"
          >
            @foreach($relatedDocuments as $relatedDocument)
              <option
                value="{{ $relatedDocument->id }}"
                data-type="{{ $relatedDocument->type }}"
                data-search="{{ mb_strtolower($relatedDocument->number . ' ' . $relatedDocument->subject . ' ' . $relatedDocument->doc_date?->format('d.m.Y')) }}"
                @selected(in_array((string) $relatedDocument->id, $oldRelatedDocumentIds, true))
              >
                {{ $relatedDocument->number }} &middot; {{ $relatedDocument->subject }} &middot; {{ $relatedDocument->doc_date?->format('d.m.Y') }}
              </option>
            @endforeach
          </select>
          <div style="margin-top:6px; font-size:12px; color:var(--text-muted);">
            Удерживайте Ctrl или Cmd, чтобы выбрать несколько документов для связи.
          </div>
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
const relatedDocumentSearch = document.getElementById('relatedDocumentSearch');
const relatedDocumentsSelect = document.getElementById('relatedDocumentsSelect');
const typeRadios = Array.from(document.querySelectorAll('.type-radio'));
let files = [];

function syncTypeOptions() {
  typeRadios.forEach(radio => {
    const option = radio.closest('label')?.querySelector('.type-opt');
    option?.classList.toggle('is-active', radio.checked);
  });

  const selectedType = typeRadios.find(r => r.checked)?.value ?? '';
  const hideRecipientFields = selectedType === 'memo';
  document.querySelectorAll('.memo-hide-recipient').forEach(el => {
    el.style.display = hideRecipientFields ? 'none' : '';
  });
}

dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
dz.addEventListener('drop', e => { e.preventDefault(); dz.classList.remove('drag-over'); addFiles(e.dataTransfer.files); });
fi.addEventListener('change', () => addFiles(fi.files));
typeRadios.forEach(radio => radio.addEventListener('change', syncTypeOptions));
syncTypeOptions();

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

let activeDocTypeFilter = '';

function filterRelatedDocs() {
  const query = relatedDocumentSearch.value.trim().toLowerCase();
  Array.from(relatedDocumentsSelect.options).forEach(option => {
    const haystack = option.dataset.search || option.text.toLowerCase();
    const matchesQuery = query === '' || haystack.includes(query);
    const matchesType = activeDocTypeFilter === '' || option.dataset.type === activeDocTypeFilter;
    option.hidden = !matchesQuery || !matchesType;
  });
}

relatedDocumentSearch?.addEventListener('input', filterRelatedDocs);

document.querySelectorAll('.doc-type-filter').forEach(btn => {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.doc-type-filter').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    activeDocTypeFilter = this.dataset.type;
    filterRelatedDocs();
  });
});

const usersData = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'dept' => $u->department?->name ?? ''])->values());

function makeUserCombobox(inputId, hiddenId, dropdownId) {
  const input    = document.getElementById(inputId);
  const hidden   = document.getElementById(hiddenId);
  const dropdown = document.getElementById(dropdownId);
  if (!input) return;

  function renderOptions(query) {
    const q = query.trim().toLowerCase();
    const matches = q ? usersData.filter(u => u.name.toLowerCase().includes(q)) : usersData;
    if (!matches.length) { dropdown.classList.remove('open'); return; }
    dropdown.innerHTML = matches.slice(0, 12).map(u =>
      `<div class="user-combobox-option" data-id="${u.id}" data-name="${u.name}">
        <span>${u.name}</span>
        ${u.dept ? `<span class="user-combobox-option-dept">${u.dept}</span>` : ''}
      </div>`
    ).join('');
    dropdown.classList.add('open');
    dropdown.querySelectorAll('.user-combobox-option').forEach(opt => {
      opt.addEventListener('mousedown', function (e) {
        e.preventDefault();
        input.value  = this.dataset.name;
        hidden.value = this.dataset.id;
        dropdown.classList.remove('open');
      });
    });
  }

  input.addEventListener('input', function () { hidden.value = ''; renderOptions(this.value); });
  input.addEventListener('focus', function () { renderOptions(this.value); });
  input.addEventListener('blur',  function () { setTimeout(() => dropdown.classList.remove('open'), 150); });
}

makeUserCombobox('senderComboInput',   'senderComboId',   'senderComboDropdown');

// Multi-user recipient picker
@php
  $oldRecipientIds = array_map('intval', old('recipient_ids', []));
  $oldExecutorIds = array_map('intval', old('executor_ids', []));
  $preRecipients = $users->whereIn('id', $oldRecipientIds)
    ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values()->all();
  $preExecutors = $users->whereIn('id', $oldExecutorIds)
    ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values()->all();
  $preOrgs = old('recipient_orgs', []);
@endphp

let selectedRecipients = @json($preRecipients);
let selectedExecutors = @json($preExecutors);
let selectedOrgs = @json($preOrgs);

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function renderRecipientTags() {
  const wrap = document.getElementById('recipientTagsWrap');
  wrap.innerHTML = selectedRecipients.map((u, i) =>
    `<span class="multi-tag">
      ${escHtml(u.name)}
      <button type="button" onclick="removeRecipient(${i})">×</button>
      <input type="hidden" name="recipient_ids[]" value="${u.id}">
    </span>`
  ).join('');
}

function removeRecipient(i) {
  selectedRecipients.splice(i, 1);
  renderRecipientTags();
}

function renderExecutorTags() {
  const wrap = document.getElementById('executorTagsWrap');
  wrap.innerHTML = selectedExecutors.map((u, i) =>
    `<span class="multi-tag">
      ${escHtml(u.name)}
      <button type="button" onclick="removeExecutor(${i})">×</button>
      <input type="hidden" name="executor_ids[]" value="${u.id}">
    </span>`
  ).join('');
}

function removeExecutor(i) {
  selectedExecutors.splice(i, 1);
  renderExecutorTags();
}

(function () {
  const searchInput = document.getElementById('recipientSearchInput');
  const dropdown    = document.getElementById('recipientSearchDropdown');

  function renderDropdown(query) {
    const q   = query.trim().toLowerCase();
    const ids = selectedRecipients.map(u => u.id);
    const matches = (q ? usersData.filter(u => u.name.toLowerCase().includes(q)) : usersData)
      .filter(u => !ids.includes(u.id));
    if (!matches.length) { dropdown.classList.remove('open'); return; }
    dropdown.innerHTML = matches.slice(0, 12).map(u =>
      `<div class="user-combobox-option" data-id="${u.id}" data-name="${escHtml(u.name)}">
        <span>${escHtml(u.name)}</span>
        ${u.dept ? `<span class="user-combobox-option-dept">${escHtml(u.dept)}</span>` : ''}
      </div>`
    ).join('');
    dropdown.classList.add('open');
    dropdown.querySelectorAll('.user-combobox-option').forEach(opt => {
      opt.addEventListener('mousedown', function (e) {
        e.preventDefault();
        selectedRecipients.push({ id: +this.dataset.id, name: this.dataset.name });
        renderRecipientTags();
        searchInput.value = '';
        dropdown.classList.remove('open');
      });
    });
  }

  searchInput.addEventListener('input',  function () { renderDropdown(this.value); });
  searchInput.addEventListener('focus',  function () { renderDropdown(this.value); });
  searchInput.addEventListener('blur',   function () { setTimeout(() => dropdown.classList.remove('open'), 150); });
})();

(function () {
  const searchInput = document.getElementById('executorSearchInput');
  const dropdown    = document.getElementById('executorSearchDropdown');

  function renderDropdown(query) {
    const q   = query.trim().toLowerCase();
    const ids = selectedExecutors.map(u => u.id);
    const matches = (q ? usersData.filter(u => u.name.toLowerCase().includes(q)) : usersData)
      .filter(u => !ids.includes(u.id));
    if (!matches.length) { dropdown.classList.remove('open'); return; }
    dropdown.innerHTML = matches.slice(0, 12).map(u =>
      `<div class="user-combobox-option" data-id="${u.id}" data-name="${escHtml(u.name)}">
        <span>${escHtml(u.name)}</span>
        ${u.dept ? `<span class="user-combobox-option-dept">${escHtml(u.dept)}</span>` : ''}
      </div>`
    ).join('');
    dropdown.classList.add('open');
    dropdown.querySelectorAll('.user-combobox-option').forEach(opt => {
      opt.addEventListener('mousedown', function (e) {
        e.preventDefault();
        selectedExecutors.push({ id: +this.dataset.id, name: this.dataset.name });
        renderExecutorTags();
        searchInput.value = '';
        dropdown.classList.remove('open');
      });
    });
  }

  searchInput.addEventListener('input',  function () { renderDropdown(this.value); });
  searchInput.addEventListener('focus',  function () { renderDropdown(this.value); });
  searchInput.addEventListener('blur',   function () { setTimeout(() => dropdown.classList.remove('open'), 150); });
})();

// Multi-org picker
function renderOrgTags() {
  const wrap = document.getElementById('recipientOrgTagsWrap');
  wrap.innerHTML = selectedOrgs.map((org, i) =>
    `<span class="multi-tag">
      ${escHtml(org)}
      <button type="button" onclick="removeOrg(${i})">×</button>
      <input type="hidden" name="recipient_orgs[]" value="${escHtml(org)}">
    </span>`
  ).join('');
}

function removeOrg(i) {
  selectedOrgs.splice(i, 1);
  renderOrgTags();
}

function addRecipientOrg() {
  const input = document.getElementById('recipientOrgInput');
  const val   = input.value.trim();
  if (val && !selectedOrgs.includes(val)) {
    selectedOrgs.push(val);
    renderOrgTags();
  }
  input.value = '';
}

document.getElementById('recipientOrgInput').addEventListener('keydown', function (e) {
  if (e.key === 'Enter') { e.preventDefault(); addRecipientOrg(); }
});

renderRecipientTags();
renderExecutorTags();
renderOrgTags();
</script>
@endpush
@endsection
