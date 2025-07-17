<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Credential;
use App\Models\CredentialShare;

class CredentialShareControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_shares_credential_with_group_excluding_owner()
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create();
        $users = User::factory()->count(3)->create();
        $group->users()->attach($users->pluck('id')->toArray());
        $group->users()->attach($owner->id); // El dueño también está en el grupo

        $credential = Credential::factory()->create(['user_id' => $owner->id]);

        $this->withoutMiddleware()
            ->actingAs($owner)
            ->postJson('/credential-share/group', [
                'credential_id' => $credential->id,
                'group_id' => $group->id,
                'owner_user_id' => $owner->id,
                'permission' => 'read',
            ])
            ->assertJson(['success' => true]);

        // Verifica que todos los usuarios del grupo (excepto el dueño) recibieron la credencial
        foreach ($users as $user) {
            $this->assertDatabaseHas('credential_shares', [
                'credential_id' => $credential->id,
                'shared_with_user_id' => $user->id,
                'shared_by_user_id' => $owner->id,
                'permission' => 'read',
            ]);
        }
        // Verifica que el dueño NO recibió la credencial compartida consigo mismo
        $this->assertDatabaseMissing('credential_shares', [
            'credential_id' => $credential->id,
            'shared_with_user_id' => $owner->id,
        ]);
    }
}
