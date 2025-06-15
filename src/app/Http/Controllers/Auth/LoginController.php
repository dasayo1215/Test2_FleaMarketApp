<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm(){
        return view('auth.login');
    }

    //ログイン処理：Fortifyのバリデーションは利用せずフォームリクエストを利用
    public function login(LoginRequest $request) {

        $credentials = $request->only('email', 'password');

        // ログイン機能を利用
        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'password' => 'ログイン情報が登録されていません',
            ])->withInput();
        }

        return redirect('/?page=mylist');
    }
}