@extends('layouts.app')
@section('title', 'Inventaris · '.$unit->name)
@section('content')
<div class="two-col">
    <div class="panel">
        <div class="panel-head"><h2>Inventaris {{ $unit->name }}</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Barang</th><th>Total</th><th>Tersedia</th><th>Reserved</th><th>Dipinjam</th><th>Rusak</th><th>Hilang</th><th>Sumber</th></tr></thead>
                <tbody>
                @forelse($stocks as $stock)
                    <tr>
                        <td><strong>{{ $stock->item->name }}</strong><div class="small muted">{{ $stock->item->specification }}</div><div class="small muted">Input: {{ $stock->creator?->name ?? '-' }}</div>@if($stock->photo_object_key)<div style="margin-top:6px"><a class="btn sm outline" target="_blank" href="{{ route('inventory.photo', [$unit, $stock]) }}">Lihat foto</a></div>@endif</td>
                        <td>{{ $stock->total_qty }}</td><td>{{ $stock->available_qty }}</td><td>{{ $stock->reserved_qty }}</td><td>{{ $stock->borrowed_qty }}</td><td>{{ $stock->damaged_qty }}</td><td>{{ $stock->lost_qty }}</td>
                        <td><span class="badge">{{ $stock->acquisition_source }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Belum ada inventaris.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-body">{{ $stocks->links() }}</div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Catat Barang Existing</h2></div>
        <div class="panel-body">
            <form method="post" enctype="multipart/form-data" action="{{ route('inventory.existing.store', $unit) }}" class="stack">
                @csrf
                <div class="field"><label>Pilih barang yang sudah ada di katalog (opsional)</label>
                    <select class="select" name="item_id"><option value="">Buat barang baru</option>@foreach($catalog as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
                </div>
                <div class="field"><label>Nama barang baru</label><input class="input" name="name"></div>
                <div class="field"><label>Spesifikasi (opsional)</label><textarea class="textarea" name="specification"></textarea></div>
                <div class="field"><label>Jumlah</label><input class="input" type="number" min="1" name="quantity" required></div>
                <div class="field"><label>Foto barang</label><input class="input" type="file" accept="image/*" name="photo"></div>
                <div class="field"><label>Catatan</label><textarea class="textarea" name="notes"></textarea></div>
                <label><input type="checkbox" name="borrowable" value="1" checked> Bisa dipinjam</label>
                <label><input type="checkbox" name="require_return_photo" value="1"> Foto return wajib untuk barang ini</label>
                <label><input type="checkbox" name="allow_increment" value="1"> Ini penambahan qty untuk barang yang sudah tercatat</label>
                <button class="btn primary" type="submit">Simpan ke inventaris unit</button>
            </form>
        </div>
    </div>
</div>
@endsection
