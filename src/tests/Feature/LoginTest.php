<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    // メールアドレスが入力されていない場合、バリデーションメッセージが表示される
    public function test_email_is_required_for_login()
    {
        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
        $this->followRedirects($response)->assertSeeText('メールアドレスを入力してください');
    }

    // パスワードが入力されていない場合、バリデーションメッセージが表示される
    public function test_password_is_required_for_login()
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
        $this->followRedirects($response)->assertSeeText('パスワードを入力してください');
    }

    // 入力情報が間違っている場合、バリデーションメッセージが表示される
    public function test_login_fails_with_incorrect_credentials()
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'invalidpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password' => 'ログイン情報が登録されていません']);
        $this->followRedirects($response)->assertSeeText('ログイン情報が登録されていません');
    }

    // 正しい情報が入力された場合、ログイン処理が実行される
    public function test_login_success_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/?page=mylist');
        $this->assertAuthenticatedAs($user);
    }
}
