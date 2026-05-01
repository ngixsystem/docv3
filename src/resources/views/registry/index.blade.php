@extends('layouts.app')
@section('page-title', 'Реестр отдела')

@push('styles')
<style>
.registry-toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
  flex-wrap: wrap;
  align-items: center;
}

.registry-search {
  flex: 1;
  min-width: 200px;
  padding: 9px 14px 9px 38px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-size: 13.5px;
  background: var(--card-solid) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 11px center;
  color: var(--text);
}

.registry-entry {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border);
  transition: background var(--transition);
  position: relative;
}

.registry-entry:last-child { border-bottom: none; }
.registry-entry:hover { background: var(--surface-soft); }
.registry-entry[data-hidden="true"] { display: none; }

.registry-entry-pin {
  flex-shrink: 0;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  font-size: 18px;
  line-height: 1;
  opacity: 0.35;
  transition: opacity .15s, transform .15s;
  color: #f59e0b;
}
.registry-entry-pin:hover { opacity: .7; transform: scale(1.15); }
.registry-entry-pin.is-pinned { opacity: 1; }

.registry-entry-body { flex: 1; min-width: 0; }

.registry-entry-title {
  font-weight: 700;
  font-size: 14.5px;
  color: var(--text);
  text-decoration: none;
  display: inline;
}
.registry-entry-title:hover { color: var(--accent); }

.registry-entry-meta {
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-muted);
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.registry-entry-note {
  margin-top: 6px;
  font-size: 12.5px;
  color: var(--text-muted);
  font-style: italic;
  border-left: 2px solid var(--accent);
  padding-left: 8px;
}

.registry-entry-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.registry-section-label {
  padding: 10px 18px 6px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
}

.registry-empty {
  padding: 50px 24px;
  text-align: center;
  color: var(--text-muted);
  font-size: 14px;
}

.dept-tab-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  background: var(--accent);
  color: #fff;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 700;
  margin-left: 4px;
}

