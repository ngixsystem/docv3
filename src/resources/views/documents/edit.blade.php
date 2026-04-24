@extends('layouts.app')
@section('page-title', 'Редактирование документа')

@section('topbar-actions')
  <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary btn-sm">К документу</a>
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
</style>
@endpush

@section('content')
@php
  $selectedRelatedDocumentIds = collect(old('related_document_ids', $document->all_related_documents->pluck('id')->all()))
    ->map(fn ($id) => (string) $id)
    ->all();
@endphp
<div style="max-width:860px; margin:0 auto;">
  <div class="card">
    <div class="card-header"><div class="card-title">Редактирование документа</div></div>
    <div class="card-body">
      <form method="POST" action="{{ route('documents.update', $document) }}" id="editForm">
        @csrf
        @method('PATCH')

        <div class="form-group">
          <label class="form-label">Тип документа</label>
          <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px;">
            @foreach($user->allowedDocumentTypes() as $type)
              <label style="cursor:pointer;">
                <input
                  type="radio"
                  name="type"
                  value="{{ $type }}"
                  {{ old('type', $document->type) === $type ? 'checked' : '' }}
                  style="display:none;"
                  class="type-radio"
                >
                <div class="type-opt" data-val="{{ $type }}" style="padding:14px; border:2px solid var(--border); border-radius:10px; text-align:center;">
                  <div style="font-size:13px; font-weight:600;">{{ \App\Models\Document::$typeNames[$type] }}</div>
                </div>
              </label>
            @endforeach
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Тема</label>
          <input type="text" name="subject" class="form-control" value="{{ old('subject', $document->subject) }}" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Дата документа</label>
            <input type="date" name="doc_date" class="form-control" value="{{ old('doc_date', $document->doc_date?->format('Y-m-d')) }}" required>
          </div>
          <div class="form-group">
            <label class="form-label">Срок исполнения</label>
            <input type="date" name="deadline" class="form-control" value="{{ old('deadline', $document->deadline?->format('Y-m-d')) }}">
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
                value="{{ old('sender_id') ? ($users->find(old('sender_id'))?->name ?? '') : ($document->sender?->name ?? '') }}"
              >
              <input type="hidden" name="sender_id" id="senderComboId" value="{{ old('sender_id', $document->sender_id) }}">
              <div class="user-combobox-dropdown" id="senderComboDropdown"></div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Отправитель (организация)</label>
            <input
              type="text"
              name="sender_org"
              class="form-control"
              value="{{ old('sender_org', $document->sender_org) }}"
              list="senderCompaniesList"
              placeholder="Выберите компанию из справочника или введите вручную"
            >
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
          <div class="form-group">
            <label class="form-label">Получатель (сотрудник)</label>
            <div class="user-combobox">
              <input type="text" id="recipientComboInput" class="form-control user-combobox-input"
                placeholder="Введите имя или выберите из списка" autocomplete="off"
                value="{{ old('recipient_id') ? ($users->find(old('recipient_id'))?->name ?? '') : ($document->recipient?->name ?? '') }}">
              <input type="hidden" name="recipient_id" id="recipientComboId" value="{{ old('recipient_id', $document->recipient_id) }}">
              <div class="user-combobox-dropdown" id="recipientComboDropdown"></div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Получатель (организация)</label>
            <input type="text" name="recipient_org" class="form-control" value="{{ old('recipient_org', $document->recipient_org) }}">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Получатель (группа)</label>
            <select name="recipient_group_id" class="form-control">
              <option value="">—</option>
              @foreach($groups as $group)
                <option value="{{ $group->id }}" @selected((string) old('recipient_group_id', $document->recipient_group_id) === (string) $group->id)>{{ $group->name }} ({{ $group->users_count }})</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Исполнитель</label>
            <div class="user-combobox">
              <input type="text" id="executorComboInput" class="form-control user-combobox-input"
                placeholder="Введите имя или выберите из списка" autocomplete="off"
                value="{{ old('executor_id') ? ($users->find(old('executor_id'))?->name ?? '') : ($document->executor?->name ?? '') }}">
              <input type="hidden" name="executor_id" id="executorComboId" value="{{ old('executor_id', $document->executor_id) }}">
              <div class="user-combobox-dropdown" id="executorComboDropdown"></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Описание</label>
          <textarea name="description" class="form-control" rows="5">{{ old('description', $document->description) }}</textarea>
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
                @selected(in_array((string) $relatedDocument->id, $selectedRelatedDocumentIds, true))
              >
                {{ $relatedDocument->number }} · {{ $relatedDocument->subject }} · {{ $relatedDocument->doc_date?->format('d.m.Y') }}
              </option>
            @endforeach
          </select>
          <div style="margin-top:6px; font-size:12px; color:var(--text-muted);">
            Удерживайте Ctrl или Cmd, чтобы выбрать несколько документов для связи.
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
          <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary">Отмена</a>
          <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
const relatedDocumentSearch = document.getElementById('relatedDocumentSearch');
const relatedDocumentsSelect = document.getElementById('relatedDocumentsSelect');
const typeRadios = Array.from(document.querySelectorAll('.type-radio'));

function syncTypeOptions() {
  typeRadios.forEach(radio => {
    const option = radio.closest('label')?.querySelector('.type-opt');
    option?.classList.toggle('is-active', radio.checked);
  });
}

typeRadios.forEach(radio => radio.addEventListener('change', syncTypeOptions));
syncTypeOptions();

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

// User combobox factory
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

makeUserCombobox('senderComboInput',    'senderComboId',    'senderComboDropdown');
makeUserCombobox('recipientComboInput', 'recipientComboId', 'recipientComboDropdown');
makeUserCombobox('executorComboInput',  'executorComboId',  'executorComboDropdown');
</script>
@endpush
@endsection
