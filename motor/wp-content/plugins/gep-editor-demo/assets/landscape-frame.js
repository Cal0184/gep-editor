/* GEP Editor · v9.25.0 · Encuadre de la foto de «Mensaje de paisaje» (Inicio).
 *
 * - Pulsar (o arrastrar) sobre la miniatura elige el punto de la foto que
 *   siempre debe verse; se guarda como "x% y%" en gep_home_landscape_focus.
 * - Las flechas del teclado mueven el punto de a 5 %.
 * - El recuadro claro sobre la miniatura muestra qué parte se verá en PC.
 * - window.gepLandscapePreview() lo usa admin.js al actualizar la vista previa:
 *   aplica el mismo encuadre y la misma forma que tendrá la página pública.
 * No escucha el desplazamiento de la página ni cambia nada fuera de este campo.
 */
(function($){
    'use strict';
    if (!$) return;

    var DEFAULT_FOCUS = '50% 46%';
    var ratioCache = {};

    function parseFocus(value){
        var m = String(value || '').match(/^(\d{1,3}(?:\.\d+)?)% (\d{1,3}(?:\.\d+)?)%$/);
        if (!m) m = DEFAULT_FOCUS.match(/^(\d+)% (\d+)%$/);
        return {x: Math.min(100, Math.max(0, parseFloat(m[1]))), y: Math.min(100, Math.max(0, parseFloat(m[2])))};
    }
    function formatFocus(p){ return Math.round(p.x) + '% ' + Math.round(p.y) + '%'; }

    function $field(){ return $('#gep_home_landscape_focus'); }
    function $fit(){ return $('#gep_home_landscape_fit'); }
    function fitValue(){
        var $f = $fit();
        if ($f.length && !$f.val()) $f.val('auto');
        return $f.val() === 'cover' ? 'cover' : 'auto';
    }

    /* Proporción de la foto (ancho / alto), leída del archivo una sola vez. */
    function withRatio(url, done){
        if (!url) { done(0); return; }
        if (ratioCache[url] !== undefined) { done(ratioCache[url]); return; }
        var img = new window.Image();
        img.onload = function(){ ratioCache[url] = img.naturalWidth && img.naturalHeight ? img.naturalWidth / img.naturalHeight : 0; done(ratioCache[url]); };
        img.onerror = function(){ ratioCache[url] = 0; done(0); };
        img.src = url;
    }

    /* Recuadro aproximado de lo que se verá en una pantalla de PC (1440 x 900). */
    function cropBox(ratio, fit, isCustom, focus){
        var viewW = 1440, viewH = 900, boxH;
        if (isCustom && fit === 'auto') boxH = Math.min(Math.max(viewW / ratio, 380), viewH * 0.86);
        else boxH = 560;
        if (isCustom && fit === 'auto' && ratio < 1.15) return {w: 100, h: 100, x: 0, y: 0, whole: true};
        var stripRatio = viewW / boxH;
        var w = 100, h = 100;
        if (ratio > stripRatio) w = stripRatio / ratio * 100; else h = ratio / stripRatio * 100;
        return {w: w, h: h, x: (100 - w) * focus.x / 100, y: (100 - h) * focus.y / 100, whole: w > 99 && h > 99};
    }

    function currentImage(){
        var raw = $.trim($('[name="gep_home_landscape_image"]').val() || '');
        var resolved = typeof window.gepPreviewResolveImage === 'function'
            ? window.gepPreviewResolveImage(raw, '#gep-home-preview-shell', 'assets/field/floracion-territorio-02.jpg')
            : raw;
        return {url: resolved || '', custom: !!raw};
    }

    function syncPicker(){
        var $frame = $('.gep-landscape-frame');
        if (!$frame.length) return;
        var info = currentImage(), focus = parseFocus($field().val()), fit = fitValue();
        var $img = $frame.find('.gep-landscape-picker img');
        if (info.url && $img.attr('src') !== info.url) $img.attr('src', info.url);
        $frame.find('.gep-landscape-dot').css({left: focus.x + '%', top: focus.y + '%'});
        $frame.toggleClass('is-default-photo', !info.custom);
        withRatio(info.url, function(ratio){
            var $crop = $frame.find('.gep-landscape-crop');
            var hint = '';
            if (!ratio) { $crop.hide(); }
            else {
                var box = cropBox(ratio, fit, info.custom, focus);
                $crop.toggle(!box.whole).css({left: box.x + '%', top: box.y + '%', width: box.w + '%', height: box.h + '%'});
                if (info.custom && fit === 'auto' && ratio < 1.15) hint = 'Foto vertical: se mostrará completa, con un fondo difuminado de la misma foto a los lados.';
                else if (box.whole) hint = 'La foto se verá completa en PC.';
                else hint = 'El recuadro claro es lo que se verá en una pantalla de PC; en teléfonos se ve una parte más angosta alrededor del punto.';
                if (!info.custom) hint = 'Foto predeterminada del sitio. ' + hint;
            }
            $frame.find('.gep-landscape-hint').text(hint);
        });
    }

    function setFocus(p, silent){
        $field().val(formatFocus(p));
        syncPicker();
        if (!silent) $field().trigger('change');
    }

    /* Vista previa: mismo encuadre y forma que la página pública. */
    window.gepLandscapePreview = function($el, url, isCustom){
        if (!$el || !$el.length) return;
        var focus = parseFocus($field().val()), fit = fitValue();
        $el.css('background-position', focus.x + '% ' + focus.y + '%');
        $el.removeClass('is-photo-fit is-photo-tall').css({'aspect-ratio': '', '--gep-landscape-img': ''});
        syncPicker();
        if (!isCustom || fit !== 'auto' || !url) return;
        withRatio(url, function(ratio){
            if (!ratio) return;
            $el.addClass('is-photo-fit').css('aspect-ratio', String(Math.max(ratio, 0.8)));
            if (ratio < 1.15) $el.addClass('is-photo-tall').css('--gep-landscape-img', 'url("' + String(url).replace(/"/g, '%22') + '")');
        });
    };

    $(function(){
        var $picker = $('.gep-landscape-picker');
        if (!$picker.length) return;
        var dragging = false;
        function pointFromEvent(e){
            var img = $picker.find('img')[0], r = (img && img.getBoundingClientRect().width ? img : $picker[0]).getBoundingClientRect();
            var t = e.originalEvent && e.originalEvent.touches && e.originalEvent.touches[0] ? e.originalEvent.touches[0] : e;
            return {x: Math.min(100, Math.max(0, (t.clientX - r.left) / r.width * 100)), y: Math.min(100, Math.max(0, (t.clientY - r.top) / r.height * 100))};
        }
        $picker.on('pointerdown', function(e){ e.preventDefault(); dragging = true; try { this.setPointerCapture(e.originalEvent.pointerId); } catch(err) {} setFocus(pointFromEvent(e), true); });
        $picker.on('pointermove', function(e){ if (dragging) setFocus(pointFromEvent(e), true); });
        $picker.on('pointerup pointercancel', function(){ if (!dragging) return; dragging = false; $field().trigger('change'); });
        $picker.on('keydown', function(e){
            var p = parseFocus($field().val()), step = e.shiftKey ? 15 : 5;
            if (e.key === 'ArrowLeft') p.x -= step; else if (e.key === 'ArrowRight') p.x += step;
            else if (e.key === 'ArrowUp') p.y -= step; else if (e.key === 'ArrowDown') p.y += step;
            else return;
            e.preventDefault();
            p.x = Math.min(100, Math.max(0, p.x)); p.y = Math.min(100, Math.max(0, p.y));
            setFocus(p);
        });
        $(document).on('click', '.gep-landscape-center', function(e){ e.preventDefault(); setFocus(parseFocus(DEFAULT_FOCUS)); });
        $(document).on('change', '#gep_home_landscape_fit,[name="gep_home_landscape_image"]', function(){ window.setTimeout(syncPicker, 0); });
        syncPicker();
    });
})(window.jQuery);
