/**
 * GEP Editor v9.18.0 · Fondo de cabecera en un solo control.
 *
 * Solo para los encabezados (hero): une el campo de imagen y el de video en
 * una tarjeta con dos opciones, Imagen o Video. Los campos que se guardan son
 * los mismos de siempre (…hero_image y …hero_video), así que el guardado, el
 * historial y la web pública no cambian.
 *
 * - Imagen: se usa solo la imagen. Si había un video, se quita (queda
 *   recordado mientras no se guarde, por si se vuelve a elegir Video).
 * - Video: se elige el video y, debajo, una imagen de respaldo opcional que
 *   se ve mientras carga el video o cuando el teléfono no lo reproduce solo.
 */
(function ($) {
    'use strict';

    function hasValue($input) {
        return $.trim($input.val() || '') !== '';
    }

    function setLabel($field, text, hint) {
        var $label = $field.children('.gep-field-label').first();
        if (!$label.length) {
            $label = $('<span class="gep-field-label"></span>').prependTo($field);
        }
        $label.text(text);
        if (hint) {
            $label.append(' ').append($('<span class="gep-optional"></span>').text(hint));
        }
    }

    function enhance($videoField) {
        if ($videoField.data('gepHeroMedia')) return;

        var $imageField = $videoField.prevAll('.gep-media-field').first();
        var $imageInput = $imageField.find('input.gep-image-url[name$="hero_image"]');
        var $videoInput = $videoField.find('input.gep-video-url[name$="hero_video"]');
        if (!$imageInput.length || !$videoInput.length) return;

        $videoField.data('gepHeroMedia', true);

        var uid = 'gep-hero-media-' + String($videoInput.attr('name')).replace(/[^a-z0-9_-]/gi, '');
        var $box = $('<div class="gep-admin-field gep-hero-media-switch"></div>').attr('id', uid);
        var $head = $('<div class="gep-hero-media-head"></div>');
        var $copy = $('<div class="gep-hero-media-copy"></div>')
            .append('<span class="gep-field-label">Fondo de la cabecera</span>')
            .append('<small>Elige si la cabecera muestra una imagen o un video.</small>');
        var $tabs = $('<div class="gep-hero-media-tabs" role="radiogroup" aria-label="Tipo de fondo de la cabecera"></div>');
        var $btnImage = $('<button type="button" class="gep-hero-media-tab" role="radio" data-mode="image"><span class="dashicons dashicons-format-image" aria-hidden="true"></span><span>Imagen</span></button>');
        var $btnVideo = $('<button type="button" class="gep-hero-media-tab" role="radio" data-mode="video"><span class="dashicons dashicons-video-alt3" aria-hidden="true"></span><span>Video</span></button>');
        var $note = $('<p class="gep-hero-media-note"><span class="dashicons dashicons-smartphone" aria-hidden="true"></span><span>La imagen de respaldo se ve mientras carga el video y en teléfonos que no lo reproducen solos (por ejemplo, iPhone con ahorro de batería). Si no eliges una, el editor la crea sola con el primer cuadro del video.</span></p>');

        $tabs.append($btnImage, $btnVideo);
        $head.append($copy, $tabs);
        $box.insertBefore($imageField).append($head, $imageField, $videoField, $note);
        $videoField.find('.gep-video-control > p.description').hide();

        var rememberedVideo = '';
        var mode = hasValue($videoInput) ? 'video' : 'image';

        function render() {
            var isVideo = mode === 'video';
            $box.toggleClass('is-video', isVideo).toggleClass('is-image', !isVideo);
            $btnImage.toggleClass('is-active', !isVideo).attr('aria-checked', String(!isVideo));
            $btnVideo.toggleClass('is-active', isVideo).attr('aria-checked', String(isVideo));
            $videoField.toggle(isVideo);
            $note.toggle(isVideo);
            if (isVideo) {
                setLabel($videoField, 'Video', '(archivo MP4 de la Mediateca)');
                setLabel($imageField, 'Imagen de respaldo', '(opcional, recomendada)');
            } else {
                setLabel($imageField, 'Imagen', '');
            }
        }

        function restoreVideo(url) {
            var $control = $videoField.find('.gep-video-control');
            $videoInput.val(url).trigger('change');
            $control.find('.gep-video-preview video').attr('src', window.gepPreviewVideoFirstFrame ? window.gepPreviewVideoFirstFrame(url) : url);
            $control.find('.gep-video-preview').show();
            $control.find('.gep-remove-video').show();
        }

        function setMode(next) {
            if (next === mode) return;
            if (next === 'image' && hasValue($videoInput)) {
                rememberedVideo = $videoInput.val();
                // Reutiliza el botón "Quitar video" para que la vista previa y el
                // aviso de cambios sin guardar reaccionen igual que siempre.
                $videoField.find('.gep-remove-video').first().trigger('click');
            }
            mode = next;
            render();
            if (next === 'video') {
                if (rememberedVideo && !hasValue($videoInput)) {
                    restoreVideo(rememberedVideo);
                } else if (!hasValue($videoInput)) {
                    $videoField.find('.gep-select-video').first().trigger('focus');
                }
            }
        }

        $tabs.on('click', '.gep-hero-media-tab', function () {
            setMode($(this).data('mode'));
        });
        $tabs.on('keydown', '.gep-hero-media-tab', function (e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                e.preventDefault();
                var next = mode === 'image' ? 'video' : 'image';
                setMode(next);
                (next === 'image' ? $btnImage : $btnVideo).trigger('focus');
            }
        });
        // Si se elige un video con el botón, el control pasa a "Video".
        $videoInput.on('change', function () {
            if (hasValue($videoInput) && mode !== 'video') {
                mode = 'video';
                render();
            }
        });

        render();
    }

    function init(root) {
        $(root || document).find('.gep-hero-video-field').each(function () {
            enhance($(this));
        });
    }

    $(function () { init(document); });
    // Algunos formularios se arman después de cargar la página.
    $(document).on('gep:editor-ready', function () { init(document); });
    window.gepHeroMediaInit = init;
})(jQuery);
