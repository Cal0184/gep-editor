jQuery(function($){
    var mediaFrame = null;
    var mediaTarget = null;
    var videoMediaFrame = null;
    var videoMediaTarget = null;
    var sectionCounter = 1000;
    var itemCounter = 1000;
    var teamCardCounter = 1000;
    var docenciaStepCounter = 1000;
    var docenciaCourseCounter = 1000;
    var $form = $('#gep-content-form');
    var currentPreviewMode = 'team';
    var $currentPreviewContext = $form.find('.gep-step-card[data-preview="team"]').first();
    var hasUnsavedChanges = false;
    var isSubmitting = false;
    var homePreviewTimer = null;
    var docenciaPreviewTimer = null;

    /* v9.12.4 — Resolver único para imágenes de preview.
     * Permite URLs de WordPress y rutas relativas del sitio público (assets/...). */
    function gepPreviewResolveImage(url, shellSelector, fallback){
        url = $.trim(url || '') || $.trim(fallback || '');
        if (!url) return '';
        if (/^(?:https?:)?\/\//i.test(url) || /^(?:data:|blob:)/i.test(url) || url.charAt(0) === '/') return url;
        var $shell = shellSelector ? $(shellSelector).first() : $();
        var base = $shell.length ? ($shell.attr('data-asset-base') || $shell.data('asset-base') || $shell.attr('data-public-url') || $shell.data('public-url') || '') : '';
        if (!base && window.gepHomeAssetBase) base = String(window.gepHomeAssetBase).replace(/\/?$/, '/');
        if (!base) return url;
        try { return new URL(url, base).href; } catch(e) { return url; }
    }

    window.gepPreviewResolveImage = gepPreviewResolveImage;

    function gepPreviewSetHero($el, image, shellSelector, fallback){
        if (!$el || !$el.length) return;
        // v9.20.0: sin imagen elegida la página pública no muestra foto de cabecera,
        // así que la preview tampoco usa una foto de reemplazo (fallback).
        var url = gepPreviewResolveImage(image, shellSelector, '');
        if (url) {
            $el.css({
                'background-image':'linear-gradient(90deg,rgba(5,24,14,.84),rgba(5,24,14,.48) 58%,rgba(5,24,14,.16)),url("'+url.replace(/"/g,'%22')+'")',
                'background-size':'cover',
                'background-position':'center'
            }).addClass('has-preview-cover');
        } else {
            $el.css({'background-image':'','background-size':'','background-position':''}).removeClass('has-preview-cover');
        }
    }

    /* v9.21.3 — La preview muestra el primer cuadro del video, quieto, en vez de
     * reproducirlo: carga mucho menos y se ve igual que el inicio de la cabecera.
     * "#t=0.1" hace que el navegador (también Safari) dibuje ese cuadro. */
    function gepPreviewVideoFirstFrame(url){
        url=$.trim(url||'');
        if(!url||url.indexOf('#')>=0) return url;
        return url+'#t=0.1';
    }

    function gepPreviewVideoEmbed(url, poster){
        url=$.trim(url||''); if(!url) return null;
        var safe=gepPreviewResolveImage(url,'','');
        var fileVideo=function(){
            var $v=$('<video class="gep-admin-hero-video" muted playsinline preload="metadata" tabindex="-1" aria-hidden="true"></video>');
            if(poster) $v.attr('poster',poster);
            return $v.attr('src',gepPreviewVideoFirstFrame(safe));
        };
        if(/\.(?:mp4|m4v|webm|ogv|ogg|mov)(?:[?#].*)?$/i.test(safe) || /\/uploads\//i.test(safe)) return fileVideo();
        try{
            var parsed=new URL(safe,window.location.href), host=(parsed.hostname||'').replace(/^www\./,''), id='';
            if(host==='youtu.be') id=(parsed.pathname||'').replace(/^\//,'').split('/')[0];
            else if(host.indexOf('youtube.com')>=0){ id=parsed.searchParams.get('v')||''; if(!id){var m=(parsed.pathname||'').match(/\/(?:embed|shorts)\/([^/?#]+)/); if(m)id=m[1];} }
            // YouTube: su miniatura oficial es el cuadro de portada del video.
            if(id) return $('<img class="gep-admin-hero-video" alt="" aria-hidden="true">').attr('src','https://i.ytimg.com/vi/'+encodeURIComponent(id)+'/hqdefault.jpg');
            // Vimeo no entrega miniatura sin su API: se muestra el reproductor detenido.
            if(host.indexOf('vimeo.com')>=0){var vm=(parsed.pathname||'').match(/\/(?:video\/)?(\d+)/);if(vm)return $('<iframe class="gep-admin-hero-video gep-admin-hero-video-iframe" tabindex="-1" aria-hidden="true" frameborder="0"></iframe>').attr('src','https://player.vimeo.com/video/'+vm[1]+'?autoplay=0&muted=1&controls=0&title=0&byline=0&portrait=0&dnt=1');}
        }catch(e){}
        return fileVideo();
    }

    /* Pone (o deja) el cuadro del video en la cabecera de la preview. Si el
     * video no cambió se conserva el que ya está, así escribir un texto no
     * vuelve a descargar el video. Devuelve true si hay video. */
    function gepPreviewPlaceHeroVideo($el, url){
        if(!$el||!$el.length)return false;
        url=$.trim(url||'');
        var $old=$el.children('.gep-admin-hero-video');
        if(url && $old.length===1 && $old.attr('data-gep-video')===url) return true;
        $old.remove();
        var $node=url?gepPreviewVideoEmbed(url):null;
        if(!$node||!$node.length) return false;
        $el.prepend($node.attr('data-gep-video',url));
        return true;
    }

    window.gepPreviewVideoFirstFrame = gepPreviewVideoFirstFrame;
    window.gepPreviewHeroVideo = gepPreviewVideoEmbed;
    window.gepPreviewPlaceHeroVideo = gepPreviewPlaceHeroVideo;

    function gepPreviewSetHeroMedia($el, image, video, shellSelector, fallback){
        if(!$el||!$el.length)return;
        var resolvedVideo=gepPreviewResolveImage(video,shellSelector,'');
        if(resolvedVideo){
            $el.toggleClass('has-preview-video',gepPreviewPlaceHeroVideo($el,resolvedVideo));
            gepPreviewSetHero($el,image,shellSelector,fallback);
        }else{
            $el.children('.gep-admin-hero-video').remove();
            $el.removeClass('has-preview-video');
            gepPreviewSetHero($el,image,shellSelector,fallback);
        }
    }

    function newKey(prefix, counter){
        return prefix + Date.now() + '_' + counter;
    }

    function markDirty(){
        hasUnsavedChanges = true;
        var $status = $('#gep-save-status');
        if ($status.length) {
            $status.removeClass('is-saved').addClass('is-dirty');
            $status.find('.dashicons').attr('class','dashicons dashicons-warning');
            $status.find('strong').text('Cambios sin guardar');
            $status.find('small').text('Pulsa “Guardar cambios” cuando termines.');
        }
        $('.gep-save-button').prop('disabled', false);
        var $inline = $('#gep-contact-inline-status,#gep-directory-inline-status,#gep-docencia-inline-status,#gep-project-inline-status,#gep-publication-inline-status,.gep-hub-inline-status');
        if ($inline.length) {
            $inline.removeClass('is-clean is-error').addClass('is-dirty').html('<span class="dashicons dashicons-warning"></span> Cambios sin guardar');
        }
    }

    function markClean(){
        hasUnsavedChanges = false;
        $('.gep-save-button').prop('disabled', true);
        var $status = $('#gep-save-status');
        if ($status.length && ($('#gep-contact-preview-shell').length || $('#gep-directory-preview-shell').length || $('#gep-docencia-preview-shell').length || $('#gep-project-preview-shell').length || $('#gep-publication-preview-shell').length)) {
            $status.removeClass('is-dirty').addClass('is-saved');
            $status.find('.dashicons').attr('class','dashicons dashicons-yes-alt');
            $status.find('strong').text('Todo guardado');
            $status.find('small').text('No hay cambios pendientes.');
        }
        var $inline = $('#gep-contact-inline-status,#gep-directory-inline-status,#gep-docencia-inline-status,#gep-project-inline-status,#gep-publication-inline-status,.gep-hub-inline-status');
        if ($inline.length) {
            $inline.removeClass('is-dirty is-error').addClass('is-clean').html('<span class="dashicons dashicons-yes-alt"></span> Todo guardado');
        }
    }

    function updateHeaderPreview(){
        $('#gep-preview-eyebrow').text($('#gep_directory_eyebrow').val() || 'Directorio GEP');
        $('#gep-preview-title').text($('#gep_directory_title').val() || 'Título de la sección');
        $('#gep-preview-description').text($('#gep_directory_description').val() || 'Texto introductorio de la sección.');
        $('#gep-preview-legend').text($('#gep_directory_legend').val() || 'Estados del registro original: D = en desarrollo · T = terminado.');

        var directoryFallbackHero = $('#gep-directory-preview-shell').attr('data-hero-fallback') || 'assets/field/trabajo-terreno-araucarias.jpg';
        gepPreviewSetHeroMedia(
            $('.gep-preview-pane[data-pane="header"] .gep-live-preview'),
            $('[name="gep_about_hero_image"]').val() || '',
            $('[name="gep_about_hero_video"]').val() || '',
            '#gep-directory-preview-shell',
            directoryFallbackHero
        );

        var featured = gepPreviewResolveImage($('[name="gep_directory_image_url"]').val() || '', '#gep-directory-preview-shell', '');
        var $feature = $('#gep-preview-directory-feature');
        if ($feature.length) {
            $feature.toggle(!!featured);
            $feature.find('img').attr('src', featured || '');
        }
    }

    function updateTeamPreview(){
        var eyebrow = $.trim($('#gep_team_eyebrow').val() || '') || 'Equipo y disciplinas';
        var title = $.trim($('#gep_team_title').val() || '') || 'Personas detrás\nde los datos,\nmapas y paisajes.';
        var description = $.trim($('#gep_team_description').val() || '') || 'Estas imágenes son referenciales y se reemplazarán por fotografías institucionales del equipo antes de la publicación definitiva.';

        $('#gep-preview-team-eyebrow').text(eyebrow);
        $('#gep-preview-team-title').html($('<div>').text(title).html().replace(/\n/g, '<br>'));
        $('#gep-preview-team-description').text(description);

        var $grid = $('#gep-team-preview-grid').empty();
        $('#gep-team-cards .gep-team-card-row').slice(0, 4).each(function(){
            var $card = $(this);
            var cardTitle = fieldValue($card, '[title]', 'Nueva tarjeta');
            var cardText = fieldValue($card, '[text]', 'Descripción de la disciplina');
            var cardBadge = fieldValue($card, '[badge]', 'IMAGEN REFERENCIAL');
            var image = fieldValue($card, '[image]', '');

            var $preview = $('<div class="gep-team-preview-card"></div>');
            var $media = $('<div class="gep-team-preview-image"><span class="dashicons dashicons-admin-users"></span></div>');
            image = gepPreviewResolveImage(image, '#gep-directory-preview-shell', '');
            if (image) {
                $media.append($('<img alt="">').attr('src', image));
                $media.find('.dashicons').hide();
            }
            if (cardBadge) {
                $media.append($('<span class="gep-team-preview-badge"></span>').text(cardBadge));
            }
            $preview.append($media);
            $preview.append(
                $('<div class="gep-team-preview-card-copy"></div>')
                    .append($('<strong></strong>').text(cardTitle))
                    .append($('<span></span>').text(cardText))
            );
            $grid.append($preview);
        });

        if (!$grid.children().length) {
            $grid.append('<div class="gep-empty-builder" style="grid-column:1/-1;min-height:120px"><span class="dashicons dashicons-format-gallery"></span><strong>Sin tarjetas</strong><span>Añade una tarjeta para verla aquí.</span></div>');
        }
    }

    function updateTeamEmptyState(){
        $('#gep-team-empty').toggleClass('is-hidden', $('#gep-team-cards .gep-team-card-row').length > 0);
    }

    function fieldValue($root, suffix, fallback){
        if (!$root || !$root.length) return fallback;
        var $field = $root.find('[name$="' + suffix + '"]').first();
        var value = $field.length ? $.trim($field.val() || '') : '';
        return value || fallback;
    }

    function setDemoImage($container, url){
        var $img = $container.find('img').first();
        var $icon = $container.find('.dashicons').first();
        url = gepPreviewResolveImage(url, '#gep-directory-preview-shell', '');
        if (url) {
            $img.attr('src', url).show();
            $icon.hide();
        } else {
            $img.attr('src', '').hide();
            $icon.show();
        }
    }

    function previewMeta(mode){
        var map = {
            team: ['Equipo y disciplinas', ''],
            header: ['Encabezado', ''],
            directory: ['Grupos', ''],
            people: ['Personas', ''],
            status: ['Estados / tesis', ''],
            photos: ['Tarjetas con foto', ''],
            publish: ['Publicación', '']
        };
        return map[mode] || map.header;
    }

    function activatePreview(mode, $context){
        if (!$('.gep-preview-pane[data-pane="' + mode + '"]').length) mode = 'header';
        currentPreviewMode = mode;
        $currentPreviewContext = $context && $context.length ? $context : $currentPreviewContext;
        $('.gep-preview-pane').removeClass('is-active');
        $('.gep-preview-pane[data-pane="' + mode + '"]').addClass('is-active');
        $('.gep-step-card,.gep-section-builder').removeClass('is-preview-source');
        var $section = $currentPreviewContext.closest('.gep-section-builder');
        if ($section.length) {
            $section.addClass('is-preview-source');
        } else {
            $currentPreviewContext.closest('.gep-step-card').addClass('is-preview-source');
        }
        var meta = previewMeta(mode);
        $('#gep-preview-heading-title').text(meta[0]);
        var $zoneCard = $currentPreviewContext.closest('.gep-step-card');
        var zoneNumber = $.trim($zoneCard.children('.gep-step-heading').find('.gep-step-number').first().text() || '');
        $('#gep-preview-zone-number').text(zoneNumber ? ('Zona ' + zoneNumber) : '').toggle(!!zoneNumber);
        if (mode === 'team') updateTeamPreview();
        if (mode === 'header') updateHeaderPreview();
        if (mode === 'people' || mode === 'status' || mode === 'photos') updateSectionPreview(mode, $currentPreviewContext);
    }

    function updateEmptyState(){
        $('#gep-empty-builder').toggleClass('is-hidden', $('#gep-sections .gep-section-builder').length > 0);
    }


    function previewFromElement($element){
        var $section = $element.closest('.gep-section-builder');
        if ($section.length) {
            activatePreview($section.data('type') || 'people', $element);
            return;
        }
        var $step = $element.closest('.gep-step-card[data-preview]');
        if ($step.length) activatePreview($step.data('preview'), $step);
    }

    function setDirectoryDevice(device){
        var labels = {desktop:'Vista escritorio', tablet:'Vista tablet', mobile:'Vista móvil'};
        var $frame = $('#gep-directory-device-frame');
        if (!$frame.length) return;
        $frame.removeClass('is-device-desktop is-device-tablet is-device-mobile').addClass('is-device-' + device);
        $('.gep-directory-device-button').removeClass('is-active').filter('[data-device="' + device + '"]').addClass('is-active');
        $('#gep-directory-device-label').text(labels[device] || labels.desktop);
    }

    function renameNestedNames($root, oldToken, newToken){
        $root.find('[name]').each(function(){
            var name = $(this).attr('name') || '';
            if (name.indexOf(oldToken) !== -1) $(this).attr('name', name.replace(oldToken, newToken));
        });
    }

    $(document).on('click', '.gep-directory-device-button', function(e){ e.preventDefault(); setDirectoryDevice($(this).data('device') || 'desktop'); });

    $(document).on('click', '.gep-select-image', function(e){
        e.preventDefault();
        mediaTarget = $(this).closest('.gep-image-control');
        previewFromElement(mediaTarget);
        mediaFrame = wp.media({
            title: 'Seleccionar imagen para GEP',
            button: { text: 'Usar esta imagen' },
            multiple: false
        });
        mediaFrame.on('select', function(){
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            mediaTarget.find('.gep-image-url').val(attachment.url).trigger('change');
            mediaTarget.find('.gep-image-preview img').attr('src', attachment.url);
            mediaTarget.find('.gep-image-preview').show();
            mediaTarget.find('.gep-remove-image').show();
            markDirty();
            previewFromElement(mediaTarget);
        });
        mediaFrame.open();
    });

    $(document).on('click', '.gep-remove-image', function(e){
        e.preventDefault();
        var control = $(this).closest('.gep-image-control');
        control.find('.gep-image-url').val('').trigger('change');
        control.find('.gep-image-preview').hide().find('img').attr('src','');
        $(this).hide();
        markDirty();
        previewFromElement(control);
    });

    $(document).on('click', '.gep-select-video', function(e){
        e.preventDefault();
        videoMediaTarget=$(this).closest('.gep-video-control');
        previewFromElement(videoMediaTarget);
        videoMediaFrame=wp.media({title:'Seleccionar video para GEP',button:{text:'Usar este video'},multiple:false,library:{type:'video'}});
        videoMediaFrame.on('select',function(){
            var attachment=videoMediaFrame.state().get('selection').first().toJSON(), url=attachment&&attachment.url?attachment.url:'';
            videoMediaTarget.find('.gep-video-url').val(url).trigger('change');
            videoMediaTarget.find('.gep-video-preview video').attr('src',gepPreviewVideoFirstFrame(url));
            videoMediaTarget.find('.gep-video-preview').show();
            videoMediaTarget.find('.gep-remove-video').show();
            markDirty(); previewFromElement(videoMediaTarget);
        });
        videoMediaFrame.open();
    });

    $(document).on('click', '.gep-remove-video', function(e){
        e.preventDefault();
        var control=$(this).closest('.gep-video-control');
        control.find('.gep-video-url').val('').trigger('change');
        control.find('.gep-video-preview').hide().find('video').attr('src','');
        $(this).hide(); markDirty(); previewFromElement(control);
    });

    $(document).on('click', '.gep-add-team-card', function(e){
        e.preventDefault();
        var cardKey = newKey('c', ++teamCardCounter);
        var html = $('#gep-team-card-template').html().replaceAll('__C__', cardKey);
        $('#gep-team-cards').append(html);
        var $newCard = $('#gep-team-cards .gep-team-card-row').last();
        updateTeamEmptyState();
        updateTeamPreview();
        markDirty();
        activatePreview('team', $newCard);
        $('html, body').animate({scrollTop: $newCard.offset().top - 80}, 250);
    });

    $('#gep-team-cards').on('click', '.gep-duplicate-team-card', function(e){
        e.preventDefault();
        var $source = $(this).closest('.gep-team-card-row');
        var oldKey = String($source.data('card-key') || '');
        var newCardKey = newKey('c', ++teamCardCounter);
        var $clone = $source.clone(false, false);
        $clone.attr('data-card-key', newCardKey).attr('open', true);
        renameNestedNames($clone, 'gep_team_cards[' + oldKey + ']', 'gep_team_cards[' + newCardKey + ']');
        $clone.removeData('sortable-ready').insertAfter($source);
        updateTeamEmptyState(); updateTeamPreview(); markDirty(); activatePreview('team', $clone);
    });

    $('#gep-team-cards').on('click', '.gep-remove-team-card', function(e){
        e.preventDefault();
        if (!window.confirm('¿Eliminar esta tarjeta?')) return;
        $(this).closest('.gep-team-card-row').remove();
        updateTeamEmptyState();
        updateTeamPreview();
        markDirty();
        activatePreview('team', $('.gep-step-card[data-preview="team"]'));
    });

    $('#gep-team-cards').on('click', '.gep-team-move-up', function(e){
        e.preventDefault();
        var $card = $(this).closest('.gep-team-card-row');
        var $prev = $card.prev('.gep-team-card-row');
        if ($prev.length) {
            $card.insertBefore($prev);
            updateTeamPreview();
            markDirty();
        }
    });

    $('#gep-team-cards').on('click', '.gep-team-move-down', function(e){
        e.preventDefault();
        var $card = $(this).closest('.gep-team-card-row');
        var $next = $card.next('.gep-team-card-row');
        if ($next.length) {
            $card.insertAfter($next);
            updateTeamPreview();
            markDirty();
        }
    });

    $('#gep-team-cards').on('input', '.gep-team-card-title-input', function(){
        var value = $(this).val() || 'Nueva tarjeta';
        $(this).closest('.gep-team-card-row').find('.gep-team-card-summary-title').first().text(value);
    });

    $('#gep-sections').on('input', '.gep-section-title-input', function(){
        var value = $(this).val() || 'Nuevo apartado';
        var $section = $(this).closest('.gep-section-builder');
        $section.find('.gep-section-name').first().text(value);
        if (typeof directoryRefreshStatusMode === 'function') directoryRefreshStatusMode($section);
        if (typeof directorySectionItems === 'function') { var sectionType=$section.data('type')||'people'; $section.find('.gep-directory-list-row').each(function(){ var d=directoryItemByKey($section,$(this).attr('data-item-key')); if(d) directoryUpdateRowElement($(this),sectionType,d); }); }
        previewFromElement($(this));
    });


    $form.on('focusin', 'input, textarea, select', function(){
        previewFromElement($(this));
    });

    $form.on('click', '.gep-step-heading, .gep-section-head, .gep-item-row > summary', function(e){
        previewFromElement($(e.target));
    });

    $form.on('input change', 'input, textarea, select', function(){
        markDirty();
        if ($(this).is('#gep_team_eyebrow,#gep_team_title,#gep_team_description') || $(this).closest('.gep-team-card-row').length) {
            updateTeamPreview();
        }
        if ($(this).is('#gep_directory_eyebrow,#gep_directory_title,#gep_directory_description,#gep_directory_legend,[name="gep_about_hero_image"],[name="gep_about_hero_video"],[name="gep_directory_image_url"]')) {
            updateHeaderPreview();
        }
        previewFromElement($(this));
    });


    function updateContactEmptyState(){
        $('#gep-contact-empty').toggleClass('is-hidden', $('#gep-contact-rows .gep-contact-row').length > 0);
    }

    function contactValue(selector, fallback){
        var $field = $(selector).first();
        if (!$field.length) return fallback || '';
        var value = $.trim($field.val() || '');
        return value || (fallback || '');
    }

    function gepPreviewStepMeta($step, fallbackTitle, fallbackText){
        var zone = $.trim($step.find('> .gep-step-heading .gep-step-number').first().text() || '');
        var title = $.trim($step.find('> .gep-step-heading h3').first().text() || '') || fallbackTitle || '';
        var text = $.trim($step.find('> .gep-step-heading p').first().text() || '') || fallbackText || '';
        return { zone: zone, title: title, text: text };
    }

    function gepSyncParentPreview($step, zoneSelector, titleSelector, textSelector, fallbackTitle, fallbackText){
        if (!$step || !$step.length) return;
        var meta = gepPreviewStepMeta($step, fallbackTitle, fallbackText);
        $(zoneSelector).text(meta.zone ? ('Zona ' + meta.zone) : '').toggle(!!meta.zone);
        $(titleSelector).text(meta.title);
    }

    function gepScrollPreviewStage($stage, $target){
        if (!$stage || !$stage.length || !$target || !$target.length) return;
        var stage = $stage.get(0), target = $target.get(0);
        if (!stage || !target || stage.scrollHeight <= stage.clientHeight + 8) return;
        var stageRect = stage.getBoundingClientRect(), targetRect = target.getBoundingClientRect();
        var next = stage.scrollTop + (targetRect.top - stageRect.top) - 18;
        $stage.stop(true).animate({scrollTop: Math.max(0, next)}, 150);
    }

    function highlightContactPreview(section){
        var $shell = $('#gep-contact-preview-shell');
        if (!$shell.length) return;
        $shell.find('[data-live-section]').removeClass('is-live-active');
        var $target = $shell.find('[data-live-section="' + section + '"]').addClass('is-live-active').first();
        $('.gep-step-card[data-contact-preview]').removeClass('is-preview-source');
        var $step = $('.gep-step-card[data-contact-preview="' + section + '"]').addClass('is-preview-source').first();
        gepSyncParentPreview($step, '#gep-contact-preview-zone-number', '#gep-contact-preview-heading-title', '#gep-contact-preview-heading-text', 'Contacto', 'Se actualiza mientras editas.');
        gepScrollPreviewStage($('.gep-contact-device-stage'), $target);
    }

    function renderContactRowsPreview(){
        var $list = $('#gep-contact-preview-rows').empty();
        $('#gep-contact-rows .gep-contact-row').each(function(){
            var $row = $(this);
            var label = $.trim($row.find('.gep-contact-label-input').val() || '') || 'Dato';
            var value = $.trim($row.find('.gep-contact-value-input').val() || '');
            var kind = $row.find('select[name*="[kind]"]').val() || 'text';
            if (!value && !label) return;
            var $previewRow = $('<div class="gep-contact-live-row"></div>');
            if (kind === 'email' || kind === 'url' || kind === 'tel') $previewRow.addClass('is-link');
            $previewRow.append($('<strong></strong>').text(label));
            $previewRow.append($('<span></span>').text(value || '—'));
            $list.append($previewRow);
        });
        if (!$list.children().length) {
            $list.append('<div class="gep-contact-live-empty">No hay datos en la ficha.</div>');
        }
    }

    function updateContactPreview(){
        if (!$('#gep-contact-preview-shell').length) return;
        $('#gep-contact-preview-eyebrow').text(contactValue('#gep_contact_hero_eyebrow', 'Universidad de Chile'));
        $('#gep-contact-preview-title').text(contactValue('#gep_contact_hero_title', 'Contáctanos'));
        $('#gep-contact-preview-text').text(contactValue('#gep_contact_hero_text', 'Campus Antumapu · Universidad de Chile.'));
        gepPreviewSetHeroMedia(
            $('#gep-contact-preview-shell [data-live-section="hero"]'),
            $('[name="gep_contact_hero_image"]').val() || '',
            $('[name="gep_contact_hero_video"]').val() || '',
            '#gep-contact-preview-shell',
            'assets/field/cascada-bosque.jpg'
        );
        $('#gep-contact-preview-card-eyebrow').text(contactValue('#gep_contact_card_eyebrow', 'Contacto GEP'));
        $('#gep-contact-preview-card-title').text(contactValue('#gep_contact_card_title', 'Laboratorio de Geomática y Ecología del Paisaje'));
        $('#gep-contact-preview-email-button').text(contactValue('#gep_contact_email_button_text', 'Escribir por correo'));
        $('#gep-contact-preview-map-button').text(contactValue('#gep_contact_map_button_text', 'Ver dirección ↗'));
        $('#gep-contact-preview-location-eyebrow').text(contactValue('#gep_contact_location_eyebrow', 'Ubicación'));
        $('#gep-contact-preview-location-title').text(contactValue('#gep_contact_location_title', 'Campus Antumapu'));
        $('#gep-contact-preview-location-text').text(contactValue('#gep_contact_location_text', 'La Pintana · Santiago de Chile'));

        var mapImage = gepPreviewResolveImage($('[name="gep_contact_map_image_url"]').val() || '', '#gep-contact-preview-shell', '');
        var $mapImage = $('#gep-contact-preview-map-image');
        if (mapImage) {
            $mapImage.attr('src', mapImage).show();
            $mapImage.siblings('.dashicons').hide();
        } else {
            $mapImage.attr('src', '').hide();
            $mapImage.siblings('.dashicons').show();
        }

        var note = $.trim($('#gep_contact_note').val() || '');
        $('#gep-contact-preview-note').text(note);
        $('#gep-contact-preview-note-wrap').toggle(!!note);
        $('#gep-contact-preview-cta-title').text(contactValue('#gep_contact_cta_title', '¿Necesitas información sobre el laboratorio?'));
        $('#gep-contact-preview-cta-button').text(contactValue('#gep_contact_cta_button_text', 'Contactar al GEP'));
        renderContactRowsPreview();
    }

    function makeContactRow(data, $after){
        data = data || {};
        var rowKey = newKey('r', Date.now() + Math.floor(Math.random() * 1000));
        var html = $('#gep-contact-row-template').html().replaceAll('__R__', rowKey);
        var $row = $(html);
        if ($after && $after.length) $row.insertAfter($after); else $('#gep-contact-rows').append($row);
        $row.find('.gep-contact-label-input').val(data.label || '');
        $row.find('.gep-contact-value-input').val(data.value || '');
        $row.find('select[name*="[kind]"]').val(data.kind || 'text');
        $row.find('.gep-contact-summary-label').text(data.label || 'Nuevo dato');
        $row.find('.gep-contact-summary-value').text(data.value || '');
        return $row;
    }

    function getContactDefaults(){
        var node = document.getElementById('gep-contact-defaults');
        if (!node) return null;
        try { return JSON.parse(node.textContent || '{}'); } catch (e) { return null; }
    }

    function setContactDevice(device){
        var labels = {desktop:'Vista escritorio', tablet:'Vista tablet', mobile:'Vista móvil'};
        var $shell = $('#gep-contact-preview-shell');
        if (!$shell.length) return;
        $shell.removeClass('is-device-desktop is-device-tablet is-device-mobile').addClass('is-device-' + device);
        $('.gep-device-button').removeClass('is-active').filter('[data-device="' + device + '"]').addClass('is-active');
        $('#gep-preview-device-label').text(labels[device] || labels.desktop);
    }

    function clearFieldError($field){
        if (!$field || !$field.length) return;
        var $wrap = $field.closest('.gep-admin-field,label');
        $wrap.removeClass('is-invalid');
        $field.next('.gep-field-error').remove();
    }

    function setFieldError($field, message, show){
        if (!$field || !$field.length) return false;
        if (!message) { clearFieldError($field); return true; }
        if (show || $field.closest('.is-invalid').length) {
            var $wrap = $field.closest('.gep-admin-field,label');
            $wrap.addClass('is-invalid');
            $field.next('.gep-field-error').remove();
            $('<span class="gep-field-error"></span>').text(message).insertAfter($field);
        }
        return false;
    }

    function isValidEmail(value){
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function isValidHttpUrl(value){
        try {
            var parsed = new URL(value);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (e) { return false; }
    }

    function validateContact(showAll){
        if (!$('#gep-contact-preview-shell').length) return true;
        var valid = true;
        $('[data-gep-required="1"]').each(function(){
            var $field = $(this);
            var value = $.trim($field.val() || '');
            if (!value) valid = setFieldError($field, 'Este campo es obligatorio.', showAll) && valid;
            else clearFieldError($field);
        });

        var $email = $('#gep_contact_primary_email');
        var emailValue = $.trim($email.val() || '');
        if (emailValue && !isValidEmail(emailValue)) valid = setFieldError($email, 'Escribe un correo válido.', showAll) && valid;
        else if (emailValue) clearFieldError($email);

        var $mapUrl = $('#gep_contact_map_url');
        var mapUrlValue = $.trim($mapUrl.val() || '');
        if (mapUrlValue && !isValidHttpUrl(mapUrlValue)) valid = setFieldError($mapUrl, 'Usa una URL completa que empiece por http:// o https://.', showAll) && valid;
        else clearFieldError($mapUrl);

        $('#gep-contact-rows .gep-contact-row').each(function(){
            var $row = $(this);
            $row.removeClass('is-row-invalid');
            var $label = $row.find('.gep-contact-label-input');
            var $value = $row.find('.gep-contact-value-input');
            var label = $.trim($label.val() || '');
            var value = $.trim($value.val() || '');
            var kind = $row.find('select[name*="[kind]"]').val() || 'text';
            var rowValid = true;
            if (label || value) {
                if (!label) rowValid = setFieldError($label, 'Añade una etiqueta.', showAll) && rowValid; else clearFieldError($label);
                if (!value) rowValid = setFieldError($value, 'Añade el contenido.', showAll) && rowValid; else clearFieldError($value);
                if (value && kind === 'email' && !isValidEmail(value)) rowValid = setFieldError($value, 'Este dato debe ser un correo válido.', showAll) && rowValid;
                if (value && kind === 'url' && !isValidHttpUrl(value)) rowValid = setFieldError($value, 'Este dato debe ser una URL completa.', showAll) && rowValid;
            } else {
                clearFieldError($label); clearFieldError($value);
            }
            if (!rowValid) $row.addClass('is-row-invalid');
            valid = rowValid && valid;
        });
        return valid;
    }

    function showContactValidationError(){
        var $status = $('#gep-save-status');
        if ($status.length) {
            $status.removeClass('is-saved is-dirty').addClass('is-dirty');
            $status.find('.dashicons').attr('class','dashicons dashicons-dismiss');
            $status.find('strong').text('Revisa los campos');
            $status.find('small').text('Hay información obligatoria o formatos que corregir.');
        }
        $('#gep-contact-inline-status').removeClass('is-clean is-dirty').addClass('is-error').html('<span class="dashicons dashicons-dismiss"></span> Revisa los campos');
    }

    $(document).on('click', '.gep-device-button', function(){
        setContactDevice($(this).data('device') || 'desktop');
    });

    $(document).on('click', '.gep-add-contact-row', function(e){
        e.preventDefault();
        var $row = makeContactRow({}, null);
        updateContactEmptyState();
        updateContactPreview();
        highlightContactPreview('card');
        markDirty();
        $('html, body').animate({scrollTop: $row.offset().top - 110}, 250);
    });

    $('#gep-contact-rows').on('click', '.gep-duplicate-contact-row', function(e){
        e.preventDefault();
        var $source = $(this).closest('.gep-contact-row');
        var data = {
            label: $source.find('.gep-contact-label-input').val() || '',
            value: $source.find('.gep-contact-value-input').val() || '',
            kind: $source.find('select[name*="[kind]"]').val() || 'text'
        };
        var $row = makeContactRow(data, $source);
        updateContactEmptyState(); updateContactPreview(); highlightContactPreview('card'); markDirty();
        $row.attr('open', true);
    });

    $('#gep-contact-rows').on('click', '.gep-remove-contact-row', function(e){
        e.preventDefault();
        if (!window.confirm('¿Eliminar este dato de contacto?')) return;
        $(this).closest('.gep-contact-row').remove();
        updateContactEmptyState(); updateContactPreview(); highlightContactPreview('card'); markDirty();
    });

    $('#gep-contact-rows').on('click', '.gep-contact-move-up', function(e){
        e.preventDefault();
        var $row = $(this).closest('.gep-contact-row');
        var $prev = $row.prev('.gep-contact-row');
        if ($prev.length) { $row.insertBefore($prev); updateContactPreview(); highlightContactPreview('card'); markDirty(); }
    });

    $('#gep-contact-rows').on('click', '.gep-contact-move-down', function(e){
        e.preventDefault();
        var $row = $(this).closest('.gep-contact-row');
        var $next = $row.next('.gep-contact-row');
        if ($next.length) { $row.insertAfter($next); updateContactPreview(); highlightContactPreview('card'); markDirty(); }
    });

    $('#gep-contact-rows').on('input', '.gep-contact-label-input', function(){
        $(this).closest('.gep-contact-row').find('.gep-contact-summary-label').first().text($(this).val() || 'Nuevo dato');
        updateContactPreview(); validateContact(false);
    });
    $('#gep-contact-rows').on('input', '.gep-contact-value-input', function(){
        $(this).closest('.gep-contact-row').find('.gep-contact-summary-value').first().text($(this).val() || '');
        updateContactPreview(); validateContact(false);
    });
    $('#gep-contact-rows').on('change', 'select[name*="[kind]"]', function(){ updateContactPreview(); validateContact(false); });

    if ($('#gep-contact-rows').length && $.fn.sortable) {
        $('#gep-contact-rows').sortable({
            items: '> .gep-contact-row',
            handle: '.gep-grip',
            placeholder: 'gep-contact-sort-placeholder',
            forcePlaceholderSize: true,
            tolerance: 'pointer',
            stop: function(){ updateContactPreview(); highlightContactPreview('card'); markDirty(); }
        });
    }

    /* Apartados plegables unificados v8.6: mismo comportamiento visual de Inicio en todos los editores. */
    function setUniversalStepCollapsed($card, collapsed){
        if (!$card || !$card.length) return;
        $card.toggleClass('is-collapsed', !!collapsed);
        var $heading = $card.children('.gep-step-heading').first();
        $heading.attr('aria-expanded', collapsed ? 'false' : 'true');
    }

    function initUniversalCollapsibleSteps(){
        $('.gep-questionnaire').each(function(){
            var $questionnaire = $(this);
            var $allSteps = $questionnaire.children('.gep-step-card');
            if (!$allSteps.length) return;

            var $standardSteps = $allSteps.not('.gep-home-step');
            $standardSteps.each(function(index){
                var $card = $(this);
                var $heading = $card.children('.gep-step-heading').first();
                if (!$heading.length) return;

                $card.addClass('gep-universal-step');
                $heading.find('.gep-section-collapse').remove();
                $heading.attr({ role: 'button', tabindex: '0' });
                if (!$heading.find('.gep-universal-chevron').length) {
                    $heading.append('<span class="dashicons dashicons-arrow-down-alt2 gep-universal-chevron" aria-hidden="true"></span>');
                }
                if (!$card.children('.gep-universal-step-body').length) {
                    $heading.nextAll().wrapAll('<div class="gep-universal-step-body"></div>');
                }
                setUniversalStepCollapsed($card, true);
            });

            if ($allSteps.length > 1 && !$questionnaire.children('.gep-section-visibility-tools').length) {
                var $tools = $('<div class="gep-section-visibility-tools"><div><span class="dashicons dashicons-list-view"></span><strong>Apartados editables</strong><small>Abre solo la parte que necesitas o controla todas de una vez.</small></div><div class="gep-section-visibility-actions"><button type="button" class="button button-small gep-collapse-all-steps"><span class="dashicons dashicons-arrow-up-alt2"></span> Contraer todo</button><button type="button" class="button button-small gep-expand-all-steps"><span class="dashicons dashicons-arrow-down-alt2"></span> Expandir todo</button></div></div>');
                $allSteps.first().before($tools);
            }

            // Quita las reglas CSS de pre-inicialización únicamente cuando la
            // estructura definitiva ya está lista, evitando cualquier salto visual.
            $questionnaire.addClass('is-collapsible-initialized');
        });
    }

    $(document).on('click', '.gep-universal-step > .gep-step-heading', function(e){
        if ($(e.target).closest('button,a,input,textarea,select,label').length) return;
        var $card = $(this).closest('.gep-universal-step');
        previewFromElement($card);
        setUniversalStepCollapsed($card, !$card.hasClass('is-collapsed'));
    });
    $(document).on('keydown', '.gep-universal-step > .gep-step-heading', function(e){
        if (e.key !== 'Enter' && e.key !== ' ') return;
        if ($(e.target).is('button,a,input,textarea,select,label')) return;
        e.preventDefault();
        var $card = $(this).closest('.gep-universal-step');
        setUniversalStepCollapsed($card, !$card.hasClass('is-collapsed'));
    });
    $(document).on('click', '.gep-collapse-all-steps', function(e){
        e.preventDefault();
        var $q = $(this).closest('.gep-questionnaire');
        $q.children('.gep-home-step').prop('open', false);
        $q.children('.gep-universal-step').each(function(){ setUniversalStepCollapsed($(this), true); });$q.find('.gep-section-builder').each(function(){if(typeof directorySetSectionCollapsed==='function')directorySetSectionCollapsed($(this),true);});
    });
    $(document).on('click', '.gep-expand-all-steps', function(e){
        e.preventDefault();
        var $q = $(this).closest('.gep-questionnaire');
        $q.children('.gep-home-step').prop('open', true);
        $q.children('.gep-universal-step').each(function(){ setUniversalStepCollapsed($(this), false); });$q.find('.gep-section-builder').each(function(){if(typeof directorySetSectionCollapsed==='function')directorySetSectionCollapsed($(this),false);});
    });

    initUniversalCollapsibleSteps();

    function showDirectoryValidationError(){
        var $status=$('#gep-save-status');
        if($status.length){$status.removeClass('is-saved is-dirty').addClass('is-dirty');$status.find('.dashicons').attr('class','dashicons dashicons-dismiss');$status.find('strong').text('Revisa los campos');$status.find('small').text('Hay información obligatoria o formatos que corregir.');}
        $('#gep-directory-inline-status').removeClass('is-clean is-dirty').addClass('is-error').html('<span class="dashicons dashicons-dismiss"></span> Revisa los campos');
    }

    $(document).on('click', '.gep-restore-directory', function(e){
        e.preventDefault();
        if (!window.confirm('¿Descartar los cambios sin guardar y volver al último estado guardado de Quiénes somos?')) return;
        hasUnsavedChanges=false; isSubmitting=true; window.location.reload();
    });

    $form.on('input change', '[name^="gep_team_"],[name^="gep_directory_"],[name^="gep_sections"]', function(){ if($('#gep-directory-preview-shell').length) validateDirectory(false); });

    $form.on('submit.gepDirectory', function(e){
        if (!$('#gep-directory-preview-shell').length) return;
        if (!validateDirectory(true)) {
            e.preventDefault(); showDirectoryValidationError();
            var $first=$('.is-invalid').first(); if($first.length) $('html, body').animate({scrollTop:$first.offset().top-120},220);
            return false;
        }
        isSubmitting=true; hasUnsavedChanges=false;
        $('.gep-save-button').prop('disabled',true);
        $('#gep-directory-inline-status').removeClass('is-clean is-dirty is-error').addClass('is-clean').html('<span class="dashicons dashicons-update gep-spin"></span> Guardando…');
    });

    $(document).on('click', '.gep-restore-contact', function(e){
        e.preventDefault();
        var defaults = getContactDefaults();
        if (!defaults || !window.confirm('¿Restaurar el contenido original de Contacto? Los cambios no se publicarán hasta que pulses “Guardar cambios”.')) return;
        $.each(defaults.fields || {}, function(selector, value){ $(selector).val(value == null ? '' : value); });
        $('[name="gep_contact_map_image_url"]').val('');
        var $imageControl = $('[name="gep_contact_map_image_url"]').closest('.gep-image-control');
        $imageControl.find('.gep-image-preview').hide().find('img').attr('src','');
        $imageControl.find('.gep-remove-image').hide();
        $('#gep-contact-rows').empty();
        $.each(defaults.rows || [], function(_, row){ makeContactRow(row, null); });
        $('.gep-step-card[data-contact-preview]').each(function(){ setUniversalStepCollapsed($(this), false); });
        updateContactEmptyState(); updateContactPreview(); validateContact(false); highlightContactPreview('hero'); markDirty();
    });

    $form.on('input change', '[name^="gep_contact_"]', function(){ updateContactPreview(); validateContact(false); });
    $form.on('focusin click', '.gep-step-card[data-contact-preview] input,.gep-step-card[data-contact-preview] textarea,.gep-step-card[data-contact-preview] select,.gep-step-card[data-contact-preview] button,.gep-step-card[data-contact-preview] .gep-step-heading', function(){
        var section = $(this).closest('.gep-step-card').data('contact-preview');
        if (section) highlightContactPreview(section);
    });

    $form.on('submit.gepContact', function(e){
        if (!$('#gep-contact-preview-shell').length) return;
        if (!validateContact(true)) {
            e.preventDefault();
            showContactValidationError();
            var $first = $('.is-invalid').first();
            if ($first.length) $('html, body').animate({scrollTop: $first.offset().top - 120}, 220);
            return false;
        }
        isSubmitting = true;
        hasUnsavedChanges = false;
        $('.gep-save-button').prop('disabled', true).find('span:last-child');
        $('#gep-contact-inline-status').removeClass('is-clean is-dirty is-error').addClass('is-clean').html('<span class="dashicons dashicons-update gep-spin"></span> Guardando…');
    });


    function docenciaDefaults(){
        var node=document.getElementById('gep-docencia-defaults');
        if(!node) return null;
        try{return JSON.parse(node.textContent||'{}');}catch(e){return null;}
    }
    function docField(selector,fallback){var $f=$(selector).first();if(!$f.length)return fallback||'';var v=$.trim($f.val()||'');return v||(fallback||'');}
    function docenciaFallbackImage(group,index){
        // v9.20.0: la página pública no pone fotos de reemplazo en los cursos.
        return '';
        var sets={
            pre:['assets/mapa-chile.png','assets/field/mosaico-rural-satelital.jpg','assets/cobertura-suelo.jpg'],
            post:['assets/field/bosque-araucarias.jpg','assets/mapbiomas-chile.jpg','assets/field/valle-rural.jpg','assets/urbano-agropecuario.jpg'],
            diploma:['assets/hero-santiago.jpg','assets/field/paisaje-carretera.jpg'],
            workshop:['assets/cobertura-suelo.jpg','assets/field/mosaico-rural-satelital.jpg']
        };
        var list=sets[group]||sets.pre;
        return list[(index||0)%list.length];
    }
    function setDocenciaDevice(device){
        var labels={desktop:'Vista escritorio',tablet:'Vista tablet',mobile:'Vista móvil'};
        var $shell=$('#gep-docencia-preview-shell'); if(!$shell.length)return;
        $shell.removeClass('is-device-desktop is-device-tablet is-device-mobile').addClass('is-device-'+device);
        $('.gep-docencia-device-button').removeClass('is-active').filter('[data-device="'+device+'"]').addClass('is-active');
        $('#gep-docencia-device-label').text(labels[device]||labels.desktop);
    }
    function highlightDocencia(section){
        var $shell=$('#gep-docencia-preview-shell');if(!$shell.length)return;
        $shell.find('[data-docencia-live]').removeClass('is-live-active');
        var $target=$shell.find('[data-docencia-live="'+section+'"]').addClass('is-live-active').first();
        $('.gep-step-card[data-docencia-preview]').removeClass('is-preview-source');
        var $step=$('.gep-step-card[data-docencia-preview="'+section+'"]').addClass('is-preview-source').first();
        gepSyncParentPreview($step,'#gep-docencia-preview-zone-number','#gep-docencia-preview-heading-title','#gep-docencia-preview-heading-text','Docencia','Selecciona un bloque del editor para revisarlo aquí.');
        gepScrollPreviewStage($('.gep-docencia-device-stage'),$target);
    }
    function updateDocenciaEmpty(){
        $('#gep-docencia-learning-empty').toggleClass('is-hidden',$('#gep-docencia-learning-steps .gep-docencia-step-row').length>0);
        $('#gep-docencia-pre-empty').toggleClass('is-hidden',$('#gep-docencia-pre-courses .gep-docencia-course-row').length>0);
        $('#gep-docencia-post-empty').toggleClass('is-hidden',$('#gep-docencia-post-courses .gep-docencia-course-row').length>0);
        $('#gep-docencia-diploma-empty').toggleClass('is-hidden',$('#gep-docencia-diploma-courses .gep-docencia-course-row').length>0);
        $('#gep-docencia-workshop-empty').toggleClass('is-hidden',$('#gep-docencia-workshop-courses .gep-docencia-course-row').length>0);
    }
    function updateDocenciaSummaries(){
        $('.gep-docencia-step-row').each(function(){var $r=$(this);$r.find('.gep-docencia-step-number-summary').text($.trim($r.find('.gep-docencia-step-number').val()||'')||'—');$r.find('.gep-docencia-step-title-summary').text($.trim($r.find('.gep-docencia-step-title').val()||'')||'Nuevo paso');});
        $('.gep-docencia-course-row').each(function(){var $r=$(this);$r.find('.gep-docencia-course-code-summary').text($.trim($r.find('.gep-docencia-course-code').val()||'')||'CURSO');$r.find('.gep-docencia-course-title-summary').text($.trim($r.find('.gep-docencia-course-title').val()||'')||'Nuevo curso');});
    }
    function scheduleDocenciaPreview(){ clearTimeout(docenciaPreviewTimer); docenciaPreviewTimer=setTimeout(updateDocenciaPreview,90); }
    function updateDocenciaPreview(){
        if(!$('#gep-docencia-preview-shell').length)return;
        $('#gep-doc-prev-hero-eyebrow').text(docField('#gep_docencia_hero_eyebrow','Formación'));
        $('#gep-doc-prev-hero-title').text(docField('#gep_docencia_hero_title','Docencia'));
        $('#gep-doc-prev-hero-text').text(docField('#gep_docencia_hero_text','Cursos y programas vinculados a geomática y territorio.'));
        gepPreviewSetHeroMedia(
            $('#gep-docencia-preview-shell [data-docencia-live="hero"]'),
            $('[name="gep_docencia_hero_image"]').val() || '',
            $('[name="gep_docencia_hero_video"]').val() || '',
            '#gep-docencia-preview-shell',
            'assets/field/bosque-ladera.jpg'
        );
        $('#gep-doc-prev-nav-pre').text(docField('#gep_docencia_nav_pregrado','Pregrado'));
        $('#gep-doc-prev-nav-post').text(docField('#gep_docencia_nav_postgrado','Postgrado'));
        $('#gep-doc-prev-nav-fields').text(docField('#gep_docencia_nav_campos','Campos de formación'));
        var $steps=$('#gep-doc-prev-learning').empty();
        $('#gep-docencia-learning-steps .gep-docencia-step-row').each(function(){var $r=$(this),n=$.trim($r.find('[name$="[number]"]').val()||''),t=$.trim($r.find('[name$="[title]"]').val()||''),d=$.trim($r.find('[name$="[text]"]').val()||'');var $card=$('<div class="gep-docencia-preview-step"><b></b><strong></strong><p></p></div>');$card.find('b').text(n||'—');$card.find('strong').text(t||'Nuevo paso');$card.find('p').text(d||'Descripción del paso.');$steps.append($card);});
        $('#gep-doc-prev-pre-eyebrow').text(docField('#gep_docencia_pre_eyebrow','Pregrado')); $('#gep-doc-prev-pre-title').text(docField('#gep_docencia_pre_title','Bases para leer y representar el territorio.')); $('#gep-doc-prev-pre-text').text(docField('#gep_docencia_pre_text','Cursos asociados al laboratorio.'));
        $('#gep-doc-prev-post-eyebrow').text(docField('#gep_docencia_post_eyebrow','Postgrado')); $('#gep-doc-prev-post-title').text(docField('#gep_docencia_post_title','Profundización y herramientas aplicadas.')); $('#gep-doc-prev-post-text').text(docField('#gep_docencia_post_text','Cursos avanzados y programas asociados.'));
        function drawCourses(container,root,group){
            var $c=$(container).empty(),idx=0;
            $(root+' .gep-docencia-course-row').each(function(){
                var $r=$(this),
                    code=$.trim($r.find('[name$="[code]"]').val()||''),
                    title=$.trim($r.find('[name$="[title]"]').val()||''),
                    status=$.trim($r.find('[name$="[status]"]').val()||''),
                    btn=$.trim($r.find('[name$="[button]"]').val()||''),
                    image=$.trim($r.find('[name$="[image]"]').val()||''),
                    resolved=gepPreviewResolveImage(image,'#gep-docencia-preview-shell',docenciaFallbackImage(group,idx++));
                var $row=$('<div class="gep-docencia-preview-course"><span class="gep-docencia-preview-course-media"><img alt=""></span><b></b><div><strong></strong><small></small></div><em></em></div>');
                if(resolved)$row.find('.gep-docencia-preview-course-media img').attr('src',resolved);else $row.addClass('has-no-image').find('.gep-docencia-preview-course-media').remove();
                $row.find('b').text(code||'CURSO');
                $row.find('strong').text(title||'Nuevo curso');
                $row.find('small').text(status||'');
                $row.find('em').text(btn||'');
                $c.append($row);
            });
            if(!idx)$c.append($('<div class="gep-docencia-preview-empty"></div>').text('Aún no hay registros en esta categoría.'));
        }
        drawCourses('#gep-doc-prev-pre-courses','#gep-docencia-pre-courses','pre'); drawCourses('#gep-doc-prev-post-courses','#gep-docencia-post-courses','post');
        // v9.20.0: Diplomado y Cursos y talleres también se ven en la preview, como en la página.
        $('#gep-doc-prev-nav-diploma').text(docField('#gep_docencia_nav_diplomado','Diplomado')); $('#gep-doc-prev-nav-workshop').text(docField('#gep_docencia_nav_cursos','Cursos y talleres'));
        $('#gep-doc-prev-diploma-eyebrow').text(docField('#gep_docencia_diploma_eyebrow','Diplomado')); $('#gep-doc-prev-diploma-title').text(docField('#gep_docencia_diploma_title','Formación profesional especializada.')); $('#gep-doc-prev-diploma-text').text(docField('#gep_docencia_diploma_text',''));
        $('#gep-doc-prev-workshop-eyebrow').text(docField('#gep_docencia_workshop_eyebrow','Cursos y talleres')); $('#gep-doc-prev-workshop-title').text(docField('#gep_docencia_workshop_title','Formación breve y actividades aplicadas.')); $('#gep-doc-prev-workshop-text').text(docField('#gep_docencia_workshop_text',''));
        drawCourses('#gep-doc-prev-diploma-courses','#gep-docencia-diploma-courses','diploma'); drawCourses('#gep-doc-prev-workshop-courses','#gep-docencia-workshop-courses','workshop');
        $('#gep-doc-prev-note').text(docField('#gep_docencia_note',''));
        $('#gep-doc-prev-fields-eyebrow').text(docField('#gep_docencia_fields_eyebrow','Campos de formación')); $('#gep-doc-prev-fields-title').text(docField('#gep_docencia_fields_title','Cartografía, SIG, teledetección, geomática y ecología del paisaje.')); $('#gep-doc-prev-fields-text').text(docField('#gep_docencia_fields_text','La docencia histórica del GEP conecta herramientas geográficas con análisis territorial.'));
        var image=gepPreviewResolveImage($('[name="gep_docencia_fields_image_url"]').val()||'','#gep-docencia-preview-shell',''),$wrap=$('#gep-doc-prev-fields-image').closest('.gep-docencia-preview-image');if(image){$('#gep-doc-prev-fields-image').attr('src',image);$wrap.addClass('has-image');}else{$('#gep-doc-prev-fields-image').attr('src','');$wrap.removeClass('has-image');}
        updateDocenciaSummaries();
    }
    function initDocenciaSortables(){
        $('#gep-docencia-learning-steps,#gep-docencia-pre-courses,#gep-docencia-post-courses,#gep-docencia-diploma-courses,#gep-docencia-workshop-courses').each(function(){var $el=$(this);if($el.data('gep-doc-sortable'))return;$el.data('gep-doc-sortable',true).sortable({items:'> .gep-item-row',handle:'.gep-grip',placeholder:'gep-docencia-sort-placeholder',forcePlaceholderSize:true,tolerance:'pointer',stop:function(){updateDocenciaPreview();updateDocenciaEmpty();markDirty();}});});
    }
    function makeDocenciaStep(data){var key=newKey('ds',++docenciaStepCounter),html=$('#gep-docencia-step-template').html().replaceAll('__K__',key);$('#gep-docencia-learning-steps').append(html);var $r=$('#gep-docencia-learning-steps .gep-docencia-step-row').last();data=data||{};$r.find('[name$="[number]"]').val(data.number||'');$r.find('[name$="[title]"]').val(data.title||'');$r.find('[name$="[text]"]').val(data.text||'');return $r;}
    function makeDocenciaCourse(group,data){var key=newKey('dc',++docenciaCourseCounter),html=$('#gep-docencia-course-template').html().replaceAll('__G__',group).replaceAll('__K__',key),roots={pre:'#gep-docencia-pre-courses',post:'#gep-docencia-post-courses',diploma:'#gep-docencia-diploma-courses',workshop:'#gep-docencia-workshop-courses'},$root=$(roots[group]||roots.pre);$root.append(html);var $r=$root.find('.gep-docencia-course-row').last();data=data||{};$r.find('[name$="[code]"]').val(data.code||'');$r.find('[name$="[title]"]').val(data.title||'');$r.find('[name$="[status]"]').val(data.status||'');$r.find('[name$="[button]"]').val(data.button||'Abrir ↗');$r.find('[name$="[url]"]').val(data.url||'');$r.find('[name$="[image]"]').val(data.image||'');$r.find('[name$="[image_alt]"]').val(data.image_alt||'');if(data.image){$r.find('.gep-image-preview').show().find('img').attr('src',data.image);$r.find('.gep-remove-image').show();}return $r;}
    function docenciaStepData($r){return{number:$r.find('[name$="[number]"]').val()||'',title:$r.find('[name$="[title]"]').val()||'',text:$r.find('[name$="[text]"]').val()||''};}
    function docenciaCourseData($r){return{code:$r.find('[name$="[code]"]').val()||'',title:$r.find('[name$="[title]"]').val()||'',status:$r.find('[name$="[status]"]').val()||'',button:$r.find('[name$="[button]"]').val()||'',url:$r.find('[name$="[url]"]').val()||'',image:$r.find('[name$="[image]"]').val()||'',image_alt:$r.find('[name$="[image_alt]"]').val()||''};}
    $(document).on('click','.gep-docencia-device-button',function(e){e.preventDefault();setDocenciaDevice($(this).data('device')||'desktop');});
    $(document).on('click','.gep-docencia-add-step',function(e){e.preventDefault();var $r=makeDocenciaStep({number:String($('#gep-docencia-learning-steps .gep-docencia-step-row').length+1).padStart(2,'0')});updateDocenciaEmpty();updateDocenciaPreview();highlightDocencia('learning');markDirty();$r.attr('open',true);});
    $(document).on('click','.gep-docencia-add-course',function(e){e.preventDefault();var group=$(this).data('group')||'pre',roots={pre:'#gep-docencia-pre-courses',post:'#gep-docencia-post-courses',diploma:'#gep-docencia-diploma-courses',workshop:'#gep-docencia-workshop-courses'},prefix={pre:'PRE ',post:'POST ',diploma:'DIP ',workshop:'TALL '},count=$(roots[group]||roots.pre).find('.gep-docencia-course-row').length+1;var $r=makeDocenciaCourse(group,{code:(prefix[group]||'CUR ')+String(count).padStart(2,'0')});updateDocenciaEmpty();updateDocenciaPreview();highlightDocencia(group==='pre'?'pregrado':(group==='post'?'postgrado':group));markDirty();$r.attr('open',true);});
    $(document).on('click','.gep-docencia-remove-step,.gep-docencia-remove-course',function(e){e.preventDefault();if(!window.confirm('¿Eliminar este elemento?'))return;$(this).closest('.gep-item-row').remove();updateDocenciaEmpty();updateDocenciaPreview();markDirty();});
    $(document).on('click','.gep-docencia-duplicate-step',function(e){e.preventDefault();var $src=$(this).closest('.gep-docencia-step-row'),$r=makeDocenciaStep(docenciaStepData($src));$r.insertAfter($src);updateDocenciaPreview();updateDocenciaEmpty();markDirty();});
    $(document).on('click','.gep-docencia-duplicate-course',function(e){e.preventDefault();var $src=$(this).closest('.gep-docencia-course-row'),group=$src.data('docencia-group')||'pre',$r=makeDocenciaCourse(group,docenciaCourseData($src));$r.insertAfter($src);updateDocenciaPreview();updateDocenciaEmpty();markDirty();});
    $(document).on('click','.gep-docencia-move-up',function(e){e.preventDefault();var $r=$(this).closest('.gep-item-row'),$p=$r.prev('.gep-item-row');if($p.length){$r.insertBefore($p);updateDocenciaPreview();markDirty();}});
    $(document).on('click','.gep-docencia-move-down',function(e){e.preventDefault();var $r=$(this).closest('.gep-item-row'),$n=$r.next('.gep-item-row');if($n.length){$r.insertAfter($n);updateDocenciaPreview();markDirty();}});
    function validateDocencia(showAll){
        if(!$('#gep-docencia-preview-shell').length)return true;var valid=true;
        $('[data-gep-docencia-required="1"]').each(function(){var $f=$(this),v=$.trim($f.val()||'');if(!v)valid=setFieldError($f,'Este campo es obligatorio.',showAll)&&valid;else clearFieldError($f);});
        $('.gep-docencia-course-url').each(function(){var $f=$(this),v=$.trim($f.val()||'');if(v&&!isValidHttpUrl(v))valid=setFieldError($f,'Escribe una URL completa (http:// o https://).',showAll)&&valid;else clearFieldError($f);});return valid;
    }
    function showDocenciaValidationError(){var $status=$('#gep-save-status');if($status.length){$status.removeClass('is-saved is-dirty').addClass('is-dirty');$status.find('.dashicons').attr('class','dashicons dashicons-dismiss');$status.find('strong').text('Revisa los campos');$status.find('small').text('Hay información obligatoria o enlaces que corregir.');}$('#gep-docencia-inline-status').removeClass('is-clean is-dirty').addClass('is-error').html('<span class="dashicons dashicons-dismiss"></span> Revisa los campos');}
    $(document).on('click','.gep-restore-docencia',function(e){e.preventDefault();var d=docenciaDefaults();if(!d||!window.confirm('¿Restaurar el contenido original de Docencia? Los cambios no se publicarán hasta guardar.'))return;var map={hero_eyebrow:'#gep_docencia_hero_eyebrow',hero_title:'#gep_docencia_hero_title',hero_text:'#gep_docencia_hero_text',nav_pregrado:'#gep_docencia_nav_pregrado',nav_postgrado:'#gep_docencia_nav_postgrado',nav_diplomado:'#gep_docencia_nav_diplomado',nav_cursos:'#gep_docencia_nav_cursos',nav_campos:'#gep_docencia_nav_campos',pre_eyebrow:'#gep_docencia_pre_eyebrow',pre_title:'#gep_docencia_pre_title',pre_text:'#gep_docencia_pre_text',post_eyebrow:'#gep_docencia_post_eyebrow',post_title:'#gep_docencia_post_title',post_text:'#gep_docencia_post_text',diploma_eyebrow:'#gep_docencia_diploma_eyebrow',diploma_title:'#gep_docencia_diploma_title',diploma_text:'#gep_docencia_diploma_text',workshop_eyebrow:'#gep_docencia_workshop_eyebrow',workshop_title:'#gep_docencia_workshop_title',workshop_text:'#gep_docencia_workshop_text',note:'#gep_docencia_note',fields_eyebrow:'#gep_docencia_fields_eyebrow',fields_title:'#gep_docencia_fields_title',fields_text:'#gep_docencia_fields_text',fields_image_alt:'#gep_docencia_fields_image_alt'};$.each(map,function(k,sel){$(sel).val(d[k]||'');});$('[name="gep_docencia_fields_image_url"]').val('');var $ic=$('[name="gep_docencia_fields_image_url"]').closest('.gep-image-control');$ic.find('.gep-image-preview').hide().find('img').attr('src','');$ic.find('.gep-remove-image').hide();$('#gep-docencia-learning-steps,#gep-docencia-pre-courses,#gep-docencia-post-courses,#gep-docencia-diploma-courses,#gep-docencia-workshop-courses').empty();$.each(d.learning_steps||[],function(_,row){makeDocenciaStep(row);});$.each(d.pre_courses||[],function(_,row){makeDocenciaCourse('pre',row);});$.each(d.post_courses||[],function(_,row){makeDocenciaCourse('post',row);});$.each(d.diploma_courses||[],function(_,row){makeDocenciaCourse('diploma',row);});$.each(d.workshop_courses||[],function(_,row){makeDocenciaCourse('workshop',row);});updateDocenciaEmpty();updateDocenciaPreview();validateDocencia(false);initDocenciaSortables();highlightDocencia('hero');markDirty();});
    $form.on('input change','[name^="gep_docencia_"]',function(){scheduleDocenciaPreview();validateDocencia(false);});
    $form.on('focusin click','.gep-step-card[data-docencia-preview] input,.gep-step-card[data-docencia-preview] textarea,.gep-step-card[data-docencia-preview] button,.gep-step-card[data-docencia-preview] .gep-step-heading,.gep-step-card[data-docencia-preview] summary',function(){var section=$(this).closest('.gep-step-card').data('docencia-preview');if(section)highlightDocencia(section);});
    $form.on('submit.gepDocencia',function(e){if(!$('#gep-docencia-preview-shell').length)return;if(!validateDocencia(true)){e.preventDefault();showDocenciaValidationError();var $first=$('.is-invalid').first();if($first.length)$('html, body').animate({scrollTop:$first.offset().top-120},220);return false;}isSubmitting=true;hasUnsavedChanges=false;$('.gep-save-button').prop('disabled',true);$('#gep-docencia-inline-status').removeClass('is-clean is-dirty is-error').addClass('is-clean').html('<span class="dashicons dashicons-update gep-spin"></span> Guardando…');});


    /* Proyectos v9.8.1: trayectoria compacta + ficha y galería administrables */
    var projectCounter=2000, projectStatusFilter='all', projectPreviewEra='all', projectData=[], projectActiveKey=null, projectPreviewTimer=null;
    function projectDefaults(){try{return JSON.parse($('#gep-project-defaults-json').text()||'{}');}catch(e){return {};}}
    function projectInitialRecords(){try{var d=JSON.parse($('#gep-project-records-json').text()||'[]');return $.isArray(d)?d:[];}catch(e){return[];}}
    function pfield(sel,fallback){var v=$.trim($(sel).val()||'');return v||fallback||'';}
    function setProjectDevice(device){var labels={desktop:'Vista escritorio',tablet:'Vista tablet',mobile:'Vista móvil'},$p=$('#gep-project-live-preview');if(!$p.length)return;$p.removeClass('is-device-desktop is-device-tablet is-device-mobile').addClass('is-device-'+device);$('.gep-project-device-button').removeClass('is-active').filter('[data-device="'+device+'"]').addClass('is-active');$('#gep-project-device-label').text(labels[device]||labels.desktop);}
    function highlightProject(section){if(!$('#gep-project-preview-shell').length)return;$('[data-project-live]').removeClass('is-live-active');var $target=$('[data-project-live="'+section+'"]').addClass('is-live-active').first();$('.gep-step-card[data-project-preview]').removeClass('is-preview-source');var $step=$('.gep-step-card[data-project-preview="'+section+'"]').addClass('is-preview-source').first();gepSyncParentPreview($step,'#gep-project-preview-zone-number','#gep-project-preview-heading-title','#gep-project-preview-heading-text','Proyectos','La preview se actualiza mientras editas.');gepScrollPreviewStage($('.gep-project-device-stage'),$target);}
    function projectResolveImage(url){url=$.trim(url||'');if(!url)return'';if(/^https?:\/\//i.test(url)||/^data:/i.test(url))return url;var base=$('#gep-project-preview-shell').data('public-url')||window.location.href;try{return new URL(url,base).href;}catch(e){return url;}}
    function projectFeatureData($r){return{image:$r.find('[name$="[image]"]').val()||'',alt:$r.find('[name$="[alt]"]').val()||'',kicker:$r.find('[name$="[kicker]"]').val()||'',title:$r.find('[name$="[title]"]').val()||'',text:$r.find('[name$="[text]"]').val()||''};}
    function projectAreaData($r){return{icon:$r.find('[name$="[icon]"]').val()||'',title:$r.find('[name$="[title]"]').val()||'',text:$r.find('[name$="[text]"]').val()||''};}
    function projectSlugify(value){return String(value||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');}
    function projectNormalizeRecord(data,key){var d=data||{},explicitSlug=$.trim(d.slug||''),autoSlug=projectSlugify((d.title||'')+'-'+(d.year||''));return{_key:String(key||newKey('pr',++projectCounter)),_slugAuto:!explicitSlug,slug:explicitSlug||autoSlug,status:d.status||'cerrado',year:d.year||'',era:d.era||'',theme:d.theme||'',title:d.title||'',text:d.text||'',url:d.url||'',button:d.button||'',tag:d.tag||'',image:d.image||'',image_alt:d.image_alt||'',details:d.details||'',institution:d.institution||'',lead:d.lead||'',gallery:d.gallery||'',detail_url:d.detail_url||''};}
    function projectGalleryUrls(raw){var out=[];String(raw||'').split(/\r?\n/).forEach(function(v){v=$.trim(v);if(v&&out.indexOf(v)<0)out.push(v);});return out;}
    function projectRenderGallery(d){var $list=$('#gep-project-single-editor .gep-project-gallery-list').empty(),urls=projectGalleryUrls(d&&d.gallery||'');$('#gep-project-single-editor .gep-project-gallery-empty').toggle(urls.length===0);$.each(urls,function(_,url){var main=$.trim(d.image||'')===$.trim(url),$item=$('<div class="gep-project-gallery-item"><span class="gep-project-gallery-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><img alt=""><div class="gep-project-gallery-item-copy"><strong></strong><small></small></div><div class="gep-project-gallery-item-actions"><button type="button" class="button button-small gep-project-gallery-main"></button><button type="button" class="button-link-delete gep-project-gallery-remove">Quitar</button></div></div>');$item.attr('data-url',url);$item.find('img').attr('src',url);$item.find('strong').text(main?'Imagen principal':'Imagen de galería');$item.find('small').text(url);$item.find('.gep-project-gallery-main').text(main?'Principal ✓':'Usar como principal').prop('disabled',main);$list.append($item);});projectInitGallerySortable();}
    function projectSyncGallery(urls,render){var d=projectRecordByKey(projectActiveKey);if(!d)return;d.gallery=(urls||[]).join('\n');$('#gep-project-single-editor [data-gep-project-editor-field="gallery"]').val(d.gallery);if(render!==false)projectRenderGallery(d);projectSchedulePreview();markDirty();}
    function projectInitGallerySortable(){var $list=$('#gep-project-single-editor .gep-project-gallery-list');if(!$list.length||$list.data('gep-gallery-sortable'))return;$list.data('gep-gallery-sortable',true).sortable({items:'> .gep-project-gallery-item',handle:'.gep-project-gallery-grip',placeholder:'gep-project-gallery-placeholder',forcePlaceholderSize:true,tolerance:'pointer',stop:function(){var urls=[];$list.children('.gep-project-gallery-item').each(function(){urls.push($(this).attr('data-url')||'');});projectSyncGallery(urls,false);}});}
    function projectInitData(rows){projectData=[];$.each(rows||[],function(i,d){projectData.push(projectNormalizeRecord(d,String(i)));});}
    function projectRecords(){return projectData;}
    function projectRecordByKey(key){key=String(key);for(var i=0;i<projectData.length;i++)if(String(projectData[i]._key)===key)return projectData[i];return null;}
    function projectIndexByKey(key){key=String(key);for(var i=0;i<projectData.length;i++)if(String(projectData[i]._key)===key)return i;return-1;}
    function projectSummary(d){return $.trim(d.title||'')||'Nuevo proyecto';}
    function projectListRow(d){var $r=$('<div class="gep-project-record-list-row"><span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><button type="button" class="gep-project-record-open"><span class="gep-item-summary-badge gep-project-year-summary"></span><strong class="gep-project-title-summary"></strong><span class="gep-project-theme-summary"></span><span class="gep-project-dup-badge" hidden>Posible duplicado</span></button><button type="button" class="gep-project-status-pill" title=""></button><span class="dashicons dashicons-arrow-right-alt2 gep-project-record-arrow"></span></div>');$r.attr('data-project-key',d._key);projectUpdateListRowElement($r,d);return $r;}
    function projectUpdateListRowElement($r,d){$r.find('.gep-project-year-summary').text(d.year||'AÑO');$r.find('.gep-project-title-summary').text(projectSummary(d));$r.find('.gep-project-theme-summary').text(d.theme||'');/* v9.26.0: estado visible y cambiable desde la lista */var cur=d.status==='vigente';$r.toggleClass('is-status-vigente',cur).toggleClass('is-status-cerrado',!cur);$r.find('.gep-project-status-pill').text(cur?'Vigente':'Trayectoria').attr('title',cur?'Vigente: aparece en «Proyectos vigentes». Pulsa para pasarlo a la trayectoria (finalizado).':'Finalizado: aparece en «Trayectoria». Pulsa para marcarlo como vigente.').attr('aria-label',(cur?'Estado: vigente. Pasar a trayectoria':'Estado: finalizado. Marcar como vigente'));$r.toggleClass('is-active',String(d._key)===String(projectActiveKey));}
    function projectUpdateListRow(key){var d=projectRecordByKey(key);if(!d)return;$('#gep-project-records .gep-project-record-list-row').each(function(){if(String($(this).attr('data-project-key'))===String(key)){projectUpdateListRowElement($(this),d);return false;}});$('#gep-project-active-summary').text(projectSummary(d));}
    function renderProjectList(){var $root=$('#gep-project-records'),$ed=$('#gep-project-single-editor');if($ed.length)$ed.addClass('is-hidden').appendTo('#gep-project-record-editor-parking');$root.empty();var frag=document.createDocumentFragment();$.each(projectData,function(_,d){frag.appendChild(projectListRow(d)[0]);});if($root[0])$root[0].appendChild(frag);if($root.data('gep-project-sortable')){try{$root.sortable('refresh');}catch(e){}}updateProjectEmpty();updateProjectEditorSearch();if(projectActiveKey!==null&&projectRecordByKey(projectActiveKey))projectOpenEditor(projectActiveKey);}
    function projectPopulateEditor(d){if(!d)return;$('#gep-project-single-editor [data-gep-project-editor-field]').each(function(){var $f=$(this),k=$f.data('gep-project-editor-field');$f.val(d[k]||'');});var $ic=$('#gep-project-single-editor [data-gep-project-editor-field="image"]').closest('.gep-image-control'),img=d.image||'';$ic.find('.gep-image-preview').toggle(!!img).find('img').attr('src',img);$ic.find('.gep-remove-image').toggle(!!img);$('#gep-project-active-summary').text(projectSummary(d));projectRenderGallery(d);}
    function projectOpenEditor(key){var d=projectRecordByKey(key);if(!d)return;projectActiveKey=String(d._key);projectPopulateEditor(d);var $row=$('#gep-project-records .gep-project-record-list-row').removeClass('is-active').filter('[data-project-key="'+projectActiveKey.replace(/"/g,'\\"')+'"]').addClass('is-active'),$ed=$('#gep-project-single-editor').removeClass('is-hidden');if($row.length)$ed.insertAfter($row);validateProjectActive(false);highlightProject('records');}
    function projectCloseEditor(){projectActiveKey=null;$('#gep-project-single-editor').addClass('is-hidden').appendTo('#gep-project-record-editor-parking');$('#gep-project-records .gep-project-record-list-row').removeClass('is-active');}
    function projectSchedulePreview(){clearTimeout(projectPreviewTimer);projectPreviewTimer=setTimeout(updateProjectPreview,160);}
    function makeProjectFeature(data){var key=newKey('pf',++projectCounter),html=$('#gep-project-feature-template').html().replaceAll('__K__',key);$('#gep-project-features').append(html);var $r=$('#gep-project-features .gep-project-feature-row').last(),d=data||{};$r.find('[name$="[image]"]').val(d.image||'');$r.find('[name$="[alt]"]').val(d.alt||'');$r.find('[name$="[kicker]"]').val(d.kicker||'');$r.find('[name$="[title]"]').val(d.title||'');$r.find('[name$="[text]"]').val(d.text||'');if(d.image){$r.find('.gep-image-preview').show().find('img').attr('src',projectResolveImage(d.image));$r.find('.gep-remove-image').show();}return $r;}
    function makeProjectArea(data){var key=newKey('pa',++projectCounter),html=$('#gep-project-area-template').html().replaceAll('__K__',key);$('#gep-project-areas').append(html);var $r=$('#gep-project-areas .gep-project-area-row').last(),d=data||{};$.each(['icon','title','text'],function(_,k){$r.find('[name$="['+k+']"]').val(d[k]||'');});return $r;}
    function projectCreate(data,afterKey){var d=projectNormalizeRecord(data,newKey('pr',++projectCounter)),idx=afterKey===undefined?projectData.length:projectIndexByKey(afterKey)+1;if(idx<0)idx=projectData.length;projectData.splice(idx,0,d);renderProjectList();projectOpenEditor(d._key);initProjectSortables();return d;}
    function updateProjectEmpty(){$('#gep-project-features-empty').toggleClass('is-hidden',$('#gep-project-features .gep-project-feature-row').length>0);$('#gep-project-records-empty').toggleClass('is-hidden',projectData.length>0);$('#gep-project-areas-empty').toggleClass('is-hidden',$('#gep-project-areas .gep-project-area-row').length>0);if(!projectData.length)projectCloseEditor();}
    function updateProjectSummaries(){$('.gep-project-feature-row').each(function(){var $r=$(this);$r.find('.gep-project-feature-title-summary').text($.trim($r.find('[name$="[title]"]').val()||'')||'Nuevo destacado');});$('.gep-project-area-row').each(function(){var $r=$(this);$r.find('.gep-project-area-icon-summary').text($.trim($r.find('[name$="[icon]"]').val()||'')||'＋');$r.find('.gep-project-area-title-summary').text($.trim($r.find('[name$="[title]"]').val()||'')||'Nueva área');});}
    function eraLabel(v){return String(v||'').replace(/-/g,'–');}
    function updateProjectPreviewRecords(){
        var rows=projectData,current=[],closed=[],q=$.trim($('#gep-proj-preview-search').val()||'').toLowerCase(),$pills=$('#gep-proj-preview-pills').empty(),eras=[];
        $.each(rows,function(_,r){if(r.status==='vigente')current.push(r);else closed.push(r);});
        var $current=$('#gep-proj-prev-features').empty();
        $.each(current,function(_,r){
            var $c=$('<div class="gep-project-preview-feature"><div class="gep-project-preview-feature-media"></div><div class="gep-project-preview-feature-copy"><b></b><strong></strong><p></p></div></div>'),u=projectResolveImage(r.image||'');
            if(u)$c.find('.gep-project-preview-feature-media').append($('<img alt="">').attr('src',u));else $c.find('.gep-project-preview-feature-media').addClass('is-empty');
            $c.find('b').text('Vigente'+(r.year?' · '+r.year:''));
            $c.find('strong').text(r.title||'Nuevo proyecto');
            $c.find('p').text(r.text||r.institution||'');
            $current.append($c);
        });
        if(!current.length)$current.append('<div class="gep-project-preview-current-empty">No hay proyectos marcados como vigentes.</div>');
        $.each(closed,function(_,r){var e=$.trim(r.era||'');if(e&&eras.indexOf(e)<0)eras.push(e);});
        $('<button type="button" class="gep-project-preview-pill"></button>').attr('data-era','all').toggleClass('is-active',projectPreviewEra==='all').text('Todos').appendTo($pills);
        $.each(eras,function(_,e){$('<button type="button" class="gep-project-preview-pill"></button>').attr('data-era',e).toggleClass('is-active',projectPreviewEra===e).text(eraLabel(e)).appendTo($pills);});
        if(projectPreviewEra!=='all'&&eras.indexOf(projectPreviewEra)<0)projectPreviewEra='all';
        var $out=$('#gep-proj-preview-records').empty(),shown=0;
        $.each(closed,function(_,r){
            var hay=[r.year,r.era,r.theme,r.title,r.text,r.tag,r.institution,r.lead].join(' ').toLowerCase();
            if(projectPreviewEra!=='all'&&r.era!==projectPreviewEra)return;
            if(q&&hay.indexOf(q)<0)return;
            shown++;
            var $row=$('<div class="gep-project-preview-record"><b></b><div><small></small><strong></strong><p></p><a href="#"></a></div><em></em></div>');
            $row.find('b').text(r.year||'—');$row.find('small').text(r.theme||'');$row.find('strong').text(r.title||'Nuevo proyecto');$row.find('p').text(r.text||'');
            if(r.url&&r.button){$row.find('a').text(r.button).on('click',function(e){e.preventDefault();});}else{$row.find('a').remove();}
            $row.find('em').text(r.tag||'');$out.append($row);
        });
        $('#gep-proj-preview-count').text(shown+' '+(shown===1?'proyecto cerrado':'proyectos cerrados'));
        $('#gep-proj-preview-empty').toggle(shown===0);
    }
    function updateProjectPreview(){
        if(!$('#gep-project-preview-shell').length)return;
        $('#gep-proj-prev-hero-eyebrow').text(pfield('#gep_projects_hero_eyebrow','Investigación aplicada'));
        $('#gep-proj-prev-hero-title').text(pfield('#gep_projects_hero_title','Proyectos'));
        $('#gep-proj-prev-hero-text').text(pfield('#gep_projects_hero_text','Trayectoria de investigación aplicada.'));
        gepPreviewSetHeroMedia(
            $('#gep-project-live-preview [data-project-live="hero"]'),
            $('[name="gep_projects_hero_image"]').val() || '',
            $('[name="gep_projects_hero_video"]').val() || '',
            '#gep-project-preview-shell',
            'assets/field/valle-rural.jpg'
        );
        $('#gep-proj-prev-nav-featured').text(pfield('#gep_projects_nav_featured','Vigentes'));
        $('#gep-proj-prev-nav-trajectory').text(pfield('#gep_projects_nav_trajectory','Trayectoria'));
        $('#gep-proj-prev-nav-areas').text(pfield('#gep_projects_nav_areas','Áreas de trabajo'));
        $('#gep-proj-prev-traj-eyebrow').text(pfield('#gep_projects_trajectory_eyebrow','2006 — 2016'));
        $('#gep-proj-prev-traj-title').text(pfield('#gep_projects_trajectory_title','Proyectos cerrados · trayectoria'));
        $('#gep-proj-prev-traj-text').text(pfield('#gep_projects_trajectory_text','Explora los registros.'));
        $('#gep-proj-prev-note').text(pfield('#gep_projects_note',''));
        $('#gep-proj-prev-areas-eyebrow').text(pfield('#gep_projects_areas_eyebrow','Áreas de trabajo'));
        $('#gep-proj-prev-areas-title').text(pfield('#gep_projects_areas_title','Una investigación conectada por múltiples escalas.'));
        $('#gep-proj-prev-areas-text').text(pfield('#gep_projects_areas_text','Cruces entre sistemas naturales y herramientas geoespaciales.'));
        var $a=$('#gep-proj-preview-areas').empty();
        $('#gep-project-areas .gep-project-area-row').each(function(){var d=projectAreaData($(this)),$c=$('<div class="gep-project-preview-area"><b></b><strong></strong><p></p></div>');$c.find('b').text(d.icon||'＋');$c.find('strong').text(d.title||'Nueva área');$c.find('p').text(d.text||'');$a.append($c);});
        updateProjectPreviewRecords();updateProjectSummaries();updateProjectEditorSearch();
    }
    function projectDupKey(d){var y=String(d.year||'').match(/(19|20)\d{2}/g);return projectSlugify(d.title||'')+'|'+(y?y.join('-'):'');}
    function updateProjectEditorSearch(){/* v9.26.0: búsqueda + filtro por estado (Todos / Vigentes / Trayectoria) + aviso de duplicados */var q=$.trim($('#gep-project-editor-search').val()||'').toLowerCase(),shown=0,counts={all:projectData.length,vigente:0,cerrado:0},dups={};$.each(projectData,function(_,d){counts[d.status==='vigente'?'vigente':'cerrado']++;var k=projectDupKey(d);if(k.charAt(0)!=='|')dups[k]=(dups[k]||0)+1;});$('#gep-project-records .gep-project-record-list-row').each(function(){var $r=$(this),d=projectRecordByKey($r.attr('data-project-key'));if(!d)return;var hay=[d.status,d.status==='vigente'?'vigente':'trayectoria finalizado cerrado',d.year,d.era,d.theme,d.title,d.text,d.tag,d.institution,d.lead].join(' ').toLowerCase(),st=d.status==='vigente'?'vigente':'cerrado',ok=(!q||hay.indexOf(q)>=0)&&(projectStatusFilter==='all'||projectStatusFilter===st);$r.toggleClass('is-editor-filtered',!ok);$r.find('.gep-project-dup-badge').prop('hidden',!(dups[projectDupKey(d)]>1));if(ok)shown++;});$('.gep-project-status-filter [data-status]').each(function(){var $b=$(this),k=$b.attr('data-status');$b.toggleClass('is-active',k===projectStatusFilter).attr('aria-pressed',k===projectStatusFilter?'true':'false').find('b').text(counts[k]||0);});var total=projectStatusFilter==='all'?projectData.length:counts[projectStatusFilter];$('#gep-project-editor-count').text((q||projectStatusFilter!=='all')?(shown+' de '+projectData.length):(projectData.length+' proyectos'));$('#gep-project-filter-empty').prop('hidden',!(projectData.length&&!shown));void total;}
    function projectSetStatusFilter(status){projectStatusFilter=(status==='vigente'||status==='cerrado')?status:'all';updateProjectEditorSearch();if(projectActiveKey!==null){var $a=$('#gep-project-records .gep-project-record-list-row.is-active');if($a.hasClass('is-editor-filtered'))projectCloseEditor();}}
    window.gepProjectSetStatusFilter=projectSetStatusFilter;
    function initProjectSortables(){var $simple=$('#gep-project-features,#gep-project-areas');$simple.each(function(){var $el=$(this);if($el.data('gep-project-sortable'))return;$el.data('gep-project-sortable',true).sortable({items:'> .gep-item-row',handle:'.gep-grip',placeholder:'gep-project-sort-placeholder',forcePlaceholderSize:true,tolerance:'pointer',stop:function(){updateProjectPreview();updateProjectEmpty();markDirty();}});});var $records=$('#gep-project-records');if($records.length&&!$records.data('gep-project-sortable')){$records.data('gep-project-sortable',true).sortable({items:'> .gep-project-record-list-row',handle:'.gep-grip',placeholder:'gep-project-sort-placeholder',forcePlaceholderSize:true,tolerance:'pointer',stop:function(){var ordered=[];$records.children('.gep-project-record-list-row').each(function(){var d=projectRecordByKey($(this).attr('data-project-key'));if(d)ordered.push(d);});if(ordered.length===projectData.length)projectData=ordered;if(projectActiveKey!==null)projectOpenEditor(projectActiveKey);updateProjectPreview();markDirty();}});}}
    function validateProjectActive(showAll){if(projectActiveKey===null)return true;var d=projectRecordByKey(projectActiveKey);if(!d)return true;var valid=true,$y=$('#gep-project-single-editor [data-gep-project-editor-field="year"]'),$t=$('#gep-project-single-editor [data-gep-project-editor-field="title"]'),$u=$('#gep-project-single-editor [data-gep-project-editor-field="url"]'),$du=$('#gep-project-single-editor [data-gep-project-editor-field="detail_url"]');if(!$.trim(d.year||''))valid=setFieldError($y,'Este campo es obligatorio.',showAll)&&valid;else clearFieldError($y);if(!$.trim(d.title||''))valid=setFieldError($t,'Este campo es obligatorio.',showAll)&&valid;else clearFieldError($t);if($.trim(d.url||'')&&!isValidHttpUrl($.trim(d.url)))valid=setFieldError($u,'Escribe una URL completa (http:// o https://).',showAll)&&valid;else clearFieldError($u);if($.trim(d.detail_url||'')&&!isValidHttpUrl($.trim(d.detail_url)))valid=setFieldError($du,'Escribe una URL completa (http:// o https://).',showAll)&&valid;else clearFieldError($du);return valid;}
    function projectInvalidRecord(d){/* v9.26.0: siempre true/false; con '' jQuery.toggleClass alternaba la marca roja en cada validación */return!!(!$.trim(d.year||'')||!$.trim(d.title||'')||($.trim(d.url||'')&&!isValidHttpUrl($.trim(d.url)))||($.trim(d.detail_url||'')&&!isValidHttpUrl($.trim(d.detail_url))));}
    function validateProjects(showAll){if(!$('#gep-project-preview-shell').length)return true;var valid=true;$('[data-gep-project-required="1"]').each(function(){var $f=$(this),v=$.trim($f.val()||'');if(!v)valid=setFieldError($f,'Este campo es obligatorio.',showAll)&&valid;else clearFieldError($f);});var first=null;$.each(projectData,function(_,d){var bad=projectInvalidRecord(d);$('#gep-project-records .gep-project-record-list-row[data-project-key="'+String(d._key).replace(/"/g,'\\"')+'"]').toggleClass('is-invalid-record',bad);if(bad&&!first)first=d;if(bad)valid=false;});if(showAll&&first){projectOpenEditor(first._key);validateProjectActive(true);}else validateProjectActive(false);return valid;}
    function showProjectsValidationError(){$('#gep-project-inline-status').removeClass('is-clean is-dirty').addClass('is-error').html('<span class="dashicons dashicons-dismiss"></span> Revisa los campos');}
    function projectBuildSubmitFields(){var $box=$('#gep-project-record-submit-fields').empty(),fields=['slug','status','year','era','theme','title','text','url','button','tag','image','image_alt','details','institution','lead','gallery','detail_url'];$.each(projectData,function(i,d){if(!$.trim(d.slug||''))d.slug=projectSlugify((d.title||'')+'-'+(d.year||''));$.each(fields,function(_,k){$('<input type="hidden">').attr('name','gep_projects_records['+i+']['+k+']').val(d[k]||'').appendTo($box);});});}
    function projectMoveActive(delta){var idx=projectIndexByKey(projectActiveKey),to=idx+delta;if(idx<0||to<0||to>=projectData.length)return;var d=projectData.splice(idx,1)[0];projectData.splice(to,0,d);renderProjectList();updateProjectPreview();markDirty();}
    function projectInitEditor(){projectInitData(projectInitialRecords());renderProjectList();initProjectSortables();projectCloseEditor();}
    $(document).on('click','.gep-project-device-button',function(e){e.preventDefault();setProjectDevice($(this).data('device')||'desktop');});
    $(document).on('click','.gep-project-preview-pill',function(e){e.preventDefault();projectPreviewEra=$(this).data('era')||'all';updateProjectPreviewRecords();});$('#gep-proj-preview-search').on('input',updateProjectPreviewRecords);
    $('#gep-project-editor-search').on('input',updateProjectEditorSearch);
    $(document).on('click','.gep-project-status-filter [data-status]',function(e){e.preventDefault();projectSetStatusFilter($(this).attr('data-status'));});
    $(document).on('click','.gep-project-status-pill',function(e){e.preventDefault();e.stopPropagation();var key=$(this).closest('.gep-project-record-list-row').attr('data-project-key'),d=projectRecordByKey(key);if(!d)return;d.status=d.status==='vigente'?'cerrado':'vigente';if(String(projectActiveKey)===String(key))$('#gep-project-single-editor [data-gep-project-editor-field="status"]').val(d.status);projectUpdateListRow(key);updateProjectEditorSearch();projectSchedulePreview();markDirty();});
    /* v9.26.0: al abrir «Proyectos vigentes» o «Trayectoria» desde la lista de apartados, la lista común se filtra sola. */
    $(document).on('gep:builtin-edit',function(_,context,id){if(context!=='projects')return;if(id==='features'){projectSetStatusFilter('vigente');setTimeout(function(){highlightProject('features');},0);}else if(id==='trajectory')projectSetStatusFilter('cerrado');});
    $(document).on('click','.gep-project-record-open',function(e){e.preventDefault();/* v9.23.3: pulsar de nuevo cierra la ficha */var $pr=$(this).closest('.gep-project-record-list-row');if($pr.hasClass('is-active')&&String(projectActiveKey)===String($pr.attr('data-project-key'))){projectCloseEditor();return;}projectOpenEditor($(this).closest('.gep-project-record-list-row').attr('data-project-key'));});
    $(document).on('click','.gep-project-record-close',function(e){e.preventDefault();projectCloseEditor();});
    $(document).on('click','.gep-project-gallery-add',function(e){e.preventDefault();var d=projectRecordByKey(projectActiveKey);if(!d||typeof wp==='undefined'||!wp.media)return;var frame=wp.media({title:'Añadir imágenes a la galería',button:{text:'Añadir a galería'},library:{type:'image'},multiple:true});frame.on('select',function(){var urls=projectGalleryUrls(d.gallery);frame.state().get('selection').each(function(att){var obj=att.toJSON(),url=obj&&obj.url?obj.url:'';if(url&&urls.indexOf(url)<0)urls.push(url);});projectSyncGallery(urls,true);});frame.open();});
    $(document).on('click','.gep-project-gallery-remove',function(e){e.preventDefault();var url=$(this).closest('.gep-project-gallery-item').attr('data-url')||'',d=projectRecordByKey(projectActiveKey);if(!d)return;projectSyncGallery(projectGalleryUrls(d.gallery).filter(function(v){return v!==url;}),true);});
    $(document).on('click','.gep-project-gallery-main',function(e){e.preventDefault();var url=$(this).closest('.gep-project-gallery-item').attr('data-url')||'',d=projectRecordByKey(projectActiveKey);if(!d||!url)return;d.image=url;var $input=$('#gep-project-single-editor [data-gep-project-editor-field="image"]');$input.val(url).trigger('change');var $ic=$input.closest('.gep-image-control');$ic.find('.gep-image-preview').show().find('img').attr('src',url);$ic.find('.gep-remove-image').show();projectRenderGallery(d);});
    $(document).on('input change','[data-gep-project-editor-field]',function(){var d=projectRecordByKey(projectActiveKey);if(!d)return;var $f=$(this),k=$f.data('gep-project-editor-field');d[k]=$f.val()||'';if(k==='slug'){d._slugAuto=!$.trim(d.slug||'');if(d._slugAuto){d.slug=projectSlugify((d.title||'')+'-'+(d.year||''));$f.val(d.slug);}}else if((k==='title'||k==='year')&&d._slugAuto){d.slug=projectSlugify((d.title||'')+'-'+(d.year||''));$('#gep-project-single-editor [data-gep-project-editor-field="slug"]').val(d.slug);}if(k==='image')projectRenderGallery(d);projectUpdateListRow(d._key);updateProjectEditorSearch();validateProjectActive(false);projectSchedulePreview();markDirty();});
    $(document).on('click','.gep-project-add-feature',function(e){e.preventDefault();var $r=makeProjectFeature({});updateProjectEmpty();updateProjectPreview();highlightProject('features');markDirty();$r.attr('open',true);});
    $(document).on('click','.gep-project-add-record',function(e){e.preventDefault();projectCreate({tag:'Proyecto GEP',status:projectStatusFilter==='cerrado'?'cerrado':'vigente'});updateProjectPreview();highlightProject('records');markDirty();});
    $(document).on('click','.gep-project-add-area',function(e){e.preventDefault();var $r=makeProjectArea({icon:'＋'});updateProjectEmpty();updateProjectPreview();highlightProject('areas');markDirty();$r.attr('open',true);});
    $(document).on('click','.gep-project-remove-feature,.gep-project-remove-area',function(e){e.preventDefault();if(!window.confirm('¿Eliminar este elemento?'))return;$(this).closest('.gep-item-row').remove();updateProjectEmpty();updateProjectPreview();markDirty();});
    $(document).on('click','.gep-project-record-remove',function(e){e.preventDefault();var idx=projectIndexByKey(projectActiveKey);if(idx<0||!window.confirm('¿Eliminar este proyecto?'))return;projectData.splice(idx,1);projectCloseEditor();renderProjectList();updateProjectPreview();markDirty();});
    $(document).on('click','.gep-project-duplicate-feature',function(e){e.preventDefault();var $s=$(this).closest('.gep-project-feature-row'),$r=makeProjectFeature(projectFeatureData($s));$r.insertAfter($s);updateProjectPreview();markDirty();});
    $(document).on('click','.gep-project-record-duplicate',function(e){e.preventDefault();var d=projectRecordByKey(projectActiveKey);if(!d)return;projectCreate({slug:'',status:d.status,year:d.year,era:d.era,theme:d.theme,title:d.title,text:d.text,url:d.url,button:d.button,tag:d.tag,image:d.image,image_alt:d.image_alt,details:d.details,institution:d.institution,lead:d.lead,gallery:d.gallery,detail_url:d.detail_url},d._key);updateProjectPreview();markDirty();});
    $(document).on('click','.gep-project-duplicate-area',function(e){e.preventDefault();var $s=$(this).closest('.gep-project-area-row'),$r=makeProjectArea(projectAreaData($s));$r.insertAfter($s);updateProjectPreview();markDirty();});
    $(document).on('click','.gep-project-move-up',function(e){e.preventDefault();var $r=$(this).closest('.gep-item-row'),$p=$r.prev('.gep-item-row');if($p.length){$r.insertBefore($p);updateProjectPreview();markDirty();}});$(document).on('click','.gep-project-move-down',function(e){e.preventDefault();var $r=$(this).closest('.gep-item-row'),$n=$r.next('.gep-item-row');if($n.length){$r.insertAfter($n);updateProjectPreview();markDirty();}});
    $(document).on('click','.gep-project-record-up',function(e){e.preventDefault();projectMoveActive(-1);});$(document).on('click','.gep-project-record-down',function(e){e.preventDefault();projectMoveActive(1);});
    $(document).on('click','.gep-project-sort-year',function(e){e.preventDefault();projectData.sort(function(a,b){function yr(v){var m=String(v||'').match(/(19|20)\d{2}/);return m?parseInt(m[0],10):0;}return yr(b.year)-yr(a.year);});renderProjectList();updateProjectPreview();markDirty();});
    $(document).on('click','.gep-restore-projects',function(e){e.preventDefault();var d=projectDefaults();if(!d||!window.confirm('¿Restaurar el contenido original de Proyectos? Los cambios no se publicarán hasta guardar.'))return;var map={hero_eyebrow:'#gep_projects_hero_eyebrow',hero_title:'#gep_projects_hero_title',hero_text:'#gep_projects_hero_text',nav_featured:'#gep_projects_nav_featured',nav_trajectory:'#gep_projects_nav_trajectory',nav_areas:'#gep_projects_nav_areas',trajectory_eyebrow:'#gep_projects_trajectory_eyebrow',trajectory_title:'#gep_projects_trajectory_title',trajectory_text:'#gep_projects_trajectory_text',note:'#gep_projects_note',areas_eyebrow:'#gep_projects_areas_eyebrow',areas_title:'#gep_projects_areas_title',areas_text:'#gep_projects_areas_text'};$.each(map,function(k,sel){$(sel).val(d[k]||'');});$('#gep-project-features,#gep-project-areas').empty();$.each(d.features||[],function(_,r){makeProjectFeature(r);});$.each(d.areas||[],function(_,r){makeProjectArea(r);});projectInitData(d.projects||[]);projectCloseEditor();renderProjectList();updateProjectEmpty();initProjectSortables();updateProjectPreview();validateProjects(false);highlightProject('hero');markDirty();});
    $form.on('input change','[name^="gep_projects_"]',function(){updateProjectPreview();validateProjects(false);});
    $form.on('focusin click','.gep-step-card[data-project-preview] input,.gep-step-card[data-project-preview] textarea,.gep-step-card[data-project-preview] button,.gep-step-card[data-project-preview] summary,.gep-step-card[data-project-preview] .gep-step-heading',function(){var sec=$(this).closest('.gep-step-card').data('project-preview');if(sec)highlightProject(sec);});
    $form.on('submit.gepProjects',function(e){if(!$('#gep-project-preview-shell').length)return;if(!validateProjects(true)){e.preventDefault();showProjectsValidationError();var $first=$('.is-invalid').first();if($first.length)$('html, body').animate({scrollTop:$first.offset().top-120},220);return false;}projectBuildSubmitFields();isSubmitting=true;hasUnsavedChanges=false;$('.gep-save-button').prop('disabled',true);$('#gep-project-inline-status').removeClass('is-clean is-dirty is-error').addClass('is-clean').html('<span class="dashicons dashicons-update gep-spin"></span> Guardando…');});

    /* Publicaciones v8.7: lista ligera + formulario único */
    var publicationCounter=3000, publicationPreviewCategory='all', publicationData=[], publicationActiveKey=null, publicationPreviewTimer=null;
    function publicationDefaults(){var el=document.getElementById('gep-publications-defaults-json');if(!el)return null;try{return JSON.parse(el.textContent||'{}');}catch(e){return null;}}
    function publicationInitialRecords(){var el=document.getElementById('gep-publications-records-json');if(!el)return[];try{var data=JSON.parse(el.textContent||'[]');return $.isArray(data)?data:[];}catch(e){return[];}}
    function publicationField(selector,fallback){var $el=$(selector);if(!$el.length)return fallback||'';var v=$.trim($el.val()||'');return v!==''?v:(fallback||'');}
    function publicationNormalizeRecord(data,key){var d=data||{},cat=d.category||'otros';if(cat==='internacionales'||cat==='nacionales')cat='articulos';else if(cat==='libros-free')cat='otros';else if(cat==='capitulos-libro')cat='capitulos';if(['libros','capitulos','articulos','congresos','otros'].indexOf(cat)<0)cat='otros';return{_key:String(key||newKey('pub',++publicationCounter)),category:cat,year:d.year||'',title:d.title||'',text:d.text||'',url:d.url||'',button:d.button||'',doi:d.doi||''};}
    function publicationInitData(records){publicationData=[];$.each(records||[],function(i,row){publicationData.push(publicationNormalizeRecord(row,String(i)));});}
    function publicationRecords(){return publicationData;}
    function publicationRecordByKey(key){key=String(key);for(var i=0;i<publicationData.length;i++){if(String(publicationData[i]._key)===key)return publicationData[i];}return null;}
    function publicationIndexByKey(key){key=String(key);for(var i=0;i<publicationData.length;i++){if(String(publicationData[i]._key)===key)return i;}return-1;}
    function publicationSectionMeta(cat){function val(k,fb){var $f=$form.find('[name="gep_publications_sections['+cat+']['+k+']"]');var v=$.trim($f.val()||'');return v||fb||'';}var defs={libros:['Libros','Producción editorial','Libros'],capitulos:['Capítulos de libro','Producción editorial','Capítulos de libro'],articulos:['Artículos','Revistas','Artículos científicos'],congresos:['Congresos','Divulgación académica','Congresos y encuentros científicos'],otros:['Otros','Otros formatos','Otros materiales y publicaciones']};var d=defs[cat]||[cat,'',cat];return{label:val('label',d[0]),eyebrow:val('eyebrow',d[1]),title:val('title',d[2])};}
    function publicationCategoryOrder(){return['libros','capitulos','articulos','congresos','otros'];}
    function publicationCategoryLabel(cat){return publicationSectionMeta(cat).label;}
    function publicationSummaryText(d){var t=$.trim(d.title||''),b=$.trim(d.text||'');return t||(b.slice(0,92)+(b.length>92?'…':''))||'Nueva publicación';}
    function publicationListRow(d){var $row=$('<div class="gep-publication-list-row"><span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar" aria-hidden="true"></span><button type="button" class="gep-publication-list-open" aria-label="Editar publicación"><span class="gep-item-summary-badge gep-publication-year-summary"></span><strong class="gep-publication-title-summary"></strong><span class="gep-publication-category-summary"></span></button><span class="dashicons dashicons-arrow-right-alt2 gep-publication-list-arrow" aria-hidden="true"></span></div>');$row.attr('data-publication-key',d._key);publicationUpdateListRowElement($row,d);return $row;}
    function publicationUpdateListRowElement($row,d){$row.find('.gep-publication-year-summary').text(d.year||'AÑO');$row.find('.gep-publication-title-summary').text(publicationSummaryText(d));$row.find('.gep-publication-category-summary').text(publicationCategoryLabel(d.category)||d.category);$row.toggleClass('is-active',String(d._key)===String(publicationActiveKey));}
    function publicationUpdateListRow(key){var d=publicationRecordByKey(key);if(!d)return;$('#gep-publication-records .gep-publication-list-row').each(function(){if(String($(this).attr('data-publication-key'))===String(key)){publicationUpdateListRowElement($(this),d);return false;}});$('#gep-publication-active-summary').text(publicationSummaryText(d));}
    function renderPublicationList(){var $root=$('#gep-publication-records'),$editor=$('#gep-publication-single-editor');if($editor.length)$editor.addClass('is-hidden').appendTo('#gep-publication-editor-parking');$root.empty();var frag=document.createDocumentFragment();$.each(publicationData,function(_,d){frag.appendChild(publicationListRow(d)[0]);});$root[0]&&$root[0].appendChild(frag);updatePublicationEmpty();updatePublicationEditorSearch();if(publicationActiveKey!==null&&publicationRecordByKey(publicationActiveKey))publicationOpenEditor(publicationActiveKey);}
    function publicationClearEditorErrors(){$('#gep-publication-single-editor [data-gep-publication-editor-field]').each(function(){clearFieldError($(this));});}
    function publicationPopulateEditor(d){if(!d)return;$('#gep-publication-single-editor [data-gep-publication-editor-field]').each(function(){var $f=$(this),k=$f.data('gep-publication-editor-field');$f.val(d[k]||'');});$('#gep-publication-active-summary').text(publicationSummaryText(d));}
    function publicationOpenEditor(key){var d=publicationRecordByKey(key);if(!d)return;publicationActiveKey=String(d._key);publicationPopulateEditor(d);publicationClearEditorErrors();var $row=$('#gep-publication-records .gep-publication-list-row').removeClass('is-active').filter('[data-publication-key="'+publicationActiveKey.replace(/"/g,'\\"')+'"]').addClass('is-active'),$editor=$('#gep-publication-single-editor').removeClass('is-hidden');if($row.length)$editor.insertAfter($row);validatePublicationActive(false);highlightPublication('records');}
    function publicationCloseEditor(){publicationActiveKey=null;publicationClearEditorErrors();$('#gep-publication-single-editor').addClass('is-hidden').appendTo('#gep-publication-editor-parking');$('#gep-publication-records .gep-publication-list-row').removeClass('is-active');}
    function publicationSchedulePreview(){clearTimeout(publicationPreviewTimer);publicationPreviewTimer=setTimeout(function(){updatePublicationPreview();},180);}
    function setPublicationDevice(device){var labels={desktop:'Vista escritorio',tablet:'Vista tablet',mobile:'Vista móvil'},$p=$('#gep-publication-live-preview');if(!$p.length)return;$p.removeClass('is-device-desktop is-device-tablet is-device-mobile').addClass('is-device-'+device);$('.gep-publication-device-button').removeClass('is-active').filter('[data-device="'+device+'"]').addClass('is-active');$('#gep-publication-device-label').text(labels[device]||labels.desktop);}
    function highlightPublication(section){if(!$('#gep-publication-preview-shell').length)return;$('[data-publication-live]').removeClass('is-live-active');var $target=$('[data-publication-live="'+section+'"]').addClass('is-live-active').first();$('.gep-step-card[data-publication-preview]').removeClass('is-preview-source');var $step=$('.gep-step-card[data-publication-preview="'+section+'"]').addClass('is-preview-source').first();gepSyncParentPreview($step,'#gep-publication-preview-zone-number','#gep-publication-preview-heading-title','#gep-publication-preview-heading-text','Publicaciones','La preview se actualiza mientras editas.');gepScrollPreviewStage($('.gep-publication-device-stage'),$target);}
    function updatePublicationEmpty(){var empty=publicationData.length===0;$('#gep-publication-records-empty').toggleClass('is-hidden',!empty);if(empty)publicationCloseEditor();}
    function updatePublicationEditorSearch(){var q=$.trim($('#gep-publication-editor-search').val()||'').toLowerCase(),cat=$('#gep-publication-editor-category').val()||'all',total=publicationData.length,shown=0;$('#gep-publication-records .gep-publication-list-row').each(function(){var $row=$(this),d=publicationRecordByKey($row.attr('data-publication-key'));if(!d)return;var hay=[d.category,d.year,d.title,d.text,d.url,d.button,d.doi].join(' ').toLowerCase(),ok=(cat==='all'||d.category===cat)&&(!q||hay.indexOf(q)>=0);$row.toggleClass('is-editor-filtered',!ok);if(ok)shown++;});$('#gep-publication-editor-count').text((q||cat!=='all')?(shown+' de '+total):(total+' registros'));}
    function publicationVisibleRecords(){var q=$.trim($('#gep-pub-preview-search').val()||'').toLowerCase(),year=$('#gep-pub-preview-year').val()||'all';return $.grep(publicationData,function(d){var hay=[d.year,d.title,d.text,d.url,d.button].join(' ').toLowerCase();return(publicationPreviewCategory==='all'||d.category===publicationPreviewCategory)&&(year==='all'||String(d.year)===String(year))&&(!q||hay.indexOf(q)>=0);});}
    function updatePublicationPreviewFilterControls(){var rows=publicationData,$pills=$('#gep-pub-preview-pills').empty(),years=[];$('<button type="button" class="gep-publication-preview-pill"></button>').attr('data-category','all').toggleClass('is-active',publicationPreviewCategory==='all').text('Todos').appendTo($pills);$.each(publicationCategoryOrder(),function(_,cat){$('<button type="button" class="gep-publication-preview-pill"></button>').attr('data-category',cat).toggleClass('is-active',publicationPreviewCategory===cat).text(publicationCategoryLabel(cat)).appendTo($pills);});$.each(rows,function(_,d){var y=$.trim(d.year||'');if(y&&years.indexOf(y)<0)years.push(y);});years.sort(function(a,b){var aa=parseInt((String(a).match(/(19|20)\d{2}/)||['0'])[0],10)||0,bb=parseInt((String(b).match(/(19|20)\d{2}/)||['0'])[0],10)||0;return bb-aa;});var $year=$('#gep-pub-preview-year'),current=$year.val()||'all';$year.empty().append($('<option value="all">Todos los años</option>'));$.each(years,function(_,y){$year.append($('<option></option>').attr('value',y).text(y));});$year.val(years.indexOf(current)>=0?current:'all');}
    function renderPublicationPreviewRecords(){var visible=publicationVisibleRecords(),$root=$('#gep-pub-preview-sections').empty(),shown=visible.length;$.each(publicationCategoryOrder(),function(_,cat){var records=$.grep(visible,function(d){return d.category===cat;});if(!records.length)return;var meta=publicationSectionMeta(cat),$sec=$('<section class="gep-publication-preview-section"></section>');$sec.append($('<span></span>').text(meta.eyebrow));var $h=$('<h3></h3>').text(meta.title);$h.append($('<b></b>').text(records.length));$sec.append($h);if(cat==='libros-free'){var $grid=$('<div class="gep-publication-preview-books"></div>');$.each(records,function(_,d){var $c=$('<div class="gep-publication-preview-book"><span></span><strong></strong><p></p><em></em></div>');$c.find('span').text(d.year||'');$c.find('strong').text(d.title||'Recurso');$c.find('p').text(d.text||'');$c.find('em').text(d.button||'Abrir ↗');$grid.append($c);});$sec.append($grid);}else{var $list=$('<div class="gep-publication-preview-list"></div>');$.each(records,function(_,d){var $r=$('<div class="gep-publication-preview-row"><b></b><div><p></p><a href="#"></a></div></div>');$r.find('b').text(d.year||'');$r.find('p').text(d.text||d.title||'');if(d.url){$r.find('a').text(d.button||'Abrir ↗').on('click',function(e){e.preventDefault();});}else{$r.find('a').remove();}$list.append($r);});$sec.append($list);}$root.append($sec);});$('#gep-pub-preview-count').text(shown+' '+(shown===1?'resultado':'resultados'));$('#gep-pub-preview-empty').toggle(shown===0);}
    function updatePublicationPreview(){if(!$('#gep-publication-preview-shell').length)return;$('#gep-pub-prev-hero-eyebrow').text(publicationField('#gep_publications_hero_eyebrow','Producción científica'));$('#gep-pub-prev-hero-title').text(publicationField('#gep_publications_hero_title','Publicaciones'));$('#gep-pub-prev-hero-text').text(publicationField('#gep_publications_hero_text','Producción científica y materiales del laboratorio.'));gepPreviewSetHeroMedia($('#gep-publication-live-preview [data-publication-live="hero"]'),$('[name="gep_publications_hero_image"]').val()||'',$('[name="gep_publications_hero_video"]').val()||'','#gep-publication-preview-shell','assets/field/paisaje-niebla.jpg');$('#gep-pub-prev-nav-search').text(publicationField('#gep_publications_nav_search','Buscar y filtrar'));$('#gep-pub-prev-explorer-title').text(publicationField('#gep_publications_explorer_title','Explorar publicaciones'));$('#gep-pub-prev-note').text(publicationField('#gep_publications_note',''));var rows=publicationData,count=function(cat){return $.grep(rows,function(d){return d.category===cat;}).length;};$('#gep-pub-stat-total').text(rows.length);$('#gep-pub-stat-int').text(count('articulos'));$('#gep-pub-stat-nac').text(count('libros')+count('capitulos'));$('#gep-pub-stat-cong').text(count('congresos'));updatePublicationPreviewFilterControls();renderPublicationPreviewRecords();updatePublicationEditorSearch();}
    function initPublicationSortable(){var $el=$('#gep-publication-records');if(!$el.length||$el.data('gep-publication-sortable'))return;$el.data('gep-publication-sortable',true).sortable({items:'> .gep-publication-list-row',handle:'.gep-grip',placeholder:'gep-publication-sort-placeholder',forcePlaceholderSize:true,tolerance:'pointer',stop:function(){var reordered=[];$el.children('.gep-publication-list-row').each(function(){var d=publicationRecordByKey($(this).attr('data-publication-key'));if(d)reordered.push(d);});if(reordered.length===publicationData.length)publicationData=reordered;if(publicationActiveKey!==null)publicationOpenEditor(publicationActiveKey);updatePublicationPreview();updatePublicationEmpty();markDirty();}});}
    function validatePublicationActive(showAll){if(publicationActiveKey===null)return true;var d=publicationRecordByKey(publicationActiveKey);if(!d)return true;var valid=true,$year=$('#gep-publication-edit-year'),$text=$('#gep-publication-edit-text'),$url=$('#gep-publication-edit-url');if(!$.trim(d.year||''))valid=setFieldError($year,'Este campo es obligatorio.',showAll)&&valid;else clearFieldError($year);if(!$.trim(d.text||''))valid=setFieldError($text,'Este campo es obligatorio.',showAll)&&valid;else clearFieldError($text);if($.trim(d.url||'')&&!isValidHttpUrl($.trim(d.url)))valid=setFieldError($url,'Escribe una URL completa (http:// o https://).',showAll)&&valid;else clearFieldError($url);return valid;}
    function publicationInvalidRecord(d){return!!(!$.trim(d.year||'')||!$.trim(d.text||'')||($.trim(d.url||'')&&!isValidHttpUrl($.trim(d.url))));}
    function validatePublications(showAll){if(!$('#gep-publication-preview-shell').length)return true;var valid=true;$form.find('[data-gep-publication-required="1"]').each(function(){var $f=$(this),v=$.trim($f.val()||'');if(!v)valid=setFieldError($f,'Este campo es obligatorio.',showAll)&&valid;else clearFieldError($f);});var firstInvalid=null;$.each(publicationData,function(_,d){var bad=publicationInvalidRecord(d);$('#gep-publication-records .gep-publication-list-row[data-publication-key="'+String(d._key).replace(/"/g,'\\"')+'"]').toggleClass('is-invalid-record',bad);if(bad&&!firstInvalid)firstInvalid=d;if(bad)valid=false;});if(showAll&&firstInvalid){publicationOpenEditor(firstInvalid._key);validatePublicationActive(true);}else{validatePublicationActive(false);}return valid;}
    function showPublicationsValidationError(){$('#gep-publication-inline-status').removeClass('is-clean is-dirty').addClass('is-error').html('<span class="dashicons dashicons-dismiss"></span> Revisa los campos');}
    function publicationBuildSubmitFields(){var $box=$('#gep-publication-submit-fields').empty(),fields=['category','year','title','text','url','button','doi'];$.each(publicationData,function(i,d){$.each(fields,function(_,field){$('<input type="hidden">').attr('name','gep_publications_records['+i+']['+field+']').val(d[field]||'').appendTo($box);});});}
    function publicationCreate(data,insertAfterKey){var d=publicationNormalizeRecord(data,newKey('pub',++publicationCounter)),idx=insertAfterKey===undefined?publicationData.length:publicationIndexByKey(insertAfterKey)+1;if(idx<0)idx=publicationData.length;publicationData.splice(idx,0,d);renderPublicationList();publicationOpenEditor(d._key);initPublicationSortable();return d;}
    function publicationMoveActive(delta){var idx=publicationIndexByKey(publicationActiveKey),to=idx+delta;if(idx<0||to<0||to>=publicationData.length)return;var item=publicationData.splice(idx,1)[0];publicationData.splice(to,0,item);renderPublicationList();updatePublicationPreview();markDirty();}
    function publicationInitEditor(){publicationInitData(publicationInitialRecords());updatePublicationEmpty();updatePublicationEditorSearch();initPublicationSortable();publicationCloseEditor();}
    /* v9.21.0: puente para Importar por DOI (assets/doi-import.js). Agrega registros al inicio de la lista. */
    window.gepPublicationsApi={records:function(){return publicationData.slice();},add:function(list){var added=[];$.each(list||[],function(i,row){added.push(publicationNormalizeRecord(row,newKey('pub',++publicationCounter)));});if(!added.length)return 0;Array.prototype.splice.apply(publicationData,[0,0].concat(added));publicationActiveKey=null;renderPublicationList();initPublicationSortable();if(added.length===1)publicationOpenEditor(added[0]._key);updatePublicationPreview();highlightPublication('records');markDirty();return added.length;}};
    $(document).on('click','.gep-publication-device-button',function(e){e.preventDefault();setPublicationDevice($(this).data('device')||'desktop');});
    $(document).on('click','.gep-publication-preview-pill',function(e){e.preventDefault();publicationPreviewCategory=$(this).data('category')||'all';updatePublicationPreviewFilterControls();renderPublicationPreviewRecords();});
    $('#gep-pub-preview-search,#gep-pub-preview-year').on('input change',renderPublicationPreviewRecords);
    $('#gep-publication-editor-search,#gep-publication-editor-category').on('input change',updatePublicationEditorSearch);
    $(document).on('click','.gep-publication-list-open',function(e){e.preventDefault();/* v9.23.3: pulsar de nuevo cierra la ficha */var $pr=$(this).closest('.gep-publication-list-row');if($pr.hasClass('is-active')&&String(publicationActiveKey)===String($pr.attr('data-publication-key'))){publicationCloseEditor();return;}publicationOpenEditor($(this).closest('.gep-publication-list-row').attr('data-publication-key'));});
    $(document).on('click','.gep-publication-close-editor',function(e){e.preventDefault();publicationCloseEditor();});
    $(document).on('input change','[data-gep-publication-editor-field]',function(){var d=publicationRecordByKey(publicationActiveKey);if(!d)return;var $f=$(this),field=$f.data('gep-publication-editor-field');d[field]=$f.val()||'';publicationUpdateListRow(d._key);if($('#gep-publication-editor-search').val()||($('#gep-publication-editor-category').val()||'all')!=='all')updatePublicationEditorSearch();validatePublicationActive(false);publicationSchedulePreview();markDirty();});
    $(document).on('click','.gep-publication-add',function(e){e.preventDefault();publicationCreate({category:'articulos'});updatePublicationPreview();highlightPublication('records');markDirty();});
    $(document).on('click','.gep-publication-remove',function(e){e.preventDefault();var idx=publicationIndexByKey(publicationActiveKey);if(idx<0||!window.confirm('¿Eliminar esta publicación?'))return;publicationData.splice(idx,1);publicationCloseEditor();renderPublicationList();updatePublicationPreview();markDirty();});
    $(document).on('click','.gep-publication-duplicate',function(e){e.preventDefault();var d=publicationRecordByKey(publicationActiveKey);if(!d)return;publicationCreate({category:d.category,year:d.year,title:d.title,text:d.text,url:d.url,button:d.button},d._key);updatePublicationPreview();markDirty();});
    $(document).on('click','.gep-publication-move-up',function(e){e.preventDefault();publicationMoveActive(-1);});
    $(document).on('click','.gep-publication-move-down',function(e){e.preventDefault();publicationMoveActive(1);});
    $(document).on('click','.gep-publication-sort-year',function(e){e.preventDefault();publicationData.sort(function(a,b){function yr(s){var m=String(s||'').match(/(19|20)\d{2}/);return m?parseInt(m[0],10):0;}return yr(b.year)-yr(a.year);});renderPublicationList();updatePublicationPreview();markDirty();});
    $(document).on('click','.gep-restore-publications',function(e){e.preventDefault();var d=publicationDefaults();if(!d||!window.confirm('¿Restaurar el contenido original de Publicaciones? Los cambios no se publicarán hasta guardar.'))return;var map={hero_eyebrow:'#gep_publications_hero_eyebrow',hero_title:'#gep_publications_hero_title',hero_text:'#gep_publications_hero_text',nav_search:'#gep_publications_nav_search',explorer_title:'#gep_publications_explorer_title',note:'#gep_publications_note'};$.each(map,function(k,sel){$(sel).val(d[k]||'');});$.each(d.sections||{},function(cat,meta){$.each(['label','eyebrow','title'],function(_,k){$form.find('[name="gep_publications_sections['+cat+']['+k+']"]').val(meta[k]||'');});});publicationInitData(d.records||[]);publicationPreviewCategory='all';$('#gep-publication-editor-category').val('all');$('#gep-publication-editor-search,#gep-pub-preview-search').val('');publicationCloseEditor();renderPublicationList();initPublicationSortable();updatePublicationPreview();validatePublications(false);highlightPublication('hero');markDirty();});
    $form.on('input change','[name^="gep_publications_"]',function(){publicationSchedulePreview();validatePublications(false);markDirty();});
    $form.on('focusin click','.gep-step-card[data-publication-preview] input,.gep-step-card[data-publication-preview] textarea,.gep-step-card[data-publication-preview] select,.gep-step-card[data-publication-preview] button,.gep-step-card[data-publication-preview] .gep-step-heading',function(){var section=$(this).closest('.gep-step-card').data('publication-preview');if(section)highlightPublication(section);});
    $form.on('submit.gepPublications',function(e){if(!$('#gep-publication-preview-shell').length)return;if(!validatePublications(true)){e.preventDefault();showPublicationsValidationError();var $first=$('.is-invalid').first();if($first.length)$('html, body').animate({scrollTop:$first.offset().top-120},220);return false;}publicationBuildSubmitFields();isSubmitting=true;hasUnsavedChanges=false;$('.gep-save-button').prop('disabled',true);$('#gep-publication-inline-status').removeClass('is-clean is-dirty is-error').addClass('is-clean').html('<span class="dashicons dashicons-update gep-spin"></span> Guardando…');});

    // v9.21.2: cualquier guardado válido (Inicio, Comunicaciones, Noticias y el resto)
    // apaga el aviso "¿Salir del sitio?". Se escucha en window para saber si alguna
    // validación anterior canceló el envío.
    window.addEventListener('submit', function(e){
        if ($form.length && e.target === $form[0] && !e.defaultPrevented) {
            isSubmitting = true;
            hasUnsavedChanges = false;
        }
    });

    window.addEventListener('beforeunload', function(e){
        if (!hasUnsavedChanges || isSubmitting) return;
        e.preventDefault();
        e.returnValue = '';
    });

    updateContactPreview();
    updateContactEmptyState();
    if ($('#gep-contact-preview-shell').length) {
        setContactDevice('desktop');
        highlightContactPreview('hero');
        markClean();
    }

    /* Inicio v8.5 */
    var homeCounter=7000;
    function homeDefaults(){try{return JSON.parse($('#gep-home-defaults-json').text()||'{}');}catch(e){return {};}}
    function homeVal(sel,fb){var v=$(sel).val();return typeof v==='undefined'||v===null?fb:$.trim(String(v));}
    function homeResolveImage(url){url=$.trim(url||'');if(!url)return '';if(/^https?:\/\//i.test(url)||/^\/\//.test(url)||/^data:/i.test(url))return url;var base=(window.gepHomeAssetBase||'');return base?base.replace(/\/$/,'')+'/'+url.replace(/^\//,''):url;}
    function homeRowData($r){var d={};$r.find(':input[name]').each(function(){var m=$(this).attr('name').match(/\[([^\]]+)\]$/);if(m)d[m[1]]=$(this).val()||'';});return d;}
    function homeRows(kind){var out=[];$('#gep-home-'+kind+'-list .gep-home-repeat-row').each(function(){out.push(homeRowData($(this)));});return out;}
    function homeSummary($r){var d=homeRowData($r),kind=$r.data('home-kind'),t=d.title||d.label||d.text||'Nuevo elemento';if(kind==='stat')t=d.label||'Nueva cifra';$r.find('.gep-home-row-summary').text(t);}
    function setHomeDevice(device){var labels={desktop:'Vista escritorio',tablet:'Vista tablet',mobile:'Vista móvil'};$('#gep-home-live-preview').removeClass('is-device-desktop is-device-tablet is-device-mobile').addClass('is-device-'+device);$('.gep-home-device-button').removeClass('is-active').filter('[data-device="'+device+'"]').addClass('is-active');$('#gep-home-device-label').text(labels[device]||labels.desktop);}
    function highlightHome(section){$('[data-home-live]').removeClass('is-live-active');var $target=$('[data-home-live="'+section+'"]').addClass('is-live-active').first();$('.gep-home-step').removeClass('is-preview-source');var $step=$('.gep-home-step[data-home-preview="'+section+'"]').addClass('is-preview-source').first();gepSyncParentPreview($step,'#gep-home-preview-zone-number','#gep-home-preview-heading-title','#gep-home-preview-heading-text','Inicio','La portada completa se actualiza mientras editas.');gepScrollPreviewStage($('.gep-home-device-stage'),$target);}
    function homeImg($el,url){url=homeResolveImage(url);$el.css('background-image',url?'url("'+url.replace(/"/g,'%22')+'")':'none');}
    function scheduleHomePreview(){ clearTimeout(homePreviewTimer); homePreviewTimer=setTimeout(updateHomePreview,90); }
    function updateHomePreview(){if(!$('#gep-home-preview-shell').length)return;
      $('#hp-hero-eyebrow').text(homeVal('#gep_home_hero_eyebrow',''));$('#hp-hero-title').text(homeVal('#gep_home_hero_title',''));$('#hp-hero-text').text(homeVal('#gep_home_hero_text',''));$('#hp-hero-primary').text(homeVal('#gep_home_hero_primary_text',''));$('#hp-hero-secondary').text(homeVal('#gep_home_hero_secondary_text',''));$('#hp-hero-geo-a').text(homeVal('#gep_home_hero_geo_a',''));$('#hp-hero-geo-b').text(homeVal('#gep_home_hero_geo_b',''));gepPreviewSetHeroMedia($('#gep-home-live-preview [data-home-live="hero"]'),$('[name="gep_home_hero_image"]').val()||'',$('[name="gep_home_hero_video"]').val()||'','#gep-home-preview-shell','assets/hero-santiago.jpg');
      $('#hp-intro-eyebrow').text(homeVal('#gep_home_intro_eyebrow',''));$('#hp-intro-title').text(homeVal('#gep_home_intro_title',''));$('#hp-intro-p1').text(homeVal('#gep_home_intro_p1',''));$('#hp-intro-p2').text(homeVal('#gep_home_intro_p2',''));var $p=$('#hp-pills').empty();$.each(homeRows('pill'),function(_,d){if(d.text)$('<span></span>').text(d.text).appendTo($p);});
      var $st=$('#hp-stats').empty();$.each(homeRows('stat'),function(_,d){$('<div class="gep-home-prev-stat"><strong></strong><span></span><small></small></div>').find('strong').text(d.value||'—').end().find('span').text(d.label||'').end().find('small').text(d.text||'').end().appendTo($st);});
      var $df=$('#hp-definitions').empty();$.each(homeRows('definition'),function(_,d){$('<div class="gep-home-prev-definition"><small></small><strong></strong><p></p></div>').find('small').text(d.eyebrow||'').end().find('strong').text(d.title||'').end().find('p').text(d.text||'').end().appendTo($df);});
      homeImg($('#hp-field-image'),$('[name="gep_home_field_image"]').val()||'assets/field/trabajo-terreno-araucarias.jpg');$('#hp-field-eyebrow').text(homeVal('#gep_home_field_eyebrow',''));$('#hp-field-title').text(homeVal('#gep_home_field_title',''));$('#hp-field-text').text(homeVal('#gep_home_field_text',''));
      $('#hp-research-eyebrow').text(homeVal('#gep_home_research_eyebrow',''));$('#hp-research-title').text(homeVal('#gep_home_research_title',''));$('#hp-research-text').text(homeVal('#gep_home_research_text',''));var $rc=$('#hp-research-cards').empty();$.each(homeRows('research'),function(_,d){var $c=$('<div class="gep-home-prev-card"><div class="gep-home-prev-card-img"></div><div class="gep-home-prev-card-copy"><small></small><strong></strong><p></p></div></div>');homeImg($c.find('.gep-home-prev-card-img'),d.image);$c.find('small').text(d.kicker||'');$c.find('strong').text(d.title||'');$c.find('p').text(d.text||'');$rc.append($c);});
      $('#hp-portal-eyebrow').text(homeVal('#gep_home_portal_eyebrow',''));$('#hp-portal-title').text(homeVal('#gep_home_portal_title',''));$('#hp-portal-text').text(homeVal('#gep_home_portal_text',''));var $pc=$('#hp-portal-cards').empty();$.each(homeRows('portal'),function(_,d){$('<div class="gep-home-prev-portal"><b></b><strong></strong><p></p><small></small></div>').find('b').text(d.icon||'•').end().find('strong').text(d.title||'').end().find('p').text(d.text||'').end().find('small').text(d.button||'').end().appendTo($pc);});
      $('#hp-landscape-label').text(homeVal('#gep_home_landscape_label',''));$('#hp-landscape-title').text(homeVal('#gep_home_landscape_title',''));var landscapeImage=gepPreviewResolveImage($('[name="gep_home_landscape_image"]').val()||'','#gep-home-preview-shell','assets/field/floracion-territorio-02.jpg');$('#gep-home-live-preview [data-home-live="landscape"]').css({'background-image':landscapeImage?'linear-gradient(90deg,rgba(5,24,14,.64),rgba(5,24,14,.30)),url("'+landscapeImage.replace(/"/g,'%22')+'")':'','background-size':'cover','background-position':'center 46%'});if(window.gepLandscapePreview)window.gepLandscapePreview($('#gep-home-live-preview [data-home-live="landscape"]'),landscapeImage,!!$.trim($('[name="gep_home_landscape_image"]').val()||''));
      $('#hp-resources-eyebrow').text(homeVal('#gep_home_resources_eyebrow',''));$('#hp-resources-title').text(homeVal('#gep_home_resources_title',''));$('#hp-resources-text').text(homeVal('#gep_home_resources_text',''));var $rs=$('#hp-resources').empty();$.each(homeRows('resource'),function(_,d){$('<div class="gep-home-prev-resource"><small></small><strong></strong><b></b></div>').find('small').text(d.kicker||'').end().find('strong').text(d.title||'').end().find('b').text(d.button||'').end().appendTo($rs);});
      $('#hp-topics-eyebrow').text(homeVal('#gep_home_topics_eyebrow',''));$('#hp-topics-title').text(homeVal('#gep_home_topics_title',''));$('#hp-topics-text').text(homeVal('#gep_home_topics_text',''));var $tp=$('#hp-topics').empty();$.each(homeRows('topic'),function(_,d){if(d.text)$('<span></span>').text(d.text).appendTo($tp);});
      homeImg($('#hp-network-image'),$('[name="gep_home_network_image"]').val()||'assets/images/branding/mapbiomas.png');$('#hp-network-eyebrow').text(homeVal('#gep_home_network_eyebrow',''));$('#hp-network-title').text(homeVal('#gep_home_network_title',''));$('#hp-network-text').text(homeVal('#gep_home_network_text',''));var $nl=$('#hp-network-links').empty();$.each(homeRows('network'),function(_,d){if(d.title)$('<span></span>').text(d.title).appendTo($nl);});
      var gepNote=$.trim(homeVal('#gep_home_note',''));if(/antes de (la|su|una) publicaci[oó]n|antes de publicar el nuevo sitio|antes de habilitar la versi[oó]n final/i.test(gepNote))gepNote='';$('#hp-note').text(gepNote).toggleClass('is-empty',!gepNote);$('#hp-cta-eyebrow').text(homeVal('#gep_home_cta_eyebrow',''));$('#hp-cta-title').text(homeVal('#gep_home_cta_title',''));$('#hp-cta-button').text(homeVal('#gep_home_cta_button',''));$('.gep-home-repeat-row').each(function(){homeSummary($(this));});
    }
    function initHomeSortables(){if(!$.fn.sortable)return;$('.gep-home-repeat-list').filter(function(){return !$(this).data('home-sortable');}).each(function(){$(this).data('home-sortable',1).sortable({items:'> .gep-home-repeat-row',handle:'.gep-grip',placeholder:'gep-home-sort-placeholder',forcePlaceholderSize:true,stop:function(){updateHomePreview();markDirty();}});});}
    function makeHomeRow(kind,data){var key='h'+(++homeCounter),tpl=$('#gep-home-template-'+kind).html();if(!tpl)return $();var $r=$(tpl.replaceAll('__K__',key));$('#gep-home-'+kind+'-list').append($r);data=data||{};$r.find(':input[name]').each(function(){var m=$(this).attr('name').match(/\[([^\]]+)\]$/);if(m&&Object.prototype.hasOwnProperty.call(data,m[1]))$(this).val(data[m[1]]);});if(data.image){var $ic=$r.find('.gep-image-control');$ic.find('.gep-image-preview').show().find('img').attr('src',homeResolveImage(data.image));$ic.find('.gep-remove-image').show();}homeSummary($r);initHomeSortables();return $r;}
    $(document).on('click','.gep-home-device-button',function(e){e.preventDefault();setHomeDevice($(this).data('device')||'desktop');});
    $(document).on('input change','#gep-content-form [name^="gep_home_"]',function(){scheduleHomePreview();markDirty();});
    $(document).on('focusin click','.gep-home-step :input,.gep-home-step button,.gep-home-step > .gep-step-heading',function(){var sec=$(this).closest('.gep-home-step').data('home-preview');if(sec)highlightHome(sec);});
    $(document).on('click','.gep-home-add',function(e){e.preventDefault();var kind=$(this).data('home-add');var $r=makeHomeRow(kind,{});updateHomePreview();markDirty();$r.attr('open',true);});
    $(document).on('click','.gep-home-remove',function(e){e.preventDefault();if(!window.confirm('¿Eliminar este elemento?'))return;$(this).closest('.gep-home-repeat-row').remove();updateHomePreview();markDirty();});
    $(document).on('click','.gep-home-duplicate',function(e){e.preventDefault();var $src=$(this).closest('.gep-home-repeat-row'),kind=$src.data('home-kind'),data=homeRowData($src),$r=makeHomeRow(kind,data);$r.insertAfter($src);updateHomePreview();markDirty();});
    $(document).on('click','.gep-home-up',function(e){e.preventDefault();var $r=$(this).closest('.gep-home-repeat-row'),$p=$r.prev('.gep-home-repeat-row');if($p.length){$r.insertBefore($p);updateHomePreview();markDirty();}});
    $(document).on('click','.gep-home-down',function(e){e.preventDefault();var $r=$(this).closest('.gep-home-repeat-row'),$n=$r.next('.gep-home-repeat-row');if($n.length){$r.insertAfter($n);updateHomePreview();markDirty();}});
    $(document).on('click','.gep-restore-home',function(e){e.preventDefault();var d=homeDefaults();if(!d||!window.confirm('¿Restaurar el contenido original de Inicio? Los cambios no se publicarán hasta guardar.'))return;var scalar={hero_eyebrow:'gep_home_hero_eyebrow',hero_title:'gep_home_hero_title',hero_text:'gep_home_hero_text',hero_primary_text:'gep_home_hero_primary_text',hero_primary_url:'gep_home_hero_primary_url',hero_secondary_text:'gep_home_hero_secondary_text',hero_secondary_url:'gep_home_hero_secondary_url',hero_geo_a:'gep_home_hero_geo_a',hero_geo_b:'gep_home_hero_geo_b',intro_eyebrow:'gep_home_intro_eyebrow',intro_title:'gep_home_intro_title',intro_p1:'gep_home_intro_p1',intro_p2:'gep_home_intro_p2',field_alt:'gep_home_field_alt',field_index:'gep_home_field_index',field_eyebrow:'gep_home_field_eyebrow',field_title:'gep_home_field_title',field_text:'gep_home_field_text',research_eyebrow:'gep_home_research_eyebrow',research_title:'gep_home_research_title',research_text:'gep_home_research_text',portal_eyebrow:'gep_home_portal_eyebrow',portal_title:'gep_home_portal_title',portal_text:'gep_home_portal_text',landscape_label:'gep_home_landscape_label',landscape_title:'gep_home_landscape_title',landscape_focus:'gep_home_landscape_focus',landscape_fit:'gep_home_landscape_fit',resources_eyebrow:'gep_home_resources_eyebrow',resources_title:'gep_home_resources_title',resources_text:'gep_home_resources_text',topics_eyebrow:'gep_home_topics_eyebrow',topics_title:'gep_home_topics_title',topics_text:'gep_home_topics_text',network_alt:'gep_home_network_alt',network_eyebrow:'gep_home_network_eyebrow',network_title:'gep_home_network_title',network_text:'gep_home_network_text',note:'gep_home_note',cta_eyebrow:'gep_home_cta_eyebrow',cta_title:'gep_home_cta_title',cta_button:'gep_home_cta_button',cta_url:'gep_home_cta_url'};$.each(scalar,function(k,n){$('[name="'+n+'"]').val(d[k]||'');});$.each(['field_image','network_image','landscape_image'],function(_,k){var n='gep_home_'+k,$i=$('[name="'+n+'"]').val(d[k]||''),$c=$i.closest('.gep-image-control');if(d[k]){$c.find('.gep-image-preview').show().find('img').attr('src',homeResolveImage(d[k]));$c.find('.gep-remove-image').show();}else{$c.find('.gep-image-preview').hide();$c.find('.gep-remove-image').hide();}});var map={pill:'pills',stat:'stats',definition:'definitions',research:'research_cards',portal:'portal_cards',resource:'resources',topic:'topics',network:'network_links'};$.each(map,function(kind,key){$('#gep-home-'+kind+'-list').empty();$.each(d[key]||[],function(_,row){if(kind==='pill'||kind==='topic')row={text:typeof row==='string'?row:(row.text||'')};makeHomeRow(kind,row);});});updateHomePreview();initHomeSortables();highlightHome('hero');markDirty();});
    if($('#gep-home-preview-shell').length){setHomeDevice('desktop');initHomeSortables();updateHomePreview();highlightHome('hero');markClean();}

    updateDocenciaPreview();
    updateDocenciaEmpty();
    if ($('#gep-docencia-preview-shell').length) {
        setDocenciaDevice('desktop');
        initDocenciaSortables();
        validateDocencia(false);
        highlightDocencia('hero');
        markClean();
    }
    if ($('#gep-project-preview-shell').length) {
        projectInitEditor();
    }
    updateProjectPreview();
    updateProjectEmpty();
    if ($('#gep-project-preview-shell').length) {
        setProjectDevice('desktop');
        initProjectSortables();
        validateProjects(false);
        highlightProject('hero');
        markClean();
    }
    if ($('#gep-publication-preview-shell').length) {
        publicationInitEditor();
        setPublicationDevice('desktop');
        updatePublicationPreview();
        updatePublicationEmpty();
        validatePublications(false);
        highlightPublication('hero');
        markClean();
    }
    /* Quiénes somos v8.8: colecciones compactas por grupo */
    var directoryItems={}, directoryGroups={}, directoryActive={}, directoryItemCounter=9000;
    function directoryInitialSections(){try{var raw=JSON.parse($('#gep-directory-sections-json').text()||'[]');return raw||[];}catch(e){return[];}}
    function directoryNormalizeItem(type,data,key){var d=data||{},base={_key:String(key||newKey('di',++directoryItemCounter)),name:d.name||'',text:d.text||''};if(type==='people'){$.extend(base,{group:d.group||'',specialization:d.specialization||'',year:d.year||'',meta:d.meta||'',email:d.email||'',image:d.image||''});}else if(type==='status'){$.extend(base,{group:d.group||'',status:d.status||''});}else{$.extend(base,{label:d.label||'',image:d.image||''});}return base;}
    function directorySectionKey($section){return String($section.attr('data-section-key')||'');}
    function directorySectionItems($section){var k=directorySectionKey($section);return directoryItems[k]||[];}
    function directoryItemByKey($section,key){var items=directorySectionItems($section);key=String(key);for(var i=0;i<items.length;i++)if(String(items[i]._key)===key)return items[i];return null;}
    function directoryItemIndex($section,key){var items=directorySectionItems($section);key=String(key);for(var i=0;i<items.length;i++)if(String(items[i]._key)===key)return i;return-1;}
    function directoryGroupKey(value){var v=$.trim(String(value||'')).toLowerCase();try{v=v.normalize('NFD').replace(/[\u0300-\u036f]/g,'');}catch(e){}return v;}
    function directoryNormalizeGroups(raw,items){var out=[],seen={};$.each(raw||[],function(_,name){name=$.trim(String(name||''));var k=directoryGroupKey(name);if(!name||seen[k])return;seen[k]=1;out.push(name);});$.each(items||[],function(_,d){var name=$.trim(String((d&&d.group)||'')),k=directoryGroupKey(name);if(!name||seen[k])return;seen[k]=1;out.push(name);});return out;}
    function directorySectionGroups($section){var sk=directorySectionKey($section);return directoryGroups[sk]||[];}
    function directoryResolveGroupName($section,name){var key=directoryGroupKey(name),found='';$.each(directorySectionGroups($section),function(_,g){if(directoryGroupKey(g)===key){found=g;return false;}});return found;}
    function directoryEnsureGroupValue($section,name){name=$.trim(String(name||''));if(!name)return '';var existing=directoryResolveGroupName($section,name);if(existing)return existing;var sk=directorySectionKey($section);directoryGroups[sk]=directorySectionGroups($section).slice();directoryGroups[sk].push(name);return name;}
    function directoryUsesManagedGroups($section){if(!$section||!$section.length||directoryIsBecarios($section))return false;var type=$section.data('type')||'people';return type==='people'||type==='status';}
    function directoryGroupCount($section,name){var key=directoryGroupKey(name),count=0;$.each(directorySectionItems($section),function(_,d){if(directoryGroupKey(d.group||'')===key)count++;});return count;}
    function directoryOrderItemsByGroups($section){var groups=directorySectionGroups($section),items=directorySectionItems($section),ordered=[],used={};$.each(groups,function(_,g){var k=directoryGroupKey(g);$.each(items,function(i,d){if(!used[i]&&directoryGroupKey(d.group||'')===k){ordered.push(d);used[i]=1;}});});$.each(items,function(i,d){if(!used[i])ordered.push(d);});directoryItems[directorySectionKey($section)]=ordered;}
    function directoryBadge(type,d){if(type==='people')return d.specialization||d.meta||'';if(type==='status')return d.status||'';return d.label||'';}
    function directoryRow(type,d){var $r=$('<div class="gep-directory-list-row"><span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><button type="button" class="gep-directory-list-open"><span class="gep-directory-summary-copy"><span class="gep-item-summary-group"></span><strong class="gep-item-summary-name"></strong></span><span class="gep-item-summary-badge"></span></button><label class="gep-directory-quick-move" title="Mover a otro grupo"><span class="sr-only">Mover a grupo</span><select class="gep-directory-quick-group"></select></label><span class="dashicons dashicons-arrow-right-alt2 gep-directory-list-arrow"></span></div>');$r.attr('data-item-key',d._key);directoryUpdateRowElement($r,type,d);return $r;}
    function directoryUpdateRowElement($r,type,d){var $s=$r.closest('.gep-section-builder'),isBecarios=$s.length?directoryIsBecarios($s):false,managed=$s.length?directoryUsesManagedGroups($s):false,badge=(type==='people'&&isBecarios)?(d.year||''):directoryBadge(type,d),group=((type==='status'||type==='people')&&!managed)?(d.group||''):'';$r.find('.gep-item-summary-name').text($.trim(d.name||'')||'Nuevo elemento');$r.find('.gep-item-summary-group').text(group).toggle(!!group);if(type==='people'&&isBecarios&&!badge)badge='Añadir año';if(type==='status'&&isBecarios&&!badge)badge='Añadir año';$r.find('.gep-item-summary-badge').text(badge).toggle(!!badge).toggleClass('is-missing-year',(type==='people'||type==='status')&&isBecarios&&badge==='Añadir año');var $quick=$r.find('.gep-directory-quick-group');if(managed&&$quick.length){directoryPopulateGroupSelect($s,$quick,d.group||'');$quick.show();}else{$quick.hide();}var sk=$s.length?directorySectionKey($s):'';$r.toggleClass('is-active',String(directoryActive[sk]||'')===String(d._key));}
    function directoryEditorHtml(type){
        var image='<div class="gep-admin-field compact gep-person-image-field"><span class="gep-field-label">Foto <span class="gep-optional">(opcional)</span></span><div class="gep-image-control"><input type="hidden" class="gep-image-url" data-gep-directory-editor-field="image" value=""><div class="gep-image-actions"><button type="button" class="button gep-select-image"><span class="dashicons dashicons-format-image"></span>Seleccionar / cambiar foto</button><button type="button" class="button-link-delete gep-remove-image" style="display:none">Quitar imagen</button></div><div class="gep-image-preview" style="display:none"><img src="" alt="Vista previa"></div></div></div>';
        var fields='';
        if(type==='people'){
            fields='<label class="gep-people-group-field gep-status-group-field"><span class="gep-status-group-label">Grupo</span><select class="gep-directory-group-select" data-gep-directory-editor-field="group"></select><small>Organiza esta persona dentro del apartado. También puedes moverla arrastrando.</small></label><label class="gep-becario-year-field">Año / período <input type="text" data-gep-directory-editor-field="year" placeholder="Ej: 2007–2008"><small>Se muestra como la etiqueta de año del becario.</small></label><label class="gep-directory-specialization-field">Especialización <span class="gep-optional">(opcional)</span><input type="text" data-gep-directory-editor-field="specialization" placeholder="Ej: Geomática aplicada a recursos naturales"></label><div class="gep-grid-2"><label>Nombre<input type="text" data-gep-directory-editor-field="name"></label><label>Formación / cargo destacado<input type="text" data-gep-directory-editor-field="meta"></label></div><label>Descripción<textarea data-gep-directory-editor-field="text"></textarea></label><label>Correo <span class="gep-optional">(opcional)</span><input type="email" data-gep-directory-editor-field="email"></label>'+image;
        }else if(type==='status'){
            fields='<label class="gep-status-group-field"><span class="gep-status-group-label">Grupo / programa</span><select class="gep-directory-group-select" data-gep-directory-editor-field="group"></select><small>La persona puede moverse a otro grupo desde aquí o arrastrándola entre grupos.</small></label><div class="gep-grid-2 gep-status-person-grid"><label class="gep-status-value-field"><span class="gep-status-value-label">Estado original (D/T)</span><input type="text" data-gep-directory-editor-field="status" placeholder="D o T"><small class="gep-status-value-help">D = en desarrollo · T = terminado.</small></label><label>Nombre<input type="text" data-gep-directory-editor-field="name"></label></div><label>Descripción / tesis<textarea data-gep-directory-editor-field="text"></textarea></label>';
        }else{
            fields='<div class="gep-grid-2"><label>Nombre / título<input type="text" data-gep-directory-editor-field="name"></label><label>Etiqueta sobre la foto<input type="text" data-gep-directory-editor-field="label"></label></div><label>Descripción<textarea data-gep-directory-editor-field="text"></textarea></label>'+image;
        }
        return '<div class="gep-directory-single-editor is-hidden"><div class="gep-publication-single-head"><div><span>Editando elemento</span><strong class="gep-directory-active-summary">Elemento</strong></div><button type="button" class="button-link gep-directory-close-editor"><span class="dashicons dashicons-no-alt"></span></button></div><div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-directory-item-up">Subir</button><button type="button" class="button button-small gep-directory-item-down">Bajar</button><button type="button" class="button button-small gep-directory-item-duplicate"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-directory-item-remove"><span class="dashicons dashicons-trash"></span> Eliminar</button></div>'+fields+'</div>';
    }
    function directoryGroupsShellHtml(){return '<div class="gep-directory-groups-shell"><div class="gep-directory-groups-head"><div class="gep-directory-groups-title"><span class="dashicons dashicons-category"></span><div><strong>Grupos</strong><small class="gep-directory-groups-summary">Organiza las personas por programa.</small></div></div><button type="button" class="button button-small gep-directory-groups-toggle"><span class="dashicons dashicons-admin-settings"></span> Gestionar grupos</button></div><div class="gep-directory-group-chips"></div><div class="gep-directory-groups-manager is-hidden"><div class="gep-directory-groups-list"></div><div class="gep-directory-group-add"><input type="text" class="gep-directory-new-group" placeholder="Ej: Magíster en Áreas Silvestres…"><button type="button" class="button gep-directory-group-add-button"><span class="dashicons dashicons-plus-alt2"></span> Crear grupo</button></div><small class="gep-directory-groups-help">Arrastra los grupos para ordenar cómo aparecerán. También puedes arrastrar personas entre grupos en la lista.</small></div></div>';}
    function directoryEnsureChrome($section){
        var type=$section.data('type')||'people';
        if(!$section.find('.gep-directory-item-toolbar').length)$section.find('.gep-items').before('<div class="gep-directory-item-toolbar"><label class="gep-directory-item-search"><span class="dashicons dashicons-search"></span><input type="search" class="gep-directory-search-input" placeholder="Buscar en este grupo…"></label><span class="gep-directory-search-count"></span></div>');
        if((type==='status'||type==='people')&&!$section.find('.gep-directory-groups-shell').length)$section.find('.gep-directory-item-toolbar').before(directoryGroupsShellHtml());
        if(!$section.find('.gep-directory-editor-parking').length)$section.find('.gep-items').after('<div class="gep-directory-editor-parking"></div>');
        if(!$section.find('.gep-directory-single-editor').length)$section.find('.gep-directory-editor-parking').append(directoryEditorHtml(type));
        directoryInitSectionCollapse($section);directoryPlaceSectionActions($section);directoryPlaceSectionSettings($section);directoryRefreshStatusMode($section);directoryRenderGroupManager($section);
    }
    // v9.23.1: Duplicar y Eliminar van dentro del apartado (al final), no en su cabecera.
    function directoryPlaceSectionActions($section){
        var $body=$section.children('.gep-directory-section-body'),$actions=$section.children('.gep-section-head').find('.gep-section-actions');
        if(!$body.length||!$actions.length)return;
        var $footer=$body.children('.gep-section-footer');
        if(!$footer.length)$footer=$('<div class="gep-section-footer"><span class="gep-section-footer-label">Acciones del apartado</span></div>').appendTo($body);
        $actions.appendTo($footer);
    }
    // v9.23.2: el título y "Mostrar abierto" se ajustan pocas veces; van al pie, junto a Duplicar y Eliminar.
    function directoryPlaceSectionSettings($section){
        var $footer=$section.find('.gep-directory-section-body > .gep-section-footer'),$settings=$section.find('.gep-directory-section-body > .gep-section-settings');
        if(!$footer.length)return;
        $footer.find('.gep-section-footer-label').text('Ajustes del apartado');
        if($settings.length)$settings.insertAfter($footer.find('.gep-section-footer-label'));
    }
    // v9.23.2: menos cosas a la vista. Grupos, buscador y selector de grupo solo aparecen cuando sirven.
    function directorySimplify($section){
        var type=$section.data('type')||'people',managed=directoryUsesManagedGroups($section),groups=managed?directorySectionGroups($section):[],count=directorySectionItems($section).length,$toolbar=$section.find('.gep-directory-item-toolbar').first(),$shell=$section.find('.gep-directory-groups-shell').first();
        var hasGroups=groups.length>0,showSearch=count>8;
        $section.toggleClass('is-groupless',!hasGroups).toggleClass('has-search',showSearch);
        if($shell.length&&$toolbar.length){
            if(!$shell.prev().is($toolbar))$shell.insertAfter($toolbar);
            var $toggle=$shell.find('.gep-directory-groups-toggle');
            if($toggle.length)$toggle.appendTo($toolbar);
            $toggle=$toolbar.find('.gep-directory-groups-toggle');
            $toggle.html('<span class="dashicons dashicons-category"></span> '+(hasGroups?('Grupos ('+groups.length+')'):'Organizar en grupos'));
        }
        $toolbar.toggleClass('is-empty',!showSearch&&!$toolbar.find('.gep-directory-groups-toggle').length);
        var addLabel=type==='people'?'Añadir persona':(type==='status'?'Añadir registro':'Añadir tarjeta');
        $section.find('.gep-add-item').first().html('<span class="dashicons dashicons-plus-alt2"></span> '+addLabel).toggleClass('is-hidden-by-groups',hasGroups);
        $section.find('.gep-directory-add-to-group').html('<span class="dashicons dashicons-plus-alt2"></span> Añadir');
        $section.find('.gep-directory-search-input').attr('placeholder','Buscar en este apartado…');
    }
    function directoryPopulateGroupSelect($section,$select,value){if(!$select||!$select.length)return;value=$.trim(String(value||''));if(value)directoryEnsureGroupValue($section,value);var groups=directorySectionGroups($section),resolved=directoryResolveGroupName($section,value)||value;$select.empty().append($('<option></option>').val('').text('Sin grupo'));$.each(groups,function(_,g){$select.append($('<option></option>').val(g).text(g));});$select.val(resolved);}
    function directoryRenderGroupManager($section){
        if(!$section||!$section.length)return;var sectionType=$section.data('type')||'people';if(sectionType!=='status'&&sectionType!=='people')return;
        var sk=directorySectionKey($section),groups=directorySectionGroups($section),items=directorySectionItems($section),$shell=$section.find('.gep-directory-groups-shell'),$list=$shell.find('.gep-directory-groups-list'),$chips=$shell.find('.gep-directory-group-chips');
        if(!$shell.length)return;
        $list.empty();$chips.empty();
        $.each(groups,function(_,g){var count=directoryGroupCount($section,g),$row=$('<div class="gep-directory-group-manager-row"><span class="gep-directory-group-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><input type="text" class="gep-directory-group-name-input"><span class="gep-directory-group-manager-count"></span><button type="button" class="button-link-delete gep-directory-group-remove" title="Eliminar grupo"><span class="dashicons dashicons-trash"></span></button></div>');$row.attr('data-group-name',g);$row.find('input').val(g);$row.find('.gep-directory-group-manager-count').text(count+' '+(count===1?'persona':'personas'));$list.append($row);var $chip=$('<button type="button" class="gep-directory-group-chip"><span></span><b></b></button>');$chip.attr('data-group-name',g).find('span').text(g);$chip.find('b').text(count);$chips.append($chip);});
        var unassigned=0;$.each(items,function(_,d){if(!$.trim(String(d.group||'')))unassigned++;});if(unassigned){var $u=$('<button type="button" class="gep-directory-group-chip is-unassigned"><span>Sin grupo</span><b></b></button>');$u.attr('data-group-name','').find('b').text(unassigned);$chips.append($u);}
        $shell.find('.gep-directory-groups-summary').text(groups.length+' '+(groups.length===1?'grupo':'grupos')+' · '+items.length+' '+(items.length===1?'persona':'personas'));
        if(!groups.length)$list.append('<div class="gep-directory-groups-empty">Aún no hay grupos. Crea el primero y mueve personas dentro.</div>');
        if($.fn.sortable&&$list.length&&!$list.data('gep-group-manager-sortable')){$list.data('gep-group-manager-sortable',1).sortable({items:'> .gep-directory-group-manager-row',handle:'.gep-directory-group-grip',placeholder:'gep-directory-group-manager-placeholder',forcePlaceholderSize:true,stop:function(){var ordered=[];$list.children('.gep-directory-group-manager-row').each(function(){ordered.push($(this).attr('data-group-name')||'');});directoryGroups[sk]=ordered;directoryOrderItemsByGroups($section);directoryRenderSection($section);initDirectorySortables($section);markDirty();}});}
    }
    function directoryIsBecarios($section){var title=$.trim($section.find('.gep-section-title-input').first().val()||'').toLowerCase();if(title.normalize)title=title.normalize('NFD').replace(/[\u0300-\u036f]/g,'');return title.indexOf('becari')>=0;}
    function directorySetSectionCollapsed($section,collapsed){if(!$section||!$section.length)return;$section.toggleClass('is-collapsed',!!collapsed);$section.children('.gep-section-head').attr('aria-expanded',collapsed?'false':'true');}
    function directoryInitSectionCollapse($section){if(!$section||!$section.length)return;var $head=$section.children('.gep-section-head').first();if(!$head.length)return;if(!$section.children('.gep-directory-section-body').length){$section.children().not('.gep-section-head').wrapAll('<div class="gep-directory-section-body"></div>');}if(!$head.find('.gep-directory-section-chevron').length)$head.append('<span class="dashicons dashicons-arrow-down-alt2 gep-directory-section-chevron" aria-hidden="true"></span>');$head.attr({role:'button',tabindex:'0'});if(!$section.data('gep-section-collapse-ready')){$section.data('gep-section-collapse-ready',1);directorySetSectionCollapsed($section,true);}}
    function directoryRefreshStatusMode($section){
        if(!$section||!$section.length)return;
        var type=$section.data('type')||'people',isBecarios=directoryIsBecarios($section);
        $section.toggleClass('is-becarios-section',isBecarios);
        if(type==='people'){$section.find('.gep-becario-year-field').toggle(isBecarios);$section.find('.gep-people-group-field').toggle(!isBecarios);$section.find('.gep-directory-groups-shell').toggle(!isBecarios);return;}
        if(type!=='status')return;
        var $field=$section.find('.gep-status-value-field'),$input=$field.find('[data-gep-directory-editor-field="status"]'),$group=$section.find('.gep-status-group-field');
        $field.toggleClass('is-becarios',isBecarios);
        $field.find('.gep-status-value-label').text(isBecarios?'Año / período':'Estado original (D/T)');
        $field.find('.gep-status-value-help').text(isBecarios?'Conserva el año o período del registro original.':'D = en desarrollo · T = terminado.');
        $input.attr('placeholder',isBecarios?'Ej: 2007–2008':'D o T');
        $group.toggle(!isBecarios);
        $section.find('.gep-directory-groups-shell').toggle(!isBecarios);
    }
    function directoryCloseEditor($section){if(!$section||!$section.length)return;var sk=directorySectionKey($section);directoryActive[sk]=null;$section.find('.gep-directory-single-editor').addClass('is-hidden').appendTo($section.find('.gep-directory-editor-parking'));$section.find('.gep-directory-list-row').removeClass('is-active');}
    function directoryPopulateEditor($section,d){
        var $ed=$section.find('.gep-directory-single-editor');
        $ed.find('[data-gep-directory-editor-field]').each(function(){var $f=$(this),k=$f.data('gep-directory-editor-field');if(k==='group'&&$f.is('select'))directoryPopulateGroupSelect($section,$f,d[k]||'');else $f.val(d[k]||'');});
        var img=d.image||'', $ic=$ed.find('.gep-image-control');
        if($ic.length){$ic.find('.gep-image-preview').toggle(!!img).find('img').attr('src',img);$ic.find('.gep-remove-image').toggle(!!img);}
        $ed.find('.gep-directory-active-summary').text($.trim(d.name||'')||'Nuevo elemento');
        directoryRefreshStatusMode($section);
    }
    function directoryOpenEditor($section,key){directoryEnsureChrome($section);directorySetSectionCollapsed($section,false);var d=directoryItemByKey($section,key);if(!d)return;var sk=directorySectionKey($section);directoryActive[sk]=String(d._key);directoryPopulateEditor($section,d);var $row=$section.find('.gep-directory-list-row').removeClass('is-active').filter('[data-item-key="'+String(d._key).replace(/"/g,'\\"')+'"]').addClass('is-active'),$ed=$section.find('.gep-directory-single-editor').removeClass('is-hidden');if($row.length)$ed.insertAfter($row);validateDirectoryActive($section,false);activatePreview($section.data('type')||'people',$ed);}
    function directoryRenderSection($section){
        directoryEnsureChrome($section);
        var sk=directorySectionKey($section),type=$section.data('type')||'people',items=directoryItems[sk]||[],$root=$section.find('.gep-items'),active=directoryActive[sk],managed=directoryUsesManagedGroups($section);
        directoryCloseEditor($section);$root.empty();$section.toggleClass('has-directory-groups',managed);
        if(managed){
            directoryGroups[sk]=directoryNormalizeGroups(directorySectionGroups($section),items);
            var groups=directorySectionGroups($section),buckets={},unassigned=[];
            $.each(groups,function(_,g){buckets[directoryGroupKey(g)]=[];});
            $.each(items,function(_,d){var resolved=directoryResolveGroupName($section,d.group||'');if(resolved){d.group=resolved;buckets[directoryGroupKey(resolved)].push(d);}else{d.group='';unassigned.push(d);}});
            function addBlock(name,list,isUnassigned){var cls='gep-group-connect-'+String(sk).replace(/[^a-zA-Z0-9_-]/g,'_'),$block=$('<section class="gep-directory-group-block"><div class="gep-directory-group-head"><div class="gep-directory-group-heading"><span class="dashicons dashicons-category"></span><strong></strong><span class="gep-directory-group-count"></span></div><button type="button" class="button-link gep-directory-add-to-group"><span class="dashicons dashicons-plus-alt2"></span> Añadir persona</button></div><div class="gep-directory-group-items"></div></section>');$block.attr('data-group-name',name||'').toggleClass('is-unassigned',!!isUnassigned);$block.find('.gep-directory-group-heading strong').text(isUnassigned?'Sin grupo':name);$block.find('.gep-directory-group-count').text(list.length+' '+(list.length===1?'persona':'personas'));$block.find('.gep-directory-add-to-group').attr('data-group-name',name||'');var $list=$block.find('.gep-directory-group-items').addClass(cls);$.each(list,function(_,d){$list.append(directoryRow(type,d));});$root.append($block);}
            $.each(groups,function(_,g){addBlock(g,buckets[directoryGroupKey(g)]||[],false);});
            if(unassigned.length||!groups.length)addBlock('',unassigned,true);
        }else{
            var frag=document.createDocumentFragment();$.each(items,function(_,d){frag.appendChild(directoryRow(type,d)[0]);});if($root[0])$root[0].appendChild(frag);
        }
        $root.find('.gep-directory-list-row').each(function(){var d=directoryItemByKey($section,$(this).attr('data-item-key'));if(d)directoryUpdateRowElement($(this),type,d);});
        if($root.data('gep-sortable-ready')){try{$root.sortable('refresh');}catch(e){}}
        updateSectionCount($section);directoryRenderGroupManager($section);directorySimplify($section);directoryUpdateSearch($section);if(active&&directoryItemByKey($section,active))directoryOpenEditor($section,active);
    }
    function directoryUpdateSearch($section){
        var q=$.trim($section.find('.gep-directory-search-input').val()||'').toLowerCase(),items=directorySectionItems($section),shown=0;
        $section.find('.gep-directory-list-row').each(function(){var $r=$(this),d=directoryItemByKey($section,$r.attr('data-item-key'));if(!d)return;var hay=Object.keys(d).filter(function(k){return k!=='_key';}).map(function(k){return d[k]||'';}).join(' ').toLowerCase(),ok=!q||hay.indexOf(q)>=0;$r.toggleClass('is-editor-filtered',!ok);if(ok)shown++;});
        $section.find('.gep-directory-group-block').each(function(){var visible=$(this).find('.gep-directory-list-row').filter(function(){return !$(this).hasClass('is-editor-filtered');}).length;$(this).toggleClass('is-search-hidden',!!q&&!visible);});
        $section.find('.gep-directory-search-count').text(q?(shown+' de '+items.length):'');
    }
    function updateSectionCount($section){var count=directorySectionItems($section).length;$section.find('.gep-section-count').first().text(count+' elemento'+(count===1?'':'s'));}
    function directoryPreviewData($section,$context){var sk=directorySectionKey($section),key=directoryActive[sk];if($context&&$context.length){var $row=$context.closest('.gep-directory-list-row');if($row.length)key=$row.attr('data-item-key');}return directoryItemByKey($section,key)||(directorySectionItems($section)[0]||{});}
    function updateSectionPreview(mode,$context){var $section=$context&&$context.length?$context.closest('.gep-section-builder'):$();if(!$section.length&&$context&&$context.hasClass('gep-section-builder'))$section=$context;if(!$section.length)return;var titleFallback=mode==='status'?'Estudiantes de postgrado':(mode==='photos'?'Equipo actual':'Investigadores'),sectionTitle=$.trim($section.find('.gep-section-title-input').first().val()||'')||titleFallback,d=directoryPreviewData($section,$context),$pane=$('.gep-preview-pane[data-pane="'+mode+'"]'),isBecarios=directoryIsBecarios($section);$pane.find('.gep-demo-section-title').text(sectionTitle);if(mode==='people'){$pane.toggleClass('is-becarios-preview',isBecarios);$pane.find('.gep-demo-person-specialization').text(d.specialization||'Especialización').toggle(!!d.specialization);$pane.find('.gep-demo-person-name').text(d.name||(isBecarios?'Nombre del becario':'Nombre de la persona'));$pane.find('.gep-demo-person-meta').text(d.meta||'Formación o cargo');$pane.find('.gep-demo-person-text').text(d.text||'Una breve descripción aparecerá junto a sus datos.');$pane.find('.gep-demo-person-email').text(d.email||'correo@ejemplo.cl');var $year=$pane.find('.gep-demo-becario-year');if(!$year.length)$year=$('<span class="gep-demo-becario-year"></span>').insertBefore($pane.find('.gep-demo-person-name').first());$year.text(d.year||'Año').toggle(isBecarios);setDemoImage($pane.find('.gep-person-demo-photo'),d.image||'');}else if(mode==='status'){$pane.toggleClass('is-becarios-preview',isBecarios);$pane.find('.gep-demo-section-title').text(sectionTitle);$pane.find('.gep-demo-section-group').text(d.group||'').toggle(!isBecarios&&!!d.group);$pane.find('.gep-status-demo-row').first().find('.gep-demo-person-name').text(d.name||(isBecarios?'Nombre del becario':'Nombre del estudiante'));$pane.find('.gep-status-demo-row').first().find('.gep-demo-person-text').text(d.text||(isBecarios?'Formación o antecedente del registro.':'Descripción, programa o tema de tesis.'));$pane.find('.gep-status-demo-row').first().find('.gep-status-demo-pill').text(d.status||(isBecarios?'Año':'D'));$pane.find('.gep-status-demo-row.is-muted').toggle(!isBecarios);}else if(mode==='photos'){$pane.find('.gep-demo-person-name').first().text(d.name||'Nombre');$pane.find('.gep-demo-person-meta').first().text(d.label||'Investigador/a');setDemoImage($pane.find('.gep-photo-demo-card').first().find('.gep-photo-demo-image'),d.image||'');}}
    function initDirectorySortables($scope){
        if(!$.fn.sortable||!$('#gep-directory-preview-shell').length)return;
        var $root=$scope&&$scope.length?$scope:$(document),$team=$root.is('#gep-team-cards')?$root:$root.find('#gep-team-cards');
        if(!$team.length&&!$scope)$team=$('#gep-team-cards');
        $team.filter(function(){return !$(this).data('gep-sortable-ready');}).each(function(){$(this).data('gep-sortable-ready',true).sortable({items:'> .gep-team-card-row',handle:'.gep-grip',placeholder:'gep-team-sort-placeholder',forcePlaceholderSize:true,tolerance:'pointer',stop:function(){updateTeamPreview();markDirty();activatePreview('team',$('.gep-step-card[data-preview="team"]'));}});});
        var $sections=$root.is('#gep-sections')?$root:$root.find('#gep-sections');if(!$sections.length&&!$scope)$sections=$('#gep-sections');
        $sections.filter(function(){return !$(this).data('gep-sortable-ready');}).each(function(){$(this).data('gep-sortable-ready',true).sortable({items:'> .gep-section-builder',handle:'.gep-section-grip',placeholder:'gep-directory-sort-placeholder',forcePlaceholderSize:true,tolerance:'pointer',stop:function(){markDirty();activatePreview('directory',$('.gep-step-card[data-preview="directory"]'));}});});

        var $groupLists=$root.is('.gep-directory-group-items')?$root:$root.find('.gep-directory-group-items');if(!$groupLists.length&&!$scope)$groupLists=$('.gep-directory-group-items');
        $groupLists.filter(function(){return !$(this).data('gep-sortable-ready');}).each(function(){var $el=$(this),$section=$el.closest('.gep-section-builder'),sk=directorySectionKey($section),connect='.'+('gep-group-connect-'+String(sk).replace(/[^a-zA-Z0-9_-]/g,'_'));$el.data('gep-sortable-ready',true).sortable({items:'> .gep-directory-list-row',handle:'.gep-grip',connectWith:connect,placeholder:'gep-directory-item-placeholder',forcePlaceholderSize:true,tolerance:'pointer',start:function(){var active=directoryActive[sk];$section.data('gep-drag-active-key',active||'');},stop:function(){var ordered=[];$section.find('.gep-directory-group-block').each(function(){var group=$(this).attr('data-group-name')||'';$(this).find('> .gep-directory-group-items > .gep-directory-list-row').each(function(){var d=directoryItemByKey($section,$(this).attr('data-item-key'));if(d){d.group=group;ordered.push(d);}});});directoryItems[sk]=ordered;var active=$section.data('gep-drag-active-key')||'';directoryActive[sk]=active;directoryRenderSection($section);initDirectorySortables($section);if(active&&directoryItemByKey($section,active))directoryOpenEditor($section,active);markDirty();activatePreview($section.data('type')||'people',$section);}});});

        var $items=$root.is('.gep-items')?$root:$root.find('.gep-items');if(!$items.length&&!$scope)$items=$('.gep-items');
        $items.filter(function(){return !$(this).children('.gep-directory-group-block').length&&!$(this).data('gep-sortable-ready');}).each(function(){var $el=$(this);$el.data('gep-sortable-ready',true).sortable({items:'> .gep-directory-list-row',handle:'.gep-grip',placeholder:'gep-directory-item-placeholder',forcePlaceholderSize:true,tolerance:'pointer',stop:function(){var $section=$el.closest('.gep-section-builder'),ordered=[];$el.children('.gep-directory-list-row').each(function(){var d=directoryItemByKey($section,$(this).attr('data-item-key'));if(d)ordered.push(d);});directoryItems[directorySectionKey($section)]=ordered;var active=directoryActive[directorySectionKey($section)];if(active)directoryOpenEditor($section,active);markDirty();activatePreview($section.data('type')||'people',$section);}});});
    }
    function validateDirectoryActive($section,showAll){var sk=directorySectionKey($section),d=directoryItemByKey($section,directoryActive[sk]);if(!d)return true;var valid=true,$ed=$section.find('.gep-directory-single-editor'),$name=$ed.find('[data-gep-directory-editor-field="name"]'),$email=$ed.find('[data-gep-directory-editor-field="email"]'),any=false;Object.keys(d).forEach(function(k){if(k!=='_key'&&$.trim(String(d[k]||'')))any=true;});if(any&&!$.trim(d.name||''))valid=setFieldError($name,'Añade un nombre o título.',showAll)&&valid;else clearFieldError($name);if($email.length&&$.trim(d.email||'')&&!isValidEmail($.trim(d.email||'')))valid=setFieldError($email,'Escribe un correo válido.',showAll)&&valid;else if($email.length)clearFieldError($email);return valid;}
    function directoryItemInvalid(type,d){var any=false;Object.keys(d).forEach(function(k){if(k!=='_key'&&$.trim(String(d[k]||'')))any=true;});if(any&&!$.trim(d.name||''))return true;if(type==='people'&&$.trim(d.email||'')&&!isValidEmail($.trim(d.email||'')))return true;return false;}
    function validateDirectory(showAll){if(!$('#gep-directory-preview-shell').length)return true;var valid=true;$('[data-gep-required-directory="1"]').each(function(){var $f=$(this),v=$.trim($f.val()||'');if(!v)valid=setFieldError($f,'Este campo es obligatorio.',showAll)&&valid;else clearFieldError($f);});$('#gep-team-cards .gep-team-card-row').each(function(){var $row=$(this),$title=$row.find('[name$="[title]"]').first(),any=false;$row.find('input,textarea').each(function(){if($.trim($(this).val()||''))any=true;});if(any&&!$.trim($title.val()||'')){valid=setFieldError($title,'Pon un título a esta tarjeta.',showAll)&&valid;$row.addClass('gep-directory-row-invalid');}else{clearFieldError($title);$row.removeClass('gep-directory-row-invalid');}});var first=null;$('#gep-sections .gep-section-builder').each(function(){var $section=$(this),$title=$section.find('.gep-section-title-input').first(),type=$section.data('type')||'people';if(!$.trim($title.val()||''))valid=setFieldError($title,'Pon un título a este apartado.',showAll)&&valid;else clearFieldError($title);$.each(directorySectionItems($section),function(_,d){var bad=directoryItemInvalid(type,d);$section.find('.gep-directory-list-row[data-item-key="'+String(d._key).replace(/"/g,'\\"')+'"]').toggleClass('is-invalid-record',bad);if(bad&&!first)first={$section:$section,key:d._key};if(bad)valid=false;});});if(showAll&&first){directoryOpenEditor(first.$section,first.key);validateDirectoryActive(first.$section,true);}else $('#gep-sections .gep-section-builder').each(function(){validateDirectoryActive($(this),false);});return valid;}
    function directoryBuildSubmitFields(){
        var $box=$('#gep-directory-submit-fields').empty();
        $('#gep-sections .gep-section-builder').each(function(){
            var $section=$(this),sk=directorySectionKey($section),type=$section.data('type')||'people',fields=type==='people'?['group','specialization','year','name','meta','text','email','image']:(type==='status'?['group','status','name','text']:['name','label','text','image']);
            if(type==='status'||type==='people'){$.each(directorySectionGroups($section),function(i,g){$('<input type="hidden">').attr('name','gep_sections['+sk+'][groups]['+i+']').val(g||'').appendTo($box);});}
            $.each(directorySectionItems($section),function(i,d){$.each(fields,function(_,f){$('<input type="hidden">').attr('name','gep_sections['+sk+'][items]['+i+']['+f+']').val(d[f]||'').appendTo($box);});});
        });
    }
    function directoryCompactInit(){
        var source=directoryInitialSections();
        $('#gep-sections .gep-section-builder').each(function(){var $section=$(this),sk=directorySectionKey($section),type=$section.data('type')||'people',src=(source&&source[sk]&&source[sk].items)?source[sk].items:[],items=[];$.each(src||[],function(i,d){items.push(directoryNormalizeItem(type,d,String(i)));});directoryItems[sk]=items;directoryGroups[sk]=(type==='status'||type==='people')?directoryNormalizeGroups((source&&source[sk]&&source[sk].groups)||[],items):[];directoryActive[sk]=null;directoryRenderSection($section);});
        initDirectorySortables();
    }
    var $dirRoot=$('#gep-sections');$dirRoot.off('click','.gep-add-item').off('click','.gep-duplicate-item').off('click','.gep-remove-item').off('input','.gep-item-name-input').off('click','.gep-duplicate-section').off('click','.gep-remove-section');$(document).off('click','.gep-add-section');
    $dirRoot.on('click','.gep-section-head',function(e){if($(e.target).closest('button,a,input,textarea,select,label,.gep-section-grip').length)return;var $s=$(this).closest('.gep-section-builder');directorySetSectionCollapsed($s,!$s.hasClass('is-collapsed'));});
    $dirRoot.on('keydown','.gep-section-head',function(e){if(e.key!=='Enter'&&e.key!==' ')return;if($(e.target).is('button,a,input,textarea,select,label'))return;e.preventDefault();var $s=$(this).closest('.gep-section-builder');directorySetSectionCollapsed($s,!$s.hasClass('is-collapsed'));});
    $(document).off('click.gepAddToggle').on('click.gepAddToggle','.gep-add-section-toggle',function(e){e.preventDefault();var $bar=$(this).closest('.gep-add-section-bar');$bar.toggleClass('is-collapsed');$(this).attr('aria-expanded',$bar.hasClass('is-collapsed')?'false':'true');});
    $(document).on('click','.gep-add-section',function(e){e.preventDefault();$(this).closest('.gep-add-section-bar').addClass('is-collapsed');var type=$(this).data('type'),sk=newKey('s',++sectionCounter),html=$('#gep-section-template-'+type).html().replaceAll('__S__',sk);$('#gep-sections').append(html);var $s=$('#gep-sections .gep-section-builder').last();directoryItems[String(sk)]=[];directoryGroups[String(sk)]=[];directoryActive[String(sk)]=null;directoryEnsureChrome($s);directoryRenderSection($s);directorySetSectionCollapsed($s,false);updateEmptyState();initDirectorySortables($s);markDirty();activatePreview(type,$s);});
    $dirRoot.on('click','.gep-duplicate-section',function(e){e.preventDefault();var $src=$(this).closest('.gep-section-builder'),old=directorySectionKey($src),sk=newKey('s',++sectionCounter),type=$src.data('type')||'people',$clone=$src.clone(false,false);$clone.attr('data-section-key',sk);renameNestedNames($clone,'gep_sections['+old+']','gep_sections['+sk+']');$clone.find('.gep-items').attr('data-items-for',sk).empty().removeData('gep-sortable-ready');$clone.find('.gep-directory-single-editor,.gep-directory-groups-shell').remove();$clone.find('.gep-directory-editor-parking').empty();$clone.find('.gep-directory-search-input').val('');$clone.insertAfter($src);directoryItems[String(sk)]=$.map(directorySectionItems($src),function(d){var copy=$.extend({},d);delete copy._key;return directoryNormalizeItem(type,copy,newKey('di',++directoryItemCounter));});directoryGroups[String(sk)]=directorySectionGroups($src).slice();directoryActive[String(sk)]=null;directoryEnsureChrome($clone);directoryRenderSection($clone);directorySetSectionCollapsed($clone,false);initDirectorySortables($clone);updateEmptyState();markDirty();activatePreview(type,$clone);});
    $dirRoot.on('click','.gep-remove-section',function(e){e.preventDefault();var $s=$(this).closest('.gep-section-builder'),sk=directorySectionKey($s);if(!window.confirm('¿Eliminar este apartado y todos sus elementos?'))return;delete directoryItems[sk];delete directoryGroups[sk];delete directoryActive[sk];$s.remove();updateEmptyState();markDirty();activatePreview('directory',$('.gep-step-card[data-preview="directory"]'));});
    $dirRoot.on('click','.gep-add-item',function(e){e.preventDefault();var $s=$(this).closest('.gep-section-builder'),sk=directorySectionKey($s),type=$s.data('type')||'people',d=directoryNormalizeItem(type,{},newKey('di',++directoryItemCounter));directoryItems[sk]=directoryItems[sk]||[];directoryItems[sk].push(d);directoryRenderSection($s);directoryOpenEditor($s,d._key);initDirectorySortables($s);markDirty();});
    $dirRoot.on('click','.gep-directory-add-to-group',function(e){e.preventDefault();var $s=$(this).closest('.gep-section-builder'),sk=directorySectionKey($s),group=$(this).attr('data-group-name')||'',type=$s.data('type')||'people',d=directoryNormalizeItem(type,{group:group},newKey('di',++directoryItemCounter));directoryItems[sk]=directoryItems[sk]||[];directoryItems[sk].push(d);directoryOrderItemsByGroups($s);directoryRenderSection($s);initDirectorySortables($s);directoryOpenEditor($s,d._key);markDirty();});
    $dirRoot.on('click','.gep-directory-list-open',function(e){e.preventDefault();var $s=$(this).closest('.gep-section-builder');/* v9.23.3: pulsar de nuevo cierra la ficha */var $dr=$(this).closest('.gep-directory-list-row');if($dr.hasClass('is-active')&&String(directoryActive[directorySectionKey($s)]||'')===String($dr.attr('data-item-key'))){directoryCloseEditor($s);return;}directoryOpenEditor($s,$(this).closest('.gep-directory-list-row').attr('data-item-key'));});
    $dirRoot.on('click','.gep-directory-quick-move,.gep-directory-quick-group',function(e){e.stopPropagation();});
    $dirRoot.on('change','.gep-directory-quick-group',function(e){e.preventDefault();e.stopPropagation();var $select=$(this),$row=$select.closest('.gep-directory-list-row'),$s=$row.closest('.gep-section-builder'),sk=directorySectionKey($s),d=directoryItemByKey($s,$row.attr('data-item-key'));if(!d)return;d.group=directoryResolveGroupName($s,$select.val()||'')||($select.val()||'');directoryOrderItemsByGroups($s);directoryRenderSection($s);initDirectorySortables($s);markDirty();});
    $dirRoot.on('click','.gep-directory-close-editor',function(e){e.preventDefault();directoryCloseEditor($(this).closest('.gep-section-builder'));});
    $dirRoot.on('input change','[data-gep-directory-editor-field]',function(){var $field=$(this),$s=$field.closest('.gep-section-builder'),sk=directorySectionKey($s),d=directoryItemByKey($s,directoryActive[sk]);if(!d)return;var k=$field.data('gep-directory-editor-field'),value=$field.val()||'';d[k]=value;if(k==='group'){d.group=directoryResolveGroupName($s,value)||value;var active=d._key;directoryOrderItemsByGroups($s);directoryRenderSection($s);initDirectorySortables($s);directoryOpenEditor($s,active);activatePreview($s.data('type')||'people',$s.find('.gep-directory-single-editor'));markDirty();return;}var $row=$s.find('.gep-directory-list-row[data-item-key="'+String(d._key).replace(/"/g,'\\"')+'"]');directoryUpdateRowElement($row,$s.data('type')||'people',d);$s.find('.gep-directory-active-summary').text($.trim(d.name||'')||'Nuevo elemento');directoryUpdateSearch($s);validateDirectoryActive($s,false);activatePreview($s.data('type')||'people',$field);markDirty();});
    $dirRoot.on('input','.gep-directory-search-input',function(){directoryUpdateSearch($(this).closest('.gep-section-builder'));});
    $dirRoot.on('change','.gep-section-title-input',function(){var $s=$(this).closest('.gep-section-builder');directoryRefreshStatusMode($s);directoryRenderSection($s);initDirectorySortables($s);});
    $dirRoot.on('click','.gep-directory-item-remove',function(e){e.preventDefault();var $s=$(this).closest('.gep-section-builder'),sk=directorySectionKey($s),idx=directoryItemIndex($s,directoryActive[sk]);if(idx<0||!window.confirm('¿Eliminar este elemento?'))return;directoryItems[sk].splice(idx,1);directoryCloseEditor($s);directoryRenderSection($s);initDirectorySortables($s);markDirty();activatePreview($s.data('type')||'people',$s);});
    $dirRoot.on('click','.gep-directory-item-duplicate',function(e){e.preventDefault();var $s=$(this).closest('.gep-section-builder'),sk=directorySectionKey($s),idx=directoryItemIndex($s,directoryActive[sk]);if(idx<0)return;var src=directoryItems[sk][idx],copy=$.extend({},src);delete copy._key;var d=directoryNormalizeItem($s.data('type')||'people',copy,newKey('di',++directoryItemCounter));directoryItems[sk].splice(idx+1,0,d);directoryRenderSection($s);initDirectorySortables($s);directoryOpenEditor($s,d._key);markDirty();});
    function directoryMoveActive($s,delta){var sk=directorySectionKey($s),idx=directoryItemIndex($s,directoryActive[sk]);if(idx<0)return;var items=directoryItems[sk],d=items[idx];if(directoryUsesManagedGroups($s)){var same=[];$.each(items,function(i,it){if(directoryGroupKey(it.group||'')===directoryGroupKey(d.group||''))same.push(i);});var pos=same.indexOf(idx),targetPos=pos+delta;if(pos<0||targetPos<0||targetPos>=same.length)return;var otherIdx=same[targetPos],tmp=items[idx];items[idx]=items[otherIdx];items[otherIdx]=tmp;}else{var to=idx+delta;if(to<0||to>=items.length)return;var moved=items.splice(idx,1)[0];items.splice(to,0,moved);}directoryRenderSection($s);initDirectorySortables($s);directoryOpenEditor($s,d._key);markDirty();}
    $dirRoot.on('click','.gep-directory-item-up',function(e){e.preventDefault();directoryMoveActive($(this).closest('.gep-section-builder'),-1);});$dirRoot.on('click','.gep-directory-item-down',function(e){e.preventDefault();directoryMoveActive($(this).closest('.gep-section-builder'),1);});

    $dirRoot.on('click','.gep-directory-groups-toggle',function(e){e.preventDefault();var $shell=$(this).closest('.gep-section-builder').find('.gep-directory-groups-shell').first(),$manager=$shell.find('.gep-directory-groups-manager');$manager.toggleClass('is-hidden');$(this).toggleClass('is-active',!$manager.hasClass('is-hidden'));});
    function directoryCreateGroup($s,name){name=$.trim(String(name||''));if(!name)return false;if(directoryResolveGroupName($s,name)){window.alert('Ya existe un grupo con ese nombre.');return false;}var sk=directorySectionKey($s);directoryGroups[sk]=directorySectionGroups($s).slice();directoryGroups[sk].push(name);directoryRenderSection($s);initDirectorySortables($s);$s.find('.gep-directory-groups-manager').removeClass('is-hidden');markDirty();return true;}
    $dirRoot.on('click','.gep-directory-group-add-button',function(e){e.preventDefault();var $s=$(this).closest('.gep-section-builder'),$input=$s.find('.gep-directory-new-group'),name=$input.val();if(directoryCreateGroup($s,name))$s.find('.gep-directory-new-group').val('').focus();});
    $dirRoot.on('keydown','.gep-directory-new-group',function(e){if(e.key!=='Enter')return;e.preventDefault();$(this).closest('.gep-directory-groups-manager').find('.gep-directory-group-add-button').trigger('click');});
    $dirRoot.on('change','.gep-directory-group-name-input',function(){var $row=$(this).closest('.gep-directory-group-manager-row'),$s=$(this).closest('.gep-section-builder'),sk=directorySectionKey($s),oldName=$row.attr('data-group-name')||'',newName=$.trim($(this).val()||'');if(!newName){$(this).val(oldName);return;}var existing=directoryResolveGroupName($s,newName);if(existing&&directoryGroupKey(existing)!==directoryGroupKey(oldName)){window.alert('Ya existe un grupo con ese nombre.');$(this).val(oldName);return;}var groups=directorySectionGroups($s).slice();$.each(groups,function(i,g){if(directoryGroupKey(g)===directoryGroupKey(oldName))groups[i]=newName;});directoryGroups[sk]=groups;$.each(directorySectionItems($s),function(_,d){if(directoryGroupKey(d.group||'')===directoryGroupKey(oldName))d.group=newName;});directoryOrderItemsByGroups($s);directoryRenderSection($s);initDirectorySortables($s);$s.find('.gep-directory-groups-manager').removeClass('is-hidden');markDirty();});
    $dirRoot.on('click','.gep-directory-group-remove',function(e){e.preventDefault();var $row=$(this).closest('.gep-directory-group-manager-row'),$s=$(this).closest('.gep-section-builder'),sk=directorySectionKey($s),name=$row.attr('data-group-name')||'',count=directoryGroupCount($s,name),msg=count?'¿Eliminar “'+name+'”? Sus '+count+' '+(count===1?'persona quedará':'personas quedarán')+' en “Sin grupo”.':'¿Eliminar el grupo “'+name+'”?';if(!window.confirm(msg))return;directoryGroups[sk]=$.grep(directorySectionGroups($s),function(g){return directoryGroupKey(g)!==directoryGroupKey(name);});$.each(directorySectionItems($s),function(_,d){if(directoryGroupKey(d.group||'')===directoryGroupKey(name))d.group='';});directoryOrderItemsByGroups($s);directoryRenderSection($s);initDirectorySortables($s);$s.find('.gep-directory-groups-manager').removeClass('is-hidden');markDirty();});
    $dirRoot.on('click','.gep-directory-group-chip',function(e){e.preventDefault();var $s=$(this).closest('.gep-section-builder'),name=$(this).attr('data-group-name')||'',$target=$s.find('.gep-directory-group-block').filter(function(){return directoryGroupKey($(this).attr('data-group-name')||'')===directoryGroupKey(name);}).first();if($target.length){$target.addClass('is-pulse')[0].scrollIntoView({behavior:'smooth',block:'nearest'});setTimeout(function(){$target.removeClass('is-pulse');},900);}});
    $form.on('submit.gepDirectoryCompact',function(e){if(!$('#gep-directory-preview-shell').length)return;if(e.isDefaultPrevented())return;directoryBuildSubmitFields();});

    /* GEP Editor v9.10.2 · Preview inmediata al pasar el mouse por un apartado.
     * No abre/cierra tarjetas: solo lleva la preview al bloque correspondiente. */
    var gepHoverPreviewTimer = null;
    function gepQueueHoverPreview(callback){
        clearTimeout(gepHoverPreviewTimer);
        gepHoverPreviewTimer = setTimeout(callback, 70);
    }
    $form.on('mouseenter.gepHoverPreview', '.gep-home-step[data-home-preview]', function(){
        var section=$(this).data('home-preview');
        if(section) gepQueueHoverPreview(function(){ highlightHome(section); });
    });
    $form.on('mouseenter.gepHoverPreview', '.gep-step-card[data-contact-preview]', function(){
        var section=$(this).data('contact-preview');
        if(section) gepQueueHoverPreview(function(){ highlightContactPreview(section); });
    });
    $form.on('mouseenter.gepHoverPreview', '.gep-step-card[data-docencia-preview]', function(){
        var section=$(this).data('docencia-preview');
        if(section) gepQueueHoverPreview(function(){ highlightDocencia(section); });
    });
    $form.on('mouseenter.gepHoverPreview', '.gep-step-card[data-project-preview]', function(){
        var section=$(this).data('project-preview');
        if(section) gepQueueHoverPreview(function(){ highlightProject(section); });
    });
    $form.on('mouseenter.gepHoverPreview', '.gep-step-card[data-publication-preview]', function(){
        var section=$(this).data('publication-preview');
        if(section) gepQueueHoverPreview(function(){ highlightPublication(section); });
    });
    $form.on('mouseenter.gepHoverPreview', '.gep-section-builder', function(e){
        var $section=$(this);
        if(!$section.closest('#gep-sections').length) return;
        e.stopPropagation();
        gepQueueHoverPreview(function(){ activatePreview($section.data('type')||'people',$section); });
    });
    $form.on('mouseenter.gepHoverPreview', '.gep-step-card[data-preview]', function(){
        var $step=$(this);
        if($step.data('preview')==='directory' && $step.find('.gep-section-builder:hover').length) return;
        var mode=$step.data('preview');
        if(mode) gepQueueHoverPreview(function(){ activatePreview(mode,$step); });
    });

    updateTeamPreview();
    updateTeamEmptyState();
    updateHeaderPreview();
    updateEmptyState();
    if ($('#gep-directory-preview-shell').length) {
        directoryCompactInit();
        setDirectoryDevice('desktop');
        initDirectorySortables();
        validateDirectory(false);
        activatePreview('team', $currentPreviewContext);
        markClean();
    } else if ($('#gep-contact-preview-shell').length) {
        highlightContactPreview('hero');
    } else if ($('#gep-docencia-preview-shell').length) {
        highlightDocencia('hero');
    } else if ($('#gep-project-preview-shell').length) {
        highlightProject('hero');
    } else if ($('#gep-publication-preview-shell').length) {
        highlightPublication('hero');
    } else {
        activatePreview('team', $currentPreviewContext);
    }
});
/* GEP Editor v9.10.1 · Feedback de guardado y resolución segura de assets */
jQuery(function($){
    var $form = $('#gep-content-form');
    if (!$form.length) return;

    function publicBase(){
        var $shell = $('#gep-directory-preview-shell,#gep-docencia-preview-shell,#gep-project-preview-shell,#gep-publication-preview-shell,#gep-hub-preview-shell').first();
        return String($shell.data('public-url') || '');
    }
    function resolvePublicAsset(value){
        value = $.trim(String(value || ''));
        if (!value || /^(?:https?:)?\/\//i.test(value) || /^(?:data:|blob:)/i.test(value) || value.charAt(0)==='/') return value;
        var base = publicBase();
        if (!base) return value;
        try { return new URL(value, base).href; } catch(e) { return value; }
    }
    function resolvePreviewAssets(root){
        var $root = root ? $(root) : $form;
        $root.find('img[src]').each(function(){
            var $img=$(this), raw=$img.attr('src')||'';
            if (!raw || $img.attr('data-gep-source-resolved')===raw) return;
            var fixed=resolvePublicAsset(raw);
            if (fixed && fixed!==raw) {
                $img.attr('src', fixed).attr('data-gep-source-resolved', fixed);
            }
        });
    }

    $form.on('input change',':input',function(){
        setTimeout(function(){ resolvePreviewAssets($form); },80);
    });
    $form.on('click','.gep-add-team-card,.gep-add-section,.gep-add-item,.gep-docencia-add-course,.gep-project-add-record,.gep-publication-add,.gep-home-add,.gep-hub-add,.gep-select-image,.gep-remove-image,.gep-select-video,.gep-remove-video',function(){
        setTimeout(function(){ resolvePreviewAssets($form); },120);
    });

    $form.on('submit.gepProfessionalFeedback',function(e){
        if(e.isDefaultPrevented()) return;
        $('.gep-save-button').addClass('is-saving').prop('disabled',true).find('.dashicons').attr('class','dashicons dashicons-update');
        var $status=$('#gep-save-status');
        if($status.length){$status.removeClass('is-saved is-dirty').addClass('is-dirty');$status.find('strong').text('Guardando…');$status.find('small').text('Estamos aplicando los cambios.');}
    });

    var $toast=$('#gep-save-toast');
    if($toast.length){setTimeout(function(){$toast.addClass('is-hiding');setTimeout(function(){$toast.remove();},240);},3600);}

    setTimeout(function(){ resolvePreviewAssets($form); },120);
});


/* GEP Editor v9.13.2 · Drawer para volver temporalmente al WordPress nativo.
 * No desplaza el editor: el menú de WordPress aparece superpuesto y se cierra
 * con el mismo botón, Escape o al cambiar de página. */
jQuery(function($){
    var $body=$('body.toplevel_page_gep-content-editor');
    var $toggle=$('#gep-wp-drawer-toggle');
    if(!$body.length||!$toggle.length)return;

    function nativeWidth(){
        var $wrap=$('#adminmenuwrap');
        var w=$wrap.length?$wrap.outerWidth():0;
        if(!w||w<30)w=window.innerWidth<=782?190:160;
        document.documentElement.style.setProperty('--gep-native-admin-w',Math.round(w)+'px');
    }
    function setNativeNav(open){
        $body.toggleClass('gep-native-nav-open',!!open);
        $toggle.attr('aria-expanded',open?'true':'false');
        $toggle.attr('title',open?'Volver al editor GEP':'Mostrar menú de WordPress');
        $toggle.find('.gep-wp-drawer-label').text(open?'Volver al editor':'WordPress');
        if(open){
            requestAnimationFrame(nativeWidth);
        }
    }

    $toggle.on('click.gepNativeNav',function(e){
        e.preventDefault();
        setNativeNav(!$body.hasClass('gep-native-nav-open'));
    });
    $(document).on('keydown.gepNativeNav',function(e){
        if(e.key==='Escape'&&$body.hasClass('gep-native-nav-open'))setNativeNav(false);
    });
    $(window).on('resize.gepNativeNav',function(){
        if($body.hasClass('gep-native-nav-open'))nativeWidth();
    });
});


/* GEP Editor v9.13.6 · Pantalla de carga de transición.
 * Oculta la preconstrucción de los editores y deja aparecer la interfaz sólo
 * cuando DOM, previews e imágenes principales ya terminaron de montar. */
(function(){
    var started = Date.now();
    var done = false;
    var minimum = 720;
    function finishLoader(){
        if(done) return;
        var loader = document.getElementById('gep-editor-loader');
        if(!loader){ done = true; return; }
        var wait = Math.max(0, minimum - (Date.now() - started));
        window.setTimeout(function(){
            if(done) return;
            done = true;
            loader.classList.add('is-done');
            document.documentElement.classList.add('gep-editor-ready');
            window.setTimeout(function(){ if(loader && loader.parentNode) loader.parentNode.removeChild(loader); }, 320);
        }, wait);
    }
    if(document.readyState === 'complete') finishLoader();
    else window.addEventListener('load', finishLoader, {once:true});
    /* Fallback: un recurso externo roto nunca debe dejar el editor bloqueado. */
    window.setTimeout(finishLoader, 1800);
})();
