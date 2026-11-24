<?php
require_once __DIR__ . '/image-alt.php';
require_once __DIR__ . '/media-urls.php';
require_once __DIR__ . '/image-frame.php'; // v9.26.0: «Encuadre de la foto»
require_once __DIR__ . '/cms-ubicacion.php';   // v9.27.1: ubicación explícita del CMS (/gep/motor/)
require_once __DIR__ . '/direcciones-limpias.php'; // v9.29.0: /gep/proyectos/ en vez de proyectos.php
/**
 * v9.27.1 · Operación Motor: carga WordPress SOLO desde la carpeta «motor»
 * (ver includes/cms-ubicacion.php). Ya no recorre carpetas con glob(): antes
 * podía elegir /gep/wordpress/ o un respaldo si existían.
 * Si el CMS no está, responde 503 «en mantenimiento» sin mostrar rutas.
 */
if ( ! function_exists( 'gep_site_bootstrap_wordpress' ) ) {
    function gep_site_bootstrap_wordpress() {
        // v9.29.0: «…/proyectos.php» → «…/proyectos/» y enlaces limpios en la página.
        gep_direcciones_redirigir();
        gep_direcciones_inicio();
        if ( defined( 'ABSPATH' ) ) { gep_site_media_start(); return true; }

        $wp_load = gep_site_cms_wp_load();
        if ( '' === $wp_load ) {
            gep_site_cms_unavailable(); // termina con 503, salvo GEP_SITIO_SIN_CMS
            return false;
        }
        if ( ! defined( 'WP_USE_THEMES' ) ) define( 'WP_USE_THEMES', false );
        require_once $wp_load;
        gep_site_media_start(); // v9.21.4: dominio y fotos livianas
        return defined( 'ABSPATH' );
    }
}
