<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamQuestionRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $examId,
        private readonly string $examTitle,
        private readonly int $questionId,
        private readonly string $rejectionReason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(User $notifiable): string
    {
        return 'exam_question_rejected';
    }

    /** @return array<string, int|string> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'exam_question_rejected',
            'exam_id' => $this->examId,
            'exam_title' => $this->examTitle,
            'question_id' => $this->questionId,
            'rejection_reason' => $this->rejectionReason,
            'message' => "Your question was rejected for the {$this->examTitle} exam.",
        ];
    }
}
