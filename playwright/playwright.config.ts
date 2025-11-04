import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
	testDir: '.',
	use: { baseURL: 'http://localhost' },
	projects: [
		{
			name: 'webkit-desktop',
			use: {
				browserName: 'webkit',
				...devices['Desktop Safari'],
				viewport: { width: 1366, height: 900 },
			},
		},
		{
			name: 'webkit-ipad',
			use: { browserName: 'webkit', ...devices['iPad (gen 7)'] },
		},
	],
});
