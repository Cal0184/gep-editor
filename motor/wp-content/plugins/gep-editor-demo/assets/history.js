(function(){
    'use strict';

    function all(selector, root){ return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }

    var MODE_KEY = 'gepHistoryCompareMode';
    function readMode(){ try { return window.localStorage.getItem(MODE_KEY) || ''; } catch(e){ return ''; } }
    function saveMode(mode){ try { window.localStorage.setItem(MODE_KEY, mode); } catch(e){} }

    function openDialog(dialog){
        if(!dialog){ return; }
        if(typeof dialog.showModal === 'function'){ dialog.showModal(); } else { dialog.setAttribute('open', 'open'); }
        var input = dialog.querySelector('input[type="text"]');
        if(input){ window.setTimeout(function(){ input.focus(); }, 30); }
    }
    function closeDialog(dialog){
        if(!dialog){ return; }
        if(typeof dialog.close === 'function'){ dialog.close(); } else { dialog.removeAttribute('open'); }
    }

    document.addEventListener('DOMContentLoaded', function(){
        // Restaurar: ventana propia en la página.
        document.addEventListener('click', function(e){
            var opener = e.target.closest('[data-gep-hx-open]');
            if(opener){
                e.preventDefault();
                openDialog(document.getElementById(opener.getAttribute('data-gep-hx-open')));
                return;
            }
            var closer = e.target.closest('[data-gep-hx-close]');
            if(closer){
                e.preventDefault();
                closeDialog(closer.closest('dialog'));
                return;
            }
            if(e.target.tagName === 'DIALOG' && e.target.classList.contains('gep-hx-dialog')){
                closeDialog(e.target); // clic en el fondo oscuro
            }
            // Cierra los menús "más opciones" abiertos al hacer clic fuera.
            all('.gep-hx-more[open]').forEach(function(menu){ if(!menu.contains(e.target)){ menu.open = false; } });
        });

        all('.gep-hx-restore-form').forEach(function(form){
            form.addEventListener('submit', function(){
                var button = form.querySelector('button[type="submit"]');
                if(button){
                    window.setTimeout(function(){ button.disabled = true; }, 0);
                    button.innerHTML = '<span class="dashicons dashicons-update"></span> Restaurando…';
                }
            });
        });

        // Comparar: modos Lado a lado / Solo diferencias / Vista previa.
        var compare = document.querySelector('.gep-hx-compare');
        if(compare){
            var buttons = all('[data-gep-hx-mode]', compare);
            var setMode = function(mode, remember){
                if(!buttons.some(function(b){ return b.getAttribute('data-gep-hx-mode') === mode; })){ mode = 'side'; }
                compare.setAttribute('data-mode', mode);
                buttons.forEach(function(b){
                    var active = b.getAttribute('data-gep-hx-mode') === mode;
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                if(remember){ saveMode(mode); }
            };
            buttons.forEach(function(b){ b.addEventListener('click', function(){ setMode(b.getAttribute('data-gep-hx-mode'), true); }); });
            if(buttons.length){ setMode(readMode() || 'side', false); }

            // Marca A y B a la vista dentro de la tira de versiones.
            var active = compare.querySelector('.gep-hx-version.is-b') || compare.querySelector('.gep-hx-version.is-a');
            if(active && active.parentNode){
                active.parentNode.scrollLeft = Math.max(0, active.offsetLeft - active.parentNode.clientWidth / 2);
            }
            all('.gep-hx-version.is-a, .gep-hx-version.is-b', compare).forEach(function(a){
                a.addEventListener('click', function(e){ e.preventDefault(); });
            });
        }

        // Deslizador de imagen antes / después.
        all('[data-gep-hx-slider]').forEach(function(slider){
            var range = slider.querySelector('input[type="range"]');
            if(!range){ return; }
            var update = function(){ slider.style.setProperty('--cut', range.value + '%'); };
            range.addEventListener('input', update);
            update();
        });

        // Resalta la versión recién creada (por ejemplo, tras restaurar).
        var wrap = document.querySelector('.gep-history-wrap');
        var focus = wrap ? wrap.getAttribute('data-focus-entry') : '';
        if(focus && focus !== '0'){
            var target = document.getElementById('gep-history-entry-' + focus);
            if(target){
                target.classList.add('is-flash');
                window.setTimeout(function(){ target.scrollIntoView({behavior:'smooth', block:'center'}); }, 120);
            }
        }
    });
})();
