@extends('layouts.app')
@section('page-title', $pageTitle ?? 'Реестр документов')

@section('content')
@if($showFilters ?? true)
<div class="filter-tabs">
  @php $type = request('type', 'all'); @endphp
  <a href="{{ route('documents.index') }}" class="filter-tab {{ $type === 'all' || !$type ? 'active' : '' }}">Все</a>
  @foreach(array_keys(\App\Models\Document::$typeNames) as $docType)
    <a href="{{ route('documents.index', ['type' => $docType]) }}" class="filter-tab {{ $type === $docType ? 'active' : '' }}">
      {{ \App\Models\Document::$typeNames[$docType] }}
    </a>
  @endforeach
</div>
@else
  @php $type = null; @endphp
@endif

<form method="GET" action="{{ $listRoute ?? route('documents.index') }}" class="search-bar">
  @if($type && $type !== 'all')
    <input type="hidden" name="type" value="{{ $type }}">
  @endif
  <input type="text" name="search" class="search-input" placeholder="Поиск по теме, номеру, организации или группе..." value="{{ request('search') }}">
  <select name="status" class="form-control" style="width:180px;">
    <option value="">Все статусы</option>
    @foreach(\App\Models\Document::$statusNames as $key => $label)
      <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-primary">Найти</button>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title">{{ $cardTitle ?? 'Документы' }}</div>
    @if(count($user->allowedDocumentTypes()) > 0)
      <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm">+ Создать</a>
    @endif
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Номер</th>
          <th>Тип</th>
          <th>Тема</th>
          <th>От / Кому</th>
          <th>Дата</th>
          <th>Статус</th>
          <th>Исполнитель</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($documents as $document)
          <tr>
            <td><a href="{{ route('documents.show', $document) }}" class="td-link"><code>{{ $document->number }}</code></a></td>
            <td><span class="badge type-{{ $document->type }}">{{ $document->type_name }}</span></td>
            <td><a href="{{ route('documents.show', $document) }}" class="td-link">{{ \Illuminate\Support\Str::limit($document->subject, 60) }}</a></td>
            <td style="font-size:12.5px; color:var(--text-muted);">
              {{ $document->sender_org ?: $document->sender?->short_name ?: '—' }}
              <br>
              →
              {{ collect($document->recipient_orgs ?? [])->first() ?: $document->recipients->first()?->short_name ?: $document->recipientGroup?->name ?: '—' }}
            </td>
            <td>{{ $document->doc_date->format('d.m.Y') }}</td>
            <td><span class="badge status-{{ $document->status }}">{{ $document->status_name }}</span></td>
            <td>
              @if($document->executors->isNotEmpty())
                {{ $document->executors->map(fn($executor) => $executor->short_name)->join(', ') }}
              @else
                {{ $document->executor?->short_name ?? '—' }}
              @endif
            </td>
            <td><a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-secondary">Открыть</a></td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align:center; padding:40px; color:var(--text-muted);">Документы не найдены</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
