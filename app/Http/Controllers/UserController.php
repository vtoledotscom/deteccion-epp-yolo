<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\SearchNormalizer;
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
        $matchingRoles = SearchNormalizer::matchingUserRoles($search);

        $users = User::query()
            ->when($search !== '', function ($query) use ($search, $matchingRoles) {
                $query->where(function ($query) use ($search, $matchingRoles) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");

                    if ($matchingRoles !== []) {
                        $query->orWhereIn('role', $matchingRoles);
                    }
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLogger::log(
            'create_user',
            'users',
            'Creación de usuario',
            'user',
            $user->id,
            [
                'target_email' => $user->email,
                'target_role' => $user->role,
                'is_active' => $user->is_active,
            ],
            request: $request,
        );

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
        $previous = [
            'role' => $user->role,
            'is_active' => $user->is_active,
        ];

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

        ActivityLogger::log(
            'update_user',
            'users',
            'Edición de usuario',
            'user',
            $user->id,
            [
                'target_email' => $user->email,
                'previous_role' => $previous['role'],
                'new_role' => $user->role,
                'previous_is_active' => $previous['is_active'],
                'new_is_active' => $user->is_active,
                'password_changed' => $request->filled('password'),
            ],
            request: $request,
        );

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function activate(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        ActivityLogger::log(
            'activate_user',
            'users',
            'Activación de usuario',
            'user',
            $user->id,
            [
                'target_email' => $user->email,
                'target_role' => $user->role,
            ],
        );

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

        ActivityLogger::log(
            'disable_user',
            'users',
            'Deshabilitación de usuario',
            'user',
            $user->id,
            [
                'target_email' => $user->email,
                'target_role' => $user->role,
            ],
        );

        return back()->with('status', 'Usuario deshabilitado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ((int) auth()->id() === (int) $user->id) {
            return back()->withErrors([
                'user' => 'No puedes eliminar tu propio usuario.',
            ]);
        }

        $metadata = [
            'target_email' => $user->email,
            'target_role' => $user->role,
            'was_active' => $user->is_active,
        ];
        $targetId = $user->id;

        $user->delete();

        ActivityLogger::log(
            'delete_user',
            'users',
            'Eliminación de usuario',
            'user',
            $targetId,
            $metadata,
        );

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }
}
