<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies.
     */
    public function index(): View
    {
        $companies = Company::latest()->paginate(10);
        return view('company.index', compact('companies'));
    }

    /**
     * Show the form for creating a new company.
     */
    public function create(): View
    {
        return view('company.create');
    }

    /**
     * Store a newly created company and create a linked user account.
     */
    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            // Create the user account with role_id = 2 (company)
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => $request->password,   // hashed by User model cast
                'role_id'  => 2,                     // company role
            ]);

            // Create the company and link to the user
            Company::create([
                'name'           => $request->name,
                'email'          => $request->email,
                'contact_number' => $request->contact_number,
                'password'       => $request->password,   // stored plain for reference
                'user_id'        => $user->id,
                'status'         => $request->status,
            ]);
        });

        return redirect()->route('company.index')
            ->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(Company $company): View
    {
        return view('company.edit', compact('company'));
    }

    /**
     * Update the specified company and its linked user account.
     */
    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        DB::transaction(function () use ($request, $company) {
            // Build user update payload
            $userPayload = [
                'name'  => $request->name,
                'email' => $request->email,
            ];
            if ($request->filled('password')) {
                $userPayload['password'] = $request->password;
            }

            // Update linked user if it exists
            if ($company->user_id && $user = User::find($company->user_id)) {
                $user->update($userPayload);
            }

            // Build company update payload
            $companyPayload = [
                'name'           => $request->name,
                'email'          => $request->email,
                'contact_number' => $request->contact_number,
                'status'         => $request->status,
            ];
            if ($request->filled('password')) {
                $companyPayload['password'] = $request->password;
            }

            $company->update($companyPayload);
        });

        return redirect()->route('company.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified company and its linked user account.
     */
    public function destroy(Company $company): RedirectResponse
    {
        DB::transaction(function () use ($company) {
            $userId = $company->user_id;

            // Unlink first to avoid FK constraint during user delete
            $company->update(['user_id' => null]);
            $company->delete();

            if ($userId) {
                User::where('id', $userId)->delete();
            }
        });

        return redirect()->route('company.index')
            ->with('success', 'Company deleted successfully.');
    }
}
