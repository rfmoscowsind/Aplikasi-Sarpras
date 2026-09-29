@extends('layouts.app')
@section('title', 'Unit / Program Keahlian')
@section('content')
@if(auth()->user()->isSystemAdmin())
<div class="panel">
    <div class="panel-head"><h2>Tambah Unit</h2></div>
    <div class="panel-body">
        <form method="post" action="{{ route('units.store') }}" class="form-grid">
            @csrf
            <div class="field"><label>Nama Unit</label><input class="input" name="name" required></div>
            <div class="field"><label>Kode</label><input class="input" name="code" required placeholder="TJKT"></div>
            <div class="field"><label>Tipe</label>
                <select class="select" name="type">
                    <option value="program">Program Keahlian</option>
                    <option value="department">Unit / Bagian</option>
                    <option value="other">Lainnya</option>
                    <option value="central">Sarpras Pusat</option>
                </select>
            </div>
            <div class="field" style="align-self:end"><button class="btn primary" type="submit">Tambah Unit</button></div>
        </form>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-head"><h2>Unit yang Bisa Diakses</h2></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Unit</th><th>Kode</th><th>Tipe</th><th>Jenis Inventaris</th><th></th></tr></thead>
            <tbody>
            @forelse($units as $unit)
                <tr>
                    <td><strong>{{ $unit->name }}</strong></td>
                    <td>{{ $unit->code }}</td>
                    <td>{{ $unit->type }}</td>
                    <td>{{ number_format($unit->stocks_count) }}</td>
                    <td><a class="btn sm" href="{{ route('units.show', $unit) }}">Buka</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Belum ada unit.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
