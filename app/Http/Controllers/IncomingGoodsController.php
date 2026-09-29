<?php

namespace App\Http\Controllers;

use App\Models\IncomingGood;
use App\Models\IncomingGoodItem;
use App\Models\Item;
use App\Models\Unit;
use App\Services\IncomingGoodsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncomingGoodsController extends Controller
{
    public function __construct(private readonly IncomingGoodsService $service)
    {
    }

    public function index()
    {
        $entries = IncomingGood::query()
            ->with(['centralUnit:id,name,code', 'items.item:id,name'])
            ->latest('received_at')
            ->latest('id')
            ->paginate(30);

        return view('incoming.index', compact('entries'));
    }

    public function create()
    {
        $centralUnits = Unit::query()->where('type', 'central')->orderBy('name')->get();
        $items = Item::query()->orderBy('name')->limit(300)->get(['id', 'name', 'specification']);

        return view('incoming.create', compact('centralUnits', 'items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'central_unit_id' => ['required', 'exists:units,id'],
            'received_at' => ['required', 'date'],
            'supplier' => ['required', 'string', 'max:190'],
            'invoice_number' => ['nullable', 'string', 'max:190'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.item_id' => ['nullable', 'exists:items,id'],
            'items.*.name' => ['required_without:items.*.item_id', 'nullable', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:3000'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.borrowable' => ['nullable', 'boolean'],
            'items.*.require_return_photo' => ['nullable', 'boolean'],
            'items.*.photo' => ['required', 'image', 'max:8192'],
        ]);

        $centralUnit = Unit::query()->where('type', 'central')->findOrFail($data['central_unit_id']);

        $storedPaths = [];

        try {
            $invoicePath = null;
            if ($request->hasFile('invoice_file')) {
                $invoicePath = $request->file('invoice_file')->store('incoming/invoices');
                $storedPaths[] = $invoicePath;
            }

            $normalizedItems = [];
            foreach ($data['items'] as $index => $row) {
                $photoPath = null;
                if ($request->hasFile("items.$index.photo")) {
                    $photoPath = $request->file("items.$index.photo")->store('incoming/photos');
                    $storedPaths[] = $photoPath;
                }

                $normalizedItems[] = [
                    'item_id' => $row['item_id'] ?? null,
                    'name' => $row['name'] ?? null,
                    'specification' => $row['specification'] ?? null,
                    'quantity' => (int) $row['quantity'],
                    'borrowable' => (bool) ($row['borrowable'] ?? true),
                    'require_return_photo' => (bool) ($row['require_return_photo'] ?? false),
                    'photo_object_key' => $photoPath,
                ];
            }

            $incoming = $this->service->create($centralUnit, $request->user(), [
                'received_at' => $data['received_at'],
                'supplier' => $data['supplier'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'invoice_object_key' => $invoicePath,
                'notes' => $data['notes'] ?? null,
                'items' => $normalizedItems,
            ]);
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::delete($path);
            }
            throw $exception;
        }

        return redirect()->route('incoming.show', $incoming)
            ->with('success', 'Barang masuk berhasil dicatat.');
    }

    public function show(IncomingGood $incoming)
    {
        $incoming->load(['centralUnit', 'items.item']);

        return view('incoming.show', compact('incoming'));
    }

    public function photo(IncomingGood $incoming, IncomingGoodItem $item)
    {
        abort_unless($item->incoming_good_id === $incoming->id, 404);
        abort_unless($item->photo_object_key, 404);

        return Storage::response(
            $item->photo_object_key,
            basename($item->photo_object_key),
            ['Cache-Control' => 'private, no-store, max-age=0'],
        );
    }

    public function invoice(IncomingGood $incoming)
    {
        abort_unless($incoming->invoice_object_key, 404);

        return Storage::download($incoming->invoice_object_key);
    }
}
