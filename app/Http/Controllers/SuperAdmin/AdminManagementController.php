<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    // Admin စာရင်းပြသခြင်းနှင့် Dashboard ပင်မစာမျက်နှာ
    public function index()
    {
        // Super Admin ဟုတ်မဟုတ် စစ်ဆေးခြင်း
        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        // role က admin ဖြစ်သူများသာ ထုတ်ပြရန်
        $admins = User::where('role', 'admin')->latest()->get();
        $totalUsers = User::count();

        return view('admin.dashboard', compact('admins', 'totalUsers'));
    }

    // Admin အသစ် သိမ်းဆည်းခြင်း
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin', // Admin အဖြစ် သတ်မှတ်ခြင်း
        ]);

        return redirect()->route('super.admin.admins.index')->with('success', 'Admin အသစ်ကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။');
    }

    // Admin ဖျက်သိမ်းခြင်း
    public function destroy($id)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $admin = User::where('role', 'admin')->findOrFail($id);
        $admin->delete();

        return redirect()->route('super.admin.admins.index')->with('success', 'Admin ကို အောင်မြင်စွာ ဖယ်ရှားပြီးပါပြီ။');
    }
}