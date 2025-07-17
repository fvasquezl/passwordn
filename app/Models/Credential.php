<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = ['username', 'password', 'group_id', 'user_id'];

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
