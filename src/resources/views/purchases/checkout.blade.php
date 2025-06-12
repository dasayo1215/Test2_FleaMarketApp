@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
    <div class="content-wrapper4">
        <div class="verify-text">
            <p>{{ $message }}</p>
        </div>
    </div>
@endsection('content')
