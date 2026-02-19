<?php

namespace Database\Seeders;

use App\Models\MessageReadStatus;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageReadStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $messages = Message::all();
        $users = User::all();

        // Для каждого сообщения создаём статусы чтения для случайных пользователей
        foreach ($messages as $message) {
            // Исключаем отправителя из списка получателей
            $recipients = $users->where('id', '!=', $message->sender_id);

            // Случайно выбираем 2-5 получателей
            $selectedUsers = $recipients->shuffle()->slice(0, fake()->numberBetween(2, 5));

            foreach ($selectedUsers as $user) {
                MessageReadStatus::factory()
                    ->when(
                        fake()->boolean(70),
                        fn($factory) => $factory->read(),
                        fn($factory) => $factory->delivered()
                    )
                    ->create([
                        'message_id' => $message->id,
                        'user_id' => $user->id,
                    ]);
            }
        }
    }
}

