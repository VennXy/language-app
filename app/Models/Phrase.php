<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phrase extends Model
{
    use HasFactory;

    protected $table = 'phrases';

    protected $fillable = [
        'lesson_id',
        'phrase',
        'meaning',
        'romaji',
        'audio_text',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}