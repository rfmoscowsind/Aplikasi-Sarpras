@extends('layouts.app')
@section('title', 'Distribusi '.$distribution->document_number)
@section('content')
<div class="actions" style="margin-bottom:16px">
    <a class="btn outline" target="_blank" href="{{ route('distributions.letter', $distribution) }}">Cetak Surat Serah Terima</a>
    @if($distribution->signed_document_object_key)
        <a class="btn outline" href="{{ route('distributions.signed.download', $distribution) }}">Download Surat TTD</a>
    @endif
</div>

<div class="panel">
    <div class="panel-head"><h2>{{ $distribution->sourceUnit->name }} → {{ $distribution->targetUnit->name }}</h2><span class="badge {{ $distribution->status === 'completed' ? 'good' : 'warn' }}">{{ str_replace('_', ' ', $distribution->status) }}</span></div>
    <div class="panel-body form-grid">
        <div><div class="small muted">Nomor</div><strong>{{ $distribution->document_number }}</strong></div>
        <div><div class="small muted">Penerima</div><strong>{{ $distribution->recipient_name ?: '-' }}</strong><div class="small muted">{{ $distribution->recipient_title }}</div></div>
        <div class="field full"><div class="small muted">Catatan</div>{{ $distribution->notes ?: '-' }}</div>
    </div>
    <div class="table-wrap">
        <table class="table"><thead><tr><th>Barang</th><th>Jumlah</th></tr></thead><tbody>
        @foreach($distribution->items as $row)<tr><td>{{ $row->item->name }}</td><td>{{ $row->quantity }}</td></tr>@endforeach
        </tbody></table>
    </div>
</div>

@if($distribution->status !== 'completed')
<div class="two-col">
    <div class="panel">
        <div class="panel-head"><h2>1. Upload Surat yang Sudah TTD</h2></div>
        <div class="panel-body">
            <form method="post" enctype="multipart/form-data" action="{{ route('distributions.signed.upload', $distribution) }}" class="stack">
                @csrf
                <div class="field"><label>PDF / foto surat</label><input class="input" type="file" name="signed_document" accept=".pdf,image/*" required></div>
                <button class="btn primary" type="submit">Upload Surat Final</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>2. Konfirmasi Perpindahan Stok</h2></div>
        <div class="panel-body">
            <p class="muted">Stok baru berpindah setelah langkah ini. Sistem mengecek ulang stok dengan row lock di dalam transaction.</p>
            <form method="post" action="{{ route('distributions.complete', $distribution) }}" onsubmit="return confirm('Pastikan surat sudah lengkap ditandatangani. Pindahkan stok sekarang?')">
                @csrf
                <button class="btn primary" type="submit" {{ $distribution->signed_document_object_key ? '' : 'disabled' }}>Konfirmasi Serah Terima</button>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
