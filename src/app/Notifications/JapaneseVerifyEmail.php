<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class JapaneseVerifyEmail extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('【要確認】メールアドレスの認証を完了してください')
            ->line('ご登録ありがとうございます。以下のボタンをクリックして、メールアドレスの認証を完了してください。')
            ->action('メールアドレスを認証する', $verificationUrl)
            ->line('このメールに心当たりがない場合は、無視してください。');
    }
}
