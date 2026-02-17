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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('call_uuid')->unique(); // уникальный идентификатор звонка
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('callee_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['audio', 'video'])->default('audio');
            $table->enum('status', ['pending', 'ringing', 'active', 'ended', 'missed', 'declined', 'failed'])
                ->default('pending');
            $table->text('sdp_offer')->nullable();
            $table->text('sdp_answer')->nullable();
            $table->json('ice_candidates')->nullable(); // массив ICE кандидатов
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // длительность в секундах
            $table->string('end_reason')->nullable(); // причина завершения
            $table->timestamps();

            $table->index('call_uuid');
            $table->index('status');
            $table->index(['caller_id', 'callee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
