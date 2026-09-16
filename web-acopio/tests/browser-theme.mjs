import { chromium, expect } from '@playwright/test';
import { writeFile } from 'node:fs/promises';
const base=process.env.MILKFLOW_URL || 'http://127.0.0.1:8011';
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const report={pages:[],errors:[],checks:[]};
const colors={light:'rgb(244, 245, 248)',dark:'rgb(17, 21, 28)'};
async function check(page,mode){
    await expect(page.locator('html')).toHaveAttribute('data-theme',mode);
    await expect(page.getByLabel('Apariencia')).toHaveValue(mode);
    const background=await page.locator('body').evaluate(node=>getComputedStyle(node).backgroundColor);
    if(mode==='dark')expect(background).toBe(colors.dark);
    expect(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth)).toBe(false);
}
async function login(page,role){
    for(let n=0;n<3;n++){
        await page.goto(base+'/login');
        await page.locator('[name=login]').fill(role);
        await page.locator('[name=password]').fill(process.env.MILKFLOW_DEMO_PASSWORD || 'MilkFlow!2026');
        const responsePromise=page.waitForResponse(r=>r.request().method()==='POST'&&new URL(r.url()).pathname==='/login');
        await page.getByRole('button',{name:'Iniciar sesión',exact:true}).click();
        const response=await responsePromise;
        if(response.status()===429&&n<2){
            await page.waitForTimeout(Math.min(60,Number(response.headers()['retry-after'])||60)*1000);
            continue;
        }
        expect(response.status()).toBe(302);
        await expect(page).toHaveURL(base+'/'+role+'/dashboard');
        break;
    }
}
try{
    const context=await browser.newContext();
    const page=await context.newPage();
    page.on('pageerror',e=>report.errors.push(e.message));
    await page.goto(base+'/login');
    await check(page,'light');
    for(const mode of ['dark','light']){
        await page.getByLabel('Apariencia').selectOption(mode);
        for(const path of ['/login','/register','/auth/forgot-password','/reset-password/preview','/registro/pendiente']){
            await page.goto(base+path);
            await check(page,mode);
            await page.reload();
            await check(page,mode);
            report.pages.push({path,mode});
        }
    }
    await page.getByLabel('Apariencia').selectOption('dark');
    const other=await context.newPage();await other.goto(base+'/login');
    await other.getByLabel('Apariencia').selectOption('light');await check(page,'light');
    await other.close();
    report.checks.push('Persistencia al navegar/recargar y sincronización entre pestañas');
    for(const role of ['admin','acopiador','supervisor','produccion','despacho','productor']){
        await page.setViewportSize({width:1440,height:1000});
        await login(page,role);
        const links=await page.locator('.sidebar a').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href')));
        const forms={admin:['/admin/producers/create','/admin/settings'],acopiador:['/acopiador/entregas/create'],supervisor:['/supervisor/calidad/create'],produccion:['/produccion/lotes/create'],despacho:['/despacho/ventas/create'],productor:['/productor/rotaciones/create']};
        for(const mode of ['dark','light']){
            await page.getByLabel('Apariencia').selectOption(mode);
            for(const path of [...new Set([...links,...forms[role]])]){
                expect((await page.goto(base+path)).status()).toBe(200);
                await check(page,mode);
                report.pages.push({path,mode});
            }
            await page.goto(base+'/'+role+'/dashboard');
            await page.screenshot({path:'artifacts/theme-'+role+'-'+mode+'-desktop.png',fullPage:true});
            for(const width of [390,320]){
                await page.setViewportSize({width,height:844});
                await check(page,mode);
                await page.getByRole('button',{name:'Abrir menú'}).click();
                await expect(page.locator('.sidebar')).toBeVisible();
                await page.getByRole('button',{name:'Abrir menú'}).click();
                await page.screenshot({path:'artifacts/theme-'+role+'-'+mode+'-'+width+'.png',fullPage:true});
            }
            await page.setViewportSize({width:1440,height:1000});
            const denied=role==='admin'?'productor':'admin';
            expect((await page.goto(base+'/'+denied+'/dashboard')).status()).toBe(403);
            await check(page,mode);
            await page.goto(base+'/'+role+'/dashboard');
        }
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        console.log(role+': ambos temas, listas, formularios, 403 y móvil OK');
    }
    await page.getByLabel('Apariencia').selectOption('dark');
    await page.setViewportSize({width:390,height:844});
    await page.goto(base+'/register');
    await page.screenshot({path:'artifacts/theme-register-dark.png',fullPage:true});
    await page.evaluate(()=>navigator.serviceWorker.ready);
    await page.waitForFunction(()=>!!navigator.serviceWorker.controller);
    await context.setOffline(true);
    await page.reload();
    await expect(page.getByRole('heading',{name:'Estás sin conexión'})).toBeVisible();
    await check(page,'dark');
    await page.getByLabel('Apariencia').selectOption('light');await check(page,'light');
    await context.setOffline(false);
    report.checks.push('Cambio de tema sin red en la PWA');
    await context.close();
    const restricted=await browser.newContext();
    await restricted.addInitScript(()=>{Storage.prototype.getItem=()=>{throw Error('blocked')};Storage.prototype.setItem=()=>{throw Error('blocked')};});
    const restrictedPage=await restricted.newPage();
    restrictedPage.on('pageerror',e=>report.errors.push(e.message));
    await restrictedPage.goto(base+'/login');
    await restrictedPage.getByLabel('Apariencia').selectOption('dark');await check(restrictedPage,'dark');
    report.checks.push('Cambio de tema funcional con almacenamiento bloqueado');
    await restricted.close();
    expect(report.errors).toEqual([]);
} catch(error){report.errors.push(error.message);throw error;}
finally{await browser.close();await writeFile('artifacts/theme-verification.json',JSON.stringify(report,null,2));}
