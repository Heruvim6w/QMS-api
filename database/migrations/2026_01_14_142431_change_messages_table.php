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
        Schema::table('messages', function (Blueprint $table) {
            // Сначала удаляем индекс, который ссылается на receiver_id
            $table->dropIndex('messages_sender_id_receiver_id_index');

            // Потом удаляем foreign key
            $table->dropForeign(['receiver_id']);

            // Потом удаляем колонки
            $table->dropColumn('receiver_id');
            $table->dropColumn('file_path');

            // Добавляем новую колонку для связи с чатом
            $table->foreignId('chat_id')->constrained('chats')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Удаляем связь и колонку chat_id
            $table->dropForeign(['chat_id']);
            $table->dropColumn('chat_id');

            // Восстанавливаем старые колонки
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_path')->nullable();

            // Восстанавливаем индекс
            $table->index(['sender_id', 'receiver_id'], 'messages_sender_id_receiver_id_index');
        });
    }
};
