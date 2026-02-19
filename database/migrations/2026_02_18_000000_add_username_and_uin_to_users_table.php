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
            // UIN - уникальный идентификатор как в ICQ (не меняется, генерируется при создании)
            // Формат: 8-значное число (только цифры), уникальное, не может быть NULL
            $table->string('uin', 8)->unique()->after('email');

            // username - опциональное уникальное имя, придуманное пользователем (может быть изменено)
            // Формат: латиница, цифры, подчеркивание, дефис (3-20 символов), по умолчанию NULL
            $table->string('username')->unique()->nullable()->after('uin');

            // Индексы для быстрого поиска
            $table->index('uin');
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['uin']);
            $table->dropIndex(['username']);
            $table->dropColumn(['uin', 'username']);
        });
    }
};

