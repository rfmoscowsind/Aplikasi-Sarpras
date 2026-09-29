<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\Item;
use App\Models\Unit;
use App\Services\AuditService;
use App\Services\DistributionService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DistributionController extends Controller
{
    public function __construct(
        private readonly DistributionService $service,
        private readonly StockService $stock,
        private readonly AuditService $audit,
    ) {
    }

    public function index()
    {
        $distributions = Distribution::query()
            ->with(['sourceUnit:id,name,code', 'targetUnit:id,name,code'])
            ->latest()
            ->paginate(30);

        return view('distributions.index', compact('distributions'));
    }

    public function create()
    {
        $sourceUnits = Unit::query()->where('type', 'central')->orderBy('name')->get();
        $targetUnits = Unit::query()->where('type', '!=', 'central')->orderBy('name')->get();
        $items = Item::query()->orderBy('name')->get(['id', 'name']);

        return view('distributions.create', compact('sourceUnits', 'targetUnits', 'items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_unit_id' => ['required', 'exists:units,id'],
            'target_unit_id' => ['required', 'different:source_unit_id', 'exists:units,id'],
            'recipient_name' => ['nullable', 'string', 'max:190'],
            'recipient_title' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.item_id' => ['required', 'distinct', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $source = Unit::query()->where('type', 'central')->findOrFail($data['source_unit_id']);

        $distribution = DB::transaction(function () use ($data, $source, $request) {
            $distribution = Distribution::create([
                'public_id' => (string) Str::uuid(),
                'source_unit_id' => $source->id,
                'target_unit_id' => $data['target_unit_id'],
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_title' => $data['recipient_title'] ?? null,
                'status' => 'awaiting_signed_document',
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $distribution->document_number = sprintf(
                'BAST-SARPRAS/%s/%06d',
                now()->format('Y'),
                $distribution->id,
            );
            $distribution->save();

            foreach ($data['items'] as $row) {
                $distribution->items()->create([
                    'item_id' => $row['item_id'],
                    'quantity' => $row['quantity'],
                ]);

                $this->stock->reserve(
                    $source->id,
                    (int) $row['item_id'],
                    (int) $row['quantity'],
                    'distribution_allocated',
                    Distribution::class,
                    $distribution->id,
                    $request->user()->id,
                );
            }

            $this->audit->log($request->user(), 'distribution.created', $distribution, null, [
                'target_unit_id' => $data['target_unit_id'],
            ]);

            return $distribution;
        }, 3);

        return redirect()->route('distributions.show', $distribution)
            ->with('success', 'Distribusi dibuat. Cetak surat, tanda tangani, lalu upload kembali.');
    }

    public function show(Distribution $distribution)
    {
        $distribution->load(['sourceUnit', 'targetUnit', 'items.item']);

        return view('distributions.show', compact('distribution'));
    }

    public function letter(Distribution $distribution)
    {
        $distribution->load(['sourceUnit', 'targetUnit', 'items.item']);

        return view('distributions.letter', compact('distribution'));
    }

    public function uploadSigned(Request $request, Distribution $distribution)
    {
        abort_if(in_array($distribution->status, ['completed', 'cancelled'], true), 422, 'Distribusi sudah final.');

        $request->validate([
            'signed_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $old = $distribution->signed_document_object_key;
        $path = $request->file('signed_document')->store('distributions/signed/'.$distribution->id);

        $distribution->update([
            'signed_document_object_key' => $path,
            'status' => 'awaiting_signed_document',
        ]);

        if ($old) {
            Storage::delete($old);
        }

        $this->audit->log($request->user(), 'distribution.signed_document_uploaded', $distribution);

        return back()->with('success', 'Surat bertanda tangan berhasil di-upload.');
    }

    public function complete(Request $request, Distribution $distribution)
    {
        $this->service->complete($distribution, $request->user());

        return back()->with('success', 'Distribusi selesai dan stok sudah dipindahkan.');
    }

    public function cancel(Request $request, Distribution $distribution)
    {
        $this->service->cancel($distribution, $request->user());

        return back()->with('success', 'Distribusi dibatalkan dan alokasi stok dikembalikan.');
    }

    public function signedDocument(Distribution $distribution)
    {
        abort_unless($distribution->signed_document_object_key, 404);

        return Storage::download($distribution->signed_document_object_key);
    }
}
