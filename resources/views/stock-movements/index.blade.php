@extends('layouts.app')
@section('title', 'Stock Ledger')
@section('content')
<div class="panel">
    <div class="panel-head"><h2>Filter Pergerakan Stok</h2></div>
    <div class="panel-body">
        <form method="get" class="form-grid">
            <div class="field"><label>Unit</label><select class="select" name="unit_id"><option value="">Semua</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string)request('unit_id')===(string)$unit->id)>{{ $unit->name }}</option>@endforeach</select></div>
            <div class="field"><label>Barang</label><select class="select" name="item_id"><option value="">Semua</option>@foreach($items as $item)<option value="{{ $item->id }}" @selected((string)request('item_id')===(string)$item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="field"><label>Bucket</label><select class="select" name="bucket"><option value="">Semua</option>@foreach(['available','reserved','borrowed','damaged','lost'] as $bucket)<option value="{{ $bucket }}" @selected(request('bucket')===$bucket)>{{ ucfirst($bucket) }}</option>@endforeach</select></div>
            <div class="field"><label>Reason</label><input class="input" name="reason" value="{{ request('reason') }}" placeholder="distribution"></div>
            <div class="field" style="align-self:end"><button class="btn primary">Terapkan</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Unit</th><th>Barang</th><th>Bucket</th><th>Delta</th><th>Reason</th><th>Reference</th><th>Aktor</th></tr></thead>
            <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td>{{ $movement->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $movement->unit?->name ?? '#'.$movement->unit_id }}</td>
                    <td>{{ $movement->item?->name ?? '#'.$movement->item_id }}</td>
                    <td><span class="badge">{{ $movement->bucket }}</span></td>
                    <td><strong style="color:{{ $movement->delta >= 0 ? 'var(--primary)' : 'var(--danger)' }}">{{ $movement->delta >= 0 ? '+' : '' }}{{ $movement->delta }}</strong></td>
                    <td>{{ $movement->reason }}</td>
                    <td class="small">{{ $movement->reference_type ? class_basename($movement->reference_type).' #'.$movement->reference_id : '-' }}</td>
                    <td>{{ $movement->actor?->name ?? 'System/Public' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">Belum ada pergerakan stok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $movements->links() }}</div>
</div>
@endsection
