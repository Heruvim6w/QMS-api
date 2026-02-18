<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
        DB::statement('INSERT INTO users_new (id, name, email, email_verified_at, password, remember_token, public_key, private_key, created_at, updated_at)
                       SELECT id::text::uuid, name, email, email_verified_at, password, remember_token, public_key, private_key, created_at, updated_at FROM users');

        // Удаляем старую таблицу
        Schema::dropIfExists('users');

        // Переименовываем новую таблицу в users
        Schema::rename('users_new', 'users');

        // Обновляем sessions таблицу
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullableOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Обратная миграция будет сложной, поэтому оставляем минимальную
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};


