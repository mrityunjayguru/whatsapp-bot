<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'faq_hash_id',
        'python_source_ids',
        'question',
        'category',
        'keywords',
        'match_type',
        'priority',
        'answer',
        'attachment',
        'url',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'python_source_ids' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->faq_hash_id)) {
                $model->faq_hash_id = '#' . substr(md5(uniqid(rand(), true)), 0, 8);
            }
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }
}
