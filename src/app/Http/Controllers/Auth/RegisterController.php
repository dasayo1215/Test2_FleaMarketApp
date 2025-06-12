<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\RegisterRequest;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm(){
        return view('auth.register');
    }

    public function register(RegisterRequest $request){
        $data = $request->validated();

        $existing = User::where('email', $data['email'])->first();

        if ($existing) {
            if ($existing->hasVerifiedEmail()) {
                // すでに認証済み
                return back()->withErrors(['email' => 'このメールアドレスは既に登録されています'])->withInput();
            } else {
                // 認証未完了 → メールを再送する
                $existing->sendEmailVerificationNotification();
                return redirect()
                    ->route('verification.notice')
                    ->with('message', '以前登録されたメールアドレスが未認証のため、認証メールを再送しました。');
            }
        }

        $user = app(CreatesNewUsers::class)->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password']
        ]);

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        return redirect()->route('verification.notice')->with('message', '登録していただいたメールアドレスに認証メールを送付しました。');
    }
}
