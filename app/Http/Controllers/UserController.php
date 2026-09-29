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

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
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

        if ($user->is($request->user()) && !$request->boolean('is_active')) {
            return back()
                ->withErrors(['is_active' => 'Akun yang sedang digunakan tidak boleh menonaktifkan dirinya sendiri.'])
                ->withInput();
        }

        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }
}
