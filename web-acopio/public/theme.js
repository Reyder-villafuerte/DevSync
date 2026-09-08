(() => {
    const key = 'milkflow.theme';
    const root = document.documentElement;
    function apply(theme) {
        const value = theme === 'dark' ? 'dark' : 'light';
        root.dataset.theme = value;
        root.dataset.bsTheme = value;
        root.style.colorScheme = value;
        document.querySelectorAll('[data-theme-selector]').forEach(select => { select.value = value; });
    }
    try { apply(localStorage.getItem(key)); } catch { apply('light'); }
    document.addEventListener('change', event => {
        if (!event.target.matches('[data-theme-selector]')) return;
        apply(event.target.value);
        try { localStorage.setItem(key, root.dataset.theme); } catch { /* Theme still works for this page. */ }
    });
    window.addEventListener('storage', event => {
        if (event.key === key || event.key === null) apply(event.newValue);
    });
    document.addEventListener('DOMContentLoaded', () => {
        apply(root.dataset.theme);
        const header = document.querySelector('.mf-topbar');
        if (header && 'ResizeObserver' in window) {
            new ResizeObserver(() => root.style.setProperty('--mf-header-height', header.offsetHeight + 'px')).observe(header);
        }
    });
})();
