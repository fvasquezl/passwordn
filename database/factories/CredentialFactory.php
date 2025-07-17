<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Credential;
use App\Models\User;
use App\Models\Group;

/**
 * @extends Factory<\App\Models\Credential>
 */
class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'user_id' => User::factory(),
            'group_id' => null,
        ];
    }
}
