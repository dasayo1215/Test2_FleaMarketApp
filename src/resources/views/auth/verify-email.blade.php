@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="/css/auth/verify-email.css">
@endsection

@section('content')
    <div class="content-wrapper-small">
        @if (session('message'))
            <div class="verify-text">
                {{ session('message') }}
            </div>
        @endif
        <div class="verify-text">
            メール認証を完了してください。
        </div>

        <a class="mail-link" href="http://localhost:8025/">認証はこちらから</a>

        <form class="content-form" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="content-btn" type="submit">認証メールを再送する</button>
        </form>
    </div>
@endsection('content')
