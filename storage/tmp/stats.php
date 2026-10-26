<?php

$rows = function ($sql) {
    return collect(DB::select($sql));
};

echo "== USERS ==\n";
echo 'role_counts: ' . $rows("SELECT role, COUNT(*) c FROM users GROUP BY role")->toJson() . "\n";
echo 'total: ' . DB::table('users')->count() . "\n";
echo "== LEVELS/CATS ==\n";
echo 'levels: ' . DB::table('levels')->count() . ' cats: ' . DB::table('categories')->count() . "\n";
echo 'categories_by_level: ' . $rows("SELECT level_id, COUNT(*) c FROM categories GROUP BY level_id")->toJson() . "\n";
echo 'lessons: ' . DB::table('lessons')->count() . "\n";
echo 'lesson_types: ' . $rows("SELECT type, COUNT(*) c FROM lessons GROUP BY type")->toJson() . "\n";
echo 'lesson_scores: ' . $rows("SELECT score, COUNT(*) c FROM lessons GROUP BY score")->toJson() . "\n";
echo 'vocabularies: ' . DB::table('vocabularies')->count() . "\n";
echo 'bookmarks: ' . DB::table('bookmarks')->count() . "\n";
echo 'user_mistakes: ' . DB::table('user_question_mistakes')->count() . "\n";
echo 'user_levels: ' . DB::table('user_levels')->count() . "\n";
echo 'user_levels_status: ' . $rows("SELECT status, COUNT(*) c FROM user_levels GROUP BY status")->toJson() . "\n";
echo 'user_progress: ' . DB::table('user_progress')->count() . "\n";
echo 'user_progress_status: ' . $rows("SELECT status, COUNT(*) c FROM user_progress GROUP BY status")->toJson() . "\n";
echo 'exam_attempts: ' . DB::table('exam_attempts')->count() . "\n";
echo 'exam_attempts_status: ' . $rows("SELECT status, COUNT(*) c FROM exam_attempts GROUP BY status")->toJson() . "\n";
echo 'exams: ' . DB::table('exams')->count() . "\n";
echo 'exams_status: ' . $rows("SELECT status, COUNT(*) c FROM exams GROUP BY status")->toJson() . "\n";
echo 'exams_level: ' . $rows("SELECT level_id, COUNT(*) c FROM exams GROUP BY level_id")->toJson() . "\n";
echo 'exam_questions: ' . DB::table('exam_questions')->count() . "\n";
echo 'exam_questions_status: ' . $rows("SELECT status, COUNT(*) c FROM exam_questions GROUP BY status")->toJson() . "\n";
echo 'exam_questions_source: ' . $rows("SELECT source, COUNT(*) c FROM exam_questions GROUP BY source")->toJson() . "\n";
echo 'exam_slots: ' . DB::table('exam_slots')->count() . "\n";
echo "=== END ===\n";
