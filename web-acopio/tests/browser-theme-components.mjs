import { chromium, expect } from '@playwright/test';
import { readFile, writeFile } from 'node:fs/promises';
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const results=[];
try{
    const page=await browser.newPage({viewport:{width:390,height:844}});
    await page.goto((process.env.MILKFLOW_URL||'http://127.0.0.1:8011')+'/register');
    const fixture=(await readFile('resources/views/components/modal.blade.php','utf8')).split('\n').slice(1).join('\n')
        .replaceAll('{{ $id }}','theme-modal').replaceAll('{{ $title }}','Confirmación').replaceAll('{{ $slot }}','Revisa los datos antes de continuar.');
    for(const mode of ['light','dark']){
        await page.getByLabel('Apariencia').selectOption(mode);
        await page.evaluate(html=>{
            document.getElementById('theme-modal')?.remove();
            document.body.insertAdjacentHTML('beforeend',html);
            const modal=document.getElementById('theme-modal');
            modal.classList.add('show');modal.style.display='block';modal.removeAttribute('aria-hidden');
        },fixture);
        await expect(page.locator('.modal-content')).toBeVisible();
        const samples=await page.evaluate(()=>{
            const pairs=[['.modal-content','.modal-content'],['.auth-help','.auth-form'],['[name=email]','[name=email]']];
            return pairs.map(([text,background])=>({
                text:getComputedStyle(document.querySelector(text)).color,
                background:getComputedStyle(document.querySelector(background)).backgroundColor,
                selector:text
            }));
        });
        if(mode==='dark'){
            const luminance=color=>{
                const rgb=color.match(/\d+/g).slice(0,3).map(Number).map(v=>v/255).map(v=>v<=0.04045?v/12.92:((v+0.055)/1.055)**2.4);
                return rgb[0]*0.2126+rgb[1]*0.7152+rgb[2]*0.0722;
            };
            for(const sample of samples){
                const values=[luminance(sample.text),luminance(sample.background)].sort((a,b)=>b-a);
                sample.contrast=(values[0]+0.05)/(values[1]+0.05);
                expect(sample.contrast,sample.selector).toBeGreaterThanOrEqual(4.5);
            }
            await expect(page.locator('.btn-close')).toHaveCSS('filter',/invert/);
        }
        await page.screenshot({path:'artifacts/theme-modal-'+mode+'.png',fullPage:true});
        await page.locator('#theme-modal').evaluate(node=>node.remove());
        results.push({mode,samples});
    }
    console.log('Modal claro/oscuro y contraste de texto, ayuda y campos oscuros: OK');
}finally{
    await browser.close();
    await writeFile('artifacts/theme-components.json',JSON.stringify(results,null,2));
}
