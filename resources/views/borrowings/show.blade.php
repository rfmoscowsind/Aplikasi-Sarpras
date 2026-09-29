@extends('layouts.app')
@section('title', 'Peminjaman · '.$borrowing->student_name)
@section('content')
<div class="panel">
    <div class="panel-head">
        <div><h2>{{ $borrowing->student_name }}</h2><div class="small muted">NIS {{ $borrowing->student_nis }} · {{ $borrowing->student_class }} · {{ $borrowing->unit->name }}</div></div>
        @if($borrowing->isOverdue())<span class="badge danger">Terlambat</span>@else<span class="badge">{{ str_replace('_',' ',$borrowing->status) }}</span>@endif
    </div>
    <div class="panel-body form-grid">
        <div><div class="small muted">Nomor HP</div><strong>+{{ $borrowing->phone }}</strong></div>
        <div><div class="small muted">Kode transaksi</div><span class="code">{{ $borrowing->public_id }}</span></div>
        <div><div class="small muted">Waktu Pinjam</div><strong>{{ $borrowing->borrowed_at?->format('d/m/Y H:i') ?? 'Belum diserahkan' }}</strong></div>
        <div><div class="small muted">Perkiraan Pengembalian</div><strong>{{ $borrowing->expected_return_at->format('d/m/Y H:i') }}</strong></div>
        <div class="field full"><div class="small muted">Keperluan</div>{{ $borrowing->purpose }}</div>
        @if($borrowing->rejection_reason)<div class="field full"><div class="small muted">Alasan ditolak</div>{{ $borrowing->rejection_reason }}</div>@endif
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Barang</h2></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Barang</th><th>Diminta</th><th>Disetujui</th><th>Diserahkan</th><th>Selesai/Return</th><th>Rusak</th><th>Hilang</th><th>Sisa</th></tr></thead>
            <tbody>
            @foreach($borrowing->items as $item)
                <tr>
                    <td><strong>{{ $item->item->name }}</strong></td>
                    <td>{{ $item->requested_qty }}</td>
                    <td>{{ $item->approved_qty ?? '-' }}</td>
                    <td>{{ $item->handed_over_qty }}</td>
                    <td>{{ $item->returned_qty }}</td>
                    <td>{{ $item->damaged_qty }}</td>
                    <td>{{ $item->lost_qty }}</td>
                    <td>{{ $item->outstandingQty() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($borrowing->status === 'pending_approval')
<div class="two-col">
    <div class="panel">
        <div class="panel-head"><h2>Setujui Peminjaman</h2></div>
        <div class="panel-body">
            <form method="post" action="{{ route('borrowings.approve', $borrowing) }}" class="stack">
                @csrf
                @foreach($borrowing->items as $item)
                    <div class="field"><label>{{ $item->item->name }} · diminta {{ $item->requested_qty }}</label><input class="input" type="number" min="1" max="{{ $item->requested_qty }}" name="approved[{{ $item->id }}]" value="{{ $item->requested_qty }}" required></div>
                @endforeach
                <button class="btn primary" type="submit">Setujui & Reservasi Stok</button>
            </form>
        </div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Tolak</h2></div>
        <div class="panel-body">
            <form method="post" action="{{ route('borrowings.reject', $borrowing) }}" class="stack">
                @csrf
                <div class="field"><label>Alasan</label><textarea class="textarea" name="reason" required></textarea></div>
                <button class="btn danger" type="submit">Tolak Peminjaman</button>
            </form>
        </div>
    </div>
</div>
@endif

@if($borrowing->status === 'approved')
<div class="panel">
    <div class="panel-head"><h2>Serahkan Barang</h2></div>
    <div class="panel-body">
        <p class="muted">Klik setelah barang benar-benar diserahkan ke siswa. Waktu pinjam tercatat saat tombol ini ditekan dan stok berpindah dari reserved menjadi borrowed.</p>
        <form method="post" action="{{ route('borrowings.handover', $borrowing) }}" onsubmit="return confirm('Barang benar-benar sudah diserahkan?')">
            @csrf
            <button class="btn primary" type="submit">Serahkan Barang</button>
        </form>
    </div>
</div>
@endif

@if($borrowing->returnRequests->isNotEmpty())
<div class="panel">
    <div class="panel-head"><h2>Pengembalian</h2></div>
    <div class="panel-body stack">
        @foreach($borrowing->returnRequests as $return)
        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:10px">
                <div><strong>Return {{ $return->created_at->format('d/m/Y H:i') }}</strong><div class="small muted">{{ $return->borrower_notes ?: 'Tanpa catatan siswa' }}</div></div>
                <span class="badge {{ $return->status === 'approved' ? 'good' : ($return->status === 'rejected' ? 'danger' : 'warn') }}">{{ $return->status }}</span>
            </div>
            <div class="actions" style="margin-bottom:10px"><a class="btn sm outline" href="{{ route('returns.photo', $return) }}">Lihat Foto Bukti</a></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Barang</th><th>Qty</th><th>Klaim Siswa</th><th>Verifikasi</th></tr></thead>
                    <tbody>
                    @foreach($return->items as $row)
                        <tr><td>{{ $row->borrowingItem->item->name }}</td><td>{{ $row->quantity }}</td><td>{{ $row->borrower_condition }}</td><td>{{ $row->verified_condition ?: '-' }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($return->status === 'pending')
            <div class="two-col" style="margin-top:14px">
                <form method="post" action="{{ route('returns.approve', $return) }}" class="stack">
                    @csrf
                    @foreach($return->items as $row)
                        <div class="field"><label>{{ $row->borrowingItem->item->name }} · {{ $row->quantity }} pcs</label>
                            <select class="select" name="condition[{{ $row->id }}]">
                                <option value="good" @selected($row->borrower_condition === 'good')>Baik</option>
                                <option value="damaged" @selected($row->borrower_condition === 'damaged')>Rusak</option>
                                <option value="lost" @selected($row->borrower_condition === 'lost')>Hilang</option>
                            </select>
                        </div>
                    @endforeach
                    <div class="field"><label>Catatan petugas</label><textarea class="textarea" name="notes"></textarea></div>
                    <button class="btn primary" type="submit">ACC Pengembalian</button>
                </form>
                <form method="post" action="{{ route('returns.reject', $return) }}" class="stack">
                    @csrf
                    <div class="field"><label>Alasan ditolak / minta foto ulang</label><textarea class="textarea" name="notes" required></textarea></div>
                    <button class="btn danger" type="submit">Tolak Return</button>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
