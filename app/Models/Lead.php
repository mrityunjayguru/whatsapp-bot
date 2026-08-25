<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'meta_lead_id',
        'meta_form_id',
        'lead_name',
        'company_name',
        'phone_number',
        'whatsapp_number',
        'email',
        'lead_source_id',
        'interested_in_id',
        'requirement',
        'lead_status_id',
        'follow_up_date',
        'follow_up_time',
        'created_by',
        'updated_by',
        'last_activity_at',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date'   => 'date',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Generate next Lead ID: LD0001, LD0002 ...
     */
    public static function generateLeadId(): string
    {
        $last = self::orderByDesc('id')->value('lead_id');

        if (!$last) {
            return 'LD0001';
        }

        $number = (int) substr($last, 2);   // strip "LD"
        return 'LD' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class, 'lead_status_id');
    }

    public function interestedIn()
    {
        return $this->belongsTo(InterestedIn::class, 'interested_in_id');
    }

    public function notes()
    {
        return $this->hasMany(LeadNote::class, 'lead_id', 'id');
    }

    public function followUps()
    {
        return $this->hasMany(LeadFollowUp::class, 'lead_id', 'id');
    }

    public function latestFollowUp()
    {
        return $this->hasOne(LeadFollowUp::class, 'lead_id', 'id')
            ->whereNotNull('note')
            ->latestOfMany();
    }

    public function recentFollowUps()
    {
        return $this->hasMany(LeadFollowUp::class, 'lead_id', 'id')
            ->whereNotNull('note')
            ->latest();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
