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
        Schema::table('users', function (Blueprint $table) {
            // Статус пользователя (online/offline)
            $table->enum('status', ['online', 'offline'])->default('offline')->after('username');

            // Выбранный статус когда пользователь онлайн
            $table->enum('online_status', [
                'online',
                'chatty',
                'angry',
                'depressed',
                'home',
                'work',
                'eating',
                'away',
                'unavailable',
                'busy',
                'do_not_disturb'
            ])->default('online')->after('status');

            // Кастомный текст статуса (до 50 символов, может быть emoji)
            $table->string('custom_status', 50)->nullable()->after('online_status');

            // Время последнего онлайна
            $table->timestamp('last_seen_at')->nullable()->after('custom_status')->index();

            // Индексы для быстрого поиска
            $table->index('status');
            $table->index('online_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['online_status']);
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn(['status', 'online_status', 'custom_status', 'last_seen_at']);
        });
    }
};

