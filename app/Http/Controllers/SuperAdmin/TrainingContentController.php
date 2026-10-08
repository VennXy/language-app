<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Vocabulary;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingContentController extends Controller
{
    private const LEVEL_ORDER = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    private const QUESTION_TYPES = [
        'image_selection',
        'word_selection',
        'translation_tiles',
        'phrase_ordering',
    ];

    private const LEARNING_STAGES = [
        'picture_recognition',
        'word_recognition',
        'recall',
        'listening',
        'usage',
        'review',
    ];

    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();
        $levels = $this->levels();
        $selectedLevel = $levels->firstWhere('id', (int) $request->query('level_id')) ?? $levels->first();
        $lessons = $selectedLevel
            ? $selectedLevel->lessons()->with('category')->withCount('vocabularies')->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        $itemCounts = DB::table('lesson_vocabulary_items')
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->select('lesson_id')
            ->selectRaw('COUNT(*) AS aggregate_count')
            ->groupBy('lesson_id')
            ->pluck('aggregate_count', 'lesson_id');
        $categories = $selectedLevel
            ? Category::where('level_id', $selectedLevel->id)->orderBy('title')->get()
            : collect();

        return view('admin.training.index', compact('levels', 'selectedLevel', 'lessons', 'itemCounts', 'categories'));
    }

    public function storeLesson(Request $request, Level $level): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('level_id', $level->id)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $level->lessons()->create([
            ...$validated,
            'type' => 'training',
            'score' => 0,
        ]);

        return redirect()->route('super.admin.training.index', ['level_id' => $level->id])
            ->with('success', 'Lesson created. Add its learning content before students begin it.');
    }

    public function updateLesson(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);
        $lesson->update($validated);

        return redirect()->route('super.admin.training.lessons.show', $lesson)
            ->with('success', 'Lesson details and display order updated.');
    }

    public function show(Lesson $lesson): View
    {
        $this->authorizeSuperAdmin();
        $lesson->load(['level', 'category']);
        abort_unless($lesson->level && in_array($lesson->level->level, self::LEVEL_ORDER, true), 404);

        $items = DB::table('lesson_vocabulary_items')
            ->where('lesson_id', $lesson->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $questions = Vocabulary::query()
            ->where('lesson_id', $lesson->id)
            ->orderBy('id')
            ->get();

        $questionLinks = DB::table('lesson_vocabulary_questions as lvq')
            ->join('lesson_vocabulary_items as lvi', 'lvi.id', '=', 'lvq.lesson_vocabulary_item_id')
            ->where('lvi.lesson_id', $lesson->id)
            ->whereIn('lvq.vocabulary_question_id', $questions->pluck('id'))
            ->orderBy('lvi.sort_order')
            ->orderBy('lvq.stage_order')
            ->get([
                'lvq.lesson_vocabulary_item_id',
                'lvq.vocabulary_question_id',
                'lvq.stage',
                'lvq.stage_order',
                'lvi.japanese as item_japanese',
            ])
            ->groupBy('vocabulary_question_id');

        return view('admin.training.lesson', compact('lesson', 'items', 'questions', 'questionLinks'));
    }

    public function storeItem(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $validated = $this->validateItem($request);
        $media = $this->storeItemMedia($request);

        DB::table('lesson_vocabulary_items')->insert([
            'lesson_id' => $lesson->id,
            'japanese' => $validated['japanese'],
            'reading' => $validated['reading'],
            'english' => $validated['english'],
            'image_path' => $media['image_path'],
            'audio_path' => $media['audio_path'],
            'sort_order' => $validated['sort_order'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Vocabulary / learning item added.');
    }

    public function updateItem(Request $request, Lesson $lesson, int $item): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $row = DB::table('lesson_vocabulary_items')->where('lesson_id', $lesson->id)->where('id', $item)->first();
        abort_unless($row, 404);

        $validated = $this->validateItem($request);
        $media = $this->storeItemMedia($request);
        $imagePath = $media['image_path'] ?? $row->image_path;
        $audioPath = $media['audio_path'] ?? $row->audio_path;

        DB::table('lesson_vocabulary_items')->where('id', $item)->update([
            'japanese' => $validated['japanese'],
            'reading' => $validated['reading'],
            'english' => $validated['english'],
            'image_path' => $imagePath,
            'audio_path' => $audioPath,
            'sort_order' => $validated['sort_order'],
            'updated_at' => now(),
        ]);

        if ($media['image_path'] && $row->image_path) {
            $this->deleteFileIfUnused($row->image_path, 'lesson_vocabulary_items', 'image_path');
        }
        if ($media['audio_path'] && $row->audio_path) {
            $this->deleteFileIfUnused($row->audio_path, 'lesson_vocabulary_items', 'audio_path');
        }

        return back()->with('success', 'Learning item updated.');
    }

    public function destroyItem(Lesson $lesson, int $item): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $row = DB::table('lesson_vocabulary_items')->where('lesson_id', $lesson->id)->where('id', $item)->first();
        abort_unless($row, 404);

        DB::transaction(function () use ($lesson, $item): void {
            DB::table('lesson_vocabulary_questions')->where('lesson_vocabulary_item_id', $item)->delete();
            DB::table('lesson_vocabulary_items')->where('lesson_id', $lesson->id)->where('id', $item)->delete();
        });

        if ($row->image_path) {
            $this->deleteFileIfUnused($row->image_path, 'lesson_vocabulary_items', 'image_path');
        }
        if ($row->audio_path) {
            $this->deleteFileIfUnused($row->audio_path, 'lesson_vocabulary_items', 'audio_path');
        }

        return back()->with('success', 'Learning item removed. Its linked question records were kept and unlinked.');
    }

    public function storeQuestion(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $validated = $this->validateQuestion($request, $lesson);
        DB::transaction(function () use ($request, $lesson, $validated): void {
            $question = $this->saveQuestionMediaAndRecord($request, $lesson, $validated);
            DB::table('lesson_vocabulary_questions')->insert([
                'lesson_vocabulary_item_id' => $validated['learning_item_id'],
                'vocabulary_question_id' => $question->id,
                'stage' => $validated['stage'],
                'stage_order' => $validated['stage_order'],
            ]);
        });

        return back()->with('success', 'Training question created and linked to its learning item.');
    }

    public function editQuestion(Request $request, Lesson $lesson, Vocabulary $question): View
    {
        $this->authorizeSuperAdmin();
        $this->assertQuestionBelongsToLesson($question, $lesson);
        $link = null;
        if ($request->filled('link_item_id') && $request->filled('link_stage')) {
            $link = DB::table('lesson_vocabulary_questions as lvq')
                ->join('lesson_vocabulary_items as lvi', 'lvi.id', '=', 'lvq.lesson_vocabulary_item_id')
                ->where('lvi.lesson_id', $lesson->id)
                ->where('lvq.vocabulary_question_id', $question->id)
                ->where('lvq.lesson_vocabulary_item_id', $request->integer('link_item_id'))
                ->where('lvq.stage', $request->string('link_stage')->toString())
                ->first(['lvq.lesson_vocabulary_item_id', 'lvq.stage', 'lvq.stage_order']);
            abort_unless($link, 404);
        }

        $items = DB::table('lesson_vocabulary_items')->where('lesson_id', $lesson->id)->orderBy('sort_order')->orderBy('id')->get();
        return view('admin.training.question-form', compact('lesson', 'question', 'link', 'items'));
    }

    public function updateQuestion(Request $request, Lesson $lesson, Vocabulary $question): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $this->assertQuestionBelongsToLesson($question, $lesson);
        $validated = $this->validateQuestion($request, $lesson);
        $currentItemId = $request->integer('current_item_id');
        $currentStage = $request->input('current_stage');

        if ($currentItemId) {
            $existingLink = DB::table('lesson_vocabulary_questions as lvq')
                ->join('lesson_vocabulary_items as lvi', 'lvi.id', '=', 'lvq.lesson_vocabulary_item_id')
                ->where('lvi.lesson_id', $lesson->id)
                ->where('lvq.lesson_vocabulary_item_id', $currentItemId)
                ->where('lvq.vocabulary_question_id', $question->id)
                ->where('lvq.stage', $currentStage)
                ->first();
            abort_unless($existingLink, 404);
        }

        $question->question_type = $validated['question_type'];
        $question->question = $validated['question'];
        $question->correct_answer = $validated['correct_answer'];
        $question->options = implode(',', $validated['parsed_options']);
        $linkedItem = DB::table('lesson_vocabulary_items')->where('id', $validated['learning_item_id'])->first();
        $question->word = $linkedItem->japanese;
        $question->reading = $linkedItem->reading;
        $question->meaning = $linkedItem->english;

        $oldAudio = $question->audio;
        $oldImages = $this->imagePaths($question->image_url);
        $this->applyQuestionMedia($request, $question, $validated['parsed_options']);

        DB::transaction(function () use ($question, $validated, $currentItemId, $currentStage): void {
            $question->save();

            $newItemId = (int) $validated['learning_item_id'];
            $newStage = $validated['stage'];
            $duplicate = DB::table('lesson_vocabulary_questions')
                ->where('lesson_vocabulary_item_id', $newItemId)
                ->where('vocabulary_question_id', $question->id)
                ->where('stage', $newStage)
                ->when($currentItemId, fn (Builder $query) => $query
                    ->where(function (Builder $query) use ($currentItemId, $currentStage): void {
                        $query->where('lesson_vocabulary_item_id', '<>', $currentItemId)
                            ->orWhere('stage', '<>', $currentStage);
                    }))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages(['learning_item_id' => 'This question is already linked to that item and stage.']);
            }

            if ($currentItemId && ($currentItemId !== $newItemId || $currentStage !== $newStage)) {
                DB::table('lesson_vocabulary_questions')
                    ->where('lesson_vocabulary_item_id', $currentItemId)
                    ->where('vocabulary_question_id', $question->id)
                    ->where('stage', $currentStage)
                    ->delete();
            }

            DB::table('lesson_vocabulary_questions')->updateOrInsert(
                [
                    'lesson_vocabulary_item_id' => $newItemId,
                    'vocabulary_question_id' => $question->id,
                    'stage' => $newStage,
                ],
                ['stage_order' => $validated['stage_order']]
            );
        });

        if ($request->hasFile('audio') && $oldAudio) {
            $this->deleteFileIfUnused($oldAudio, 'vocabularies', 'audio', $question->id);
        }
        foreach (array_diff($oldImages, $this->imagePaths($question->image_url)) as $path) {
            $this->deleteImageIfUnused($path, $question->id);
        }

        return redirect()->route('super.admin.training.lessons.show', $lesson)->with('success', 'Training question and its learning-stage link were updated.');
    }

    public function destroyQuestion(Lesson $lesson, Vocabulary $question): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $this->assertQuestionBelongsToLesson($question, $lesson);
        $audio = $question->audio;
        $images = $this->imagePaths($question->image_url);

        DB::transaction(function () use ($question): void {
            DB::table('lesson_vocabulary_questions')->where('vocabulary_question_id', $question->id)->delete();
            $question->delete();
        });

        if ($audio) {
            $this->deleteFileIfUnused($audio, 'vocabularies', 'audio');
        }
        foreach ($images as $path) {
            $this->deleteImageIfUnused($path);
        }

        return back()->with('success', 'Training question and its links removed.');
    }

    private function levels()
    {
        $order = array_flip(self::LEVEL_ORDER);
        return Level::query()->whereIn('level', self::LEVEL_ORDER)->get()
            ->sortBy(fn (Level $level) => $order[$level->level] ?? count($order))
            ->values();
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'japanese' => ['required', 'string', 'max:100', 'regex:/^[^\p{Han}]*$/u'],
            'reading' => ['required', 'string', 'max:100', 'regex:/^[^\p{Han}]*$/u'],
            'english' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,aac', 'max:10240'],
        ]);
    }

    private function storeItemMedia(Request $request): array
    {
        return [
            'image_path' => $request->file('image')?->store('learning-items/images', 'public'),
            'audio_path' => $request->file('audio')?->store('learning-items/audio', 'public'),
        ];
    }

    private function validateQuestion(Request $request, Lesson $lesson): array
    {
        $validated = $request->validate([
            'question_type' => ['required', Rule::in(self::QUESTION_TYPES)],
            'learning_item_id' => ['required', 'integer', Rule::exists('lesson_vocabulary_items', 'id')->where('lesson_id', $lesson->id)],
            'stage' => ['required', Rule::in(self::LEARNING_STAGES)],
            'stage_order' => ['required', 'integer', 'min:0'],
            'question' => ['required', 'string', 'max:2000'],
            'correct_answer' => ['required', 'string', 'max:2000'],
            'options' => ['required', 'string', 'max:5000'],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,aac', 'max:10240'],
            'option_images' => ['nullable', 'array'],
            'option_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'current_item_id' => ['nullable', 'integer', 'min:1'],
            'current_stage' => ['nullable', Rule::in(self::LEARNING_STAGES)],
        ]);

        $options = collect(preg_split('/[\r\n,]+/', $validated['options']) ?: [])
            ->map(fn (string $option) => trim($option))
            ->filter()
            ->unique()
            ->values();

        if ($options->count() < 2) {
            throw ValidationException::withMessages(['options' => 'Enter at least two distinct answer options or tiles.']);
        }

        if (in_array($validated['question_type'], ['image_selection', 'word_selection'], true)
            && !$options->contains(fn (string $option) => mb_strtolower($option) === mb_strtolower(trim($validated['correct_answer'])))) {
            throw ValidationException::withMessages(['correct_answer' => 'For this question type, the correct answer must match one of the listed options.']);
        }

        return [...$validated, 'parsed_options' => $options->all()];
    }

    private function saveQuestionMediaAndRecord(Request $request, Lesson $lesson, array $validated): Vocabulary
    {
        $question = new Vocabulary([
            'lesson_id' => $lesson->id,
            'question_type' => $validated['question_type'],
            'question' => $validated['question'],
            'correct_answer' => $validated['correct_answer'],
            'options' => implode(',', $validated['parsed_options']),
        ]);
        $linkedItem = DB::table('lesson_vocabulary_items')->where('id', $validated['learning_item_id'])->firstOrFail();
        $question->word = $linkedItem->japanese;
        $question->reading = $linkedItem->reading;
        $question->meaning = $linkedItem->english;
        $this->applyQuestionMedia($request, $question, $validated['parsed_options']);
        $question->save();

        return $question;
    }

    private function applyQuestionMedia(Request $request, Vocabulary $question, array $options): void
    {
        $images = $this->imageMap($question->image_url, $options);
        foreach ($request->file('option_images', []) as $index => $file) {
            if ($file && isset($options[$index])) {
                $images[$options[$index]] = $file->store('vocabularies', 'public');
            }
        }

        $question->image_url = $images ? json_encode($images, JSON_UNESCAPED_UNICODE) : null;
        if ($request->hasFile('audio')) {
            $question->audio = $request->file('audio')->store('audios', 'public');
        }
    }

    private function imageMap(?string $imageUrl, array $options): array
    {
        $decoded = json_decode($imageUrl ?? '', true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_intersect_key($decoded, array_flip($options));
    }

    private function imagePaths(?string $imageUrl): array
    {
        $decoded = json_decode($imageUrl ?? '', true);
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    private function assertQuestionBelongsToLesson(Vocabulary $question, Lesson $lesson): void
    {
        abort_unless((int) $question->lesson_id === (int) $lesson->id, 404);
    }

    private function deleteFileIfUnused(string $path, string $table, string $column, ?int $excludeId = null): void
    {
        if (!$column) {
            return;
        }

        $query = DB::table($table)->where($column, $path);
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        if (!$query->exists()) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteImageIfUnused(string $path, ?int $excludeQuestionId = null): void
    {
        $stillUsed = Vocabulary::query()
            ->when($excludeQuestionId, fn ($query) => $query->where('id', '<>', $excludeQuestionId))
            ->pluck('image_url')
            ->contains(fn (?string $imageUrl) => in_array($path, $this->imagePaths($imageUrl), true));

        if (!$stillUsed) {
            Storage::disk('public')->delete($path);
        }
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);
    }
}
