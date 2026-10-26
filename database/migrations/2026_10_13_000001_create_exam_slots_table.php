<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stable per-student exam slots.
 *
 * Each student is offered up to three eligible published exams at a time. The
 * selection must not be re-randomised on every request, so the chosen exam ids
 * are persisted here (one row per occupied slot). A slot is only replaced when
 * its exam has been passed or has otherwise become ineligible; the other slots
 * are left untouched. Existing attempts, scores, progress and exam data are
 * never modified by this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_slots')) {
            return;
        }

        Schema::create('exam_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            // 1..3 : the stable slot position shown to the student.
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            // One exam per slot position, and the same exam never occupies two slots.
            $table->unique(['user_id', 'position']);
            $table->unique(['user_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_slots');
    }
};
