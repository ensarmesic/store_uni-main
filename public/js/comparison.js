(() => {
    const input = document.querySelector('#comparison-link');
    const button = document.querySelector('[data-copy-comparison]');
    const status = document.querySelector('[data-copy-status]');
    if (!input || !button || !status) return;
    button.hidden = false;
    input.addEventListener('click', () => input.select());
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Link je kopiran.';
        } catch {
            input.focus();
            input.select();
            status.textContent = 'Kopiraj označeni link ručno.';
        }
    });
})();
