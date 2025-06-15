<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Controller;

class EmailVerificationController extends Controller
{
    public function showNotice()
    {
        return view('auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill(); // 認証完了
        return redirect('/mypage/profile');
    }

    public function resendVerificationEmail()
    {
        auth()->user()->sendEmailVerificationNotification();
        return back()->with('message', '登録していただいたメールアドレスに認証メールを送付しました。');
    }
}