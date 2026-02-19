<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\ChatUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatUser>
 */
class ChatUserFactory extends Factory
{
    protected $model = ChatUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => Chat::factory(),
            'user_id' => User::factory(),
            'is_muted' => fake()->boolean(20),
            'joined_at' => fake()->dateTimeBetween('-6 months'),
            'is_active' => fake()->boolean(80),
        ];
    }

    /**
     * Создать заглушённого участника
     */
    public function muted(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_muted' => true,
        ]);
    }

    /**
     * Создать неактивного участника
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Создать недавно присоединившегося участника
     */
    public function recentlyJoined(): static
    {
        return $this->state(fn(array $attributes) => [
            'joined_at' => now()->subDays(fake()->numberBetween(1, 7)),
        ]);
    }
}
