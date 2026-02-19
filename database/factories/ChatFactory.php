<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chat>
 */
class ChatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['private', 'group', 'favorites']),
            'name' => fake()->word(),
            'creator_id' => User::factory(),
        ];
    }

    /**
     * Создать приватный чат
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Chat::TYPE_PRIVATE,
            'name' => null,
        ]);
    }

    /**
     * Создать групповой чат
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Chat::TYPE_GROUP,
            'name' => fake()->sentence(2),
        ]);
    }

    /**
     * Создать чат "Избранное"
     */
    public function favorites(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Chat::TYPE_FAVORITES,
            'name' => null,
        ]);
    }
}

