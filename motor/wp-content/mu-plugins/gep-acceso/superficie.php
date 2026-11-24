<?php
/**
 * GEP · Acceso seguro — reducción de superficie.
 *
 * Complementa (no repite) el mu-plugin existente gep-seguridad.php, que ya:
 * apaga los métodos con contraseña de XML-RPC, quita pingbacks, oculta
 * /wp/v2/users a visitantes y redirige ?author=.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ---- API REST solo con sesión (opcional, independiente del resto) -------- */

if ( gep_acceso_ajuste( 'rest_anonimo' ) ) {
    add_filter( 'rest_pre_dispatch', function ( $resultado, $servidor, $pedido ) {
        if ( null !== $resultado || is_user_logged_in() ) {
            return $resultado;
        }
        // Rutas que pueden seguir abiertas a visitantes (filtro para desarrolladores si hiciera falta).
        $abiertas = (array) apply_filters( 'gep_acceso_rest_publico', array( '/oembed/1.0' ) );
        $ruta     = $pedido->get_route();
        foreach ( $abiertas as $prefijo ) {
            if ( '' !== $prefijo && 0 === strpos( $ruta, $prefijo ) ) {
                return $resultado;
            }
        }
        return new WP_Error( 'rest_gep_sesion', 'Se requiere iniciar sesión.', array( 'status' => 401 ) );
    }, 10, 3 );
}

if ( ! gep_acceso_ajuste( 'superficie' ) ) {
    return;
}

/* ---- XML-RPC: corte total antes de procesar nada ------------------------- */

if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
    if ( ! headers_sent() ) {
        status_header( 403 );
        header( 'Content-Type: text/plain; charset=utf-8' );
    }
    echo 'XML-RPC desactivado.';
    exit;
}
remove_action( 'wp_head', 'rsd_link' );
add_filter( 'wp_headers', function ( $h ) {
    unset( $h['X-Pingback'] );
    return $h;
} );
add_filter( 'pings_open', '__return_false', 20 );

/* ---- Comentarios: no se aceptan nuevos; los existentes se conservan ------ */

add_filter( 'comments_open', '__return_false', 20 );
add_action( 'pre_comment_on_post', function () {
    wp_die( 'Los comentarios están desactivados.', 'Comentarios desactivados', array( 'response' => 403 ) );
} );

/* ---- Versión de WordPress fuera del HTML y de los feeds ------------------ */

remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/* ---- Enumeración de usuarios -------------------------------------------- */

// oEmbed (/wp-json/oembed/... y embeds): sin nombre ni enlace del autor.
add_filter( 'oembed_response_data', function ( $datos ) {
    unset( $datos['author_name'], $datos['author_url'] );
    return $datos;
}, 20 );

// Sitemap de usuarios (wp-sitemap-users-1.xml / ?sitemap=users).
add_filter( 'wp_sitemaps_add_provider', function ( $proveedor, $nombre ) {
    return 'users' === $nombre ? false : $proveedor;
}, 10, 2 );

// Feeds y archivos de autor: sin sesión no se muestran (los feeds incluyen el nombre de usuario).
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
add_action( 'template_redirect', function () {
    if ( is_user_logged_in() ) {
        return;
    }
    if ( is_feed() || is_author() ) {
        status_header( 404 );
        nocache_headers();
        header( 'Content-Type: text/plain; charset=utf-8' );
        echo 'No encontrado.';
        exit;
    }
}, 2 ); // Después de gep-seguridad.php (prioridad 1, ?author=).
