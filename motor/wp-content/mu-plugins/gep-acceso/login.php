<?php
/**
 * GEP · Acceso seguro — límite de intentos, mensajes genéricos y recuperación
 * de contraseña sin revelar qué cuentas existen.
 *
 * Estrategia (no depende de la IP, porque detrás del proxy de la Universidad
 * REMOTE_ADDR puede ser la misma para todos):
 *   - Los fallos se cuentan por CUENTA. Tras N fallos en la ventana, la cuenta
 *     queda bloqueada X minutos en equipos no reconocidos (15, 30, 60... hasta el tope).
 *   - Un navegador donde esa persona ya inició sesión completa (con segundo
 *     factor si corresponde) recibe una cookie firmada «equipo reconocido».
 *     En ese navegador los fallos se cuentan aparte: un atacante que bloquea la
 *     cuenta desde fuera NO bloquea a la persona en su computador habitual.
 *   - Restablecer la contraseña por correo levanta el bloqueo de la cuenta.
 *   - Opcional: límite por IP solo si se configura un proxy de confianza.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! gep_acceso_ajuste( 'login' ) ) {
    return;
}

define( 'GEP_ACCESO_COOKIE_EQUIPO', 'gep_acceso_equipo' );
define( 'GEP_ACCESO_DIAS_EQUIPO', 90 );

function gep_acceso_mensaje_credenciales() {
    return '<strong>Error:</strong> el usuario o la contraseña no son correctos.' . ' <a href="' . esc_url( wp_lostpassword_url() ) . '">¿Olvidaste tu contraseña?</a>';
}

function gep_acceso_mensaje_bloqueo() {
    return '<strong>Acceso detenido temporalmente.</strong> Hubo demasiados intentos fallidos. Espera unos minutos, entra desde el computador donde sueles trabajar o usa <a href="' . esc_url( wp_lostpassword_url() ) . '">«¿Olvidaste tu contraseña?»</a>.';
}

/** Busca la cuenta escrita (usuario o correo). No revela nada: solo se usa internamente. */
function gep_acceso_usuario_por_nombre( $nombre ) {
    $nombre = trim( wp_unslash( (string) $nombre ) );
    if ( '' === $nombre ) {
        return false;
    }
    $u = get_user_by( 'login', $nombre );
    if ( ! $u && is_email( $nombre ) ) {
        $u = get_user_by( 'email', $nombre );
    }
    return $u ? $u : false;
}

/* ---- Cookie de equipo reconocido ---------------------------------------- */

function gep_acceso_firma_equipo( $user, $expira ) {
    // Incluye un fragmento del hash de la contraseña: si la contraseña cambia, la cookie deja de valer.
    $frag = substr( (string) $user->user_pass, 8, 4 );
    return hash_hmac( 'sha256', $user->ID . '|' . $expira . '|' . $frag, wp_salt( 'secure_auth' ) );
}

/** ID del usuario para quien este navegador está reconocido, o 0. */
function gep_acceso_equipo_reconocido() {
    if ( empty( $_COOKIE[ GEP_ACCESO_COOKIE_EQUIPO ] ) ) {
        return 0;
    }
    $partes = explode( '|', (string) $_COOKIE[ GEP_ACCESO_COOKIE_EQUIPO ] );
    if ( 3 !== count( $partes ) ) {
        return 0;
    }
    list( $id, $expira, $firma ) = $partes;
    $id     = (int) $id;
    $expira = (int) $expira;
    if ( $id <= 0 || $expira < time() ) {
        return 0;
    }
    $user = get_userdata( $id );
    if ( ! $user || ! hash_equals( gep_acceso_firma_equipo( $user, $expira ), (string) $firma ) ) {
        return 0;
    }
    return $id;
}

/**
 * La cookie se entrega cuando la persona YA tiene sesión completa (después del
 * segundo factor, si el plugin Two Factor lo pide), no en el momento de escribir la contraseña.
 */
