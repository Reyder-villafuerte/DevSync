import 'bootstrap/js/dist/offcanvas';
const media=matchMedia('(prefers-color-scheme: dark)');
let mode='sistema';try{mode=localStorage.getItem('huata-theme')||mode}catch(e){}
function apply(){document.documentElement.dataset.bsTheme=mode==='oscuro'||mode==='sistema'&&media.matches?'dark':'light';document.querySelectorAll('[data-huata-theme]').forEach(s=>s.value=mode)}
document.querySelectorAll('[data-huata-theme]').forEach(s=>s.addEventListener('change',()=>{mode=s.value;try{localStorage.setItem('huata-theme',mode)}catch(e){}apply()}));
media.addEventListener('change',apply);apply();
