@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="/css/trades/show.css">
@endsection

@section('content')
    <div class="trade-page">
        <!-- サイドバー -->
        <aside class="trade-sidebar">
            <p class="trade-sidebar-title">その他の取引</p>
            <ul class="trade-sidebar-list">
                @forelse($otherTrades as $t)
                    <li>
                        <a class="trade-sidebar-btn {{ $t['room_id'] === $roomId ? 'is-active' : '' }}"
                            href="{{ route('trade.show', ['roomId' => $t['room_id']]) }}">
                            {{ $t['item_name'] }}
                        </a>
                    </li>
                @empty
                    <li class="trade-sidebar-empty">その他の進行中の取引は<br>ありません</li>
                @endforelse
            </ul>
        </aside>

        <!-- 評価モーダル -->
        <div id="review-modal" class="modal" aria-hidden="true">
            <div class="modal-backdrop" data-close="1"></div>
            <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="review-title">
                <div id="review-title" class="modal-title">取引が完了しました。</div>
                <p class="modal-sub">今回の取引相手はどうでしたか？</p>

                <div class="stars" id="star-group" aria-label="5段階評価">
                    <!-- data-valueに1..5 -->
                    <button type="button" class="star" data-value="1" aria-label="1星">★</button>
                    <button type="button" class="star" data-value="2" aria-label="2星">★</button>
                    <button type="button" class="star" data-value="3" aria-label="3星">★</button>
                    <button type="button" class="star" data-value="4" aria-label="4星">★</button>
                    <button type="button" class="star" data-value="5" aria-label="5星">★</button>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn" id="review-submit" disabled>送信する</button>
                </div>
            </div>
        </div>

        <!-- メイン -->
        <main class="trade-main">
            <!-- タイトル行 -->
            <div class="trade-user">
                <img class="trade-user-image" src="{{ asset('storage/users/' . $partner->image_filename) }}"
                    alt="{{ $partner->image_filename }}">
                <h2 class="trade-user-text">「{{ $partner->name }}」さんとの取引画面</h2>

                @if ($myRole === 'buyer')
                    @if (!empty($waitingForSellerReview) && $waitingForSellerReview)
                        <p class="trade-waiting-review">出品者評価待ち</p>
                    @else
                        <button class="trade-finish">取引を完了する</button>
                    @endif
                @endif
            </div>

            <!-- 商品情報行 -->
            <div class="trade-item">
                <img class="trade-item-img"
                    src="{{ asset('storage/items/' . $item->image_filename) }}?v={{ now()->timestamp }}"
                    alt="{{ $item->name }}">
                <div class="trade-item-texts">
                    <h3 class="trade-item-name">{{ $item->name }}</h3>
                    <p class="trade-item-price">￥ <span class="price-num">{{ number_format($item->price) }}</span>（税込）</p>
                </div>
            </div>

            <!-- チャット -->
            <div class="chat" id="chat"></div>

            <!-- 入力 -->
            <form id="trade-message-form" class="composer-form"
                action="{{ route('trade.messages.store', ['roomId' => $roomId]) }}" method="post"
                enctype="multipart/form-data">
                @csrf
                <!-- 追加：編集モード識別 -->
                <input type="hidden" id="editing-message-id" name="editing_message_id" value="">

                <div class="composer">
                    <p class="composer-error" id="trade-message-error"></p>
                    <div class="composer-wrapper">
                        <input id="trade-message-input" name="message" class="composer-input" type="text"
                            placeholder="取引メッセージを記入してください">
                        <input id="trade-image-input" name="image" type="file" style="display:none">
                        <button type="button" class="composer-image" id="composer-image-btn">画像を追加</button>

                        <!-- 送信ボタンの文言をJSで切替（送信/更新） -->
                        <button type="submit" class="composer-send" aria-label="送信" id="composer-send-btn">
                            <img src="{{ asset('storage/assets/send.jpg') }}" alt="送信" class="composer-send-img">
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
@endsection

@section('scripts')
    <script>
        window.TRADE_INFO = {
            room_id: @json($roomId),
            me: {
                id: @json($me->id),
                name: @json($me->name),
                image: @json($me->image_filename)
            },
            partner: {
                id: @json($partner->id),
                name: @json($partner->name),
                image: @json($partner->image_filename)
            },
            item: {
                id: @json($item->id),
                name: @json($item->name),
                price: @json($item->price),
                image: @json($item->image_filename)
            },
            myRole: @json($myRole),
            messages: @json($messages),
            review: {
                should_prompt: @json($shouldPromptReview)
            },
        };

        window.TRADE_ROUTES = {
            store: @json(route('trade.messages.store', ['roomId' => $roomId])),
            update: @json(route('trade.messages.update', ['roomId' => $roomId, 'messageId' => '__ID__'])),
            destroy: @json(route('trade.messages.destroy', ['roomId' => $roomId, 'messageId' => '__ID__'])),
            image_validate: @json(route('trade.messages.image_validate', ['roomId' => $roomId])),
            review_store: @json(route('trade.reviews.store', ['roomId' => $roomId])),
        };
    </script>
    <script src="/js/trades/show.js" defer></script>
    <script src="/js/trades/review-modal.js" defer></script>
@endsection
