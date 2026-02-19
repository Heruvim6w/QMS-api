<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'text/plain',
            'video/mp4',
            'audio/mpeg',
        ];

        $mimeType = fake()->randomElement($mimeTypes);
        $name = fake()->word() . '.' . $this->getExtensionFromMime($mimeType);

        return [
            'message_id' => Message::factory(),
            'file_path' => '/storage/uploads/' . fake()->uuid() . '/' . $name,
            'mime_type' => $mimeType,
            'size' => fake()->numberBetween(1024, 10485760), // 1KB to 10MB
            'name' => $name,
        ];
    }

    /**
     * Создать изображение
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => fake()->randomElement(['image/jpeg', 'image/png', 'image/gif']),
            'name' => fake()->word() . '.jpg',
            'size' => fake()->numberBetween(10240, 5242880), // 10KB to 5MB
        ]);
    }

    /**
     * Создать PDF документ
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'application/pdf',
            'name' => fake()->word() . '.pdf',
            'size' => fake()->numberBetween(51200, 10485760), // 50KB to 10MB
        ]);
    }

    /**
     * Создать видеофайл
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'video/mp4',
            'name' => fake()->word() . '.mp4',
            'size' => fake()->numberBetween(1048576, 104857600), // 1MB to 100MB
        ]);
    }

    /**
     * Создать аудиофайл
     */
    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'audio/mpeg',
            'name' => fake()->word() . '.mp3',
            'size' => fake()->numberBetween(1048576, 52428800), // 1MB to 50MB
        ]);
    }

    /**
     * Получить расширение файла из MIME типа
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'text/plain' => 'txt',
            'video/mp4' => 'mp4',
            'audio/mpeg' => 'mp3',
        ];

        return $extensions[$mimeType] ?? 'bin';
    }
}

