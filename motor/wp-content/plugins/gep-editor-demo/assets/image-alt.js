/* GEP Editor v9.21.0 · Descripción de imágenes: lista primero, editar una a la vez. */
(function($){
    'use strict';
    var $app = $('#gep-alt-app');
    if (!$app.length) return;
    var filter = 'used', page = '';

    function closeAll(){
        $app.find('.gep-alt-row.is-open').each(function(){
            var $row = $(this);
            $row.removeClass('is-open').find('.gep-alt-editor').prop('hidden', true);
            $row.find('.gep-alt-open').attr('aria-expanded', 'false');
            $row.find('textarea').val($row.data('saved-alt') !== undefined ? $row.data('saved-alt') : $row.find('textarea').prop('defaultValue'));
            $row.find('.gep-alt-status').text('');
        });
    }
    function open($row){
        closeAll();
        $row.addClass('is-open').find('.gep-alt-editor').prop('hidden', false);
        $row.find('.gep-alt-open').attr('aria-expanded', 'true');
        $row.find('textarea').trigger('focus');
    }
    function counts(){
        var $used = $app.find('.gep-alt-row').not('.is-unused'), total = $used.length, done = $used.filter('.has-alt').length, missing = total - done;
        $('#gep-alt-count-done').text(done);
        $('#gep-alt-count-missing').text(missing);
        $('[data-alt-chip-done]').text(done);
        $('[data-alt-chip-missing]').text(missing);
        $('#gep-alt-summary-title').text(missing ? (missing + ' de ' + total + ' fotos del sitio sin descripción') : 'Todas las fotos del sitio tienen descripción');
    }
    function apply(){
        var q = $.trim($('#gep-alt-search').val() || '').toLowerCase(), shown = 0;
        $app.find('.gep-alt-row').each(function(){
            var $row = $(this), has = $row.hasClass('has-alt'), unused = $row.hasClass('is-unused'), pages = $row.data('alt-pages') || [], ok = true;
            if (filter === 'used') ok = !unused;
            else if (filter === 'unused') ok = unused;
            else if (filter === 'missing') ok = !has && !unused;
            else if (filter === 'done') ok = has && !unused;
            else if (filter === 'page') ok = $.inArray(page, pages) >= 0;
            if (ok && q) ok = String($row.attr('data-alt-search') || '').indexOf(q) >= 0;
            $row.prop('hidden', !ok);
            if (ok) shown++;
        });
        $('#gep-alt-empty').prop('hidden', shown > 0);
    }
    function markRow($row, alt){
        var has = alt !== '';
        $row.toggleClass('has-alt', has).data('saved-alt', alt);
        $row.find('textarea').prop('defaultValue', alt).val(alt);
        var $text = $row.find('.gep-alt-text');
        if (has) $text.text(alt); else $text.html('<em>Sin descripción</em>');
        $row.find('.gep-alt-state').toggleClass('is-current', has).toggleClass('is-changed', !has).text(has ? 'Con descripción' : 'Pendiente');
        var search = String($row.attr('data-alt-search') || '');
        $row.attr('data-alt-search', (search + ' ' + alt).toLowerCase());
    }

    $app.find('.gep-alt-row').each(function(){
        var $row = $(this);
        $row.toggleClass('has-alt', $.trim($row.find('textarea').val() || '') !== '');
    });

    $app.on('click', '.gep-alt-open', function(e){
        e.preventDefault();
        var $row = $(this).closest('.gep-alt-row');
        if ($row.hasClass('is-open')) closeAll(); else open($row);
    });
    $app.on('click', '.gep-alt-cancel', function(e){ e.preventDefault(); closeAll(); });
    $app.on('keydown', '.gep-alt-row textarea', function(e){
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $(this).closest('.gep-alt-row').find('.gep-alt-save').trigger('click'); }
        if (e.key === 'Escape') { e.preventDefault(); closeAll(); }
    });
    $app.on('click', '.gep-alt-save', function(e){
        e.preventDefault();
        var $btn = $(this), $row = $btn.closest('.gep-alt-row'), alt = $.trim($row.find('textarea').val() || ''), $status = $row.find('.gep-alt-status');
        $btn.prop('disabled', true);
        $status.removeClass('is-error').text('Guardando…');
        $.post($app.data('ajax-url'), { action: 'gep_alt_save', nonce: $app.data('nonce'), id: $row.data('alt-id'), alt: alt })
            .done(function(res){
                if (res && res.success) {
                    markRow($row, res.data.alt);
                    counts();
                    $status.text('');
                    closeAll();
                    $row.addClass('is-saved');
                    setTimeout(function(){ $row.removeClass('is-saved'); }, 1600);
                    apply();
                } else {
                    $status.addClass('is-error').text((res && res.data && res.data.message) || 'No se pudo guardar.');
                }
            })
            .fail(function(xhr){
                $status.addClass('is-error').text(xhr && xhr.status === 403 ? 'La sesión expiró. Recarga la página.' : 'No se pudo guardar. Revisa la conexión.');
            })
            .always(function(){ $btn.prop('disabled', false); });
    });
    $app.on('click', '[data-alt-filter]', function(e){
        e.preventDefault();
        filter = $(this).data('alt-filter');
        page = $(this).data('alt-page') || '';
        $app.find('[data-alt-filter]').removeClass('is-active');
        $(this).addClass('is-active');
        closeAll();
        apply();
    });
    $('#gep-alt-search').on('input', apply);
    apply();
})(jQuery);
