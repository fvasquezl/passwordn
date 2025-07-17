<?php

use App\Models\Group;
use App\Models\CredentialShare;

if (!function_exists('shareCredentialWithGroup')) {
    function shareCredentialWithGroup($credentialId, $groupId, $ownerUserId, $permission = 'read')
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
