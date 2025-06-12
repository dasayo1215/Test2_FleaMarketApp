<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\JapaneseVerifyEmail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // 名前が入力されていない場合、バリデーションメッセージが表示される
    public function test_name_is_required()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);

        $this->followRedirects($response)
            ->assertSeeText('お名前を入力してください');
    }

    // メールアドレスが入力されていない場合、バリデーションメッセージが表示される
    public function test_email_is_required()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        $this->followRedirects($response)
            ->assertSeeText('メールアドレスを入力してください');
    }

    // パスワードが入力されていない場合、バリデーションメッセージが表示される
    public function test_password_is_required()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        $this->followRedirects($response)
            ->assertSeeText('パスワードを入力してください');
    }

    // パスワードが7文字以下の場合、バリデーションメッセージが表示される
    public function test_password_minimum_length()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '1234567', // 7文字以下
            'password_confirmation' => '1234567',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);

        $this->followRedirects($response)
            ->assertSeeText('パスワードは8文字以上で入力してください');
    }

    // パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
    public function test_password_confirmation_must_match()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password_confirmation' => 'パスワードと一致しません']);

        $this->followRedirects($response)
            ->assertSeeText('パスワードと一致しません');
    }

    // ユーザーが会員登録に成功した場合、認証メール（JapaneseVerifyEmail）が送信されることを確認する。
    public function test_registration_sends_verification_email()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user, 'User was not created');

        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo(
            [$user],
            JapaneseVerifyEmail::class
        );
    }

    // 署名付き認証URLにアクセスしたとき、メール認証が完了し、マイページのプロフィール画面（/mypage/profile）にリダイレクトされることを確認する。
    public function test_user_can_verify_email_and_is_redirected_to_profile()
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user);

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/mypage/profile');

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
