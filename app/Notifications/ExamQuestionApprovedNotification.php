<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamQuestionApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $examId,
        private readonly string $examTitle,
        private readonly int $questionId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(User $notifiable): string
    {
        return 'exam_question_approved';
    }

    /** @return array<string, int|string> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'exam_question_approved',
            'exam_id' => $this->examId,
            'exam_title' => $this->examTitle,
            'question_id' => $this->questionId,
            'message' => "Your question has been approved for the {$this->examTitle} exam.",
        ];
    }
}
