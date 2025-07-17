<?php

namespace App\Services;

use App\Models\Group;
use App\Models\CredentialShare;

class CredentialShareService
{
    /**
     * Comparte una credencial con todos los usuarios de un grupo, evitando al dueño.
     */
    public function shareWithGroup(int $credentialId, int $groupId, int $ownerUserId, string $permission = 'read'): void
    {
        $group = Group::with('users')->findOrFail($groupId);
        foreach ($group->users as $user) {
            if ($user->id !== $ownerUserId) {
                CredentialShare::updateOrCreate(
                    [
                        'credential_id' => $credentialId,
                        'shared_with_user_id' => $user->id,
                    ],
                    [
                        'shared_by_user_id' => $ownerUserId,
                        'permission' => $permission,
                    ]
                );
            }
        }
    }
}
