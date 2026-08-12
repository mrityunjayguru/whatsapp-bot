<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterestedIn extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'status', 'created_by'];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
