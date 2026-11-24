/* GEP Editor v9.23.0 · Aviso cuando dos personas editan la misma página.
 * v10.0.0: guardado seguro (sesión vencida, sin conexión o error del servidor).
 * - Muestra quién más tiene abierta la página (latido de WordPress cada 15 s).
 * - Antes de guardar pregunta al servidor si alguien guardó después de que se
 *   abrió el editor; si fue así, pide confirmar antes de reemplazar sus cambios. */
(function($){
    'use strict';
    var data = window.gepLockData;
    if (!data) return;
    var $form = $('form').filter(function(){ return $(this).find('input[name="gep_demo_admin_nonce"]').length > 0; }).first();
    if (!$form.length) return;
    var form = $form[0];
    var postId = parseInt($form.find('input[name="post_id"]').val(), 10) || 0;
    if (!postId) return;
    var loadedRev = (data.revs && data.revs[postId]) || '';
    var forced = false, checking = false;

    var $banner = $('<div class="gep-lock-banner" role="status" aria-live="polite" hidden></div>').insertBefore($form);

    function esc(v){ return $('<span>').text(String(v == null ? '' : v)).html(); }
    function names(list){
        if (list.length === 1) return list[0];
        return list.slice(0, -1).join(', ') + ' y ' + list[list.length - 1];
    }
    function render(state){
        if (!state) return;
        var html = '';
        if (state.changed) {
            html = '<span class="dashicons dashicons-warning"></span><div><strong>' + esc(state.by) + ' guardó cambios en esta página a las ' + esc(state.at) + '.</strong>' +
                '<span>Recarga para ver lo que cambió antes de seguir. Si guardas sin recargar, reemplazarás sus cambios.</span></div>' +
                '<button type="button" class="button gep-lock-reload">Recargar</button>';
            $banner.attr('class', 'gep-lock-banner is-changed');
        } else if (state.editors && state.editors.length) {
            html = '<span class="dashicons dashicons-groups"></span><div><strong>' + esc(names(state.editors)) + (state.editors.length > 1 ? ' también tienen' : ' también tiene') + ' abierta esta página.</strong>' +
                '<span>Si los dos guardan, el último guardado reemplaza al otro. Pónganse de acuerdo para guardar uno a la vez.</span></div>';
            $banner.attr('class', 'gep-lock-banner is-shared');
        }
        $banner.html(html).prop('hidden', html === '');
    }

    // Latido de WordPress.
    $(document).on('heartbeat-send.gepLock', function(e, payload){ payload.gep_lock = { post_id: postId, rev: loadedRev }; });
    $(document).on('heartbeat-tick.gepLock', function(e, res){ if (res && res.gep_lock) render(res.gep_lock); });
    if (window.wp && wp.heartbeat) { wp.heartbeat.interval(15); setTimeout(function(){ wp.heartbeat.connectNow(); }, 800); }

    function leave(){
        try {
            var fd = new FormData();
            fd.append('action', 'gep_lock_leave'); fd.append('nonce', data.nonce); fd.append('post_id', postId);
            if (navigator.sendBeacon) navigator.sendBeacon(data.ajaxUrl, fd);
        } catch (err) {}
    }
    window.addEventListener('pagehide', leave);

    $(document).on('click', '.gep-lock-reload', function(e){ e.preventDefault(); window.location.reload(); });

    // Ventana de confirmación dentro de la página.
    function ask(state, submitter){
        $('.gep-lock-modal').remove();
        var $m = $('<div class="gep-lock-modal" role="dialog" aria-modal="true" aria-labelledby="gep-lock-modal-title">' +
            '<div class="gep-lock-modal-box">' +
            '<h2 id="gep-lock-modal-title">' + esc(state.by) + ' guardó cambios mientras editabas</h2>' +
            '<p>Guardó esta página a las <strong>' + esc(state.at) + '</strong>, después de que la abriste. Si guardas ahora, tu versión reemplaza la suya completa.</p>' +
            '<p class="gep-lock-modal-note">Si te equivocas, sus cambios quedan en <strong>Historial de cambios</strong> y se pueden restaurar.</p>' +
            '<div class="gep-lock-modal-actions">' +
            '<button type="button" class="button gep-lock-cancel">Seguir editando</button>' +
            '<button type="button" class="button gep-lock-reload">Recargar sin guardar</button>' +
            '<button type="button" class="button button-primary gep-lock-force">Guardar igual</button>' +
            '</div></div></div>').appendTo('body');
        $m.find('.gep-lock-cancel').trigger('focus');
        $m.on('click', '.gep-lock-cancel', function(){ $m.remove(); });
        $m.on('keydown', function(e){ if (e.key === 'Escape') $m.remove(); });
        $m.on('click', '.gep-lock-force', function(){
            $m.remove();
            forced = true;
            resubmit(submitter);
        });
    }
    function resubmit(submitter){
        // v10.0.0: si una validación del editor cancela este envío, el próximo
        // «Guardar» vuelve a comprobar la sesión.
        setTimeout(function(){ forced = false; }, 0);
        if (typeof form.requestSubmit === 'function') {
            try { form.requestSubmit(submitter && submitter.form === form ? submitter : undefined); return; } catch (err) {}
        }
        $form.find('[type="submit"]').first().trigger('click');
    }

    // Fase de captura en window: corre antes que las validaciones del editor.
    window.addEventListener('submit', function(e){
        if (e.target !== form || forced) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        if (checking) return;
        checking = true;
        var submitter = e.submitter || null;
        check(submitter, true);
    }, true);

    /* v10.0.0 · Guardado seguro.
     * Antes, si la consulta fallaba (sesión vencida o sin Internet) el formulario
     * se enviaba igual y WordPress respondía «El enlace ha caducado»: se perdía
     * todo lo escrito. Ahora no se envía nada hasta confirmar que el guardado
     * va a funcionar; lo escrito sigue en pantalla. */
    function applyNonces(d){
        if (!d) return;
        if (d.lock_nonce) data.nonce = d.lock_nonce;
        if (d.save_nonce) $form.find('input[name="gep_demo_admin_nonce"]').val(d.save_nonce);
    }
    function check(submitter, canRefresh){
        checking = true;
        $.ajax({ url: data.ajaxUrl, type: 'POST', dataType: 'json', timeout: 20000,
                 data: { action: 'gep_lock_check', nonce: data.nonce, post_id: postId, rev: loadedRev } })
            .done(function(res){
                checking = false;
                if (!res || !res.success) { problem('server', submitter); return; }
                applyNonces(res.data);
                if (res.data && res.data.changed) { render(res.data); ask(res.data, submitter); return; }
                forced = true; resubmit(submitter);
            })
            .fail(function(xhr, status){
                if (!xhr || xhr.status === 0 || status === 'timeout') { checking = false; problem('offline', submitter); return; }
                if ((xhr.status === 400 || xhr.status === 403) && canRefresh) { refresh(submitter); return; }
                checking = false;
                problem(xhr.status === 400 || xhr.status === 403 ? 'session' : 'server', submitter, xhr.status);
            });
    }
    // La clave de la página venció pero la persona puede tener sesión (p. ej.
    // volvió a entrar en otra pestaña): se piden claves nuevas y se reintenta.
    function refresh(submitter){
        $.ajax({ url: data.ajaxUrl, type: 'POST', dataType: 'json', timeout: 20000, data: { action: 'gep_lock_refresh', post_id: postId } })
            .done(function(res){
                if (res && res.success) { applyNonces(res.data); check(submitter, false); return; }
                checking = false; problem('session', submitter);
            })
            .fail(function(xhr){ checking = false; problem(xhr && xhr.status === 0 ? 'offline' : 'session', submitter); });
    }
    function problem(kind, submitter, code){
        $('.gep-lock-modal').remove();
        var t = {
            session: { title: 'Tu sesión terminó', text: 'Por seguridad, WordPress cerró tu sesión. <strong>No se guardó nada y tus cambios siguen en esta pantalla.</strong>', steps: 'Pulsa «Iniciar sesión»: se abre en otra pestaña. Cuando entres, vuelve aquí y pulsa «Ya entré, guardar». No cierres ni recargues esta pestaña.' },
            offline: { title: 'No hay conexión', text: 'No se pudo llegar al servidor. <strong>No se guardó nada y tus cambios siguen en esta pantalla.</strong>', steps: 'Revisa tu Internet y pulsa «Reintentar». No cierres ni recargues esta pestaña.' },
            server:  { title: 'El servidor no respondió', text: 'El guardado no se pudo comprobar' + (code ? ' (error ' + esc(code) + ')' : '') + '. <strong>No se guardó nada y tus cambios siguen en esta pantalla.</strong>', steps: 'Espera un momento y pulsa «Reintentar». Si se repite, avisa al administrador.' }
        }[kind];
        var login = kind === 'session' ? '<a class="button" href="' + esc(data.loginUrl || 'wp-login.php') + '" target="_blank" rel="noopener">Iniciar sesión</a>' : '';
        var $m = $('<div class="gep-lock-modal gep-save-problem is-' + kind + '" role="alertdialog" aria-modal="true" aria-labelledby="gep-save-problem-title">' +
            '<div class="gep-lock-modal-box">' +
            '<h2 id="gep-save-problem-title"><span class="dashicons dashicons-warning"></span> ' + t.title + '</h2>' +
            '<p>' + t.text + '</p><p class="gep-lock-modal-note">' + t.steps + '</p>' +
            '<div class="gep-lock-modal-actions">' +
            '<button type="button" class="button gep-lock-cancel">Seguir editando</button>' + login +
            '<button type="button" class="button button-primary gep-save-retry">' + (kind === 'session' ? 'Ya entré, guardar' : 'Reintentar') + '</button>' +
            '</div></div></div>').appendTo('body');
        $m.find('.gep-save-retry').trigger('focus');
        $m.on('click', '.gep-lock-cancel', function(){ $m.remove(); });
        $m.on('keydown', function(e){ if (e.key === 'Escape') $m.remove(); });
        $m.on('click', '.gep-save-retry', function(){ $m.remove(); if (!checking) check(submitter, true); });
    }
})(jQuery);
