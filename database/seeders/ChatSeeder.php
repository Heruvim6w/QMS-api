<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        // Создаём приватные чаты между пользователями
        for ($i = 0; $i < count($users) - 1; $i++) {
            $chat = Chat::factory()
                ->private()
                ->create([
                    'creator_id' => $users[$i]->id,
                ]);

            // Добавляем пользователей напрямую в таблицу
            DB::table('chat_users')->insert([
                [
                    'chat_id' => $chat->id,
                    'user_id' => $users[$i]->id,
                    'is_muted' => false,
                    'joined_at' => now(),
                    'is_active' => true,
                ],
                [
                    'chat_id' => $chat->id,
                    'user_id' => $users[$i + 1]->id,
                    'is_muted' => false,
                    'joined_at' => now()->addMinutes(5),
                    'is_active' => true,
                ],
            ]);
        }

        // Создаём групповые чаты
        for ($i = 0; $i < 3; $i++) {
            $creator = $users->random();
            $groupChat = Chat::factory()
                ->group()
                ->create([
                    'creator_id' => $creator->id,
                ]);

            // Добавляем случайных участников в групповой чат
            $members = $users->shuffle()->slice(0, fake()->numberBetween(3, 6));

            $insertData = [];
            foreach ($members as $member) {
                $insertData[] = [
                    'chat_id' => $groupChat->id,
                    'user_id' => $member->id,
                    'is_muted' => fake()->boolean(20),
                    'joined_at' => now()->subDays(fake()->numberBetween(1, 30)),
                    'is_active' => fake()->boolean(80),
                ];
            }
            DB::table('chat_users')->insert($insertData);
        }

        // Создаём чаты "Избранное" для каждого пользователя
        foreach ($users as $user) {
            $favoritesChat = Chat::factory()
                ->favorites()
                ->create([
                    'creator_id' => $user->id,
                ]);

            DB::table('chat_users')->insert([
                'chat_id' => $favoritesChat->id,
                'user_id' => $user->id,
                'is_muted' => false,
                'joined_at' => now(),
                'is_active' => true,
            ]);
        }
    }
}
