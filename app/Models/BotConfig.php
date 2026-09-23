<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotConfig extends Model
{
    protected $fillable = ['payload', 'tenant_id'];

    protected $casts = [
        'payload' => 'array',
    ];
}
