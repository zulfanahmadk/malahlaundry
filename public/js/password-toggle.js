document.querySelectorAll('input[type="password"]').forEach((input) => {
    const wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    input.style.paddingRight = '3.25rem';
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.setAttribute('aria-label', 'Tampilkan password');
    toggle.setAttribute('aria-pressed', 'false');
    toggle.style.cssText = 'position:absolute;right:2px;top:50%;transform:translateY(-50%);width:44px;height:44px;border:0;background:transparent;color:#526477;cursor:pointer;display:grid;place-items:center;border-radius:8px';
    toggle.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="eye-slash" d="M3 3l18 18" style="display:none"/></svg>';
    toggle.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.querySelector('.eye-slash').style.display = visible ? '' : 'none';
    });
    wrapper.appendChild(toggle);
});
