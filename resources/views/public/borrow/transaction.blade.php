@extends('layouts.public')
@section('title', 'Transaksi Peminjaman')
@section('heading', 'Transaksi '.$unit->name)
@section('subheading', $borrowing->student_name.' · '.$borrowing->student_class)
@section('content')
<div class="panel">
    <div class="panel-head">
        <h2>Status Peminjaman</h2>
        @if($borrowing->isOverdue())<span class="badge danger">Terlambat</span>
        @elseif($borrowing->status === 'completed')<span class="badge good">Selesai</span>
        @else<span class="badge">{{ str_replace('_',' ',$borrowing->status) }}</span>@endif
    </div>
    <div class="panel-body stack">
        <div><div class="small muted">Kode transaksi</div><div class="code" style="overflow-wrap:anywhere">{{ $borrowing->public_id }}</div></div>
        <div class="form-grid">
            <div><div class="small muted">Waktu Pinjam</div><strong>{{ $borrowing->borrowed_at?->format('d/m/Y H:i') ?? 'Belum diserahkan' }}</strong></div>
            <div><div class="small muted">Perkiraan Pengembalian</div><strong>{{ $borrowing->expected_return_at->format('d/m/Y H:i') }}</strong></div>
        </div>
        <div><div class="small muted">Keperluan</div>{{ $borrowing->purpose }}</div>
        @if($borrowing->rejection_reason)<div class="alert error">{{ $borrowing->rejection_reason }}</div>@endif
    </div>
    <div class="table-wrap">
        <table class="table"><thead><tr><th>Barang</th><th>Dipinjam</th><th>Sudah diproses kembali</th><th>Sisa</th></tr></thead><tbody>
        @foreach($borrowing->items as $row)
            <tr><td>{{ $row->item->name }}</td><td>{{ $row->handed_over_qty ?: ($row->approved_qty ?: $row->requested_qty) }}</td><td>{{ $row->returned_qty }}</td><td>{{ $row->outstandingQty() }}</td></tr>
        @endforeach
        </tbody></table>
    </div>
</div>

@if(in_array($borrowing->status, ['borrowed','partially_returned'], true))
<div class="panel">
    <div class="panel-head"><h2>Ajukan Pengembalian</h2></div>
    <div class="panel-body">
        <form method="post" enctype="multipart/form-data" action="{{ route('public.borrow.return', [$unit->borrow_public_token, $borrowing->public_id]) }}" class="stack">
            @csrf
            @foreach($borrowing->items as $row)
                @if($row->outstandingQty() > 0)
                <div class="card">
                    <strong>{{ $row->item->name }}</strong>
                    <div class="small muted" style="margin-bottom:10px">Masih dipinjam: {{ $row->outstandingQty() }}</div>
                    <div class="form-grid">
                        <div class="field"><label>Jumlah dikembalikan</label><input class="input" type="number" min="0" max="{{ $row->outstandingQty() }}" value="0" name="items[{{ $row->id }}][quantity]"></div>
                        <div class="field"><label>Kondisi menurut peminjam</label>
                            <select class="select" name="items[{{ $row->id }}][condition]">
                                <option value="good">Baik</option>
                                <option value="damaged">Rusak</option>
                                <option value="lost">Hilang</option>
                            </select>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
            <div class="field"><label>Foto bukti barang yang dikembalikan</label><input class="input" type="file" accept="image/*" capture="environment" name="photo" required></div>
            <div class="field"><label>Catatan (opsional)</label><textarea class="textarea" name="notes"></textarea></div>
            <button class="btn primary" type="submit">Kirim untuk Verifikasi Petugas</button>
        </form>
    </div>
</div>
@elseif($borrowing->status === 'return_pending')
<div class="alert success">Foto dan pengembalian sudah dikirim. Tunggu petugas unit memeriksa barang fisik dan memberi ACC.</div>
@endif

<div class="actions"><a class="btn outline" href="{{ route('public.borrow.start', $unit->borrow_public_token) }}">Kembali ke Form Unit</a></div>
@endsection
