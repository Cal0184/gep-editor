<?php
/**
 * GEP Editor 10.0.0 · Registro técnico de errores.
 *
 * Guarda los últimos 100 problemas técnicos del editor (no los de seguridad,
 * que van en Herramientas → Seguridad GEP → Registro). Se ve en
 * Contenido GEP → Diagnóstico, solo para administradores.
 *
 * Nunca guarda contraseñas, claves, contenido de las páginas ni rutas completas
 * del servidor: las rutas se acortan desde la carpeta del sitio y cualquier
 * texto con forma de clave se reemplaza por «[oculto]».
 *
 * Se guarda en la opción «gep_registro_tecnico» (sin carga automática).
 * Borrar la opción no afecta al contenido.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const GEP_REGISTRO_OPCION = 'gep_registro_tecnico';
const GEP_REGISTRO_MAXIMO = 100;

/** Acorta rutas y oculta datos sensibles de un texto. */
function gep_registro_limpiar( $texto ) {
    $texto = wp_strip_all_tags( (string) $texto );
    $raices = array_unique( array_filter( array(
        wp_normalize_path( dirname( ABSPATH ) ),
        wp_normalize_path( untrailingslashit( ABSPATH ) ),
    ) ) );
    $texto = wp_normalize_path( $texto );
    foreach ( $raices as $raiz ) {
        if ( strlen( $raiz ) > 1 ) {
            $texto = str_replace( $raiz, '…', $texto );
        }
    }
    // Pares tipo «password=xxx», «clave: xxx», «nonce=xxx», «token=xxx».
    $texto = preg_replace( '/\b(pass(word)?|pwd|clave|contrase(ñ|n)a|secret|token|nonce|key|auth)\b\s*[=:]\s*\S+/iu', '$1=[oculto]', $texto );
    // Correos electrónicos.
    $texto = preg_replace( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[correo]', $texto );
    return mb_substr( trim( preg_replace( '/\s+/', ' ', $texto ) ), 0, 400 );
}

/**
 * Agrega una entrada al registro.
 *
 * @param string $tipo    Una palabra: guardado, sesion, respaldo, doi, imagen, correo, php…
 * @param string $mensaje Qué pasó, en lenguaje simple.
 * @param string $donde   Pantalla o página relacionada (opcional).
 */
function gep_registro( $tipo, $mensaje, $donde = '' ) {
    $lista = get_option( GEP_REGISTRO_OPCION, array() );
    $lista = is_array( $lista ) ? $lista : array();
    $nuevo = array(
        't'     => time(),
        'tipo'  => sanitize_key( $tipo ),
        'msg'   => gep_registro_limpiar( $mensaje ),
        'donde' => gep_registro_limpiar( $donde ),
        'user'  => function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0,
        'v'     => defined( 'GEP_EDITOR_VERSION' ) ? GEP_EDITOR_VERSION : '',
    );
    // El mismo problema repetido seguido no llena el registro: se cuenta.
    if ( $lista && $lista[0]['tipo'] === $nuevo['tipo'] && $lista[0]['msg'] === $nuevo['msg'] && $nuevo['t'] - (int) $lista[0]['t'] < 3600 ) {
        $lista[0]['n'] = ( isset( $lista[0]['n'] ) ? (int) $lista[0]['n'] : 1 ) + 1;
        $lista[0]['t'] = $nuevo['t'];
    } else {
        array_unshift( $lista, $nuevo );
    }
    $lista = array_slice( $lista, 0, GEP_REGISTRO_MAXIMO );
    if ( false === get_option( GEP_REGISTRO_OPCION, false ) ) {
        add_option( GEP_REGISTRO_OPCION, $lista, '', 'no' );
    } else {
        update_option( GEP_REGISTRO_OPCION, $lista, false );
    }
}

function gep_registro_leer() {
    $lista = get_option( GEP_REGISTRO_OPCION, array() );
    return is_array( $lista ) ? $lista : array();
}

function gep_registro_vaciar() {
    delete_option( GEP_REGISTRO_OPCION );
}

/**
 * Errores fatales de PHP dentro del código GEP (plugin, gep-acceso, sitio
 * público). Los de otros plugins o de WordPress no se registran aquí: ya los
 * maneja el «modo de recuperación» de WordPress.
 */
function gep_registro_fatal() {
    $e = error_get_last();
    if ( ! $e || ! in_array( (int) $e['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
        return;
    }
    $archivo = wp_normalize_path( (string) $e['file'] );
    $propio  = false !== strpos( $archivo, '/gep-editor-demo/' ) || false !== strpos( $archivo, '/mu-plugins/gep-' ) || false !== strpos( $archivo, '/gep-media-organizer/' );
    if ( ! $propio && defined( 'ABSPATH' ) ) {
        $sitio  = wp_normalize_path( dirname( ABSPATH ) ) . '/';
        $propio = 0 === strpos( $archivo, $sitio ) && false === strpos( $archivo, wp_normalize_path( ABSPATH ) );
    }
    if ( ! $propio ) {
        return;
    }
    try {
        gep_registro( 'php', 'Error fatal: ' . $e['message'] . ' (' . $archivo . ':' . (int) $e['line'] . ')', isset( $_SERVER['REQUEST_URI'] ) ? strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '' );
    } catch ( Throwable $t ) {
        // Si la base de datos tampoco responde, no hay dónde guardar: se ignora.
    }
}
register_shutdown_function( 'gep_registro_fatal' );

/* Correos que WordPress no pudo enviar (recuperar contraseña, avisos). */
add_action( 'wp_mail_failed', function ( $error ) {
    if ( is_wp_error( $error ) ) {
        gep_registro( 'correo', 'No se pudo enviar un correo: ' . $error->get_error_message() );
    }
} );
