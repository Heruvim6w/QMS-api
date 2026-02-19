<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаём тестовых пользователей с известными данными
        User::factory()->create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'username' => 'alice_j',
        ]);

        User::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'username' => 'bob_smith',
        ]);

        User::factory()->create([
            'name' => 'Carol White',
            'email' => 'carol@example.com',
            'username' => 'carol_w',
        ]);

        User::factory()->create([
            'name' => 'David Brown',
            'email' => 'david@example.com',
            'username' => 'david_b',
        ]);

        User::factory()->create([
            'name' => 'Eve Davis',
            'email' => 'eve@example.com',
            'username' => 'eve_d',
        ]);

        // Создаём дополнительно 10 случайных пользователей
        User::factory(10)->create();
    }
}

