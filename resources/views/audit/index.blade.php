@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')
<div class="panel">
    <div class="panel-head"><h2>Filter Audit</h2></div>
    <div class="panel-body">
        <form method="get" class="form-grid">
            <div class="field"><label>Action</label><input class="input" name="action" value="{{ request('action') }}" placeholder="borrowing."></div>
            <div class="field"><label>Aktor</label><select class="select" name="actor_id"><option value="">Semua</option>@foreach($actors as $actor)<option value="{{ $actor->id }}" @selected((string)request('actor_id')===(string)$actor->id)>{{ $actor->name }}</option>@endforeach</select></div>
            <div class="field"><label>Subject type</label><input class="input" name="subject_type" value="{{ request('subject_type') }}" placeholder="Borrowing"></div>
            <div class="field" style="align-self:end"><button class="btn primary">Terapkan</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Aktor</th><th>Action</th><th>Subject</th><th>Perubahan / Metadata</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->actor?->name ?? 'Public/System' }}</td>
                    <td><span class="badge">{{ $log->action }}</span></td>
                    <td><span class="small">{{ class_basename($log->subject_type) }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</span></td>
                    <td class="small">
                        @if($log->before)<details><summary>Before</summary><pre style="white-space:pre-wrap">{{ json_encode($log->before, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>@endif
                        @if($log->after)<details><summary>After</summary><pre style="white-space:pre-wrap">{{ json_encode($log->after, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>@endif
                        @if(!$log->before && !$log->after)-@endif
                    </td>
                    <td class="small muted">{{ $log->ip_address ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Belum ada audit log.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $logs->links() }}</div>
</div>
@endsection
