<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with('unitMemberships.unit:id,name,code')
            ->orderBy('name')
            ->paginate(30);

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10'],
            'system_role' => ['required', Rule::in(['admin', 'sarpras', 'unit'])],
        ]);

        User::create([
            ...$data,
            'is_active' => true,
        ]);

        return back()->with('success', 'Akun berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'system_role' => ['required', Rule::in(['admin', 'sarpras', 'unit'])],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:10'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }
}
