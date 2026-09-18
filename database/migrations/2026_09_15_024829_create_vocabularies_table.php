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
        Schema::create('vocabularies', function (Blueprint $table) {
            
        $table->id();
        $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
        $table->string('word');
        $table->string('reading');
        $table->string('romaji')->nullable();
        $table->string('meaning');
        $table->string('audio')->nullable();
        $table->text('example_sentence')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vocabularies');
    }
};
