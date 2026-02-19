<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['text', 'image', 'voice', 'video', 'file']);

        return [
            'chat_id' => Chat::factory(),
            'sender_id' => User::factory(),
            'encrypted_content' => json_encode([
                'tag' => Str::random(16),
                'ciphertext' => Str::random(32),
            ]),
            'encryption_key' => Str::random(64),
            'iv' => Str::random(32),
            'type' => $type,
        ];
    }

    /**
     * Создать текстовое сообщение
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::TYPE_TEXT,
        ]);
    }

    /**
     * Создать сообщение с изображением
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::TYPE_IMAGE,
        ]);
    }

    /**
     * Создать голосовое сообщение
     */
    public function voice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::TYPE_VOICE,
        ]);
    }

    /**
     * Создать видео сообщение
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::TYPE_VIDEO,
        ]);
    }

    /**
     * Создать сообщение с файлом
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::TYPE_FILE,
        ]);
    }

    /**
     * Создать прочитанное сообщение
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}

