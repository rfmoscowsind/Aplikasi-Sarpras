@extends('layouts.app')
@section('title', 'Catat Barang Masuk')
@section('content')
<form method="post" enctype="multipart/form-data" action="{{ route('incoming.store') }}" class="stack">
    @csrf
    <div class="panel">
        <div class="panel-head"><h2>Informasi Penerimaan</h2></div>
        <div class="panel-body form-grid">
            <div class="field"><label>Unit Sarpras Pusat</label><select class="select" name="central_unit_id" required>@foreach($centralUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></div>
            <div class="field"><label>Tanggal barang masuk</label><input class="input" type="date" name="received_at" value="{{ date('Y-m-d') }}" required></div>
            <div class="field"><label>Supplier / Vendor</label><input class="input" name="supplier" required></div>
            <div class="field"><label>Nomor Invoice (opsional)</label><input class="input" name="invoice_number"></div>
            <div class="field"><label>File Invoice (opsional)</label><input class="input" type="file" name="invoice_file" accept=".pdf,image/*"></div>
            <div class="field"><label>Catatan</label><textarea class="textarea" name="notes"></textarea></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Barang</h2><button class="btn sm" type="button" id="add-item">+ Tambah baris</button></div>
        <div class="panel-body stack" id="item-rows"></div>
    </div>
    <button class="btn primary" type="submit">Simpan Barang Masuk</button>
</form>

<template id="item-template">
    <div class="card incoming-row">
        <div class="form-grid">
            <div class="field"><label>Katalog (opsional)</label>
                <select class="select" data-name="item_id"><option value="">Barang baru</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
            </div>
            <div class="field"><label>Nama barang baru</label><input class="input" data-name="name"></div>
            <div class="field full"><label>Spesifikasi (opsional)</label><textarea class="textarea" data-name="specification"></textarea></div>
            <div class="field"><label>Jumlah</label><input class="input" type="number" min="1" value="1" data-name="quantity" required></div>
            <div class="field"><label>Foto barang</label><input class="input" type="file" accept="image/*" data-name="photo"></div>
            <label><input type="checkbox" value="1" data-name="borrowable" checked> Bisa dipinjam</label>
            <label><input type="checkbox" value="1" data-name="require_return_photo"> Foto return wajib</label>
        </div>
        <div style="margin-top:10px"><button class="btn sm danger remove-row" type="button">Hapus baris</button></div>
    </div>
</template>

<script>
(function () {
    const host = document.getElementById('item-rows');
    const tpl = document.getElementById('item-template');
    const add = document.getElementById('add-item');
    let index = 0;

    function addRow() {
        const fragment = tpl.content.cloneNode(true);
        fragment.querySelectorAll('[data-name]').forEach(function (el) {
            el.name = 'items[' + index + '][' + el.dataset.name + ']';
        });
        fragment.querySelector('.remove-row').addEventListener('click', function (event) {
            if (host.children.length > 1) {
                event.target.closest('.incoming-row').remove();
            }
        });
        host.appendChild(fragment);
        index++;
    }

    add.addEventListener('click', addRow);
    addRow();
})();
</script>
@endsection
