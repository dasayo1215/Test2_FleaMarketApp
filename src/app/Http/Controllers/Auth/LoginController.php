<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function showLoginForm(){
        return view('auth.login');
    }

    public function login(LoginRequest $request) {
        return redirect('/?page=mylist');
    }
}