<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
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
    }
}