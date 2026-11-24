<?php
/**
 * GEP Editor · v9.26.0 · «Encuadre de la foto» (componente compartido).
 *
 * Un solo componente para todas las pestañas: junto a cada selector de
 * imagen (cabeceras, tarjetas, personas, proyectos, galería, apartados
 * propios…) aparece el botón «Encuadre de la foto», que abre la misma
 * herramienta visual (assets/image-frame.js).
 *
 * Guardado: meta de la página gep_image_frames_json, un objeto por foto:
 *   { "u:2026/10/foto.jpg": { "x":50, "y":40, "z":1.2, "mx":60, "my":40 } }
 * La clave es la foto sin dominio ni tamaño (-300x200, -scaled), así sirve
 * con la copia liviana que muestra el sitio y después de un cambio de dominio.
 * Se guarda con el botón «Guardar cambios» de la barra superior, entra en el
 * Historial y en el respaldo completo (es una meta gep_ de la página).
 * El sitio la aplica en includes/image-frame.php (sin JavaScript).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Misma clave que calcula el editor y el sitio público. */
function gep_frames_key( $url ) {
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

/** Encuadres guardados de una página. */
function gep_frames_get( $post_id ) {
    $raw  = get_post_meta( (int) $post_id, 'gep_image_frames_json', true );
    $data = '' !== $raw ? json_decode( (string) $raw, true ) : array();
    return is_array( $data ) ? gep_frames_clean( $data ) : array();
}

/** Limpia lo que llega del navegador: claves válidas y números en rango. */
function gep_frames_clean( $raw ) {
    $clean = array();
    if ( ! is_array( $raw ) ) return $clean;
    foreach ( $raw as $key => $f ) {
        $key = strtolower( (string) $key );
        if ( strlen( $key ) > 400 || ! preg_match( '#^[uax]:[^"<>\\\\\s]+$#', $key ) || ! is_array( $f ) ) continue;
        $num = function ( $v, $min, $max, $dec ) { return round( max( $min, min( $max, (float) $v ) ), $dec ); };
        $item = array(
            'x' => isset( $f['x'] ) ? $num( $f['x'], 0, 100, 1 ) : 50,
            'y' => isset( $f['y'] ) ? $num( $f['y'], 0, 100, 1 ) : 50,
            'z' => isset( $f['z'] ) ? $num( $f['z'], 1, 3, 2 ) : 1,
        );
        if ( isset( $f['mx'], $f['my'] ) && '' !== $f['mx'] && '' !== $f['my'] ) {
            $item['mx'] = $num( $f['mx'], 0, 100, 1 );
            $item['my'] = $num( $f['my'], 0, 100, 1 );
        }
        // Un encuadre igual al original no se guarda.
        if ( 50.0 === (float) $item['x'] && 50.0 === (float) $item['y'] && 1.0 === (float) $item['z'] && ! isset( $item['mx'] ) ) continue;
        $clean[ $key ] = $item;
        if ( count( $clean ) >= 300 ) break;
    }
    return $clean;
}

/** Guarda los encuadres (lo llama gep_demo_handle_admin_save, ya con nonce y permisos revisados). */
function gep_frames_save( $post_id, $source ) {
    if ( ! isset( $source['gep_image_frames_json'] ) ) return;
    $raw = json_decode( wp_unslash( (string) $source['gep_image_frames_json'] ), true );
    if ( ! is_array( $raw ) ) return;
    $clean = gep_frames_clean( $raw );
    if ( ! $clean && ! metadata_exists( 'post', $post_id, 'gep_image_frames_json' ) ) return;
    $json = wp_json_encode( $clean ? $clean : new stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    if ( get_post_meta( $post_id, 'gep_image_frames_json', true ) === $json ) return;
    update_post_meta( $post_id, 'gep_image_frames_json', wp_slash( $json ) );
}

function gep_frames_enqueue() {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'toplevel_page_gep-content-editor' !== $screen->id ) return;
    $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) return;
    $base = plugin_dir_url( GEP_EDITOR_PLUGIN_FILE );
    wp_enqueue_style( 'gep-editor-tools', $base . 'assets/editor-tools.css', array( 'gep-editor-theme' ), gep_demo_asset_version( 'assets/editor-tools.css' ) );
    wp_enqueue_script( 'gep-image-frame', $base . 'assets/image-frame.js', array( 'jquery' ), gep_demo_asset_version( 'assets/image-frame.js' ), true );
    wp_localize_script( 'gep-image-frame', 'gepFrameData', array(
        'frames' => (object) gep_frames_get( $post_id ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'gep_frames_enqueue', 45 );
