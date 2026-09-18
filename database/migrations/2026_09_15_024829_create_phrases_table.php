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
        Schema::create('phrases', function (Blueprint $table) {
         $table->id();
        $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
        $table->text('phrase');
        $table->text('meaning');
        $table->string('romaji')->nullable();
        $table->string('audio_text')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phrases');
    }
};
