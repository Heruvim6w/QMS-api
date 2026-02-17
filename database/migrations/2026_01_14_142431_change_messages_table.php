<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['receiver_id']);
            $table->dropColumn('receiver_id');
            $table->dropColumn('file_path');
            $table->foreignId('chat_id')->constrained('chats')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('receiver_id')->constrained('users');
            $table->dropForeign(['chat_id']);
            $table->dropColumn('chat_id');
            $table->string('file_path')->nullable();
        });
    }
};
