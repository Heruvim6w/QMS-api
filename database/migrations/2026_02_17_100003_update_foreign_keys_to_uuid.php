<?php
//
//use Illuminate\Database\Migrations\Migration;
//use Illuminate\Database\Schema\Blueprint;
//use Illuminate\Support\Facades\Schema;
//
//return new class extends Migration
//{
//    /**
//     * Run the migrations.
//     */
//    public function up(): void
//    {
//        // Обновляем таблицу chat_users
//        if (Schema::hasTable('chat_users')) {
//            Schema::table('chat_users', function (Blueprint $table) {
//                $table->dropForeign(['user_id']);
//            });
//
//            Schema::table('chat_users', function (Blueprint $table) {
//                // Изменяем тип колонки с bigInteger на uuid string
//                $table->dropColumn('user_id');
//            });
//
//            Schema::table('chat_users', function (Blueprint $table) {
//                $table->uuid('user_id')->after('chat_id');
//                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
//            });
//        }
//
//        // Обновляем таблицу message_read_statuses
//        if (Schema::hasTable('message_read_statuses')) {
//            Schema::table('message_read_statuses', function (Blueprint $table) {
//                $table->dropForeign(['user_id']);
//            });
//
//            Schema::table('message_read_statuses', function (Blueprint $table) {
//                $table->dropColumn('user_id');
//            });
//
//            Schema::table('message_read_statuses', function (Blueprint $table) {
//                $table->uuid('user_id')->after('message_id');
//                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
//            });
//        }
//
//        // Обновляем таблицу calls
//        if (Schema::hasTable('calls')) {
//            Schema::table('calls', function (Blueprint $table) {
//                $table->dropForeign(['caller_id']);
//                $table->dropForeign(['callee_id']);
//            });
//
//            Schema::table('calls', function (Blueprint $table) {
//                $table->dropColumn('caller_id');
//                $table->dropColumn('callee_id');
//            });
//
//            Schema::table('calls', function (Blueprint $table) {
//                $table->uuid('caller_id')->after('chat_id');
//                $table->uuid('callee_id')->after('caller_id');
//                $table->foreign('caller_id')->references('id')->on('users')->cascadeOnDelete();
//                $table->foreign('callee_id')->references('id')->on('users')->cascadeOnDelete();
//            });
//        }
//
//        // Обновляем таблицу messages
//        if (Schema::hasTable('messages')) {
//            Schema::table('messages', function (Blueprint $table) {
//                $table->dropForeign(['sender_id']);
//            });
//
//            Schema::table('messages', function (Blueprint $table) {
//                $table->dropColumn('sender_id');
//            });
//
//            Schema::table('messages', function (Blueprint $table) {
//                $table->uuid('sender_id')->after('chat_id');
//                $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
//            });
//        }
//    }
//
//    /**
//     * Reverse the migrations.
//     */
//    public function down(): void
//    {
//        // Обратная миграция сложная, оставляем минимальный rollback
//    }
//};
//


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем временные колонки и переносим данные
        $this->migrateChatUsers();
        $this->migrateMessageReadStatuses();
        $this->migrateCalls();
        $this->migrateMessages();
    }

    /**
     * Migrate chat_users table
     */
    private function migrateChatUsers(): void
    {
        if (!Schema::hasTable('chat_users')) {
            return;
        }

        Schema::table('chat_users', function (Blueprint $table) {
            // Создаем временную колонку
            $table->uuid('user_id_uuid')->nullable()->after('chat_id');
        });

        // Копируем данные (предполагая, что user_id в users - UUID)
        DB::statement('UPDATE chat_users SET user_id_uuid = user_id::uuid WHERE user_id IS NOT NULL');

        Schema::table('chat_users', function (Blueprint $table) {
            // Удаляем старый внешний ключ и колонку
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Переименовываем временную колонку и добавляем внешний ключ
            $table->renameColumn('user_id_uuid', 'user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Migrate message_read_statuses table
     */
    private function migrateMessageReadStatuses(): void
    {
        if (!Schema::hasTable('message_read_statuses')) {
            return;
        }

        Schema::table('message_read_statuses', function (Blueprint $table) {
            $table->uuid('user_id_uuid')->nullable()->after('message_id');
        });

        DB::statement('UPDATE message_read_statuses SET user_id_uuid = user_id::uuid WHERE user_id IS NOT NULL');

        Schema::table('message_read_statuses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->renameColumn('user_id_uuid', 'user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Migrate calls table
     */
    private function migrateCalls(): void
    {
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            $table->uuid('caller_id_uuid')->nullable()->after('chat_id');
            $table->uuid('callee_id_uuid')->nullable()->after('caller_id_uuid');
        });

        DB::statement('UPDATE calls SET caller_id_uuid = caller_id::uuid WHERE caller_id IS NOT NULL');
        DB::statement('UPDATE calls SET callee_id_uuid = callee_id::uuid WHERE callee_id IS NOT NULL');

        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign(['caller_id']);
            $table->dropForeign(['callee_id']);
            $table->dropColumn(['caller_id', 'callee_id']);

            $table->renameColumn('caller_id_uuid', 'caller_id');
            $table->renameColumn('callee_id_uuid', 'callee_id');

            $table->foreign('caller_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('callee_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Migrate messages table
     */
    private function migrateMessages(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('sender_id_uuid')->nullable()->after('chat_id');
        });

        DB::statement('UPDATE messages SET sender_id_uuid = sender_id::uuid WHERE sender_id IS NOT NULL');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
            $table->renameColumn('sender_id_uuid', 'sender_id');
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем обратно к bigInteger
        $this->rollbackChatUsers();
        $this->rollbackMessageReadStatuses();
        $this->rollbackCalls();
        $this->rollbackMessages();
    }

    private function rollbackChatUsers(): void
    {
        if (!Schema::hasTable('chat_users')) {
            return;
        }

        Schema::table('chat_users', function (Blueprint $table) {
            $table->bigInteger('user_id_bigint')->nullable()->after('chat_id');
        });

        // Преобразуем UUID обратно в bigInteger (может потребоваться кастинг)
        DB::statement('UPDATE chat_users SET user_id_bigint = (user_id::text)::bigint WHERE user_id IS NOT NULL');

        Schema::table('chat_users', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->renameColumn('user_id_bigint', 'user_id');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    // Аналогичные rollback методы для других таблиц...
    private function rollbackMessageReadStatuses(): void
    {
        if (!Schema::hasTable('message_read_statuses')) {
            return;
        }

        Schema::table('message_read_statuses', function (Blueprint $table) {
            $table->bigInteger('user_id_bigint')->nullable()->after('message_id');
        });

        DB::statement('UPDATE message_read_statuses SET user_id_bigint = (user_id::text)::bigint WHERE user_id IS NOT NULL');

        Schema::table('message_read_statuses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->renameColumn('user_id_bigint', 'user_id');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    private function rollbackCalls(): void
    {
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            $table->bigInteger('caller_id_bigint')->nullable()->after('chat_id');
            $table->bigInteger('callee_id_bigint')->nullable()->after('caller_id_bigint');
        });

        DB::statement('UPDATE calls SET caller_id_bigint = (caller_id::text)::bigint WHERE caller_id IS NOT NULL');
        DB::statement('UPDATE calls SET callee_id_bigint = (callee_id::text)::bigint WHERE callee_id IS NOT NULL');

        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign(['caller_id']);
            $table->dropForeign(['callee_id']);
            $table->dropColumn(['caller_id', 'callee_id']);

            $table->renameColumn('caller_id_bigint', 'caller_id');
            $table->renameColumn('callee_id_bigint', 'callee_id');

            $table->foreign('caller_id')->references('id')->on('users');
            $table->foreign('callee_id')->references('id')->on('users');
        });
    }

    private function rollbackMessages(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->bigInteger('sender_id_bigint')->nullable()->after('chat_id');
        });

        DB::statement('UPDATE messages SET sender_id_bigint = (sender_id::text)::bigint WHERE sender_id IS NOT NULL');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
            $table->renameColumn('sender_id_bigint', 'sender_id');
            $table->foreign('sender_id')->references('id')->on('users');
        });
    }
};
