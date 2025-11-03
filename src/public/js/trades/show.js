/* 名前空間 */
const TradeUI = {
    qs: (s, r = document) => r.querySelector(s),
    qsa: (s, r = document) => Array.from(r.querySelectorAll(s)),
    csrf: () =>
        document.querySelector('meta[name="csrf-token"]')?.content || "",
    routes: window.TRADE_ROUTES || {},
    info: window.TRADE_INFO || {},
    state: {
        editingMsgEl: null,
    },
};

/* --- 汎用ユーティリティ --- */
function escapeHtml(s) {
    const d = document.createElement("div");
    d.textContent = s ?? "";
    return d.innerHTML;
}
function textToHtml(text) {
    return escapeHtml(text).replace(/\n/g, "<br>");
}
function htmlToText(html) {
    return (html || "")
        .replace(/<br\s*\/?>/gi, "\n")
        .replace(/&amp;/g, "&")
        .replace(/&lt;/g, "<")
        .replace(/&gt;/g, ">")
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'");
}
function renderErrors(errors) {
    const el = document.getElementById("trade-message-error");
    if (!el) return;
    const all = [];
    if (Array.isArray(errors?.message)) all.push(...errors.message);
    if (Array.isArray(errors?.image)) all.push(...errors.image);
    el.innerHTML = all.length
        ? `<ul class="form-error-list">${all
            .map((e) => `<li>${escapeHtml(e)}</li>`)
            .join("")}</ul>`
        : "";
}

/* --- メッセージHTML生成（左右で共通化） --- */
function buildMsgHTML({ id, side, user_name, user_image, text, image_url }) {
    const hasText = !!(text && String(text).trim().length);
    const img = image_url
        ? `<div class="msg-image-wrap"><img class="msg-image" src="${image_url}" alt=""></div>`
        : "";
    const txt = hasText ? `<div class="msg-text">${text}</div>` : "";
    const ops =
        side === "right"
            ? `<div class="msg-ops">
                    <button class="msg-op-btn js-edit">編集</button>
                    <button class="msg-op-btn js-delete">削除</button>
                </div>`
            : "";
    const userImg = user_image
        ? `<img class="user-image" src="/storage/users/${user_image}" alt="${user_name}">`
        : `<div class="user-image"></div>`;

    return `
        <div class="msg msg-${side}" data-id="${id}">
            <div class="msg-user">
            ${
                side === "left"
                    ? `${userImg}<p class="user-name">${user_name ?? ""}</p>`
                    : ""
            }
            ${
                side === "right"
                    ? `<p class="user-name">${user_name ?? ""}</p>${userImg}`
                    : ""
            }
    </div>
    <div class="msg-bubble">${txt}${img}</div>
    ${ops}
    </div>`;
}

/* --- 初期メッセージ描画 --- */
function bootstrapMessages() {
    const chatEl = TradeUI.qs("#chat");
    if (!chatEl) return;
    const meId = TradeUI.info?.me?.id;
    const list = TradeUI.info?.messages || [];
    const html = list
        .map((m) =>
            buildMsgHTML({
                id: m.id,
                side: m.sender_id === meId ? "right" : "left",
                user_name: m.user_name,
                user_image: m.user_image,
                text: textToHtml(m.text),
                image_url: m.image_url ?? null,
            })
        )
        .join("");
    chatEl.innerHTML = html;
    chatEl.lastElementChild?.scrollIntoView({ behavior: "auto" });
}

