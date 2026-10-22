<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Resolve the current tenant ID
     */
    protected function getTenantId()
    {
        return auth()->user()->company_id ?? auth()->user()->tenant_id;
    }

    /**
     * Display a listing of roles with permission matrix counts.
     */
    public function index(Request $request)
    {
        $tenantId = $this->getTenantId();

        // Make sure permissions and default roles exist for this tenant
        RolePermissionService::ensureTenantDefaultRoles($tenantId);

        $query = Role::query()->with(['permissions', 'creator', 'employees']);
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $roles = $query->orderBy('is_system', 'desc')->orderBy('name', 'asc')->paginate(15)->withQueryString();
        $totalPermissionsCount = Permission::count();
        $permissionCatalog = RolePermissionService::getPermissionCatalog();

        return view('roles.index', compact('roles', 'totalPermissionsCount', 'permissionCatalog', 'request'));
    }

    /**
     * Show form for creating a new role.
     */
    public function create()
    {
        RolePermissionService::seedPermissions();
        $catalog = RolePermissionService::getPermissionCatalog();
        $allPermissions = Permission::all()->groupBy('module');

        return view('roles.create', compact('catalog', 'allPermissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $name = trim($validated['name']);
        $slug = Str::slug($name);

        $exists = Role::where('tenant_id', $tenantId)->where('slug', $slug)->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['name' => 'A role with this name already exists.'])->withInput();
        }

        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'created_by' => auth()->id(),
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Show form for editing a role and its permission matrix.
     */
    public function edit($id)
    {
        $tenantId = $this->getTenantId();
        RolePermissionService::seedPermissions();

        $query = Role::query()->with('permissions');
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $role = $query->findOrFail($id);

        $catalog = RolePermissionService::getPermissionCatalog();
        $allPermissions = Permission::all()->groupBy('module');
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();

        return view('roles.edit', compact('role', 'catalog', 'allPermissions', 'rolePermissionIds'));
    }

    /**
     * Update the specified role and sync permissions.
     */
    public function update(Request $request, $id)
    {
        $tenantId = $this->getTenantId();

        $query = Role::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $role = $query->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $name = trim($validated['name']);
        $slug = Str::slug($name);

        $exists = Role::where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->where('id', '!=', $role->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['name' => 'Another role with this name already exists.'])->withInput();
        }

        $role->update([
            'name' => $name,
            'slug' => $role->is_system ? $role->slug : $slug, // preserve system slugs
            'description' => $validated['description'] ?? null,
        ]);

        // Sync permissions
        $permissions = $validated['permissions'] ?? [];
        $role->permissions()->sync($permissions);

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' updated successfully.");
    }

    /**
     * Remove the specified custom role.
     */
    public function destroy($id)
    {
        $tenantId = $this->getTenantId();

        $query = Role::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $role = $query->findOrFail($id);

        if ($role->is_system) {
            return redirect()->route('roles.index')->with('error', "Default system roles ({$role->name}) cannot be deleted.");
        }

        $assignedCount = $role->employees()->count();
        if ($assignedCount > 0) {
            return redirect()->route('roles.index')->with('error', "Cannot delete role '{$role->name}' because it is assigned to {$assignedCount} employee(s). Please reassign them first.");
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' deleted successfully.");
    }
}
