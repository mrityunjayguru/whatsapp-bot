<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contact) {
            if (empty($contact->whatsapp_phone_number_id)) {
                $maxId = (int) static::max('whatsapp_phone_number_id');
                $contact->whatsapp_phone_number_id = $maxId >= 701 ? $maxId + 1 : 701;
            }
        });
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'contact_tags', 'contact_id', 'tag_id', 'id', 'tag_id')->withTimestamps();
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'contact_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'contact_id');
    }

    public function chatBoats()
    {
        return $this->hasMany(ChatBoat::class, 'phonenumber', 'phone_number');
    }
}