<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Call>
 */
class CallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subDays(fake()->numberBetween(1, 30))->subHours(fake()->numberBetween(0, 23))->subMinutes(fake()->numberBetween(0, 59));
        $answeredAt = fake()->boolean(70) ? $startedAt->copy()->addSeconds(2) : null;
        $endedAt = $answeredAt ? $answeredAt->copy()->addSeconds(fake()->numberBetween(10, 300)) : null;

        return [
            'call_uuid' => Str::uuid()->toString(),
            'chat_id' => Chat::factory(),
            'caller_id' => User::factory(),
            'callee_id' => User::factory(),
            'type' => fake()->randomElement(['audio', 'video']),
            'status' => fake()->randomElement(['pending', 'ringing', 'active', 'ended', 'missed', 'declined', 'failed']),
            'started_at' => $startedAt,
            'answered_at' => $answeredAt,
            'ended_at' => $endedAt,
            'duration' => $endedAt && $answeredAt ? (int)$endedAt->diffInSeconds($answeredAt) : null,
        ];
    }

    /**
     * Создать аудиозвонок
     */
    public function audio(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Call::TYPE_AUDIO,
        ]);
    }

    /**
     * Создать видеозвонок
     */
    public function video(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Call::TYPE_VIDEO,
        ]);
    }

    /**
     * Создать завершённый звонок
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Call::STATUS_ENDED,
            'answered_at' => $attributes['started_at']->copy()->addSeconds(2),
            'ended_at' => $attributes['started_at']->copy()->addSeconds(fake()->numberBetween(10, 300)),
        ])->afterCreating(function (Call $call) {
            $call->update([
                'duration' => (int)$call->ended_at->diffInSeconds($call->answered_at),
            ]);
        });
    }

    /**
     * Создать пропущенный звонок
     */
    public function missed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Call::STATUS_MISSED,
            'answered_at' => null,
            'ended_at' => $attributes['started_at']->copy()->addSeconds(30),
            'duration' => null,
        ]);
    }

    /**
     * Создать отклонённый звонок
     */
    public function declined(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Call::STATUS_DECLINED,
            'answered_at' => null,
            'ended_at' => $attributes['started_at']->copy()->addSeconds(3),
            'duration' => null,
        ]);
    }
}

