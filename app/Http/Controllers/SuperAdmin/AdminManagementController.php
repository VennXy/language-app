<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    // Student များကို ဖယ်ထုတ်ပြီး Super Admin နှင့် Admin များကိုသာ ဆွဲထုတ်မည်
    $admins = User::whereIn('role', ['super_admin', 'admin'])
                ->orderByRaw("FIELD(role, 'super_admin', 'admin')")
                ->orderBy('id', 'ASC')
                ->get();
                
    $totalUsers = User::count(); // စနစ်ထဲရှိ User အားလုံး၏ စုစုပေါင်း အရေအတွက်

    return view('admin.dashboard', compact('admins', 'totalUsers'));
}

    /**
     * Store a newly created resource in storage.
     */
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
    'date_of_birth' => $request->date_of_birth,
    'role' => 'admin', // ဤနေရာတွင် admin ဟု သေချာပါစေ
]);
        // POST request ပြီးပါက view ကို တိုက်ရိုက်မခေါ်ဘဲ redirect ပြန်ပေးခြင်းဖြင့် index() မှ data များကို ပို့ပေးမည်
        return redirect()->back()->with('success', 'Admin အသစ်ကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $admin = User::where('role', 'admin')->findOrFail($id);
        $admin->delete();

        return redirect()->back()->with('success', 'Admin ကို အောင်မြင်စွာ ဖယ်ရှားပြီးပါပြီ။');
    }
}