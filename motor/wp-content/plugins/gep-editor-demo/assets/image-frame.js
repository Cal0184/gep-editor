/* GEP Editor · v9.26.0 · «Encuadre de la foto» (componente compartido).
 *
 * Un solo componente para todas las pestañas. Junto a cada selector de imagen
 * aparece el botón «Encuadre de la foto»; abre la misma herramienta:
 *  - foto completa: pulsar o arrastrar el punto que siempre debe verse;
 *  - vistas PC, tablet y teléfono con la forma real del recuadro, donde
 *    también se puede arrastrar la foto (mouse o dedo);
 *  - acercamiento (en fotos que no son cabecera), encuadre distinto en
 *    teléfonos (opcional), Restablecer, Cancelar y Aplicar.
 * Lo aplicado se guarda con «Guardar cambios» (campo oculto
 * gep_image_frames_json) y se ve al instante en la vista previa del editor.
 * Sin listeners de desplazamiento ni de tamaño de ventana: nada de esto mueve
 * la página (regla desde 9.24.1).
 */
(function($){
    'use strict';
    if (!$) return;

    var data = window.gepFrameData || {};
    var frames = $.extend({}, data.frames && typeof data.frames === 'object' ? data.frames : {});
    var ratioCache = {};
    var LANDSCAPE_DEFAULT = {x: 50, y: 46};

    /* ---------- Claves y datos ---------- */
    function frameKey(url){
        var u = $.trim(String(url || ''));
        if (!u) return '';
        u = u.replace(/[?#].*$/, '');
        try { u = decodeURIComponent(u); } catch (e) {}
        u = u.replace(/^(?:[a-z][a-z0-9+.\-]*:)?\/\/[^\/]+/i, '');
        var m = u.match(/\/uploads\/(.+)$/i), k;
        if (m) k = 'u:' + m[1];
        else if ((m = u.match(/(?:^|\/)(assets\/.+)$/i))) k = 'a:' + m[1];
        else k = 'x:' + u.replace(/^[.\/]+/, '');
        k = k.replace(/-\d+x\d+(\.[a-z0-9]+)$/i, '$1').replace(/-scaled(\.[a-z0-9]+)$/i, '$1');
        return k.toLowerCase();
    }
    window.gepFrameKey = frameKey;

    function clamp(v, a, b){ v = parseFloat(v); if (isNaN(v)) v = a; return Math.min(b, Math.max(a, v)); }
    function round1(v){ return Math.round(v * 10) / 10; }
    function num(v){ return String(Math.round(v * 100) / 100); }

    function normalize(f){
        f = f || {};
        var out = {x: clamp(f.x === undefined ? 50 : f.x, 0, 100), y: clamp(f.y === undefined ? 50 : f.y, 0, 100), z: clamp(f.z === undefined ? 1 : f.z, 1, 3)};
        if (f.mx !== undefined && f.mx !== '' && f.my !== undefined && f.my !== '') { out.mx = clamp(f.mx, 0, 100); out.my = clamp(f.my, 0, 100); }
        return out;
    }
    function isDefault(f){ return f.x === 50 && f.y === 50 && f.z === 1 && f.mx === undefined; }

    function resolveUrl(raw){
        raw = $.trim(String(raw || ''));
        if (!raw) return '';
        if (/^(https?:)?\/\//i.test(raw) || /^data:/i.test(raw)) return raw;
        var base = $('[data-public-url]').first().attr('data-public-url') || window.location.href;
        try { return new URL(raw, base).href; } catch (e) { return raw; }
    }

    function withRatio(url, done){
        if (!url) { done(0); return; }
        if (ratioCache[url] !== undefined) { done(ratioCache[url]); return; }
        var img = new window.Image();
        img.onload = function(){ ratioCache[url] = img.naturalWidth && img.naturalHeight ? img.naturalWidth / img.naturalHeight : 0; done(ratioCache[url]); };
        img.onerror = function(){ ratioCache[url] = 0; done(0); };
        img.src = url;
    }

    /* Campo oculto que viaja con «Guardar cambios». */
    function $store(){
        var $form = $('#gep-content-form');
        if (!$form.length) return $();
        var $in = $form.find('input[name="gep_image_frames_json"]');
        if (!$in.length) $in = $('<input type="hidden" name="gep_image_frames_json" id="gep-image-frames-json">').val(JSON.stringify(frames)).appendTo($form);
        return $in;
    }

    /* ---------- Variables CSS (mismas que el sitio público) ---------- */
    function frameVars(f, zoom){
        var v = {'--gep-px': num(f.x) + '%', '--gep-py': num(f.y) + '%', '--gep-mx': num(f.mx === undefined ? f.x : f.mx) + '%', '--gep-my': num(f.my === undefined ? f.y : f.my) + '%'};
        if (zoom && f.z > 1.001) { v['--gep-z'] = num(f.z); v['--gep-k'] = String(Math.round((1 - 1 / f.z) * 10000) / 10000); }
        return v;
    }
    var VAR_NAMES = ['--gep-px', '--gep-py', '--gep-mx', '--gep-my', '--gep-z', '--gep-k'];
    function setVars(el, vars){
        VAR_NAMES.forEach(function(n){
            var want = vars && vars[n] !== undefined ? vars[n] : '';
            if (el.style.getPropertyValue(n) !== want) { if (want) el.style.setProperty(n, want); else el.style.removeProperty(n); }
        });
    }

    /* ---------- Vista previa del editor: aplica los encuadres ---------- */
    var SKIP = '.gep-frame-modal, .gep-image-preview, .gep-home-prev-landscape, .gep-landscape-frame, .gep-project-gallery-item';
    function applyEl(el, url, isBg){
        var key = frameKey(url), f = key && frames[key] ? normalize(frames[key]) : null;
        var sig = f ? key + '|' + f.x + '|' + f.y + '|' + f.z + '|' + f.mx + '|' + f.my : '';
        if (el.getAttribute('data-gep-frame-sig') === sig || (!sig && !el.hasAttribute('data-gep-frame-sig'))) return;
        if (!f) {
            el.removeAttribute('data-gep-frame-sig'); el.removeAttribute('data-gep-frame'); el.removeAttribute('data-gep-frame-bg'); el.removeAttribute('data-gep-zoom');
            setVars(el, null); return;
        }
        el.setAttribute('data-gep-frame-sig', sig);
        if (isBg) { el.setAttribute('data-gep-frame-bg', ''); el.removeAttribute('data-gep-frame'); el.removeAttribute('data-gep-zoom'); }
        else { el.setAttribute('data-gep-frame', ''); if (f.z > 1.001) el.setAttribute('data-gep-zoom', ''); else el.removeAttribute('data-gep-zoom'); }
        setVars(el, frameVars(f, !isBg));
    }
    function applyAll(){
        var root = document.querySelector('.gep-editor-wrap') || document.body;
        if (!root) return;
        root.querySelectorAll('img[src]').forEach(function(img){ if (!$(img).closest(SKIP).length) applyEl(img, img.getAttribute('src'), false); });
        root.querySelectorAll('[style*="url("]').forEach(function(el){
            if (el.tagName === 'IMG' || $(el).closest(SKIP).length) return;
            var m = String(el.style.backgroundImage || '').match(/url\(\s*["']?([^"')]+)["']?\s*\)/g), url = '';
            if (m) { for (var i = 0; i < m.length; i++) { var u = m[i].replace(/^url\(\s*["']?|["']?\s*\)$/g, ''); if (frames[frameKey(u)]) { url = u; break; } if (!url) url = u; } }
            applyEl(el, url, true);
        });
        syncButtons();
    }
    var applyQueued = false;
    function scheduleApply(){
        if (applyQueued) return;
        applyQueued = true;
        (window.requestAnimationFrame || setTimeout)(function(){ applyQueued = false; applyAll(); });
    }
    window.gepFrameRefresh = scheduleApply;

    /* ---------- Botones junto a cada selector de imagen ---------- */
    function sourceInfo($btn){
        var $ctl = $btn.closest('.gep-image-control'), raw = '', name = '', slot = 'card';
        if ($ctl.length) {
            var $in = $ctl.find('input.gep-image-url').first();
            raw = $in.val() || ''; name = String($in.attr('name') || $in.attr('data-gep-project-editor-field') || '');
            if (/landscape_image$/.test(name)) slot = 'landscape';
            else if (/hero_image|hero\]\[image|header_image/.test(name)) slot = 'hero';
            else if ($ctl.closest('.gep-person-image-field,.gep-team-card-row,.gep-directory-single-editor').length || /photo|foto|avatar/i.test(name)) slot = 'square';
            var $lab = $ctl.closest('.gep-admin-field').find('.gep-field-label').first();
            if (!$lab.length) $lab = $ctl.closest('.gep-admin-field').children('label').first();
            var label = $.trim($lab.clone().children('.gep-optional,.gep-required-dot').remove().end().text());
            var $step = $ctl.closest('.gep-step-card,.gep-item-row,.gep-home-repeat-row');
            var where = $.trim($step.find('.gep-step-heading h3,summary strong').first().text());
            return {raw: raw, slot: slot, label: (where && label && where !== label ? where + ' · ' + label : (label || where)) || 'Imagen', $anchor: $btn};
        }
        var $custom = $btn.closest('.gep-custom-image-actions');
        if ($custom.length) return {raw: $custom.find('[data-custom-field="image"]').val() || '', slot: 'card', label: 'Imagen del apartado', $anchor: $btn};
        var $gal = $btn.closest('.gep-project-gallery-item');
        if ($gal.length) return {raw: $gal.attr('data-url') || '', slot: 'card', label: 'Imagen de la galería', $anchor: $btn};
        return null;
    }
    function buttonHtml(small){
        return '<button type="button" class="button' + (small ? ' button-small' : '') + ' gep-frame-open"><span class="dashicons dashicons-image-crop" aria-hidden="true"></span> ' + (small ? 'Encuadre' : 'Encuadre de la foto') + '<span class="gep-frame-badge" hidden>ajustado</span></button>';
    }
    function ensureButtons(){
        $('.gep-image-control').each(function(){
            var $a = $(this).children('.gep-image-actions');
            if ($a.length && !$a.children('.gep-frame-open').length) { var $b = $(buttonHtml(false)); var $sel = $a.children('.gep-select-image').first(); if ($sel.length) $b.insertAfter($sel); else $b.prependTo($a); }
        });
        $('.gep-custom-image-actions').each(function(){ var $a = $(this); if (!$a.children('.gep-frame-open').length) $(buttonHtml(false)).appendTo($a); });
        $('.gep-project-gallery-item-actions').each(function(){ var $a = $(this); if (!$a.children('.gep-frame-open').length) $(buttonHtml(true)).prependTo($a); });
    }
    function syncButtons(){
        ensureButtons();
        $('.gep-frame-open').each(function(){
            var $b = $(this), info = sourceInfo($b);
            var has = !!(info && $.trim(info.raw));
            if ($b.prop('hidden') !== !has) $b.prop('hidden', !has);
            var framed = false;
            if (has && info.slot === 'landscape') { var v = $.trim($('#gep_home_landscape_focus').val() || ''); framed = !!v && v !== '50% 46%'; }
            else if (has) framed = !!frames[frameKey(info.raw)];
            $b.toggleClass('is-framed', framed).find('.gep-frame-badge').prop('hidden', !framed);
        });
    }

    /* ---------- Formas de cada recuadro (aprox. a la página pública) ---------- */
    function viewRatios(slot, photoRatio){
        if (slot === 'hero') return {desktop: 1440 / 560, tablet: 820 / 520, mobile: 390 / 600};
        if (slot === 'square') return {desktop: 1, tablet: 1, mobile: 1};
        if (slot === 'landscape') {
            var fit = $('#gep_home_landscape_fit').val() === 'cover' ? 'cover' : 'auto';
            var r = photoRatio || 1.6;
            function strip(w, maxH){ if (fit === 'cover') return w / 560; if (r < 1.15) return Math.max(r, 0.8); return w / Math.min(Math.max(w / r, 380), maxH); }
            return {desktop: strip(1440, 774), tablet: strip(820, 900), mobile: strip(390, 700)};
        }
        return {desktop: 16 / 10, tablet: 3 / 2, mobile: 4 / 3};
    }

    /* ---------- Ventana de encuadre ---------- */
    var $modal = null, st = null, cur = null, lastFocus = null;

    function buildModal(){
        if ($modal) return $modal;
        $modal = $(
            '<div class="gep-frame-modal" hidden>' +
              '<div class="gep-frame-backdrop"></div>' +
              '<div class="gep-frame-dialog" role="dialog" aria-modal="true" aria-labelledby="gep-frame-title" tabindex="-1">' +
                '<div class="gep-frame-head"><span class="gep-frame-icon dashicons dashicons-image-crop" aria-hidden="true"></span><div><span>Encuadre de la foto</span><strong id="gep-frame-title"></strong></div></div>' +
                '<div class="gep-frame-body">' +
                  '<div class="gep-frame-main">' +
                    '<p class="gep-frame-help">Arrastra la foto dentro de cualquier vista, o pulsa en la foto completa el punto que siempre debe verse.</p>' +
                    '<div class="gep-frame-source" tabindex="0" role="button" aria-label="Foto completa: pulsa el punto que siempre debe verse. También funcionan las flechas del teclado."><img alt=""><span class="gep-frame-dot" aria-hidden="true"></span></div>' +
                    '<div class="gep-frame-controls">' +
                      '<label class="gep-frame-zoom"><span>Acercamiento</span><input type="range" min="1" max="2.5" step="0.05" value="1"><output>100 %</output></label>' +
                      '<p class="gep-frame-zoom-note" hidden></p>' +
                      '<label class="gep-frame-split"><input type="checkbox"> <span>Encuadre distinto en teléfonos</span></label>' +
                      '<div class="gep-frame-target" role="group" aria-label="Qué encuadre estás moviendo" hidden><button type="button" data-target="desktop" class="is-active">PC y tablet</button><button type="button" data-target="mobile">Teléfono</button></div>' +
                    '</div>' +
                  '</div>' +
                  '<div class="gep-frame-views">' +
                    '<figure data-view="desktop"><div class="gep-frame-box" tabindex="0" aria-label="Vista PC: arrastra para mover la foto"><img alt="" data-gep-frame></div><figcaption><span class="dashicons dashicons-desktop"></span> PC</figcaption></figure>' +
                    '<figure data-view="tablet"><div class="gep-frame-box" tabindex="0" aria-label="Vista tablet: arrastra para mover la foto"><img alt="" data-gep-frame></div><figcaption><span class="dashicons dashicons-tablet"></span> Tablet</figcaption></figure>' +
                    '<figure data-view="mobile"><div class="gep-frame-box" tabindex="0" aria-label="Vista teléfono: arrastra para mover la foto"><img alt="" data-gep-frame></div><figcaption><span class="dashicons dashicons-smartphone"></span> Teléfono</figcaption></figure>' +
                    '<p class="gep-frame-views-note">Formas aproximadas de cómo se ve en la página.</p>' +
                  '</div>' +
                '</div>' +
                '<div class="gep-frame-foot"><button type="button" class="button gep-frame-reset"><span class="dashicons dashicons-image-rotate" aria-hidden="true"></span> Restablecer</button><span class="gep-frame-spacer"></span><button type="button" class="button gep-frame-cancel">Cancelar</button><button type="button" class="button button-primary gep-frame-apply"><span class="dashicons dashicons-yes" aria-hidden="true"></span> Aplicar encuadre</button></div>' +
              '</div>' +
            '</div>'
        ).appendTo(document.body);
        bindModal();
        return $modal;
    }

    function point(target){
        if (target === 'mobile' && st.split) return {x: st.mx, y: st.my};
        return {x: st.x, y: st.y};
    }
    function setPoint(target, p){
        p = {x: round1(clamp(p.x, 0, 100)), y: round1(clamp(p.y, 0, 100))};
        if (target === 'mobile' && st.split) { st.mx = p.x; st.my = p.y; }
        else { st.x = p.x; st.y = p.y; if (!st.split) { st.mx = p.x; st.my = p.y; } }
        render();
    }

    function render(){
        if (!$modal || !st) return;
        var f = {x: st.x, y: st.y, z: cur.zoom ? st.z : 1};
        if (st.split) { f.mx = st.mx; f.my = st.my; }
        var editPoint = point(st.target);
        $modal.find('.gep-frame-dot').css({left: editPoint.x + '%', top: editPoint.y + '%'}).toggleClass('is-mobile', st.split && st.target === 'mobile');
        $modal.find('.gep-frame-views figure').each(function(){
            var view = $(this).attr('data-view'), $img = $(this).find('img'), el = $img[0];
            var p = (view === 'mobile' && st.split) ? {x: st.mx, y: st.my} : {x: st.x, y: st.y};
            var vars = frameVars({x: p.x, y: p.y, z: f.z}, cur.zoom);
            setVars(el, vars);
            if (cur.zoom && f.z > 1.001) $img.attr('data-gep-zoom', ''); else $img.removeAttr('data-gep-zoom');
            $(this).toggleClass('is-editing', st.split ? ((st.target === 'mobile') === (view === 'mobile')) : true);
        });
        $modal.find('.gep-frame-zoom input').val(st.z);
        $modal.find('.gep-frame-zoom output').text(Math.round(st.z * 100) + ' %');
        $modal.find('.gep-frame-split input').prop('checked', st.split);
        $modal.find('.gep-frame-target').prop('hidden', !st.split).find('button').each(function(){ $(this).toggleClass('is-active', $(this).attr('data-target') === st.target).attr('aria-pressed', $(this).attr('data-target') === st.target ? 'true' : 'false'); });
    }

    function layoutViews(){
        var r = viewRatios(cur.slot, cur.ratio);
        $modal.find('.gep-frame-views figure').each(function(){
            var view = $(this).attr('data-view');
            $(this).find('.gep-frame-box').css('aspect-ratio', String(Math.round(r[view] * 1000) / 1000));
        });
    }

    function open(info){
        buildModal();
        var display = resolveUrl(info.raw);
        cur = {key: frameKey(info.raw), slot: info.slot, zoom: info.slot === 'card' || info.slot === 'square', url: display, ratio: 0, $anchor: info.$anchor};
        var saved;
        if (info.slot === 'landscape') {
            var m = String($('#gep_home_landscape_focus').val() || '').match(/^(\d{1,3}(?:\.\d+)?)% (\d{1,3}(?:\.\d+)?)%$/);
            saved = m ? {x: parseFloat(m[1]), y: parseFloat(m[2]), z: 1} : {x: LANDSCAPE_DEFAULT.x, y: LANDSCAPE_DEFAULT.y, z: 1};
        } else {
            saved = normalize(frames[cur.key]);
        }
        st = {x: saved.x, y: saved.y, z: cur.zoom ? saved.z : 1, split: saved.mx !== undefined, mx: saved.mx === undefined ? saved.x : saved.mx, my: saved.my === undefined ? saved.y : saved.my, target: 'desktop'};
        cur.initial = $.extend({}, st);
        var labels = {hero: 'Cabecera', landscape: 'Mensaje de paisaje', square: 'Foto de persona', card: 'Imagen'};
        $modal.find('#gep-frame-title').text(info.label && info.label !== 'Imagen' ? info.label : labels[cur.slot]);
        $modal.find('.gep-frame-zoom').prop('hidden', !cur.zoom);
        var note = cur.slot === 'hero' ? 'En las cabeceras la foto siempre cubre todo el ancho: aquí eliges qué parte se ve.' : (cur.slot === 'landscape' ? 'La altura de la franja se elige abajo, en «Altura de la franja».' : '');
        $modal.find('.gep-frame-zoom-note').text(note).prop('hidden', !note);
        $modal.find('.gep-frame-split').prop('hidden', cur.slot === 'landscape');
        $modal.find('img').attr('src', display);
        lastFocus = document.activeElement;
        $modal.prop('hidden', false).addClass('is-open');
        $('body').addClass('gep-frame-modal-open');
        withRatio(display, function(ratio){ cur.ratio = ratio; layoutViews(); render(); });
        layoutViews(); render();
        $modal.find('.gep-frame-dialog').trigger('focus');
    }

    function close(){
        if (!$modal) return;
        $modal.prop('hidden', true).removeClass('is-open');
        $('body').removeClass('gep-frame-modal-open');
        if (lastFocus && document.body.contains(lastFocus)) { try { lastFocus.focus({preventScroll: true}); } catch (e) {} }
        st = null; cur = null;
    }

    function commit(){
        if (!st || !cur) return;
        if (cur.slot === 'landscape') {
            $('#gep_home_landscape_focus').val(Math.round(st.x) + '% ' + Math.round(st.y) + '%').trigger('change');
        } else {
            var f = normalize({x: st.x, y: st.y, z: cur.zoom ? st.z : 1, mx: st.split ? st.mx : undefined, my: st.split ? st.my : undefined});
            if (st.split && f.mx === f.x && f.my === f.y) { delete f.mx; delete f.my; }
            if (isDefault(f)) delete frames[cur.key]; else frames[cur.key] = f;
            $store().val(JSON.stringify(frames)).trigger('change');
        }
        scheduleApply();
    }

    function bindModal(){
        var drag = null;
        function evPoint(e){ var oe = e.originalEvent || e; var t = oe.touches && oe.touches[0] ? oe.touches[0] : oe; return {x: t.clientX, y: t.clientY}; }

        // Foto completa: pulsar / arrastrar el punto.
        $modal.on('pointerdown', '.gep-frame-source', function(e){
            e.preventDefault();
            var box = this, img = $(box).find('img')[0];
            try { box.setPointerCapture(e.originalEvent.pointerId); } catch (err) {}
            function set(ev){ var r = (img && img.getBoundingClientRect().width ? img : box).getBoundingClientRect(), p = evPoint(ev); setPoint(st.target, {x: (p.x - r.left) / r.width * 100, y: (p.y - r.top) / r.height * 100}); }
            set(e);
            drag = {type: 'source', set: set};
        });
        // Vistas: arrastrar la foto.
        $modal.on('pointerdown', '.gep-frame-box', function(e){
            e.preventDefault();
            var box = this, view = $(box).closest('figure').attr('data-view');
            var target = (view === 'mobile' && st.split) ? 'mobile' : ((st.split && view !== 'mobile') ? 'desktop' : st.target);
            if (st.split) { st.target = target; }
            try { box.setPointerCapture(e.originalEvent.pointerId); } catch (err) {}
            var rect = box.getBoundingClientRect(), w = rect.width, h = rect.height, ratio = cur.ratio || (w / h);
            var rw, rh; // tamaño de la foto cubriendo el recuadro
            if (w / h > ratio) { rw = w; rh = w / ratio; } else { rh = h; rw = h * ratio; }
            var z = cur.zoom ? st.z : 1, start = evPoint(e), p0 = point(target);
            drag = {type: 'box', move: function(ev){
                var p = evPoint(ev), dx = p.x - start.x, dy = p.y - start.y;
                var denX = w - z * rw, denY = h - z * rh, nx = p0.x, ny = p0.y;
                if (Math.abs(denX) > 1) nx = p0.x + dx * 100 / denX;
                if (Math.abs(denY) > 1) ny = p0.y + dy * 100 / denY;
                setPoint(target, {x: nx, y: ny});
            }};
            render();
        });
        $modal.on('pointermove', '.gep-frame-source, .gep-frame-box', function(e){
            if (!drag) return;
            if (drag.type === 'source') drag.set(e); else drag.move(e);
        });
        $modal.on('pointerup pointercancel lostpointercapture', '.gep-frame-source, .gep-frame-box', function(){ drag = null; });

        // Teclado: flechas mueven el punto (Mayús = más rápido).
        $modal.on('keydown', '.gep-frame-source, .gep-frame-box', function(e){
            var step = e.shiftKey ? 10 : 2, p = point(st.target), map = {ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step]};
            if (!map[e.key]) return;
            e.preventDefault();
            setPoint(st.target, {x: p.x + map[e.key][0], y: p.y + map[e.key][1]});
        });
        $modal.on('input change', '.gep-frame-zoom input', function(){ st.z = clamp($(this).val(), 1, 2.5); render(); });
        $modal.on('change', '.gep-frame-split input', function(){
            st.split = this.checked;
            if (!st.split) { st.mx = st.x; st.my = st.y; st.target = 'desktop'; }
            else st.target = 'mobile';
            render();
        });
        $modal.on('click', '.gep-frame-target button', function(e){ e.preventDefault(); st.target = $(this).attr('data-target') === 'mobile' ? 'mobile' : 'desktop'; render(); });
        $modal.on('click', '.gep-frame-reset', function(e){
            e.preventDefault();
            var d = cur.slot === 'landscape' ? LANDSCAPE_DEFAULT : {x: 50, y: 50};
            st.x = st.mx = d.x; st.y = st.my = d.y; st.z = 1; st.split = false; st.target = 'desktop';
            render();
        });
        $modal.on('click', '.gep-frame-cancel, .gep-frame-backdrop', function(e){ e.preventDefault(); close(); });
        $modal.on('click', '.gep-frame-apply', function(e){ e.preventDefault(); commit(); close(); });
        $modal.on('keydown', function(e){
            if (e.key === 'Escape') { e.preventDefault(); close(); return; }
            if (e.key !== 'Tab') return;
            // Mantiene el foco dentro de la ventana.
            var $f = $modal.find('button:visible, input:visible, [tabindex="0"]:visible').filter(':not([disabled])');
            if (!$f.length) return;
            var first = $f.first()[0], last = $f.last()[0];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
    }

    /* ---------- Arranque ---------- */
    $(document).on('click', '.gep-frame-open', function(e){
        e.preventDefault(); e.stopPropagation();
        var info = sourceInfo($(this));
        if (!info || !$.trim(info.raw)) return;
        open(info);
    });
    $(document).on('change input', 'input.gep-image-url, [data-custom-field="image"], #gep_home_landscape_focus', function(){ scheduleApply(); });

    $(function(){
        if (!$('#gep-content-form').length) return;
        $store();
        $('body').addClass('gep-has-frame-tool');
        applyAll();
        var root = document.querySelector('.gep-editor-wrap') || document.body;
        if (window.MutationObserver && root) {
            // Solo cambios de contenido o de foto (src/style): nunca desplazamiento.
            new MutationObserver(function(list){
                for (var i = 0; i < list.length; i++) {
                    var m = list[i];
                    if (m.target && m.target.closest && m.target.closest('.gep-frame-modal')) continue;
                    if (m.type === 'attributes' && m.attributeName === 'style' && m.target.hasAttribute && m.target.hasAttribute('data-gep-frame-sig') && !/url\(/.test(m.target.getAttribute('style') || '')) continue;
                    scheduleApply(); return;
                }
            }).observe(root, {subtree: true, childList: true, attributes: true, attributeFilter: ['src', 'style']});
        }
    });
})(window.jQuery);
