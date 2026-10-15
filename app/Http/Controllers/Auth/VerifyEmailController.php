<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectDestination($request->user());
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->redirectDestination($request->user());
    }

    protected function redirectDestination($user): RedirectResponse
    {
        if (!is_null($user->company_id)) {
            $company = \App\Models\Company::find($user->company_id);
            if ($company && $company->bot_usage_type === 'whatsapp') {
                $whatsappNumber = \App\Models\WhatsappNumber::where('company_id', $user->company_id)->latest('id')->first();
                if ($whatsappNumber) {
                    return redirect()->route('whatsapp-numbers.edit', ['phoneNumberId' => $whatsappNumber->phone_number_id]);
                }
                return redirect()->route('whatsapp-numbers.index');
            }

            $widget = \App\Models\Widget::where('company_id', $user->company_id)->latest('id')->first();
            if ($widget) {
                return redirect()->route('widgets.edit', ['token' => $widget->token]);
            }
        } elseif (auth()->id() === 1 || is_null($user->company_id)) {
            return redirect()->route('companies.index');
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
