<?php
/**
 * GEP · Acceso seguro — registro de eventos de seguridad.
 *
 * Siempre activo (no bloquea nada; solo anota). Se guarda en la base de datos
 * (opciones gep_acceso_registro y gep_acceso_registro_fallos, máx. 200 cada una,
 * 30 días). Nunca se anotan contraseñas, códigos, cookies ni nombres de cuentas
 * inexistentes. Solo lo ve un administrador en Herramientas → Seguridad GEP.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function gep_acceso_usuario_actual_nombre() {
    $u = wp_get_current_user();
    return $u && $u->exists() ? $u->user_login : '(sistema)';
}

add_action( 'activated_plugin', function ( $plugin ) {
    gep_acceso_registrar( 'plugin', gep_acceso_usuario_actual_nombre(), 'activado: ' . $plugin );
} );
add_action( 'deactivated_plugin', function ( $plugin ) {
    gep_acceso_registrar( 'plugin', gep_acceso_usuario_actual_nombre(), 'desactivado: ' . $plugin );
} );
add_action( 'deleted_plugin', function ( $plugin, $ok ) {
    if ( $ok ) {
        gep_acceso_registrar( 'plugin', gep_acceso_usuario_actual_nombre(), 'eliminado: ' . $plugin );
    }
}, 10, 2 );
add_action( 'switch_theme', function ( $nombre ) {
    gep_acceso_registrar( 'plugin', gep_acceso_usuario_actual_nombre(), 'tema cambiado a: ' . $nombre );
} );

add_action( 'set_user_role', function ( $user_id, $rol, $anteriores ) {
    $u = get_userdata( $user_id );
    gep_acceso_registrar( 'rol', $u ? $u->user_login : '#' . $user_id, 'rol: ' . implode( ',', (array) $anteriores ) . ' → ' . $rol . ' (por ' . gep_acceso_usuario_actual_nombre() . ')' );
}, 10, 3 );
add_action( 'add_user_role', function ( $user_id, $rol ) {
    if ( 'administrator' === $rol ) {
        $u = get_userdata( $user_id );
        gep_acceso_registrar( 'rol', $u ? $u->user_login : '#' . $user_id, 'se agregó rol administrador (por ' . gep_acceso_usuario_actual_nombre() . ')' );
    }
}, 10, 2 );

add_action( 'user_register', function ( $user_id ) {
    $u = get_userdata( $user_id );
    if ( $u ) {
        gep_acceso_registrar( 'usuario', $u->user_login, 'cuenta creada (' . implode( ',', $u->roles ) . ') por ' . gep_acceso_usuario_actual_nombre() );
    }
} );
add_action( 'delete_user', function ( $user_id ) {
    $u = get_userdata( $user_id );
    gep_acceso_registrar( 'usuario', $u ? $u->user_login : '#' . $user_id, 'cuenta eliminada por ' . gep_acceso_usuario_actual_nombre() );
} );
add_action( 'after_password_reset', function ( $user ) {
    // El módulo de inicio de sesión también lo anota cuando está activo; aquí solo si está apagado.
    if ( ! gep_acceso_ajuste( 'login' ) && $user instanceof WP_User ) {
        gep_acceso_registrar( 'recuperacion', $user->user_login, 'contraseña restablecida' );
    }
} );

// Cambios en el segundo factor (metadato del plugin Two Factor). Solo se anota que cambió, no su contenido.
$gep_acceso_meta_2fa = function ( $meta_id, $user_id, $clave ) {
    if ( '_two_factor_enabled_providers' === $clave || '_two_factor_provider' === $clave ) {
        $u = get_userdata( $user_id );
        gep_acceso_registrar( 'dos_factores', $u ? $u->user_login : '#' . $user_id, 'configuración de segundo factor cambiada (por ' . gep_acceso_usuario_actual_nombre() . ')' );
    }
};
add_action( 'added_user_meta', $gep_acceso_meta_2fa, 10, 3 );
add_action( 'updated_user_meta', $gep_acceso_meta_2fa, 10, 3 );
add_action( 'deleted_user_meta', function ( $ids, $user_id, $clave ) {
    if ( '_two_factor_enabled_providers' === $clave ) {
        $u = get_userdata( $user_id );
        gep_acceso_registrar( 'dos_factores', $u ? $u->user_login : '#' . $user_id, 'segundo factor quitado (por ' . gep_acceso_usuario_actual_nombre() . ')' );
    }
}, 10, 3 );
unset( $gep_acceso_meta_2fa );

// Inicio de sesión de cuentas que editan, aunque el límite de intentos esté apagado.
add_action( 'wp_login', function ( $login, $user ) {
    if ( ! gep_acceso_ajuste( 'login' ) && $user instanceof WP_User && user_can( $user, 'edit_posts' ) ) {
        gep_acceso_registrar( 'acceso', $user->user_login, 'contraseña correcta (' . implode( ',', $user->roles ) . ')' );
    }
}, 5, 2 );

// Segundo factor completado (acción del plugin Two Factor, si existe en la versión instalada).
add_action( 'two_factor_user_authenticated', function ( $user ) {
    if ( $user instanceof WP_User ) {
        gep_acceso_registrar( 'acceso', $user->user_login, 'segundo factor correcto' );
    }
} );
