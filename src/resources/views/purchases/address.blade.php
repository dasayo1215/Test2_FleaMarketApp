@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <h2 class="content-heading">住所の変更</h2>
        <form class="content-form-wrapper" action="{{ url('/purchase/address/' . $itemId) }}" method="post">
            @csrf
            <label class="content-form-label" for="postal_code">郵便番号</label>
            <input class="content-form-input form-control" type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}">
            <p class="form-error">
                @error('postal_code')
                    {{ $message }}
                @enderror
            </p>
            <label class="content-form-label" for="address">住所</label>
            <input class="content-form-input form-control" type="text" name="address" id="address" value="{{ old('address') }}">
            <p class="form-error">
                @error('address')
                    {{ $message }}
                @enderror
            </p>
            <label class="content-form-label" for="building">建物名</label>
            <input class="content-form-input form-control" type="text" name="building" id="building" value="{{ old('building') }}">
            <p class="form-error">
                @error('building')
                    {{ $message }}
                @enderror
            </p>
            {{-- AddressRequestをプロフィール編集画面と共通で使うため --}}
            <input type="hidden" name="name" value="ダミー名">
            <input class="content-form-btn" type="submit" value="更新する">
        </form>
    </div>
@endsection('content')
