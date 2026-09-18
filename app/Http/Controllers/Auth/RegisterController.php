<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RegisterController extends Controller
{
    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Register Form မျက်နှာပြင်ကို ပြသရန်
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Form မှ ပေးပို့လာသော ဒေတာများကို လက်ခံပြီး User အသစ်ဖန်တီးရန် (Laravel UI လိုအပ်ချက်အရ register() ကို အသုံးပြုသည်)
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
             'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student', 
            'target_language' => 'ja', 
            'streak_count' => 0,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect('/');
    }
}