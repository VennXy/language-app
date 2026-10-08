<?php

namespace App\Services;

class TrainingAnswerValidator
{
    public function matches(string $answer, string $correctAnswer, ?string $questionType): bool
    {
        $answer = mb_strtolower(trim($answer));
        $correctAnswer = mb_strtolower(trim($correctAnswer));

        if ($questionType === 'phrase_ordering') {
            $answer = preg_replace('/\s+/u', '', $answer) ?? $answer;
            $correctAnswer = preg_replace('/\s+/u', '', $correctAnswer) ?? $correctAnswer;
        } elseif ($questionType === 'translation_tiles') {
            $answer = preg_replace('/\s+/u', ' ', $answer) ?? $answer;
            $correctAnswer = preg_replace('/\s+/u', ' ', $correctAnswer) ?? $correctAnswer;
        }

        return $answer === $correctAnswer;
    }
}
