<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name'])
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function show(Role $role)
    {
        return response()->json($role->load('permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        } else {
            $role->permissions()->sync([]);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        // Prevent deleting Super Admin
        if ($role->id === 1 || $role->slug === 'super-admin') {
            return response()->json(['message' => 'Cannot delete Super Admin role.'], 403);
        }

        // Prevent deleting if assigned to users
        if ($role->users()->exists()) {
            return response()->json(['message' => 'Cannot delete role because it is assigned to users.'], 403);
        }

        $role->delete();
        return response()->json(null, 204);
    }
}
