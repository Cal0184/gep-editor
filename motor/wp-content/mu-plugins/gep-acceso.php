<?php
/**
 * Plugin Name: GEP · Acceso seguro
 * Description: Límite de intentos de inicio de sesión, mensajes genéricos, segundo factor obligatorio (con el plugin Two Factor), cierre del WordPress público y reducción de superficie. Cada parte se activa por separado en Herramientas → Seguridad GEP.
 * Version: 10.0.0
 *
 * RECUPERACIÓN DE EMERGENCIA (sin iniciar sesión, solo FileZilla):
 *   - Apagar TODO: renombrar este archivo a «gep-acceso.php.off».
 *   - Apagar una sola parte: renombrar su archivo dentro de la carpeta
 *     «gep-acceso/» (p. ej. login.php -> login.php.off).
 *   - También se puede poner en wp-config.php: define( 'GEP_ACCESO_APAGADO', true );
 * Ninguna de estas acciones borra datos: al volver a activar, todo sigue igual.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GEP_ACCESO_VERSION', '10.0.0' );
define( 'GEP_ACCESO_DIR', __DIR__ . '/gep-acceso/' );

if ( ! is_file( GEP_ACCESO_DIR . 'comun.php' ) ) {
    return; // Sin la carpeta de módulos no se hace nada.
}
require_once GEP_ACCESO_DIR . 'comun.php';

if ( gep_acceso_apagado_por_constante() ) {
    // Interruptor de emergencia en wp-config.php: solo queda el panel informativo.
    if ( is_file( GEP_ACCESO_DIR . 'panel.php' ) ) {
        require_once GEP_ACCESO_DIR . 'panel.php';
    }
    return;
}

foreach ( array( 'registro', 'login', 'dos-factores', 'publico', 'superficie', 'panel' ) as $gep_acceso_modulo ) {
    if ( is_file( GEP_ACCESO_DIR . $gep_acceso_modulo . '.php' ) ) {
        require_once GEP_ACCESO_DIR . $gep_acceso_modulo . '.php';
    }
}
unset( $gep_acceso_modulo );
