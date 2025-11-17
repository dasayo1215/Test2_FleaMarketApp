@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <h2 class="content-heading">会員登録</h2>
        <form class="content-form-wrapper" action="{{ route('register') }}" method="post">
            @csrf
            <label class="content-form-label" for="name">ユーザー名</label>
            <input class="content-form-input form-control" type="text" name="name" id="name" value="{{ old('name') }}">
            <p class="form-error">
                @error('name')
                    {{ $message }}
                @enderror
            </p>
            <label class="content-form-label" for="email">メールアドレス</label>
            <input class="content-form-input form-control" type="text" name="email" id="email" value="{{ old('email') }}">
            <p class="form-error">
                @error('email')
                    {{ $message }}
                @enderror
            </p>
            <label class="content-form-label" for="password">パスワード</label>
            <input class="content-form-input" type="password" name="password" id="password">
            <p class="form-error">
                @error('password')
                    {{ $message }}
                @enderror
            </p>
            <label class="content-form-label" for="password_confirmation">確認用パスワード</label>
            <input class="content-form-input" type="password" name="password_confirmation" id="password_confirmation">
            <p class="form-error">
                @error('password_confirmation')
                    {{ $message }}
                @enderror
            </p>

            <input class="content-form-btn" type="submit" value="登録する">
        </form>
        <a class="content-btn" href="/login">ログインはこちら</a>
    </div>
@endsection('content')
