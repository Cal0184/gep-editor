<?php
/**
 * GEP · Ajustes del CMS en https://gep.uchile.cl/gep/  ·  versión 10.0.0 (antes 9.27.1 «Operación Motor»).
 *
 * Se carga desde wp-config.php con UNA línea (ya está puesta desde el 11-11):
 *
 *     require_once __DIR__ . '/gep-config-migracion.php';
 *
 * Este archivo NO contiene usuario ni contraseña de la base de datos.
 * Historial: 10-11 creado para /gep/wordpress/; 9.27.1 la carpeta pasa a /gep/motor/.
 * Reversión: volver a subir la copia anterior de este archivo (la de /gep/wordpress/).
 */

// Si alguien abre este archivo directo en el navegador, no hace nada.
if ( ! defined( 'DB_NAME' ) ) {
    http_response_code( 404 );
    exit;
}

/* 0) Carpeta oficial del CMS. Es la ÚNICA ubicación válida.
 *    Si estos archivos se ejecutan desde otra carpeta (la antigua /gep/wordpress/,
 *    una copia o un respaldo), WordPress NO arranca: responde 503 sin mostrar
 *    rutas. Así nunca hay dos WordPress escribiendo en la misma base. */
if ( ! defined( 'GEP_CMS_CARPETA' ) ) {
    define( 'GEP_CMS_CARPETA', 'motor' );
}
if ( basename( __DIR__ ) !== GEP_CMS_CARPETA ) {
    if ( ! headers_sent() ) {
        http_response_code( 503 );
        header( 'Retry-After: 600' );
        header( 'Content-Type: text/html; charset=utf-8' );
        header( 'Cache-Control: no-store' );
        header( 'X-Robots-Tag: noindex' );
    }
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>GEP</title></head>'
        . '<body style="font-family:system-ui,sans-serif;padding:24px;max-width:560px;margin:auto"><h1 style="font-size:1.3rem">Esta dirección ya no está en uso</h1>'
        . '<p>El sitio del Laboratorio GEP sigue en <a href="/gep/">/gep/</a>.</p></body></html>';
    exit;
}

/* 0b) v10.0.0 · Instalador de WordPress bloqueado.
 *     Si la base de datos falla o queda vacía, WordPress manda a cualquier
 *     visitante a wp-admin/install.php, donde se puede crear un sitio nuevo
 *     con su propio administrador. Aquí esas pantallas responden «no
 *     disponible» siempre. Para una reinstalación planificada (muy rara):
 *     agrega en wp-config.php, ANTES de la línea que carga este archivo,
 *         define( 'GEP_PERMITIR_INSTALADOR', true );
 *     y quítala apenas termines. */
define( 'GEP_INSTALADOR_BLOQUEADO', true );
if ( ! ( defined( 'GEP_PERMITIR_INSTALADOR' ) && GEP_PERMITIR_INSTALADOR ) ) {
    $gep_script = isset( $_SERVER['SCRIPT_FILENAME'] ) ? str_replace( '\\', '/', (string) $_SERVER['SCRIPT_FILENAME'] ) : '';
    $gep_nombre = isset( $_SERVER['SCRIPT_NAME'] ) ? (string) $_SERVER['SCRIPT_NAME'] : '';
    if ( preg_match( '#/wp-admin/(install|setup-config)\.php$#', $gep_script ) || preg_match( '#/wp-admin/(install|setup-config)\.php$#', $gep_nombre ) ) {
        if ( ! headers_sent() ) {
            http_response_code( 503 );
            header( 'Retry-After: 3600' );
            header( 'Content-Type: text/html; charset=utf-8' );
            header( 'Cache-Control: no-store' );
            header( 'X-Robots-Tag: noindex' );
        }
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>GEP</title></head>'
            . '<body style="font-family:system-ui,sans-serif;padding:24px;max-width:560px;margin:auto"><h1 style="font-size:1.3rem">Sitio en mantenimiento</h1>'
            . '<p>El sitio del Laboratorio GEP no está disponible en este momento. Vuelve a intentarlo más tarde.</p></body></html>';
        exit;
    }
    unset( $gep_script, $gep_nombre );
}

/* 1) Dirección de WordPress. Única definición de WP_HOME y WP_SITEURL:
 *    no las repitas en wp-config.php. */
if ( ! defined( 'WP_HOME' ) ) {
    define( 'WP_HOME', 'https://gep.uchile.cl/gep/motor' );
}
if ( ! defined( 'WP_SITEURL' ) ) {
    define( 'WP_SITEURL', 'https://gep.uchile.cl/gep/motor' );
}

/* 2) HTTPS detrás del proxy de la Universidad.
 *    En el servidor es TRUE (diagnóstico del 11-11: CASO 4, el proxy envía
 *    X-Forwarded-Proto: https). WordPress cree que la conexión es HTTPS SOLO
 *    cuando el proxy lo avisa con X-Forwarded-Proto: https o X-Forwarded-Ssl: on. */
if ( ! defined( 'GEP_PROXY_HTTPS' ) ) {
    define( 'GEP_PROXY_HTTPS', true );
}
if ( GEP_PROXY_HTTPS && ! ( isset( $_SERVER['HTTPS'] ) && in_array( strtolower( (string) $_SERVER['HTTPS'] ), array( 'on', '1' ), true ) ) ) {
    $gep_proto = isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ? strtolower( trim( current( explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ) ) : '';
    $gep_fssl  = isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) ? strtolower( trim( (string) $_SERVER['HTTP_X_FORWARDED_SSL'] ) ) : '';
    if ( 'https' === $gep_proto || 'on' === $gep_fssl ) {
        $_SERVER['HTTPS'] = 'on';
    }
    unset( $gep_proto, $gep_fssl );
}

/* 3) Seguridad: sin editor de archivos de temas/plugins dentro de wp-admin.
 *    (No afecta a GEP Editor ni a la Mediateca.) */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}
