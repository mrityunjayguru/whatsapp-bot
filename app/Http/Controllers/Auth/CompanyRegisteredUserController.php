<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Services\WidgetApiService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyRegisteredUserController extends Controller
{
    /**
     * Display the company registration view.
     */
    public function create(): View
    {
        return view('auth.register-company');
    }

    /**
     * Handle an incoming company registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, WidgetApiService $api): RedirectResponse
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $widget = $api->createWidget($request->company_name, $request->email);

        if (!$widget) {
            return back()->withInput()->with('error', 'Could not create the widget - check the bot service is running.');
        }

        $company = Company::create([
            'name' => $request->company_name,
            'contact_email' => $request->email,
            'contact_number' => $request->contact_number,
            'widget_token' => $widget['token'],
            'is_active' => true,
            'bot_usage_type' => 'widget',
        ]);

        // Widget row is the real source of truth for company ownership
        // now (see the 2026_09_29_000001 migration) - WidgetMessageController
        // resolves incoming messages through this table, so without it this
        // brand-new widget would be rejected as "Unknown or inactive widget."
        // the moment the first visitor tried to chat.
        \App\Models\Widget::create([
            'company_id' => $company->id,
            'token' => $widget['token'],
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => $request->company_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $company->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
