<?php
/**
 * GEP · Acceso seguro — cierra el WordPress público (tema Kadence) a visitantes.
 *
 * Solo actúa en «template_redirect», que WordPress ejecuta únicamente cuando
 * va a mostrar una página del tema (portada /gep/motor/, ?p=, ?page_id=,
 * búsquedas, feeds, archivos, sitemaps, embeds). NO pasa por aquí:
 * wp-admin, wp-login.php, admin-ajax, admin-post, la API REST, wp-cron,
 * archivos de medios/uploads (los entrega nginx) ni las páginas públicas del
 * GEP en /gep/ (cargan WordPress sin el tema).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! gep_acceso_ajuste( 'publico' ) ) {
    return;
}

add_action( 'template_redirect', function () {
    if ( is_user_logged_in() || is_robots() || is_favicon() || is_admin() ) {
        return; // Con sesión se sigue viendo todo (vista previa, personalizador).
    }
    $destino = gep_acceso_url_publica();
    $actual  = ( is_ssl() ? 'https' : 'http' ) . '://' . ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' ) . strtok( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/', '?' );
    if ( untrailingslashit( $destino ) === untrailingslashit( $actual ) ) {
        return; // Protección contra bucles.
    }
    $codigo = gep_acceso_ajuste( 'publico_301' ) ? 301 : 302;
    nocache_headers();
    header( 'X-Robots-Tag: noindex', true );
    wp_safe_redirect( $destino, $codigo, 'GEP' );
    exit;
}, 0 );

// Sin sitemaps de WordPress: el buscador debe indexar /gep/, no el tema.
add_filter( 'wp_sitemaps_enabled', '__return_false' );

// El destino /gep/ está en el mismo dominio, pero se declara para wp_safe_redirect.
add_filter( 'allowed_redirect_hosts', function ( $hosts ) {
    $h = wp_parse_url( gep_acceso_url_publica(), PHP_URL_HOST );
    if ( $h ) {
        $hosts[] = $h;
    }
    return $hosts;
} );
