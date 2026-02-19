<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Seeder;

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
            Chat::factory()
                ->private()
                ->create([
                    'creator_id' => $users[$i]->id,
                ])
                ->users()
                ->attach([
                    $users[$i]->id => [
                        'joined_at' => now(),
                        'is_active' => true,
                    ],
                    $users[$i + 1]->id => [
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

            foreach ($members as $member) {
                $groupChat->users()->attach($member->id, [
                    'joined_at' => now()->subDays(fake()->numberBetween(1, 30)),
                    'is_active' => fake()->boolean(80),
                    'is_muted' => fake()->boolean(20),
                ]);
            }
        }

        // Создаём чаты "Избранное" для каждого пользователя
        foreach ($users as $user) {
            Chat::factory()
                ->favorites()
                ->create([
                    'creator_id' => $user->id,
                ])
                ->users()
                ->attach($user->id, [
                    'joined_at' => now(),
                    'is_active' => true,
                ]);
        }
    }
}

