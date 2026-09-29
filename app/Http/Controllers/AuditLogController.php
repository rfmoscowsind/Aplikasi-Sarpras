<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()
            ->with('actor:id,name,email')
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', trim((string) $request->input('action')).'%');
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', 'like', '%'.trim((string) $request->input('subject_type')).'%');
        }

        $logs = $query->paginate(50)->withQueryString();
        $actors = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('audit.index', compact('logs', 'actors'));
    }
}
