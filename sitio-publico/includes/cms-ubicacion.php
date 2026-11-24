<?php
/**
 * GEP 9.27.1 · Operación Motor · Ubicación EXPLÍCITA del CMS (WordPress).
 *
 * El sitio público ya no busca WordPress en cualquier carpeta: solo en la
 * carpeta «motor». Así nunca carga por accidente una instalación antigua
 * (/gep/wordpress/), un respaldo (/gep/motor-respaldo/) u otra copia.
 *
 * Rutas que se aceptan, en este orden (las dos se llaman exactamente «motor»):
 *   1. <carpeta del sitio>/motor/          servidor: /gep/ + /gep/motor/
 *   2. <carpeta superior>/motor/           copia local con el sitio en GEP_LEP/
 * Para otra estructura se puede definir GEP_CMS_RUTA (ruta absoluta a la
 * carpeta de WordPress) ANTES de cargar este archivo; no hace falta hoy.
 */

if ( ! defined( 'GEP_CMS_CARPETA' ) ) {
    define( 'GEP_CMS_CARPETA', 'motor' );
}

if ( ! function_exists( 'gep_site_cms_wp_load' ) ) {
    /** Ruta de wp-load.php del CMS o '' si no está. */
    function gep_site_cms_wp_load() {
        $site_root = dirname( __DIR__ );
        $dirs      = array();
        if ( defined( 'GEP_CMS_RUTA' ) && GEP_CMS_RUTA ) {
            $dirs[] = rtrim( (string) GEP_CMS_RUTA, '/\\' );
        } else {
            $dirs[] = $site_root . '/' . GEP_CMS_CARPETA;
            $dirs[] = dirname( $site_root ) . '/' . GEP_CMS_CARPETA;
        }
        foreach ( $dirs as $dir ) {
            // Debe ser un WordPress completo, no una carpeta vacía o a medio subir.
            if ( is_file( $dir . '/wp-load.php' ) && is_file( $dir . '/wp-config.php' ) && is_file( $dir . '/wp-includes/version.php' ) ) {
                return $dir . '/wp-load.php';
            }
        }
        return '';
    }

    /**
     * Respuesta controlada cuando el CMS no está: 503 sin rutas ni detalles.
     * Para una copia de demostración sin WordPress: define( 'GEP_SITIO_SIN_CMS', true ).
     */
    function gep_site_cms_unavailable() {
        if ( defined( 'GEP_SITIO_SIN_CMS' ) && GEP_SITIO_SIN_CMS ) {
            return; // Muestra el contenido de demostración incluido en las plantillas.
        }
        error_log( 'GEP: no se encontró el CMS en la carpeta «' . GEP_CMS_CARPETA . '» (revisar instalación).' );
        if ( ! headers_sent() ) {
            http_response_code( 503 );
            header( 'Retry-After: 600' );
            header( 'Content-Type: text/html; charset=utf-8' );
            header( 'Cache-Control: no-store' );
        }
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>GEP · En mantenimiento</title>'
            . '<style>body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4f6f2;color:#1d2b22;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:16px}main{max-width:520px;background:#fff;border-radius:14px;padding:28px 24px;box-shadow:0 6px 24px rgba(0,0,0,.08)}h1{font-size:1.35rem;margin:0 0 10px;color:#1f5130}p{margin:0;line-height:1.5}</style></head>'
            . '<body><main><h1>Sitio en mantenimiento</h1><p>El sitio del Laboratorio GEP está en una actualización breve. Vuelve a intentarlo en unos minutos.</p></main></body></html>';
        exit;
    }
}
