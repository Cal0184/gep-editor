<?php
/**
 * Plugin Name: GEP · Seguridad básica
 * Description: Desactiva el acceso con contraseña por XML-RPC y los pingbacks y oculta la lista de usuarios a visitantes sin sesión. No toca contenido ni ajustes. Para quitarlo, borrar este archivo.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// XML-RPC: no lo usa el GEP; es una puerta frecuente de ataques a contraseñas.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', function ( $methods ) {
    unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
    return $methods;
} );

// Lista de usuarios por la API (/wp-json/wp/v2/users): solo con sesión iniciada.
add_filter( 'rest_endpoints', function ( $endpoints ) {
    if ( is_user_logged_in() ) {
        return $endpoints;
    }
    foreach ( array_keys( $endpoints ) as $route ) {
        if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
            unset( $endpoints[ $route ] );
        }
    }
    return $endpoints;
} );

// ?author=1 no debe revelar el nombre de usuario a visitantes
// (prioridad 1: antes de que WordPress redirija a /author/nombre/).
add_action( 'template_redirect', function () {
    if ( ! is_user_logged_in() && isset( $_GET['author'] ) ) {
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
}, 1 );
