import { test } from '@playwright/test';

test('Open local site in WebKit browser', async ({ page }) => {
	await page.goto('http://localhost'); // nginx 経由で Laravel にアクセス
	await page.pause();
});