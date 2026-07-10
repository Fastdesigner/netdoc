'use strict';

function setButtonLabel(button, label) {
    const target = button.querySelector('.button__label');
    if (target) target.textContent = label;
}

document.addEventListener('click', function (event) {
    const target = event.target.closest('[data-confirm]');
    if (target && !window.confirm(target.getAttribute('data-confirm'))) event.preventDefault();
});

document.addEventListener('click', function (event) {
    const button = event.target.closest('.notification__dismiss');
    if (button) button.closest('.notification').remove();
});

document.addEventListener('click', function (event) {
    const button = event.target.closest('.toggle-password');
    if (!button) return;
    const input = button.parentElement.querySelector('input');
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.classList.toggle('is-active', show);
    button.setAttribute('aria-pressed', show ? 'true' : 'false');
    setButtonLabel(button, show ? 'Verbergen' : 'Anzeigen');
});

document.addEventListener('click', async function (event) {
    const button = event.target.closest('.reveal');
    if (!button) return;
    const id = button.getAttribute('data-id');
    const code = document.querySelector('.secret[data-id="' + id + '"]');
    if (!code) return;

    if (code.dataset.shown === '1') {
        code.textContent = '••••••••';
        code.dataset.shown = '0';
        button.classList.remove('is-active');
        setButtonLabel(button, 'Anzeigen');
        return;
    }

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    setButtonLabel(button, 'Lädt');
    try {
        const response = await fetch('index.php?r=cred.reveal&id=' + encodeURIComponent(id), {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const data = await response.json();
        code.textContent = data.secret || '(leer)';
        code.dataset.shown = '1';
        button.classList.add('is-active');
        setButtonLabel(button, 'Verbergen');
    } catch (error) {
        setButtonLabel(button, 'Fehler');
    } finally {
        button.disabled = false;
        button.removeAttribute('aria-busy');
    }
});

document.addEventListener('click', function (event) {
    const target = event.target.closest('.copy-action, .secret[data-shown="1"]');
    if (!target || !navigator.clipboard) return;
    const value = target.getAttribute('data-copy') || target.textContent;
    navigator.clipboard.writeText(value).then(function () {
        target.classList.add('is-success');
        if (target.classList.contains('copy-action')) setButtonLabel(target, 'Kopiert');
        setTimeout(function () {
            target.classList.remove('is-success');
            if (target.classList.contains('copy-action')) setButtonLabel(target, 'Benutzername kopieren');
        }, 1200);
    });
});

document.addEventListener('click', function (event) {
    document.querySelectorAll('.mobile-nav[open]').forEach(function (menu) {
        if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
});
