<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('user_progress')
            ->select('user_id', 'lesson_id')
            ->groupBy('user_id', 'lesson_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new \RuntimeException('Duplicate user_progress rows exist; resolve them before adding the unique user/lesson constraint.');
        }

        if (DB::table('bookmarks')
            ->select('user_id', 'bookmarkable_type', 'bookmarkable_id')
            ->groupBy('user_id', 'bookmarkable_type', 'bookmarkable_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new \RuntimeException('Duplicate bookmark rows exist; resolve them before adding the unique user/bookmark constraint.');
        }

        Schema::table('user_progress', function (Blueprint $table): void {
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('status');
            $table->unique(['user_id', 'lesson_id'], 'user_progress_user_lesson_unique');
        });

        Schema::table('bookmarks', function (Blueprint $table): void {
            $table->unique(
                ['user_id', 'bookmarkable_type', 'bookmarkable_id'],
                'bookmarks_user_item_unique'
            );
        });

        DB::table('user_progress')->where('status', 'completed')->update(['progress_percent' => 100]);
    }

    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table): void {
            $table->dropUnique('bookmarks_user_item_unique');
        });

        Schema::table('user_progress', function (Blueprint $table): void {
            $table->dropUnique('user_progress_user_lesson_unique');
            $table->dropColumn('progress_percent');
        });
    }
};
