(() => {
    const panel = document.querySelector('#catalog-filters');
    const trigger = document.querySelector('[data-filter-open]');
    const backdrop = document.querySelector('[data-filter-backdrop]');
    if (!panel || !trigger || !backdrop) return;
    document.documentElement.classList.add('shopping-js');
    let previousFocus;
    let previousOverflow;
    const close = () => {
        if (!panel.classList.contains('is-open')) return;
        panel.classList.remove('is-open');
        panel.removeAttribute('role');
        panel.removeAttribute('aria-modal');
        panel.removeAttribute('aria-label');
        backdrop.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = previousOverflow;
        previousFocus?.focus();
    };
    trigger.addEventListener('click', () => {
        previousFocus = document.activeElement;
        previousOverflow = document.body.style.overflow;
        panel.classList.add('is-open');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('aria-label', 'Filteri kataloga');
        backdrop.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        panel.querySelector('[data-filter-close]').focus();
    });
    panel.querySelector('[data-filter-close]').addEventListener('click', close);
    backdrop.addEventListener('click', close);
    document.addEventListener('keydown', event => {
        if (!panel.classList.contains('is-open')) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key === 'Tab') {
            const items = [...panel.querySelectorAll('button,a,input,select')].filter(el => el.type !== 'hidden' && !el.disabled && el.getClientRects().length);
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    window.matchMedia('(max-width:780px)').addEventListener('change', event => { if (!event.matches) close(); });
})();
