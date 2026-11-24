/* GEP Editor · Servicios: lista primero, editar uno, preview en vivo. */
(function(){
    // Se registra antes que el aviso de "cambios sin guardar" de admin.js para
    // que un guardado válido no muestre el diálogo de salir de la página.
    window.addEventListener('beforeunload', function(e){
        if (window.gepServicesSubmitting) e.stopImmediatePropagation();
    }, true);
})();
jQuery(function($){
    var $form = $('.gep-services-form');
    if (!$form.length) return;
    var $list = $('#gep-service-list'), counter = 1000;
    var zones = {hero:['Zona 1','Encabezado'], services:['Zona 2','Servicios'], steps:['Zona 3','Cómo trabajamos'], cta:['Zona 4','Llamado final']};

    function val(name){ return $.trim($form.find('[name="'+name+'"]').first().val() || ''); }
    function touch(){ $form.find('[name="gep_services_hero_title"]').trigger('change'); }
    function lines(text){ return $.grep(String(text || '').split(/[\r\n]+/), function(l){ return $.trim(l) !== ''; }); }
    function resolve(url){
        url = $.trim(url || '');
        if (!url || /^(?:https?:)?\/\//i.test(url) || /^(?:data:|blob:)/i.test(url) || url.charAt(0) === '/') return url;
        var base = $('#gep-service-preview-shell').data('public-url') || '';
        try { return base ? new URL(url, base).href : url; } catch(e) { return url; }
    }
    function rowData($r){
        function f(k){ return $.trim($r.find('[name$="['+k+']"]').not('[type=hidden][name$="[visible]"]').first().val() || ''); }
        return {
            visible: $r.find('.gep-service-visible').is(':checked'),
            title: f('title'), tag: f('tag'), nav: f('nav_label'), summary: f('summary'),
            deliverables: lines(f('deliverables')), applications: lines(f('applications')),
            image: f('image'), button: f('button'), url: f('url')
        };
    }

    function syncRow($r){
        var d = rowData($r), title = d.title || 'Nuevo servicio';
        $r.find('.gep-service-row-title,.gep-service-active-title').text(title);
        $r.find('.gep-service-row-tag').text(d.tag);
        $r.toggleClass('is-hidden-service', !d.visible).find('.gep-service-row-state').text(d.visible ? 'Visible' : 'Oculto');
    }
    function renumber(){
        var $rows = $list.children('.gep-service-row');
        $rows.each(function(i){ $(this).find('.gep-service-row-number').text(('0' + (i + 1)).slice(-2)); });
        var n = $rows.length;
        $('#gep-service-count').text(n + ' ' + (n === 1 ? 'servicio' : 'servicios'));
        $('#gep-service-empty').toggleClass('is-hidden', n > 0);
    }
    function rekey($r, key){
        $r.attr('data-service-key', key).find('[name]').each(function(){
            var n = $(this).attr('name'); if (n) $(this).attr('name', n.replace(/gep_services_items\[[^\]]+\]/, 'gep_services_items['+key+']'));
        });
    }

    function openRow($r){
        $list.children('.gep-service-row').not($r).each(function(){ closeRow($(this)); });
        $r.addClass('is-open').find('.gep-service-row-body').prop('hidden', false);
        setZone('services');
        update();
    }
    function closeRow($r){
        $r.removeClass('is-open is-invalid-row').find('.gep-service-row-body').prop('hidden', true);
    }

    function setZone(zone){
        var z = zones[zone] || zones.hero;
        $('#gep-service-preview-zone').text(z[0]); $('#gep-service-preview-heading').text(z[1]);
        $form.find('[data-service-preview]').removeClass('is-preview-source').filter('[data-service-preview="'+zone+'"]').addClass('is-preview-source');
        $('[data-service-live]').removeClass('is-live-active').filter('[data-service-live="'+zone+'"]').addClass('is-live-active');
    }
    function setDevice(device){
        var labels = {desktop:'Vista escritorio', tablet:'Vista tablet', mobile:'Vista móvil'}, $b = $('#gep-service-live-preview');
        $b.removeClass('is-device-tablet is-device-mobile');
        if (device === 'tablet') $b.addClass('is-device-tablet');
        if (device === 'mobile') $b.addClass('is-device-mobile');
        $('.gep-service-device-button').removeClass('is-active').filter('[data-device="'+device+'"]').addClass('is-active');
        $('#gep-service-device-label').text(labels[device] || labels.desktop);
    }

    function update(){
        renumber();
        $('#gep-service-live-eyebrow').text(val('gep_services_hero_eyebrow'));
        $('#gep-service-live-title').text(val('gep_services_hero_title') || 'Servicios');
        $('#gep-service-live-text').text(val('gep_services_hero_text'));
        var $hero = $('#gep-service-live-hero'), img = resolve(val('gep_services_hero_image'));
        // v9.21.3: mismo criterio que las demás pestañas (admin.js): primer cuadro
        // quieto del video; miniatura si es YouTube.
        var vid = resolve(val('gep_services_hero_video'));
        var hasVid = window.gepPreviewPlaceHeroVideo ? window.gepPreviewPlaceHeroVideo($hero, vid) : false;
        $hero.toggleClass('has-preview-video', hasVid);
        $hero.css('background-image', img ? 'linear-gradient(90deg,rgba(6,31,19,.72),rgba(20,71,45,.38)),url("'+img.replace(/"/g,'%22')+'")' : '');
        $('#gep-service-live-intro-eyebrow').text(val('gep_services_intro_eyebrow'));
        $('#gep-service-live-intro-title').text(val('gep_services_intro_title'));

        var $nav = $('#gep-service-live-nav').empty(), $live = $('#gep-service-live-list').empty(), shown = 0;
        $list.children('.gep-service-row').each(function(){
            var $r = $(this), d = rowData($r);
            if (!d.visible) return;
            shown++;
            $('<span></span>').text(d.nav || d.title || 'Servicio').appendTo($nav);
            var $card = $('<div class="gep-service-live-card"><div class="gep-service-live-media"><b></b><i></i></div><div class="gep-service-live-copy"><small></small><strong></strong><p></p><ul></ul><em></em></div></div>');
            $card.toggleClass('is-reverse', shown % 2 === 0).toggleClass('is-active', $r.hasClass('is-open'));
            var src = resolve(d.image);
            if (src) $card.find('.gep-service-live-media').addClass('has-image').css('background-image', 'url("'+src.replace(/"/g,'%22')+'")');
            $card.find('.gep-service-live-media b').text(('0' + shown).slice(-2));
            $card.find('.gep-service-live-media i').text(d.tag);
            $card.find('small').text(d.tag); $card.find('strong').text(d.title || 'Nuevo servicio'); $card.find('p').text(d.summary);
            $.each(d.deliverables.slice(0, 3), function(_, l){ $('<li></li>').text(l).appendTo($card.find('ul')); });
            if (d.url) $card.find('em').text((d.button || 'Más información') + ' →'); else $card.find('em').remove();
            $live.append($card);
        });
        if (!shown) $live.append('<div class="gep-hub-preview-empty">No hay servicios visibles.</div>');

        $('#gep-service-live-steps-eyebrow').text(val('gep_services_steps_eyebrow'));
        $('#gep-service-live-steps-title').text(val('gep_services_steps_title'));
        var $steps = $('#gep-service-live-steps').empty(), n = 0;
        for (var i = 0; i < 4; i++) {
            var t = val('gep_services_steps['+i+'][title]'), x = val('gep_services_steps['+i+'][text]');
            if (!t && !x) continue; n++;
            var $s = $('<div class="gep-service-live-step"><span></span><strong></strong><p></p></div>');
            $s.find('span').text(('0' + n).slice(-2)); $s.find('strong').text(t); $s.find('p').text(x);
            $steps.append($s);
        }
        $('#gep-service-live-cta-eyebrow').text(val('gep_services_cta_eyebrow'));
        $('#gep-service-live-cta-title').text(val('gep_services_cta_title'));
        $('#gep-service-live-cta-button').text(val('gep_services_cta_button')).toggle(!!val('gep_services_cta_button'));
    }

    function validate(){
        var ok = true, $firstBad = null;
        $form.find('.gep-field-error-msg').remove();
        $form.find('.is-invalid').removeClass('is-invalid');
        var $heroTitle = $form.find('[name="gep_services_hero_title"]');
        if (!$.trim($heroTitle.val() || '')) { ok = false; $heroTitle.addClass('is-invalid'); $firstBad = $firstBad || $heroTitle; }
        $list.children('.gep-service-row').each(function(){
            var $r = $(this), $t = $r.find('.gep-service-title');
            if (!$.trim($t.val() || '')) { ok = false; $t.addClass('is-invalid').after('<small class="gep-field-error-msg">Escribe el nombre del servicio.</small>'); $r.addClass('is-invalid-row'); if (!$firstBad) { openRow($r); $firstBad = $t; } }
        });
        if (!ok) {
            $('.gep-hub-inline-status').removeClass('is-clean is-dirty').addClass('is-error').html('<span class="dashicons dashicons-dismiss"></span> Revisa los campos');
            if ($firstBad) { $('html, body').animate({scrollTop: Math.max(0, $firstBad.offset().top - 140)}, 200); try { $firstBad[0].focus({preventScroll:true}); } catch(err) {} }
        }
        return ok;
    }

    function initSort(){
        if ($list.data('service-sort') || !$.fn.sortable) return;
        $list.data('service-sort', 1).sortable({items:'> .gep-service-row', handle:'.gep-grip', placeholder:'gep-docencia-sort-placeholder', forcePlaceholderSize:true, stop:function(){ update(); touch(); }});
    }

    $form.on('click', '.gep-service-open', function(e){ e.preventDefault(); var $r = $(this).closest('.gep-service-row'); if ($r.hasClass('is-open')) closeRow($r); else openRow($r); });
    $form.on('click', '.gep-service-row-copy', function(){ $(this).closest('.gep-service-row').find('.gep-service-open').trigger('click'); });
    $form.on('click', '.gep-service-close', function(e){ e.preventDefault(); closeRow($(this).closest('.gep-service-row')); update(); });
    $form.on('click', '.gep-service-add', function(e){
        e.preventDefault();
        var key = 'n' + (++counter), $r = $($('#gep-service-row-template').html().split('__K__').join(key));
        $list.append($r); rekey($r, key); syncRow($r); openRow($r); initSort(); touch();
        $r.find('.gep-service-title').trigger('focus');
    });
    $form.on('click', '.gep-service-duplicate', function(e){
        e.preventDefault();
        var $src = $(this).closest('.gep-service-row'), key = 'n' + (++counter), $copy = $src.clone(false);
        $copy.find('textarea').each(function(i){ $(this).val($src.find('textarea').eq(i).val()); });
        rekey($copy, key);
        $copy.find('[name$="[slug]"]').val('');
        var $title = $copy.find('.gep-service-title'); $title.val(($title.val() || '') + ' (copia)');
        $copy.insertAfter($src); syncRow($copy); openRow($copy); touch();
    });
    $form.on('click', '.gep-service-remove', function(e){
        e.preventDefault();
        var $r = $(this).closest('.gep-service-row'), name = $r.find('.gep-service-title').val() || 'este servicio';
        if (!window.confirm('¿Eliminar "' + name + '"? Desaparecerá de la página al guardar. Si te equivocas, puedes recuperarlo desde el Historial de cambios.')) return;
        $r.remove(); update(); touch();
    });
    $form.on('click', '.gep-service-up', function(e){ e.preventDefault(); var $r = $(this).closest('.gep-service-row'), $p = $r.prev('.gep-service-row'); if ($p.length) { $r.insertBefore($p); update(); touch(); } });
    $form.on('click', '.gep-service-down', function(e){ e.preventDefault(); var $r = $(this).closest('.gep-service-row'), $n = $r.next('.gep-service-row'); if ($n.length) { $r.insertAfter($n); update(); touch(); } });
    $form.on('input change', '.gep-service-row :input', function(){ var $r = $(this).closest('.gep-service-row'); syncRow($r); if ($(this).is('.gep-service-title') && $.trim($(this).val())) { $(this).removeClass('is-invalid').next('.gep-field-error-msg').remove(); } update(); });
    $form.on('input change', '[name^="gep_services_"]', update);
    $form.on('focusin click', '[data-service-preview] :input,[data-service-preview] .gep-step-heading', function(){ setZone($(this).closest('[data-service-preview]').data('service-preview') || 'hero'); });
    $(document).on('click', '.gep-service-device-button', function(e){ e.preventDefault(); setDevice($(this).data('device') || 'desktop'); });
    $(document).on('click', '.gep-select-image,.gep-remove-image,.gep-select-video,.gep-remove-video', function(){ setTimeout(update, 200); });
    // Fase de captura: valida antes que los manejadores genéricos de admin.js.
    $form[0].addEventListener('submit', function(e){
        if (!validate()) { e.preventDefault(); e.stopImmediatePropagation(); return; }
        window.gepServicesSubmitting = true;
    }, true);

    $list.children('.gep-service-row').each(function(){ syncRow($(this)); });
    initSort(); setDevice('desktop'); setZone('hero'); update();
});
