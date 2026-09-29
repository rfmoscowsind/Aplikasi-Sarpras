@extends('layouts.app')
@section('title', 'Peminjaman')
@section('content')
<div class="panel">
    <div class="panel-head"><h2>Filter Peminjaman</h2></div>
    <div class="panel-body">
        <form method="get" class="form-grid">
            <div class="field"><label>Cari siswa / NIS / kode</label><input class="input" name="q" value="{{ request('q') }}"></div>
            <div class="field"><label>Unit</label><select class="select" name="unit_id"><option value="">Semua unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string)request('unit_id') === (string)$unit->id)>{{ $unit->name }}</option>@endforeach</select></div>
            <div class="field"><label>Status</label>
                <select class="select" name="status">
                    <option value="">Semua status</option>
                    @foreach(['pending_approval','approved','borrowed','return_pending','partially_returned','completed','rejected','overdue'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="align-self:end"><button class="btn primary">Terapkan Filter</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Peminjam</th><th>Unit</th><th>Barang</th><th>Waktu Pinjam</th><th>Perkiraan Pengembalian</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($borrowings as $loan)
                <tr>
                    <td><strong>{{ $loan->student_name }}</strong><div class="small muted">{{ $loan->student_class }} · NIS {{ $loan->student_nis }}</div></td>
                    <td>{{ $loan->unit->name }}</td>
                    <td>{{ $loan->items->map(fn($row) => $row->item->name.' × '.($row->handed_over_qty ?: $row->approved_qty ?: $row->requested_qty))->join(', ') }}</td>
                    <td>{{ $loan->borrowed_at?->format('d/m/Y H:i') ?? 'Belum diserahkan' }}</td>
                    <td>{{ $loan->expected_return_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($loan->isOverdue())<span class="badge danger">Terlambat</span>
                        @elseif($loan->status === 'completed')<span class="badge good">Selesai</span>
                        @elseif(in_array($loan->status,['pending_approval','return_pending']))<span class="badge warn">{{ str_replace('_',' ',$loan->status) }}</span>
                        @else<span class="badge">{{ str_replace('_',' ',$loan->status) }}</span>@endif
                    </td>
                    <td><a class="btn sm" href="{{ route('borrowings.show', $loan) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Tidak ada transaksi yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $borrowings->links() }}</div>
</div>
@endsection
