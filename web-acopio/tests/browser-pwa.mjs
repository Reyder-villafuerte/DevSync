import { chromium, expect } from '@playwright/test';
import { writeFile } from 'node:fs/promises';

const base = process.env.MILKFLOW_URL || 'http://127.0.0.1:8011';
const browser = await chromium.launch({headless:true, executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const report = { checkedAt: new Date().toISOString(), checks: [], errors: [] };
try {
    const context = await browser.newContext();
    const page = await context.newPage();
    page.on('pageerror', error => report.errors.push(error.message));
    await page.goto(base + '/login');
    await page.evaluate(() => navigator.serviceWorker.ready);
    await page.waitForFunction(() => !!navigator.serviceWorker.controller);
    const manifest = await (await context.request.get(base + '/manifest.webmanifest')).json();
    expect(manifest.name).toBe('MilkFlow');
    for (const icon of manifest.icons) {
        const dimensions = await page.evaluate(async src => {
            const image = new Image();
            image.src = src;
            await image.decode();
            return image.naturalWidth + 'x' + image.naturalHeight;
        }, icon.src);
        expect(dimensions).toBe(icon.sizes);
    }
    report.checks.push('Manifest, iconos 192/512 y service worker activos');
    for (let attempt = 0; attempt < 3; attempt++) {
        await page.goto(base + '/login');
        await page.locator('[name=login]').fill('acopiador');
        await page.locator('[name=password]').fill(process.env.MILKFLOW_DEMO_PASSWORD || 'MilkFlow!2026');
        const responsePromise = page.waitForResponse(response =>
            response.request().method() === 'POST' && new URL(response.url()).pathname === '/login');
        await page.getByRole('button',{name:'Iniciar sesión',exact:true}).click();
        const response = await responsePromise;
        if (response.status() === 429 && attempt < 2) {
            const seconds = Math.min(60, Math.max(1, Number(response.headers()['retry-after']) || 60));
            console.log('Límite de acceso activo: la prueba espera ' + seconds + ' segundos.');
            await page.waitForTimeout(seconds * 1000);
            continue;
        }
        expect(response.status()).toBe(302);
        await expect(page).toHaveURL(base + '/acopiador/dashboard');
        break;
    }
    await page.goto(base + '/acopiador/offline');
    const userId = await page.locator('#offline-app').getAttribute('data-user');
    const key = 'milkflow.entregas.' + userId;
    const producer = await page.locator('[name=productor_id] option').nth(1).getAttribute('value');
    await context.setOffline(true);
    await page.locator('[name=productor_id]').selectOption(producer);
    await page.locator('[name=litros]').fill('1.125');
    await page.getByRole('button', {name:'GUARDAR EN EL DISPOSITIVO',exact:true}).click();
    await expect(page.locator('#offline-status')).toHaveText('1 registros pendientes');
    const saved = await page.evaluate(key => JSON.parse(localStorage.getItem(key)), key);
    expect(saved).toHaveLength(1);
    expect(saved[0].estado).toBe('PENDIENTE');
    expect(saved[0].litros).toBe('1.125');
    report.checks.push('Recolección persistida sin red');
    await page.reload();
    await expect(page.getByRole('heading', {name:'Estás sin conexión'})).toBeVisible();
    expect(await page.evaluate(key => JSON.parse(localStorage.getItem(key))[0].uuid, key)).toBe(saved[0].uuid);
    report.checks.push('Recarga sin conexión muestra alternativa y conserva registros');

    // Simulated transport: no sample delivery is written to the working database.
    const attempts = [];
    await context.route('**/acopiador/sync/enviar', async route => {
        attempts.push(route.request().postDataJSON());
        await route.fulfill({
            status: attempts.length === 1 ? 503 : 201,
            contentType:'application/json',
            body: JSON.stringify(attempts.length === 1 ? {message:'Error temporal de prueba'} : {data:{uuid:saved[0].uuid}}),
        });
    });
    await context.setOffline(false);
    await page.goto(base + '/acopiador/offline');
    await expect(page.locator('#offline-records')).toContainText('ERROR');
    await page.getByRole('button', {name:'SINCRONIZAR AHORA',exact:true}).click();
    await expect(page.locator('#offline-status')).toHaveText('Sin registros pendientes');
    expect(attempts).toHaveLength(2);
    expect(attempts.map(attempt => attempt.uuid)).toEqual([saved[0].uuid, saved[0].uuid]);
    report.checks.push('Reintento conserva UUID y confirma envío (transporte simulado)');

    const cachedPaths = await page.evaluate(async () => {
        const paths = [];
        for (const name of await caches.keys()) {
            for (const request of await (await caches.open(name)).keys()) paths.push(new URL(request.url).pathname);
        }
        return paths;
    });
    expect(cachedPaths).toEqual(expect.arrayContaining(['/offline.html', '/offline.css']));
    expect(cachedPaths.every(path => ['/offline.html','/offline.css','/theme.js','/theme.css','/manifest.webmanifest','/icons/milkflow-192.png','/icons/milkflow-512.png'].includes(path))).toBe(true);
    report.checks.push('Caché contiene solo archivos públicos; sin paneles, sesiones ni tokens');

    await page.evaluate(key => localStorage.setItem(key, '{damaged'), key);
    await page.reload();
    await expect(page.locator('#offline-status')).toContainText('No se pudieron leer los registros');
    await expect(page.getByRole('button',{name:'GUARDAR EN EL DISPOSITIVO',exact:true})).toBeDisabled();
    expect(await page.evaluate(key => localStorage.getItem(key), key)).toBe('{damaged');
    report.checks.push('Almacenamiento dañado no se sobrescribe ni rompe JavaScript');
    expect(report.errors).toEqual([]);
    await context.close();
    console.log(report.checks.join('\n'));
} catch (error) {
    report.errors.push(error.message);
    throw error;
} finally {
    await browser.close();
    await writeFile('artifacts/pwa-verification.json', JSON.stringify(report, null, 2) + '\n');
}
