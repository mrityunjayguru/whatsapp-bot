<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'company_code',
        'name',
        'contact_email',
        'contact_number',
        'bot_usage_type',
        'widget_token',
        'is_active',
        'valid_from',
        'expiry_date',
    ];

    protected static function booted()
    {
        static::creating(function ($company) {
            if (empty($company->company_code)) {
                $company->company_code = self::generateUniqueCompanyCode();
            }
        });
    }

    public static function generateUniqueCompanyCode(): string
    {
        do {
            $code = 'DES' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('company_code', $code)->exists());

        return $code;
    }

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function conversations()
    {
        // Conversation's tenant_id column IS the company id - no schema
        // duplication, just a meaningful relationship name on top of it.
        return $this->hasMany(Conversation::class, 'tenant_id');
    }

    public function widgets()
    {
        // A company can now own any number of widgets (see the
        // 2026_09_29_000001 migration) - this, not the legacy
        // widget_token column below, is the source of truth for that.
        return $this->hasMany(Widget::class);
    }
}
