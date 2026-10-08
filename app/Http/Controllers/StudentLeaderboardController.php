<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StudentLeaderboardController extends Controller
{
    private const LEADERBOARD_LIMIT = 20;

    public function index()
    {
        abort_unless(auth()->user()?->role === 'student', 403);

        $students = DB::table('users')
            ->where('role', 'student')
            ->orderByDesc('streak_count')
            ->orderBy('id')
            ->limit(self::LEADERBOARD_LIMIT)
            ->get(['id', 'name', 'streak_count']);

        $myPoints = (int) auth()->user()->streak_count;

        $myRank = 1 + DB::table('users')
            ->where('role', 'student')
            ->where('streak_count', '>', $myPoints)
            ->count();

        return view('user.leaderboard', [
            'students' => $students,
            'myRank' => $myRank,
            'myPoints' => $myPoints,
            'inTopList' => $students->contains(fn ($student) => (int) $student->id === (int) auth()->id()),
        ]);
    }
}
