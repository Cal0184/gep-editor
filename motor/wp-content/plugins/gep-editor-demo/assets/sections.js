(function($){
    'use strict';

    $(function(){
        var data = window.gepSectionManagerData || {};
        var $form = $('#gep-content-form');
        var $questionnaire = $form.find('.gep-questionnaire').first();
        if (!$form.length || !$questionnaire.length || !data.context || !Array.isArray(data.layout)) return;

        var state = {
            layout: JSON.parse(JSON.stringify(data.layout || [])),
            customs: $.extend(true, {}, data.customs || {})
        };
        var catalog = data.catalog || {};
        var builtins = data.builtins || {};
        var sitePages = Array.isArray(data.pages) ? data.pages : [];
        var MAX_BUTTONS = 4;

        /* v9.25.0 · Botones visuales. Cada apartado guarda una lista de botones
         * (texto, enlace, cómo se abre y estilo). El botón único que tenían los
         * "Llamado / CTA" se convierte en el primero de la lista, sin perderse. */
        function normalizeButtons(section){
            if (!section) return [];
            if (!Array.isArray(section.buttons)) section.buttons = [];
            if (!section.buttons.length && $.trim(section.button || '') && $.trim(section.url || '')) {
                section.buttons.push({text:section.button, url:section.url, target:'same', style:'primary'});
            }
            section.buttons = section.buttons.filter(function(b){ return b && typeof b === 'object'; }).slice(0, MAX_BUTTONS).map(function(b){
                return {text:String(b.text || ''), url:String(b.url || ''), target:b.target === 'new' ? 'new' : 'same', style:b.style === 'secondary' ? 'secondary' : 'primary'};
            });
            return section.buttons;
        }
        // Se mantiene "button"/"url" igual al primer botón (compatibilidad).
        function syncLegacyButton(section){
            var first = (section.buttons || []).filter(function(b){ return $.trim(b.text) && $.trim(b.url); })[0];
            section.button = first ? first.text : '';
            section.url = first ? first.url : '';
        }
        $.each(state.customs, function(_, section){ normalizeButtons(section); });
        function hasCustomContent(section){
            section = section || {};
            if ($.trim(section.eyebrow || '') || $.trim(section.title || '') || $.trim(section.text || '')) return true;
            if ($.trim(section.image || '') || $.trim(section.video_url || '')) return true;
            if ((section.items || []).length) return true;
            return (section.buttons || []).some(function(b){ return $.trim(b.text || ''); });
        }
        var activeCustom = null;
        var customCounter = Date.now();
        var openRows = {};
        var hoverPreviewTimer = null;
        var $hoverPreview = null;
        var editingRowId = null;
        var $legacyParking = null;
        var activePreviewId = null;
        // v9.24.0: la vista previa siempre empieza mostrando la página completa.
        var previewMode = 'general';

        var $manager = $('<section class="gep-structure-manager" aria-label="Estructura de página">\
            <div class="gep-structure-head">\
              <div><span class="gep-structure-kicker">Contenido editable</span><h3>Secciones de la página</h3><p class="gep-structure-subtitle">Edita cada bloque, cambia su orden o añade una nueva sección.</p></div>\
              <button type="button" class="button gep-structure-add"><span class="dashicons dashicons-plus-alt2"></span> Añadir apartado</button>\
            </div>\
            <div class="gep-structure-focusbar" aria-label="Apartado en edición"><button type="button" class="gep-focus-back" title="Volver a la lista de secciones"><span class="dashicons dashicons-arrow-left-alt2"></span><span>Todas las secciones</span></button><div class="gep-focus-steps"></div></div>\
            <div class="gep-structure-list" id="gep-structure-list"></div>\
            <div class="gep-structure-library is-hidden" id="gep-structure-library"><div class="gep-structure-library-head"><strong>Elige un tipo</strong><button type="button" class="button-link gep-structure-library-close">Cerrar</button></div><div class="gep-structure-library-grid"></div></div>\
            <input type="hidden" name="gep_page_structure_json" id="gep_page_structure_json">\
            <input type="hidden" name="gep_custom_sections_json" id="gep_custom_sections_json">\
        </section>');

        var $visibilityTools = $questionnaire.children('.gep-section-visibility-tools').first();
        if ($visibilityTools.length) $manager.insertAfter($visibilityTools); else $questionnaire.prepend($manager);

        // v9.12: la estructura es el único editor visible. Los editores históricos
        // siguen dentro del formulario para conservar toda su lógica, pero viven en
        // un estacionamiento oculto hasta que el usuario pulsa "Editar sección".
        $visibilityTools.addClass('gep-legacy-ui-hidden');
        $questionnaire.children('.gep-section-visibility-tools').addClass('gep-legacy-ui-hidden');
        $legacyParking = $('<div class="gep-legacy-section-parking" aria-hidden="true"></div>').appendTo($questionnaire);
        $questionnaire.children('.gep-step-card').appendTo($legacyParking);

        function escapeHtml(value){
            return String(value == null ? '' : value).replace(/[&<>'"]/g, function(ch){
                return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch];
            });
        }

        function typeInfo(row){
            if (row.kind === 'custom') {
                var custom = state.customs[row.id] || {};
                return catalog[custom.type] || {label:'Apartado', icon:'dashicons-screenoptions'};
            }
            var def = builtins[row.id] || {};
            return {label:def.type || 'Apartado', icon:'dashicons-layout'};
        }

        function rowTitle(row){
            if (row.kind === 'custom') {
                var custom = state.customs[row.id] || {};
                return $.trim(custom.title || '') || ((catalog[custom.type] || {}).label || 'Nuevo apartado');
            }
            return (builtins[row.id] || {}).label || row.id;
        }

        function legacySourceNodes(id){
            var context = String(data.context || '');
            var selectors = [];
            if (context === 'home') selectors = ['[data-home-preview="'+id+'"]'];
            else if (context === 'directory') {
                var pane = id === 'hero' ? 'header' : (id === 'team' ? 'team' : 'directory');
                selectors = ['[data-preview="'+pane+'"]'];
            } else if (context === 'contact') {
                if (id === 'contact') selectors = ['[data-contact-preview="card"]','[data-contact-preview="location"]'];
                else selectors = ['[data-contact-preview="'+id+'"]'];
            } else if (context === 'docencia') {
                if (id === 'courses') selectors = ['[data-docencia-preview="learning"]','[data-docencia-preview="pregrado"]','[data-docencia-preview="postgrado"]','[data-docencia-preview="diplomado"]','[data-docencia-preview="workshop"]'];
                else selectors = ['[data-docencia-preview="'+id+'"]'];
            } else if (context === 'projects') {
                if (id === 'trajectory') selectors = ['[data-project-preview="trajectory"]','[data-project-preview="records"]'];
                // v9.26.0: «Proyectos vigentes» abre la misma lista común de proyectos (filtrada en vigentes).
                else if (id === 'features') selectors = ['[data-project-preview="features"]','[data-project-preview="records"]'];
                else selectors = ['[data-project-preview="'+id+'"]'];
            } else if (context === 'publications') {
                // v9.24.0: Buscador (textos + filtros) separado de Publicaciones (registros).
                if (id === 'hero') selectors = ['[data-publication-preview="hero"]'];
                else if (id === 'search') selectors = ['[data-publication-preview="search"]','[data-publication-preview="sections"]'];
                else selectors = ['[data-publication-preview="records"]'];
            } else if (context === 'communications' || context === 'news') {
                selectors = ['[data-hub-preview="'+(id === 'hero' ? 'hero' : 'content')+'"]'];
            } else if (context === 'services') {
                selectors = ['[data-service-preview="'+id+'"]'];
            }
            var $nodes = $();
            $.each(selectors,function(_,selector){ $nodes = $nodes.add($questionnaire.find(selector).filter('.gep-step-card')); });
            return $nodes;
        }

        function parkInlineEditors(){
            if (!$legacyParking || !$legacyParking.length) return;
            var wasEditing = editingRowId;
            $('.gep-structure-inline-editor .gep-step-card').each(function(){ $(this).appendTo($legacyParking); });
            $('.gep-structure-inline-editor').empty().addClass('is-hidden');
            editingRowId = null;
            activeCustom = null;
            leaveFocus(wasEditing);
        }

        /* v9.24.0 · Modo enfoque.
         * Al pulsar "Editar sección" la lista se pliega y solo queda a la vista el
         * apartado en edición, con una tira compacta para saltar a otro apartado.
         * Pulsar otra vez el apartado (o "Todas las secciones") vuelve a la lista. */
        var focusReturnId = null;
        function focusStorageKey(){ return 'gepEditorFocus:' + String(data.context || '') + ':' + String($form.find('input[name="post_id"]').val() || ''); }
        function rememberFocus(id){ try { if (id) window.sessionStorage.setItem(focusStorageKey(), id); else window.sessionStorage.removeItem(focusStorageKey()); } catch(e) {} }
        function scrollToElement(el, block){
            if (!el) return;
            if (typeof window.gepScrollToElement === 'function') window.gepScrollToElement(el, {block: block || 'start'});
            else el.scrollIntoView({behavior:'smooth', block: block || 'start'});
        }
        function renderFocusbar(){
            var $steps = $manager.find('.gep-focus-steps').empty();
            state.layout.forEach(function(row, index){
                var active = row.id === editingRowId;
                $steps.append('<button type="button" class="gep-focus-step'+(active?' is-active':'')+(row.enabled===false?' is-disabled':'')+'" data-section-id="'+escapeHtml(row.id)+'" aria-pressed="'+(active?'true':'false')+'" title="'+(active?'Pulsa de nuevo para volver a la lista':'Editar este apartado')+'"><b>'+(index+1)+'</b><span>'+escapeHtml(rowTitle(row))+'</span></button>');
            });
        }
        function enterFocus(id, $row){
            focusReturnId = null;
            $manager.addClass('is-focus-mode');
            renderFocusbar();
            rememberFocus(id);
            var $active = $manager.find('.gep-focus-step.is-active');
            if ($active.length && $active[0].scrollIntoView) $active[0].scrollIntoView({block:'nearest', inline:'center'});
            scrollToElement($manager[0], 'start');
            if ($row && $row.length) {
                $row.addClass('gep-focus-enter');
                window.setTimeout(function(){ $row.removeClass('gep-focus-enter'); }, 650);
            }
        }
        function leaveFocus(lastId){
            if (!$manager.hasClass('is-focus-mode')) return;
            $manager.removeClass('is-focus-mode');
            rememberFocus('');
            focusReturnId = lastId || null;
            // Si enseguida se abre otro apartado, enterFocus cancela este regreso.
            window.setTimeout(function(){
                if (!focusReturnId || $manager.hasClass('is-focus-mode')) return;
                var $back = $('#gep-structure-list .gep-structure-row[data-section-id="'+String(focusReturnId).replace(/"/g,'\\"')+'"]');
                focusReturnId = null;
                if ($back.length) scrollToElement($back[0], 'center');
            }, 0);
        }
        function openAndEdit(id){
            var row = findRow(id), $row = $('#gep-structure-list .gep-structure-row[data-section-id="'+String(id).replace(/"/g,'\\"')+'"]');
            if (!row || !$row.length) return;
            $.each(openRows,function(key){ openRows[key]=false; });
            $('#gep-structure-list .gep-structure-row').removeClass('is-open is-editing').find('.gep-structure-summary').attr('aria-expanded','false');
            openRows[id] = true;
            $row.addClass('is-open').find('.gep-structure-summary').attr('aria-expanded','true');
            refreshRowPresentation(id);
            hideHoverPreview();
            applySinglePreview(id, true);
            if (row.kind === 'custom') renderCustomEditor(id); else editBuiltin(id);
        }
        function closeEditing(id){
            parkInlineEditors();
            $('#gep-structure-list .gep-structure-row[data-section-id="'+String(id).replace(/"/g,'\\"')+'"]').removeClass('is-editing');
            if (id) refreshRowPresentation(id);
        }

        function cleanSummaryText(value, max){
            value = $.trim(String(value == null ? '' : value).replace(/\s+/g,' '));
            max = max || 94;
            return value.length > max ? value.slice(0,max-1)+'…' : value;
        }

        function fieldLabel($field){
            var text = '';
            var id = $field.attr('id');
            if (id) {
                var $for = $questionnaire.find('label[for="'+id.replace(/"/g,'\\"')+'"]').first().clone();
                $for.find('*').remove();
                text = $.trim($for.text());
            }
            if (!text) {
                var $label = $field.closest('label').clone();
                $label.find('input,textarea,select,button,small,.gep-required-dot,.gep-optional').remove();
                text = $.trim($label.text());
            }
            if (!text) text = $.trim($field.closest('.gep-admin-field').find('.gep-field-label').first().text());
            if (!text) {
                var key = String($field.attr('name') || $field.attr('id') || '').toLowerCase();
                if (/title|titulo/.test(key)) text = 'Título';
                else if (/eyebrow|kicker|label/.test(key)) text = 'Etiqueta';
                else if (/description|descripcion|text|texto|paragraph|intro/.test(key)) text = 'Descripción';
                else if (/button|boton|primary|secondary|cta/.test(key)) text = 'Botón';
                else if (/image|imagen|photo|foto/.test(key)) text = 'Imagen';
                else if (/url|link|enlace/.test(key)) text = 'Enlace';
                else if (/geo|tag|topic|pill|etiqueta/.test(key)) text = 'Temas';
                else text = 'Contenido';
            }
            return cleanSummaryText(text,38);
        }

        function fieldValue($field){
            var type = String($field.attr('type') || '').toLowerCase();
            if (type === 'checkbox' || type === 'radio') return $field.is(':checked') ? 'Activado' : 'Desactivado';
            if ($field.is('select')) return cleanSummaryText($field.find('option:selected').text(),100);
            var value = $.trim($field.val() || '');
            if ($field.hasClass('gep-image-url') || /image(_url)?\]?$/i.test($field.attr('name') || '')) return value ? 'Imagen configurada' : '';
            if (/^https?:\/\//i.test(value)) return 'Enlace configurado';
            return cleanSummaryText(value,105);
        }

        function pushSummary(list,label,value,icon){
            label = cleanSummaryText(label,38); value = cleanSummaryText(value,105);
            if (!value) return;
            var duplicate = false;
            $.each(list,function(_,item){ if (item.label === label && item.value === value) duplicate = true; });
            if (!duplicate) list.push({label:label,value:value,icon:icon || summaryIcon(label)});
        }

        function summaryIcon(label){
            var t = String(label || '').toLowerCase();
            if (/imagen|foto|galer/.test(t)) return 'dashicons-format-image';
            if (/enlace|url|bot[oó]n/.test(t)) return 'dashicons-admin-links';
            if (/persona|equipo|registro|proyecto|curso|public/.test(t)) return 'dashicons-list-view';
            if (/fecha|año|per[ií]odo/.test(t)) return 'dashicons-calendar-alt';
            if (/descrip|texto|detalle/.test(t)) return 'dashicons-editor-alignleft';
            if (/t[ií]tulo|nombre/.test(t)) return 'dashicons-editor-textcolor';
            return 'dashicons-tag';
        }

        function builtinSummaryItems(id){
            var context = String(data.context || '');
            var $sources = legacySourceNodes(id);
            var items = [];

            // Resumen editorial: los apartados principales muestran conceptos útiles
            // en vez de una sucesión de campos genéricos "Contenido".
            if (context === 'home' && id === 'hero') {
                pushSummary(items,'Título',$('#gep_home_hero_title').val() || '', 'dashicons-editor-textcolor');
                pushSummary(items,'Descripción',$('#gep_home_hero_text').val() || '', 'dashicons-editor-alignleft');
                var buttons = 0;
                if ($.trim($('#gep_home_hero_primary_text').val() || '')) buttons++;
                if ($.trim($('#gep_home_hero_secondary_text').val() || '')) buttons++;
                pushSummary(items,'Botones',buttons ? (buttons + ' botones configurados') : 'Sin botones','dashicons-admin-links');
                var tags = [$('#gep_home_hero_eyebrow').val(),$('#gep_home_hero_geo_a').val(),$('#gep_home_hero_geo_b').val()].filter(function(v){ return $.trim(v || ''); });
                pushSummary(items,'Textos de apoyo',tags.join(' · '),'dashicons-tag');
                return items.slice(0,4);
            }
            if (id === 'hero') {
                var $heroFields = $sources.find('input:not([type="hidden"]),textarea,select');
                var titleValue='', descValue='', eyebrowValue='', imageValue='';
                $heroFields.each(function(){
                    var $f=$(this), key=String($f.attr('name') || $f.attr('id') || '').toLowerCase(), val=$.trim($f.val() || '');
                    if (!titleValue && /title|titulo/.test(key)) titleValue=val;
                    else if (!descValue && /description|descripcion|text|texto/.test(key)) descValue=val;
                    else if (!eyebrowValue && /eyebrow|kicker|label/.test(key)) eyebrowValue=val;
                    else if (!imageValue && /image|imagen|photo|foto/.test(key)) imageValue=val;
                });
                pushSummary(items,'Título',titleValue,'dashicons-editor-textcolor');
                pushSummary(items,'Descripción',descValue,'dashicons-editor-alignleft');
                pushSummary(items,'Etiqueta',eyebrowValue,'dashicons-tag');
                if (imageValue) pushSummary(items,'Imagen','Imagen configurada','dashicons-format-image');
            }

            if (context === 'directory' && id === 'directory') {
                pushSummary(items,'Apartados',String($sources.find('.gep-section-builder').length || 0),'dashicons-screenoptions');
                pushSummary(items,'Registros',String($sources.find('.gep-directory-list-row').length || 0),'dashicons-groups');
            }
            if (context === 'directory' && id === 'team') pushSummary(items,'Tarjetas',String($sources.find('.gep-team-card-row').length || 0),'dashicons-screenoptions');
            if (context === 'docencia' && id === 'courses') {
                pushSummary(items,'Ruta de aprendizaje',String($sources.find('.gep-docencia-step-row').length || 0)+' pasos','dashicons-editor-ol');
                pushSummary(items,'Cursos',String($sources.find('.gep-docencia-course-row').length || 0)+' registros','dashicons-welcome-learn-more');
            }
            if (context === 'projects' && id === 'trajectory') pushSummary(items,'En trayectoria',String($sources.find('.gep-project-record-list-row.is-status-cerrado').length || 0)+' proyectos','dashicons-portfolio');
            if (context === 'projects' && id === 'features') pushSummary(items,'Vigentes',String($sources.find('.gep-project-record-list-row.is-status-vigente').length || 0)+' proyectos','dashicons-portfolio');
            if (context === 'projects' && id === 'areas') pushSummary(items,'Áreas',String($sources.find('.gep-project-area-row').length || 0)+' tarjetas','dashicons-networking');
            if (context === 'publications' && id === 'search') {
                pushSummary(items,'Título',$('#gep_publications_explorer_title').val() || '','dashicons-search');
                pushSummary(items,'Filtros',String($sources.find('.gep-publication-section-card').length || 0)+' categorías','dashicons-filter');
                pushSummary(items,'Acceso en el menú',$('#gep_publications_nav_search').val() || '','dashicons-menu');
                return items;
            }
            if (context === 'publications' && id === 'content') pushSummary(items,'Publicaciones',String($sources.find('.gep-publication-list-row,.gep-publication-record-list-row,.gep-publication-row').length || 0)+' registros','dashicons-media-document');
            if (context === 'services' && id === 'services') pushSummary(items,'Servicios',String($sources.find('.gep-service-row').length || 0)+' servicios','dashicons-admin-tools');
            if ((context === 'communications' || context === 'news') && id === 'content') pushSummary(items,'Contenidos',String($sources.find('.gep-hub-record-row,.gep-hub-row').length || 0)+' registros','dashicons-admin-post');

            // v9.20.0: las imágenes van en campos ocultos; se muestran en el resumen
            // para que se vea que el apartado tiene foto y que se cambia en "Editar sección".
            if (context === 'home' && id !== 'hero') {
                var $ownImages = $sources.find('input.gep-image-url').filter(function(){ return !$(this).closest('.gep-home-repeat-row,.gep-item-row,.gep-directory-list-row,.gep-project-record-list-row,.gep-hub-row,.gep-hub-record-row').length; });
                if ($ownImages.length) {
                    var withImage = $ownImages.filter(function(){ return $.trim($(this).val() || '') !== ''; }).length;
                    pushSummary(items,'Imagen',withImage ? 'Imagen configurada' : 'Sin imagen · elígela en Editar sección','dashicons-format-image');
                }
                var $cardRows = $sources.find('.gep-home-repeat-row').filter(function(){ return $(this).find('input.gep-image-url').length > 0; });
                if ($cardRows.length) {
                    var cardsWithImage = $cardRows.filter(function(){ return $.trim($(this).find('input.gep-image-url').first().val() || '') !== ''; }).length;
                    pushSummary(items,'Tarjetas',$cardRows.length + ' tarjetas · ' + cardsWithImage + ' con imagen','dashicons-format-image');
                }
            }

            $sources.find('input:not([type="hidden"]):not([type="search"]),textarea,select').each(function(){
                if (items.length >= 4) return false;
                var $field = $(this);
                if ($field.closest('.gep-item-row,.gep-project-record-list-row,.gep-directory-list-row,.gep-hub-record-row,.gep-publication-record-list-row').length) return;
                pushSummary(items,fieldLabel($field),fieldValue($field));
            });

            if (!items.length) {
                $sources.each(function(){
                    if (items.length >= 4) return false;
                    var $card = $(this), title = $.trim($card.find('.gep-step-heading h3').first().text()), desc = $.trim($card.find('.gep-step-heading p').first().text());
                    if (title || desc) pushSummary(items,title || 'Apartado',desc || 'Contenido configurado','dashicons-layout');
                });
            }
            return items.slice(0,4);
        }

        function customSummaryItems(section){
            section = section || {};
            var items = [];
            // v9.25.0: título y descripción son opcionales; solo se listan si existen.
            if ($.trim(section.title || '')) pushSummary(items,'Título',section.title,'dashicons-editor-textcolor');
            if ($.trim(section.text || '')) pushSummary(items,'Descripción',section.text,'dashicons-editor-alignleft');
            if (section.type === 'cards') pushSummary(items,'Tarjetas',String((section.items || []).length)+' configuradas','dashicons-screenoptions');
            if (section.type === 'stats') pushSummary(items,'Cifras',String((section.items || []).length)+' configuradas','dashicons-chart-bar');
            if (section.type === 'image_text') pushSummary(items,'Imagen',section.image ? 'Imagen configurada' : 'Sin imagen','dashicons-format-image');
            if (section.type === 'video') pushSummary(items,'Video',section.video_url ? 'Video configurado' : 'Sin video','dashicons-video-alt3');
            var nButtons = (section.buttons || []).filter(function(b){ return $.trim(b.text || ''); }).length;
            if (nButtons || section.type === 'cta') pushSummary(items,'Botones',nButtons ? (nButtons === 1 ? '1 botón' : nButtons + ' botones') : 'Sin botones','dashicons-admin-links');
            return items.slice(0,4);
        }

        function rowIsComplete(row){
            if (row.enabled === false) return false;
            if (row.kind === 'custom') {
                var section = state.customs[row.id] || {};
                // v9.25.0: el título ya no es obligatorio; solo se avisa si el apartado está vacío.
                if (!hasCustomContent(section)) return false;
                if (section.type === 'video' && !$.trim(section.video_url || '')) return false;
                if (section.type === 'image_text' && !$.trim(section.image || '')) return false;
                if ((section.type === 'cards' || section.type === 'stats') && !(section.items || []).length) return false;
                return true;
            }
            var $required = legacySourceNodes(row.id).find('input,textarea,select').filter(function(){
                var required = false;
                $.each(this.attributes || [],function(_,attr){ if (/required/i.test(attr.name || '') && String(attr.value) === '1') required = true; });
                return required || this.required;
            });
            var ok = true;
            $required.each(function(){ if (!$.trim($(this).val() || '')) ok = false; });
            return ok;
        }

        function rowStatus(row){
            if (row.enabled === false) return {label:'Oculto', cls:'is-hidden-status', icon:'dashicons-hidden'};
            if (rowIsComplete(row)) return {label:'Completo', cls:'is-complete', icon:'dashicons-yes-alt'};
            // v9.25.0: un apartado nuevo sin nada escrito se marca "Vacío", no como error.
            if (row.kind === 'custom' && !hasCustomContent(state.customs[row.id])) return {label:'Vacío', cls:'is-incomplete', icon:'dashicons-edit'};
            return {label:'Incompleto', cls:'is-incomplete', icon:'dashicons-warning'};
        }

        function visualForRow(row){
            var $visual = $('<div class="gep-structure-card-visual"><div class="gep-structure-visual-canvas"></div></div>');
            var $canvas = $visual.find('.gep-structure-visual-canvas');
            if (row.kind === 'custom') {
                var section = state.customs[row.id] || {};
                $canvas.html(customPreviewHtml(section,section.type));
            } else {
                var $nodes = builtinPreviewNodes(row.id);
                if ($nodes.length) $nodes.each(function(){ $canvas.append(cleanPreviewClone($(this).clone(false,false))); });
                else $canvas.html('<div class="gep-structure-visual-empty"><span class="dashicons dashicons-layout"></span><small>Vista del apartado</small></div>');
            }
            return $visual;
        }

        function buildOverview(row){
            var info = typeInfo(row), status = rowStatus(row);
            var items = row.kind === 'custom' ? customSummaryItems(state.customs[row.id] || {}) : builtinSummaryItems(row.id);
            var $overview = $('<div class="gep-structure-overview"></div>');
            $overview.append(visualForRow(row));
            var $body = $('<div class="gep-structure-overview-body"></div>');
            var $top = $('<div class="gep-structure-overview-top"></div>');
            $top.append('<div><span class="gep-structure-overview-type">'+escapeHtml(info.label || 'Apartado')+'</span><strong>'+escapeHtml(rowTitle(row))+'</strong></div>');
            $top.append('<button type="button" class="button gep-structure-edit"><span class="dashicons dashicons-edit"></span> Editar sección</button>');
            $body.append($top);
            var $rows = $('<div class="gep-structure-summary-rows"></div>');
            if (!items.length) items = [{label:'Contenido',value:'Apartado configurado',icon:'dashicons-layout'}];
            $.each(items,function(_,item){ $rows.append('<div class="gep-structure-summary-line"><span class="dashicons '+escapeHtml(item.icon || 'dashicons-tag')+'"></span><b>'+escapeHtml(item.label)+'</b><em>'+escapeHtml(item.value)+'</em></div>'); });
            $body.append($rows);
            var $foot = $('<div class="gep-structure-overview-foot"></div>');
            $foot.append('<span class="gep-structure-status-note '+escapeHtml(status.cls)+'"><span class="dashicons '+escapeHtml(status.icon)+'"></span>'+escapeHtml(status.label)+'</span>');
            var $actions = $('<span class="gep-structure-actions"></span>');
            if (row.enabled === false) $actions.append('<button type="button" class="button button-small gep-structure-restore"><span class="dashicons dashicons-undo"></span> Restaurar</button>');
            else $actions.append('<button type="button" class="button button-small gep-structure-remove"><span class="dashicons dashicons-trash"></span> Eliminar</button>');
            $foot.append($actions); $body.append($foot); $overview.append($body);
            return $overview;
        }

        function buildRowPanel(row){
            var $panel = $('<div class="gep-structure-row-panel"></div>');
            $panel.append(buildOverview(row));
            $panel.append('<div class="gep-structure-inline-editor is-hidden"></div>');
            return $panel;
        }

        function refreshRowPresentation(id){
            var row = findRow(id), $row = $('#gep-structure-list .gep-structure-row[data-section-id="'+id.replace(/"/g,'\\"')+'"]');
            if (!row || !$row.length) return;
            var status = rowStatus(row), info = typeInfo(row);
            $row.find('.gep-structure-copy strong').text(rowTitle(row));
            $row.find('.gep-structure-copy small').text((info.label || 'Apartado') + (row.enabled === false ? ' · oculto de la página' : ''));
            $row.find('.gep-structure-status').attr('class','gep-structure-status '+status.cls).html('<span class="dashicons '+status.icon+'"></span>'+escapeHtml(status.label));
            $row.find('.gep-structure-overview').replaceWith(buildOverview(row));
            if ($manager.hasClass('is-focus-mode')) renderFocusbar();
        }

        function syncHidden(markDirty){
            $('#gep_page_structure_json').val(JSON.stringify(state.layout));
            $('#gep_custom_sections_json').val(JSON.stringify(state.customs));
            if (markDirty) $('#gep_page_structure_json').trigger('change');
        }

        function renderList(){
            parkInlineEditors();
            var $list = $('#gep-structure-list').empty();
            state.layout.forEach(function(row, index){
                var info = typeInfo(row);
                var status = rowStatus(row);
                var disabled = row.enabled === false;
                var opened = !!openRows[row.id];
                var pinned = isPinned(row);
                var $row = $('<div class="gep-structure-row'+(disabled?' is-disabled':'')+(opened?' is-open':'')+(pinned?' is-pinned':'')+'" data-section-id="'+escapeHtml(row.id)+'"></div>');
                var $summary = $('<div class="gep-structure-summary" role="button" tabindex="0" aria-expanded="'+(opened?'true':'false')+'"></div>');
                if (pinned) {
                    var anchor = builtins[builtins[row.id].pinned_before] || {};
                    $summary.append('<span class="gep-structure-pin dashicons dashicons-admin-links" title="Se mueve junto con '+escapeHtml(anchor.label || 'el apartado siguiente')+'"></span>');
                } else {
                    $summary.append('<span class="gep-structure-grip dashicons dashicons-menu-alt2" title="Arrastra para ordenar (o usa las flechas ↑ ↓ del teclado)" aria-label="Mover apartado"></span>');
                }
                $summary.append('<span class="gep-structure-number">'+(index+1)+'</span>');
                $summary.append('<span class="gep-structure-type-icon dashicons '+escapeHtml(info.icon || 'dashicons-layout')+'"></span>');
                $summary.append('<span class="gep-structure-copy"><strong>'+escapeHtml(rowTitle(row))+'</strong><small>'+escapeHtml(info.label || 'Apartado')+(disabled?' · oculto de la página':'')+'</small></span>');
                $summary.append('<span class="gep-structure-status '+escapeHtml(status.cls)+'"><span class="dashicons '+escapeHtml(status.icon)+'"></span>'+escapeHtml(status.label)+'</span>');
                $summary.append('<span class="gep-structure-chevron dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>');
                $row.append($summary).append(buildRowPanel(row));
                $list.append($row);
            });
            initSortable();
        }

        function renderLibrary(){
            var $grid = $('#gep-structure-library .gep-structure-library-grid').empty();
            $.each(catalog, function(type, item){
                $grid.append('<button type="button" class="gep-library-card" data-type="'+escapeHtml(type)+'"><span class="dashicons '+escapeHtml(item.icon || 'dashicons-layout')+'"></span><strong>'+escapeHtml(item.label || type)+'</strong><small>'+escapeHtml(item.description || '')+'</small></button>');
            });
        }

        function initSortable(){
            var $list = $('#gep-structure-list');
            if ($list.hasClass('ui-sortable')) $list.sortable('destroy');
            $list.sortable({
                items: '> .gep-structure-row:not(.is-pinned)',
                handle: '.gep-structure-grip',
                axis: 'y',
                start: function(){ parkInlineEditors(); },
                placeholder: 'gep-structure-sort-placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                update: function(e, ui){
                    var movedId = ui && ui.item ? ui.item.attr('data-section-id') : '';
                    var order = {};
                    state.layout.forEach(function(row){ order[row.id] = row; });
                    var next = [];
                    $list.children('.gep-structure-row').each(function(){
                        var id = $(this).attr('data-section-id');
                        if (order[id]) next.push(order[id]);
                    });
                    state.layout = applyPins(next);
                    renderList();
                    syncHidden(true);
                    syncLivePreviewStructure(movedId);
                }
            });
        }

        function isPinned(row){ return !!(row && row.kind !== 'custom' && builtins[row.id] && builtins[row.id].pinned_before); }
        function applyPins(rows){
            var out = rows.slice();
            $.each(builtins,function(id,def){
                if (!def || !def.pinned_before) return;
                var from = -1;
                $.each(out,function(i,r){ if (r && r.id === id && r.kind !== 'custom') { from = i; return false; } });
                if (from < 0) return;
                var pinned = out.splice(from,1)[0], to = out.length;
                $.each(out,function(i,r){ if (r && r.id === def.pinned_before) { to = i; return false; } });
                out.splice(to,0,pinned);
            });
            return out;
        }

        function findRow(id){
            for (var i=0;i<state.layout.length;i++) if (state.layout[i].id===id) return state.layout[i];
            return null;
        }

        var previewBuiltinBusy = false;
        function previewBuiltin(id, entering){
            var def = builtins[id] || {};
            if (!def.edit_selector || previewBuiltinBusy) return;
            var $target = $form.find(def.edit_selector).first();
            if (!$target.length) return;
            // El evento sintético sube hasta la fila del apartado, que vuelve a llamar aquí.
            previewBuiltinBusy = true;
            try { $target.trigger(entering ? 'mouseenter' : 'mouseleave'); } finally { previewBuiltinBusy = false; }
        }


        function ensureHoverPreview(){
            if ($hoverPreview && $hoverPreview.length) return $hoverPreview;
            $hoverPreview = $('<div class="gep-structure-hover-preview is-hidden" aria-hidden="true"><div class="gep-structure-hover-head"><span>Vista previa</span><strong></strong></div><div class="gep-structure-hover-viewport"><div class="gep-structure-hover-canvas"></div></div></div>').appendTo('body');
            return $hoverPreview;
        }

        function cleanPreviewClone($clone){
            $clone.find('[id]').addBack('[id]').removeAttr('id');
            // Una mini-preview nunca debe volver a identificarse como un bloque de la
            // preview principal. Si conserva estos data-* una búsqueda posterior puede
            // clonar la mini-preview de nuevo y producir el efecto recursivo/duplicado.
            var liveAttrs = [
                'data-home-live','data-live-section','data-docencia-live','data-project-live',
                'data-publication-live','data-hub-live','data-pane','data-gep-live-custom'
            ];
            $.each(liveAttrs,function(_,attr){ $clone.find('['+attr+']').addBack('['+attr+']').removeAttr(attr); });
            $clone.find('input,textarea,select,button,a').attr('tabindex','-1');
            $clone.find('a').removeAttr('href');
            return $clone;
        }

        function builtinPreviewNodes(id){
            // v9.12.5: tomar SIEMPRE el contenido desde la preview principal.
            // Antes se buscaba en todo el formulario; al abrir una tarjeta, sus clones
            // también coincidían con los selectores y el hover-preview podía clonarse a
            // sí mismo varias veces.
            var $root = livePreviewRoot();
            if ($root.length) {
                var $liveNodes = livePreviewBuiltinNodes(id, $root);
                if ($liveNodes.length) return $liveNodes;
            }

            var context = String(data.context || '');
            var selectors = [];
            if (context === 'home') selectors = ['[data-home-live="'+id+'"]'];
            else if (context === 'directory') {
                var pane = id === 'hero' ? 'header' : (id === 'team' ? 'team' : 'directory');
                selectors = ['.gep-preview-pane[data-pane="'+pane+'"]'];
            } else if (context === 'contact') {
                if (id === 'contact') selectors = ['[data-live-section="card"]','[data-live-section="location"]'];
                else selectors = ['[data-live-section="'+id+'"]'];
            } else if (context === 'docencia') {
                if (id === 'courses') selectors = ['[data-docencia-live="learning"]','[data-docencia-live="pregrado"]','[data-docencia-live="postgrado"]','[data-docencia-live="diplomado"]','[data-docencia-live="workshop"]'];
                else selectors = ['[data-docencia-live="'+id+'"]'];
            } else if (context === 'projects') {
                if (id === 'trajectory') selectors = ['[data-project-live="trajectory"]','[data-project-live="records"]'];
                else selectors = ['[data-project-live="'+id+'"]'];
            } else if (context === 'publications') {
                selectors = [id === 'content' ? '[data-publication-live="records"]' : (id === 'search' ? '[data-publication-live="search"]' : '[data-publication-live="hero"]')];
            } else if (context === 'communications' || context === 'news') {
                selectors = [id === 'hero' ? '#gep-hub-live-hero' : '.gep-hub-live-content'];
            } else if (context === 'services') {
                selectors = ['[data-service-live="'+id+'"]'];
            }
            var $nodes = $();
            $.each(selectors,function(_,selector){ $nodes = $nodes.add($form.find(selector).filter(function(){ return !$(this).closest('.gep-structure-manager,.gep-structure-hover-preview').length; })); });
            return $nodes;
        }

        function structurePreviewImage(url){
            url = $.trim(url || '');
            if (!url) return '';
            if (typeof window.gepPreviewResolveImage === 'function') {
                var shell = livePreviewRoot().closest('.gep-preview-card').attr('id');
                return window.gepPreviewResolveImage(url, shell ? '#'+shell : '', '');
            }
            return url;
        }

        function customVideoMediaHtml(url, compact){
            url = $.trim(url || '');
            var cls = compact ? ' is-compact' : '';
            if (!url) return '<div class="gep-custom-video-empty'+cls+'"><span class="dashicons dashicons-video-alt3"></span><strong>Aún no hay video</strong><small>Selecciona uno desde WordPress o pega una URL.</small></div>';
            var safe = escapeHtml(url);
            var lower = url.toLowerCase().split('?')[0].split('#')[0];
            if (/\.(mp4|m4v|webm|ogv|ogg|mov)$/.test(lower)) {
                // v9.21.3: "#t=0.1" muestra el primer cuadro antes de pulsar reproducir.
                return '<div class="gep-custom-video-player'+cls+'"><video controls muted playsinline preload="metadata" src="'+safe+(url.indexOf('#')<0?'#t=0.1':'')+'"></video></div>';
            }
            try {
                var parsed = new URL(url, window.location.href), host=(parsed.hostname||'').toLowerCase(), embed='';
                if (host.indexOf('youtu.be') !== -1) {
                    var yid=(parsed.pathname||'').replace(/^\/+|\/+$/g,'').split('/')[0]||'';
                    if (yid) embed='https://www.youtube-nocookie.com/embed/'+encodeURIComponent(yid);
                } else if (host.indexOf('youtube.com') !== -1) {
                    var id=parsed.searchParams.get('v')||'';
                    if (!id) {
                        var m=(parsed.pathname||'').match(/\/(?:embed|shorts)\/([A-Za-z0-9_-]+)/);
                        if (m) id=m[1];
                    }
                    if (id) embed='https://www.youtube-nocookie.com/embed/'+encodeURIComponent(id);
                } else if (host.indexOf('vimeo.com') !== -1) {
                    var vm=(parsed.pathname||'').match(/\/(?:video\/)?(\d+)/);
                    if (vm) embed='https://player.vimeo.com/video/'+vm[1];
                }
                if (embed) return '<div class="gep-custom-video-player'+cls+'"><iframe src="'+escapeHtml(embed)+'" title="Vista previa de video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
            } catch(err) {}
            return '<div class="gep-custom-video-empty'+cls+' is-link"><span class="dashicons dashicons-admin-links"></span><strong>Enlace de video configurado</strong><small>'+safe+'</small></div>';
        }

        function miniTitle(eyebrow, title, text){
            if (!eyebrow && !title && !text) return '';
            return '<div class="gep-mini-title">'+(eyebrow?'<small>'+eyebrow+'</small>':'')+(title?'<strong>'+title+'</strong>':'')+(text?'<p>'+text+'</p>':'')+'</div>';
        }
        function customButtonsPreviewHtml(section, dark){
            var list = (section && section.buttons || []).filter(function(b){ return $.trim(b.text || ''); });
            if (!list.length) return '';
            var html = '<div class="gep-mini-buttons'+(dark?' is-dark':'')+'">';
            $.each(list,function(_,b){ html += '<b class="gep-mini-btn is-'+(b.style === 'secondary' ? 'secondary' : 'primary')+'">'+escapeHtml(b.text)+(b.target === 'new' ? ' <span aria-hidden="true">↗</span>' : '')+'</b>'; });
            return html + '</div>';
        }
        function customPreviewHtml(section, type){
            section = section || {};
            type = type || section.type || 'text';
            // v9.25.0: los textos de ejemplo solo aparecen mientras el apartado está
            // completamente vacío. Si se deja sin título o sin descripción, la vista
            // previa no muestra ese espacio (igual que la página pública).
            var sample = !hasCustomContent(section);
            var eyebrow = escapeHtml(sample ? 'GEP' : (section.eyebrow || ''));
            var title = escapeHtml(sample ? ((catalog[type] || {}).label || 'Nuevo apartado') : (section.title || ''));
            var text = escapeHtml(sample ? 'Así se verá este apartado dentro de la página.' : (section.text || ''));
            var buttonsHtml = customButtonsPreviewHtml(section, type === 'cta');
            var html = '';
            if (type === 'stats') {
                var stats = Array.isArray(section.items) && section.items.length ? section.items.slice(0,4) : [{value:'24',label:'Proyectos'},{value:'16',label:'Cursos'},{value:'93',label:'Registros'},{value:'44',label:'Personas'}];
                html = '<div class="gep-mini-section gep-mini-stats">'+miniTitle(eyebrow,title,'')+'<div class="gep-mini-stats-grid">';
                $.each(stats,function(_,item){ html += '<div><b>'+escapeHtml(item.value || '—')+'</b><span>'+escapeHtml(item.label || 'Indicador')+'</span></div>'; });
                return html + '</div>'+buttonsHtml+'</div>';
            }
            if (type === 'cards') {
                var cards = Array.isArray(section.items) && section.items.length ? section.items.slice(0,3) : [{title:'Tarjeta 1',text:'Descripción breve.'},{title:'Tarjeta 2',text:'Descripción breve.'},{title:'Tarjeta 3',text:'Descripción breve.'}];
                html = '<div class="gep-mini-section">'+miniTitle(eyebrow,title,text)+'<div class="gep-mini-card-grid">';
                $.each(cards,function(_,item){ var cardImage=structurePreviewImage(item.image || ''); html += '<div class="gep-mini-card">'+(cardImage?'<span class="gep-mini-photo" style="background-image:url(&quot;'+escapeHtml(cardImage)+'&quot;)"></span>':'<span class="gep-mini-photo"></span>')+'<b>'+escapeHtml(item.title || 'Tarjeta')+'</b><p>'+escapeHtml(item.text || '')+'</p></div>'; });
                return html + '</div>'+buttonsHtml+'</div>';
            }
            if (type === 'image_text') {
                var splitImage=structurePreviewImage(section.image || '');
                return '<div class="gep-mini-section gep-mini-split'+(section.image_side==='left'?' is-left':'')+'"><div class="gep-mini-copy">'+(eyebrow?'<small>'+eyebrow+'</small>':'')+(title?'<strong>'+title+'</strong>':'')+(text?'<p>'+text+'</p>':'')+buttonsHtml+'</div><div class="gep-mini-image"'+(splitImage?' style="background-image:url(&quot;'+escapeHtml(splitImage)+'&quot;)"':'')+'><span class="dashicons dashicons-format-image"></span></div></div>';
            }
            if (type === 'video') {
                return '<div class="gep-mini-section gep-mini-video-section">'+miniTitle(eyebrow,title,text)+'<div class="gep-mini-video">'+customVideoMediaHtml(section.video_url || '', true)+'</div>'+buttonsHtml+'</div>';
            }
            if (type === 'cta') {
                return '<div class="gep-mini-section gep-mini-cta"><div>'+(eyebrow?'<small>'+eyebrow+'</small>':'')+(title?'<strong>'+title+'</strong>':'')+(text?'<p>'+text+'</p>':'')+'</div>'+(buttonsHtml || (sample ? '<div class="gep-mini-buttons is-dark"><b class="gep-mini-btn is-primary">Botón</b></div>' : ''))+'</div>';
            }
            return '<div class="gep-mini-section">'+miniTitle(eyebrow,title,text)+buttonsHtml+'</div>';
        }

        function livePreviewRoot(){
            var context = String(data.context || '');
            if (context === 'home') return $('#gep-home-live-preview').first();
            if (context === 'contact') return $('#gep-contact-preview-shell .gep-contact-live-page').first();
            if (context === 'docencia') return $('#gep-docencia-preview-shell').first();
            if (context === 'projects') return $('#gep-project-live-preview').first();
            if (context === 'publications') return $('#gep-publication-live-preview').first();
            if (context === 'communications' || context === 'news') return $('#gep-hub-live-preview .gep-preview-stage').first();
            if (context === 'services') return $('#gep-service-live-preview .gep-preview-stage').first();
            if (context === 'directory') return $('#gep-preview-stage').first();
            return $();
        }

        function livePreviewStage(){
            var context = String(data.context || '');
            if (context === 'home') return $('.gep-home-device-stage').first();
            if (context === 'contact') return $('.gep-contact-device-stage').first();
            if (context === 'docencia') return $('.gep-docencia-device-stage').first();
            if (context === 'projects') return $('.gep-project-device-stage').first();
            if (context === 'publications') return $('.gep-publication-device-stage').first();
            if (context === 'communications' || context === 'news' || context === 'services') return $('.gep-hub-device-stage').first();
            if (context === 'directory') return $('.gep-directory-device-stage').first();
            return $();
        }


        // v9.13.7 — Añade el encabezado público a todas las previews. Antes la
        // maqueta empezaba directamente en el hero y parecía una captura recortada.
        function installPreviewSiteHeader(){
            var markup = '<div class="gep-preview-site-header" aria-hidden="true">'+
                '<div class="gep-preview-site-brand"><span class="gep-preview-site-mark"><i></i><i></i><i></i><i></i></span><span><b>MAPBIOMAS</b><em>GEP</em></span></div>'+
                '<div class="gep-preview-site-nav"><span>Inicio</span><span>Quiénes somos</span><span>Docencia</span><span>Proyectos</span><span>Publicaciones</span><span>Comunicaciones</span></div>'+
                '<span class="dashicons dashicons-search gep-preview-site-search"></span>'+
            '</div>';
            $('.gep-directory-live-toolbar,.gep-contact-live-toolbar').each(function(){
                var $toolbar=$(this);
                if ($toolbar.next('.gep-preview-site-header').length) return;
                $toolbar.after(markup);
            });
        }

        /* v9.12.2 — La preview puede alternar entre un único apartado y la página completa. */
        function previewModeHeading(mode, id){
            var selectors = previewHeaderSelectors();
            if (mode === 'general') {
                if (selectors.zone) $(selectors.zone).hide();
                if (selectors.title) $(selectors.title).text('Vista general');
                return;
            }
            if (selectors.zone) $(selectors.zone).show();
            if (id) updateSinglePreviewHeading(id);
        }

        function installPreviewModeToggle(){
            var $stage = livePreviewStage();
            if (!$stage.length) return;
            var $card = $stage.closest('.gep-preview-card');
            var $heading = $card.find('.gep-preview-heading').first();
            if (!$heading.length || $heading.find('.gep-preview-mode-switcher').length) return;
            var $toggle = $('<div class="gep-preview-mode-switcher" role="group" aria-label="Modo de preview">'+
                '<button type="button" class="gep-preview-mode-button is-active" data-preview-mode="general" aria-pressed="true" title="Ver la página completa"><span class="dashicons dashicons-admin-site-alt3"></span><span>General</span></button>'+
                '<button type="button" class="gep-preview-mode-button" data-preview-mode="section" aria-pressed="false" title="Ver solo el apartado seleccionado"><span class="dashicons dashicons-excerpt-view"></span><span>Apartado</span></button>'+
            '</div>');
            var $device = $heading.find('.gep-preview-device-switcher').first();
            if ($device.length) $toggle.insertBefore($device); else $heading.append($toggle);
        }

        function applyGeneralPreview(focusId, pulse){
            var $root = livePreviewRoot();
            if (!$root.length) return;
            var context = String(data.context || '');
            activePreviewId = focusId || activePreviewId || firstEnabledPreviewId();
            $root.addClass('gep-structure-general-preview');
            var $focus = $();
            state.layout.forEach(function(item){
                if (!item || !item.id) return;
                var $nodes = item.kind === 'custom' ? livePreviewCustomNode(item,$root) : livePreviewBuiltinNodes(item.id,$root);
                if (!$nodes.length) return;
                var enabled = item.enabled !== false;
                $nodes.removeClass('gep-structure-preview-single-hidden');
                $nodes.toggleClass('gep-structure-general-visible', enabled);
                if (context === 'directory') $nodes.removeClass('is-active');
                if (item.id === activePreviewId) $focus = $focus.add($nodes);
            });
            previewModeHeading('general');
            var $stage = livePreviewStage();
            if ($stage.length) {
                if (focusId && $focus.length) focusLivePreviewNodes($focus);
                else $stage.stop(true).scrollTop(0);
            }
            if (pulse && $focus.length) {
                var $target = $focus.filter(':visible').first();
                if ($target.length) {
                    $target.addClass('gep-structure-preview-moved');
                    window.setTimeout(function(){ $target.removeClass('gep-structure-preview-moved'); },700);
                }
            }
        }

        function setPreviewMode(mode, focusId){
            previewMode = mode === 'general' ? 'general' : 'section';
            $('.gep-preview-mode-button').removeClass('is-active').attr('aria-pressed','false')
                .filter('[data-preview-mode="'+previewMode+'"]').addClass('is-active').attr('aria-pressed','true');
            if (previewMode === 'general') applyGeneralPreview(focusId || activePreviewId, false);
            else applySinglePreview(focusId || activePreviewId || firstEnabledPreviewId(), false);
        }

        function livePreviewBuiltinNodes(id, $root){
            var context = String(data.context || '');
            if (!$root || !$root.length) return $();
            if (context === 'home') return $root.children('[data-home-live="'+id+'"]');
            if (context === 'contact') {
                if (id === 'hero') return $root.children('[data-live-section="hero"]');
                if (id === 'contact') return $root.children('.gep-contact-live-content');
                if (id === 'cta') return $root.children('.gep-contact-live-cta');
            }
            if (context === 'docencia') {
                if (id === 'hero') return $root.children('[data-docencia-live="hero"]');
                if (id === 'courses') return $root.children('[data-docencia-live="learning"],[data-docencia-live="pregrado"],[data-docencia-live="postgrado"],[data-docencia-live="diplomado"],[data-docencia-live="workshop"]');
                if (id === 'fields') return $root.children('[data-docencia-live="fields"]');
            }
            if (context === 'projects') {
                if (id === 'hero') return $root.children('[data-project-live="hero"],.gep-project-live-nav');
                if (id === 'features') return $root.children('[data-project-live="features"]');
                if (id === 'trajectory') return $root.children('[data-project-live="trajectory"],[data-project-live="records"]');
                if (id === 'areas') return $root.children('[data-project-live="areas"]');
            }
            if (context === 'publications') {
                if (id === 'hero') return $root.children('[data-publication-live="hero"],[data-publication-live="sections"]');
                if (id === 'search') return $root.children('[data-publication-live="search"]');
                if (id === 'content') return $root.children('[data-publication-live="records-stats"],[data-publication-live="records"]');
            }
            if (context === 'communications' || context === 'news') {
                if (id === 'hero') return $root.children('#gep-hub-live-hero');
                if (id === 'content') return $root.children('#gep-hub-live-nav,.gep-hub-live-content');
            }
            if (context === 'services') {
                if (id === 'hero') return $root.children('[data-service-live="hero"],#gep-service-live-nav');
                return $root.children('[data-service-live="'+id+'"]');
            }
            if (context === 'directory') {
                var pane = id === 'hero' ? 'header' : (id === 'team' ? 'team' : 'directory');
                return $root.children('.gep-preview-pane[data-pane="'+pane+'"]');
            }
            return $();
        }

        function livePreviewCustomNode(row, $root){
            if (!$root || !$root.length || !row) return $();
            var $node = $root.children('.gep-structure-live-custom').filter(function(){
                return $(this).attr('data-gep-live-custom') === row.id;
            }).first();
            if (!$node.length) {
                $node = $('<div class="gep-structure-live-custom"></div>').attr('data-gep-live-custom', row.id);
            }
            var section = state.customs[row.id] || {};
            $node.html(customPreviewHtml(section, section.type || 'text'));
            return $node;
        }

        function focusLivePreviewNodes($nodes){
            if (!$nodes || !$nodes.length) return;
            var $target = $nodes.filter(':visible').first();
            if (!$target.length) return;
            $target.addClass('gep-structure-preview-moved');
            window.setTimeout(function(){ $target.removeClass('gep-structure-preview-moved'); }, 700);
            var $stage = livePreviewStage();
            if (!$stage.length || !$stage[0] || !$target[0]) return;
            var stageRect = $stage[0].getBoundingClientRect();
            var targetRect = $target[0].getBoundingClientRect();
            var nextTop = $stage.scrollTop() + (targetRect.top - stageRect.top) - 18;
            $stage.stop(true).animate({scrollTop: Math.max(0,nextTop)}, 180);
        }

        function syncLivePreviewStructure(focusId){
            var $root = livePreviewRoot();
            if (!$root.length) return;

            var validCustom = {};
            state.layout.forEach(function(row){ if (row && row.kind === 'custom') validCustom[row.id] = true; });
            $root.children('.gep-structure-live-custom').each(function(){
                if (!validCustom[$(this).attr('data-gep-live-custom')]) $(this).remove();
            });

            var $focus = $();
            state.layout.forEach(function(row){
                if (!row || !row.id) return;
                var $nodes = row.kind === 'custom' ? livePreviewCustomNode(row,$root) : livePreviewBuiltinNodes(row.id,$root);
                if (!$nodes.length) return;
                $nodes.toggleClass('gep-structure-preview-hidden', row.enabled === false);
                $nodes.each(function(){ $root.append(this); });
                if (row.id === focusId) $focus = $nodes;
            });
            if (String(data.context || '') === 'publications') {
                var $stats = $root.children('[data-publication-live="records-stats"]'), $tools = $root.children('[data-publication-live="search"]');
                if ($stats.length && $tools.length && !$tools.hasClass('gep-structure-preview-hidden')) $stats.insertBefore($tools);
            }
            var selectedId = focusId || activePreviewId || firstEnabledPreviewId();
            if (selectedId) applySinglePreview(selectedId, !!focusId);
        }

        /* v9.12.1 — Todas las previews siguen el patrón de "Quiénes somos":
         * se muestra únicamente el apartado seleccionado, en vez de una página
         * completa con scroll entre todos los bloques. */
        function firstEnabledPreviewId(){
            var id = null;
            $.each(state.layout,function(_,row){
                if (!id && row && row.id && row.enabled !== false) id = row.id;
            });
            return id;
        }

        function previewHeaderSelectors(){
            var context = String(data.context || '');
            if (context === 'home') return {zone:'#gep-home-preview-zone-number', title:'#gep-home-preview-heading-title'};
            if (context === 'directory') return {zone:'#gep-preview-zone-number', title:'#gep-preview-heading-title'};
            if (context === 'contact') return {zone:'#gep-contact-preview-zone-number', title:'#gep-contact-preview-heading-title'};
            if (context === 'docencia') return {zone:'#gep-docencia-preview-zone-number', title:'#gep-docencia-preview-heading-title'};
            if (context === 'projects') return {zone:'#gep-project-preview-zone-number', title:'#gep-project-preview-heading-title'};
            if (context === 'publications') return {zone:'#gep-publication-preview-zone-number', title:'#gep-publication-preview-heading-title'};
            if (context === 'communications' || context === 'news') return {zone:'#gep-hub-preview-zone', title:'#gep-hub-preview-heading'};
            if (context === 'services') return {zone:'#gep-service-preview-zone', title:'#gep-service-preview-heading'};
            return {zone:'',title:''};
        }

        function previewZoneNumber(id){
            var zone = 0;
            $.each(state.layout,function(_,row){
                if (!row || row.enabled === false) return;
                zone++;
                if (row.id === id) return false;
            });
            return zone || 1;
        }

        function updateSinglePreviewHeading(id){
            var row = findRow(id);
            if (!row) return;
            var selectors = previewHeaderSelectors();
            if (selectors.zone) $(selectors.zone).text('Zona ' + previewZoneNumber(id)).show();
            if (selectors.title) $(selectors.title).text(rowTitle(row));
        }

        function applySinglePreview(id, pulse){
            if (previewMode === 'general') { applyGeneralPreview(id, pulse); return; }
            var row = findRow(id);
            if (!row || row.enabled === false) {
                id = firstEnabledPreviewId();
                row = id ? findRow(id) : null;
            }
            if (!row) return;
            activePreviewId = id;

            var context = String(data.context || '');
            var $root = livePreviewRoot();
            if (!$root.length) return;
            $root.removeClass('gep-structure-general-preview');
            $root.children().removeClass('gep-structure-general-visible');
            previewModeHeading('section', id);

            // Quiénes somos ya usa paneles mutuamente excluyentes. Conservamos
            // su comportamiento nativo y hacemos que el resto lo imite.
            if (context === 'directory') {
                previewBuiltin(id, true);
                updateSinglePreviewHeading(id);
                return;
            }

            var $selected = $();
            state.layout.forEach(function(item){
                if (!item || !item.id) return;
                var $nodes = item.kind === 'custom' ? livePreviewCustomNode(item,$root) : livePreviewBuiltinNodes(item.id,$root);
                if (!$nodes.length) return;
                var show = item.id === id && item.enabled !== false;
                $nodes.toggleClass('gep-structure-preview-single-hidden', !show);
                if (show) $selected = $selected.add($nodes);
            });

            updateSinglePreviewHeading(id);
            var $stage = livePreviewStage();
            if ($stage.length) $stage.stop(true).scrollTop(0);
            if (pulse && $selected.length) {
                var $target = $selected.filter(':visible').first();
                if ($target.length) {
                    $target.addClass('gep-structure-preview-moved');
                    window.setTimeout(function(){ $target.removeClass('gep-structure-preview-moved'); },700);
                }
            }
        }

        function positionHoverPreview($anchor){
            var $preview = ensureHoverPreview();
            var node = $anchor && $anchor[0];
            if (!node) return;
            var rect = node.getBoundingClientRect();
            var width = Math.min(460, Math.max(330, window.innerWidth * 0.30));
            var height = Math.min(360, window.innerHeight - 32);
            var left = rect.right + 14;
            if (left + width > window.innerWidth - 16) left = rect.left - width - 14;
            if (left < 16) left = window.innerWidth - width - 16;
            var top = Math.max(16, Math.min(rect.top - 12, window.innerHeight - height - 16));
            $preview.css({left:left+'px',top:top+'px',width:width+'px',maxHeight:height+'px'});
        }

        function showHoverPreview(row, $anchor){
            clearTimeout(hoverPreviewTimer);
            hoverPreviewTimer = setTimeout(function(){
                var $preview = ensureHoverPreview();
                var $canvas = $preview.find('.gep-structure-hover-canvas').empty();
                var title = rowTitle(row);
                $preview.find('.gep-structure-hover-head strong').text(title);
                if (row.kind === 'custom') {
                    var section = state.customs[row.id] || {};
                    $canvas.html(customPreviewHtml(section, section.type));
                } else {
                    if (previewMode !== 'general') previewBuiltin(row.id, true);
                    var $nodes = builtinPreviewNodes(row.id);
                    if ($nodes.length) {
                        $nodes.each(function(){ $canvas.append(cleanPreviewClone($(this).clone(false,false))); });
                    } else {
                        var info = typeInfo(row);
                        $canvas.html(customPreviewHtml({title:title,text:info.label || 'Apartado'}, 'text'));
                    }
                }
                positionHoverPreview($anchor);
                $preview.removeClass('is-hidden').attr('aria-hidden','false');
            }, 90);
        }

        function showLibraryPreview(type, $anchor){
            clearTimeout(hoverPreviewTimer);
            hoverPreviewTimer = setTimeout(function(){
                var item = catalog[type] || {};
                var sample = blankCustom(type, 'preview');
                sample.title = item.label || 'Nuevo apartado';
                sample.text = item.description || 'Vista previa del apartado.';
                var $preview = ensureHoverPreview();
                $preview.find('.gep-structure-hover-head strong').text(item.label || 'Nuevo apartado');
                $preview.find('.gep-structure-hover-canvas').html(customPreviewHtml(sample,type));
                positionHoverPreview($anchor);
                $preview.removeClass('is-hidden').attr('aria-hidden','false');
            }, 90);
        }

        function hideHoverPreview(){
            clearTimeout(hoverPreviewTimer);
            if ($hoverPreview && $hoverPreview.length) $hoverPreview.addClass('is-hidden').attr('aria-hidden','true');
        }

        function editBuiltin(id){
            var row = findRow(id), $row = $('#gep-structure-list .gep-structure-row[data-section-id="'+id.replace(/"/g,'\\"')+'"]');
            if (!row || !$row.length) return;
            parkInlineEditors();
            openRows[id] = true;
            $row.addClass('is-open is-editing').find('.gep-structure-summary').attr('aria-expanded','true');
            var $host = $row.find('.gep-structure-inline-editor').removeClass('is-hidden').empty();
            var $sources = legacySourceNodes(id);
            $host.append('<div class="gep-inline-editor-head"><div><span>Editando</span><strong>'+escapeHtml(rowTitle(row))+'</strong></div><button type="button" class="button-link gep-inline-editor-close"><span class="dashicons dashicons-no-alt"></span> Cerrar edición</button></div>');
            var $content = $('<div class="gep-inline-editor-content"></div>').appendTo($host);
            if ($sources.length === 1) $content.addClass('is-single');
            $sources.each(function(){ var $source=$(this).appendTo($content).show().removeClass('is-collapsed'); $source.children('.gep-step-heading').attr('aria-expanded','true'); if ($source.is('details')) $source.prop('open',true); });
            editingRowId = id;
            enterFocus(id, $row);
            previewBuiltin(id,true);
            $(document).trigger('gep:builtin-edit', [String(data.context || ''), id]);
        }

        function blankCustom(type, id){
            return {id:id,type:type,eyebrow:'',title:'',text:'',image:'',image_alt:'',image_side:'right',video_url:'',button:'',url:'',items:[],buttons:[]};
        }

        function renderCustomEditor(id){
            activeCustom = id;
            editingRowId = id;
            var section = state.customs[id];
            var $row = $('#gep-structure-list .gep-structure-row[data-section-id="'+id.replace(/"/g,'\\"')+'"]');
            if (!section || !$row.length) return;
            parkInlineEditors();
            activeCustom = id; editingRowId = id;
            openRows[id] = true;
            $row.addClass('is-open is-editing').find('.gep-structure-summary').attr('aria-expanded','true');
            var info = catalog[section.type] || {label:'Apartado'};
            var $editor = $row.find('.gep-structure-inline-editor').removeClass('is-hidden').empty();
            var html = '<div class="gep-custom-editor-head gep-inline-editor-head"><div><span>'+escapeHtml(info.label)+'</span><strong>Editar apartado</strong></div><button type="button" class="button-link gep-custom-editor-close"><span class="dashicons dashicons-no-alt"></span> Cerrar edición</button></div>';
            html += '<div class="gep-custom-editor-shell"><div class="gep-custom-editor-grid">';
            // v9.25.0: los tres textos son opcionales; si se dejan vacíos no ocupan espacio.
            html += field('eyebrow','Texto superior',section.eyebrow,'',true);
            html += field('title','Título',section.title,'',true);
            html += textarea('text','Descripción',section.text,true);

            if (section.type === 'image_text') {
                html += '<div class="gep-custom-field gep-custom-image-field"><label>Imagen</label><div class="gep-custom-image-actions"><input type="text" data-custom-field="image" value="'+escapeHtml(section.image)+'" placeholder="https://..."><button type="button" class="button gep-custom-media">Mediateca</button></div></div>';
                html += field('image_alt','Texto alternativo',section.image_alt);
                html += '<label class="gep-custom-field"><span>Posición de imagen</span><select data-custom-field="image_side"><option value="right"'+(section.image_side!=='left'?' selected':'')+'>Derecha</option><option value="left"'+(section.image_side==='left'?' selected':'')+'>Izquierda</option></select></label>';
            }
            if (section.type === 'video') {
                html += '<div class="gep-custom-video-control is-wide">'+
                    '<div class="gep-custom-video-control-head"><div><span>Video</span><strong>Selecciona un archivo o pega un enlace</strong><small>WordPress, YouTube y Vimeo.</small></div><div class="gep-custom-video-buttons"><button type="button" class="button button-primary gep-custom-video-media"><span class="dashicons dashicons-video-alt3"></span> Elegir desde WordPress</button><button type="button" class="button gep-custom-video-remove"'+(!section.video_url?' disabled':'')+'>Quitar</button></div></div>'+
                    '<label class="gep-custom-field is-wide gep-custom-video-url"><span>URL del video</span><input type="url" data-custom-field="video_url" value="'+escapeHtml(section.video_url || '')+'" placeholder="https://..."><small>Puedes usar un MP4/WebM subido a la Mediateca o un enlace de YouTube/Vimeo.</small></label>'+
                    '<div class="gep-custom-video-editor-preview">'+customVideoMediaHtml(section.video_url || '', false)+'</div>'+
                '</div>';
            }
            html += '</div>';
            normalizeButtons(section);
            html += '<div class="gep-custom-items-head gep-buttons-head"><div><strong>Botones</strong><small>Opcional. Hasta '+MAX_BUTTONS+' botones; arrástralos para cambiar el orden.</small></div><button type="button" class="button gep-button-add"'+(section.buttons.length >= MAX_BUTTONS ? ' disabled' : '')+'><span class="dashicons dashicons-plus-alt2"></span> Añadir botón</button></div><div class="gep-button-list"></div>';

            if (section.type === 'cards' || section.type === 'stats') {
                html += '<div class="gep-custom-items-head"><strong>'+(section.type==='stats'?'Cifras':'Tarjetas')+'</strong><button type="button" class="button gep-custom-add-item"><span class="dashicons dashicons-plus-alt2"></span> Añadir</button></div><div class="gep-custom-items"></div>';
            }
            html += '</div>';
            $editor.html(html);
            renderCustomItems();
            renderButtonRows();
            enterFocus(id, $row);
        }

        function optionalTag(optional){ return optional ? ' <em class="gep-optional">(opcional)</em>' : ''; }
        function field(key,label,value,placeholder,optional){
            return '<label class="gep-custom-field"><span>'+escapeHtml(label)+optionalTag(optional)+'</span><input type="text" data-custom-field="'+escapeHtml(key)+'" value="'+escapeHtml(value || '')+'" placeholder="'+escapeHtml(placeholder || '')+'"></label>';
        }
        function textarea(key,label,value,optional){
            return '<label class="gep-custom-field is-wide"><span>'+escapeHtml(label)+optionalTag(optional)+'</span><textarea rows="4" data-custom-field="'+escapeHtml(key)+'">'+escapeHtml(value || '')+'</textarea></label>';
        }

        /* v9.25.0 · Editor visual de botones (sin HTML). */
        function pageForUrl(url){
            url = $.trim(url || '');
            if (!url) return '';
            var found = '';
            $.each(sitePages,function(_,pg){ if (pg && pg.url === url) { found = pg.url; return false; } });
            return found;
        }
        function buttonRowHtml(btn, idx, total){
            var page = pageForUrl(btn.url);
            var options = '<option value="">Elige una página…</option>';
            $.each(sitePages,function(_,pg){ options += '<option value="'+escapeHtml(pg.url)+'"'+(page === pg.url ? ' selected' : '')+'>'+escapeHtml(pg.label)+'</option>'; });
            options += '<option value="__other"'+(!page && $.trim(btn.url) ? ' selected' : '')+'>Otro enlace (escribir dirección)</option>';
            var showUrl = !page && $.trim(btn.url) ? '' : ' is-hidden';
            return '<div class="gep-custom-item gep-button-row" data-button-index="'+idx+'">'+
                '<div class="gep-custom-item-head"><span class="dashicons dashicons-menu-alt2" title="Arrastra para mover"></span><strong>Botón '+(idx+1)+(btn.text ? ' · '+escapeHtml(btn.text) : '')+'</strong>'+
                '<button type="button" class="button-link gep-button-move" data-dir="-1"'+(idx===0?' disabled':'')+' aria-label="Subir botón"><span class="dashicons dashicons-arrow-up-alt2"></span></button>'+
                '<button type="button" class="button-link gep-button-move" data-dir="1"'+(idx>=total-1?' disabled':'')+' aria-label="Bajar botón"><span class="dashicons dashicons-arrow-down-alt2"></span></button>'+
                '<button type="button" class="button-link-delete gep-button-remove">Eliminar</button></div>'+
                '<div class="gep-custom-editor-grid">'+
                    '<label class="gep-custom-field"><span>Texto del botón</span><input type="text" maxlength="60" data-button-field="text" value="'+escapeHtml(btn.text)+'" placeholder="Ej: Ver proyectos"></label>'+
                    '<label class="gep-custom-field"><span>¿Adónde lleva?</span><select data-button-field="page">'+options+'</select></label>'+
                    '<label class="gep-custom-field is-wide gep-button-url'+showUrl+'"><span>Dirección del enlace</span><input type="text" data-button-field="url" value="'+escapeHtml(page ? '' : btn.url)+'" placeholder="https://… · correo@dominio.cl · #seccion"><small>Pega una dirección web, un correo o un ancla (#nombre).</small></label>'+
                    '<label class="gep-custom-field"><span>Al pulsarlo</span><select data-button-field="target"><option value="same"'+(btn.target!=='new'?' selected':'')+'>Abrir en la misma pestaña</option><option value="new"'+(btn.target==='new'?' selected':'')+'>Abrir en una pestaña nueva</option></select></label>'+
                    '<label class="gep-custom-field"><span>Estilo</span><select data-button-field="style"><option value="primary"'+(btn.style!=='secondary'?' selected':'')+'>Principal (relleno)</option><option value="secondary"'+(btn.style==='secondary'?' selected':'')+'>Secundario (borde)</option></select></label>'+
                '</div></div>';
        }
        function renderButtonRows(){
            if (!activeCustom) return;
            var section = state.customs[activeCustom];
            var $editor = $('#gep-structure-list .gep-structure-row[data-section-id="'+activeCustom+'"] .gep-structure-inline-editor');
            var $root = $editor.find('.gep-button-list').empty();
            if (!section) return;
            var list = normalizeButtons(section);
            if (!list.length) $root.append('<p class="gep-button-empty">Este apartado no tiene botones. Pulsa «Añadir botón» si quieres uno.</p>');
            list.forEach(function(btn, idx){ $root.append(buttonRowHtml(btn, idx, list.length)); });
            $editor.find('.gep-button-add').prop('disabled', list.length >= MAX_BUTTONS);
            if ($root.hasClass('ui-sortable')) $root.sortable('destroy');
            $root.sortable({items:'> .gep-button-row',handle:'.gep-custom-item-head>.dashicons-menu-alt2',placeholder:'gep-custom-item-placeholder',forcePlaceholderSize:true,update:function(){
                var ordered=[];
                $root.children('.gep-button-row').each(function(){var i=parseInt($(this).attr('data-button-index'),10); if(section.buttons[i]) ordered.push(section.buttons[i]);});
                section.buttons=ordered; buttonsChanged(true);
            }});
        }
        function buttonsChanged(rerender){
            if (!activeCustom || !state.customs[activeCustom]) return;
            syncLegacyButton(state.customs[activeCustom]);
            if (rerender) renderButtonRows();
            refreshRowPresentation(activeCustom); syncHidden(true); syncLivePreviewStructure(activeCustom);
        }

        function renderCustomItems(){
            if (!activeCustom) return;
            var section = state.customs[activeCustom];
            var $root = $('#gep-structure-list .gep-structure-row[data-section-id="'+activeCustom+'"] .gep-custom-items').empty();
            if (!section || !Array.isArray(section.items)) return;
            section.items.forEach(function(item, idx){
                var html = '<div class="gep-custom-item" data-item-index="'+idx+'"><div class="gep-custom-item-head"><span class="dashicons dashicons-menu-alt2"></span><strong>Elemento '+(idx+1)+'</strong><button type="button" class="button-link-delete gep-custom-remove-item">Eliminar</button></div><div class="gep-custom-editor-grid">';
                if (section.type === 'stats') {
                    html += itemField(idx,'value','Valor',item.value || '');
                    html += itemField(idx,'label','Etiqueta',item.label || '');
                    html += itemField(idx,'text','Detalle',item.text || '');
                } else {
                    html += itemField(idx,'title','Título',item.title || '');
                    html += itemText(idx,'text','Texto',item.text || '');
                    html += itemField(idx,'image','Imagen',item.image || '');
                    html += itemField(idx,'button','Texto del enlace',item.button || '');
                    html += itemField(idx,'url','Enlace',item.url || '');
                }
                html += '</div></div>';
                $root.append(html);
            });
            if ($root.hasClass('ui-sortable')) $root.sortable('destroy');
            $root.sortable({items:'> .gep-custom-item',handle:'.gep-custom-item-head .dashicons',placeholder:'gep-custom-item-placeholder',forcePlaceholderSize:true,update:function(){
                var ordered=[];
                $root.children('.gep-custom-item').each(function(){var i=parseInt($(this).attr('data-item-index'),10); if(section.items[i]) ordered.push(section.items[i]);});
                section.items=ordered; renderCustomItems(); syncHidden(true); syncLivePreviewStructure(activeCustom);
            }});
        }
        function itemField(idx,key,label,value){return '<label class="gep-custom-field"><span>'+escapeHtml(label)+'</span><input type="text" data-item-field="'+escapeHtml(key)+'" data-item-index="'+idx+'" value="'+escapeHtml(value)+'"></label>';}
        function itemText(idx,key,label,value){return '<label class="gep-custom-field is-wide"><span>'+escapeHtml(label)+'</span><textarea rows="3" data-item-field="'+escapeHtml(key)+'" data-item-index="'+idx+'">'+escapeHtml(value)+'</textarea></label>';}

        renderLibrary();
        renderList();
        syncHidden(false);
        installPreviewModeToggle();
        installPreviewSiteHeader();
        syncLivePreviewStructure();
        setPreviewMode(previewMode, activePreviewId || firstEnabledPreviewId());

        // v9.24.0: tras Guardar, la página vuelve a abrir el apartado que se editaba.
        (function(){
            var saved = '';
            try { saved = window.sessionStorage.getItem(focusStorageKey()) || ''; } catch(e) {}
            if (!saved) return;
            if (!/[?&]gep_saved=1/.test(window.location.search) || !findRow(saved)) { rememberFocus(''); return; }
            window.setTimeout(function(){ openAndEdit(saved); }, 60);
        })();

        $(document).on('click','.gep-preview-mode-button',function(e){
            e.preventDefault();
            setPreviewMode($(this).attr('data-preview-mode') || 'section', activePreviewId || firstEnabledPreviewId());
        });

        $(document).on('click','.gep-focus-step',function(e){
            e.preventDefault();
            var id = $(this).attr('data-section-id');
            if (id === editingRowId) { closeEditing(id); return; }
            openAndEdit(id);
        });
        $(document).on('click','.gep-focus-back',function(e){
            e.preventDefault();
            if (editingRowId) closeEditing(editingRowId);
        });

        $(document).on('click','.gep-structure-add',function(){ $('#gep-structure-library').toggleClass('is-hidden'); });
        $(document).on('click','.gep-structure-library-close',function(){ $('#gep-structure-library').addClass('is-hidden'); });
        $(document).on('click','.gep-library-card',function(){
            var type = $(this).attr('data-type');
            if (!catalog[type]) return;
            customCounter++;
            var id = 'custom_' + customCounter.toString(36);
            state.customs[id] = blankCustom(type,id);
            state.layout.push({id:id,kind:'custom',enabled:true});
            $('#gep-structure-library').addClass('is-hidden');
            renderList(); renderCustomEditor(id); syncHidden(true); syncLivePreviewStructure(id);
            // v9.25.0: listo para escribir, sin otro salto de pantalla.
            window.setTimeout(function(){
                var $fields = $('#gep-structure-list .gep-structure-row[data-section-id="'+id+'"] .gep-structure-inline-editor [data-custom-field]').filter(':visible');
                var $first = $fields.filter('[data-custom-field="title"]').first();
                if (!$first.length) $first = $fields.first();
                if ($first.length) { try { $first[0].focus({preventScroll:true}); } catch(e) { $first[0].focus(); } }
                if (typeof window.gepFlashElement === 'function') { var $r = $('#gep-structure-list .gep-structure-row[data-section-id="'+id+'"]'); if ($r.length) window.gepFlashElement($r[0]); }
            }, 380);
        });

        $(document).on('click keydown','.gep-structure-summary',function(e){
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
            if ($(e.target).closest('.gep-structure-grip').length) return;
            e.preventDefault();
            var $row=$(this).closest('.gep-structure-row'),id=$row.attr('data-section-id'), willOpen=!$row.hasClass('is-open');
            if (editingRowId) parkInlineEditors();
            $.each(openRows,function(key){openRows[key]=false;});
            $('#gep-structure-list .gep-structure-row').removeClass('is-open is-editing').find('.gep-structure-summary').attr('aria-expanded','false');
            if (willOpen) {
                openRows[id]=true; $row.addClass('is-open'); $(this).attr('aria-expanded','true');
                refreshRowPresentation(id);
                applySinglePreview(id, true);
            } else {
                applySinglePreview(id, false);
            }
        });
        $(document).on('click','.gep-structure-edit',function(e){
            e.preventDefault(); e.stopPropagation();
            var id = $(this).closest('.gep-structure-row').attr('data-section-id');
            var row = findRow(id); if (!row) return;
            // v9.23.3: si ya se está editando, pulsar de nuevo lo cierra.
            if ($(this).closest('.gep-structure-row').hasClass('is-editing')) { $(this).closest('.gep-structure-row').find('.gep-custom-editor-close,.gep-inline-editor-close').first().trigger('click'); return; }
            hideHoverPreview();
            applySinglePreview(id, true);
            if (row.kind === 'custom') renderCustomEditor(id); else editBuiltin(id);
        });
        // v9.13.4 — Los apartados existentes ya tienen la preview principal de la derecha.
        // Evitamos abrir una segunda preview flotante al pasar el mouse por ellos.
        // La preview flotante queda reservada únicamente para los tipos nuevos de
        // la biblioteca "Añadir apartado", donde sí sirve para previsualizar antes
        // de crear el bloque.
        $(document).on('mouseenter','.gep-structure-row',function(){
            // v9.24.1: en modo General pasar el mouse no mueve la vista previa
            // (antes la desplazaba arriba y abajo con cada fila). Solo el clic la enfoca.
            if(previewMode==='general') return;
            var $row=$(this),id=$row.attr('data-section-id'),row=findRow(id);
            if(row&&!$row.hasClass('is-editing')) applySinglePreview(id,false);
        });
        $(document).on('mouseleave','.gep-structure-row',function(){
            var id=$(this).attr('data-section-id'),row=findRow(id);
            if(previewMode!=='general'&&row&&row.kind==='builtin') previewBuiltin(id,false);
        });
        $(document).on('mouseenter','.gep-library-card',function(){ showLibraryPreview($(this).attr('data-type'),$(this)); });
        $(document).on('mouseleave','.gep-library-card',hideHoverPreview);

        $(document).on('click','.gep-structure-remove',function(e){ e.preventDefault(); e.stopPropagation();
            var id=$(this).closest('.gep-structure-row').attr('data-section-id'), row=findRow(id); if(!row)return;
            if (!window.confirm((data.strings||{}).deleteConfirm || '¿Eliminar este apartado?')) return;
            row.enabled=false; if(activeCustom===id)parkInlineEditors(); renderList(); syncHidden(true); syncLivePreviewStructure(id);
        });
        $(document).on('click','.gep-structure-restore',function(e){ e.preventDefault(); e.stopPropagation();
            var id=$(this).closest('.gep-structure-row').attr('data-section-id'), row=findRow(id); if(!row)return;
            row.enabled=true; renderList(); syncHidden(true); syncLivePreviewStructure(id);
        });

        $(document).on('click','.gep-custom-editor-close,.gep-inline-editor-close',function(e){
            e.preventDefault(); e.stopPropagation();
            var id=$(this).closest('.gep-structure-row').attr('data-section-id');
            parkInlineEditors();
            $('#gep-structure-list .gep-structure-row[data-section-id="'+String(id).replace(/"/g,'\\"')+'"]').removeClass('is-editing');
            refreshRowPresentation(id);
        });
        $(document).on('input change','.gep-structure-inline-editor [data-custom-field]',function(){
            if(!activeCustom||!state.customs[activeCustom])return;
            var key=$(this).attr('data-custom-field'); state.customs[activeCustom][key]=$(this).val()||'';
            if (key === 'video_url') {
                var $editor=$(this).closest('.gep-structure-inline-editor');
                $editor.find('.gep-custom-video-editor-preview').html(customVideoMediaHtml(state.customs[activeCustom].video_url || '', false));
                $editor.find('.gep-custom-video-remove').prop('disabled', !$.trim(state.customs[activeCustom].video_url || ''));
            }
            refreshRowPresentation(activeCustom); syncHidden(true); syncLivePreviewStructure(activeCustom);
        });
        $(document).on('click','.gep-custom-media',function(){
            if(!activeCustom||!window.wp||!wp.media)return;
            var frame=wp.media({title:'Seleccionar imagen',button:{text:'Usar imagen'},multiple:false,library:{type:'image'}});
            frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();state.customs[activeCustom].image=a.url||'';renderCustomEditor(activeCustom);syncHidden(true);syncLivePreviewStructure(activeCustom);}); frame.open();
        });
        $(document).on('click','.gep-custom-video-media',function(e){
            e.preventDefault();
            if(!activeCustom||!window.wp||!wp.media)return;
            var sectionId=activeCustom;
            var frame=wp.media({
                title:'Seleccionar video',
                button:{text:'Usar este video'},
                multiple:false,
                library:{type:'video'}
            });
            frame.on('select',function(){
                var a=frame.state().get('selection').first().toJSON();
                if(!state.customs[sectionId])return;
                state.customs[sectionId].video_url=a&&a.url?a.url:'';
                if(!$.trim(state.customs[sectionId].title||'') && a && a.title) state.customs[sectionId].title=a.title;
                activeCustom=sectionId;
                renderCustomEditor(sectionId);
                refreshRowPresentation(sectionId);
                syncHidden(true);
                syncLivePreviewStructure(sectionId);
            });
            frame.open();
        });
        $(document).on('click','.gep-custom-video-remove',function(e){
            e.preventDefault();
            if(!activeCustom||!state.customs[activeCustom])return;
            state.customs[activeCustom].video_url='';
            renderCustomEditor(activeCustom);
            refreshRowPresentation(activeCustom);
            syncHidden(true);
            syncLivePreviewStructure(activeCustom);
        });
        $(document).on('click','.gep-custom-add-item',function(){
            if(!activeCustom||!state.customs[activeCustom])return; var s=state.customs[activeCustom]; if(!Array.isArray(s.items))s.items=[];
            s.items.push(s.type==='stats'?{value:'',label:'',text:''}:{title:'',text:'',image:'',button:'',url:''}); renderCustomItems(); refreshRowPresentation(activeCustom); syncHidden(true); syncLivePreviewStructure(activeCustom);
        });
        $(document).on('input change','.gep-structure-inline-editor [data-item-field]',function(){
            if(!activeCustom||!state.customs[activeCustom])return; var idx=parseInt($(this).attr('data-item-index'),10),key=$(this).attr('data-item-field');
            if(!state.customs[activeCustom].items[idx])return; state.customs[activeCustom].items[idx][key]=$(this).val()||''; refreshRowPresentation(activeCustom); syncHidden(true); syncLivePreviewStructure(activeCustom);
        });
        $(document).on('click','.gep-custom-remove-item',function(){
            if(!activeCustom||!state.customs[activeCustom])return; var idx=parseInt($(this).closest('.gep-custom-item').attr('data-item-index'),10); if(!isFinite(idx))return;
            state.customs[activeCustom].items.splice(idx,1); renderCustomItems(); refreshRowPresentation(activeCustom); syncHidden(true); syncLivePreviewStructure(activeCustom);
        });

        $(document).on('click','.gep-button-add',function(e){
            e.preventDefault();
            if(!activeCustom||!state.customs[activeCustom])return;
            var list=normalizeButtons(state.customs[activeCustom]); if(list.length>=MAX_BUTTONS)return;
            list.push({text:'',url:'',target:'same',style:list.length?'secondary':'primary'});
            buttonsChanged(true);
            var $last=$('#gep-structure-list .gep-structure-row[data-section-id="'+activeCustom+'"] .gep-button-row').last();
            if($last.length&&typeof window.gepRevealNew==='function') window.gepRevealNew($last[0]);
        });
        $(document).on('click','.gep-button-remove',function(e){
            e.preventDefault();
            if(!activeCustom||!state.customs[activeCustom])return;
            var idx=parseInt($(this).closest('.gep-button-row').attr('data-button-index'),10); if(!isFinite(idx))return;
            state.customs[activeCustom].buttons.splice(idx,1); buttonsChanged(true);
        });
        $(document).on('click','.gep-button-move',function(e){
            e.preventDefault();
            if(!activeCustom||!state.customs[activeCustom])return;
            var list=state.customs[activeCustom].buttons, idx=parseInt($(this).closest('.gep-button-row').attr('data-button-index'),10), to=idx+parseInt($(this).attr('data-dir'),10);
            if(!isFinite(idx)||to<0||to>=list.length)return;
            var moved=list.splice(idx,1)[0]; list.splice(to,0,moved); buttonsChanged(true);
            var $btn=$('#gep-structure-list .gep-structure-row[data-section-id="'+activeCustom+'"] .gep-button-row').eq(to).find('.gep-button-move[data-dir="'+$(this).attr('data-dir')+'"]');
            if($btn.length&&!$btn.prop('disabled')) $btn[0].focus({preventScroll:true});
        });
        $(document).on('input change','.gep-structure-inline-editor [data-button-field]',function(e){
            if(!activeCustom||!state.customs[activeCustom])return;
            var $row=$(this).closest('.gep-button-row'), idx=parseInt($row.attr('data-button-index'),10), btn=(state.customs[activeCustom].buttons||[])[idx];
            if(!btn)return;
            var key=$(this).attr('data-button-field'), val=$(this).val()||'';
            if(key==='page'){
                if(e.type!=='change')return;
                var $url=$row.find('.gep-button-url');
                if(val==='__other'){ $url.removeClass('is-hidden'); btn.url=$.trim($url.find('input').val()||''); $url.find('input')[0].focus({preventScroll:true}); }
                else { $url.addClass('is-hidden'); btn.url=val; }
            } else if(key==='url') btn.url=$.trim(val);
            else if(key==='target') btn.target=val==='new'?'new':'same';
            else if(key==='style') btn.style=val==='secondary'?'secondary':'primary';
            else if(key==='text'){ btn.text=val; $row.find('.gep-custom-item-head strong').text('Botón '+(idx+1)+(val?' · '+val:'')); }
            buttonsChanged(false);
        });

        $form.on('input change', '.gep-structure-inline-editor input,.gep-structure-inline-editor textarea,.gep-structure-inline-editor select', function(){
            if (editingRowId) window.setTimeout(function(){ refreshRowPresentation(editingRowId); }, 0);
        });
    });
})(jQuery);
