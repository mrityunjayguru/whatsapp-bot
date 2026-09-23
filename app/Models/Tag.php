<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $guarded = [];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_tags', 'tag_id', 'contact_id', 'tag_id', 'id')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
