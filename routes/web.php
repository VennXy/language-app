<?php
use App\Http\Controllers\SuperAdmin\PhraseController;
use App\Http\Controllers\SuperAdmin\QuizController;
use App\Http\Controllers\SuperAdmin\VocabularyController;
use App\Http\Controllers\SuperAdmin\TrainingContentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\UserTrainingController;
use App\Http\Controllers\StudentLearningController;
use App\Http\Controllers\StudentLeaderboardController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\SuperAdmin\HomeController;
use App\Http\Controllers\SuperAdmin\AdminManagementController;
use App\Http\Controllers\SuperAdmin\LessonController;
use App\Http\Controllers\SuperAdmin\CategoryController;
use App\Http\Controllers\SuperAdmin\ExamController;
use App\Http\Controllers\SuperAdmin\ExamQuestionReviewController;
use App\Http\Controllers\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Admin\ExamQuestionController;
use App\Http\Controllers\UserExamController;

// Auth & Public Routes
Auth::routes();

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/home', function () {
    return redirect('/');
});

// Super Admin Routes Group
Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->name('super.admin.')->group(function () {
    
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    // Super Admin Settings (Super-Admin-specific destination, separate from user settings)
    Route::get('/settings', [\App\Http\Controllers\SuperAdmin\SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/profile', [\App\Http\Controllers\SuperAdmin\SettingsController::class, 'profile'])->name('settings.profile');
    Route::get('/settings/access', [\App\Http\Controllers\SuperAdmin\SettingsController::class, 'access'])->name('settings.access');

    // Lesson Management Routes
    Route::get('/lessons', [LessonController::class, 'index'])->name('lessons.index');
    Route::post('/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show'); 
    Route::put('/lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');   
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy'); 

    // Super Admin Training Content Management
    Route::get('/training', [TrainingContentController::class, 'index'])->name('training.index');
    Route::post('/training/levels/{level}/lessons', [TrainingContentController::class, 'storeLesson'])->name('training.lessons.store');
    Route::get('/training/lessons/{lesson}', [TrainingContentController::class, 'show'])->name('training.lessons.show');
    Route::patch('/training/lessons/{lesson}', [TrainingContentController::class, 'updateLesson'])->name('training.lessons.update');
    Route::post('/training/lessons/{lesson}/items', [TrainingContentController::class, 'storeItem'])->name('training.items.store');
    Route::put('/training/lessons/{lesson}/items/{item}', [TrainingContentController::class, 'updateItem'])->name('training.items.update');
    Route::delete('/training/lessons/{lesson}/items/{item}', [TrainingContentController::class, 'destroyItem'])->name('training.items.destroy');
    Route::post('/training/lessons/{lesson}/questions', [TrainingContentController::class, 'storeQuestion'])->name('training.questions.store');
    Route::get('/training/lessons/{lesson}/questions/{question}/edit', [TrainingContentController::class, 'editQuestion'])->name('training.questions.edit');
    Route::put('/training/lessons/{lesson}/questions/{question}', [TrainingContentController::class, 'updateQuestion'])->name('training.questions.update');
    Route::delete('/training/lessons/{lesson}/questions/{question}', [TrainingContentController::class, 'destroyQuestion'])->name('training.questions.destroy');

    // Vocabulary Store, Update & Destroy Routes
    Route::post('/lessons/{lesson}/vocabularies', [VocabularyController::class, 'store'])->name('lessons.vocabularies.store');
    Route::put('/vocabularies/{id}', [VocabularyController::class, 'update'])->name('vocabularies.update');
    Route::delete('/vocabularies/{vocabulary}', [VocabularyController::class, 'destroy'])->name('vocabularies.destroy');

    // Phrases & Quizzes Routes
    Route::post('/lessons/{lesson}/phrases', [PhraseController::class, 'store'])->name('lessons.phrases.store');
    Route::delete('/phrases/{phrase}', [PhraseController::class, 'destroy'])->name('phrases.destroy');

    Route::post('/lessons/{lesson}/quizzes', [QuizController::class, 'store'])->name('lessons.quizzes.store');
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
    
    // Categories Routes
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');    
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update'); 
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Exam Definition Management Routes
    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
    Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/{exam}/edit', [ExamController::class, 'edit'])->name('exams.edit');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::put('/exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
    Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');
    Route::post('/exams/{exam}/close', [ExamController::class, 'close'])->name('exams.close');
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');

    // Exam Question Review Routes
    Route::get('/exam-question-review', [ExamQuestionReviewController::class, 'exams'])->name('exam.questions.exams');
    Route::get('/exams/{exam}/question-review', [ExamQuestionReviewController::class, 'index'])->name('exam.questions.index');
    Route::post('/exam-questions/{question}/approve', [ExamQuestionReviewController::class, 'approve'])->name('exam.questions.approve');
    Route::post('/exam-questions/{question}/reject', [ExamQuestionReviewController::class, 'reject'])->name('exam.questions.reject');
    // Super Admin submission of their OWN draft questions (mirrors the Admin
    // eligibility/ownership rules; review + approval stays a separate step).
    Route::post('/exam-questions/{question}/submit', [\App\Http\Controllers\SuperAdmin\ExamQuestionController::class, 'submit'])->name('exam.questions.submit');


    // Admin Management Routes
Route::get('/admins', [AdminManagementController::class, 'index'])->name('admins.index');
Route::post('/admins', [AdminManagementController::class, 'store'])->name('admins.store');
Route::get('/admins/{id}', [AdminManagementController::class, 'show'])->name('admins.show');
Route::put('/admins/{admin}', [AdminManagementController::class, 'update'])->name('admins.update');
Route::delete('/admins/{id}', [AdminManagementController::class, 'destroy'])->name('admins.destroy');
});

Route::get('/kana-table', function () {
    return view('kana');
})->name('kana');

Route::middleware('auth')->group(function () {
    Route::get('/student/exams', [UserExamController::class, 'index'])->name('student.exams.index');
    Route::get('/student/exams/{exam}', [UserExamController::class, 'show'])->name('student.exams.show');
    Route::post('/student/exams/{exam}/start', [UserExamController::class, 'start'])->name('student.exams.start');
    Route::get('/student/exam-attempts/{attempt}', [UserExamController::class, 'attempt'])->name('student.exams.attempts.show');
    Route::post('/student/exam-attempts/{attempt}/answers', [UserExamController::class, 'answer'])->name('student.exams.attempts.answer');
    Route::post('/student/exam-attempts/{attempt}/submit', [UserExamController::class, 'submit'])->name('student.exams.attempts.submit');
    Route::get('/student/exam-attempts/{attempt}/result', [UserExamController::class, 'result'])->name('student.exams.attempts.result');

    Route::get('/select-level', [LevelController::class, 'selectLevel'])->name('select.level');
    Route::get('/levels/{id}/courses', [LevelController::class, 'courses'])->name('levels.courses');
    Route::get('/lessons/{id}/training', [UserTrainingController::class, 'showTraining'])->name('user.training');
    Route::post('/lessons/{lesson}/answers', [UserTrainingController::class, 'submitAnswer'])->name('user.lesson.answer');
    Route::post('/lessons/{lesson}/skip', [UserTrainingController::class, 'skipQuestion'])->name('user.lesson.skip');
    Route::post('/lessons/{lesson}/learning-items/{itemId}/complete', [UserTrainingController::class, 'completeLearningItem'])
        ->name('user.lesson.learning.complete');
    Route::get('/student/hearts', [UserTrainingController::class, 'heartStatus'])->name('student.hearts.status');
    Route::post('/student/hearts/refill', [UserTrainingController::class, 'refillHeartsWithGems'])
        ->name('student.hearts.refill');
    Route::get('/bookmarks', [StudentLearningController::class, 'bookmarks'])->name('student.bookmarks.index');
    Route::post('/bookmarks/{vocabulary}/toggle', [StudentLearningController::class, 'toggleBookmark'])
        ->name('student.bookmarks.toggle');
    Route::get('/review-mistakes', [StudentLearningController::class, 'mistakes'])->name('student.mistakes.index');
    Route::post('/review-mistakes/{vocabulary}', [StudentLearningController::class, 'submitMistakeReview'])
        ->name('student.mistakes.answer');
    Route::get('/leaderboard', [StudentLeaderboardController::class, 'index'])
        ->middleware('role:student')
        ->name('student.leaderboard.index');
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])
        ->middleware('role:student')
        ->name('student.dashboard');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminManagementController::class, 'dashboard'])->name('dashboard');
    Route::get('/exams', [AdminExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/{exam}/questions', [ExamQuestionController::class, 'index'])->name('exams.questions.index');
    Route::get('/exams/{exam}/questions/create', [ExamQuestionController::class, 'create'])->name('exams.questions.create');
    Route::post('/exams/{exam}/questions', [ExamQuestionController::class, 'store'])->name('exams.questions.store');
    Route::get('/exam-questions/{question}/edit', [ExamQuestionController::class, 'edit'])->name('exams.questions.edit');
    Route::put('/exam-questions/{question}', [ExamQuestionController::class, 'update'])->name('exams.questions.update');
    Route::delete('/exam-questions/{question}', [ExamQuestionController::class, 'destroy'])->name('exams.questions.destroy');
    Route::post('/exam-questions/{question}/submit', [ExamQuestionController::class, 'submit'])->name('exams.questions.submit');
});


Route::middleware('auth')->group(function () {
    // Profile Edit Route
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    
    Route::get('/profile/access', [ProfileController::class, 'access'])->name('profile.access');
    
    // Update Profile / Password Routes
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.email.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});
Route::post('/lessons/{lesson}/vocabularies/import', [VocabularyController::class, 'importData'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('super.admin.lessons.vocabularies.import');
