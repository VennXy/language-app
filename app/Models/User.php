<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function createdExams(): HasMany
    {
        return $this->hasMany(Exam::class, 'created_by');
    }

    public function ownedExamQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'admin_id');
    }

    public function reviewedExamQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'reviewed_by');
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
     'name',
    'first_name',
    'last_name',
    'date_of_birth',
    'email',
    'password',
    'role',
    'target_language',
    'streak_count',
    'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hearts' => 'integer',
            'gems' => 'integer',
            'heart_regeneration_started_at' => 'datetime',
            'daily_streak' => 'integer',
            'last_learning_activity_date' => 'date',
        ];
    }
}
