<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\ReturnRequest;
use App\Models\Unit;
use App\Models\UnitStock;
use App\Services\AuditService;
use App\Services\JuaraStudentDirectory;
use App\Services\PhoneNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicBorrowController extends Controller
{
    private const ACCESS_TTL_MINUTES = 60;

    public function __construct(
        private readonly JuaraStudentDirectory $students,
        private readonly PhoneNormalizer $phones,
        private readonly AuditService $audit,
    ) {
    }

    public function start(Request $request, string $token)
    {
        $unit = $this->unit($token);

        if (!$this->hasAccess($request, $unit)) {
            return view('public.borrow.pin', compact('unit'));
        }

        return view('public.borrow.form', compact('unit'));
    }

    public function verifyPin(Request $request, string $token)
    {
        $unit = $this->unit($token);

        $data = $request->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        if (!$unit->borrow_pin_hash || !Hash::check($data['pin'], $unit->borrow_pin_hash)) {
            throw ValidationException::withMessages([
                'pin' => 'PIN unit salah.',
            ]);
        }

        $request->session()->put($this->sessionKey($unit), [
            'version' => $unit->borrow_pin_version,
            'expires_at' => now()->addMinutes(self::ACCESS_TTL_MINUTES)->timestamp,
        ]);

        return redirect()->route('public.borrow.start', ['token' => $unit->borrow_public_token]);
    }

    public function students(Request $request, string $token)
    {
        $unit = $this->unit($token);
        $this->requireAccess($request, $unit);

        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        return response()->json([
            'students' => $this->students->search($data['q'])->values(),
        ]);
    }

    public function items(Request $request, string $token)
    {
        $unit = $this->unit($token);
        $this->requireAccess($request, $unit);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $q = trim((string) ($data['q'] ?? ''));

        $query = UnitStock::query()
            ->where('unit_id', $unit->id)
            ->where('available_qty', '>', 0)
            ->whereHas('item', fn ($builder) => $builder->where('borrowable', true))
            ->with('item:id,name,specification,borrowable');

        if ($q !== '') {
            if (mb_strlen($q) < 2) {
                return response()->json(['items' => []]);
            }

            $query->whereHas('item', function ($builder) use ($q) {
                $builder->where('borrowable', true)
                    ->where('name', 'like', $q.'%');
            });
        }

        $items = $query->orderByDesc('available_qty')
            ->limit(10)
            ->get()
            ->map(fn (UnitStock $stock) => [
                'item_id' => $stock->item_id,
                'name' => $stock->item->name,
                'specification' => $stock->item->specification,
                'available_qty' => $stock->available_qty,
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    public function activeLoans(Request $request, string $token)
    {
        $unit = $this->unit($token);
        $this->requireAccess($request, $unit);

        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $q = trim($data['q']);

        $loans = Borrowing::query()
            ->where('unit_id', $unit->id)
            ->whereIn('status', ['borrowed', 'return_pending', 'partially_returned'])
            ->where(function ($builder) use ($q) {
                $builder->where('student_name', 'like', $q.'%')
                    ->orWhere('student_nis', 'like', $q.'%')
                    ->orWhere('public_id', 'like', $q.'%');
            })
            ->with('items.item:id,name')
            ->latest('borrowed_at')
            ->limit(10)
            ->get()
            ->map(fn (Borrowing $borrowing) => [
                'public_id' => $borrowing->public_id,
                'student_name' => $borrowing->student_name,
                'student_nis' => $borrowing->student_nis,
                'student_class' => $borrowing->student_class,
                'expected_return_at' => $borrowing->expected_return_at?->format('d/m/Y H:i'),
                'items' => $borrowing->items->map(fn ($item) => [
                    'name' => $item->item->name,
                    'outstanding_qty' => $item->outstandingQty(),
                ])->filter(fn ($item) => $item['outstanding_qty'] > 0)->values(),
            ]);

        return response()->json(['borrowings' => $loans]);
    }

    public function store(Request $request, string $token)
    {
        $unit = $this->unit($token);
        $this->requireAccess($request, $unit);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:30'],
            'purpose' => ['required', 'string', 'max:1500'],
            'expected_return_at' => ['required', 'date', 'after:now'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.item_id' => ['required', 'integer', 'distinct', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $student = $this->students->find((int) $data['student_id']);

        if (!$student) {
            throw ValidationException::withMessages([
                'student_id' => 'Data siswa tidak ditemukan di JUARA.',
            ]);
        }

        $phone = $this->phones->normalize($data['phone']);

        $borrowing = DB::transaction(function () use ($unit, $student, $phone, $data, $request) {
            $borrowing = Borrowing::create([
                'public_id' => (string) Str::uuid(),
                'unit_id' => $unit->id,
                'juara_student_id' => $student->student_id,
                'student_name' => $student->name,
                'student_nis' => $student->nis,
                'student_class' => $student->class_name,
                'phone' => $phone,
                'purpose' => $data['purpose'],
                'expected_return_at' => $data['expected_return_at'],
                'status' => 'pending_approval',
            ]);

            foreach ($data['items'] as $row) {
                $stock = UnitStock::query()
                    ->where('unit_id', $unit->id)
                    ->where('item_id', $row['item_id'])
                    ->whereHas('item', fn ($query) => $query->where('borrowable', true))
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->available_qty < (int) $row['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada barang yang sudah tidak tersedia dalam jumlah yang diminta.',
                    ]);
                }

                $borrowing->items()->create([
                    'item_id' => $row['item_id'],
                    'requested_qty' => $row['quantity'],
                ]);
            }

            $this->audit->log(null, 'borrowing.public_submitted', $borrowing, null, [
                'unit_id' => $unit->id,
                'student_nis' => $student->nis,
            ], $request);

            return $borrowing;
        }, 3);

        return redirect()->route('public.borrow.transaction', [
            'token' => $unit->borrow_public_token,
            'publicId' => $borrowing->public_id,
        ])->with('success', 'Permintaan peminjaman berhasil dikirim.');
    }

    public function transaction(Request $request, string $token, string $publicId)
    {
        $unit = $this->unit($token);
        $this->requireAccess($request, $unit);

        $borrowing = Borrowing::query()
            ->where('unit_id', $unit->id)
            ->where('public_id', $publicId)
            ->with([
                'items.item',
                'returnRequests' => fn ($query) => $query->latest(),
            ])
            ->firstOrFail();

        return view('public.borrow.transaction', compact('unit', 'borrowing'));
    }

    public function storeReturn(Request $request, string $token, string $publicId)
    {
        $unit = $this->unit($token);
        $this->requireAccess($request, $unit);

        $data = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'items.*.condition' => ['nullable', Rule::in(['good', 'damaged', 'lost'])],
        ]);

        $photoPath = $request->file('photo')->store('returns/public');

        try {
            $returnRequest = DB::transaction(function () use ($unit, $publicId, $data, $photoPath, $request) {
                $borrowing = Borrowing::query()
                    ->where('unit_id', $unit->id)
                    ->where('public_id', $publicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($borrowing->status, ['borrowed', 'partially_returned'], true)) {
                    throw ValidationException::withMessages([
                        'return' => 'Transaksi belum dapat diajukan untuk pengembalian.',
                    ]);
                }

                if ($borrowing->returnRequests()->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages([
                        'return' => 'Masih ada pengembalian yang menunggu verifikasi petugas.',
                    ]);
                }

                $items = $borrowing->items()->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                $returnRequest = $borrowing->returnRequests()->create([
                    'public_id' => (string) Str::uuid(),
                    'status' => 'pending',
                    'photo_object_key' => $photoPath,
                    'borrower_notes' => $data['notes'] ?? null,
                ]);

                $submitted = 0;

                foreach ($data['items'] as $borrowingItemId => $row) {
                    $quantity = (int) ($row['quantity'] ?? 0);

                    if ($quantity < 1) {
                        continue;
                    }

                    $borrowingItem = $items->get((int) $borrowingItemId);

                    if (!$borrowingItem || $quantity > $borrowingItem->outstandingQty()) {
                        throw ValidationException::withMessages([
                            'items' => 'Jumlah pengembalian tidak valid.',
                        ]);
                    }

                    $condition = $row['condition'] ?? null;
                    if (!in_array($condition, ['good', 'damaged', 'lost'], true)) {
                        throw ValidationException::withMessages([
                            'items' => 'Kondisi barang wajib dipilih.',
                        ]);
                    }

                    $returnRequest->items()->create([
                        'borrowing_item_id' => $borrowingItem->id,
                        'quantity' => $quantity,
                        'borrower_condition' => $condition,
                    ]);

                    $submitted += $quantity;
                }

                if ($submitted < 1) {
                    throw ValidationException::withMessages([
                        'items' => 'Pilih minimal satu barang yang dikembalikan.',
                    ]);
                }

                $borrowing->update(['status' => 'return_pending']);

                $this->audit->log(null, 'return.public_submitted', $returnRequest, null, [
                    'borrowing_id' => $borrowing->id,
                ], $request);

                return $returnRequest;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::delete($photoPath);
            throw $exception;
        }

        return redirect()->route('public.borrow.transaction', [
            'token' => $unit->borrow_public_token,
            'publicId' => $publicId,
        ])->with('success', 'Pengembalian dikirim dan menunggu verifikasi petugas.');
    }

    private function unit(string $token): Unit
    {
        return Unit::query()
            ->where('borrow_public_token', $token)
            ->where('borrowing_enabled', true)
            ->firstOrFail();
    }

    private function sessionKey(Unit $unit): string
    {
        return 'borrow-unit-access.'.$unit->id;
    }

    private function hasAccess(Request $request, Unit $unit): bool
    {
        $value = $request->session()->get($this->sessionKey($unit));

        return is_array($value)
            && (int) ($value['version'] ?? 0) === (int) $unit->borrow_pin_version
            && (int) ($value['expires_at'] ?? 0) >= now()->timestamp;
    }

    private function requireAccess(Request $request, Unit $unit): void
    {
        if (!$this->hasAccess($request, $unit)) {
            abort(403, 'Sesi PIN unit sudah habis. Scan QR dan masukkan PIN lagi.');
        }
    }
}
