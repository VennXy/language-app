<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Vocabulary;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        // Only an explicit asc/desc is honoured; anything else falls back to the default ordering.
        $direction = in_array($request->query('sort'), ['asc', 'desc'], true) ? $request->query('sort') : null;
        $search = trim((string) $request->query('q', ''));

        $query = Lesson::query()->with('level');

        if ($search !== '') {
            // MySQL's default collation makes LIKE a case-insensitive partial match.
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($direction !== null) {
            // Rank by the real level code (A1..C2), never by the display name, then keep a
            // stable, predictable order inside each level. The CASE ranking list is hard-coded
            // and $direction is whitelisted above, so no arbitrary user input reaches the ORDER
            // BY. CASE (rather than MySQL's FIELD) keeps this portable across MySQL and SQLite.
            $rank = "CASE lv.level "
                . "WHEN 'A1' THEN 1 WHEN 'A2' THEN 2 WHEN 'B1' THEN 3 "
                . "WHEN 'B2' THEN 4 WHEN 'C1' THEN 5 WHEN 'C2' THEN 6 ELSE 7 END";
            $query->orderByRaw(
                "(SELECT {$rank} FROM levels lv WHERE lv.id = lessons.level_id) {$direction}"
            )
                ->orderBy('lessons.sort_order', 'asc')
                ->orderBy('lessons.title', 'asc');
        } else {
            // Preserve the existing default ordering.
            $query->orderBy('lessons.sort_order', 'asc');
        }

        $lessons = $query->paginate(10)->withQueryString();
        $levels = Level::orderBy('id', 'asc')->get();
        $categories = Category::all();

        return view('admin.lessons.index', compact('lessons', 'levels', 'categories', 'direction', 'search'));
    }

 public function store(Request $request, $lessonId)
{

    $request->validate([
        'question_type'  => 'required|string', 
        'question'       => 'required|string', 
        'correct_answer' => 'required|string',
        'options'        => 'nullable|string',
    ]);

    $vocabulary = new Vocabulary();
    $vocabulary->lesson_id = $lessonId; 
    $vocabulary->question_type = $request->question_type;
    $vocabulary->question = $request->question;
    $vocabulary->correct_answer = $request->correct_answer;
    $vocabulary->options = $request->options;

    if ($request->hasFile('audio')) {
        $vocabulary->audio = $request->file('audio')->store('audios', 'public');
    }

    $vocabulary->save();

    return redirect()->back()->with('success', 'Training question added successfully!');
}
public function destroy(Lesson $lesson)
{
    $lesson->delete();

    return redirect()->route('super.admin.lessons.index');
}

  public function edit(Lesson $lesson)
    {
        $levels = Level::orderBy('id', 'asc')->get();
        $categories = Category::all(); 

        return view('admin.lessons.edit', compact('lesson', 'levels', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $validatedData = $request->validate([
            'title'       => 'required|string|max:255',
            'level_id'    => 'required|exists:levels,id',
            'score'       => 'nullable|integer',
            'sort_order'  => 'nullable|integer',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        $lesson->update([ 
            'title'       => $validatedData['title'],
            'level_id'    => $validatedData['level_id'],
            'score'       => $validatedData['score'] ?? 0,  
            'sort_order'  => $validatedData['sort_order'] ?? 0,
            'description' => $validatedData['description'],
            'category_id' => $validatedData['category_id'],
        ]);

        return redirect()->route('super.admin.lessons.index')
                         ->with('success', 'Lesson Update Successfully.');
    }public function show($id)
{
    $lesson = Lesson::with('vocabularies')->findOrFail($id);

    return view('admin.lessons.show', compact('lesson'));
}
}