<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Resolve the current tenant ID
     */
    protected function getTenantId()
    {
        return auth()->user()->company_id ?? auth()->user()->tenant_id;
    }

    /**
     * Display a listing of departments.
     */
    public function index(Request $request)
    {
        $tenantId = $this->getTenantId();

        $query = Department::query()->with('creator');
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $statusVal = $request->input('status') === 'active' ? 1 : ($request->input('status') === 'inactive' ? 0 : null);
            if (!is_null($statusVal)) {
                $query->where('status', $statusVal);
            }
        }

        $departments = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        foreach ($departments as $dept) {
            $empQuery = Employee::where('department', $dept->name);
            if (!is_null($tenantId)) {
                $empQuery->where('tenant_id', $tenantId);
            }
            $dept->employees_count = $empQuery->count();
        }

        return view('departments.index', compact('departments', 'request'));
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request)
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $name = trim($validated['name']);

        $existsQuery = Department::where('name', $name);
        if (!is_null($tenantId)) {
            $existsQuery->where('tenant_id', $tenantId);
        }
        if ($existsQuery->exists()) {
            return redirect()->back()->withErrors(['name' => 'This department already exists.'])->withInput();
        }

        Department::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'description' => $validated['description'] ?? null,
            'status' => $request->has('status') ? (bool) $request->status : true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, $id)
    {
        $tenantId = $this->getTenantId();

        $query = Department::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $department = $query->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $newName = trim($validated['name']);
        $oldName = $department->name;

        $existsQuery = Department::where('name', $newName)->where('id', '!=', $department->id);
        if (!is_null($tenantId)) {
            $existsQuery->where('tenant_id', $tenantId);
        }
        if ($existsQuery->exists()) {
            return redirect()->back()->withErrors(['name' => 'Another department with this name already exists.'])->withInput();
        }

        $department->update([
            'name' => $newName,
            'description' => $validated['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        // If department name changed, sync employee records assigned to old department name
        if ($oldName !== $newName) {
            $empUpdate = Employee::where('department', $oldName);
            if (!is_null($tenantId)) {
                $empUpdate->where('tenant_id', $tenantId);
            }
            $empUpdate->update(['department' => $newName]);
        }

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    /**
     * Toggle department active status.
     */
    public function toggleStatus($id)
    {
        $tenantId = $this->getTenantId();

        $query = Department::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $department = $query->findOrFail($id);

        $department->status = ! $department->status;
        $department->save();

        $statusLabel = $department->status ? 'activated' : 'deactivated';
        return redirect()->route('departments.index')->with('success', "Department '{$department->name}' has been {$statusLabel}.");
    }

    /**
     * Delete department if no employee is currently assigned to it.
     */
    public function destroy($id)
    {
        $tenantId = $this->getTenantId();

        $query = Department::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $department = $query->findOrFail($id);

        $empCountQuery = Employee::where('department', $department->name);
        if (!is_null($tenantId)) {
            $empCountQuery->where('tenant_id', $tenantId);
        }
        $usedCount = $empCountQuery->count();

        if ($usedCount > 0) {
            return redirect()->route('departments.index')->with('error', "Cannot delete department '{$department->name}' because it is assigned to {$usedCount} employee(s). Please reassign them first.");
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
    }
}