function gep_acceso_entregar_cookie_equipo() {
    if ( headers_sent() || ! is_user_logged_in() ) {
        return;
    }
    $user = wp_get_current_user();
    if ( ! user_can( $user, 'edit_posts' ) ) {
        return; // Solo cuentas que editan el sitio.
    }
    if ( gep_acceso_equipo_reconocido() === (int) $user->ID ) {
        return;
    }
    $expira = time() + GEP_ACCESO_DIAS_EQUIPO * DAY_IN_SECONDS;
    $valor  = $user->ID . '|' . $expira . '|' . gep_acceso_firma_equipo( $user, $expira );
    setcookie( GEP_ACCESO_COOKIE_EQUIPO, $valor, array(
        'expires'  => $expira,
        'path'     => SITECOOKIEPATH,
        'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ) );
    $_COOKIE[ GEP_ACCESO_COOKIE_EQUIPO ] = $valor;
}
add_action( 'admin_init', 'gep_acceso_entregar_cookie_equipo', 1 );

/* ---- Claves de conteo para un intento ----------------------------------- */

/**
 * @return array lista de array( clave, limite, etiqueta )
 */
function gep_acceso_claves_intento( $nombre ) {
    $user   = gep_acceso_usuario_por_nombre( $nombre );
    $limite = (int) gep_acceso_ajuste( 'intentos' );
    $claves = array();
    if ( $user ) {
        if ( gep_acceso_equipo_reconocido() === (int) $user->ID ) {
            $token    = isset( $_COOKIE[ GEP_ACCESO_COOKIE_EQUIPO ] ) ? (string) $_COOKIE[ GEP_ACCESO_COOKIE_EQUIPO ] : '';
            $claves[] = array( 'd:' . $user->ID . ':' . gep_acceso_huella( $token ), $limite, $user->user_login . ' (equipo reconocido)' );
        } else {
            $claves[] = array( 'c:' . $user->ID, $limite, $user->user_login );
        }
    } else {
        // Nombres inexistentes se bloquean igual que los reales: así el bloqueo no delata qué cuentas existen.
        $claves[] = array( 'n:' . gep_acceso_huella( strtolower( trim( wp_unslash( (string) $nombre ) ) ) ), $limite, '(inexistente)' );
    }
    $ip  = gep_acceso_ip();
    $max = (int) gep_acceso_ajuste( 'ip_limite' );
    if ( $max > 0 && $ip['fiable'] && '' !== $ip['ip'] ) {
        $claves[] = array( 'i:' . gep_acceso_huella( $ip['ip'] ), $max, 'IP ' . gep_acceso_ip_recortada( $ip['ip'] ) );
    }
    return $claves;
}

/* ---- Bloqueo (antes de aceptar la contraseña) ---------------------------- */

add_filter( 'authenticate', function ( $user, $username, $password ) {
    if ( '' === trim( (string) $username ) || '' === (string) $password ) {
        return $user; // WordPress se encarga de «falta usuario/contraseña».
    }
    foreach ( gep_acceso_claves_intento( $username ) as $k ) {
        if ( gep_acceso_bloqueo_restante( $k[0] ) > 0 ) {
            // Incluso con la contraseña correcta: si no, el bloqueo no serviría.
            return new WP_Error( 'gep_acceso_bloqueado', gep_acceso_mensaje_bloqueo() );
        }
    }
    return $user;
}, 99, 3 );

/* ---- Mensaje genérico: no decir si falló el usuario o la contraseña ------ */

add_filter( 'authenticate', function ( $user ) {
    if ( is_wp_error( $user ) && in_array( $user->get_error_code(), array( 'invalid_username', 'invalid_email', 'incorrect_password', 'authentication_failed' ), true ) ) {
        return new WP_Error( 'gep_acceso_credenciales', gep_acceso_mensaje_credenciales() );
    }
    return $user;
}, 100 );

// Que la caja de inicio de sesión «tiemble» igual que con el mensaje original.
add_filter( 'shake_error_codes', function ( $codigos ) {
    $codigos[] = 'gep_acceso_credenciales';
    $codigos[] = 'gep_acceso_bloqueado';
    return $codigos;
} );

/* ---- Contar fallos ------------------------------------------------------- */

add_action( 'wp_login_failed', function ( $username, $error = null ) {
    if ( is_wp_error( $error ) && 'gep_acceso_bloqueado' === $error->get_error_code() ) {
        return; // Un intento durante el bloqueo no alarga el bloqueo (evita bloqueos eternos).
    }
    $user   = gep_acceso_usuario_por_nombre( $username );
    $cuenta = $user ? $user->user_login : '(inexistente)';
    gep_acceso_registrar( 'fallo', $cuenta, 'contraseña incorrecta' );
    foreach ( gep_acceso_claves_intento( $username ) as $k ) {
        $minutos = gep_acceso_sumar_fallo( $k[0], $k[1], $k[2] );
        if ( $minutos ) {
            gep_acceso_registrar( 'bloqueo', $k[2], 'bloqueo de ' . $minutos . ' min' );
        }
    }
}, 10, 2 );

/* ---- Inicio correcto: limpiar contadores y registrar --------------------- */

add_action( 'wp_login', function ( $login, $user ) {
    if ( ! $user instanceof WP_User ) {
        return;
    }
    // Se limpia el contador que usó este intento (cuenta o equipo reconocido). El de IP expira solo.
    foreach ( gep_acceso_claves_intento( $login ) as $k ) {
        if ( 0 === strpos( $k[0], 'c:' ) || 0 === strpos( $k[0], 'd:' ) ) {
            gep_acceso_limpiar_clave( $k[0] );
        }
    }
    if ( user_can( $user, 'edit_posts' ) ) {
        $rol = $user->roles ? implode( ',', $user->roles ) : '';
        gep_acceso_registrar( 'acceso', $user->user_login, 'contraseña correcta (' . $rol . ')' );
    }
}, 5, 2 );

/* ---- Recuperación de contraseña sin enumeración -------------------------- */

add_action( 'lostpassword_post', function ( $errors, $user_data ) {
    if ( empty( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] || wp_doing_ajax() ) {
        return; // Envíos hechos por un administrador desde el panel siguen igual.
    }
    if ( is_wp_error( $errors ) && $errors->has_errors() ) {
        return; // Campo vacío, etc.: WordPress muestra su aviso, que no revela cuentas.
    }
    $enviar = false;
    if ( $user_data instanceof WP_User ) {
        $n = gep_acceso_contar( 'r:' . $user_data->ID, HOUR_IN_SECONDS );
        if ( $n <= 3 ) {
            $enviar = true;
            gep_acceso_registrar( 'recuperacion', $user_data->user_login, 'pedido de nueva contraseña' );
        } else {
            gep_acceso_registrar( 'recuperacion', $user_data->user_login, 'pedido ignorado: más de 3 en una hora' );
        }
    } else {
        gep_acceso_registrar( 'recuperacion', '(inexistente)', 'pedido para cuenta inexistente' );
    }
    if ( $enviar ) {
        // Si el servidor no logra enviar el correo, WordPress mostraría «no se pudo enviar»
        // solo para cuentas que existen (eso las delataría). Se anota y se muestra la pantalla normal.
        add_action( 'wp_mail_failed', function () use ( $user_data ) {
            gep_acceso_registrar( 'config', $user_data->user_login, 'el servidor no pudo enviar el correo de nueva contraseña' );
            gep_acceso_ir_a_confirmacion();
        } );
        return; // WordPress envía el correo y muestra su pantalla habitual.
    }
    gep_acceso_ir_a_confirmacion();
}, 10, 2 );

/** Misma pantalla que cuando el correo sí se envía: no se sabe si la cuenta existe. */
function gep_acceso_ir_a_confirmacion() {
    $destino = ! empty( $_REQUEST['redirect_to'] ) ? wp_unslash( (string) $_REQUEST['redirect_to'] ) : 'wp-login.php?checkemail=confirm';
    wp_safe_redirect( $destino );
    exit;
}

// Tras restablecer la contraseña por correo, la cuenta se desbloquea.
add_action( 'after_password_reset', function ( $user ) {
    if ( $user instanceof WP_User ) {
        gep_acceso_limpiar_clave( 'c:' . $user->ID );
        gep_acceso_registrar( 'recuperacion', $user->user_login, 'contraseña restablecida; bloqueo levantado' );
    }
} );
