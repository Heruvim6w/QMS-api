<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageKey;
use App\Models\MessageReadStatus;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function __construct(private readonly EncryptionService $encryptionService)
    {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chats = Chat::with('users')->get();

        foreach ($chats as $chat) {
            /** @var \Illuminate\Support\Collection<User> $participants */
            $participants = $chat->users;

            if ($participants->count() < 2) {
                continue;
            }

            $messagesCount = fake()->numberBetween(5, 15);

            for ($i = 0; $i < $messagesCount; $i++) {
                /** @var User $sender */
                $sender = $participants->random();

                $type = fake()->randomElement(['text', 'image', 'voice', 'video', 'file']);

                // Генерируем сессионный ключ и шифруем контент
                $sessionKey       = $this->encryptionService->generateKey();
                $iv               = bin2hex(random_bytes(16));
                $plaintext        = fake()->sentence();
                $encryptedContent = $this->encryptionService->encrypt($plaintext, $sessionKey, $iv);

                $message = Message::create([
                    'chat_id'           => $chat->id,
                    'sender_id'         => $sender->id,
                    'encrypted_content' => $encryptedContent,
                    'encryption_key'    => null,
                    'iv'                => $iv,
                    'type'              => $type,
                ]);

                // Создаём MessageKey для каждого участника чата
                foreach ($participants as $participant) {
                    if (empty($participant->public_key)) {
                        continue;
                    }
                    openssl_public_encrypt(hex2bin($sessionKey), $encryptedKey, $participant->public_key);
                    MessageKey::create([
                        'message_id'    => $message->id,
                        'user_id'       => $participant->id,
                        'encrypted_key' => bin2hex($encryptedKey),
                    ]);
                }

                // Вложение для медиа-сообщений
                if (in_array($type, ['image', 'voice', 'video', 'file'])) {
                    Attachment::factory()->{$type}()->create([
                        'message_id' => $message->id,
                    ]);
                }

                // Статусы чтения для остальных участников
                foreach ($participants as $participant) {
                    if ($participant->id === $sender->id) {
                        continue;
                    }
                    MessageReadStatus::factory()
                        ->state([
                            'message_id' => $message->id,
                            'user_id'    => $participant->id,
                        ])
                        ->when(
                            fake()->boolean(70),
                            fn($f) => $f->read(),
                            fn($f) => $f->delivered()
                        )
                        ->create();
                }
            }
        }
    }
}

