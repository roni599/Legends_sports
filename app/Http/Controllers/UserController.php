<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'directPermissions'])->latest()->get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'business_branch' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'business_branch' => $validated['business_branch'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'password' => Hash::make($validated['password']),
        ]);

        $user->roles()->attach($validated['role_id']);
        if (isset($validated['permissions'])) {
            $user->directPermissions()->sync($validated['permissions']);
        }

        return response()->json($user->load(['roles', 'directPermissions']), 201);
    }

    public function update(Request $request, User $user)
    {
        // Don't allow modifying the super admin (id 1) easily, unless it's the admin themselves
        if ($user->id === 1 && $request->user()->id !== 1) {
            return response()->json(['message' => 'Cannot modify super admin.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'business_branch' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (isset($validated['phone'])) $user->phone = $validated['phone'];
        if (isset($validated['business_branch'])) $user->business_branch = $validated['business_branch'];
        if (isset($validated['is_active'])) $user->is_active = $validated['is_active'];
        
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        $user->roles()->sync([$validated['role_id']]);
        if (isset($validated['permissions'])) {
            $user->directPermissions()->sync($validated['permissions']);
        }

        return response()->json($user->load(['roles', 'directPermissions']));
    }

    public function destroy(User $user, Request $request)
    {
        if ($user->id === 1 || $user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete super admin or yourself.'], 403);
        }
        
        $user->delete();
        return response()->json(null, 204);
    }

    public function roles()
    {
        return response()->json(Role::all());
    }

    public function permissions()
    {
        return response()->json(\App\Models\Permission::all());
    }
}
