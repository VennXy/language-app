<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    /**
     * Super Admin Settings landing page. This is intentionally distinct from the
     * regular user settings routes so Super Admins never land on another role's page.
     */
    public function index()
    {
        return view('admin.settings.index');
    }

    /**
     * Reuse the shared Profile edit page. The underlying profile functionality,
     * validation, and authorization are untouched.
     */
    public function profile()
    {
        return view('profile.edit');
    }

    /**
     * Reuse the shared Manage Access page. The underlying access controls,
     * validation, and authorization are untouched.
     */
    public function access()
    {
        return view('profile.access');
    }
}
