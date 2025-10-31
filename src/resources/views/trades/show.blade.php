<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="/css/common.css">
    <link rel="stylesheet" href="/css/sanitize.css">
    <link rel="stylesheet" href="/css/trades/show.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        const IS_LOGGED_IN = @json(Auth::check());
    </script>
</head>

<body>
    <header class="header">
        <div class="header-wrapper">
            <h1 class="sr-only">COACHTECH</h1>
            <a class="header-logo" href="{{ url('/') }}">
                <img class="header-logo-img" src="{{ asset('storage/assets/logo.svg') }}" alt="ロゴ">
            </a>
        </div>
    </header>

    <script>
        window.TRADE_INFO = {
            room_id: @json($roomId),

            me: {
                id: @json($me->id),
                name: @json($me->name),
                image: @json($me->image_filename),
            },

            partner: {
                id: @json($partner->id),
                name: @json($partner->name),
                image: @json($partner->image_filename),
            },

            item: {
                id: @json($item->id),
                name: @json($item->name),
                price: @json($item->price),
                image: @json($item->image_filename),
            },

            myRole: @json($myRole), // 'buyer' or 'seller'

            messages: @json($messages),
        };
    </script>

    <div class="trade-page">
        <!-- サイドバー -->
        <aside class="trade-sidebar">
            <p class="trade-sidebar-title">その他の取引</p>
            <ul class="trade-sidebar-list">
                <li><button class="trade-sidebar-btn">商品名</button></li>
                <li><button class="trade-sidebar-btn">商品名</button></li>
                <li><button class="trade-sidebar-btn">商品名</button></li>
            </ul>
        </aside>

        <!-- メイン -->
        <main class="trade-main">
            <!-- タイトル行 -->
            <div class="trade-user">
                <img class="trade-user-image" src="{{ asset('storage/users/' . $partner->image_filename) }}"
                    alt="{{ $partner->image_filename }}">
                <h2 class="trade-user-text">「{{ $partner->name }}」さんとの取引画面</h2>
                <button class="trade-finish">取引を完了する</button>
            </div>

            <!-- 商品情報行 -->
            <div class="trade-item">
                <img class="trade-item-img"
                    src="{{ asset('storage/items/' . $item->image_filename) }}?v={{ now()->timestamp }}"
                    alt="{{ $item->name }}"></img>
                <div class="trade-item-texts">
                    <p class="trade-item-name">{{ $item->name }}</p>
                    <p class="trade-item-price">￥ <span class="price-num">{{ number_format($item->price) }}</span>（税込）
                    </p>
                </div>
            </div>

            <!-- チャット -->
            <div class="chat" id="chat"></div>

            <!-- 入力 -->
            <form id="trade-message-form" class="composer" action="{{ url('/trade/' . $roomId . '/messages') }}"
                method="post">
                @csrf
                <input id="trade-message-input" name="message" class="composer-input" type="text"
                    placeholder="取引メッセージを記入してください">
                <button type="button" class="composer-image">画像を追加</button>
                <button type="submit" class="composer-send" aria-label="送信">
                    <img src="{{ asset('storage/assets/send.jpg') }}" alt="送信" class="composer-send-img">
                </button>
                <p class="form-error" id="trade-message-error"></p>
            </form>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const chatEl = document.getElementById('chat');
                    const form = document.getElementById('trade-message-form');
                    const input = document.getElementById('trade-message-input');
                    const errEl = document.getElementById('trade-message-error');

                    function myUser() {
                        return window.TRADE_INFO?.me || {};
                    }

                    function escapeToHtml(text) {
                        const div = document.createElement('div');
                        div.textContent = (text ?? '');
                        return div.innerHTML.replace(/\n/g, '<br>');
                    }

                    function buildMsgHTML({
                        id,
                        side,
                        user_name,
                        user_image,
                        text,
                        pending = false
                    }) {
                        if (side === 'left') {
                            return `
                    <div class="msg msg-left" ${id ? `data-id="${id}"`: ''} ${pending ? 'data-pending="1"': ''}>
                        <div class="msg-user">
                            ${user_image
                                ? `<img class="user-image" src="/storage/users/${user_image}" alt="${user_name}">`
                                : `<div class="user-image"></div>`}
                            <p class="user-name">${user_name ?? ''}</p>
                        </div>
                        <div class="msg-bubble">${text}</div>
                    </div>`;
                        } else {
                            return `
                    <div class="msg msg-right" ${id ? `data-id="${id}"`: ''} ${pending ? 'data-pending="1"': ''}>
                        <div class="msg-user">
                            <p class="user-name">${user_name ?? ''}</p>
                            ${user_image
                                ? `<img class="user-image" src="/storage/users/${user_image}" alt="${user_name}">`
                                : `<div class="user-image"></div>`}
                        </div>
                        <div class="msg-bubble">${text}</div>
                        <div class="msg-ops">
                            <button class="msg-op-btn" disabled>編集</button>
                            <button class="msg-op-btn" disabled>削除</button>
                        </div>
                    </div>`;
                        }
                    }

                    const me = myUser();
                    const list = window.TRADE_INFO?.messages || [];

                    let html = '';
                    for (const m of list) {
                        const side = m.sender_id === me.id ? 'right' : 'left';
                        html += buildMsgHTML({
                            id: m.id,
                            side,
                            user_name: m.user_name,
                            user_image: m.user_image,
                            text: escapeToHtml(m.text),
                        });
                    }

                    chatEl.innerHTML = html;

                    if (chatEl.lastElementChild) {
                        chatEl.lastElementChild.scrollIntoView({
                            behavior: 'auto'
                        });
                    }

                    form?.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        if (errEl) errEl.textContent = '';

                        const raw = input.value.trim();
                        if (!raw) return;

                        // 即時反映
                        const escaped = document.createElement('div');
                        escaped.textContent = raw;
                        const safeHtml = escaped.innerHTML.replace(/\n/g, '<br>');
                        const me = myUser();

                        const tempHTML = buildMsgHTML({
                            side: 'right',
                            user_name: me.name,
                            user_image: me.image,
                            text: safeHtml,
                            pending: true
                        });

                        chatEl.insertAdjacentHTML('beforeend', tempHTML);
                        chatEl.lastElementChild.scrollIntoView({
                            behavior: 'smooth'
                        });

                        const formData = new FormData(form);

                        try {
                            const res = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content,
                                    'Accept': 'application/json'
                                },
                                credentials: 'same-origin',
                                body: formData
                            });

                            const data = await res.json();
                            if (res.ok && data.success) {
                                chatEl.lastElementChild.dataset.id = data.message.id;
                                chatEl.lastElementChild.removeAttribute('data-pending');
                                input.value = '';
                            } else if (res.status === 422 && data.errors?.message) {
                                if (errEl) errEl.textContent = data.errors.message[0];
                            } else {
                                if (errEl) errEl.textContent = '送信に失敗しました。';
                            }
                        } catch (err) {
                            console.error(err);
                            if (errEl) errEl.textContent = '通信エラーが発生しました。';
                        }
                    });
                });
            </script>
        </main>
    </div>
</body>

</html>
