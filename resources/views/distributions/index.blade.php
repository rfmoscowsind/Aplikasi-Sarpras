@extends('layouts.app')
@section('title', 'Distribusi Barang')
@section('content')
<div class="actions" style="margin-bottom:16px"><a class="btn primary" href="{{ route('distributions.create') }}">Buat Distribusi</a></div>
<div class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>No. Dokumen</th><th>Dari</th><th>Tujuan</th><th>Status</th><th>Dibuat</th><th></th></tr></thead>
            <tbody>
            @forelse($distributions as $distribution)
                <tr>
                    <td>{{ $distribution->document_number ?: '-' }}</td>
                    <td>{{ $distribution->sourceUnit->name }}</td>
                    <td>{{ $distribution->targetUnit->name }}</td>
                    <td><span class="badge {{ $distribution->status === 'completed' ? 'good' : 'warn' }}">{{ str_replace('_', ' ', $distribution->status) }}</span></td>
                    <td>{{ $distribution->created_at->format('d/m/Y H:i') }}</td>
                    <td><a class="btn sm" href="{{ route('distributions.show', $distribution) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Belum ada distribusi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $distributions->links() }}</div>
</div>
@endsection
