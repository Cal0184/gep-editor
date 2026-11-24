/**
 * GEP Editor · Ayuda visual.
 * - Página Ayuda: buscador, tutoriales paso a paso y mapa del editor.
 * - Editor: botón "?" en cada apartado y recorrido de bienvenida.
 * Datos en window.gepHelpData (includes/help.php).
 */
(function () {
    'use strict';

    var data = window.gepHelpData || {};
    var tutorials = data.tutorials || [];

    // Zonas del esquema del editor, en % del escenario: left, top, width, height.
    var ZONES = {
        tabs: [2, 3, 40, 10], dev: [82, 3, 16, 10],
        list: [2, 15, 36, 12], list2: [2, 28, 36, 12], field: [2, 41, 36, 12],
        prev: [40, 15, 58, 66], add: [2, 84, 18, 12], save: [74, 84, 24, 12]
    };

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text) node.textContent = text;
        return node;
    }
    function findTutorial(id) {
        for (var i = 0; i < tutorials.length; i++) if (tutorials[i].id === id) return tutorials[i];
        return null;
    }
    function plain(text) {
        return String(text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }
    function isVisible(node) {
        return !!(node && node.getClientRects().length && getComputedStyle(node).visibility !== 'hidden');
    }

    /* ---------------- Reproductor de tutoriales ---------------- */
    var player = null, current = null, stepIndex = 0, lastFocus = null;

    function buildPlayer() {
        player = el('div', 'gep-hp');
        player.hidden = true;
        player.innerHTML =
            '<div class="gep-hp-box" role="dialog" aria-modal="true" aria-labelledby="gep-hp-title">' +
            '<div class="gep-hp-head"><strong id="gep-hp-title"></strong><button type="button" class="gep-hp-x" aria-label="Cerrar">&times;</button></div>' +
            '<div class="gep-hp-stage" aria-hidden="true"><i class="gep-hp-tabs"></i><i class="gep-hp-dev"></i><i class="gep-hp-list"></i><i class="gep-hp-prev"></i><i class="gep-hp-add"></i><i class="gep-hp-save"></i>' +
            '<span class="gep-hp-target"></span><svg class="gep-hp-cursor" viewBox="0 0 18 18"><path d="M2 1l12 8-5 1 3 6-2 1-3-6-4 3z" fill="#fff" stroke="#13201a" stroke-width="1.2" stroke-linejoin="round"/></svg></div>' +
            '<div class="gep-hp-text" aria-live="polite"><small></small><p></p></div>' +
            '<div class="gep-hp-foot"><span class="gep-hp-dots"></span><span class="gep-hp-btns"><button type="button" class="button gep-hp-prev-btn">Atrás</button><button type="button" class="button button-primary gep-hp-next-btn">Siguiente</button></span></div>' +
            '</div>';
        document.body.appendChild(player);

        player.querySelector('.gep-hp-x').addEventListener('click', closePlayer);
        player.querySelector('.gep-hp-prev-btn').addEventListener('click', function () { if (stepIndex > 0) { stepIndex--; renderStep(); } });
        player.querySelector('.gep-hp-next-btn').addEventListener('click', function () {
            if (stepIndex < current.steps.length - 1) { stepIndex++; renderStep(); } else { closePlayer(); }
        });
        player.addEventListener('click', function (e) { if (e.target === player) closePlayer(); });
        player.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { e.preventDefault(); closePlayer(); }
            else if (e.key === 'ArrowRight') player.querySelector('.gep-hp-next-btn').click();
            else if (e.key === 'ArrowLeft') player.querySelector('.gep-hp-prev-btn').click();
            else if (e.key === 'Tab') {
                // Mantiene el foco dentro del diálogo.
                var items = player.querySelectorAll('button:not([disabled])');
                var first = items[0], last = items[items.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
    }

    function renderStep() {
        var step = current.steps[stepIndex];
        var z = ZONES[step[0]] || ZONES.prev;
        var target = player.querySelector('.gep-hp-target');
        target.style.left = 'calc(' + z[0] + '% - 3px)';
        target.style.top = 'calc(' + z[1] + '% - 3px)';
        target.style.width = 'calc(' + z[2] + '% + 6px)';
        target.style.height = 'calc(' + z[3] + '% + 6px)';
        var cursor = player.querySelector('.gep-hp-cursor');
        cursor.style.left = (z[0] + z[2] * 0.6) + '%';
        cursor.style.top = (z[1] + z[3] * 0.55) + '%';

        player.querySelector('.gep-hp-text small').textContent = 'Paso ' + (stepIndex + 1) + ' de ' + current.steps.length;
        player.querySelector('.gep-hp-text p').textContent = step[1];
        var dots = player.querySelector('.gep-hp-dots');
        dots.innerHTML = '';
        for (var i = 0; i < current.steps.length; i++) dots.appendChild(el('i', i === stepIndex ? 'is-on' : ''));
        player.querySelector('.gep-hp-prev-btn').disabled = stepIndex === 0;
        player.querySelector('.gep-hp-next-btn').textContent = stepIndex === current.steps.length - 1 ? 'Listo' : 'Siguiente';
    }

    function openPlayer(id) {
        current = findTutorial(id);
        if (!current || !current.steps.length) return;
        if (!player) buildPlayer();
        stepIndex = 0;
        lastFocus = document.activeElement;
        player.querySelector('#gep-hp-title').textContent = current.title;
        player.hidden = false;
        renderStep();
        player.querySelector('.gep-hp-next-btn').focus();
    }

    function closePlayer() {
        if (!player || player.hidden) return;
        player.hidden = true;
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    /* ---------------- Página Ayuda ---------------- */
    function initHelpPage() {
        var root = document.querySelector('.gep-ayuda');
        if (!root) return;

        root.addEventListener('click', function (e) {
            var card = e.target.closest('[data-gep-tutorial]');
            if (card) { e.preventDefault(); openPlayer(card.getAttribute('data-gep-tutorial')); }
        });

        // Buscador: filtra todos los elementos de la ayuda y oculta secciones vacías.
        var search = document.getElementById('gep-ayuda-q');
        var noResults = document.getElementById('gep-ayuda-noresults');
        var sections = root.querySelectorAll('.gep-ayuda-card');
        if (search) {
            search.addEventListener('input', function () {
                var q = plain(search.value.trim());
                var any = false;
                sections.forEach(function (section) {
                    var visible = 0;
                    section.querySelectorAll('.gep-ayuda-item').forEach(function (item) {
                        var ok = !q || plain(item.getAttribute('data-keys') + ' ' + item.textContent).indexOf(q) > -1;
                        item.hidden = !ok;
                        if (ok) visible++;
                    });
                    section.hidden = !!q && !visible;
                    if (visible) any = true;
                });
                if (noResults) noResults.hidden = any;
            });
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && search.value) { e.stopPropagation(); search.value = ''; search.dispatchEvent(new Event('input')); }
            });
            // Ctrl + K / ⌘ + K: ir al buscador.
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && !e.altKey && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    search.focus();
                    search.select();
                }
            });
            if (/Mac|iPhone|iPad/.test(navigator.platform || '')) {
                var hint = root.querySelector('.gep-ayuda-kbd-hint');
                if (hint) hint.textContent = '⌘ K';
            }
        }

        // Menú lateral: marca la sección visible.
        var links = root.querySelectorAll('.gep-ayuda-nav a');
        function setActive(id) {
            links.forEach(function (a) {
                var on = a.getAttribute('data-target') === id;
                a.classList.toggle('is-active', on);
                if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
            });
        }
        links.forEach(function (a) {
            a.addEventListener('click', function (e) {
                var target = document.getElementById(a.getAttribute('data-target'));
                if (!target || target.hidden) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
                setActive(target.id);
                if (history.replaceState) history.replaceState(null, '', '#' + target.id);
            });
        });
        if ('IntersectionObserver' in window && links.length) {
            var tracked = [];
            links.forEach(function (a) { var s = document.getElementById(a.getAttribute('data-target')); if (s) tracked.push(s); });
            var observer = new IntersectionObserver(function () {
                // La sección activa es la última cuyo borde superior ya pasó el tercio superior de la pantalla.
                var line = window.innerHeight * 0.33, current = tracked[0];
                tracked.forEach(function (s) { if (!s.hidden && s.getBoundingClientRect().top <= line) current = s; });
                if (current) setActive(current.id);
            }, { threshold: [0, 0.25, 0.5, 1], rootMargin: '0px 0px -40% 0px' });
            tracked.forEach(function (s) { observer.observe(s); });
        }

        // Mapa del editor.
        var hots = root.querySelectorAll('.gep-ayuda-hot');
        var items = root.querySelectorAll('.gep-ayuda-legend li');
        function activate(index) {
            hots.forEach(function (h) { h.classList.toggle('is-on', h.getAttribute('data-index') === String(index)); });
            items.forEach(function (li) { li.classList.toggle('is-on', li.getAttribute('data-index') === String(index)); });
        }
        hots.forEach(function (h) { h.addEventListener('click', function () { activate(h.getAttribute('data-index')); }); });
        items.forEach(function (li) { li.querySelector('button').addEventListener('click', function () { activate(li.getAttribute('data-index')); }); });
        if (items.length) activate(0);
    }

    /* ---------------- Ctrl + S en el editor ---------------- */
    function initSaveShortcut() {
        document.addEventListener('keydown', function (e) {
            if (!(e.ctrlKey || e.metaKey) || e.altKey || e.shiftKey || (e.key !== 's' && e.key !== 'S')) return;
            var buttons = document.querySelectorAll('.gep-save-button');
            for (var i = 0; i < buttons.length; i++) {
                if (isVisible(buttons[i])) {
                    e.preventDefault();
                    // Si está desactivado no hay cambios pendientes o falta un campo obligatorio.
                    if (!buttons[i].disabled) buttons[i].click();
                    else buttons[i].focus();
                    return;
                }
            }
        });
    }
    /* ---------------- Botón ? en cada apartado ---------------- */
    var openPop = null;

    function tutorialForCard(card) {
        if (card.querySelector('.gep-hero-video-field')) return 'portada';
        var buttons = card.querySelectorAll('button, .button');
        for (var i = 0; i < buttons.length; i++) {
            if (/añadir/i.test(buttons[i].textContent || '')) return 'anadir';
        }
        return 'texto';
    }

    // El apartado tiene overflow:hidden, así que el globo vive en <body> con posición fija.
    function placePop(pop, btn) {
        var r = btn.getBoundingClientRect();
        var w = pop.offsetWidth, h = pop.offsetHeight;
        var left = Math.min(Math.max(12, r.right - w), document.documentElement.clientWidth - w - 12);
        var top = r.bottom + 8 + h < window.innerHeight ? r.bottom + 8 : Math.max(12, r.top - h - 8);
        pop.style.left = left + 'px';
        pop.style.top = top + 'px';
    }

    function closePop() {
        if (!openPop) return;
        openPop.pop.hidden = true;
        openPop.btn.setAttribute('aria-expanded', 'false');
        openPop = null;
    }

    function initContextHelp() {
        var headings = document.querySelectorAll('.gep-step-card > .gep-step-heading');
        headings.forEach(function (heading, n) {
            if (heading.querySelector('.gep-hq-btn')) return;
            var card = heading.parentNode;
            var tutorial = findTutorial(tutorialForCard(card));
            if (!tutorial) return;
            var titleNode = heading.querySelector('h3');
            var title = titleNode ? titleNode.textContent : 'Este apartado';
            var popId = 'gep-hq-pop-' + n;

            heading.classList.add('gep-hq-host');
            var btn = el('button', 'gep-hq-btn', '?');
            btn.type = 'button';
            btn.setAttribute('aria-expanded', 'false');
            btn.setAttribute('aria-controls', popId);
            btn.setAttribute('aria-label', 'Ayuda: ' + title);

            var pop = el('div', 'gep-hq-pop');
            pop.id = popId;
            pop.hidden = true;
            pop.appendChild(el('strong', '', tutorial.title));
            var list = el('ol');
            tutorial.steps.filter(function (s, i) {
                // Dentro del apartado sobra el paso "elige la página / abre el apartado".
                return !(i === 0 && tutorial.steps.length > 3 && (s[0] === 'tabs' || s[0] === 'list'));
            }).slice(0, 3).forEach(function (s) { list.appendChild(el('li', '', s[1])); });
            pop.appendChild(list);
            var play = el('button', 'gep-hq-play', '▶ Ver tutorial de 1 minuto');
            play.type = 'button';
            pop.appendChild(play);

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var wasOpen = openPop && openPop.pop === pop;
                closePop();
                if (!wasOpen) {
                    pop.hidden = false;
                    placePop(pop, btn);
                    btn.setAttribute('aria-expanded', 'true');
                    openPop = { pop: pop, btn: btn };
                }
            });
            play.addEventListener('click', function (e) {
                e.preventDefault();
                closePop();
                openPlayer(tutorial.id);
            });
            pop.addEventListener('click', function (e) { e.stopPropagation(); });

            var chevron = heading.querySelector('.gep-universal-chevron');
            heading.insertBefore(btn, chevron);
            document.body.appendChild(pop);
        });

        document.addEventListener('click', closePop);
        window.addEventListener('resize', closePop);
        window.addEventListener('scroll', function () { if (openPop) placePop(openPop.pop, openPop.btn); }, true);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && openPop) { var b = openPop.btn; closePop(); b.focus(); }
        });
    }

    /* ---------------- Recorrido de bienvenida ---------------- */
    var TOUR = [
        // v10.0.0: recorrido completo del editor actual (pasos que no se ven en la pantalla se saltan solos).
        ['.gep-page-tabs', 'Elige la página', 'Este menú tiene una entrada por cada página del sitio: Inicio, Quiénes somos, Proyectos…'],
        ['.gep-structure-row', 'Apartados de la página', 'Cada fila es una parte de la página. Haz clic para abrirla y editarla; otro clic la cierra. Arrastra desde las rayas ≡ para cambiar el orden.'],
        ['.gep-structure-add', 'Añadir apartado', 'Agrega una sección nueva. Luego puedes moverla, ocultarla o eliminarla.'],
        ['.gep-step-card .gep-hq-btn', 'Ayuda en cada apartado', 'El botón ? explica en tres pasos qué hacer en ese apartado.'],
        ['.gep-preview-device-switcher', 'Revisa antes de guardar', 'La vista previa muestra el cambio al instante. Prueba PC, Tablet y Móvil.'],
        ['.gep-inline-save-state', 'Estado de la página', 'Aquí ves si hay cambios sin guardar. Si cierras la pestaña con cambios, el navegador te avisa.'],
        ['.gep-save-button', 'Guarda', 'Guardar cambios publica en el sitio y crea una versión que puedes deshacer en Historial de cambios. Si se corta la sesión o Internet, no se pierde nada: aparece un aviso.'],
        ['.gep-top-folders', 'Carpetas de imágenes', 'Ordena fotos y documentos en carpetas. Al abrir un archivo verás en qué páginas se usa.'],
        ['.gep-top-help', 'Ayuda', 'Tutoriales cortos con buscador. Desde ahí puedes volver a ver este recorrido.'],
        ['.gep-theme-toggle', 'Modo claro u oscuro', 'Cambia los colores del editor. No afecta al sitio público.']
    ];

    function firstVisible(selector) {
        var nodes = document.querySelectorAll(selector);
        for (var i = 0; i < nodes.length; i++) if (isVisible(nodes[i])) return nodes[i];
        return null;
    }

    function markTourDone() {
        if (!data.ajaxUrl || !window.fetch || !window.FormData) return;
        var body = new FormData();
        body.append('action', 'gep_help_tour_done');
        body.append('nonce', data.nonce || '');
        fetch(data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body }).catch(function () {});
    }

    function startTour() {
        var steps = [];
        TOUR.forEach(function (s) { var node = firstVisible(s[0]); if (node) steps.push({ node: node, title: s[1], text: s[2] }); });
        if (!steps.length) return;

        var index = 0;
        var ring = el('div', 'gep-tour-ring');
        var card = el('div', 'gep-tour-card');
        card.setAttribute('role', 'dialog');
        card.setAttribute('aria-live', 'polite');
        card.innerHTML = '<div class="gep-tour-row"><strong></strong><button type="button" class="gep-tour-skip">Saltar</button></div><p></p>' +
            '<div class="gep-tour-row"><small></small><button type="button" class="gep-tour-next">Siguiente</button></div>';
        document.body.appendChild(ring);
        document.body.appendChild(card);

        function place() {
            var r = steps[index].node.getBoundingClientRect();
            var sx = window.pageXOffset, sy = window.pageYOffset, pad = 6;
            ring.style.left = (r.left + sx - pad) + 'px';
            ring.style.top = (r.top + sy - pad) + 'px';
            ring.style.width = (r.width + pad * 2) + 'px';
            ring.style.height = (r.height + pad * 2) + 'px';
            var cw = card.offsetWidth, ch = card.offsetHeight;
            var left = Math.min(Math.max(16, r.left), document.documentElement.clientWidth - cw - 16);
            var below = r.bottom + 14 + ch < window.innerHeight;
            var top = below ? r.bottom + 14 : Math.max(16, r.top - ch - 14);
            card.style.left = (left + sx) + 'px';
            card.style.top = (top + sy) + 'px';
        }
        function show() {
            var s = steps[index];
            card.querySelector('strong').textContent = s.title;
            card.querySelector('p').textContent = s.text;
            card.querySelector('small').textContent = (index + 1) + ' de ' + steps.length;
            card.querySelector('.gep-tour-next').textContent = index === steps.length - 1 ? 'Listo' : 'Siguiente';
            var r = s.node.getBoundingClientRect();
            if (r.top < 60 || r.bottom > window.innerHeight - 160) s.node.scrollIntoView({ block: 'center' });
            place();
            card.querySelector('.gep-tour-next').focus();
        }
        function end() {
            markTourDone();
            window.removeEventListener('resize', place);
            window.removeEventListener('scroll', place, true);
            document.removeEventListener('keydown', onKey);
            ring.remove();
            card.remove();
        }
        function onKey(e) { if (e.key === 'Escape') end(); }

        card.querySelector('.gep-tour-skip').addEventListener('click', end);
        card.querySelector('.gep-tour-next').addEventListener('click', function () {
            if (index < steps.length - 1) { index++; show(); } else { end(); }
        });
        window.addEventListener('resize', place);
        window.addEventListener('scroll', place, true);
        document.addEventListener('keydown', onKey);
        show();
    }

    function whenEditorReady(callback) {
        var waited = 0;
        (function check() {
            var loader = document.getElementById('gep-editor-loader');
            if (!loader || loader.classList.contains('is-done') || waited > 8000) { setTimeout(callback, 400); return; }
            waited += 200;
            setTimeout(check, 200);
        })();
    }

    /* ---------------- Arranque ---------------- */
    function init() {
        if (data.context === 'help') initHelpPage();
        if (data.context === 'editor') {
            initContextHelp();
            initSaveShortcut();
            if (data.showTour) {
                // Quita gep_tour=1 de la dirección: al recargar o guardar no vuelve a abrirse solo.
                try {
                    var url = new URL(window.location.href);
                    if (url.searchParams.has('gep_tour')) { url.searchParams.delete('gep_tour'); window.history.replaceState(null, '', url.toString()); }
                } catch (err) {}
                whenEditorReady(startTour);
            }
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
