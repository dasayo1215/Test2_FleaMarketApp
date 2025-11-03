const ReviewUI = {
    qs: (s, r = document) => r.querySelector(s),
    qsa: (s, r = document) => Array.from(r.querySelectorAll(s)),
    csrf: () =>
        document.querySelector('meta[name="csrf-token"]')?.content || "",
    info: window.TRADE_INFO || {},
    routes: window.TRADE_ROUTES || {},
    state: { currentScore: 0 },
};

function openReviewModal() {
    const modal = ReviewUI.qs("#review-modal");
    const starsWrap = ReviewUI.qs("#star-group");
    const submitBtn = ReviewUI.qs("#review-submit");
    if (!modal || !starsWrap || !submitBtn) return;

    ReviewUI.state.currentScore = 0;
    ReviewUI.qsa(".star", starsWrap).forEach((b) =>
        b.classList.remove("is-on")
    );
    submitBtn.disabled = true;
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
}
function closeReviewModal() {
    const modal = ReviewUI.qs("#review-modal");
    if (!modal) return;
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
}

function wireReviewStars() {
    const wrap = ReviewUI.qs("#star-group");
    const submitBtn = ReviewUI.qs("#review-submit");
    if (!wrap || !submitBtn) return;

    wrap.addEventListener("click", (e) => {
        const btn = e.target.closest(".star");
        if (!btn) return;
        const v = Number(btn.dataset.value || 0);
        ReviewUI.state.currentScore = v;
        const all = ReviewUI.qsa(".star", wrap);
        all.forEach((b) => b.classList.remove("is-on"));
        for (let i = 0; i < v; i++) all[i].classList.add("is-on");
        submitBtn.disabled = !ReviewUI.state.currentScore;
    });
}

function wireReviewSubmit() {
    const submitBtn = ReviewUI.qs("#review-submit");
    if (!submitBtn) return;
    submitBtn.addEventListener("click", async () => {
        if (!ReviewUI.state.currentScore) return;
        const fd = new FormData();
        fd.append("_token", ReviewUI.csrf());
        fd.append("score", String(ReviewUI.state.currentScore));
        submitBtn.disabled = true;
        try {
            const res = await fetch(ReviewUI.routes.review_store, {
                method: "POST",
                headers: { Accept: "application/json" },
                credentials: "same-origin",
                body: fd,
            });
            if (res.redirected) {
                window.location.href = res.url;
                return;
            }
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                window.location.href = "/";
            } else {
                alert(data.message || "評価の送信に失敗しました。");
                submitBtn.disabled = false;
            }
        } catch {
            alert("通信エラーが発生しました。");
            submitBtn.disabled = false;
        }
    });
}

function wireReviewOpenClose() {
    const finishBtn = document.querySelector(".trade-finish");
    const modal = ReviewUI.qs("#review-modal");
    if (!modal) return;

    modal.addEventListener("click", (e) => {
        if (e.target.dataset.close === "1") closeReviewModal();
    });
    if (finishBtn) {
        finishBtn.addEventListener("click", () => openReviewModal());
    }
}

document.addEventListener("DOMContentLoaded", () => {
    wireReviewStars();
    wireReviewSubmit();
    wireReviewOpenClose();
    if (ReviewUI.info?.review?.should_prompt) {
        openReviewModal();
    }
});
