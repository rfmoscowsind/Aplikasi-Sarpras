@extends('layouts.app')
@section('title', 'Detail Barang Masuk')
@section('content')
<div class="panel">
    <div class="panel-head"><h2>{{ $incoming->supplier }}</h2><span class="badge">{{ $incoming->received_at->format('d/m/Y') }}</span></div>
    <div class="panel-body form-grid">
        <div><div class="small muted">Unit</div><strong>{{ $incoming->centralUnit->name }}</strong></div>
        <div><div class="small muted">Invoice</div><strong>{{ $incoming->invoice_number ?: '-' }}</strong></div>
        <div class="field full"><div class="small muted">Catatan</div>{{ $incoming->notes ?: '-' }}</div>
        @if($incoming->invoice_object_key)<div><a class="btn sm" href="{{ route('incoming.invoice', $incoming) }}">Download Invoice</a></div>@endif
    </div>
</div>
<div class="panel">
    <div class="panel-head"><h2>Barang Diterima</h2></div>
    <div class="table-wrap">
        <table class="table"><thead><tr><th>Barang</th><th>Spesifikasi</th><th>Jumlah</th><th>Foto</th></tr></thead><tbody>
        @foreach($incoming->items as $row)
            <tr><td><strong>{{ $row->item->name }}</strong></td><td>{{ $row->item->specification ?: '-' }}</td><td>{{ $row->quantity }}</td><td>@if($row->photo_object_key)<a class="btn sm outline" target="_blank" href="{{ route('incoming.photo', [$incoming, $row]) }}">Lihat foto</a>@else-@endif</td></tr>
        @endforeach
        </tbody></table>
    </div>
</div>
@endsection
