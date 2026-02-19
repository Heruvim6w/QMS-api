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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private', 'group', 'favorites']); // тип чата
            $table->string('name')->nullable(); // название для групповых чатов
            $table->foreignId('creator_id')->nullable()->constrained('users'); // кто создал
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('chat_users', function (Blueprint $table) {
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_muted')->default(false);
            $table->timestamp('joined_at')->nullable(); // когда присоединился
            $table->boolean('is_active')->default(true); // активен ли участник
            $table->primary(['chat_id', 'user_id']); // составной первичный ключ
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
