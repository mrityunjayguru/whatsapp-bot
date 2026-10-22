<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_code' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = $this->input('email');
        $companyCode = trim((string) $this->input('company_code'));

        if (!empty($companyCode)) {
            $company = \App\Models\Company::where('company_code', strtoupper($companyCode))->first();

            if (!$company) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'company_code' => 'Invalid Company ID. No company found with this code.',
                ]);
            }

            if (!$company->is_active) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'company_code' => 'Company account is inactive. Please contact support.',
                ]);
            }

            $credentials = [
                'email' => $email,
                'password' => $this->input('password'),
                'company_id' => $company->id,
            ];
        } else {
            // No company code entered: check if this is a Super Admin (company_id is null)
            $superAdminUser = \App\Models\User::where('email', $email)
                ->whereNull('company_id')
                ->first();

            if (!$superAdminUser) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'company_code' => 'Company ID is required for company accounts and employees.',
                ]);
            }

            $credentials = [
                'email' => $email,
                'password' => $this->input('password'),
                'company_id' => null,
            ];
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('company_code')) . '|' . Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
