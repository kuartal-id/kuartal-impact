<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'kuartal_id' => (string) Str::uuid(),
            'entitlements' => [],
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['entitlements' => ['kuartalimpact.admin']]);
    }

    public function investor(): static
    {
        return $this->state(fn () => ['entitlements' => ['investor.portal']]);
    }
}
