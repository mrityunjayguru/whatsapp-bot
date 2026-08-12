<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadNote extends Model
{
    protected $fillable = ['lead_id', 'note', 'added_by'];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
