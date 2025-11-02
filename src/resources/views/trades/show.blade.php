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

            review: {
                should_prompt: @json($shouldPromptReview),
            },
        };
    </script>

    <script>
        // 追加：PATCH/DELETE用のベースURL
        const updateUrlTemplate = @json(route('trade.messages.update', ['roomId' => $roomId, 'messageId' => '__ID__']));
        const destroyUrlTemplate = @json(route('trade.messages.destroy', ['roomId' => $roomId, 'messageId' => '__ID__']));

        const updateUrl = (id) => updateUrlTemplate.replace('__ID__', id);
        const destroyUrl = (id) => destroyUrlTemplate.replace('__ID__', id);

        let editingMsgEl = null; // いま編集中のメッセージDOM

        document.addEventListener('DOMContentLoaded', () => {
            const chatEl = document.getElementById('chat');
            const form = document.getElementById('trade-message-form');
            const input = document.getElementById('trade-message-input');
            const imageInput = document.getElementById('trade-image-input');
            const imageBtn = document.getElementById('composer-image-btn');
            const errEl = document.getElementById('trade-message-error');

            // 編集モード関連
            const editingIdEl = document.getElementById('editing-message-id');
            const cancelBtn = document.getElementById('composer-cancel-btn');

            const imageValidateUrl = `{{ route('trade.messages.image_validate', ['roomId' => $roomId]) }}`;

            // ===== 入力保持（本文のみ保存） =====
            const KEY = `trade:${window.TRADE_INFO?.room_id}:draft`;
            input.value = localStorage.getItem(KEY) ?? '';
            input.addEventListener('input', () => {
                localStorage.setItem(KEY, input.value);
            });

            function clearDraft() {
                localStorage.removeItem(KEY);
            }

            // 画像ボタン -> 非表示inputを起動（編集中は無効）
            imageBtn?.addEventListener('click', () => {
                if (editingIdEl.value) return; // 編集中は画像追加させない
                imageInput?.click()
            });

            //  画像選択状態の切替
            const DEFAULT_LABEL = '画像を追加';
            const SELECTED_LABEL = '画像選択済';
            const EDITING_LABEL = '画像編集不可'; // 追加

            function setImageSelectedState(selected) {
                if (!imageBtn) return;
                // 編集中は強制的にロック表示
                if (editingIdEl.value) {
                    imageBtn.textContent = EDITING_LABEL;
                    imageBtn.classList.add('is-locked');
                    imageBtn.setAttribute('aria-disabled', 'true');
                    return;
                }
                imageBtn.textContent = selected ? SELECTED_LABEL : DEFAULT_LABEL;
                imageBtn.classList.toggle('is-selected', selected);
                imageBtn.classList.remove('is-locked');
                imageBtn.removeAttribute('aria-disabled');
            }

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
                image_url,
                pending = false
            }) {
                const hasText = !!(text && String(text).trim().length);
                const bubbleInner = `
                ${hasText ? `<div class="msg-text">${text}</div>` : ''}
                ${image_url ? `<div class="msg-image-wrap"><img class="msg-image" src="${image_url}" alt=""></div>` : ''}
            `;
                if (side === 'left') {
                    return `
                <div class="msg msg-left" ${id ? `data-id="${id}"` : ''} ${pending ? 'data-pending="1"' : ''}>
                    <div class="msg-user">
                        ${user_image ? `<img class="user-image" src="/storage/users/${user_image}" alt="${user_name}">` : `<div class="user-image"></div>`}
                        <p class="user-name">${user_name ?? ''}</p>
                    </div>
                    <div class="msg-bubble">${bubbleInner}</div>
                </div>`;
                } else {
                    return `
                <div class="msg msg-right" ${id ? `data-id="${id}"` : ''} ${pending ? 'data-pending="1"' : ''}>
                    <div class="msg-user">
                        <p class="user-name">${user_name ?? ''}</p>
                        ${user_image ? `<img class="user-image" src="/storage/users/${user_image}" alt="${user_name}">` : `<div class="user-image"></div>`}
                    </div>
                    <div class="msg-bubble">${bubbleInner}</div>
                    <div class="msg-ops">
                        <button class="msg-op-btn js-edit">編集</button>
                        <button class="msg-op-btn js-delete">削除</button>
                    </div>
                </div>`;
                }
            }

            // サーバ由来のエラーをそのまま複数表示
            function renderErrorsFromServer(errors) {
                if (!errEl) return;
                const all = [];
                if (Array.isArray(errors?.message)) all.push(...errors.message);
                if (Array.isArray(errors?.image)) all.push(...errors.image);
                if (all.length === 0) {
                    errEl.innerHTML = '';
                    return;
                }
                const esc = (s) => {
                    const d = document.createElement('div');
                    d.textContent = s ?? '';
                    return d.innerHTML;
                };
                errEl.innerHTML = `<ul class="form-error-list">${all.map(e=>`<li>${esc(e)}</li>`).join('')}</ul>`;
            }

            // 初期メッセージ描画
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
                    image_url: m.image_url ?? null,
                });
            }
            chatEl.innerHTML = html;
            chatEl.lastElementChild?.scrollIntoView({
                behavior: 'auto'
            });

            // 画像：事前バリデーション
            imageInput?.addEventListener('change', async () => {
                // 編集中は選択させない（保険）
                if (editingIdEl.value) {
                    imageInput.value = '';
                    setImageSelectedState(false);
                    return;
                }
                setImageSelectedState(imageInput.files?.length > 0);
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                if (imageInput.files?.[0]) fd.append('image', imageInput.files[0]);
                try {
                    const res = await fetch(imageValidateUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: fd,
                        credentials: 'same-origin',
                    });
                    if (res.ok) {
                        renderErrorsFromServer({});
                    } else if (res.status === 422) {
                        const data = await res.json().catch(() => ({}));
                        renderErrorsFromServer(data.errors || {});
                        setImageSelectedState(false);
                    }
                } catch (_) {
                    /* 送信時に委ねる */
                }
            });

            // ===== ここから最小追加：編集/削除 & 編集モード切替 =====
            function htmlToText(html) {
                return (html || '')
                    .replace(/<br\s*\/?>/gi, '\n')
                    .replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>')
                    .replace(/&quot;/g, '"').replace(/&#039;/g, "'");
            }

            function enterEditMode(messageId, currentHtml, msgEl) {
                // 既に他を編集中なら解除
                if (editingMsgEl && editingMsgEl !== msgEl) exitEditMode();

                editingIdEl.value = messageId;
                input.value = htmlToText(currentHtml);

                // 画像は編集では扱わない
                if (imageInput) imageInput.value = '';
                setImageSelectedState(false);
                imageBtn.disabled = true;
                imageBtn.textContent = EDITING_LABEL;
                imageBtn.classList.add('is-locked');
                imageBtn.setAttribute('aria-disabled', 'true');

                const editBtn = msgEl.querySelector('.js-edit');
                if (editBtn) {
                    editBtn.textContent = '編集キャンセル';
                    editBtn.classList.add('is-cancel');
                }

                document.querySelectorAll('.js-edit').forEach(btn => {
                    if (btn !== editBtn) btn.disabled = true;
                });

                editingMsgEl = msgEl;
                input.focus();
            }

            function exitEditMode() {
                editingIdEl.value = '';
                input.value = '';
                imageBtn.disabled = false;
                imageBtn.classList.remove('is-locked');
                imageBtn.removeAttribute('aria-disabled');
                imageBtn.textContent = DEFAULT_LABEL;
                setImageSelectedState(false);

                if (editingMsgEl) {
                    const editBtn = editingMsgEl.querySelector('.js-edit');
                    if (editBtn) {
                        editBtn.textContent = '編集';
                        editBtn.classList.remove('is-cancel');
                    }
                }
                document.querySelectorAll('.js-edit').forEach(btn => btn.disabled = false);

                editingMsgEl = null;
            }

            // 編集/削除ボタン
            chatEl?.addEventListener('click', async (e) => {
                const editBtn = e.target.closest('.js-edit');
                const delBtn = e.target.closest('.js-delete');
                const msgEl = e.target.closest('.msg');
                if (!msgEl) return;
                const id = msgEl.dataset.id;

                // 編集
                if (editBtn && msgEl.classList.contains('msg-right')) {
                    if (editBtn.classList.contains('is-cancel')) {
                        exitEditMode();
                        return;
                    }
                    const textDiv = msgEl.querySelector('.msg-bubble .msg-text');
                    const currentHTML = textDiv ? textDiv.innerHTML : '';
                    enterEditMode(id, currentHTML, msgEl);
                    return;
                }

                // 削除
                if (delBtn && msgEl.classList.contains('msg-right')) {
                    if (!confirm('このメッセージを削除しますか？')) return;
                    const fd = new FormData();
                    fd.append('_method', 'DELETE');
                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    try {
                        const res = await fetch(destroyUrl(id), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin',
                            body: fd
                        });
                        let data = {};
                        try {
                            data = await res.json();
                        } catch {}
                        if (res.ok && (data.success === true || res.status === 204)) {
                            msgEl.remove();
                            if (editingIdEl.value === id) exitEditMode();
                        } else {
                            alert('削除に失敗しました。');
                        }
                    } catch (_) {
                        alert('通信エラーが発生しました。');
                    }
                }
            });

            // 送信（新規 or 編集）
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (errEl) errEl.innerHTML = '';

                const editingId = editingIdEl.value.trim();

                // 編集中
                if (editingId) {
                    const fd = new FormData();
                    fd.append('_method', 'PATCH');
                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    fd.append('message', input.value ?? '');

                    try {
                        const res = await fetch(updateUrl(editingId), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin',
                            body: fd
                        });
                        let data = {};
                        try {
                            data = await res.json();
                        } catch {}
                        if (res.ok && data.success) {
                            const msgEl = chatEl.querySelector(`.msg[data-id="${editingId}"]`);
                            const bubble = msgEl?.querySelector('.msg-bubble');
                            if (bubble) {
                                const esc = document.createElement('div');
                                esc.textContent = data.message?.text ?? '';
                                const safe = esc.innerHTML.replace(/\n/g, '<br>');
                                const textDiv = bubble.querySelector('.msg-text');
                                if (textDiv) textDiv.innerHTML = safe;
                                else bubble.insertAdjacentHTML('afterbegin',
                                    `<div class="msg-text">${safe}</div>`);
                            }
                            exitEditMode();
                            clearDraft(); // ← 編集成功時にクリア
                        } else if (res.status === 422 && data.errors) {
                            renderErrorsFromServer(data.errors);
                        } else {
                            alert('更新に失敗しました。');
                        }
                    } catch (_) {
                        alert('通信エラーが発生しました。');
                    }
                    return;
                }

                // 新規投稿
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
                    let data = {};
                    try {
                        data = await res.json();
                    } catch (_) {}
                    if (res.ok && data.success) {
                        const safeHtml = (() => {
                            const div = document.createElement('div');
                            div.textContent = data.message.text ?? '';
                            return div.innerHTML.replace(/\n/g, '<br>');
                        })();
                        const me = myUser();
                        chatEl.insertAdjacentHTML('beforeend', buildMsgHTML({
                            id: data.message.id,
                            side: 'right',
                            user_name: me.name,
                            user_image: me.image,
                            text: safeHtml,
                            image_url: data.message.image_url ?? null,
                        }));
                        chatEl.lastElementChild.scrollIntoView({
                            behavior: 'smooth'
                        });
                        input.value = '';
                        if (imageInput) imageInput.value = '';
                        setImageSelectedState(false);
                        clearDraft(); // ← 新規送信成功時にクリア
                    } else if (res.status === 422 && data.errors) {
                        renderErrorsFromServer(data.errors);
                    } else {
                        errEl.textContent = '送信に失敗しました。';
                    }
                } catch (err) {
                    console.error(err);
                    errEl.textContent = '通信エラーが発生しました。';
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const finishBtn = document.querySelector('.trade-finish');
            const modal = document.getElementById('review-modal');
            const starsWrap = document.getElementById('star-group');
            const submitBtn = document.getElementById('review-submit');

            let currentScore = 0;

            function openModal() {
                currentScore = 0;
                [...starsWrap.querySelectorAll('.star')].forEach(b => b.classList.remove('is-on'));
                submitBtn.disabled = true;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }

            // 販売者で自動表示条件を満たす場合は自動で開く
            if (window.TRADE_INFO?.review?.should_prompt) {
                openModal();
            }

            // 購入者側：手動ボタンで開く（ボタンがあるときだけ設定）
            if (finishBtn) {
                finishBtn.addEventListener('click', () => openModal());
            }

            // 背景クリックで閉じる
            modal.addEventListener('click', (e) => {
                if (e.target.dataset.close === '1') closeModal();
            });

            // 星クリックでスコア決定（既存）
            starsWrap?.addEventListener('click', (e) => {
                const btn = e.target.closest('.star');
                if (!btn) return;
                const v = Number(btn.dataset.value || 0);
                currentScore = v;
                const all = [...starsWrap.querySelectorAll('.star')];
                all.forEach(b => b.classList.remove('is-on'));
                for (let i = 0; i < v; i++) all[i].classList.add('is-on');
                submitBtn.disabled = !currentScore;
            });

            // 送信
            submitBtn.addEventListener('click', async () => {
                if (!currentScore) return;

                const route = `{{ route('trade.reviews.store', ['roomId' => $roomId]) }}`;
                const meId = window.TRADE_INFO?.me?.id;
                const buyerId = @json($item->purchase->buyer_id ?? null);
                const sellerId = @json($item->seller_id ?? null);
                const partnerId = meId === buyerId ? sellerId : buyerId;

                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fd.append('score', String(currentScore));

                submitBtn.disabled = true;

                try {
                    const res = await fetch(route, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: fd
                    });
                    if (res.redirected) {
                        window.location.href = res.url;
                        return;
                    }
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        window.location.href = '/';
                    } else {
                        alert(data.message || '評価の送信に失敗しました。');
                        submitBtn.disabled = false;
                    }
                } catch (err) {
                    console.error(err);
                    alert('通信エラーが発生しました。');
                    submitBtn.disabled = false;
                }
            });
        });
    </script>
@endsection
