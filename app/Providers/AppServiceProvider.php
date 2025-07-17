<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Puedes mover esta función a un Service, pero aquí tienes el ejemplo:
        \Illuminate\Support\Facades\Blade::directive('shareCredentialWithGroup', function ($expression) {
            // Solo para referencia, no se usa como Blade directive real
        });

        if (!function_exists('shareCredentialWithGroup')) {
            function shareCredentialWithGroup($credentialId, $groupId, $ownerUserId, $permission = 'read')
            {
                $group = \App\Models\Group::with('users')->findOrFail($groupId);
                foreach ($group->users as $user) {
                    if ($user->id !== $ownerUserId) {
                        \App\Models\CredentialShare::updateOrCreate(
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
    }
}
