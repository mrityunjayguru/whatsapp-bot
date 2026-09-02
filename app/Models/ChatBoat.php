<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatBoat extends Model
{
    protected $fillable = [
        'phonenumber',
        'requestpayload',
        'responsepayload',
        'payload',
    ];
}
