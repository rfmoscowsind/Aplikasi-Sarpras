<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
</head>
<body>
<div class="public-shell" style="display:grid;place-items:center">
    <div style="width:min(430px,100%)">
        <div class="public-brand"><h1>Aplikasi Sarpras</h1><p>Masuk untuk mengelola inventaris dan peminjaman.</p></div>
        <div class="panel"><div class="panel-body">
            @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('login.store') }}" class="stack">
                @csrf
                <div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
                <div class="field"><label>Password</label><input class="input" type="password" name="password" required></div>
                <button class="btn primary" type="submit">Masuk</button>
            </form>
        </div></div>
    </div>
</div>
</body>
</html>
