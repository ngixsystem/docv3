<div class="registry-entry"
     data-search="{{ mb_strtolower($entry->document->number . ' ' . $entry->document->subject . ' ' . ($entry->note ?? '')) }}"
     data-type="{{ $entry->document->type }}">

  <button
    class="registry-entry-pin registry-pin-btn {{ $entry->pinned ? 'is-pinned' : '' }}"
    data-id="{{ $entry->id }}"
    data-url="{{ route('registry.pin', $entry) }}"
    title="{{ $entry->pinned ? 'Открепить' : 'Закрепить' }}"
    type="button"
  >★</button>

  <div class="registry-entry-body">
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:5px;">
      <span class="badge type-{{ $entry->document->type }}">{{ $entry->document->type_name }}</span>
      <span class="badge status-{{ $entry->document->status }}">{{ $entry->document->status_name }}</span>
      @if($currentUser->hasAnyRole(['admin', 'clerk']))
        <span style="font-size:11px; color:var(--text-muted);">{{ $entry->department->name }}</span>
      @endif
    </div>

    <a href="{{ route('documents.show', $entry->document) }}" class="registry-entry-title">
      <span class="registry-entry-title" style="font-family:monospace; font-size:13px; font-weight:600;">{{ $entry->document->number }}</span>
      &nbsp;·&nbsp;
      <span class="registry-entry-note-text">{{ $entry->document->subject }}</span>
    </a>

    <div class="registry-entry-meta">
      <span>{{ $entry->document->doc_date?->format('d.m.Y') }}</span>
      <span>·</span>
      <span>Добавил: {{ $entry->addedBy->name }}</span>
      <span>·</span>
      <span>{{ $entry->created_at->format('d.m.Y') }}</span>
    </div>

    @if($entry->note)
      <div class="registry-entry-note">
        <span class="registry-entry-note-text">{{ $entry->note }}</span>
      </div>
    @endif
  </div>

  <div class="registry-entry-actions">
    <a href="{{ route('documents.show', $entry->document) }}" class="btn btn-sm btn-secondary">Открыть</a>
    @if($currentUser->hasAnyRole(['admin', 'clerk']) || ($currentUser->hasRole('manager') && $currentUser->department_id === $entry->department_id))
      <form method="POST" action="{{ route('registry.destroy', $entry) }}" onsubmit="return confirm('Удалить из реестра?');" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger" title="Удалить из реестра">×</button>
      </form>
    @endif
  </div>
</div>
