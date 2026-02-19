<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('token')->unique()->index(); // токен для подтверждения
            $table->string('device_name'); // название устройства
            $table->string('ip_address')->nullable(); // IP адрес
            $table->string('user_agent')->nullable(); // User-Agent
            $table->boolean('is_confirmed')->default(false); // подтвержден ли логин
            $table->timestamp('confirmed_at')->nullable(); // время подтверждения
            $table->timestamp('expires_at'); // истекает через 3 часа
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index('user_id');
            $table->index('token');
            $table->index('expires_at');

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_tokens');
    }
};

