if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(error => {
            console.warn('MilkFlow: no se pudo activar el modo de navegación sin conexión.', error);
        });
    });
}
