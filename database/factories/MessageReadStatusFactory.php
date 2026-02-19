<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\MessageReadStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MessageReadStatus>
 */
class MessageReadStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $deliveredAt = fake()->dateTimeBetween('-30 days');

        return [
            'message_id' => Message::factory(),
            'user_id' => User::factory(),
            'delivered_at' => $deliveredAt,
            'read_at' => fake()->boolean(70) ? $deliveredAt->modify('+' . fake()->numberBetween(1, 3600) . ' seconds') : null,
        ];
    }

    /**
     * Создать прочитанное сообщение
     */
    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'delivered_at' => $attributes['delivered_at'],
            'read_at' => $attributes['delivered_at']->modify('+' . fake()->numberBetween(1, 3600) . ' seconds'),
        ]);
    }

    /**
     * Создать только доставленное, но не прочитанное сообщение
     */
    public function delivered(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Создать непрочитанное и не доставленное сообщение
     */
    public function undelivered(): static
    {
        return $this->state(fn(array $attributes) => [
            'delivered_at' => null,
            'read_at' => null,
        ]);
    }
}

