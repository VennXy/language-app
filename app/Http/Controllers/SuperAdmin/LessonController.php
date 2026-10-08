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
    public function index()
    {
        $lessons = Lesson::with('level')->orderBy('sort_order', 'asc')->paginate(10);
        $levels = Level::orderBy('id', 'asc')->get();
        $categories = Category::all();

        return view('admin.lessons.index', compact('lessons', 'levels', 'categories'));
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