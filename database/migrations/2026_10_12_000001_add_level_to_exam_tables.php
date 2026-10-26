<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void 
    {
        // 1. Exams must belong to exactly one level.
        if (! Schema::hasColumn('exams', 'level_id')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->foreignId('level_id')
                    ->nullable()
                    ->after('title')
                    ->constrained('levels')
                    ->cascadeOnDelete();

                $table->index(['level_id', 'status'], 'exams_level_status_index');
            });
        }

        // 2. Questions need a reliable explanation and an honest provenance marker.
        if (! Schema::hasColumn('exam_questions', 'explanation')) {
            Schema::table('exam_questions', function (Blueprint $table): void {
                $table->text('explanation')->nullable()->after('option_d');
            });
        }

        if (! Schema::hasColumn('exam_questions', 'source')) {
            Schema::table('exam_questions', function (Blueprint $table): void {
                // 'manual' = authored by a contributor, 'generated' = seeder-produced draft.
                $table->string('source', 32)->default('manual')->after('explanation');
            });
        }

        // 3. Allow repeat attempts. The unique(exam_id, user_id) index made a second
        //    attempt impossible; replace it with a plain lookup index. Existing
        //    attempt rows, result history and snapshots are untouched.
        //    Driver-safe: MySQL drops the named unique index; SQLite (tests)
        //    uses a try/catch because SQLite cannot drop indexes by constraint.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            try {
                Schema::table('exam_attempts', function (Blueprint $table): void {
                    $table->dropUnique(['exam_id', 'user_id']);
                });
            } catch (Throwable) {
                // Unique index already absent; nothing to drop.
            }

            $existing = collect(Schema::getConnection()->select("PRAGMA index_list('exam_attempts')"))
                ->pluck('name');

            if (! $existing->contains('exam_attempts_exam_user_index')) {
                Schema::table('exam_attempts', function (Blueprint $table): void {
                    $table->index(['exam_id', 'user_id'], 'exam_attempts_exam_user_index');
                });
            }
        } else {
            $connection = Schema::getConnection();
            $indexName = 'exam_attempts_exam_id_user_id_unique';

            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', $connection->getDatabaseName())
                ->where('table_name', 'exam_attempts')
                ->where('index_name', $indexName)
                ->exists();

            if ($exists) {
                Schema::table('exam_attempts', function (Blueprint $table) use ($indexName): void {
                    $table->dropUnique($indexName);
                    $table->index(['exam_id', 'user_id'], 'exam_attempts_exam_user_index');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropIndex('exam_attempts_exam_user_index');
            $table->unique(['exam_id', 'user_id']);
        });

        Schema::table('exam_questions', function (Blueprint $table): void {
            $table->dropColumn(['explanation', 'source']);
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->dropIndex('exams_level_status_index');
            $table->dropConstrainedForeignId('level_id');
        });
    }
};
