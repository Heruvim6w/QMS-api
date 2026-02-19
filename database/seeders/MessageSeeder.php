<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageReadStatus;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chats = Chat::all();

        // Создаём сообщения для каждого чата
        foreach ($chats as $chat) {
            $users = $chat->users()->pluck('users.id')->toArray();

            if (count($users) < 2) {
                continue; // Пропускаем чаты без участников
            }

            // Создаём 5-15 сообщений на чат
            $messagesCount = fake()->numberBetween(5, 15);

            for ($i = 0; $i < $messagesCount; $i++) {
                $sender = $users[array_rand($users)];

                // Определяем тип сообщения
                $type = fake()->randomElement(['text', 'image', 'voice', 'video', 'file']);

                $message = Message::factory()
                    ->{$type}()
                    ->create([
                        'chat_id' => $chat->id,
                        'sender_id' => $sender,
                    ]);

                // Добавляем вложение для файловых и медиа сообщений
                if (in_array($type, ['image', 'voice', 'video', 'file'])) {
                    Attachment::factory()
                        ->{$type}()
                        ->create([
                            'message_id' => $message->id,
                        ]);
                }

                // Создаём статусы чтения для других участников
                foreach ($users as $userId) {
                    if ($userId !== $sender) {
                        MessageReadStatus::factory()
                            ->state([
                                'message_id' => $message->id,
                                'user_id' => $userId,
                            ])
                            ->when(
                                fake()->boolean(70),
                                fn ($factory) => $factory->read(),
                                fn ($factory) => $factory->delivered()
                            )
                            ->create();
                    }
                }
            }
        }
    }
}

