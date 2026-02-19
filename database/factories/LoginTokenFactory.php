<?php

namespace Database\Factories;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoginToken>
 */
class LoginTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => Str::random(64),
            'device_name' => fake()->userAgent(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'is_confirmed' => false,
            'confirmed_at' => null,
            'expires_at' => now()->addHours(24),
        ];
    }

    /**
     * Создать подтверждённый токен
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confirmed' => true,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Создать истекший токен
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(1),
            'is_confirmed' => false,
        ]);
    }

    /**
     * Создать подтверждённый и истекший токен
     */
    public function expiredAndConfirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confirmed' => true,
            'confirmed_at' => now()->subDays(2),
            'expires_at' => now()->subHours(1),
        ]);
    }
}

