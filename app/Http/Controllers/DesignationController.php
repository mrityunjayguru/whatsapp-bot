<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    /**
     * Resolve the current tenant ID
     */
    protected function getTenantId()
    {
        return auth()->user()->company_id ?? auth()->user()->tenant_id;
    }

    /**
     * Display a listing of designations.
     */
    public function index(Request $request)
    {
        $tenantId = $this->getTenantId();

        $query = Designation::query()->with(['creator', 'department']);
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($deptId = $request->input('department_id')) {
            $query->where('department_id', $deptId);
        }

        if ($request->filled('status')) {
            $statusVal = $request->input('status') === 'active' ? 1 : ($request->input('status') === 'inactive' ? 0 : null);
            if (!is_null($statusVal)) {
                $query->where('status', $statusVal);
            }
        }

        $designations = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        foreach ($designations as $desig) {
            $empQuery = Employee::where('designation', $desig->name);
            if (!is_null($tenantId)) {
                $empQuery->where('tenant_id', $tenantId);
            }
            $desig->employees_count = $empQuery->count();
        }

        // Fetch active departments for dropdown
        $deptQuery = Department::query()->where('status', true);
        if (!is_null($tenantId)) {
            $deptQuery->where('tenant_id', $tenantId);
        }
        $departments = $deptQuery->orderBy('name')->get();

        return view('designations.index', compact('designations', 'departments', 'request'));
    }

    /**
     * Store a newly created designation.
     */
    public function store(Request $request)
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $name = trim($validated['name']);

        $existsQuery = Designation::where('name', $name);
        if (!is_null($tenantId)) {
            $existsQuery->where('tenant_id', $tenantId);
        }
        if ($existsQuery->exists()) {
            return redirect()->back()->withErrors(['name' => 'This designation already exists.'])->withInput();
        }

        Designation::create([
            'tenant_id' => $tenantId,
            'department_id' => $validated['department_id'] ?? null,
            'name' => $name,
            'description' => $validated['description'] ?? null,
            'status' => $request->has('status') ? (bool) $request->status : true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('designations.index')->with('success', 'Designation created successfully.');
    }

    /**
     * Update the specified designation.
     */
    public function update(Request $request, $id)
    {
        $tenantId = $this->getTenantId();

        $query = Designation::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $designation = $query->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $newName = trim($validated['name']);
        $oldName = $designation->name;

        $existsQuery = Designation::where('name', $newName)->where('id', '!=', $designation->id);
        if (!is_null($tenantId)) {
            $existsQuery->where('tenant_id', $tenantId);
        }
        if ($existsQuery->exists()) {
            return redirect()->back()->withErrors(['name' => 'Another designation with this name already exists.'])->withInput();
        }

        $designation->update([
            'name' => $newName,
            'department_id' => $validated['department_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        // If designation renamed, sync existing employee records
        if ($oldName !== $newName) {
            $empUpdate = Employee::where('designation', $oldName);
            if (!is_null($tenantId)) {
                $empUpdate->where('tenant_id', $tenantId);
            }
            $empUpdate->update(['designation' => $newName]);
        }

        return redirect()->route('designations.index')->with('success', 'Designation updated successfully.');
    }

    /**
     * Toggle designation active status.
     */
    public function toggleStatus($id)
    {
        $tenantId = $this->getTenantId();

        $query = Designation::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $designation = $query->findOrFail($id);

        $designation->status = ! $designation->status;
        $designation->save();

        $statusLabel = $designation->status ? 'activated' : 'deactivated';
        return redirect()->route('designations.index')->with('success', "Designation '{$designation->name}' has been {$statusLabel}.");
    }

    /**
     * Delete designation if no employee is currently assigned to it.
     */
    public function destroy($id)
    {
        $tenantId = $this->getTenantId();

        $query = Designation::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $designation = $query->findOrFail($id);

        $empCountQuery = Employee::where('designation', $designation->name);
        if (!is_null($tenantId)) {
            $empCountQuery->where('tenant_id', $tenantId);
        }
        $usedCount = $empCountQuery->count();

        if ($usedCount > 0) {
            return redirect()->route('designations.index')->with('error', "Cannot delete designation '{$designation->name}' because it is assigned to {$usedCount} employee(s). Please reassign them first.");
        }

        $designation->delete();

        return redirect()->route('designations.index')->with('success', 'Designation deleted successfully.');
    }
}
