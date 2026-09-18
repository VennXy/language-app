<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level;

class LevelController extends Controller
{
    public function selectLevel()
    {
        $levels = Level::all(); 
        
        return view('levels.select', compact('levels'));
    }

    public function showCourses($id)
{
   
    $level = Level::with('lessons')->findOrFail($id);
    
    return view('levels.courses', compact('level'));
}
}