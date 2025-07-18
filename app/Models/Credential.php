<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = ['username', 'password', 'group_id', 'user_id'];

    /**
     * Scope a query to only include credentials shared with a given user (directly or via group).
     */
    public function scopeSharedWithUser($query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->whereHas('shares', function ($q) use ($userId) {
                $q->where(function ($subQ) use ($userId) {
                    $subQ->where('shared_with_type', \App\Models\User::class)
                        ->where('shared_with_id', $userId);
                })
                    ->orWhere(function ($subQ) use ($userId) {
                        $subQ->where('shared_with_type', \App\Models\Group::class)
                            ->whereHas('sharedWith.users', function ($gq) use ($userId) {
                                $gq->where('users.id', $userId);
                            });
                    });
            });
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sharedWithUsers(): HasMany
    {
        return $this->hasMany(CredentialShare::class)->with('sharedWith');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(CredentialShare::class);
    }

    public function canBeShared(): bool
    {
        return $this->category?->name !== 'Personal';
    }

}
