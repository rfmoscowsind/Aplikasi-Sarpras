@extends('layouts.app')
@section('title', $unit->name)
@section('content')
<div class="grid cards">
    <div class="card"><div class="stat-label">Jenis Barang</div><div class="stat-value">{{ $unit->stocks->count() }}</div></div>
    <div class="card"><div class="stat-label">Total Qty</div><div class="stat-value">{{ number_format($unit->stocks->sum('total_qty')) }}</div></div>
    <div class="card"><div class="stat-label">Dipinjam</div><div class="stat-value">{{ number_format($unit->stocks->sum('borrowed_qty')) }}</div></div>
    <div class="card"><div class="stat-label">Rusak</div><div class="stat-value">{{ number_format($unit->stocks->sum('damaged_qty')) }}</div></div>
</div>

<div class="actions" style="margin-bottom:18px">
    <a class="btn primary" href="{{ route('inventory.index', $unit) }}">Inventaris Unit</a>
    @if($unit->borrowing_enabled && $unit->borrow_public_token)
        <a class="btn outline" href="{{ route('units.qr', $unit) }}" target="_blank">Buka QR SVG</a>
        <a class="btn outline" href="{{ route('public.borrow.start', $unit->borrow_public_token) }}" target="_blank">Tes Form Publik</a>
    @endif
</div>

<div class="two-col">
    <div class="panel">
        <div class="panel-head"><h2>Inventaris Ringkas</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Barang</th><th>Total</th><th>Tersedia</th><th>Dipinjam</th><th>Rusak</th><th>Dicatat oleh</th></tr></thead>
                <tbody>
                @forelse($unit->stocks as $stock)
                    <tr>
                        <td>{{ $stock->item->name }}</td>
                        <td>{{ $stock->total_qty }}</td>
                        <td>{{ $stock->available_qty }}</td>
                        <td>{{ $stock->borrowed_qty }}</td>
                        <td>{{ $stock->damaged_qty }}</td>
                        <td>{{ $stock->creator?->name ?? '-' }}<div class="small muted">{{ $stock->acquisition_source }}</div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Belum ada inventaris.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="stack">
        @if($unit->type !== 'central')
        <div class="panel">
            <div class="panel-head"><h2>Keamanan Form Peminjaman</h2></div>
            <div class="panel-body stack">
                <div>
                    <div class="small muted">URL publik</div>
                    <div class="code" style="overflow-wrap:anywhere">{{ $unit->borrow_public_token ? route('public.borrow.start', $unit->borrow_public_token) : '-' }}</div>
                </div>
                <form method="post" action="{{ route('units.borrow-pin', $unit) }}" class="stack">
                    @csrf
                    <div class="field"><label>PIN baru (6 digit)</label><input class="input" type="password" inputmode="numeric" name="pin" pattern="[0-9]{6}" required></div>
                    <div class="field"><label>Ulangi PIN</label><input class="input" type="password" inputmode="numeric" name="pin_confirmation" pattern="[0-9]{6}" required></div>
                    <button class="btn primary" type="submit">Ganti PIN</button>
                </form>
                <form method="post" action="{{ route('units.rotate-token', $unit) }}" onsubmit="return confirm('QR lama akan langsung tidak berlaku. Lanjut?')">
                    @csrf
                    <button class="btn danger" type="submit">Rotate QR / Token</button>
                </form>
            </div>
        </div>
        @endif

        <div class="panel">
            <div class="panel-head"><h2>Pengelola Unit</h2></div>
            <div class="panel-body stack">
                @forelse($unit->memberships as $membership)
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <div><strong>{{ $membership->user->name }}</strong><div class="small muted">{{ $membership->role }}</div></div>
                        @if(auth()->user()->isSystemAdmin())
                        <form method="post" action="{{ route('units.memberships.destroy', [$unit, $membership]) }}">
                            @csrf @method('DELETE')
                            <button class="btn sm danger">Hapus</button>
                        </form>
                        @endif
                    </div>
                @empty
                    <div class="muted">Belum ada pengelola unit.</div>
                @endforelse

                @if(auth()->user()->isSystemAdmin())
                <hr style="border:0;border-top:1px solid var(--line);width:100%">
                <form method="post" action="{{ route('units.memberships.store', $unit) }}" class="stack">
                    @csrf
                    <div class="field"><label>Akun</label><select class="select" name="user_id" required>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>@endforeach</select></div>
                    <div class="field"><label>Peran unit</label><select class="select" name="role"><option value="head">Kaprodi / Kepala Unit</option><option value="staff">Petugas</option><option value="member">Anggota</option></select></div>
                    <label><input type="checkbox" name="can_manage_inventory" value="1"> Kelola inventaris</label>
                    <label><input type="checkbox" name="can_manage_borrowing" value="1"> Kelola peminjaman</label>
                    <button class="btn" type="submit">Simpan akses</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
