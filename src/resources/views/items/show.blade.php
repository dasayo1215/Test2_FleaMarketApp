@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="/css/items/show.css">
@endsection

@section('content')
    <div class="item-content-wrapper">
        <div class="image-container">
            <img class="image-square" src="{{ asset('storage/items/' . $item->image_filename) }}?v={{ now()->timestamp }}"
                alt="{{ $item->name }}"></img>
            @if ($item->purchase && $item->purchase->completed_at !== null)
                <div class="sold-label">Sold</div>
            @endif
        </div>
        <div class="content-detail">
            <div class="content-heading">
                <h2 class="item-name">{{ $item->name }}</h2>
                <div class="content-brand">{{ $item->brand }}</div>
                <div class=" content-price">￥ <span class="content-price-num">{{ number_format($item->price) }}</span>（税込）
                </div>
                <div class="content-like-comment">
                    @php
                        $isLiked = $user ? $item->isLikedBy($user) : false;
                        $likeCount = $item->likes->count();
                        $commentCount = $item->comments->count();
                    @endphp
                    <div class="content-like">
                        @auth
                            @if (!Auth::user()->hasVerifiedEmail())
                                {{-- メール未認証ユーザー --}}
                                <a href="{{ route('verification.notice') }}" class="like-button-unverified">
                                    <img class="content-like-img" src="{{ asset('storage/assets/star-off.png') }}"
                                        alt="いいね（未認証）">
                                </a>
                            @else
                                {{-- メール認証済みユーザー --}}
                                <form class="like-form" method="POST" action="{{ route('like', $item->id) }}">
                                    @csrf
                                    <button class="like-button" data-item-id="{{ $item->id }}">
                                        <img class="content-like-img"
                                            src="{{ $isLiked ? asset('storage/assets/star-on.png') : asset('storage/assets/star-off.png') }}"
                                            alt="いいね">
                                    </button>
                                </form>
                            @endif
                        @else
                            {{-- ゲストユーザー --}}
                            <div class="like-button disabled">
                                <img class="content-like-img-guest" src="{{ asset('storage/assets/star-off.png') }}"
                                    alt="いいね">
                            </div>
                        @endauth
                        <div class="content-like-num" id="like-count-{{ $item->id }}">{{ $likeCount }}</div>
                    </div>
                    <div class="content-comment">
                        <img class="content-comment-img" src="{{ asset('storage/assets/bubble.png') }}" alt="ロゴ">
                        <div class="content-comment-num" id="comment-icon-count">{{ $commentCount }}</div>
                    </div>
                </div>
            </div>
            @php
                $purchase = $item->purchase;
            @endphp

            @if ($item->seller_id == auth()->id())
                <div class="purchase-unavailable">自身の出品です</div>
            @elseif ($purchase)
                @if (!is_null($purchase->paid_at))
                    <div class="purchase-sold">Sold</div>
                @elseif (!auth()->check() || $purchase->buyer_id !== auth()->id())
                    <div class="purchase-unavailable">他ユーザーが購入手続き中です</div>
                @elseif(!is_null($purchase->completed_at) && $purchase->buyer_id === auth()->id())
                    <div class="purchase-unavailable">お支払いを完了してください</div>
                @else
                    <a class="content-purchase-btn" href="{{ url('/purchase/' . $item->id) }}">購入手続きを再開</a>
                @endif
            @else
                <a class="content-purchase-btn" href="{{ url('/purchase/' . $item->id) }}">購入手続きへ</a>
            @endif

            <h3 class="item-description">商品説明</h3>
            <div>{{ $item->description }}</div>

            <h3 class="item-info">商品の情報</h3>
            <table class="info-table">
                <tr class="info-table-tr table-row1">
                    <th class="info-table-th th-category">カテゴリー</th>
                    <td class="td-category">
                        @foreach ($item->categories as $category)
                            <span class="td-category-span">{{ $category->name }}</span>
                        @endforeach
                    </td>
                </tr>
                <tr class="info-table-tr">
                    <th class="info-table-th">商品の状態</th>
                    <td class="td-state">{{ $item->itemCondition->name }}</td>
                </tr>
            </table>

            <h3 class="comment-title">
                コメント(<span id="comment-count">{{ $item->comments->count() }}</span>)
            </h3>
            @foreach ($item->comments as $comment)
                <div class="comment-user">
                    <div class="user-image-container">
                        @if ($comment->user->image_filename)
                            <img class="comment-user-image"
                                src="{{ asset('storage/users/' . $comment->user->image_filename) }}"
                                alt="{{ $comment->user->image_filename }}" class="user-icon">
                        @else
                            <div class="comment-user-image"></div>
                        @endif
                    </div>
                    <div class="comment-user-name">{{ $comment->user->name }}</div>
                </div>
                <div class="comment-content">{{ $comment->comment }}</div>
            @endforeach

            <form method="POST" action="{{ route('comment', $item->id) }}" id="comment-form">
                @csrf
                <label class="content-form-label" for="comment">商品へのコメント</label>
                <textarea class="content-form-textarea form-control" name="comment" id="comment" cols="30" rows="10"></textarea>
                <p class="form-error">
                    @error('comment')
                        {{ $message }}
                    @enderror
                </p>
                @if ($purchase && !is_null($purchase->completed_at))
                    <div class="comment-unavailable">コメントできません</div>
                @else
                    @auth
                        @if (!Auth::user()->hasVerifiedEmail())
                            <a class="content-form-btn btn-unverified" href="{{ route('verification.notice') }}"
                                class="content-form-btn comment-unverified">
                                コメントを送信する
                            </a>
                        @else
                            <input class="content-form-btn" type="submit" value="コメントを送信する" id="submit-comment">
                        @endif
                    @else
                        <input class="content-form-btn" type="submit" value="コメントを送信する" id="submit-comment">
                    @endauth
                @endif
            </form>
        </div>
    </div>
