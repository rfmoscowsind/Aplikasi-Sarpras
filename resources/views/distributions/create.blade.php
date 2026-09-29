@extends('layouts.app')
@section('title', 'Buat Distribusi')
@section('content')
<form method="post" action="{{ route('distributions.store') }}" class="stack">
    @csrf
    <div class="panel">
        <div class="panel-head"><h2>Tujuan Distribusi</h2></div>
        <div class="panel-body form-grid">
            <div class="field"><label>Dari</label><select class="select" name="source_unit_id" required>@foreach($sourceUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></div>
            <div class="field"><label>Ke Unit / Prodi</label><select class="select" name="target_unit_id" required>@foreach($targetUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></div>
            <div class="field"><label>Nama penerima (opsional)</label><input class="input" name="recipient_name"></div>
            <div class="field"><label>Jabatan penerima (opsional)</label><input class="input" name="recipient_title" placeholder="Kaprodi / Kepala Unit"></div>
            <div class="field full"><label>Catatan</label><textarea class="textarea" name="notes"></textarea></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Barang yang Didistribusikan</h2><button type="button" class="btn sm" id="add-distribution-item">+ Tambah barang</button></div>
        <div class="panel-body stack" id="distribution-items"></div>
    </div>
    <button class="btn primary" type="submit">Buat dan Generate Surat</button>
</form>

<template id="distribution-template">
    <div class="item-row distribution-row">
        <select class="select" data-name="item_id" required>
            <option value="">Pilih barang</option>
            @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
        </select>
        <input class="input" type="number" min="1" value="1" data-name="quantity" required>
        <button type="button" class="btn danger remove-distribution-row">×</button>
    </div>
</template>
<script>
(function () {
    const host = document.getElementById('distribution-items');
    const template = document.getElementById('distribution-template');
    let index = 0;
    function addRow() {
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[data-name]').forEach(function (el) {
            el.name = 'items[' + index + '][' + el.dataset.name + ']';
        });
        fragment.querySelector('.remove-distribution-row').addEventListener('click', function (event) {
            if (host.children.length > 1) event.target.closest('.distribution-row').remove();
        });
        host.appendChild(fragment);
        index++;
    }
    document.getElementById('add-distribution-item').addEventListener('click', addRow);
    addRow();
})();
</script>
@endsection
