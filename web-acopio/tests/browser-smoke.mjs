import { chromium, expect } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';

const base = process.env.MILKFLOW_URL || 'http://127.0.0.1:8011';
const roles = ['admin', 'acopiador', 'supervisor', 'produccion', 'despacho', 'productor'];
const report = { base, checkedAt: new Date().toISOString(), roles: [], authentication: [], errors: [] };
await mkdir('artifacts', { recursive: true });
const browser = await chromium.launch({ headless: true, executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
try {
    for (const role of roles) {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
        try {
            const page = await context.newPage();
            page.on('pageerror', error => report.errors.push(role + ': ' + error.message));
            expect((await page.goto(base + '/login')).status()).toBe(200);
            await page.locator('[name=login]').fill(role);
            await page.locator('[name=password]').fill(process.env.MILKFLOW_DEMO_PASSWORD || 'MilkFlow!2026');
            await Promise.all([
                page.waitForURL('**/' + role + '/dashboard'),
                page.getByRole('button', { name: 'Iniciar sesión', exact: true }).click(),
            ]);
            await expect(page.locator('.workspace')).toBeVisible();
            await page.screenshot({ path: 'artifacts/' + role + '-desktop.png', fullPage: true });
            const result = { role, dashboard: new URL(page.url()).pathname, links: [], forbiddenDashboards: [], forbiddenWrites: [] };
            const csrf = await page.locator('meta[name=csrf-token]').getAttribute('content');
            const links = await page.locator('.sidebar a').evaluateAll(nodes => nodes.map(node => node.getAttribute('href')));
            for (const link of links) {
                const response = await page.goto(new URL(link, base).href);
                expect(response.status(), role + ' ' + link).toBe(200);
                result.links.push({ path: link, status: response.status() });
            }
            for (const other of roles.filter(other => other !== role)) {
                const path = '/' + other + '/dashboard';
                const response = await page.goto(base + path);
                expect(response.status(), role + ' -> ' + other).toBe(403);
                result.forbiddenDashboards.push({ path, status: response.status() });
            }
            const forbiddenWrites = role === 'admin' ? ['/acopiador/entregas'] : ['/admin/producers'];
            if (role === 'productor') forbiddenWrites.push('/productor/entregas');
            if (role === 'acopiador') forbiddenWrites.push('/acopiador/producers');
            for (const path of forbiddenWrites) {
                const response = await context.request.post(base + path, {
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    data: {},
                });
                expect(response.status(), role + ' POST ' + path).toBe(403);
                result.forbiddenWrites.push({ path, status: response.status() });
            }
            await page.setViewportSize({ width: 390, height: 844 });
            await page.goto(base + '/' + role + '/dashboard');
            expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
            await page.screenshot({ path: 'artifacts/' + role + '-mobile.png', fullPage: true });
            await page.getByRole('button', { name: 'Abrir menú' }).click();
            await expect(page.locator('.sidebar')).toBeVisible();
            await page.getByRole('button', { name: 'Abrir menú' }).click();
            await Promise.all([page.waitForURL('**/login'), page.getByRole('button', { name: 'Salir', exact: true }).click()]);
            await page.goto(base + '/' + role + '/dashboard');
            await expect(page).toHaveURL(base + '/login');
            result.logout = 'passed';
            report.roles.push(result);
            console.log(role + ': login, redirección, ' + links.length + ' enlaces, 5 paneles ajenos 403, permisos POST, móvil y logout OK');
        } finally {
            await context.close();
        }
    }
    const context = await browser.newContext();
    try {
        const page = await context.newPage();
        page.on('pageerror', error => report.errors.push('auth: ' + error.message));
        for (const width of [1440, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            for (const path of ['/login', '/register', '/auth/forgot-password', '/reset-password/preview']) {
                expect((await page.goto(base + path)).status()).toBe(200);
                expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
                for (const input of await page.locator('.auth-password input').all()) {
                    await input.fill('Example!2026');
                    const button = input.locator('..').getByRole('button');
                    await button.click();
                    await expect(input).toHaveAttribute('type', 'text');
                    await expect(button).toHaveAttribute('aria-pressed', 'true');
                    await button.click();
                    await expect(input).toHaveAttribute('type', 'password');
                    await expect(input).toHaveValue('Example!2026');
                    await input.fill('');
                }
                await page.screenshot({ path: 'artifacts/auth-' + path.slice(1).replaceAll('/', '-') + '-' + width + '.png', fullPage: true });
                report.authentication.push({ path, width, status: 'passed' });
            }
        }
    } finally {
        await context.close();
    }
    expect(report.errors).toEqual([]);
} catch (error) {
    report.errors.push(error.message);
    throw error;
} finally {
    await browser.close();
    await writeFile('artifacts/browser-verification.json', JSON.stringify(report, null, 2) + '\n');
}
console.log('Verificación de navegador completada sin errores.');
