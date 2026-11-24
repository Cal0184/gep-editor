<?php
/**
 * GEP Editor v9.21.4 · Cambio de dominio sin fotos rotas en el editor.
 *
 * Las fotos y videos se guardan con su dirección completa. Si el sitio se
 * muda de dominio, el editor mostraría las vistas previas rotas. Aquí:
 * - se recuerda cada dominio por el que pasa el sitio (opción gep_known_hosts,
 *   la misma que usa el sitio público);
 * - en las pantallas del editor, las direcciones de dominios propios antiguos
 *   se muestran con el dominio actual. Al pulsar Guardar quedan guardadas
 *   ya con el dominio nuevo.
 * Se pueden agregar dominios antiguos con la constante GEP_SITE_OLD_HOSTS
 * en wp-config.php ('dominio1.cl,dominio2.cl').
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function gep_demo_domain_current_origin() {
    $p = wp_parse_url( home_url( '/' ) );
    if ( ! is_array( $p ) || empty( $p['host'] ) ) {
        return '';
    }
    return ( ! empty( $p['scheme'] ) ? $p['scheme'] : 'https' ) . '://' . $p['host'] . ( ! empty( $p['port'] ) ? ':' . (int) $p['port'] : '' );
}

function gep_demo_domain_old_hosts() {
    static $hosts = null;
    if ( null !== $hosts ) {
        return $hosts;
    }
    $list = array( 'gepdev.bonfyr.cl' );
    if ( defined( 'GEP_SITE_OLD_HOSTS' ) ) {
        $list = array_merge( $list, explode( ',', (string) GEP_SITE_OLD_HOSTS ) );
    }
    $saved = get_option( 'gep_known_hosts', array() );
    if ( is_array( $saved ) ) {
        $list = array_merge( $list, $saved );
    }
    $current = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
    $hosts   = array();
    foreach ( $list as $h ) {
        $h = preg_replace( '#[/:].*$#', '', preg_replace( '#^https?://#', '', strtolower( trim( (string) $h ) ) ) );
        if ( '' === $h ) {
            continue;
        }
        $hosts[ $h ] = true;
        $hosts[ 0 === strpos( $h, 'www.' ) ? substr( $h, 4 ) : 'www.' . $h ] = true;
    }
    unset( $hosts[ $current ] );
    $hosts = array_keys( $hosts );
    return $hosts;
}

function gep_demo_domain_remember_host() {
    $host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
    if ( '' === $host || in_array( $host, array( 'localhost', '127.0.0.1' ), true ) ) {
        return;
    }
    $saved = get_option( 'gep_known_hosts', array() );
    $saved = is_array( $saved ) ? $saved : array();
    if ( ! in_array( $host, $saved, true ) ) {
        $saved[] = $host;
        update_option( 'gep_known_hosts', array_slice( $saved, -10 ), false );
    }
}
add_action( 'admin_init', 'gep_demo_domain_remember_host' );

function gep_demo_domain_fix_html( $html ) {
    // v9.27.1 · Primero, ubicaciones anteriores del CMS (/gep/wordpress/, gepdev/wordpress) -> /gep/motor/.
    if ( is_string( $html ) && function_exists( 'gep_cms_normalize_string' ) ) {
        $html = gep_cms_normalize_string( $html );
    }
    $origin = gep_demo_domain_current_origin();
    $hosts  = gep_demo_domain_old_hosts();
    if ( ! is_string( $html ) || '' === $origin || ! $hosts ) {
        return $html;
    }
    $pattern = implode( '|', array_map( function ( $h ) { return preg_quote( $h, '#' ); }, $hosts ) );
    // v9.26.1: si WordPress ahora vive en una carpeta base (p. ej. /gep/wordpress),
    // /wordpress/... y /GEP_LEP/... del dominio antiguo pasan a /gep/wordpress/...
    $base    = gep_demo_domain_base_path();
    $folders = '' !== $base ? gep_demo_domain_moved_folders() : array();
    $seg     = $folders ? '(/(?:' . implode( '|', array_map( function ( $f ) { return preg_quote( $f, '#' ); }, $folders ) ) . ')(?=[/"\'\s?\#<)]|$))?' : '()';
    $seg_js  = $folders ? '(\\\\/(?:' . implode( '|', array_map( function ( $f ) { return preg_quote( $f, '#' ); }, $folders ) ) . ')(?=\\\\/))?' : '()';
    // Normal (https://host) y dentro de JSON (https:\/\/host).
    $html = preg_replace_callback( '#(?:https?:)?//(?:' . $pattern . ')(?::\d+)?' . $seg . '(?=[/"\'\s?\#<)]|$)#i', function ( $m ) use ( $origin, $base ) {
        return $origin . ( isset( $m[1] ) && '' !== $m[1] ? $base . $m[1] : '' );
    }, $html );
    $html = preg_replace_callback( '#https?:\\\\/\\\\/(?:' . $pattern . ')(?::\d+)?' . $seg_js . '(?=\\\\/)#i', function ( $m ) use ( $origin, $base ) {
        return str_replace( '/', '\\/', $origin . ( isset( $m[1] ) && '' !== $m[1] ? $base . str_replace( '\\/', '/', $m[1] ) : '' ) );
    }, $html );
    return $html;
}

/** v9.26.1 · Carpeta base de WordPress ("/gep" si está en /gep/wordpress; vacío si está en /wordpress). */
function gep_demo_domain_base_path() {
    $path = trim( (string) wp_parse_url( site_url( '/' ), PHP_URL_PATH ), '/' );
    if ( '' === $path || false === strpos( $path, '/' ) ) {
        return '';
    }
    return '/' . substr( $path, 0, strrpos( $path, '/' ) );
}

/** v9.26.1 · Carpetas que se movieron con la base: la de WordPress y la del sitio público. */
function gep_demo_domain_moved_folders() {
    $path    = trim( (string) wp_parse_url( site_url( '/' ), PHP_URL_PATH ), '/' );
    $folders = array();
    if ( '' !== $path ) {
        $folders[] = basename( $path );
    }
    if ( function_exists( 'gep_editor_public_site_folder' ) ) {
        $folders[] = gep_editor_public_site_folder();
    }
    return array_values( array_unique( array_filter( $folders ) ) );
}

/** Solo en las pantallas de Contenido GEP. */
function gep_demo_domain_start_buffer() {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( in_array( $page, array( 'gep-content-editor', 'gep-content-images', 'gep-content-history' ), true ) && gep_demo_domain_old_hosts() ) {
        ob_start( 'gep_demo_domain_fix_html' );
    }
}
add_action( 'admin_init', 'gep_demo_domain_start_buffer', 20 );
