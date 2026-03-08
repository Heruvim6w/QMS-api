<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Получаем имя подключения БД
        $connection = DB::connection()->getDriverName();

        // Создаём новую временную таблицу с UUID
        Schema::create('users_new', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->text('public_key')->nullable();
            $table->text('private_key')->nullable();
            $table->timestamps();
        });

        // Копируем данные из старой таблицы в новую
        // Для SQLite просто копируем id как есть (уже в нужном формате)
        // Для других БД преобразуем в UUID
        if ($connection === 'sqlite') {
            // SQLite: просто копируем существующие id
            DB::statement('INSERT INTO users_new (id, name, email, email_verified_at, password, remember_token, public_key, private_key, created_at, updated_at)
                           SELECT id, name, email, email_verified_at, password, remember_token, public_key, private_key, created_at, updated_at FROM users');
        } else {
            // PostgreSQL и другие: преобразуем в UUID
            DB::statement('INSERT INTO users_new (id, name, email, email_verified_at, password, remember_token, public_key, private_key, created_at, updated_at)
                           SELECT id::text::uuid, name, email, email_verified_at, password, remember_token, public_key, private_key, created_at, updated_at FROM users');
        }

        // Удаляем старую таблицу
        Schema::dropIfExists('users');

        // Переименовываем новую таблицу в users
        Schema::rename('users_new', 'users');

        // Обновляем sessions таблицу
        Schema::table('sessions', function (Blueprint $table) {
            // Сначала удаляем индекс
            $table->dropIndex('sessions_user_id_index');

            // Потом удаляем foreign key
            $table->dropForeign(['user_id']);

            // Потом удаляем старую колонку
            $table->dropColumn('user_id');
        });

        // Добавляем новую UUID колонку
        Schema::table('sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Обратная миграция: удаляем UUID колонку user_id из sessions
        Schema::table('sessions', function (Blueprint $table) {
            // Сначала удаляем индекс
            $table->dropIndex('sessions_user_id_index');

            // Потом удаляем foreign key
            $table->dropForeign(['user_id']);

            // Потом удаляем колонку
            $table->dropColumn('user_id');
        });

        // Восстанавливаем старую структуру sessions
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Примечание: обратная миграция для users таблицы будет очень сложной
        // и потребует конвертирования UUID обратно в integer ID.
        // Рекомендуется не откатывать эту миграцию в production.
    }
};


