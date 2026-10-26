<?php

return [
    'heart_refill_gem_cost' => (int) env('HEART_REFILL_GEM_COST', 50),
    'learning_completion_gem_reward' => (int) env('LEARNING_COMPLETION_GEM_REWARD', 50),

    /*
    |--------------------------------------------------------------------------
    | Exam testing mode
    |--------------------------------------------------------------------------
    | When true, Super Admin may publish an exam with fewer than the
    | production requirement of 25 approved questions (minimum 1).
    | Student attempts then snapshot whatever approved questions exist.
    | Set EXAM_TESTING_MODE=false (or 25 in EXAM_MINIMUM_APPROVED_QUESTIONS)
    | to restore the strict production rule. No other flow changes.
    */
    'exam_testing_mode' => filter_var(env('EXAM_TESTING_MODE', true), FILTER_VALIDATE_BOOLEAN),
    'exam_minimum_approved_questions' => (int) env('EXAM_MINIMUM_APPROVED_QUESTIONS', 1),
    'exam_production_approved_questions' => 25,

    /*
    |--------------------------------------------------------------------------
    | Questions shown per student attempt
    |--------------------------------------------------------------------------
    | Each attempt randomly selects up to this many approved, valid questions
    | from the exam's eligible bank (fewer only when the bank is smaller).
    | Progress, scoring, and result denominators use the actual number of
    | questions in the attempt, not this target.
    */
    'exam_questions_per_attempt' => (int) env('EXAM_QUESTIONS_PER_ATTEMPT', 3),
];
