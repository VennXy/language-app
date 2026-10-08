<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_question_mistakes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vocabulary_id')->constrained('vocabularies')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'vocabulary_id'], 'user_mistakes_user_question_unique');
            $table->index(['user_id', 'lesson_id'], 'user_mistakes_user_lesson_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_question_mistakes');
    }
};
