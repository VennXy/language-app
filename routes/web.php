<?php

use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\AdminManagementController;
use App\Http\Controllers\LevelController;



// Auth Routes 
Auth::routes();

// User Home Page Route
Route::get('/', function () {
    return view('home');
});

// /home Root Route 
Route::get('/home', function () {
    return redirect('/');
});

// only Login Profile Routes 
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/access', [ProfileController::class, 'access'])->name('profile.access');
    Route::patch('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
});

// Change Email 
Route::patch('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.email.update');

// Change Password 
Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

// Super Admin Routes)
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::post('/super-admin/create-admin', [AdminManagementController::class, 'store'])->name('super.admin.store');
});

Route::get('/select-level', [LevelController::class, 'selectLevel'])->name('select.level');

Route::get('/level/{id}/courses', [LessonController::class, 'index'])->name('level.courses');


// Home Page Route
Route::get('/', function () {
    return view('home');
})->name('home'); // ဒီနေရာမှာ ->name('home') ပါဖို့ လိုပါတယ်

// Kana Table Page Route
Route::get('/kana-table', function () {
    return view('kana');
})->name('kana');