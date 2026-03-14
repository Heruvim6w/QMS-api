<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageKey;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        // Валидные hex-плейсхолдеры правильного формата.
        // Реальное шифрование выполняется в afterCreating.
        return [
            'chat_id'           => Chat::factory(),
            'sender_id'         => User::factory(),
            'encrypted_content' => bin2hex(random_bytes(80)), // 16 байт тег + 64 байт данных
            'encryption_key'    => null,
            'iv'                => bin2hex(random_bytes(16)),
            'type'              => fake()->randomElement(['text', 'image', 'voice', 'video', 'file']),
        ];
    }

    /**
     * Configure the factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Message $message) {
            $sender = User::find($message->sender_id);

            if (!$sender?->public_key) {
                return;
            }

            /** @var EncryptionService $encryptionService */
            $encryptionService = app(EncryptionService::class);

            // Генерируем сессионный ключ и шифруем плейсхолдер-контент
            $sessionKey = $encryptionService->generateKey();
            $iv         = bin2hex(random_bytes(16));
            $encryptedContent = $encryptionService->encrypt(fake()->sentence(), $sessionKey, $iv);

            // Обновляем сообщение реальными зашифрованными данными
            $message->update([
                'encrypted_content' => $encryptedContent,
                'iv'                => $iv,
            ]);

            // Создаём MessageKey для отправителя
            openssl_public_encrypt(hex2bin($sessionKey), $encryptedKey, $sender->public_key);
            MessageKey::create([
                'message_id'    => $message->id,
                'user_id'       => $sender->id,
                'encrypted_key' => bin2hex($encryptedKey),
            ]);
        });
    }

    /**
     * Создать текстовое сообщение
     */
    public function text(): static
    {
        return $this->state(fn(array $attributes) => ['type' => Message::TYPE_TEXT]);
    }

    /**
     * Создать сообщение с изображением
     */
    public function image(): static
    {
        return $this->state(fn(array $attributes) => ['type' => Message::TYPE_IMAGE]);
    }

    /**
     * Создать голосовое сообщение
     */
    public function voice(): static
    {
        return $this->state(fn(array $attributes) => ['type' => Message::TYPE_VOICE]);
    }

    /**
     * Создать видео сообщение
     */
    public function video(): static
    {
        return $this->state(fn(array $attributes) => ['type' => Message::TYPE_VIDEO]);
    }

    /**
     * Создать сообщение с файлом
     */
    public function file(): static
    {
        return $this->state(fn(array $attributes) => ['type' => Message::TYPE_FILE]);
    }

    /**
     * Создать прочитанное сообщение
     */
    public function read(): static
    {
        return $this->state(fn(array $attributes) => ['read_at' => now()]);
    }
}

