<?php
/**
 * GEP · Acceso seguro — funciones comunes: ajustes, IP, contadores y registro.
 * No guarda contraseñas, códigos, cookies ni secretos.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** ¿Interruptor de emergencia en wp-config.php? */
function gep_acceso_apagado_por_constante() {
    return defined( 'GEP_ACCESO_APAGADO' ) && GEP_ACCESO_APAGADO;
}

/** Valores por defecto: al instalar, nada bloquea (Etapa A). */
function gep_acceso_ajustes_por_defecto() {
    return array(
        'login'             => 0,   // Etapa B: límite de intentos + mensajes genéricos.
        'intentos'          => 5,   // Fallos permitidos dentro de la ventana.
        'ventana_min'       => 15,  // Minutos en que se cuentan los fallos.
        'bloqueo_min'       => 15,  // Primer bloqueo; se duplica si se repite.
        'bloqueo_max_min'   => 120, // Tope del bloqueo progresivo.
        'exigir_2fa'        => 0,   // Etapa B: administradores deben configurar Two Factor.
        'exigir_2fa_editor' => 0,   // Opcional: también el rol Editor GEP.
        'publico'           => 0,   // Etapa C: WordPress público -> /gep/.
        'publico_301'       => 0,   // 0 = 302 (pruebas), 1 = 301 (definitivo).
        'superficie'        => 0,   // Etapa D: XML-RPC, comentarios, versión, oEmbed, sitemaps.
        'rest_anonimo'      => 0,   // Etapa D (opcional): API REST solo con sesión.
        'ip_cabecera'       => '',  // Cabecera con la IP real (vacío = no se confía en ninguna).
        'ip_proxies'        => '',  // IPs del proxy de confianza, separadas por comas.
        'ip_limite'         => 0,   // Fallos por IP antes de bloquear esa IP (0 = no se bloquea por IP).
    );
}

function gep_acceso_ajustes() {
    static $cache = null;
    if ( null !== $cache ) {
        return $cache;
    }
    $guardados = get_option( 'gep_acceso_ajustes', array() );
    $cache     = array_merge( gep_acceso_ajustes_por_defecto(), is_array( $guardados ) ? $guardados : array() );
    return $cache;
}

function gep_acceso_ajuste( $clave ) {
    $a = gep_acceso_ajustes();
    return isset( $a[ $clave ] ) ? $a[ $clave ] : null;
}

