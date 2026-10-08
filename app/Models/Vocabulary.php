<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vocabulary extends Model
{
    use HasFactory;

    protected $table = 'vocabularies';

   protected $fillable = [
    'lesson_id',
    'question_type',
    'question',
    'correct_answer',
    'options',
    'audio',
    'image_url'
];

    /**
     *      
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Bookmark Polymorphic Relation
     */
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }
}