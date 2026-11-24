/* GEP Editor · v9.26.0 · Editor visual de botones (ver includes/button-editor.php).
 * - «¿A dónde lleva?» escribe el destino en el campo de enlace de siempre.
 * - «Enlace personalizado o externo…» muestra ese campo para escribirlo.
 * - «Visible / Oculto» guarda gep_*_off sin borrar texto ni enlace.
 * - La muestra del botón y la vista previa se actualizan al escribir.
 */
(function($){
    'use strict';
    if (!$) return;

    function sync($card){
        var text = $.trim($card.find('.gep-btn-text').val() || '');
        var on = !$card.find('.gep-btn-on').length || $card.find('.gep-btn-on').is(':checked');
        $card.find('.gep-btn-sample').text(text || 'Botón');
        $card.toggleClass('is-off', !on).toggleClass('is-empty', !text);
        $card.find('.gep-btn-switch-text').text(on ? 'Visible' : 'Oculto');
        var sel = $card.attr('data-preview');
        if (sel) $(sel).toggleClass('gep-btn-preview-off', !on);
    }

    $(document).on('change', '.gep-btn-card .gep-link-picker', function(){
        var $card = $(this).closest('.gep-btn-card'), v = $(this).val(), $url = $card.find('.gep-btn-url'), $custom = $card.find('.gep-link-custom');
        if (v === '__custom') {
            $custom.prop('hidden', false);
            $url.trigger('focus');
            return;
        }
        $custom.prop('hidden', true);
        $url.val(v).trigger('input').trigger('change');
    });
    $(document).on('input change', '.gep-btn-card .gep-btn-text', function(){ sync($(this).closest('.gep-btn-card')); });
    $(document).on('change', '.gep-btn-card .gep-btn-on', function(){
        var $card = $(this).closest('.gep-btn-card');
        $card.find('.gep-btn-off').val(this.checked ? '0' : '1').trigger('change');
        sync($card);
    });
    // Si alguien escribe a mano una dirección que está en la lista, se elige sola.
    $(document).on('change', '.gep-btn-card .gep-btn-url', function(){
        var $card = $(this).closest('.gep-btn-card'), v = $.trim($(this).val() || ''), $pick = $card.find('.gep-link-picker');
        var $opt = $pick.find('option').filter(function(){ return this.value === v; });
        if ($opt.length && $pick.val() !== v) { $pick.val(v); $card.find('.gep-link-custom').prop('hidden', true); }
    });

    $(function(){
        $('.gep-btn-card').each(function(){ sync($(this)); });
        // La vista previa se redibuja con los textos; se vuelve a aplicar lo oculto.
        $(document).on('input change', '#gep-content-form :input', function(){
            window.setTimeout(function(){ $('.gep-btn-card').each(function(){ var sel = $(this).attr('data-preview'); if (sel) $(sel).toggleClass('gep-btn-preview-off', $(this).hasClass('is-off')); }); }, 0);
        });
    });
})(window.jQuery);
