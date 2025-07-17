<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialShare extends Model
{
    protected $fillable = [
        'credential_id',
        'shared_by_user_id',
        'shared_with_user_id',
        'shared_with_group_id',
        'permission',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function sharedWithGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'shared_with_group_id');
    }
}
