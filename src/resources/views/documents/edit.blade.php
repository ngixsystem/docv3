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
            <label class="form-label">Отправитель (сотрудник)</label>
            <select name="sender_id" class="form-control">
              <option value="">—</option>
              @foreach($users as $item)
                <option value="{{ $item->id }}" @selected((string) old('sender_id', $document->sender_id) === (string) $item->id)>{{ $item->name }} ({{ $item->department?->name }})</option>
              @endforeach
            </select>
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
            <select name="recipient_id" class="form-control">
              <option value="">—</option>
              @foreach($users as $item)
                <option value="{{ $item->id }}" @selected((string) old('recipient_id', $document->recipient_id) === (string) $item->id)>{{ $item->name }} ({{ $item->department?->name }})</option>
              @endforeach
            </select>
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
            <select name="executor_id" class="form-control">
              <option value="">—</option>
              @foreach($users as $item)
                <option value="{{ $item->id }}" @selected((string) old('executor_id', $document->executor_id) === (string) $item->id)>{{ $item->name }} ({{ $item->department?->name }})</option>
              @endforeach
            </select>
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
            style="margin-bottom:10px;"
          >
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

relatedDocumentSearch?.addEventListener('input', function () {
  const query = this.value.trim().toLowerCase();

  Array.from(relatedDocumentsSelect.options).forEach(option => {
    const haystack = option.dataset.search || option.text.toLowerCase();
    option.hidden = query !== '' && !haystack.includes(query);
  });
});
</script>
@endpush
@endsection
