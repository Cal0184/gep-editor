<?php
/**
 * GEP v9.21.0 · Texto alternativo de imágenes.
 * Orden: el texto escrito en la ficha (si no es uno genérico o "referencial" de partida),
 * luego la descripción guardada en la Biblioteca de medios (Contenido GEP >
 * Descripción de imágenes) y, al final, el texto de respaldo de cada página.
 */
if ( ! function_exists( 'gep_site_image_alt' ) ) {
    function gep_site_image_alt( $url, $own = '', $fallback = '' ) {
        $own = trim( (string) $own );
        $generic = $own === '' || preg_match( '/^Imagen territorial (referencial|asociada)|imagen referencial/iu', $own );
        if ( ! $generic ) return $own;
        if ( function_exists( 'gep_demo_alt_for_url' ) ) {
            $library = gep_demo_alt_for_url( (string) $url );
            if ( $library !== '' ) return $library;
        }
        return $own !== '' ? $own : (string) $fallback;
    }
}
