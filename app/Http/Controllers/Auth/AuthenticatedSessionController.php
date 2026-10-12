<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Widget;
use App\Models\WhatsappNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if (is_null($user->company_id)) {
            return redirect()->intended(route('companies.index', absolute: false));
        }

        $employee = Employee::where('email', $user->email)->first();
        if ($employee && $employee->role !== 'ADMIN') {
            return redirect()->intended(route('conversations.index', absolute: false));
        }

        $company = Company::find($user->company_id);
        $botUsageType = $company?->bot_usage_type ?? 'widget';

        if ($botUsageType === 'whatsapp') {
            $whatsappNumber = WhatsappNumber::where('company_id', $user->company_id)->first();
            if ($whatsappNumber) {
                return redirect()->intended(route('whatsapp-numbers.edit', ['phoneNumberId' => $whatsappNumber->phone_number_id]));
            }
            return redirect()->intended(route('whatsapp-numbers.index', absolute: false));
        }

        $widget = Widget::where('company_id', $user->company_id)->first();
        if ($widget) {
            return redirect()->intended(route('widgets.edit', ['token' => $widget->token]));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
