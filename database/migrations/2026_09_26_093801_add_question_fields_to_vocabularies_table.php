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
    Schema::table('vocabularies', function (Blueprint $table) {
        $table->string('question_type')->default('word_selection'); 
        $table->string('audio_path')->nullable(); 
        json_encode($table->text('options')->nullable()); 
        $table->text('correct_answer')->nullable(); 
    });
}

public function down(): void
{
    Schema::table('vocabularies', function (Blueprint $table) {
        $table->dropColumn(['question_type', 'audio_path', 'options', 'correct_answer']);
    });
}
};
