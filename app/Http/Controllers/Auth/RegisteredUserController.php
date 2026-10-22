<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:companies,company_code'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'bot_usage_type' => ['nullable', 'in:widget,whatsapp'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $botUsageType = $request->input('bot_usage_type', 'widget');

        $companyData = [
            'name' => $request->name,
            'contact_email' => $request->email,
            'is_active' => true,
            'bot_usage_type' => $botUsageType,
        ];
        if ($request->filled('company_code')) {
            $companyData['company_code'] = strtoupper(trim($request->company_code));
        }

        $company = \App\Models\Company::create($companyData);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $company->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
