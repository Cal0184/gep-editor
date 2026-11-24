<?php
/**
 * GEP v9.26.0 · «Encuadre de la foto» en la página pública.
 *
 * El editor guarda, por página, el encuadre de cada foto en la meta
 * gep_image_frames_json: { "u:2026/10/foto.jpg": {"x":50,"y":40,"z":1.2,"mx":60,"my":40}, ... }
 *   x, y   = punto de la foto que siempre debe verse (0-100 %), PC y tablet.
 *   z      = acercamiento (1 = sin acercar). Solo en fotos <img>; las cabeceras
 *            (fondos) siempre cubren todo el ancho y solo se mueven.
 *   mx, my = punto en teléfonos (opcional; si falta, el mismo de PC).
 *
 * Se aplica al final, sobre el HTML ya armado (mismo filtro que las imágenes
 * livianas de media-urls.php), así ninguna plantilla cambia: a cada <img> o
 * fondo con esa foto se le agregan variables CSS y un atributo data-gep-frame;
 * assets/css/style.css hace el resto. Sin JavaScript y sin parpadeo.
 */

if ( ! function_exists( 'gep_site_frame_key' ) ) {

    /** Clave estable de una foto: sin dominio, sin tamaño -300x200 ni -scaled. */
    function gep_site_frame_key( $url ) {
        $u = trim( html_entity_decode( (string) $url, ENT_QUOTES, 'UTF-8' ) );
        if ( '' === $u ) return '';
        $u = preg_replace( '/[?#].*$/', '', $u );
        $u = rawurldecode( $u );
        $u = preg_replace( '#^(?:[a-z][a-z0-9+.-]*:)?//[^/]+#i', '', $u );
        if ( preg_match( '#/uploads/(.+)$#i', $u, $m ) ) {
            $k = 'u:' . $m[1];
        } elseif ( preg_match( '#(?:^|/)(assets/.+)$#i', $u, $m ) ) {
            $k = 'a:' . $m[1];
        } else {
            $k = 'x:' . ltrim( $u, './' );
        }
        $k = preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $k );
        $k = preg_replace( '/-scaled(\.[a-z0-9]+)$/i', '$1', $k );
        return strtolower( $k );
    }

    /** Página pública actual (según la plantilla) para dar prioridad a su encuadre. */
    function gep_site_frame_current_page_id() {
        $slug = isset( $GLOBALS['activePage'] ) ? (string) $GLOBALS['activePage'] : '';
        if ( '' === $slug || ! function_exists( 'get_page_by_path' ) ) return 0;
        if ( 'inicio' === $slug && function_exists( 'get_option' ) && (int) get_option( 'page_on_front' ) ) return (int) get_option( 'page_on_front' );
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        return $page && ! empty( $page->ID ) ? (int) $page->ID : 0;
    }

    /** Encuadres guardados: los de todas las páginas, y los de la página actual mandan. */
    function gep_site_frames_map() {
        static $map = null;
        if ( null !== $map ) return $map;
        $map = array();
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) return $map;
        $rows = $wpdb->get_results( "SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = 'gep_image_frames_json' AND p.post_type = 'page'" );
        if ( ! $rows ) return $map;
        $current = gep_site_frame_current_page_id();
        $mine    = array();
        foreach ( $rows as $row ) {
            $data = json_decode( (string) $row->meta_value, true );
            if ( ! is_array( $data ) ) continue;
            if ( (int) $row->post_id === $current ) $mine = $data;
            else $map = array_merge( $map, $data );
        }
        $map = array_merge( $map, $mine );
        foreach ( $map as $k => $f ) {
            if ( ! is_array( $f ) ) { unset( $map[ $k ] ); continue; }
            $x  = isset( $f['x'] ) ? max( 0, min( 100, (float) $f['x'] ) ) : 50;
            $y  = isset( $f['y'] ) ? max( 0, min( 100, (float) $f['y'] ) ) : 50;
            $z  = isset( $f['z'] ) ? max( 1, min( 3, (float) $f['z'] ) ) : 1;
            $mx = isset( $f['mx'] ) && '' !== $f['mx'] ? max( 0, min( 100, (float) $f['mx'] ) ) : $x;
            $my = isset( $f['my'] ) && '' !== $f['my'] ? max( 0, min( 100, (float) $f['my'] ) ) : $y;
            $map[ $k ] = compact( 'x', 'y', 'z', 'mx', 'my' );
        }
        return $map;
    }

    /** Variables CSS de un encuadre. */
    function gep_site_frame_vars( $f, $with_zoom ) {
        $n    = function ( $v ) { return rtrim( rtrim( number_format( (float) $v, 2, '.', '' ), '0' ), '.' ); };
        $vars = '--gep-px:' . $n( $f['x'] ) . '%;--gep-py:' . $n( $f['y'] ) . '%;--gep-mx:' . $n( $f['mx'] ) . '%;--gep-my:' . $n( $f['my'] ) . '%';
        if ( $with_zoom && $f['z'] > 1.001 ) $vars .= ';--gep-z:' . $n( $f['z'] ) . ';--gep-k:' . number_format( 1 - 1 / $f['z'], 4, '.', '' );
        return $vars;
    }

    /** Agrega atributos y variables a una etiqueta HTML (sin tocar lo demás). */
    function gep_site_frame_tag( $tag, $attr, $vars ) {
        if ( preg_match( '/\sstyle="([^"]*)"/i', $tag, $m ) ) {
            $style = rtrim( $m[1], "; \t" );
            $tag   = str_replace( $m[0], ' style="' . ( '' !== $style ? $style . ';' : '' ) . $vars . '"', $tag );
        } else {
            $tag = preg_replace( '/^<([a-z0-9]+)/i', '<$1 style="' . $vars . '"', $tag, 1 );
        }
        return preg_replace( '/^<([a-z0-9]+)/i', '<$1 ' . $attr, $tag, 1 );
    }

    /** Filtro del HTML final (lo llama gep_site_media_filter_html). */
    function gep_site_frames_filter_html( $html ) {
        $map = gep_site_frames_map();
        if ( ! $map || ! is_string( $html ) || '' === $html ) return $html;

        // Fotos <img>.
        $html = preg_replace_callback( '#<img\b[^>]*>#i', function ( $m ) use ( $map ) {
            $tag = $m[0];
            if ( false !== stripos( $tag, 'data-gep-frame' ) ) return $tag;
            if ( ! preg_match( '#\ssrc="([^"]+)"#i', $tag, $s ) ) return $tag;
            $key = gep_site_frame_key( $s[1] );
            if ( '' === $key || ! isset( $map[ $key ] ) ) return $tag;
            $f    = $map[ $key ];
            $attr = 'data-gep-frame' . ( $f['z'] > 1.001 ? ' data-gep-zoom' : '' );
            return gep_site_frame_tag( $tag, $attr, gep_site_frame_vars( $f, true ) );
        }, $html );

        // Fondos (cabeceras y bloques con style="background-image:url(...)").
        $html = preg_replace_callback( '#<(?!img\b)[a-z][a-z0-9]*\b[^>]*\sstyle="[^"]*url\([^>]*>#i', function ( $m ) use ( $map ) {
            $tag = $m[0];
            if ( false !== stripos( $tag, 'data-gep-frame' ) ) return $tag;
            // «Mensaje de paisaje» tiene su propio encuadre (gep_home_landscape_focus).
            if ( preg_match( '/\sclass="landscape[\s"]/i', $tag ) ) return $tag;
            if ( ! preg_match( '/\sstyle="([^"]*)"/i', $tag, $st ) ) return $tag;
            if ( ! preg_match_all( '#url\(\s*[\'"]?([^\'")\s]+)#i', html_entity_decode( $st[1], ENT_QUOTES, 'UTF-8' ), $urls ) ) return $tag;
            foreach ( $urls[1] as $u ) {
                $key = gep_site_frame_key( $u );
                if ( '' !== $key && isset( $map[ $key ] ) ) {
                    return gep_site_frame_tag( $tag, 'data-gep-frame-bg', gep_site_frame_vars( $map[ $key ], false ) );
                }
            }
            return $tag;
        }, $html );

        return $html;
    }
}