.filter-tab .dept-tab-count { background: rgba(155,28,28,.18); color: var(--accent); }
.filter-tab.active .dept-tab-count { background: rgba(255,255,255,.25); color: #fff; }

mark {
  background: color-mix(in srgb, var(--accent) 22%, transparent);
  color: var(--accent);
  border-radius: 2px;
  padding: 0 1px;
}
</style>
@endpush

@section('content')

{{-- Department tabs --}}
<div class="filter-tabs">
  <a href="{{ route('registry.index') }}"
     class="filter-tab {{ !$activeDeptId ? 'active' : '' }}">
    Все
    <span class="dept-tab-count">{{ $deptCounts->sum() }}</span>
  </a>
  @foreach($departments as $dept)
    <a href="{{ route('registry.index', ['dept' => $dept->id]) }}"
       class="filter-tab {{ $activeDeptId === $dept->id ? 'active' : '' }}">
      {{ $dept->name }}
      @if($deptCounts->get($dept->id))
        <span class="dept-tab-count">{{ $deptCounts->get($dept->id) }}</span>
      @endif
    </a>
  @endforeach
</div>

{{-- Search --}}
<div class="registry-toolbar">
  <input
    type="text"
    id="registrySearch"
    class="registry-search"
    placeholder="Поиск по номеру, теме, примечанию..."
    autocomplete="off"
    value=""
  >
  <div id="registryTypeFilters" class="doc-type-filters" style="display:flex; flex-wrap:wrap; gap:6px;">
    <button type="button" class="doc-type-filter active" data-type="">Все типы</button>
    <button type="button" class="doc-type-filter" data-type="incoming">Входящие</button>
    <button type="button" class="doc-type-filter" data-type="outgoing">Исходящие</button>
    <button type="button" class="doc-type-filter" data-type="memo">СЗ</button>
    <button type="button" class="doc-type-filter" data-type="internal">Внутренние</button>
  </div>
</div>

<div class="card">
  @php
    $pinned = $entries->where('pinned', true);
    $rest   = $entries->where('pinned', false);
  @endphp

  @if($entries->isEmpty())
    <div class="registry-empty">
      <div style="font-size:36px; margin-bottom:10px; opacity:.3;">📂</div>
      <div>Реестр пуст. Добавьте документы через страницу документа.</div>
    </div>
  @else
    @if($pinned->isNotEmpty())
      <div class="registry-section-label" id="sectionPinned">⭐ Закреплённые</div>
      @foreach($pinned as $entry)
        @include('registry._entry', ['entry' => $entry])
      @endforeach
    @endif

    <div class="registry-section-label" id="sectionAll">Все документы</div>
    @if($rest->isEmpty())
      <div style="padding:16px 18px; color:var(--text-muted); font-size:13px;">Нет незакреплённых документов</div>
    @else
      @foreach($rest as $entry)
        @include('registry._entry', ['entry' => $entry])
      @endforeach
    @endif
  @endif

  <div class="registry-empty" id="registryNoResults" style="display:none;">
    Ничего не найдено по запросу
  </div>
</div>

@push('scripts')
<script>
const searchInput  = document.getElementById('registrySearch');
const noResults    = document.getElementById('registryNoResults');
const typeFilters  = document.querySelectorAll('#registryTypeFilters .doc-type-filter');
let activeType = '';

function highlight(text, query) {
  if (!query) return text;
  return text.replace(new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'), '<mark>$1</mark>');
}

function applyFilters() {
  const q = searchInput.value.trim().toLowerCase();
  let visible = 0;

  document.querySelectorAll('.registry-entry').forEach(el => {
    const searchable = (el.dataset.search || '').toLowerCase();
    const type       = el.dataset.type || '';

    const matchesQ    = !q || searchable.includes(q);
    const matchesType = !activeType || type === activeType;

    if (matchesQ && matchesType) {
      el.removeAttribute('data-hidden');
      visible++;

      // Highlight matching text
      const titleEl = el.querySelector('.registry-entry-title');
      const noteEl  = el.querySelector('.registry-entry-note-text');
      if (titleEl) titleEl.innerHTML = highlight(titleEl.dataset.raw || titleEl.textContent, q);
      if (noteEl)  noteEl.innerHTML  = highlight(noteEl.dataset.raw  || noteEl.textContent,  q);
    } else {
      el.dataset.hidden = 'true';
    }
  });

  noResults.style.display = visible === 0 ? 'block' : 'none';

  // Hide section labels if all entries below them are hidden
  ['sectionPinned', 'sectionAll'].forEach(id => {
    const section = document.getElementById(id);
    if (!section) return;
    let next = section.nextElementSibling;
    let anyVisible = false;
    while (next && !next.classList.contains('registry-section-label')) {
      if (!next.dataset.hidden) anyVisible = true;
      next = next.nextElementSibling;
    }
    section.style.display = anyVisible ? '' : 'none';
  });
}

searchInput.addEventListener('input', applyFilters);

typeFilters.forEach(btn => {
  btn.addEventListener('click', function() {
    typeFilters.forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    activeType = this.dataset.type;
    applyFilters();
  });
});

// Pin toggle via AJAX
document.querySelectorAll('.registry-pin-btn').forEach(btn => {
  btn.addEventListener('click', async function() {
    const entryId = this.dataset.id;
    const url     = this.dataset.url;
    const token   = document.querySelector('meta[name="csrf-token"]')?.content
                 || '{{ csrf_token() }}';

    try {
      const res  = await fetch(url, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
      const data = await res.json();

      this.classList.toggle('is-pinned', data.pinned);
      this.title = data.pinned ? 'Открепить' : 'Закрепить';

      // Move entry to correct section without full reload
      setTimeout(() => location.reload(), 300);
    } catch (e) {
      console.error(e);
    }
  });
});

// Store raw text for re-highlighting
document.querySelectorAll('.registry-entry-title, .registry-entry-note-text').forEach(el => {
  el.dataset.raw = el.textContent;
});
</script>
@endpush
@endsection