@endsection('content')

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // いいねボタンの処理
            document.querySelectorAll('.like-button').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const itemId = this.dataset.itemId;
                    fetch(`/item/${itemId}/like`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                        })
                        .then(response => response.json())
                        .then(data => {
                            const img = this.querySelector('img');
                            img.src = data.liked ?
                                '/storage/assets/star-on.png' :
                                '/storage/assets/star-off.png';

                            document.getElementById(`like-count-${itemId}`).textContent = data
                                .like_count;
                        })
                        .catch(error => {
                            console.error('通信失敗:', error);
                        });
                });
            });

            // コメント送信フォームの非同期処理
            const commentForm = document.getElementById('comment-form');
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (!IS_LOGGED_IN) {
                        window.location.href = '/login';
                        return;
                    }

                    const formData = new FormData(this);
                    const errorMessage = document.querySelector('.form-error');
                    errorMessage.textContent = ''; // エラーをリセット

                    fetch(this.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content,
                                'Accept': 'application/json',
                            },
                            body: formData
                        })
                        .then(async response => {
                            const data = await response.json();

                            if (response.ok && data.success) {
                                const c = data.comment;

                                const commentHtml = `
                                <div class="comment-user">
                                    <div class="user-image-container">
                                        ${c.user_image
                                            ? `<img class="comment-user-image" src="/storage/users/${c.user_image}" alt="${c.user_name}">`
                                            : `<div class="comment-user-image"></div>`}
                                    </div>
                                    <div class="comment-user-name">${c.user_name}</div>
                                </div>
                                <div class="comment-content">${c.text}</div>
                            `;

                                commentForm.insertAdjacentHTML('beforebegin', commentHtml);
                                document.getElementById('comment').value = '';

                                // コメント数の更新
                                const countEl = document.getElementById('comment-count');
                                const iconCountEl = document.getElementById('comment-icon-count');

                                if (countEl) countEl.textContent = parseInt(countEl.textContent) +
                                    1;
                                if (iconCountEl) iconCountEl.textContent = parseInt(iconCountEl
                                    .textContent) + 1;

                            } else if (response.status === 422 && data.errors) {
                                // バリデーションエラー処理
                                if (data.errors.comment) {
                                    errorMessage.textContent = data.errors.comment[0];
                                }
                            } else {
                                alert('コメントの送信に失敗しました。');
                            }
                        })
                        .catch(error => {
                            console.error('通信エラー:', error);
                        });
                });
            }
        });
    </script>
@endsection('scripts')
