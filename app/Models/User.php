<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if user is an employee.
     */
    public function isEmployee(): bool
    {
        return Employee::where('email', $this->email)->exists();
    }

    /**
     * Determine if the user has verified their email address.
     * Employees do not need email verification.
     */
    public function hasVerifiedEmail(): bool
    {
        if ($this->isEmployee()) {
            return true;
        }

        return !is_null($this->email_verified_at);
    }

    /**
     * Send email verification notification only if not an employee.
     */
    public function sendEmailVerificationNotification(): void
    {
        if ($this->isEmployee()) {
            return;
        }

        $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail);
    }
}
