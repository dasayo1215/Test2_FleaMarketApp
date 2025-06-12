<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages() {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスは「ユーザー名@ドメイン」形式で入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
        ];
    }

    public function withValidator($validator) {
        $validator->after(function ($validator) {
            // 入力に他のエラーがあれば、ここで終了
            if ($validator->errors()->any()) {
                return;
            }

            // 認証試行（成功したらセッションにログインされる）
            if (!Auth::attempt([
                'email' => $this->input('email'),
                'password' => $this->input('password'),
            ])) {
                $validator->errors()->add('password', 'ログイン情報が登録されていません');
            }
        });
    }
}
