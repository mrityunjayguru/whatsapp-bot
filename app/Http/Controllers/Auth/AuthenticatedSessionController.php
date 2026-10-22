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

        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (is_null($user->company_id)) {
            return redirect()->intended(route('companies.index', absolute: false));
        }

        $employee = Employee::where('tenant_id', $user->company_id)
            ->where('email', $user->email)
            ->first();

        if (!$employee) {
            $employee = Employee::where('email', $user->email)->first();
        }

        if ($employee) {
            $employee->update([
                'last_login_at' => now(),
                'is_online' => true,
            ]);
            if ($employee->role !== 'ADMIN') {
                return redirect()->intended(route('conversations.index', absolute: false));
            }
        }

        // Pull intended URL: if it's absent or just / or /dashboard, ignore it
        // so company users land straight on their bot management page.
        $intended = $request->session()->pull('url.intended');
        if ($intended) {
            $path = parse_url($intended, PHP_URL_PATH) ?? '';
            $isGeneric = in_array(rtrim($path, '/'), ['', '/dashboard', '/login'], true);
            if (!$isGeneric) {
                return redirect()->to($intended);
            }
        }

        $company = Company::find($user->company_id);
        $botUsageType = $company?->bot_usage_type ?? 'widget';

        if($botUsageType === 'whatsapp') {
            $whatsappNumber = WhatsappNumber::where('company_id', $user->company_id)->latest('id')->first();
            if ($whatsappNumber) {
                return redirect()->route('whatsapp-numbers.edit', ['phoneNumberId' => $whatsappNumber->phone_number_id]);
            }
            return redirect()->route('whatsapp-numbers.index');
        }

        $widget = Widget::where('company_id', $user->company_id)->latest('id')->first();
        if ($widget) {
            return redirect()->route('widgets.edit', ['token' => $widget->token]);
        }

        return redirect()->route('dashboard');
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
