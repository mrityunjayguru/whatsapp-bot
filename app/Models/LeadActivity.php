<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    protected $fillable = ['lead_id', 'type', 'description', 'performed_by'];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Type labels for display
    public static function typeLabel(string $type): string
    {
        return match($type) {
            'lead_created'    => 'Lead Created',
            'follow_up_added' => 'Follow-up Added',
            'status_changed'  => 'Status Changed',
            'lead_closed'     => 'Lead Closed',
            default           => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    // Badge colors
    public static function typeBadge(string $type): string
    {
        return match($type) {
            'lead_created'    => 'bg-primary',
            'follow_up_added' => 'bg-info',
            'status_changed'  => 'bg-warning text-dark',
            'lead_closed'     => 'bg-secondary',
            default           => 'bg-secondary',
        };
    }
}
