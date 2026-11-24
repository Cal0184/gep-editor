/* GEP Editor v9.21.0 · Importar publicaciones por DOI (Publicaciones > Registros bibliográficos). */
(function($){
    'use strict';
    var $panel = $('#gep-doi-panel');
    if (!$panel.length) return;

    var results = [], busy = false, MAX = 25;

    function api(){ return window.gepPublicationsApi || null; }
    function doiKey(v){
        v = $.trim(String(v || '')).replace(/^(https?:\/\/)?(dx\.)?doi\.org\//i, '').replace(/^doi:\s*/i, '');
        try { v = decodeURIComponent(v); } catch (e) {}
        return v.replace(/[\s.,;]+$/, '').toLowerCase();
    }
    function plain(v){
        v = String(v || '').toLowerCase();
        if (v.normalize) v = v.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        return v.replace(/[^a-z0-9]+/g, ' ').replace(/^ | $/g, '');
    }
    /* Los registros antiguos no tienen DOI: también se compara el título contra su referencia. */
    function titleExists(title){
        var t = plain(title), a = api(), found = false;
        if (!a || t.length < 20) return false;
        $.each(a.records(), function(_, r){ if (plain((r.title || '') + ' ' + (r.text || '')).indexOf(t) >= 0) { found = true; return false; } });
        return found;
    }
    function existingDois(){
        var map = {}, a = api();
        if (!a) return map;
        $.each(a.records(), function(_, r){
            var k = doiKey(r.doi || '');
            if (k) map[k] = true;
            var m = String((r.url || '') + ' ' + (r.text || '')).match(/10\.\d{4,9}\/[^\s"<>]+/g);
            if (m) $.each(m, function(_, d){ map[doiKey(d)] = true; });
        });
        return map;
    }
    function openPanel(){
        $panel.removeClass('is-hidden');
        $('.gep-doi-open').attr('aria-expanded', 'true');
        $('#gep-doi-input').trigger('focus');
    }
    function closePanel(){
        $panel.addClass('is-hidden');
        $('.gep-doi-open').attr('aria-expanded', 'false');
    }
    function categoryOptions(selected){
        var html = '';
        $('#gep-publication-edit-category option').each(function(){
            var v = $(this).val();
            html += '<option value="' + v + '"' + (v === selected ? ' selected' : '') + '>' + $('<span>').text($(this).text()).html() + '</option>';
        });
        return html;
    }
    function updateFooter(){
        var n = 0;
        $.each(results, function(_, r){ if (r.ok && r.checked) n++; });
        $('#gep-doi-footer').toggleClass('is-hidden', !results.length || !$.grep(results, function(r){ return r.ok; }).length);
        $('#gep-doi-selected-count').text(n === 1 ? '1 publicación seleccionada' : n + ' publicaciones seleccionadas');
        $('.gep-doi-add-selected').prop('disabled', n === 0);
    }
    function renderCard(i){
        var r = results[i], $card = $('#gep-doi-results [data-doi-index="' + i + '"]');
        if (!$card.length) {
            $card = $('<div class="gep-doi-card" data-doi-index="' + i + '"></div>').appendTo('#gep-doi-results');
        }
        $card.removeClass('is-loading is-ok is-duplicate is-error');
        if (r.state === 'loading') {
            $card.addClass('is-loading').html('<span class="dashicons dashicons-update gep-spin"></span><div class="gep-doi-card-body"><strong></strong><p>Buscando…</p></div>');
            $card.find('strong').text(r.doi);
            return;
        }
        if (!r.ok) {
            $card.addClass('is-error').html('<span class="dashicons dashicons-warning"></span><div class="gep-doi-card-body"><strong></strong><p></p></div><span class="gep-doi-pill is-error">No encontrado</span>');
            $card.find('strong').text(r.doi);
            $card.find('p').text(r.message);
            return;
        }
        var rec = r.record;
        $card.addClass(r.duplicate ? 'is-duplicate' : 'is-ok').html(
            '<label class="gep-doi-check"><input type="checkbox"' + (r.checked ? ' checked' : '') + (r.duplicate ? ' disabled' : '') + ' aria-label="Agregar esta publicación"></label>' +
            '<div class="gep-doi-card-body"><div class="gep-doi-card-top"><span class="gep-item-summary-badge"></span><strong class="gep-doi-card-title"></strong></div>' +
            '<p class="gep-doi-card-ref"></p>' +
            '<div class="gep-doi-card-meta"><select aria-label="Categoría">' + categoryOptions(rec.category) + '</select><a target="_blank" rel="noopener"></a></div></div>' +
            (r.duplicate ? '<span class="gep-doi-pill is-duplicate" title="Esta publicación ya está en la lista y no se agregará de nuevo.">Ya está en tu lista</span>' : '<span class="gep-doi-pill is-ok">Nueva</span>')
        );
        $card.find('.gep-item-summary-badge').text(rec.year || 'AÑO');
        $card.find('.gep-doi-card-title').text((rec.preview && rec.preview.title) || rec.doi);
        $card.find('.gep-doi-card-ref').text(rec.text);
        $card.find('.gep-doi-card-meta a').attr('href', rec.url).text('doi.org/' + rec.doi + ' ↗');
    }
    function lookup(queue, done){
        if (!queue.length) { done(); return; }
        var i = queue.shift(), r = results[i];
        $('#gep-doi-progress').text('Buscando ' + (results.length - queue.length) + ' de ' + results.length + '…');
        $.post($panel.data('ajax-url'), { action: 'gep_doi_lookup', nonce: $panel.data('nonce'), doi: r.doi })
            .done(function(res){
                if (res && res.success) {
                    r.ok = true;
                    r.record = res.data;
                    r.duplicate = !!existingDois()[doiKey(res.data.doi)] || titleExists(res.data.preview && res.data.preview.title) || !!$.grep(results.slice(0, i), function(o){ return o.ok && doiKey(o.record.doi) === doiKey(res.data.doi); }).length;
                    r.checked = !r.duplicate;
                } else {
                    r.ok = false;
                    r.message = (res && res.data && res.data.message) || 'No se pudo leer este DOI.';
                }
            })
            .fail(function(xhr){
                r.ok = false;
                r.message = xhr && xhr.status === 403 ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.' : 'No se pudo conectar. Inténtalo de nuevo.';
            })
            .always(function(){ r.state = 'done'; renderCard(i); updateFooter(); lookup(queue, done); });
    }
    function search(){
        if (busy) return;
        var lines = String($('#gep-doi-input').val() || '').split(/[\n,;]+|\s+(?=(?:https?:\/\/|doi:|10\.))/i), seen = {}, list = [];
        $.each(lines, function(_, line){
            var raw = $.trim(line), k = doiKey(raw);
            if (!raw || seen[k]) return;
            seen[k] = true;
            list.push(raw);
        });
        if (!list.length) { $('#gep-doi-progress').text('Pega al menos un DOI.'); return; }
        if (list.length > MAX) { list = list.slice(0, MAX); }
        results = [];
        $('#gep-doi-results').empty();
        $.each(list, function(i, raw){ results.push({ doi: raw, state: 'loading' }); renderCard(i); });
        updateFooter();
        busy = true;
        $('.gep-doi-search').prop('disabled', true);
        lookup($.map(results, function(_, i){ return i; }), function(){
            busy = false;
            $('.gep-doi-search').prop('disabled', false);
            var ok = $.grep(results, function(r){ return r.ok; }).length;
            $('#gep-doi-progress').text(ok + ' de ' + results.length + ' encontrados.' + (lines.length > MAX ? ' Solo se buscan ' + MAX + ' a la vez.' : ''));
        });
    }
    function addSelected(){
        var a = api(), list = [], skipped = 0;
        if (!a) return;
        // v9.23.0: se vuelve a revisar contra la lista justo antes de agregar,
        // por si la publicación se agregó en otra búsqueda.
        var existing = existingDois();
        $.each(results, function(_, r){
            if (!r.ok || !r.checked) return;
            if (r.duplicate || existing[doiKey(r.record.doi)] || titleExists(r.record.preview && r.record.preview.title)) { skipped++; return; }
            existing[doiKey(r.record.doi)] = true;
            var rec = $.extend({}, r.record);
            delete rec.preview;
            list.push(rec);
        });
        if (!list.length) {
            if (skipped) $('#gep-doi-progress').text(skipped === 1 ? 'Esa publicación ya está en la lista: no se agregó de nuevo.' : 'Esas publicaciones ya están en la lista: no se agregaron de nuevo.');
            return;
        }
        var n = a.add(list);
        results = [];
        $('#gep-doi-results').empty();
        $('#gep-doi-input').val('');
        updateFooter();
        $('#gep-doi-progress').html('<span class="dashicons dashicons-yes-alt"></span> ' + (n === 1 ? 'Se agregó 1 publicación' : 'Se agregaron ' + n + ' publicaciones') + ' al inicio de la lista. Revísalas y pulsa <strong>Guardar cambios</strong>.');
    }

    $(document).on('click', '.gep-doi-open', function(e){ e.preventDefault(); if ($panel.hasClass('is-hidden')) openPanel(); else closePanel(); });
    $(document).on('click', '.gep-doi-close', function(e){ e.preventDefault(); closePanel(); });
    $(document).on('click', '.gep-doi-search', function(e){ e.preventDefault(); search(); });
    $panel.on('keydown', '#gep-doi-input', function(e){ if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); search(); } });
    $panel.on('change', '#gep-doi-results input[type=checkbox]', function(){
        var r = results[$(this).closest('.gep-doi-card').data('doi-index')];
        if (r) { r.checked = this.checked; updateFooter(); }
    });
    $panel.on('change', '#gep-doi-results select', function(){
        var r = results[$(this).closest('.gep-doi-card').data('doi-index')];
        if (r && r.record) r.record.category = $(this).val();
    });
    $(document).on('click', '.gep-doi-add-selected', function(e){ e.preventDefault(); addSelected(); });
    // Los campos del panel no son parte del formulario guardado ni marcan cambios pendientes.
    $panel.on('input change', 'textarea,input,select', function(e){ e.stopPropagation(); });
})(jQuery);