/** Limpia y guarda los ajustes (solo desde el panel, con nonce y manage_options). */
function gep_acceso_guardar_ajustes( $entrada ) {
    $base  = gep_acceso_ajustes_por_defecto();
    $nuevo = array();
    foreach ( array( 'login', 'exigir_2fa', 'exigir_2fa_editor', 'publico', 'publico_301', 'superficie', 'rest_anonimo' ) as $k ) {
        $nuevo[ $k ] = empty( $entrada[ $k ] ) ? 0 : 1;
    }
    $limites = array(
        'intentos'        => array( 3, 20 ),
        'ventana_min'     => array( 5, 120 ),
        'bloqueo_min'     => array( 1, 120 ),
        'bloqueo_max_min' => array( 5, 1440 ),
        'ip_limite'       => array( 0, 500 ),
    );
    foreach ( $limites as $k => $rango ) {
        $v           = isset( $entrada[ $k ] ) ? (int) $entrada[ $k ] : $base[ $k ];
        $nuevo[ $k ] = max( $rango[0], min( $rango[1], $v ) );
    }
    if ( $nuevo['bloqueo_max_min'] < $nuevo['bloqueo_min'] ) {
        $nuevo['bloqueo_max_min'] = $nuevo['bloqueo_min'];
    }
    $cab                  = isset( $entrada['ip_cabecera'] ) ? strtoupper( preg_replace( '/[^A-Za-z_]/', '', (string) $entrada['ip_cabecera'] ) ) : '';
    $nuevo['ip_cabecera'] = in_array( $cab, array( '', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP' ), true ) ? $cab : '';
    $proxies              = array();
    foreach ( preg_split( '/[\s,]+/', isset( $entrada['ip_proxies'] ) ? (string) $entrada['ip_proxies'] : '' ) as $ip ) {
        if ( '' !== $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $proxies[] = $ip;
        }
    }
    $nuevo['ip_proxies'] = implode( ',', array_slice( array_unique( $proxies ), 0, 10 ) );

    $antes = gep_acceso_ajustes();
    update_option( 'gep_acceso_ajustes', $nuevo, true );
    $cambios = array();
    foreach ( $nuevo as $k => $v ) {
        if ( (string) $antes[ $k ] !== (string) $v ) {
            $cambios[] = $k . '=' . $v;
        }
    }
    return $cambios;
}

/**
 * IP del cliente. Por defecto se usa REMOTE_ADDR, que detrás de un proxy puede
 * ser la IP del proxy (compartida por todos). Solo se lee una cabecera si
 * REMOTE_ADDR es uno de los proxies de confianza configurados.
 *
 * @return array{ip:string,fiable:bool}
 */
function gep_acceso_ip() {
    static $r = null;
    if ( null !== $r ) {
        return $r;
    }
    $remota  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $remota  = filter_var( $remota, FILTER_VALIDATE_IP ) ? $remota : '';
    $r       = array( 'ip' => $remota, 'fiable' => false );
    $cab     = (string) gep_acceso_ajuste( 'ip_cabecera' );
    $proxies = array_filter( explode( ',', (string) gep_acceso_ajuste( 'ip_proxies' ) ) );
    if ( '' === $cab || ! $proxies || ! in_array( $remota, $proxies, true ) || empty( $_SERVER[ $cab ] ) ) {
        return $r;
    }
    // X-Forwarded-For: "cliente, proxy1, proxy2". Se toma la última IP que no sea de un proxy propio.
    $lista = array_reverse( array_map( 'trim', explode( ',', (string) $_SERVER[ $cab ] ) ) );
    foreach ( $lista as $ip ) {
        if ( filter_var( $ip, FILTER_VALIDATE_IP ) && ! in_array( $ip, $proxies, true ) ) {
            $r = array( 'ip' => $ip, 'fiable' => true );
            break;
        }
    }
    return $r;
}

/** IP recortada para el registro (IPv4 /24, IPv6 /48): suficiente para diagnosticar, menos dato personal. */
function gep_acceso_ip_recortada( $ip ) {
    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
        $p = explode( '.', $ip );
        return $p[0] . '.' . $p[1] . '.' . $p[2] . '.x';
    }
    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
        $p = explode( ':', $ip );
        return implode( ':', array_slice( $p, 0, 3 ) ) . '::x';
    }
    return '';
}

/** Huella no reversible para claves de contadores (no se guarda el nombre escrito). */
function gep_acceso_huella( $texto ) {
    return substr( hash_hmac( 'sha256', (string) $texto, wp_salt( 'auth' ) ), 0, 24 );
}

/* ---------------------------------------------------------------------------
 * Contadores de fallos y bloqueos: una sola opción, con tope de tamaño.
 * ------------------------------------------------------------------------- */

define( 'GEP_ACCESO_MAX_CONTADORES', 2000 );

function gep_acceso_contadores_leer() {
    $c = get_option( 'gep_acceso_contadores', array() );
    return is_array( $c ) ? $c : array();
}

