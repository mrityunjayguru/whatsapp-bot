<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappNumber extends Model
{
    protected $fillable = [
        'company_id',
        'phone_number_id',
        'waba_id',
        'display_number',
        'label',
        'access_token',
        'graph_version',
        'is_active',
        'valid_from',
        'expiry_date',
    ];

    protected $casts = [
        // Encrypted at rest - this is a real secret (it can send WhatsApp
        // messages as this client's number), unlike a widget's token.
        // Laravel decrypts it transparently on read/write through the
        // model; it's unreadable directly in the database.
        'access_token' => 'encrypted',
        'is_active' => 'boolean',
        'valid_from' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Same semantics as Widget's availability check (see
     * WidgetMessageController::checkCompany() and widget_config.py's
     * is_currently_active()) - switched on AND today falls inside
     * [valid_from, expiry_date]. MetaWebhookController checks this
     * before ever forwarding a message to this number's bot.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $today = now()->toDateString();
        if ($this->valid_from && $today < $this->valid_from->toDateString()) {
            return false;
        }
        if ($this->expiry_date && $today > $this->expiry_date->toDateString()) {
            return false;
        }
        return true;
    }
}
