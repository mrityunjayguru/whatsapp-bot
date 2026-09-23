<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Company::latest();

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'ACTIVE');
        }

        $companies = $query->paginate(15)->withQueryString();

        return view('companies.index', compact('companies', 'request'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,contact_email|unique:users,email',
            'contact_number' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $company = Company::create([
            'name' => $request->name,
            'contact_email' => $request->email,
            'contact_number' => $request->contact_number,
            'is_active' => $request->status === 'ACTIVE',
        ]);

        // Create Admin User for the company
        User::create([
            'name' => $request->name . ' Admin',
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $company->id,
        ]);

        return redirect()->route('companies.index')->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,contact_email,' . $company->id,
            'contact_number' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        // Validate user email unique if it's changing
        $user = User::where('company_id', $company->id)->first();
        if ($user && $user->email !== $request->email) {
            $request->validate([
                'email' => 'unique:users,email,' . $user->id,
            ]);
        }

        $company->update([
            'name' => $request->name,
            'contact_email' => $request->email,
            'contact_number' => $request->contact_number,
            'is_active' => $request->status === 'ACTIVE',
        ]);

        if ($user) {
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
        } else {
            // Create user if missing, for example when editing a seeded company
            User::create([
                'name' => $request->name . ' Admin',
                'email' => $request->email,
                'password' => Hash::make($request->password ?? 'password'),
                'company_id' => $company->id,
            ]);
        }

        return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        // Delete corresponding User record
        User::where('company_id', $company->id)->delete();
        $company->delete();
        
        return redirect()->route('companies.index')->with('success', 'Company deleted successfully.');
    }

    /**
     * Toggle the status of the company.
     */
    public function toggleStatus(Company $company)
    {
        $company->update(['is_active' => !$company->is_active]);
        return redirect()->back()->with('success', 'Company status updated successfully.');
    }
}
