<?php

namespace App\Providers;

use App\Services\HeartService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ၁။ Database Migration ပြုလုပ်ရာတွင် String Length Error မတက်စေရန် သတ်မှတ်ခြင်း
        Schema::defaultStringLength(191);

        // ၂။ Laravel Pagination တွင် Bootstrap 5 Style ကို အသုံးပြုရန် သတ်မှတ်ခြင်း
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            if ($user?->role === 'student') {
                $streak = DB::table('users')->where('id', $user->id)->first([
                    'daily_streak',
                    'last_learning_activity_date',
                ]);
                $today = now()->toDateString();

                $view->with([
                    'navbarHeartState' => app(HeartService::class)->state($user),
                    'navbarDailyStreak' => [
                        'days' => (int) ($streak->daily_streak ?? 0),
                        'completed_today' => ($streak->last_learning_activity_date ?? null) === $today,
                    ],
                ]);
            }
        });
    }
}