@extends('layouts.app')
@section('title', 'Edit Akun')
@section('content')
<div class="panel" style="max-width:720px">
    <div class="panel-head"><h2>{{ $user->name }}</h2></div>
    <div class="panel-body">
        <form method="post" action="{{ route('users.update', $user) }}" class="stack">
            @csrf
            @method('PUT')
            <div class="field"><label>Nama</label><input class="input" name="name" value="{{ old('name', $user->name) }}" required></div>
            <div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
            <div class="field"><label>Role sistem</label>
                <select class="select" name="system_role">
                    <option value="unit" @selected(old('system_role', $user->system_role)==='unit')>Unit / Kaprodi</option>
                    <option value="sarpras" @selected(old('system_role', $user->system_role)==='sarpras')>Petugas Sarpras</option>
                    <option value="admin" @selected(old('system_role', $user->system_role)==='admin')>Administrator</option>
                </select>
            </div>
            <div class="field"><label>Password baru (kosongkan jika tidak diganti)</label><input class="input" type="password" name="password" minlength="10"></div>
            <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Akun aktif</label>
            <div class="actions">
                <button class="btn primary" type="submit">Simpan</button>
                <a class="btn outline" href="{{ route('users.index') }}">Kembali</a>
            </div>
        </form>
    </div>
</div>
@endsection