function gep_acceso_contadores_guardar( $c ) {
    $ahora = time();
    foreach ( $c as $k => $e ) {
        // Se borra lo que ya no cuenta: sin bloqueo vigente y sin actividad en 24 h.
        $ultimo = isset( $e['u'] ) ? (int) $e['u'] : 0;
        $hasta  = isset( $e['h'] ) ? (int) $e['h'] : 0;
        if ( $hasta < $ahora && $ultimo < $ahora - DAY_IN_SECONDS ) {
            unset( $c[ $k ] );
        }
    }
    if ( count( $c ) > GEP_ACCESO_MAX_CONTADORES ) {
        // Si hay que recortar, se conservan primero los bloqueos vigentes y luego lo más reciente
        // (así una avalancha de nombres inventados no borra el bloqueo de una cuenta real).
        uasort( $c, function ( $a, $b ) use ( $ahora ) {
            $ba = (int) $a['h'] > $ahora ? 1 : 0;
            $bb = (int) $b['h'] > $ahora ? 1 : 0;
            return $ba !== $bb ? $bb - $ba : (int) $b['u'] - (int) $a['u'];
        } );
        $c = array_slice( $c, 0, GEP_ACCESO_MAX_CONTADORES, true );
    }
    if ( $c ) {
        update_option( 'gep_acceso_contadores', $c, false );
    } else {
        delete_option( 'gep_acceso_contadores' );
    }
}

/** Segundos que le quedan de bloqueo a una clave (0 = libre). */
function gep_acceso_bloqueo_restante( $clave ) {
    $c = gep_acceso_contadores_leer();
    if ( empty( $c[ $clave ]['h'] ) ) {
        return 0;
    }
    return max( 0, (int) $c[ $clave ]['h'] - time() );
}

/**
 * Suma un fallo. Devuelve los minutos de bloqueo si este fallo provocó uno; si no, 0.
 * $limite: fallos permitidos dentro de la ventana.
 * $etiqueta: texto para el panel (usuario existente, «(inexistente)» o IP recortada).
 */
function gep_acceso_sumar_fallo( $clave, $limite, $etiqueta = '' ) {
    $ahora   = time();
    $ventana = (int) gep_acceso_ajuste( 'ventana_min' ) * MINUTE_IN_SECONDS;
    $c       = gep_acceso_contadores_leer();
    $e       = isset( $c[ $clave ] ) ? $c[ $clave ] : array( 'n' => 0, 'p' => $ahora, 'h' => 0, 'l' => 0, 'u' => $ahora );
    if ( $ahora - (int) $e['p'] > $ventana ) {
        $e['n'] = 0;
        $e['p'] = $ahora;
    }
    // El nivel de castigo se olvida tras 24 h sin bloqueos.
    if ( (int) $e['h'] && $ahora - (int) $e['h'] > DAY_IN_SECONDS ) {
        $e['l'] = 0;
    }
    $e['n']++;
    $e['u']  = $ahora;
    $e['k']  = mb_substr( (string) $etiqueta, 0, 60 );
    $minutos = 0;
    if ( $e['n'] >= $limite ) {
        $e['l']  = (int) $e['l'] + 1;
        $minutos = (int) gep_acceso_ajuste( 'bloqueo_min' ) * (int) pow( 2, $e['l'] - 1 );
        $minutos = min( $minutos, (int) gep_acceso_ajuste( 'bloqueo_max_min' ) );
        $e['h']  = $ahora + $minutos * MINUTE_IN_SECONDS;
        $e['n']  = 0;
        $e['p']  = $ahora;
    }
    $c[ $clave ] = $e;
    gep_acceso_contadores_guardar( $c );
    return $minutos;
}

/** Cuenta una acción simple (p. ej. pedidos de nueva contraseña) dentro de una ventana. Devuelve el total. */
function gep_acceso_contar( $clave, $ventana_seg ) {
    $ahora = time();
    $c     = gep_acceso_contadores_leer();
    $e     = isset( $c[ $clave ] ) ? $c[ $clave ] : array( 'n' => 0, 'p' => $ahora, 'h' => 0, 'l' => 0, 'u' => $ahora );
    if ( $ahora - (int) $e['p'] > $ventana_seg ) {
        $e['n'] = 0;
        $e['p'] = $ahora;
    }
    $e['n']++;
    $e['u']      = $ahora;
    $c[ $clave ] = $e;
    gep_acceso_contadores_guardar( $c );
    return (int) $e['n'];
}

function gep_acceso_limpiar_clave( $clave ) {
    $c = gep_acceso_contadores_leer();
    if ( isset( $c[ $clave ] ) ) {
        unset( $c[ $clave ] );
        gep_acceso_contadores_guardar( $c );
    }
}

