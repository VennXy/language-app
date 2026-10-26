<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'level_id',
        'duration_minutes',
        'status',
        'created_by',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Student-facing label with the trailing exam-number suffix removed.
     *
     * Seeded/admin titles such as "A1 Exam 01" or "A1 Exam 05" are shown to
     * students as a generic "A1 Exam" (the numeric suffix is stripped). This is
     * a display-only transform: the stored title, ids, ordering, relationships,
     * route parameters and progression logic are all unchanged. Numbers that are
     * meaningful elsewhere (question numbering, scores, pagination) are never
     * touched because only the exam title is transformed here.
     */
    public function getDisplayTitleAttribute(): string
    {
        $title = (string) $this->title;

        // Strip a single trailing numeric token (e.g. " 01", " 5") and any
        // surrounding whitespace. Titles without a trailing number are returned
        // unchanged, and an all-numeric title is preserved as a safety net.
        $stripped = rtrim((string) preg_replace('/\s*\d+\s*$/', '', $title));

        return $stripped !== '' ? $stripped : $title;
    }
}

