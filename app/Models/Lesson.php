<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['level_id', 'title', 'type', 'score', 'description'];

    // Level နဲ့ ချိတ်ဆက်ခြင်း (One-to-Many Inverse)
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}