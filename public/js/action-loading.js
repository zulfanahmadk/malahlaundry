'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.createElement('dialog');
    overlay.className = 'action-loading';
    overlay.setAttribute('aria-label', 'Proses sedang berjalan');
    overlay.innerHTML = '<div class="action-loading-card" role="status" aria-live="polite"><span class="action-spinner" aria-hidden="true"></span><h2 data-loading-title>Mohon tunggu…</h2><p data-loading-note>Sedang memproses permintaan.</p><progress max="100" value="0" hidden aria-label="Progress upload APK"></progress><button type="button" class="btn btn-secondary" data-loading-dismiss hidden>Kembali ke halaman</button></div>';
    document.body.append(overlay);
    const title = overlay.querySelector('[data-loading-title]');
    const note = overlay.querySelector('[data-loading-note]');
    const progress = overlay.querySelector('progress');
    const dismiss = overlay.querySelector('[data-loading-dismiss]');
    let recovery;
    let uploading = false;
    const hide = () => {
        clearTimeout(recovery);
        overlay.close();
        document.body.removeAttribute('aria-busy');
    };
    const show = (message = 'Mohon tunggu…', detail = 'Sedang memproses permintaan.', upload = false) => {
        title.textContent = message;
        note.textContent = detail;
        progress.hidden = !upload;
        progress.value = 0;
        dismiss.hidden = true;
        document.body.setAttribute('aria-busy', 'true');
        if (!overlay.open) overlay.showModal();
        clearTimeout(recovery);
        if (!upload) recovery = setTimeout(() => { dismiss.hidden = false; }, 20000);
    };
    overlay.addEventListener('cancel', event => event.preventDefault());
    dismiss.addEventListener('click', hide);
    window.addEventListener('pageshow', hide);
    const isDownload = url => /\/(download|export)(\/|$)/.test(url.pathname);
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.getAttribute('href').startsWith('#') || link.hasAttribute('download') || link.hasAttribute('data-no-loading') || (link.target && link.target !== '_self')) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin || !['http:', 'https:'].includes(url.protocol) || isDownload(url) || (url.pathname === location.pathname && url.search === location.search && url.hash)) return;
        queueMicrotask(() => { if (!event.defaultPrevented) show('Memuat halaman…'); });
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (event.defaultPrevented || form.method === 'dialog' || form.hasAttribute('data-no-loading') || (form.target && form.target !== '_self')) return;
        if (!form.matches('[data-apk-upload]')) {
            if (isDownload(new URL(form.action, location.href))) return;
            queueMicrotask(() => { if (!event.defaultPrevented) show(form.method === 'get' ? 'Memuat data…' : 'Memproses…'); });
            return;
        }
        event.preventDefault();
        if (uploading) return;
        const input = form.querySelector('[name="apk"]');
        const error = form.querySelector('[data-upload-error]');
        const file = input.files[0];
        if (!file) return;
        if (!/\.apk$/i.test(file.name) || file.size > 100 * 1024 * 1024) {
            error.textContent = 'Pilih file APK dengan ukuran maksimal 100 MB.';
            error.hidden = false;
            return;
        }
        error.hidden = true;
        const data = new FormData(form);
        const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]')].filter(button => !button.disabled);
        uploading = true;
        buttons.forEach(button => { button.disabled = true; });
        show('Mengunggah APK · 0%', 'Jangan tutup halaman sampai APK selesai diterbitkan.', true);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.timeout = 30 * 60 * 1000;
        const fail = message => {
            uploading = false;
            buttons.forEach(button => { button.disabled = false; });
            hide();
            error.textContent = message;
            error.hidden = false;
            error.scrollIntoView({ block: 'center', behavior: 'smooth' });
        };
        xhr.upload.addEventListener('progress', event => {
            if (!event.lengthComputable) return;
            const percent = Math.min(100, Math.floor(event.loaded / event.total * 100));
            progress.value = percent;
            title.textContent = percent === 100 ? 'Upload 100% · Memproses APK…' : `Mengunggah APK · ${percent}%`;
            note.textContent = `${(event.loaded / 1048576).toFixed(1)} / ${(event.total / 1048576).toFixed(1)} MB terkirim. ${percent === 100 ? 'Server sedang memeriksa dan menerbitkan APK.' : 'Jangan tutup halaman ini.'}`;
        });
        xhr.addEventListener('load', () => {
            let result;
            try { result = JSON.parse(xhr.responseText); } catch { /* Handle non-JSON proxy and login responses below. */ }
            if (xhr.status >= 200 && xhr.status < 300 && result?.redirect) {
                title.textContent = 'APK berhasil diterbitkan';
                location.assign(result.redirect);
                return;
            }
            const messages = result?.errors ? Object.values(result.errors).flat().join(' ') : '';
            fail(messages || (xhr.status === 413 ? 'File terlalu besar untuk server. Periksa batas upload server.' : [401, 419].includes(xhr.status) || xhr.responseURL.includes('/login') ? 'Sesi login berakhir. Muat ulang halaman dan login kembali.' : result?.message || 'Upload belum dapat dikonfirmasi. Cek riwayat APK sebelum mencoba kembali.'));
        });
        xhr.addEventListener('error', () => fail('Koneksi terputus. Cek riwayat APK sebelum mencoba upload kembali.'));
        xhr.addEventListener('timeout', () => fail('Waktu upload habis. Cek koneksi dan riwayat APK sebelum mencoba kembali.'));
        xhr.addEventListener('abort', () => fail('Upload dibatalkan.'));
        xhr.send(data);
    });
});
