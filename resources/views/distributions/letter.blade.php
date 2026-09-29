<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $distribution->document_number }}</title>
<style>
body{font-family:Arial,sans-serif;color:#111;margin:36px;font-size:13px}.head{text-align:center;margin-bottom:28px}.head h1{font-size:17px;margin:0 0 6px}.head p{margin:0}.meta{margin:20px 0;line-height:1.7}table{width:100%;border-collapse:collapse;margin:18px 0}th,td{border:1px solid #222;padding:8px;text-align:left}th{background:#eee}.sign{display:grid;grid-template-columns:1fr 1fr;gap:80px;margin-top:55px;text-align:center}.space{height:75px}.no-print{margin-bottom:20px}@media print{.no-print{display:none}body{margin:15mm}}
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
<div class="head">
    <h1>BERITA ACARA SERAH TERIMA BARANG</h1>
    <p>{{ $distribution->document_number }}</p>
</div>
<p>Pada hari ini, {{ now()->translatedFormat('l, d F Y') }}, telah dilakukan serah terima barang dari <strong>{{ $distribution->sourceUnit->name }}</strong> kepada <strong>{{ $distribution->targetUnit->name }}</strong>.</p>
<div class="meta">
    Penerima: {{ $distribution->recipient_name ?: '................................................' }}<br>
    Jabatan: {{ $distribution->recipient_title ?: '................................................' }}
</div>
<table>
    <thead><tr><th style="width:50px">No</th><th>Nama Barang</th><th style="width:120px">Jumlah</th></tr></thead>
    <tbody>
    @foreach($distribution->items as $row)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $row->item->name }}</td><td>{{ $row->quantity }}</td></tr>
    @endforeach
    </tbody>
</table>
@if($distribution->notes)<p>Catatan: {{ $distribution->notes }}</p>@endif
<p>Demikian berita acara ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
<div class="sign">
    <div>Yang Menyerahkan,<div class="space"></div><strong>________________________</strong></div>
    <div>Yang Menerima,<div class="space"></div><strong>{{ $distribution->recipient_name ?: '________________________' }}</strong></div>
</div>
</body>
</html>
