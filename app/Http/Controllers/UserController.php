<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private const ROLES = ['admin', 'supervisor', 'operator', 'viewer'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'roles' => self::ROLES,
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => self::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => self::ROLES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ((int) auth()->id() === (int) $user->id) {
            $data['is_active'] = true;
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function activate(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        return back()->with('status', 'Usuario activado correctamente.');
    }

    public function disable(User $user): RedirectResponse
    {
        if ((int) auth()->id() === (int) $user->id) {
            return back()->withErrors([
                'user' => 'No puedes deshabilitar tu propio usuario.',
            ]);
        }

        $user->update(['is_active' => false]);

        return back()->with('status', 'Usuario deshabilitado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ((int) auth()->id() === (int) $user->id) {
            return back()->withErrors([
                'user' => 'No puedes eliminar tu propio usuario.',
            ]);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }
}
