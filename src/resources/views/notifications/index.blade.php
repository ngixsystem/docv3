@extends('layouts.app')
@section('page-title', 'Уведомления')

@section('topbar-actions')
  @if($notifications->total() > 0)
    <form method="POST" action="{{ route('notifications.read-all') }}">
      @csrf
      <button type="submit" class="btn btn-secondary btn-sm">Прочитать все</button>
    </form>
  @endif
@endsection

@section('content')
<div style="max-width:720px; margin:0 auto; display:flex; flex-direction:column; gap:10px;">
  @forelse($notifications as $n)
    @php $data = $n->data; @endphp
    <form method="POST" action="{{ route('notifications.read', $n->id) }}" style="display:contents;">
      @csrf
      <button type="submit" style="all:unset; display:block; width:100%; cursor:pointer;">
        <div class="card" style="padding:16px 20px; display:flex; gap:14px; align-items:flex-start; opacity:{{ $n->read_at ? '.6' : '1' }}; border-left: 3px solid {{ $n->read_at ? 'var(--border)' : 'var(--accent)' }};">
          <div style="width:8px; height:8px; border-radius:50%; background:{{ $n->read_at ? 'var(--border)' : 'var(--accent)' }}; margin-top:6px; flex-shrink:0;"></div>
          <div style="flex:1; min-width:0;">
            <div style="font-weight:600; margin-bottom:3px;">{{ $data['title'] ?? '—' }}</div>
            <div style="font-size:13px; color:var(--text-muted); margin-bottom:6px;">{{ $data['body'] ?? '' }}</div>
            <div style="font-size:11px; color:var(--text-muted);">{{ $n->created_at->diffForHumans() }}</div>
          </div>
        </div>
      </button>
    </form>
  @empty
    <div class="card" style="padding:40px; text-align:center; color:var(--text-muted);">Нет уведомлений</div>
  @endforelse

  <div style="margin-top:10px;">{{ $notifications->links() }}</div>
</div>
@endsection
