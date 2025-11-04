/// <reference types="node" />
import { test, expect } from '@playwright/test';

const EMAIL = process.env.PW_EMAIL ?? 'user1@example.com';
const PASS = process.env.PW_PASS ?? 'user1234';
const ROOM_ID = process.env.PW_ROOM_ID ?? '1';

test.use({
	browserName: 'webkit',
	viewport: { width: 1366, height: 900 },
});

test('Safari desktop -> review modal via button click', async ({ page }) => {
	// ログイン
	await page.goto('http://localhost/login');
	await page.getByLabel('メールアドレス').fill(EMAIL);
	await page.getByLabel('パスワード').fill(PASS);
	await page.getByRole('button', { name: 'ログイン' }).click();
	await expect(page).toHaveURL(/localhost/);

	// 取引チャットへ
	await page.goto(`http://localhost/trades/${ROOM_ID}`);
	await page.waitForLoadState('domcontentloaded');

	// JS（review-modal.js）が読み込まれるまで少し待機
	await page.waitForTimeout(1000);

	// 「取引を完了する」ボタンが出てくるまで待ってクリック
	await page.waitForSelector('.trade-finish', { state: 'visible', timeout: 5000 });
	await page.click('.trade-finish');

	// モーダルが開くのを確認（.is-open 付与）
	await page.waitForSelector('#review-modal.is-open', { timeout: 5000 });

	// ここで停止してSafari相当の見た目を確認
	await page.pause();
});
