<?php

namespace Database\Seeders;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoginTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        // Для каждого пользователя создаём несколько токенов логина
        foreach ($users as $user) {
            // Создаём один подтверждённый токен
            LoginToken::factory()
                ->confirmed()
                ->create([
                    'user_id' => $user->id,
                    'device_name' => 'Desktop Chrome',
                    'ip_address' => fake()->ipv4(),
                ]);

            // Создаём один неподтверждённый токен
            LoginToken::factory()
                ->create([
                    'user_id' => $user->id,
                    'device_name' => 'Mobile Safari',
                    'ip_address' => fake()->ipv4(),
                ]);

            // Создаём один истекший токен
            LoginToken::factory()
                ->expired()
                ->create([
                    'user_id' => $user->id,
                    'device_name' => 'Tablet Firefox',
                    'ip_address' => fake()->ipv4(),
                ]);
        }
    }
}

