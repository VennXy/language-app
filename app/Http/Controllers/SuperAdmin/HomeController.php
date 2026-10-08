<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin; 
use App\Models\User;  

class HomeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->role === 'super_admin', 403);

        $admins = Admin::all(); 
        $totalUsers = User::count();

        return view('admin.dashboard', compact('admins', 'totalUsers'));
    }
}
