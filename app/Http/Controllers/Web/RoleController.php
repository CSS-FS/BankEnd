<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $role = Role::create($validated);
        return redirect()
            ->route('roles.index')
            ->with('success', 'Role is created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $validated['name'] = strtolower($validated['name']);
        $role->update($validated);
        return redirect()
            ->route('roles.index')
            ->with('success', 'Role is updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Role "' . ucfirst($role->name) . '" cannot be deleted because it has ' . $role->users()->count() . ' user(s) attached to it. Please reassign those users before deleting this role.');
        }

        $roleName = ucfirst($role->name);
        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role "' . $roleName . '" has been deleted successfully.');
    }

    public function getPermissions(Role $role)
    {
        $permissions = $role->permissions;
        return view(
            'admin.roles.permissions',
            compact('role', 'permissions')
        );
    }

    public function setPermissions(Request $request, Role $role)
    {

    }

    public function attachedUsers($roleId)
    {
        $role = Role::with('users')->findOrFail($roleId);
        $users = $role->users;

        // Render a blade partial as HTML for the modal-body
        $view = view('admin.roles.users_list', compact('users'))->render();

        return response()->json([
            'role_name' => ucfirst($role->name),
            'html' => $view,
        ]);
    }
}
