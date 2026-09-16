const root = document.getElementById('offline-app');
if (root) {
    const key = `milkflow.entregas.${root.dataset.user}`;
    let entries = [];
    let storageReady = true;
    try {
        entries = JSON.parse(localStorage.getItem(key) || '[]');
        if (!Array.isArray(entries) || entries.some(entry => !entry || typeof entry !== 'object' || !entry.uuid)) {
            throw new Error('Formato de almacenamiento no válido');
        }
    } catch {
        storageReady = false;
    }
    let sending = false;
    const status = document.getElementById('offline-status');
    const render = () => {
        const pending = entries.filter(e => e.estado !== 'ENVIADO').length;
        status.textContent = pending ? `${pending} registros pendientes` : 'Sin registros pendientes';
        const list = document.getElementById('offline-records'); list.replaceChildren();
        for (const e of entries.slice().reverse()) { const item = document.createElement('p'); item.textContent = `${e.litros} L · ${e.estado} · ${e.uuid}${e.error ? ' · ' + e.error : ''}`; list.append(item); }
    };
    const save = () => { localStorage.setItem(key, JSON.stringify(entries)); render(); };
    const sync = async () => {
        if (!storageReady || sending || !navigator.onLine) return;
        sending = true;
        try {
            for (const entry of entries.filter(e => e.estado !== 'ENVIADO')) {
                try {
                    const response = await fetch('/acopiador/sync/enviar', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}, body:JSON.stringify(entry) });
                    if (!response.ok) { const data=await response.json().catch(()=>({})); throw new Error([401,419].includes(response.status)?'La sesión venció. Inicia sesión con tu misma cuenta.':Object.values(data.errors||{}).flat().join(' ')||data.message||'No se pudo enviar.'); }
                    entry.estado='ENVIADO'; entry.error=null;
                } catch (error) { entry.estado='ERROR'; entry.error=error.message; }
                save();
            }
        } finally { sending=false; }
    };
    document.getElementById('offline-form').addEventListener('submit', event => {
        event.preventDefault(); if (!storageReady) return; const form=event.currentTarget;
        const data=Object.fromEntries(new FormData(form));
        entries.push({...data,uuid:crypto.randomUUID(),tipo:'RECOGIDA',estado:'PENDIENTE'});
        try { save(); form.reset(); sync(); } catch { entries.pop(); status.textContent='No se pudo guardar en este dispositivo. No cierres la pantalla.'; }
    });
    document.getElementById('sync-now').addEventListener('click',sync);
    window.addEventListener('online',sync);
    if (storageReady) {
        render();
        sync();
    } else {
        status.textContent = 'No se pudieron leer los registros del dispositivo. No borres los datos del navegador; solicita ayuda para recuperarlos.';
        root.querySelectorAll('button').forEach(button => { button.disabled = true; });
    }
}
