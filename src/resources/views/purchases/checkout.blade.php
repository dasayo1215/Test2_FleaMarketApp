@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="/css/purchases/checkout.css">
@endsection

@section('content')
    <div class="content-wrapper-small">
        <div class="verify-text">
            <p>{{ $message }}</p>
        </div>
    </div>
@endsection('content')
