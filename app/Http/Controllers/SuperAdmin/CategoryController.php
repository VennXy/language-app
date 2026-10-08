<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Level;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
public function index()
{
    
    $categories = Category::with('level')->paginate(10);
    $levels = Level::all();
    
   
    return view('admin.categories.index', compact('categories', 'levels'));
}
public function store(Request $request)
{
    $request->validate([
        'level_id'    => 'required|exists:levels,id',
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    // Category::create([
    //     'level_id'    => $request->level_id,
    //     'title'       => $request->name, 
    //     'description' => $request->description,
    // ]);
     $category = new Category();
     $category->level_id = $request->level_id;
     $category->title = $request->name;
     $category->description = $request->description;
     $category->save();

    return redirect()->route('super.admin.categories.index')->with('success', 'Topic created successfully!');
    // return $request->title;
  }
  public function edit($id)
{
    $category = Category::findOrFail($id);
    $levels = Level::all();
    
    // Edit Page သို့မဟုတ် Data များကို Return ပြန်ရန် (ဒီနေရာတွင် Modal သုံးမလား သို့မဟုတ် Page သီးသန့်ခွဲမလား အပေါ်မူတည်ပါတယ်)
    return view('admin.categories.edit', compact('category', 'levels'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'level_id'    => 'required|exists:levels,id',
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    $category = Category::findOrFail($id);
    $category->level_id = $request->level_id;
    $category->title = $request->name;
    $category->description = $request->description;
    $category->save();

    return redirect()->route('super.admin.categories.index')->with('success', 'Topic updated successfully!');
}

public function destroy($id)
{
    $category = Category::findOrFail($id);
    $category->delete();

    return redirect()->route('super.admin.categories.index')->with('success', 'Topic deleted successfully!');
}

}