/** Bloqueos vigentes (para el panel). */
function gep_acceso_bloqueos_vigentes() {
    $ahora = time();
    $out   = array();
    foreach ( gep_acceso_contadores_leer() as $k => $e ) {
        if ( ! empty( $e['h'] ) && (int) $e['h'] > $ahora ) {
            $out[ $k ] = $e;
        }
    }
    return $out;
}

/* ---------------------------------------------------------------------------
 * Registro de eventos: dos listas circulares en la base de datos (no en
 * archivos, que nginx podría servir). Eventos importantes y fallos por
 * separado, para que una avalancha de fallos no borre lo importante.
 * ------------------------------------------------------------------------- */

define( 'GEP_ACCESO_MAX_EVENTOS', 200 );
define( 'GEP_ACCESO_DIAS_REGISTRO', 30 );

/**
 * @param string $tipo    acceso|bloqueo|fallo|recuperacion|ajustes|config|plugin|rol|usuario
 * @param string $cuenta  Nombre de usuario existente o «(inexistente)». Nunca contraseñas.
 * @param string $detalle Texto corto.
 */
function gep_acceso_registrar( $tipo, $cuenta = '', $detalle = '' ) {
    $opcion = 'fallo' === $tipo ? 'gep_acceso_registro_fallos' : 'gep_acceso_registro';
    $lista  = get_option( $opcion, array() );
    $lista  = is_array( $lista ) ? $lista : array();
    $limite = time() - GEP_ACCESO_DIAS_REGISTRO * DAY_IN_SECONDS;
    $lista  = array_values( array_filter( $lista, function ( $e ) use ( $limite ) {
        return isset( $e['t'] ) && (int) $e['t'] >= $limite;
    } ) );
    $ip      = gep_acceso_ip();
    $lista[] = array(
        't' => time(),
        'e' => sanitize_key( $tipo ),
        'c' => mb_substr( sanitize_text_field( (string) $cuenta ), 0, 60 ),
        'i' => gep_acceso_ip_recortada( $ip['ip'] ),
        'd' => mb_substr( sanitize_text_field( (string) $detalle ), 0, 160 ),
    );
    if ( count( $lista ) > GEP_ACCESO_MAX_EVENTOS ) {
        $lista = array_slice( $lista, -GEP_ACCESO_MAX_EVENTOS );
    }
    update_option( $opcion, $lista, false );
}

/** URL del sitio público (/gep/), a partir del propio GEP Editor si está activo. */
function gep_acceso_url_publica() {
    if ( function_exists( 'gep_editor_public_base_url' ) ) {
        $base = gep_editor_public_base_url();
        if ( $base ) {
            return trailingslashit( $base );
        }
    }
    $home   = untrailingslashit( home_url() );
    $partes = wp_parse_url( $home );
    $ruta   = isset( $partes['path'] ) ? dirname( $partes['path'] ) : '/';
    $ruta   = ( '.' === $ruta || '\\' === $ruta ) ? '/' : $ruta;
    return ( isset( $partes['scheme'] ) ? $partes['scheme'] : 'https' ) . '://' . $partes['host'] . ( isset( $partes['port'] ) ? ':' . $partes['port'] : '' ) . trailingslashit( $ruta );
}

/** ¿Está activo el plugin Two Factor (WordPress.org)? */
function gep_acceso_two_factor_activo() {
    return class_exists( 'Two_Factor_Core' ) && method_exists( 'Two_Factor_Core', 'is_user_using_two_factor' );
}

/** ¿Debe este usuario tener segundo factor según los ajustes? */
function gep_acceso_usuario_requiere_2fa( $user ) {
    if ( ! $user instanceof WP_User || ! $user->exists() ) {
        return false;
    }
    if ( gep_acceso_ajuste( 'exigir_2fa' ) && user_can( $user, 'manage_options' ) ) {
        return true;
    }
    if ( gep_acceso_ajuste( 'exigir_2fa_editor' ) && user_can( $user, 'edit_pages' ) ) {
        return true;
    }
    return false;
}
