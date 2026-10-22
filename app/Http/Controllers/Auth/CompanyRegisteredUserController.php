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
            'company_code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:companies,company_code'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'bot_usage_type' => ['required', 'in:widget,whatsapp'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $botUsageType = $request->input('bot_usage_type', 'widget');
        $widgetToken = null;

        if ($botUsageType === 'widget') {
            $widget = $api->createWidget($request->company_name);

            if (!$widget) {
                return back()->withInput()->with('error', 'Could not create the widget - check the bot service is running.');
            }
            $widgetToken = $widget['token'];
        }

        $company = Company::create([
            'company_code' => strtoupper(trim($request->company_code)),
            'name' => $request->company_name,
            'contact_email' => $request->email,
            'contact_number' => $request->contact_number,
            'widget_token' => $widgetToken,
            'is_active' => true,
            'bot_usage_type' => $botUsageType,
        ]);

        if ($botUsageType === 'widget' && $widgetToken) {
            \App\Models\Widget::create([
                'company_id' => $company->id,
                'token' => $widgetToken,
                'is_active' => true,
                'valid_from' => now()->format('Y-m-d'),
                'expiry_date' => now()->addMonths(2)->format('Y-m-d'),
            ]);
        }

        $user = User::create([
            'name' => $request->company_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $company->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
