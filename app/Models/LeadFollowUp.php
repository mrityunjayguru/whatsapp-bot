<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadFollowUp extends Model
{
    protected $table = 'lead_follow_ups';

    protected $fillable = ['lead_id', 'follow_up_date', 'follow_up_time', 'note', 'added_by'];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
        ];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
