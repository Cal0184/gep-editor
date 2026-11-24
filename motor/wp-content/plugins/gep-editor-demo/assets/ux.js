/* GEP Editor · v9.25.0 · Comodidad del editor (includes/ux.php).
 * Barra de estado fija, vista previa que acompaña, ir a lo recién creado y
 * arrastre más cómodo. No guarda nada: solo cambia cómo se usa la pantalla. */
(function($){
    'use strict';
    if (!$) return;

    /* ---------------------------------------------------------------
     * 5 · Arrastre: valores comunes para TODAS las listas ordenables.
     * Se aplican antes de que admin.js / sections.js creen sus listas.
     * ------------------------------------------------------------- */
    var HANDLES = '.gep-grip,.gep-structure-grip,.gep-section-grip,.gep-directory-group-grip,.gep-project-gallery-grip,.gep-custom-item-head>.dashicons-menu-alt2';
    if ($.ui && $.ui.sortable) {
        $.extend($.ui.sortable.prototype.options, {
            distance: 6,               // un clic o un pequeño temblor no inicia un arrastre
            cursor: 'grabbing',
            scrollSensitivity: 90,     // cerca del borde la página se desplaza sola
            scrollSpeed: 22,
            tolerance: 'pointer'
        });
    }

    $(function(){
        var $form = $('#gep-content-form');
        if (!$form.length) return;
        var $bar = $form.find('.gep-contact-commandbar').first();

        function scrollParentOf(el){
            var node = el && el.parentElement;
            while (node && node !== document.documentElement) {
                var oy = window.getComputedStyle(node).overflowY;
                if ((oy === 'auto' || oy === 'scroll') && node.scrollHeight > node.clientHeight + 1) return node;
                node = node.parentElement;
            }
            return document.scrollingElement || document.documentElement;
        }

        /* -----------------------------------------------------------
         * 1 y 3 · Barra de estado y vista previa fijas: v9.24.1 lo hace
         * SOLO con CSS (ux.css). Aquí no se escucha el desplazamiento, no se
         * mide nada en bucle y no se escriben estilos: en la 9.24.0 ese
         * código (sentinela + is-stuck + variables CSS + ResizeObserver)
         * formaba un ciclo que hacía parpadear la pantalla.
         * --------------------------------------------------------- */
        function stickyOffset(){
            // Solo lectura y solo cuando se pide un desplazamiento.
            if (!$bar.length) return 14;
            var cs = window.getComputedStyle($bar[0]);
            if (cs.position !== 'sticky') return 14;
            return (parseFloat(cs.top) || 0) + $bar[0].getBoundingClientRect().height + 14;
        }
        // v9.25.0 · La barra inferior "Guardar cambios" también es fija: lo nuevo
        // no debe quedar escondido detrás de ella.
        function bottomOffset(){
            var $actions = $form.find('.gep-form-actions').filter(':visible').last();
            if (!$actions.length) return 12;
            var cs = window.getComputedStyle($actions[0]);
            if (cs.position !== 'sticky' && cs.position !== 'fixed') return 12;
            return $actions[0].getBoundingClientRect().height + 12;
        }

        /* v9.25.0 · Espacio para poder llevar lo nuevo arriba.
         * Lo recién creado suele quedar al final de la página y el navegador
         * no puede desplazarse más allá del final. Se agrega (una sola vez, al
         * crear) el espacio justo que falta. No se mide al desplazarse. */
        function ensureScrollRoom(sp, target){
            var max = sp.scrollHeight - sp.clientHeight;
            if (target <= max) return;
            var $wrap = $form.closest('.gep-editor-wrap');
            if (!$wrap.length) $wrap = $form.parent();
            var $room = $wrap.children('.gep-scroll-room');
            if (!$room.length) $room = $('<div class="gep-scroll-room" aria-hidden="true"></div>').appendTo($wrap);
            var current = parseFloat($room[0].style.height) || 0;
            $room[0].style.height = Math.min(current + (target - max) + 8, window.innerHeight) + 'px';
        }

        /* -----------------------------------------------------------
         * Desplazamiento suave respetando la barra fija (lo usan sections.js
         * y la detección de elementos nuevos).
         * --------------------------------------------------------- */
        window.gepScrollToElement = function(el, opts){
            if (!el) return;
            opts = opts || {};
            var sp = scrollParentOf(el);
            // v9.25.0: listas con su propio desplazamiento (p. ej. Proyectos):
            // primero se mueve la lista hasta el elemento (al instante) y luego
            // la página hasta la lista, para que lo nuevo quede realmente a la vista.
            var docScroller = document.scrollingElement || document.documentElement;
            var guard = 0;
            while (sp && sp !== docScroller && sp !== document.body && guard++ < 4) {
                var inner = sp, ir = inner.getBoundingClientRect(), er = el.getBoundingClientRect();
                var innerTarget = Math.max(0, Math.round(inner.scrollTop + er.top - ir.top - 8));
                if (Math.abs(innerTarget - inner.scrollTop) > 2) inner.scrollTop = innerTarget;
                var outer = scrollParentOf(inner);
                if (outer === inner) break;
                sp = outer;
            }
            var offset = stickyOffset();
            var rect = el.getBoundingClientRect();
            var portTop = sp === document.scrollingElement || sp === document.documentElement ? 0 : sp.getBoundingClientRect().top;
            var portH = sp.clientHeight || window.innerHeight;
            var current = sp.scrollTop;
            var target;
            if (opts.block === 'center') target = current + rect.top - portTop - Math.max(offset, (portH - rect.height) / 2);
            else if (opts.block === 'nearest') {
                var bottom = bottomOffset();
                if (rect.top - portTop >= offset && rect.bottom - portTop <= portH - bottom) return;
                // Si cabe entera, se deja completa a la vista; si no, se muestra desde arriba.
                if (rect.height <= portH - offset - bottom - 12 && rect.top - portTop >= offset) target = current + (rect.bottom - portTop) - (portH - bottom);
                else target = current + rect.top - portTop - offset;
            } else target = current + rect.top - portTop - offset;
            target = Math.max(0, Math.round(target));
            if (opts.makeRoom) ensureScrollRoom(sp, target);
            if (Math.abs(target - current) < 4) return;
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            try { sp.scrollTo({top: target, behavior: reduce ? 'auto' : 'smooth'}); } catch(e) { sp.scrollTop = target; }
        };
        function flash(el){
            var $el = $(el);
            $el.removeClass('gep-just-added');
            void el.offsetWidth;
            $el.addClass('gep-just-added');
            window.setTimeout(function(){ $el.removeClass('gep-just-added'); }, 1600);
        }
        window.gepFlashElement = flash;

        /* v9.25.0 · Deja lo nuevo listo para escribir: cursor en el primer
         * campo de texto vacío, sin provocar otro salto de pantalla. */
        function focusFirstField(el){
            var $field = $(el).find('input[type="text"],input[type="url"],input:not([type]),textarea').filter(function(){
                return $(this).is(':visible') && !this.disabled && !this.readOnly && !$(this).closest('[aria-hidden="true"],.gep-directory-item-search').length;
            });
            var $empty = $field.filter(function(){ return !$.trim(this.value || ''); });
            var input = ($empty.length ? $empty : $field).get(0);
            if (!input) return;
            try { input.focus({preventScroll: true}); } catch(e) { input.focus(); }
        }

        /* Un elemento "nuevo" de verdad: la tarjeta, fila o apartado completo,
         * no un pedacito interno (un ícono, un texto de ayuda). */
        var NEW_ITEM = '.gep-section-builder,.gep-structure-row,.gep-item-row,.gep-directory-list-row,.gep-directory-group,.gep-home-repeat-row,.gep-team-card-row,.gep-contact-row,.gep-publication-row,.gep-publication-record-list-row,.gep-project-record-list-row,.gep-project-area-row,.gep-docencia-course-row,.gep-docencia-step-row,.gep-service-row,.gep-hub-row,.gep-hub-record-row,.gep-custom-item,.gep-button-row';
        window.gepRevealNew = function(el, opts){
            if (!el || !document.body.contains(el)) return;
            opts = opts || {};
            window.gepScrollToElement(el, {block: 'start', makeRoom: true});
            flash(el);
            if (opts.focus !== false) window.setTimeout(function(){ focusFirstField(opts.focusIn || el); }, 360);
        };

        /* -----------------------------------------------------------
         * 2 · Al crear algo nuevo, llevar la vista hasta allí.
         * Funciona con todos los botones "Añadir…" del editor: se observa
         * qué aparece en el formulario justo después del clic.
         * v9.25.0: se lleva el elemento ARRIBA (bajo la barra fija), aunque
         * esté al final de la página, y se deja el cursor en su primer campo.
         * --------------------------------------------------------- */
        var ADD_BUTTONS = '.gep-add-section,.gep-add-item,.gep-add-team-card,.gep-add-contact-row,.gep-home-add,.gep-publication-add,.gep-project-add-record,.gep-project-add-area,.gep-docencia-add-course,.gep-docencia-add-step,.gep-service-add,.gep-hub-add,.gep-custom-add-item,.gep-directory-group-add-button,.gep-directory-add-to-group';
        var watch = null;
        function isUsable(node){
            if (!node || node.nodeType !== 1) return false;
            if (!document.body.contains(node)) return false;
            var $n = $(node);
            if ($n.closest('.gep-preview-column,.gep-legacy-section-parking,.gep-structure-hover-preview,[aria-hidden="true"]').length) return false;
            if (!$n.is(':visible')) return false;
            var r = node.getBoundingClientRect();
            return r.height >= 24 && r.width >= 60;
        }
        function finishWatch(){
            if (!watch) return;
            var w = watch; watch = null;
            w.observer.disconnect();
            window.clearTimeout(w.timer);
            var nodes = w.nodes.filter(function(n){
                if (!isUsable(n)) return false;
                // Solo el nodo exterior de cada grupo añadido.
                for (var i = 0; i < w.nodes.length; i++) if (w.nodes[i] !== n && w.nodes[i].contains && w.nodes[i].contains(n)) return false;
                return true;
            });
            if (!nodes.length) return;
            // Algunas listas se vuelven a dibujar completas al añadir: entre todo
            // lo que apareció se elige, en este orden, la ficha de edición que se
            // abrió, la fila marcada como activa o, si no, lo último que apareció.
            function itemFor(n){
                var $item = $(n).closest(NEW_ITEM);
                if (!$item.length || !$form[0].contains($item[0])) return n;
                // Nunca subir hasta el apartado completo desde algo que está dentro de su editor.
                if ($item.is('.gep-structure-row') && $(n).closest('.gep-structure-inline-editor').length && !$(n).is('.gep-structure-row')) return n;
                return $item[0];
            }
            var FICHA = /(^|\s)gep-[a-z-]*(single-editor|item-editor|record-editor|editor-panel|ficha)(\s|$)/;
            var ficha = null, active = null, last = null;
            nodes.forEach(function(n){
                var cls = String(n.className || '');
                if (FICHA.test(cls) && isUsable(n)) ficha = n;
                var item = itemFor(n);
                if (/(^|\s)is-active(\s|$)/.test(String(item.className || ''))) active = item;
                last = item;
            });
            var target = ficha || active || last;
            window.gepRevealNew(target, {focusIn: target});
            // Si la ficha terminó de abrirse un instante después y se movió, se corrige una sola vez.
            var top0 = target.getBoundingClientRect().top;
            window.setTimeout(function(){
                if (!document.body.contains(target)) return;
                if (Math.abs(target.getBoundingClientRect().top - stickyOffset()) > 40 && Math.abs(target.getBoundingClientRect().top - top0) > 40) window.gepScrollToElement(target, {block: 'start', makeRoom: true});
            }, 700);
        }
        // v9.25.0: escucha nativa en fase de captura. admin.js hace
        // $(document).off('click','.gep-add-section') al iniciar y eso borraba
        // también este aviso: en Quiénes somos el apartado nuevo no se mostraba.
        document.addEventListener('click', function(e){
            var btn = e.target && e.target.closest ? e.target.closest(ADD_BUTTONS) : null;
            if (!btn || !$form[0].contains(btn)) return;
            if (!window.MutationObserver) return;
            if (watch) finishWatch();
            var w = {nodes: []};
            w.observer = new window.MutationObserver(function(list){
                list.forEach(function(m){
                    Array.prototype.forEach.call(m.addedNodes, function(n){ if (n.nodeType === 1) w.nodes.push(n); });
                    // Fichas que ya existían y se muestran al crear (quitan "is-hidden").
                    if (m.type === 'attributes' && /(^|\s)is-hidden(\s|$)/.test(m.oldValue || '') && !$(m.target).hasClass('is-hidden')) w.nodes.push(m.target);
                });
                window.clearTimeout(w.timer);
                w.timer = window.setTimeout(finishWatch, 140);
            });
            w.observer.observe($form[0], {childList: true, subtree: true, attributes: true, attributeFilter: ['class'], attributeOldValue: true});
            w.timer = window.setTimeout(finishWatch, 900);
            watch = w;
        }, true);

        /* -----------------------------------------------------------
         * 5 · Arrastre: zonas de agarre visibles y accesibles, aviso
         * de dónde se soltará, y flechas ↑ ↓ del teclado.
         * --------------------------------------------------------- */
        function prepareHandles(){
            $form.find(HANDLES).each(function(){
                if (this.getAttribute('data-gep-handle')) return;
                this.setAttribute('data-gep-handle', '1');
                if (!$(this).closest('.ui-sortable').length) return;
                this.setAttribute('tabindex', '0');
                this.setAttribute('role', 'button');
                if (!this.getAttribute('aria-label')) this.setAttribute('aria-label', 'Mover. Arrastra o usa las flechas arriba y abajo.');
                if (!this.getAttribute('title')) this.setAttribute('title', 'Arrastra para mover (o usa ↑ ↓ del teclado)');
            });
        }
        var prepTimer = null;
        if (window.MutationObserver) new window.MutationObserver(function(){ window.clearTimeout(prepTimer); prepTimer = window.setTimeout(prepareHandles, 120); }).observe($form[0], {childList: true, subtree: true});
        window.setTimeout(prepareHandles, 300);

        $(document).on('sortstart', '#gep-content-form', function(e, ui){
            $('body').addClass('gep-is-sorting');
            if (ui && ui.placeholder && ui.placeholder.length) {
                ui.placeholder.attr('data-gep-drop', 'Soltar aquí');
                if (ui.item && ui.item.length) ui.placeholder.css('min-height', Math.min(Math.max(ui.item.outerHeight(), 44), 90) + 'px');
            }
        });
        $(document).on('sortstop', '#gep-content-form', function(){ $('body').removeClass('gep-is-sorting'); });

        var $live = $('<div class="screen-reader-text" aria-live="polite"></div>').appendTo('body');
        $(document).on('keydown', '[data-gep-handle]', function(e){
            if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
            var handle = this, $c = $(handle).closest('.ui-sortable');
            if (!$c.length) return;
            var inst = $c.sortable('instance');
            if (!inst || inst.options.disabled) return;
            e.preventDefault(); e.stopPropagation();
            var sel = inst.options.items || '> *';
            var $items = $c.find(sel).filter(':visible');
            var $item = $items.filter(function(){ return this === handle || $.contains(this, handle); }).last();
            var from = $items.index($item), to = from + (e.key === 'ArrowUp' ? -1 : 1);
            if (from < 0 || to < 0 || to >= $items.length) return;
            if (to < from) $item.insertBefore($items.eq(to)); else $item.insertAfter($items.eq(to));
            inst.currentItem = $item;
            var ui = inst._uiHash();
            try { inst._trigger('change', null, ui); } catch(err) {}
            try { inst._trigger('update', null, ui); } catch(err) {}
            try { inst._trigger('stop', null, ui); } catch(err) {}
            window.setTimeout(function(){
                prepareHandles();
                var $again = $c.find(sel).filter(':visible').eq(to).find('[data-gep-handle]').first();
                if ($again.length) { $again[0].focus({preventScroll: true}); window.gepScrollToElement($again.closest($c.find(sel))[0] || $again[0], {block: 'nearest'}); }
                $live.text('Movido a la posición ' + (to + 1) + ' de ' + $items.length + '.');
            }, 40);
        });
        // Un clic en la zona de agarre no debe abrir la fila (evita aperturas accidentales).
        $(document).on('click', '[data-gep-handle]', function(e){ e.preventDefault(); });
    });
})(window.jQuery);
