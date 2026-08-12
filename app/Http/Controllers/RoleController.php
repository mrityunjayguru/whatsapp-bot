<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    // Only roles created by this company user
    private function ownRoles()
    {
        return Role::where('created_by', auth()->id())
            ->whereNotIn('name', Role::SYSTEM_ROLES);
    }

    public function index(): View
    {
        $roles = $this->ownRoles()->withCount('permissions')->latest()->paginate(10);
        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::orderBy('module')->orderBy('action')->get()->groupBy('module');
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'display_name'  => ['required', 'string', 'max:255'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'display_name.required' => 'Role name is required.',
        ]);

        $name = strtolower(str_replace(' ', '-', trim($request->display_name)));

        $exists = Role::where('name', $name)
            ->where('created_by', auth()->id())
            ->exists();

        if ($exists) {
            return back()->withErrors(['display_name' => 'A role with this name already exists for your account.'])->withInput();
        }

        $role = Role::create([
            'name'         => $name,
            'display_name' => $request->display_name,
            'created_by'   => auth()->id(),
        ]);

        // Assign permissions on create
        if ($request->filled('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        abort_if($role->created_by !== auth()->id(), 403);

        $permissions = Permission::orderBy('module')->orderBy('action')->get()->groupBy('module');
        $assigned    = $role->permissions->pluck('id')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'assigned'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->created_by !== auth()->id(), 403);

        $request->validate([
            'display_name'  => ['required', 'string', 'max:255'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $name = strtolower(str_replace(' ', '-', trim($request->display_name)));

        $role->update([
            'name'         => $name,
            'display_name' => $request->display_name,
        ]);

        // Sync permissions
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->created_by !== auth()->id(), 403);

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