/* --- 入力ドラフト＆画像ボタン状態 --- */
function initComposerState() {
    const key = `trade:${TradeUI.info?.room_id}:draft`;
    const input = TradeUI.qs("#trade-message-input");
    const imgInput = TradeUI.qs("#trade-image-input");
    const imgBtn = TradeUI.qs("#composer-image-btn");
    const editingIdEl = TradeUI.qs("#editing-message-id");

    if (input) {
        input.value = localStorage.getItem(key) ?? "";
        input.addEventListener("input", () =>
            localStorage.setItem(key, input.value)
        );
    }

    function setImageSelectedState(selected) {
        if (!imgBtn) return;
        if (editingIdEl?.value) {
            imgBtn.textContent = "画像編集不可";
            imgBtn.classList.add("is-locked");
            imgBtn.setAttribute("aria-disabled", "true");
            return;
        }
        imgBtn.textContent = selected ? "画像選択済" : "画像を追加";
        imgBtn.classList.toggle("is-selected", selected);
        imgBtn.classList.remove("is-locked");
        imgBtn.removeAttribute("aria-disabled");
    }

    imgBtn?.addEventListener("click", () => {
        if (editingIdEl?.value) return;
        imgInput?.click();
    });

    imgInput?.addEventListener("change", async () => {
        if (editingIdEl?.value) {
            imgInput.value = "";
            setImageSelectedState(false);
            return;
        }
        setImageSelectedState(imgInput.files?.length > 0);

        const fd = new FormData();
        fd.append("_token", TradeUI.csrf());
        if (imgInput.files?.[0]) fd.append("image", imgInput.files[0]);

        try {
            const res = await fetch(TradeUI.routes.image_validate, {
                method: "POST",
                headers: { Accept: "application/json" },
                credentials: "same-origin",
                body: fd,
            });
            if (!res.ok && res.status === 422) {
                const data = await res.json().catch(() => ({}));
                renderErrors(data.errors || {});
                setImageSelectedState(false);
            } else {
                renderErrors({});
            }
        } catch {
            /* 送信時に最終確認するのでここでは握りつぶし */
        }
    });

    // 初期状態
    setImageSelectedState(false);
}

/* --- 編集モード（enter/exit） --- */
function enterEditMode(messageId, currentHtml, msgEl) {
    const input = TradeUI.qs("#trade-message-input");
    const editingIdEl = TradeUI.qs("#editing-message-id");
    const imgBtn = TradeUI.qs("#composer-image-btn");
    const imgInput = TradeUI.qs("#trade-image-input");

    if (TradeUI.state.editingMsgEl && TradeUI.state.editingMsgEl !== msgEl) {
        exitEditMode();
    }
    if (editingIdEl) editingIdEl.value = messageId;
    if (input) input.value = htmlToText(currentHtml);
    if (imgInput) imgInput.value = "";
    if (imgBtn) {
        imgBtn.disabled = true;
        imgBtn.textContent = "画像編集不可";
        imgBtn.classList.add("is-locked");
        imgBtn.setAttribute("aria-disabled", "true");
    }
    const editBtn = msgEl.querySelector(".js-edit");
    if (editBtn) {
        editBtn.textContent = "編集キャンセル";
        editBtn.classList.add("is-cancel");
    }
    TradeUI.qsa(".js-edit").forEach((btn) => {
        if (btn !== editBtn) btn.disabled = true;
    });
    TradeUI.state.editingMsgEl = msgEl;
    input?.focus();
}
function exitEditMode() {
    const editingIdEl = TradeUI.qs("#editing-message-id");
    const imgBtn = TradeUI.qs("#composer-image-btn");
    const input = TradeUI.qs("#trade-message-input");

    if (editingIdEl) editingIdEl.value = "";
    if (input) input.value = "";
    if (imgBtn) {
        imgBtn.disabled = false;
        imgBtn.classList.remove("is-locked");
        imgBtn.removeAttribute("aria-disabled");
        imgBtn.textContent = "画像を追加";
    }
    if (TradeUI.state.editingMsgEl) {
        const editBtn = TradeUI.state.editingMsgEl.querySelector(".js-edit");
        if (editBtn) {
            editBtn.textContent = "編集";
            editBtn.classList.remove("is-cancel");
        }
    }
    TradeUI.qsa(".js-edit").forEach((btn) => (btn.disabled = false));
    TradeUI.state.editingMsgEl = null;
}

/* --- チャット面のクリック（編集/削除） --- */
async function onChatClick(e) {
    const msgEl = e.target.closest(".msg");
    if (!msgEl) return;
    const id = msgEl.dataset.id;
    const isRight = msgEl.classList.contains("msg-right");
    const editBtn = e.target.closest(".js-edit");
    const delBtn = e.target.closest(".js-delete");

    // 編集
    if (editBtn && isRight) {
        if (editBtn.classList.contains("is-cancel")) {
            exitEditMode();
            return;
        }
        const textDiv = msgEl.querySelector(".msg-bubble .msg-text");
        const currentHTML = textDiv ? textDiv.innerHTML : "";
        enterEditMode(id, currentHTML, msgEl);
        return;
    }

    // 削除
    if (delBtn && isRight) {
        if (!confirm("このメッセージを削除しますか？")) return;
        const fd = new FormData();
        fd.append("_method", "DELETE");
        fd.append("_token", TradeUI.csrf());
        try {
            const res = await fetch(
                TradeUI.routes.destroy.replace("__ID__", id),
                {
                    method: "POST",
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                    body: fd,
                }
            );
            let data = {};
            try {
                data = await res.json();
            } catch {}
            if (res.ok && (data.success === true || res.status === 204)) {
                msgEl.remove();
                if (TradeUI.qs("#editing-message-id")?.value === id)
                    exitEditMode();
            } else {
                alert("削除に失敗しました。");
            }
        } catch {
            alert("通信エラーが発生しました。");
        }
    }
}

