@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="/css/users/show.css">
@endsection

@section('content')
    <div class="profile-header">
        @if ($user->image_filename)
            <img class="image-circle" src="{{ asset('storage/users/' . $user->image_filename) }}" alt="プロフィール画像">
        @else
            <div class="image-circle"></div>
        @endif
        <h2 class="username">{{ $user->name }}</h2>
        <a class="edit-profile" href="/mypage/profile">プロフィールを編集</a>
    </div>

    <div class="page">
        @php
            $activePage = request('page') ?? 'sell';
            $isTrade = $activePage === 'trade';
        @endphp
        <a class="page-sell {{ $activePage === 'sell' ? 'page-active' : '' }}" href="{{ url('/mypage?page=sell') }}">出品した商品</a>
        <a class="page-buy {{ $activePage === 'buy' ? 'page-active' : '' }}" href="{{ url('/mypage?page=buy') }}">購入した商品</a>
        <a class="page-trade {{ $activePage === 'trade' ? 'page-active' : '' }}"
            href="{{ url('/mypage?page=trade') }}">取引中の商品</a>
    </div>
    <div class="profile-wrapper">
        @foreach ($items as $item)
            @php
                // tradeタブのときは取引チャット画面へリンク
                // purchase -> tradeRoom が存在する場合のみリンク生成
                $roomId = optional(optional($item->purchase)->tradeRoom)->id;
                $link = $isTrade && $roomId
                    ? route('trade.rooms.show', ['roomId' => $roomId])
                    : url('/item/' . $item->id);
            @endphp

            <a class="item-container-link" href="{{ $link }}">
                <div class="item-container">
                    <img class="item-image" src="{{ asset('storage/items/' . $item->image_filename) }}"
                        alt="{{ $item->name }}">
                    @if (!$isTrade && $item->purchase && $item->purchase->completed_at !== null)
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
