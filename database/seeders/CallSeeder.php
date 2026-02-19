<?php

namespace Database\Seeders;

use App\Models\Call;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Seeder;

class CallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $chats = Chat::all();

        // Создаём звонки между пользователями
        for ($i = 0; $i < 20; $i++) {
            $caller = $users->random();
            $callee = $users->where('id', '!=', $caller->id)->random();
            $chat = $chats->random();

            // Определяем статус звонка
            $status = fake()->randomElement(['completed', 'missed', 'declined']);

            Call::factory()
                ->{$status}()
                ->create([
                    'chat_id' => $chat->id,
                    'caller_id' => $caller->id,
                    'callee_id' => $callee->id,
                    'type' => fake()->randomElement(['audio', 'video']),
                ]);
        }

        // Создаём несколько активных звонков
        for ($i = 0; $i < 3; $i++) {
            $caller = $users->random();
            $callee = $users->where('id', '!=', $caller->id)->random();
            $chat = $chats->random();

            Call::factory()->create([
                'chat_id' => $chat->id,
                'caller_id' => $caller->id,
                'callee_id' => $callee->id,
                'type' => fake()->randomElement(['audio', 'video']),
                'status' => Call::STATUS_ACTIVE,
                'started_at' => now()->subMinutes(fake()->numberBetween(1, 10)),
                'answered_at' => now()->subMinutes(fake()->numberBetween(1, 9)),
            ]);
        }
    }
}

