<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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

        $employee = \App\Models\Employee::where('email', $user->email)->first();
        if ($employee && $employee->role !== 'ADMIN') {
            return redirect()->intended(route('conversations.index', absolute: false));
        }

        $widget = \App\Models\Widget::where('company_id', $user->company_id)->first();
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
