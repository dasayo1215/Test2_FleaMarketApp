@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/items/index.css') }}">
@endsection

@section('content')
    <div class="page">
        <a class="page-recommend {{ $page !== 'mylist' ? 'active' : '' }}" href="{{ route('index', ['page' => null, 'keyword' => request('keyword')]) }}">おすすめ</a>
        <a class="page-mylist {{ $page === 'mylist' ? 'active' : '' }}" href="{{ route('index', ['page' => 'mylist', 'keyword' => request('keyword')]) }}">マイリスト</a>
    </div>
    <div class="content-wrapper3">
        @foreach ($items as $item)
            <a class="item-container-link" href="{{ url('/item/' . $item->id) }}">
                <div class="item-container">
                    <img class="item-image" src="{{ asset('storage/items/' . $item->image_filename) }}"
                        alt="{{ $item->name }}">
                    @if ($item->purchase && $item->purchase->completed_at !== null)
                        <div class="sold-label">Sold</div>
                    @endif
                    <div class="item-name">{{ $item->name }}</div>
                </div>
            </a>
        @endforeach

        {{-- 各商品を適度に間隔開けて左揃えにするためのダミー --}}
        @for ($i = 0; $i < 5; $i++)
            <div class="item-container-empty"></div>
        @endfor
    </div>
@endsection('content')
