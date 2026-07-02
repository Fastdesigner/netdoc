'use strict';

/* Löschen etc. bestätigen. */
document.addEventListener('click', function (ev) {
    const el = ev.target.closest('[data-confirm]');
    if (el && !window.confirm(el.getAttribute('data-confirm'))) {
        ev.preventDefault();
    }
});

/* Passwortfeld im Formular ein-/ausblenden. */
document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('.togglepw');
    if (!btn) return;
    const input = btn.parentElement.querySelector('input');
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'verbergen' : 'zeigen';
});

/* Gespeichertes Passwort per XHR entschlüsseln und anzeigen. */
document.addEventListener('click', async function (ev) {
    const btn = ev.target.closest('.reveal');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const code = document.querySelector('.secret[data-id="' + id + '"]');
    if (!code) return;

    if (code.dataset.shown === '1') {
        code.textContent = '••••••••';
        code.dataset.shown = '0';
        btn.textContent = 'anzeigen';
        return;
    }
    btn.textContent = '…';
    try {
        const res = await fetch('index.php?r=cred.reveal&id=' + encodeURIComponent(id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        code.textContent = data.secret || '(leer)';
        code.dataset.shown = '1';
        btn.textContent = 'verbergen';
    } catch (e) {
        btn.textContent = 'Fehler';
    }
});

/* Klick auf einen Wert kopiert ihn in die Zwischenablage. */
document.addEventListener('click', function (ev) {
    const el = ev.target.closest('.copy, .secret[data-shown="1"]');
    if (!el || !navigator.clipboard) return;
    const val = el.getAttribute('data-copy') || el.textContent;
    navigator.clipboard.writeText(val).then(function () {
        const old = el.style.color;
        el.style.color = 'var(--ok)';
        setTimeout(function () { el.style.color = old; }, 400);
    });
});