/* --- 送信：新規 --- */
async function submitNewMessage(form) {
    const chatEl = TradeUI.qs("#chat");
    const input = TradeUI.qs("#trade-message-input");
    const imageInput = TradeUI.qs("#trade-image-input");
    const me = TradeUI.info?.me || {};
    const draftKey = `trade:${TradeUI.info?.room_id}:draft`;

    const res = await fetch(TradeUI.routes.store, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": TradeUI.csrf(), Accept: "application/json" },
        credentials: "same-origin",
        body: new FormData(form),
    });
    let data = {};
    try {
        data = await res.json();
    } catch {}
    if (res.ok && data.success) {
        const safeHtml = textToHtml(data.message.text ?? "");
        chatEl.insertAdjacentHTML(
            "beforeend",
            buildMsgHTML({
                id: data.message.id,
                side: "right",
                user_name: me.name,
                user_image: me.image,
                text: safeHtml,
                image_url: data.message.image_url ?? null,
            })
        );
        chatEl.lastElementChild?.scrollIntoView({ behavior: "smooth" });
        if (input) input.value = "";
        if (imageInput) imageInput.value = "";
        localStorage.removeItem(draftKey);
        renderErrors({});
    } else if (res.status === 422 && data.errors) {
        renderErrors(data.errors);
    } else {
        const err = document.getElementById("trade-message-error");
        if (err) err.textContent = "送信に失敗しました。";
    }
}

/* --- 送信：編集 --- */
async function submitEditMessage(editingId, text) {
    const chatEl = TradeUI.qs("#chat");
    const fd = new FormData();
    fd.append("_method", "PATCH");
    fd.append("_token", TradeUI.csrf());
    fd.append("message", text ?? "");

    let resp,
        data = {};
    try {
        resp = await fetch(TradeUI.routes.update.replace("__ID__", editingId), {
            method: "POST",
            headers: { Accept: "application/json" },
            credentials: "same-origin",
            body: fd,
        });
        try {
            data = await resp.json();
        } catch {}
    } catch {
        alert("通信エラーが発生しました。");
        return;
    }

    if (resp.ok && data.success) {
        const msgEl = chatEl?.querySelector(`.msg[data-id="${editingId}"]`);
        const bubble = msgEl?.querySelector(".msg-bubble");
        if (bubble) {
            const safe = textToHtml(data.message?.text ?? "");
            const textDiv = bubble.querySelector(".msg-text");
            if (textDiv) textDiv.innerHTML = safe;
            else
                bubble.insertAdjacentHTML(
                    "afterbegin",
                    `<div class="msg-text">${safe}</div>`
                );
        }
        const draftKey = `trade:${TradeUI.info?.room_id}:draft`;
        localStorage.removeItem(draftKey);
        renderErrors({});
        exitEditMode();
    } else if (resp.status === 422 && data.errors) {
        renderErrors(data.errors);
    } else {
        alert("更新に失敗しました。");
    }
}

/* --- フォームsubmit割り当て --- */
function wireFormSubmit() {
    const form = document.getElementById("trade-message-form");
    if (!form) return;
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        renderErrors({});
        const editingId = (
            document.getElementById("editing-message-id")?.value || ""
        ).trim();
        if (editingId) {
            const txt =
                document.getElementById("trade-message-input")?.value ?? "";
            await submitEditMessage(editingId, txt);
        } else {
            await submitNewMessage(form);
        }
    });
}

/* --- エントリーポイント --- */
document.addEventListener("DOMContentLoaded", () => {
    bootstrapMessages();
    initComposerState();
    document.getElementById("chat")?.addEventListener("click", onChatClick);
    wireFormSubmit();
});
