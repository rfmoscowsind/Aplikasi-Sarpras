<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitMembership;
use App\Models\User;
use App\Services\UnitAccessService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function __construct(private readonly UnitAccessService $access)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $units = $user->isSarpras()
            ? Unit::query()->withCount('stocks')->orderBy('name')->get()
            : Unit::query()
                ->whereIn('id', $user->unitMemberships()->pluck('unit_id'))
                ->withCount('stocks')
                ->orderBy('name')
                ->get();

        return view('units.index', compact('units'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isSystemAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:units,code'],
            'type' => ['required', Rule::in(['central', 'program', 'department', 'other'])],
        ]);

        Unit::create([
            ...$data,
            'code' => strtoupper($data['code']),
            'borrow_public_token' => Str::random(48),
            'borrowing_enabled' => $data['type'] !== 'central',
        ]);

        return back()->with('success', 'Unit berhasil dibuat.');
    }

    public function show(Request $request, Unit $unit)
    {
        abort_unless($this->access->canView($request->user(), $unit), 403);

        $unit->load([
            'stocks.item',
            'stocks.creator:id,name',
            'memberships.user:id,name,email,system_role',
        ]);

        $users = $request->user()->isSystemAdmin()
            ? User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('units.show', compact('unit', 'users'));
    }

    public function updateBorrowPin(Request $request, Unit $unit)
    {
        abort_unless($this->access->canManageBorrowing($request->user(), $unit), 403);

        $data = $request->validate([
            'pin' => ['required', 'digits:6', 'confirmed'],
        ]);

        $unit->update([
            'borrow_pin_hash' => Hash::make($data['pin']),
            'borrow_pin_version' => $unit->borrow_pin_version + 1,
            'borrowing_enabled' => true,
        ]);

        return back()->with('success', 'PIN peminjaman unit berhasil diganti.');
    }

    public function rotateBorrowToken(Request $request, Unit $unit)
    {
        abort_unless($this->access->canManageBorrowing($request->user(), $unit), 403);

        $unit->update([
            'borrow_public_token' => Str::random(48),
            'borrow_pin_version' => $unit->borrow_pin_version + 1,
        ]);

        return back()->with('success', 'QR/token publik berhasil dirotasi. QR lama tidak berlaku.');
    }

    public function qr(Request $request, Unit $unit)
    {
        abort_unless($this->access->canView($request->user(), $unit), 403);
        abort_unless($unit->borrow_public_token, 404);

        $url = route('public.borrow.start', ['token' => $unit->borrow_public_token]);

        $result = (new Builder(
            writer: new SvgWriter(),
            data: $url,
            size: 360,
            margin: 12,
        ))->build();

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="qr-'.$unit->code.'.svg"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function storeMembership(Request $request, Unit $unit)
    {
        abort_unless($request->user()->isSystemAdmin(), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', Rule::in(['head', 'staff', 'member'])],
            'can_manage_inventory' => ['nullable', 'boolean'],
            'can_manage_borrowing' => ['nullable', 'boolean'],
        ]);

        UnitMembership::query()->updateOrCreate(
            ['unit_id' => $unit->id, 'user_id' => $data['user_id']],
            [
                'role' => $data['role'],
                'can_manage_inventory' => $data['role'] === 'head' || $request->boolean('can_manage_inventory'),
                'can_manage_borrowing' => $data['role'] === 'head' || $request->boolean('can_manage_borrowing'),
            ],
        );

        return back()->with('success', 'Keanggotaan unit berhasil disimpan.');
    }

    public function destroyMembership(Request $request, Unit $unit, UnitMembership $membership)
    {
        abort_unless($request->user()->isSystemAdmin(), 403);
        abort_unless($membership->unit_id === $unit->id, 404);

        $membership->delete();

        return back()->with('success', 'Keanggotaan unit dihapus.');
    }
}
