<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', 'Peminjaman Barang')</title>
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
</head>
<body>
<div class="public-shell">
    <div class="public-wrap">
        <div class="public-brand">
            <h1>@yield('heading', 'Peminjaman Barang')</h1>
            <p>@yield('subheading', 'Sarpras')</p>
        </div>
        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert error">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        @yield('content')
    </div>
</div>
@yield('scripts')
</body>
</html>
