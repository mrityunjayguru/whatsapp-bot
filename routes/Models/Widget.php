<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $fillable = [
        'company_id',
        'token',
        'is_active',
        'valid_from',
        'expiry_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
