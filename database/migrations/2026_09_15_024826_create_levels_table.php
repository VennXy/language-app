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
        Schema::create('levels', function (Blueprint $table) {
           $table->id();
            $table->string('name'); // Level အမည် (ဥပမာ - N5, N4, A1 စသည်ဖြင့်)
            $table->string('level'); // Level အတိုကောက်အမျိုးအစား (ဥပမာ - A1, A2, N5 စသည်ဖြင့်)
            $table->string('tier'); // အဆင့်အမျိုးအစား (ဥပမာ - Beginner, Intermediate စသည်ဖြင့်)
            $table->text('description')->nullable(); // Level အကြောင်း အကျဉ်းချုပ်
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
