<?php
/**
 * GEP · Acceso seguro — segundo factor obligatorio.
 *
 * El código TOTP y los códigos de respaldo los maneja el plugin «Two Factor»
 * (WordPress.org, mantenido por colaboradores de WordPress). Este módulo NO
 * implementa criptografía: solo exige que las cuentas indicadas lo tengan
 * configurado y cierra las «contraseñas de aplicación», que permiten entrar
 * sin segundo factor.
 *
 * Nunca deja a nadie fuera: quien aún no lo configuró entra con su contraseña
 * y queda limitado a su perfil hasta configurarlo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! gep_acceso_ajuste( 'exigir_2fa' ) && ! gep_acceso_ajuste( 'exigir_2fa_editor' ) ) {
    return;
}

// Contraseñas de aplicación: entran por la API sin segundo factor. El GEP no las usa.
add_filter( 'wp_is_application_passwords_available', '__return_false' );

/** ¿El usuario ya tiene un segundo factor activo? Solo lo sabe el plugin Two Factor. */
function gep_acceso_usuario_tiene_2fa( $user_id ) {
    if ( ! gep_acceso_two_factor_activo() ) {
        return false;
    }
    return (bool) Two_Factor_Core::is_user_using_two_factor( (int) $user_id );
}

/** Pantallas permitidas mientras la persona configura su segundo factor. */
function gep_acceso_pantalla_permitida_sin_2fa() {
    $pagina = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';
    if ( 'profile.php' === $pagina ) {
        return true;
    }
    if ( 'user-edit.php' === $pagina && isset( $_GET['user_id'] ) && (int) $_GET['user_id'] === get_current_user_id() ) {
        return true;
    }
    return false;
}

/** ¿La persona con sesión debe configurar su segundo factor antes de seguir? */
function gep_acceso_falta_2fa_actual() {
    if ( ! is_user_logged_in() || ! gep_acceso_two_factor_activo() ) {
        return false; // Sin el plugin no se puede exigir; el panel y el aviso lo indican.
    }
    $user = wp_get_current_user();
    return gep_acceso_usuario_requiere_2fa( $user ) && ! gep_acceso_usuario_tiene_2fa( $user->ID );
}

/** Acciones AJAX que siguen funcionando mientras se configura (perfil, Heartbeat, el propio Two Factor). */
function gep_acceso_ajax_permitido_sin_2fa( $accion ) {
    if ( in_array( $accion, array( 'heartbeat', 'generate-password', 'destroy-sessions', 'wp-remove-post-lock' ), true ) ) {
        return true;
    }
    return false !== strpos( $accion, 'two_factor' ) || false !== strpos( $accion, 'two-factor' );
}

add_action( 'admin_init', function () {
    if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || ! gep_acceso_falta_2fa_actual() ) {
        return;
    }
    if ( wp_doing_ajax() ) {
        $accion = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
        if ( gep_acceso_ajax_permitido_sin_2fa( $accion ) ) {
            return;
        }
        wp_send_json_error( array( 'message' => 'Primero activa el segundo factor en tu perfil.' ), 403 );
    }
    if ( gep_acceso_pantalla_permitida_sin_2fa() ) {
        return;
    }
    wp_safe_redirect( add_query_arg( 'gep_2fa', 'pendiente', admin_url( 'profile.php' ) ) );
    exit;
}, 2 );

// API REST con sesión: mientras falte el segundo factor, solo las rutas del propio Two Factor.
add_filter( 'rest_pre_dispatch', function ( $resultado, $servidor, $pedido ) {
    if ( null !== $resultado || ! gep_acceso_falta_2fa_actual() ) {
        return $resultado;
    }
    if ( 0 === strpos( $pedido->get_route(), '/two-factor/' ) ) {
        return $resultado;
    }
    return new WP_Error( 'rest_gep_2fa', 'Primero activa el segundo factor en tu perfil.', array( 'status' => 403 ) );
}, 9, 3 );

add_action( 'admin_notices', function () {
    $user = wp_get_current_user();
    if ( ! gep_acceso_usuario_requiere_2fa( $user ) ) {
        return;
    }
    if ( ! gep_acceso_two_factor_activo() ) {
        if ( current_user_can( 'manage_options' ) ) {
            echo '<div class="notice notice-error"><p><strong>Seguridad GEP:</strong> está marcado «Exigir segundo factor», pero el plugin <strong>Two Factor</strong> no está activo. Mientras tanto NO se exige segundo factor. Instálalo desde Plugins → Añadir nuevo (autor: WordPress.org Contributors).</p></div>';
        }
        if ( false === get_transient( 'gep_acceso_aviso_2fa_falta' ) ) {
            set_transient( 'gep_acceso_aviso_2fa_falta', 1, DAY_IN_SECONDS );
            gep_acceso_registrar( 'config', '', 'se exige segundo factor pero el plugin Two Factor no está activo' );
        }
        return;
    }
    if ( ! gep_acceso_usuario_tiene_2fa( $user->ID ) ) {
        echo '<div class="notice notice-warning"><p><strong>Seguridad GEP:</strong> para seguir usando el editor debes activar el segundo factor. Más abajo, en «Two-Factor Options», marca <strong>Authenticator App</strong>, escanea el código con tu teléfono, escribe el código de 6 dígitos y genera también los <strong>Backup Verification Codes</strong> (guárdalos en papel). Luego pulsa «Actualizar perfil».</p></div>';
    }
} );
