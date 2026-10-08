<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist the in-lesson training state (question/teaching position) alongside the
     * existing per-user lesson progress row so a learner resumes at the same position
     * when the HTTP session copy is gone (e.g. expiry after leaving at 0 hearts).
     */
    public function up(): void
    {
        Schema::table('user_progress', function (Blueprint $table): void {
            $table->text('lesson_state')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_progress', function (Blueprint $table): void {
            $table->dropColumn('lesson_state');
        });
    }
};
