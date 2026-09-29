@extends('layouts.public')
@section('title', 'PIN '.$unit->name)
@section('heading', $unit->name)
@section('subheading', 'Masukkan PIN peminjaman yang diberikan unit.')
@section('content')
<div class="panel"><div class="panel-body">
    <form method="post" action="{{ route('public.borrow.pin', $unit->borrow_public_token) }}" class="stack">
        @csrf
        <div class="field">
            <label>PIN Unit</label>
            <input class="input" style="font-size:24px;text-align:center;letter-spacing:.35em" type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="pin" required autofocus>
        </div>
        <button class="btn primary" type="submit">Lanjut</button>
    </form>
</div></div>
@endsection
