@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="grid cards">
    <div class="card"><div class="stat-label">Jenis Barang</div><div class="stat-value">{{ number_format($cards['item_types']) }}</div></div>
    <div class="card"><div class="stat-label">Total Barang</div><div class="stat-value">{{ number_format($cards['total_qty']) }}</div></div>
    <div class="card"><div class="stat-label">Sedang Dipinjam</div><div class="stat-value">{{ number_format($cards['borrowed_qty']) }}</div></div>
    <div class="card"><div class="stat-label">Rusak</div><div class="stat-value">{{ number_format($cards['damaged_qty']) }}</div></div>
    <div class="card"><div class="stat-label">Menunggu Persetujuan</div><div class="stat-value">{{ number_format($cards['pending']) }}</div></div>
    <div class="card"><div class="stat-label">Menunggu Verifikasi Kembali</div><div class="stat-value">{{ number_format($cards['return_pending']) }}</div></div>
    <div class="card"><div class="stat-label">Terlambat</div><div class="stat-value">{{ number_format($cards['overdue']) }}</div></div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Peminjaman Terbaru</h2><a class="btn sm" href="{{ route('borrowings.index') }}">Lihat semua</a></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Peminjam</th><th>Unit</th><th>Barang</th><th>Waktu Pinjam</th><th>Perkiraan Pengembalian</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($recentBorrowings as $loan)
                <tr>
                    <td><a href="{{ route('borrowings.show', $loan) }}"><strong>{{ $loan->student_name }}</strong></a><div class="small muted">{{ $loan->student_class }} · {{ $loan->student_nis }}</div></td>
                    <td>{{ $loan->unit->name }}</td>
                    <td>{{ $loan->items->pluck('item.name')->join(', ') }}</td>
                    <td>{{ $loan->borrowed_at?->format('d/m/Y H:i') ?? 'Belum diserahkan' }}</td>
                    <td>{{ $loan->expected_return_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($loan->isOverdue())<span class="badge danger">Terlambat</span>
                        @else<span class="badge">{{ str_replace('_', ' ', $loan->status) }}</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Belum ada transaksi peminjaman.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Unit yang Bisa Diakses</h2></div>
    <div class="panel-body actions">
        @foreach($units as $unit)
            <a class="btn outline" href="{{ route('units.show', $unit) }}">{{ $unit->name }}</a>
        @endforeach
    </div>
</div>
@endsection
