/* GEP Editor · interruptor de modo claro / oscuro.
   Añade el botón a la barra superior del editor y a las cabeceras de las
   demás pantallas del plugin, y guarda la preferencia de cada persona. */
(function () {
    'use strict';

    var data = window.gepThemeData || {};
    var body = document.body;

    function isDark() {
        return body.classList.contains('gep-theme-dark');
    }

    function label(button) {
        var dark = isDark();
        var icon = button.querySelector('.dashicons');
        var text = button.querySelector('.gep-theme-toggle-text');
        if (icon) {
            icon.className = 'dashicons ' + (dark ? 'dashicons-visibility' : 'dashicons-admin-appearance');
        }
        if (text) {
            text.textContent = dark ? 'Modo claro' : 'Modo oscuro';
        }
        button.setAttribute('aria-pressed', dark ? 'true' : 'false');
        button.title = dark ? 'Volver al modo claro' : 'Cambiar a modo oscuro';
    }

    function save(theme) {
        if (!data.ajaxUrl || !data.nonce || !window.fetch) {
            return;
        }
        var form = new FormData();
        form.append('action', 'gep_save_theme');
        form.append('nonce', data.nonce);
        form.append('theme', theme);
        fetch(data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: form })['catch'](function () {});
    }

    function toggle(button) {
        body.classList.toggle('gep-theme-dark');
        var theme = isDark() ? 'dark' : 'light';
        try {
            window.localStorage.setItem('gepEditorTheme', theme);
        } catch (e) {}
        label(button);
        save(theme);
    }

    function build() {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'gep-theme-toggle';
        button.innerHTML = '<span class="dashicons" aria-hidden="true"></span><span class="gep-theme-toggle-text"></span>';
        label(button);
        button.addEventListener('click', function () {
            toggle(button);
        });
        return button;
    }

    function place() {
        var topbar = document.querySelector('.gep-top-utility');
        if (topbar) {
            topbar.insertBefore(build(), topbar.firstChild);
            return;
        }
        var heading = document.querySelector('.gep-ayuda-hero-actions, .gep-hx-title-actions, .gep-hx-heading-actions');
        if (heading) {
            heading.insertBefore(build(), heading.firstChild);
            return;
        }
        // Resto de pantallas: junto al botón "Ir al editor" de la cabecera.
        var link = document.querySelector('.gep-hx-heading .button-primary, .gep-alt-wrap .button-primary');
        if (link && link.parentNode) {
            link.parentNode.insertBefore(build(), link);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', place);
    } else {
        place();
    }
})();

/* v9.23.6 · Si la barra de WordPress ("Hola, …") está visible, los botones de
 * arriba a la derecha (Modo oscuro, Carpetas, Ayuda) bajan para no quedar tapados. */
(function () {
    'use strict';
    function adjust() {
        var u = document.querySelector('.gep-top-utility');
        if (!u) return;
        var bar = document.getElementById('wpadminbar'), h = 0;
        if (bar) {
            var cs = window.getComputedStyle(bar), r = bar.getBoundingClientRect();
            if (cs.display !== 'none' && cs.visibility !== 'hidden' && r.height > 0 && r.bottom > 0) h = Math.round(r.bottom);
        }
        if (h) u.style.setProperty('top', (h + 10) + 'px', 'important');
        else u.style.removeProperty('top');
    }
    function start() {
        adjust();
        window.addEventListener('resize', adjust);
        window.addEventListener('scroll', adjust, { passive: true });
        if (window.MutationObserver) new MutationObserver(function () { window.requestAnimationFrame(adjust); }).observe(document.body, { attributes: true, attributeFilter: ['class', 'style'] });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
