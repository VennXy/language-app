<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // Profile 
    public function edit()
    {
        return view('profile.edit');
    }

    // Profile Info and Photo Update Logic
    public function update(Request $request)
    {
        // 1. Validation 
        $request->validate([
            'first_name'    => 'nullable|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();

        // 2. Profile Photo 
        if ($request->hasFile('profile_photo')) {
            
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo = $path;
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->date_of_birth = $request->date_of_birth;
        
        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    // Manage Access
    public function access()
    {
        return view('profile.access');
    }
    public function updateEmail(Request $request)
    {
        $user = $request->user();

    $request->validate([
        'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
    ]);

    $user->update([
        'email' => $request->email,
    ]);

    return back()->with('success', 'Email updated successfully!');
    }
    public function updatePassword(Request $request)
    {
        $request->validate([
        'current_password' => ['required', 'current_password'],
        'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    $request->user()->update([
        'password' => Hash::make($request->password),
    ]);

    return back()->with('success', 'Password updated successfully!');
    }
}