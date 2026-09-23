<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $query = Employee::where('tenant_id', $companyId)->with('creator')->latest();

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $employees = $query->paginate(15)->withQueryString();

        return view('employees.index', compact('employees', 'request'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'mobile_number' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'role' => 'required|in:ADMIN,MANAGER,EMPLOYEE',
            'status' => 'required|in:ACTIVE,INACTIVE,INVITED,BLOCKED',
            'password' => 'required|string|min:8',
        ]);

        $lastEmployee = Employee::orderBy('id', 'desc')->first();
        $nextId = $lastEmployee ? $lastEmployee->id + 1 : 1;
        $employeeCode = 'EMP-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        // Create User for login
        \App\Models\User::create([
            'name' => trim($request->first_name . ' ' . $request->last_name),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $companyId,
        ]);

        Employee::create([
            'tenant_id' => $companyId,
            'employee_code' => $employeeCode,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'display_name' => trim($request->first_name . ' ' . $request->last_name),
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
            'password_hash' => Hash::make($request->password),
            'designation' => $request->designation,
            'department' => $request->department,
            'role' => $request->role,
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $companyId = auth()->user()->company_id;
        $employee = Employee::where('tenant_id', $companyId)->findOrFail($id);
        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id;
        $employee = Employee::where('tenant_id', $companyId)->findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'mobile_number' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'role' => 'required|in:ADMIN,MANAGER,EMPLOYEE',
            'status' => 'required|in:ACTIVE,INACTIVE,INVITED,BLOCKED',
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'display_name' => trim($request->first_name . ' ' . $request->last_name),
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
            'designation' => $request->designation,
            'department' => $request->department,
            'role' => $request->role,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->password);
        }

        // Update corresponding User record
        $user = \App\Models\User::where('email', $employee->email)->first();
        if ($user) {
            $user->name = trim($request->first_name . ' ' . $request->last_name);
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
        } else {
            // Create user if it doesn't exist (for old employees)
            \App\Models\User::create([
                'name' => trim($request->first_name . ' ' . $request->last_name),
                'email' => $request->email,
                'password' => $request->filled('password') ? Hash::make($request->password) : Hash::make('password123'),
                'company_id' => $companyId,
            ]);
        }

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $companyId = auth()->user()->company_id;
        $employee = Employee::where('tenant_id', $companyId)->findOrFail($id);
        
        // Delete corresponding User record
        $user = \App\Models\User::where('email', $employee->email)->first();
        if ($user) {
            $user->delete();
        }

        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
