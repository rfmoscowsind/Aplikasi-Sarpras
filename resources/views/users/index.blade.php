@extends('layouts.app')
@section('title', 'Akun')
@section('content')
<div class="two-col">
    <div class="panel">
        <div class="panel-head"><h2>Daftar Akun</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Unit</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge">{{ strtoupper($user->system_role) }}</span></td>
                        <td>{{ $user->unitMemberships->pluck('unit.name')->filter()->join(', ') ?: '-' }}</td>
                        <td>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td><a class="btn sm outline" href="{{ route('users.edit', $user) }}">Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-body">{{ $users->links() }}</div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Buat Akun</h2></div>
        <div class="panel-body">
            <form method="post" action="{{ route('users.store') }}" class="stack">
                @csrf
                <div class="field"><label>Nama</label><input class="input" name="name" required></div>
                <div class="field"><label>Email</label><input class="input" type="email" name="email" required></div>
                <div class="field"><label>Password awal</label><input class="input" type="password" name="password" minlength="10" required></div>
                <div class="field"><label>Role sistem</label>
                    <select class="select" name="system_role">
                        <option value="unit">Unit / Kaprodi</option>
                        <option value="sarpras">Petugas Sarpras</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <button class="btn primary" type="submit">Buat akun</button>
            </form>
        </div>
    </div>
</div>
@endsection
