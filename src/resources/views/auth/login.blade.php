@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <h2 class="content-heading">ログイン</h2>
        <form class="content-form-form" action="/login" method="post">
            @csrf
            <label class="content-form-label" for="email">メールアドレス</label>
            <input class="content-form-input form-control" type="text" name="email" id="email"  value="{{ old('email') }}">
            <p class="content-form-error-message">
                @error('email')
                    {{ $message }}
                @enderror
            </p>
            <label class="content-form-label" for="password">パスワード</label>
            <input class="content-form-input" type="password" name="password" id="password">
            <p class="content-form-error-message">
                @error('password')
                    {{ $message }}
                @enderror
            </p>
            <input class="content-form-btn" type="submit" value="ログインする">
        </form>
        <a class="content-btn" href="/register">会員登録はこちら</a>
    </div>
@endsection('content')
