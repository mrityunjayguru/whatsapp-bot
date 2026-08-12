<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'status',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => 'integer',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * Returns the company owner's ID for this user.
     * - If user is company (role_id=2): returns own id
     * - If user is sub-user: returns created_by (company owner id)
     */
    public function companyOwnerId(): int
    {
        return $this->role_id === 2 ? $this->id : (int) $this->created_by;
    }

    /**
     * Returns all user IDs belonging to the same company:
     * company owner + all their sub-users.
     */
    public function companyUserIds(): array
    {
        $ownerId = $this->companyOwnerId();

        $subIds = \App\Models\User::where('created_by', $ownerId)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge([$ownerId], $subIds));
    }
    public function hasPermission(string $permission): bool
    {
        if (in_array($this->role_id, [1, 2])) {
            return true;
        }

        return $this->role && $this->role->hasPermission($permission);
    }
}
