<?php

namespace App\Services;

use App\Models\Group;
use App\Models\CredentialShare;

class CredentialShareService
{
    /**
     * Comparte una credencial con un usuario específico.
     */
    public function shareWithUser(int $credentialId, int $userId, int $ownerUserId, string $permission = 'read'): void
    {
        CredentialShare::updateOrCreate(
            [
                'credential_id' => $credentialId,
                'shared_with_type' => \App\Models\User::class,
                'shared_with_id' => $userId,
            ],
            [
                'shared_by_user_id' => $ownerUserId,
                'permission' => $permission,
            ]
        );
    }

    /**
     * Comparte una credencial con un grupo.
     */
    public function shareWithGroup(int $credentialId, int $groupId, int $ownerUserId, string $permission = 'read'): void
    {
        CredentialShare::updateOrCreate(
            [
                'credential_id' => $credentialId,
                'shared_with_type' => Group::class,
                'shared_with_id' => $groupId,
            ],
            [
                'shared_by_user_id' => $ownerUserId,
                'permission' => $permission,
            ]
        );
    }
}
