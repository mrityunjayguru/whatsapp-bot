<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaqCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function faqs()
    {
        return $this->hasMany(Faq::class, 'category', 'name')
            ->where('tenant_id', $this->tenant_id);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
