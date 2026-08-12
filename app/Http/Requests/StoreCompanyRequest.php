<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:companies,email', 'unique:users,email'],
            'contact_number' => ['required', 'string', 'max:20'],
            'password'       => ['required', 'string', 'min:8', 'confirmed'],
            'status'         => ['required', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'Company name is required.',
            'email.required'             => 'Email address is required.',
            'email.unique'               => 'This email is already registered.',
            'contact_number.required'    => 'Contact number is required.',
            'password.required'          => 'Password is required.',
            'password.min'               => 'Password must be at least 8 characters.',
            'password.confirmed'         => 'Password confirmation does not match.',
            'status.required'            => 'Status is required.',
        ];
    }
}
