<?php
/**
 * GEP v9.25.0 · Foto de «Mensaje de paisaje» sin recortes exagerados.
 *
 * Antes la franja medía siempre 560 px de alto a todo el ancho (una forma muy
 * apaisada, 2,6 : 1 en PC y 0,7 : 1 en un teléfono). Con "cover" cualquier foto
 * propia se ampliaba hasta llenar esa forma y se perdía más de la mitad.
 *
 * Ahora, con una foto propia:
 * - La altura de la franja sigue la forma de la foto (ancho de la pantalla ÷
 *   proporción de la foto), con un mínimo para que el texto quepa y un máximo
 *   para que no ocupe más que la pantalla.
 * - El punto de encuadre elegido en el editor queda siempre a la vista.
 * - "Franja fija" conserva el comportamiento anterior (altura de 560 px).
 * La foto predeterminada del sitio se ve exactamente igual que antes.
 */

if ( ! function_exists( 'gep_site_landscape_focus' ) ) {

    /** "x% y%" válido o el valor por defecto de la franja. */
    function gep_site_landscape_focus( $value, $default = '50% 46%' ) {
        $value = trim( (string) $value );
        if ( preg_match( '/^(\d{1,3}(?:\.\d+)?)% (\d{1,3}(?:\.\d+)?)%$/', $value, $m ) && (float) $m[1] <= 100 && (float) $m[2] <= 100 ) {
            return round( (float) $m[1], 1 ) . '% ' . round( (float) $m[2], 1 ) . '%';
        }
        return $default;
    }

    /** Proporción ancho/alto de una foto de la Mediateca (0 si no se conoce). */
    function gep_site_landscape_ratio( $url ) {
        $url = trim( (string) $url );
        if ( '' === $url ) return 0;
        $w = 0; $h = 0;
        if ( function_exists( 'gep_site_media_attachment_id' ) && function_exists( 'wp_get_attachment_metadata' ) ) {
            $id = gep_site_media_attachment_id( function_exists( 'gep_site_media_fix_url' ) ? gep_site_media_fix_url( $url ) : $url );
            if ( $id ) {
                $meta = wp_get_attachment_metadata( $id );
                if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
                    $w = (int) $meta['width'];
                    $h = (int) $meta['height'];
                }
            }
        }
        if ( ( ! $w || ! $h ) && function_exists( 'wp_get_upload_dir' ) ) {
            // Respaldo: la foto está en uploads pero no figura en la Mediateca.
            $up   = wp_get_upload_dir();
            $path = (string) parse_url( $url, PHP_URL_PATH );
            $base = (string) parse_url( isset( $up['baseurl'] ) ? $up['baseurl'] : '', PHP_URL_PATH );
            if ( '' !== $base && 0 === strpos( $path, $base . '/' ) && false === strpos( $path, '..' ) ) {
                $file = rtrim( $up['basedir'], '/' ) . substr( rawurldecode( $path ), strlen( $base ) );
                if ( is_file( $file ) ) {
                    $size = @getimagesize( $file );
                    if ( is_array( $size ) ) { $w = (int) $size[0]; $h = (int) $size[1]; }
                }
            }
        }
        return ( $w > 0 && $h > 0 ) ? round( $w / $h, 4 ) : 0;
    }

    /**
     * Atributos class/style de la sección. Devuelve el texto listo para
     * imprimir dentro de <section ...>.
     */
    function gep_site_landscape_attrs( $values, $url_cb ) {
        $image   = isset( $values['landscape_image'] ) ? trim( (string) $values['landscape_image'] ) : '';
        $safe    = '' !== $image ? call_user_func( $url_cb, $image ) : '';
        $classes = array( 'landscape' );
        $styles  = array();
        $focus   = gep_site_landscape_focus( isset( $values['landscape_focus'] ) ? $values['landscape_focus'] : '' );
        if ( '' !== $safe ) {
            // $safe ya viene escapada por la plantilla (gep_home_url); no se vuelve a escapar.
            $styles[] = 'background-image:url(' . $safe . ')';
            $fit      = isset( $values['landscape_fit'] ) && 'cover' === $values['landscape_fit'] ? 'cover' : 'auto';
            $ratio    = 'auto' === $fit ? gep_site_landscape_ratio( $image ) : 0;
            if ( $ratio > 0 ) {
                $classes[] = 'is-photo-fit';
                // Fotos muy verticales: se muestran completas sobre una copia difuminada.
                if ( $ratio < 1.15 ) {
                    $classes[] = 'is-photo-tall';
                    $styles[]  = '--gep-landscape-img:url(' . $safe . ')';
                }
                $styles[]  = '--gep-landscape-ratio:' . $ratio;
            }
        }
        if ( '50% 46%' !== $focus ) $styles[] = 'background-position:' . $focus;
        $out = ' class="' . implode( ' ', $classes ) . '"';
        if ( $styles ) $out .= ' style="' . implode( ';', $styles ) . '"';
        return $out;
    }
}
