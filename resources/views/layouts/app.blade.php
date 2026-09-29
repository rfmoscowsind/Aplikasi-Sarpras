<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sarpras') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">Sarpras<small>SMKN 1 Blora</small></div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('units.index') }}">Unit / Prodi</a>
            <a href="{{ route('borrowings.index') }}">Peminjaman</a>
            @if(auth()->user()->isSarpras())
                <div class="sep">Sarpras Pusat</div>
                <a href="{{ route('incoming.index') }}">Barang Masuk</a>
                <a href="{{ route('distributions.index') }}">Distribusi</a>
            @endif
            @if(auth()->user()->isSystemAdmin())
                <div class="sep">Administrasi</div>
                <a href="{{ route('users.index') }}">Akun</a>
            @endif
        </nav>
        <div class="sidebar-bottom">
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn outline" style="width:100%" type="submit">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="mobile-nav">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('units.index') }}">Unit</a>
            <a href="{{ route('borrowings.index') }}">Peminjaman</a>
            @if(auth()->user()->isSarpras())
                <a href="{{ route('incoming.index') }}">Barang Masuk</a>
                <a href="{{ route('distributions.index') }}">Distribusi</a>
            @endif
        </div>
        <header class="topbar">
            <h1>@yield('title', 'Dashboard')</h1>
            <div class="user-chip">
                <strong>{{ auth()->user()->name }}</strong><br>
                {{ strtoupper(auth()->user()->system_role) }}
            </div>
        </header>
        <section class="content">
            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert error">
                    <strong>Ada yang perlu diperbaiki:</strong>
                    <ul style="margin:6px 0 0 18px;padding:0">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>
