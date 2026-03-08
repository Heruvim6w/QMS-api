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
        // Создаем временные колонки и переносим данные
        $this->migrateMessages();
        $this->migrateChats();
        $this->migrateChatUsers();
        $this->migrateMessageReadStatuses();
        $this->migrateCalls();
    }

    /**
     * Migrate messages table
     */
    private function migrateMessages(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('sender_id_uuid')->nullable()->after('chat_id');
        });

        // Копируем данные в зависимости от типа БД
        if ($connection === 'sqlite') {
            // SQLite: просто копируем (уже в нужном формате)
            DB::statement('UPDATE messages SET sender_id_uuid = sender_id WHERE sender_id IS NOT NULL');
        } else {
            // PostgreSQL: используем кастинг
            DB::statement('UPDATE messages SET sender_id_uuid = sender_id::uuid WHERE sender_id IS NOT NULL');
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
            $table->renameColumn('sender_id_uuid', 'sender_id');
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Migrate chats table - creator_id to UUID
     */
    private function migrateChats(): void
    {
        if (!Schema::hasTable('chats')) {
            return;
        }

        if (!Schema::hasColumn('chats', 'creator_id')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        Schema::table('chats', function (Blueprint $table) {
            $table->uuid('creator_id_uuid')->nullable()->after('type');
        });

        // Копируем данные в зависимости от типа БД
        if ($connection === 'sqlite') {
            DB::statement('UPDATE chats SET creator_id_uuid = creator_id WHERE creator_id IS NOT NULL');
        } else {
            DB::statement('UPDATE chats SET creator_id_uuid = creator_id::uuid WHERE creator_id IS NOT NULL');
        }

        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropColumn('creator_id');
            $table->renameColumn('creator_id_uuid', 'creator_id');
            $table->foreign('creator_id')->references('id')->on('users')->nullableOnDelete();
        });
    }

    /**
     * Migrate chat_users table
     */
    private function migrateChatUsers(): void
    {
        if (!Schema::hasTable('chat_users')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        if ($connection === 'sqlite') {
            // SQLite: нужно пересоздать таблицу, так как нельзя удалять PRIMARY KEY
            // Копируем данные в новую таблицу
            DB::statement('
                CREATE TABLE chat_users_new (
                    chat_id INTEGER NOT NULL,
                    user_id TEXT NOT NULL,
                    is_muted BOOLEAN DEFAULT 0,
                    joined_at DATETIME,
                    is_active BOOLEAN DEFAULT 1,
                    PRIMARY KEY (chat_id, user_id),
                    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ');

            // Копируем существующие данные (преобразуем user_id в UUID формат)
            DB::statement('
                INSERT INTO chat_users_new (chat_id, user_id, is_muted, joined_at, is_active)
                SELECT chat_id, user_id, 0, joined_at, is_active FROM chat_users
            ');

            // Удаляем старую таблицу и переименовываем новую
            Schema::drop('chat_users');
            DB::statement('ALTER TABLE chat_users_new RENAME TO chat_users');
        } else {
            // PostgreSQL и другие БД: используем ALTER TABLE
            Schema::table('chat_users', function (Blueprint $table) {
                $table->uuid('user_id_uuid')->nullable()->after('chat_id');
            });

            DB::statement('UPDATE chat_users SET user_id_uuid = user_id::uuid WHERE user_id IS NOT NULL');

            Schema::table('chat_users', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
                $table->renameColumn('user_id_uuid', 'user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    /**
     * Migrate message_read_statuses table
     */
    private function migrateMessageReadStatuses(): void
    {
        if (!Schema::hasTable('message_read_statuses')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        Schema::table('message_read_statuses', function (Blueprint $table) {
            $table->uuid('user_id_uuid')->nullable()->after('message_id');
        });

        // Копируем данные в зависимости от типа БД
        if ($connection === 'sqlite') {
            DB::statement('UPDATE message_read_statuses SET user_id_uuid = user_id WHERE user_id IS NOT NULL');
        } else {
            DB::statement('UPDATE message_read_statuses SET user_id_uuid = user_id::uuid WHERE user_id IS NOT NULL');
        }

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

        $connection = DB::connection()->getDriverName();

        if ($connection === 'sqlite') {
            // SQLite: Нужно пересоздать таблицу, так как нельзя удалять колонки с индексами
            DB::statement('
                CREATE TABLE calls_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    call_uuid TEXT NOT NULL UNIQUE,
                    chat_id INTEGER NOT NULL,
                    caller_id TEXT NOT NULL,
                    callee_id TEXT NOT NULL,
                    type TEXT NOT NULL,
                    status TEXT NOT NULL,
                    sdp_offer TEXT,
                    sdp_answer TEXT,
                    ice_candidates TEXT,
                    started_at DATETIME,
                    answered_at DATETIME,
                    ended_at DATETIME,
                    duration INTEGER,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
                    FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (callee_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ');

            // Копируем данные (преобразуем caller_id и callee_id в UUID)
            DB::statement('
                INSERT INTO calls_new (
                    id, call_uuid, chat_id, caller_id, callee_id, type, status,
                    sdp_offer, sdp_answer, ice_candidates, started_at, answered_at,
                    ended_at, duration, created_at, updated_at
                )
                SELECT
                    id, call_uuid, chat_id, caller_id, callee_id, type, status,
                    sdp_offer, sdp_answer, ice_candidates, started_at, answered_at,
                    ended_at, duration, created_at, updated_at
                FROM calls
            ');

            Schema::drop('calls');
            DB::statement('ALTER TABLE calls_new RENAME TO calls');
        } else {
            // PostgreSQL и другие БД: используем ALTER TABLE
            Schema::table('calls', function (Blueprint $table) {
                // Сначала удаляем индекс
                $table->dropIndex('calls_caller_id_callee_id_index');

                // Создаем временные UUID колонки
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
        $this->rollbackChats();
    }

    private function rollbackChatUsers(): void
    {
        if (!Schema::hasTable('chat_users')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        if ($connection === 'sqlite') {
            // SQLite: пересоздаем таблицу с bigInteger
            DB::statement('
                CREATE TABLE chat_users_new (
                    chat_id INTEGER NOT NULL,
                    user_id BIGINT NOT NULL,
                    joined_at DATETIME,
                    is_active BOOLEAN DEFAULT 1,
                    PRIMARY KEY (chat_id, user_id),
                    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ');

            // Копируем данные (нельзя преобразовать UUID в bigint в SQLite, поэтому используем NULL)
            DB::statement('
                INSERT INTO chat_users_new (chat_id, user_id, joined_at, is_active)
                SELECT chat_id, NULL, joined_at, is_active FROM chat_users
            ');

            Schema::drop('chat_users');
            DB::statement('ALTER TABLE chat_users_new RENAME TO chat_users');
        } else {
            // PostgreSQL и другие БД
            Schema::table('chat_users', function (Blueprint $table) {
                $table->bigInteger('user_id_bigint')->nullable()->after('chat_id');
            });

            DB::statement('UPDATE chat_users SET user_id_bigint = (user_id::text)::bigint WHERE user_id IS NOT NULL');

            Schema::table('chat_users', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
                $table->renameColumn('user_id_bigint', 'user_id');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }
    }

    // Аналогичные rollback методы для других таблиц...
    private function rollbackMessageReadStatuses(): void
    {
        if (!Schema::hasTable('message_read_statuses')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        Schema::table('message_read_statuses', function (Blueprint $table) {
            $table->bigInteger('user_id_bigint')->nullable()->after('message_id');
        });

        if ($connection === 'sqlite') {
            DB::statement('UPDATE message_read_statuses SET user_id_bigint = NULL WHERE user_id IS NOT NULL');
        } else {
            DB::statement('UPDATE message_read_statuses SET user_id_bigint = (user_id::text)::bigint WHERE user_id IS NOT NULL');
        }

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

        $connection = DB::connection()->getDriverName();

        if ($connection === 'sqlite') {
            // SQLite: пересоздаем таблицу с bigInteger
            DB::statement('
                CREATE TABLE calls_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    call_uuid TEXT NOT NULL UNIQUE,
                    chat_id INTEGER NOT NULL,
                    caller_id BIGINT NOT NULL,
                    callee_id BIGINT NOT NULL,
                    type TEXT NOT NULL,
                    status TEXT NOT NULL,
                    sdp_offer TEXT,
                    sdp_answer TEXT,
                    ice_candidates TEXT,
                    started_at DATETIME,
                    answered_at DATETIME,
                    ended_at DATETIME,
                    duration INTEGER,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
                    FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (callee_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ');

            // Копируем данные (используем NULL для UUID->bigint конвертации)
            DB::statement('
                INSERT INTO calls_new (
                    id, call_uuid, chat_id, caller_id, callee_id, type, status,
                    sdp_offer, sdp_answer, ice_candidates, started_at, answered_at,
                    ended_at, duration, created_at, updated_at
                )
                SELECT
                    id, call_uuid, chat_id, NULL, NULL, type, status,
                    sdp_offer, sdp_answer, ice_candidates, started_at, answered_at,
                    ended_at, duration, created_at, updated_at
                FROM calls
            ');

            Schema::drop('calls');
            DB::statement('ALTER TABLE calls_new RENAME TO calls');
        } else {
            // PostgreSQL и другие БД
            Schema::table('calls', function (Blueprint $table) {
                $table->dropIndex('calls_caller_id_callee_id_index');

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
    }

    private function rollbackMessages(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        Schema::table('messages', function (Blueprint $table) {
            $table->bigInteger('sender_id_bigint')->nullable()->after('chat_id');
        });

        if ($connection === 'sqlite') {
            DB::statement('UPDATE messages SET sender_id_bigint = NULL WHERE sender_id IS NOT NULL');
        } else {
            DB::statement('UPDATE messages SET sender_id_bigint = (sender_id::text)::bigint WHERE sender_id IS NOT NULL');
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
            $table->renameColumn('sender_id_bigint', 'sender_id');
            $table->foreign('sender_id')->references('id')->on('users');
        });
    }

    private function rollbackChats(): void
    {
        if (!Schema::hasTable('chats')) {
            return;
        }

        // Проверяем есть ли creator_id
        if (!Schema::hasColumn('chats', 'creator_id')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        Schema::table('chats', function (Blueprint $table) {
            $table->bigInteger('creator_id_bigint')->nullable()->after('type');
        });

        if ($connection === 'sqlite') {
            DB::statement('UPDATE chats SET creator_id_bigint = NULL WHERE creator_id IS NOT NULL');
        } else {
            DB::statement('UPDATE chats SET creator_id_bigint = (creator_id::text)::bigint WHERE creator_id IS NOT NULL');
        }

        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropColumn('creator_id');
            $table->renameColumn('creator_id_bigint', 'creator_id');
            $table->foreign('creator_id')->references('id')->on('users')->nullable();
        });
    }
};
