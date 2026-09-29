@extends('layouts.app')
@section('title', 'Barang Masuk')
@section('content')
<div class="actions" style="margin-bottom:16px"><a class="btn primary" href="{{ route('incoming.create') }}">Catat Barang Masuk</a></div>
<div class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Supplier</th><th>Invoice</th><th>Barang</th><th>Unit Pusat</th><th></th></tr></thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->received_at->format('d/m/Y') }}</td>
                    <td>{{ $entry->supplier }}</td>
                    <td>{{ $entry->invoice_number ?: '-' }}</td>
                    <td>{{ $entry->items->map(fn($row) => $row->item->name.' × '.$row->quantity)->join(', ') }}</td>
                    <td>{{ $entry->centralUnit->name }}</td>
                    <td><a class="btn sm" href="{{ route('incoming.show', $entry) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Belum ada barang masuk.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $entries->links() }}</div>
</div>
@endsection
