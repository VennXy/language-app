<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * Super Admin Dashboard အတွက် (Overview & Stats များပြရန်)
     */
   public function dashboard()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        // Admin စာရင်းများကို ရှာဖွေခြင်း
        $adminId = auth()->id();

        // dashboard.blade.php သို့ $admins ကို ထည့်သွင်း ပို့ပေးခြင်း
        return view('admin.dashboard', [
            'dashboardForAdmin' => true,
            'myQuestionsCount' => ExamQuestion::where('admin_id', $adminId)->count(),
            'myDraftQuestionsCount' => ExamQuestion::where('admin_id', $adminId)->where('status', 'draft')->count(),
            'myPendingQuestionsCount' => ExamQuestion::where('admin_id', $adminId)->where('status', 'pending')->count(),
        ]);
    }
    /**
     * Administrators List စာရင်းကို သီးသန့်ပြသရန်
     */
    public function index()
    {
        $this->authorizeRole('super_admin');

        $admins = User::whereIn('role', ['super_admin', 'admin'])
                    ->orderByRaw("CASE role WHEN 'super_admin' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
                    ->orderBy('id', 'ASC')
                    ->get();
                    
        $totalUsers = User::count();
        return view('admin.admin-management', compact('admins', 'totalUsers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeRole('super_admin');

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
            'role' => 'admin',
        ]);

        return redirect()->back()->with('success', 'New Admin Successfully Created.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->authorizeRole('super_admin');

        $admin = User::where('role', 'admin')->findOrFail($id);
        $admin->delete();

        return redirect()->back()->with('success', 'Removed Admin Successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $this->authorizeRole('super_admin');

        $admin = User::whereIn('role', ['super_admin', 'admin'])->findOrFail($id);
        
        return view('super-admin.show', compact('admin'));
    }
    public function update(Request $request, $id)
{
    $this->authorizeRole('super_admin');

    // 1. Data များကို validate လုပ်ခြင်း
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $id,
        'date_of_birth' => 'nullable|date',
        'password' => 'nullable|min:8',
    ]);

    // 2. Admin အချက်အလက်ကို ရှာပြီး update လုပ်ခြင်း
    $admin = User::where('role', 'admin')->findOrFail($id);
    
    $admin->name = $request->name;
    $admin->email = $request->email;
    $admin->date_of_birth = $request->date_of_birth;

    // အကယ်၍ password အသစ်ထည့်မှသာ ပြောင်းလဲမည်
    if ($request->filled('password')) {
        $admin->password = Hash::make($request->password);
    }

    $admin->save();

    return redirect()->back()->with('success', 'Admin updated successfully.');
}

    private function authorizeRole(string $role): void
    {
        abort_unless(auth()->user()?->role === $role, 403);
    }
}
