<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_vocabulary_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_id')
                ->constrained('lessons')
                ->cascadeOnDelete();
            $table->string('japanese', 100);
            $table->string('reading', 100);
            $table->string('english');
            $table->string('image_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['lesson_id', 'japanese', 'reading'],
                'lesson_vocab_items_lesson_japanese_reading_unique'
            );
            $table->index(
                ['lesson_id', 'sort_order'],
                'lesson_vocab_items_lesson_order_index'
            );
        });

        Schema::create('lesson_vocabulary_questions', function (Blueprint $table): void {
            $table->foreignId('lesson_vocabulary_item_id')
                ->constrained('lesson_vocabulary_items')
                ->cascadeOnDelete();
            $table->foreignId('vocabulary_question_id')
                ->constrained('vocabularies')
                ->cascadeOnDelete();
            $table->string('stage', 32);
            $table->unsignedInteger('stage_order')->default(0);

            $table->index(
                'lesson_vocabulary_item_id',
                'lesson_vocab_questions_item_index'
            );
            $table->index(
                'vocabulary_question_id',
                'lesson_vocab_questions_question_index'
            );
            $table->unique(
                ['lesson_vocabulary_item_id', 'vocabulary_question_id', 'stage'],
                'lesson_vocab_questions_item_question_stage_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_vocabulary_questions');
        Schema::dropIfExists('lesson_vocabulary_items');
    }
};
