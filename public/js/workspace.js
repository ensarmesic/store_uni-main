document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.hidden = false;
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Sakrij' : 'Prikaži';
        button.setAttribute('aria-pressed', String(visible));
    });
});
const newPassword = document.querySelector('[data-new-password]');
if (newPassword) {
    newPassword.addEventListener('input', () => {
        const length = newPassword.value.length;
        document.querySelector('[data-password-meter]').style.width = Math.min(100, length / 8 * 100) + '%';
        document.querySelector('[data-password-hint]').textContent = length >= 8 ? 'Minimalna dužina ispunjena. Duža, jedinstvena lozinka je bolji izbor.' : `Još ${8 - length} znakova do minimalne dužine.`;
    });
}
document.querySelectorAll('[data-alert-form]').forEach(form => {
    const type = form.querySelector('[name=type]');
    const price = form.querySelector('[name=target_price]');
    const field = price.closest('.tool-field');
    const update = () => {
        const isPrice = type.value === 'price';
        field.hidden = !isPrice;
        price.disabled = !isPrice;
        price.required = isPrice;
    };
    type.addEventListener('change', update);
    update();
});
const upload = document.querySelector('[data-upload]');
if (upload) {
    const preview = document.querySelector('[data-preview]');
    const filename = document.querySelector('[data-filename]');
    const zone = upload.closest('.upload-zone');
    let objectUrl;
    const showFile = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        preview.hidden = true;
        const file = upload.files[0];
        upload.setCustomValidity('');
        filename.textContent = file ? file.name : '';
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            upload.setCustomValidity('Fotografija je prevelika. Odaberi datoteku do 10 MB.');
            filename.textContent = 'Fotografija je prevelika. Maksimalna veličina je 10 MB.';
            upload.reportValidity();
            return;
        }
        if (file.type.startsWith('image/')) {
            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.hidden = false;
        }
    };
    upload.addEventListener('change', showFile);
    zone.addEventListener('dragover', event => { event.preventDefault(); zone.classList.add('is-dragging'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('is-dragging'));
    zone.addEventListener('drop', event => {
        event.preventDefault(); zone.classList.remove('is-dragging');
        if (event.dataTransfer.files.length) { upload.files = event.dataTransfer.files; showFile(); }
    });
}
document.querySelectorAll('form[data-pending]').forEach(form => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type=submit]');
        if (button) { button.disabled = true; button.textContent = form.dataset.pending; }
        form.setAttribute('aria-busy', 'true');
    });
});
window.addEventListener('pageshow', event => {
    if (event.persisted) window.location.reload();
});
