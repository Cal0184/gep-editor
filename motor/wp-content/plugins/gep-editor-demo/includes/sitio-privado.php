<?php
/**
 * GEP Editor 10.0.0 (10.0.2) · Acceso al sitio público (interruptor «Sitio privado»).
 *
 * Contenido GEP → Acceso al sitio (solo administradores):
 *   - Encendido: el navegador pide usuario y clave antes de mostrar
 *     https://gep.uchile.cl/gep/ (la ventana gris del navegador).
 *   - Apagado: el sitio es público, como cualquier página web.
 *
 * Reemplaza al antiguo guardia manual /gep/includes/gep-access.php (que se
 * apagó con la herramienta 9.28.0): ahora se enciende y apaga con un botón y
 * la clave se guarda cifrada en la base de datos, nunca en un archivo.
 *
 * Qué protege: las páginas del sitio (/gep/, /gep/proyectos/, …).
 * Qué no protege: las fotos y archivos sueltos (nginx los entrega directo) ni
 * el editor, que ya tiene su propio inicio de sesión.
 *
 * Emergencia (no recuerdas la clave): entra a /gep/motor/wp-admin/ y apágalo,
 * o pon en wp-config.php: define( 'GEP_SITIO_PRIVADO_APAGADO', true );
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const GEP_PRIVADO_OPCION = 'gep_sitio_privado';
if ( ! defined( 'GEP_PRIVADO_MAX_FALLOS' ) ) {
    define( 'GEP_PRIVADO_MAX_FALLOS', 30 ); // v10.0.2: claves equivocadas antes de la pausa.
}
if ( ! defined( 'GEP_PRIVADO_MINUTOS_PAUSA' ) ) {
    define( 'GEP_PRIVADO_MINUTOS_PAUSA', 10 );
}

function gep_privado_ajustes() {
    $a = get_option( GEP_PRIVADO_OPCION, array() );
    $a = is_array( $a ) ? $a : array();
    return array_merge(
        array( 'activo' => false, 'usuario' => '', 'hash' => '', 'cambio' => 0, 'por' => 0 ),
        $a
    );
}

/** El interruptor está encendido y tiene usuario y clave (si falta algo, no bloquea). */
function gep_privado_encendido() {
    if ( defined( 'GEP_SITIO_PRIVADO_APAGADO' ) && GEP_SITIO_PRIVADO_APAGADO ) {
        return false;
    }
    $a = gep_privado_ajustes();
    return ! empty( $a['activo'] ) && '' !== $a['usuario'] && '' !== $a['hash'];
}

/** ¿Esta visita es una página del sitio público (no el editor, ni wp-cron, ni la API)? */
function gep_privado_es_sitio_publico() {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
        return false;
    }
    $script = isset( $_SERVER['SCRIPT_FILENAME'] ) ? wp_normalize_path( (string) realpath( (string) $_SERVER['SCRIPT_FILENAME'] ) ) : '';
    $wp     = wp_normalize_path( (string) realpath( ABSPATH ) );
    if ( '' === $script || '' === $wp ) {
        return false;
    }
    // Todo lo que está dentro de /gep/motor/ (wp-login, wp-admin, el tema) queda fuera.
    return 0 !== strpos( $script, trailingslashit( $wp ) );
}

/** Usuario y clave que envió el navegador. */
function gep_privado_credenciales() {
    $u = isset( $_SERVER['PHP_AUTH_USER'] ) ? (string) $_SERVER['PHP_AUTH_USER'] : '';
    $p = isset( $_SERVER['PHP_AUTH_PW'] ) ? (string) $_SERVER['PHP_AUTH_PW'] : '';
    if ( '' === $u ) {
        foreach ( array( 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ) as $k ) {
            if ( ! empty( $_SERVER[ $k ] ) && 0 === stripos( (string) $_SERVER[ $k ], 'basic ' ) ) {
                $dec = base64_decode( substr( (string) $_SERVER[ $k ], 6 ), true );
                if ( false !== $dec && false !== strpos( $dec, ':' ) ) {
                    list( $u, $p ) = explode( ':', $dec, 2 );
                }
                break;
            }
        }
    }
    return array( $u, $p );
}

function gep_privado_pedir_clave( $en_pausa = false ) {
    nocache_headers();
    status_header( 401 );
    if ( $en_pausa ) {
        header( 'Retry-After: ' . ( GEP_PRIVADO_MINUTOS_PAUSA * 60 ) );
    }
    header( 'WWW-Authenticate: Basic realm="Laboratorio GEP", charset="UTF-8"' );
    header( 'Content-Type: text/html; charset=utf-8' );
    header( 'X-Robots-Tag: noindex' );
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>GEP · Acceso restringido</title></head>'
        . '<body style="font-family:system-ui,sans-serif;padding:24px;max-width:560px;margin:auto;color:#1d2b22"><h1 style="font-size:1.3rem;color:#1f5130">Acceso restringido</h1>'
        . '<p>El sitio del Laboratorio GEP está en revisión y por ahora pide usuario y clave. Si necesitas acceso, escribe al equipo GEP.</p>'
        . ( $en_pausa ? '<p>Hubo demasiados intentos con una clave equivocada. Espera ' . (int) GEP_PRIVADO_MINUTOS_PAUSA . ' minutos y vuelve a intentarlo.</p>' : '' )
        . '</body></html>';
    exit;
}

/*
 * v10.0.2 · Contador de claves equivocadas. Se guarda en una opción (no en
 * caché) y se suma con una sola consulta, para que muchos intentos al mismo
 * tiempo cuenten todos. Se reinicia solo cuando pasan GEP_PRIVADO_MINUTOS_PAUSA
 * minutos desde el primer fallo, y al guardar la pantalla Acceso al sitio.
 */
function gep_privado_fallos_actuales() {
    $d = get_option( 'gep_privado_fallos', array() );
    if ( ! is_array( $d ) || empty( $d['desde'] ) || time() - (int) $d['desde'] > GEP_PRIVADO_MINUTOS_PAUSA * MINUTE_IN_SECONDS ) {
        return 0;
    }
    global $wpdb;
    return (int) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'gep_privado_fallos_n' ) );
}

function gep_privado_sumar_fallo() {
    global $wpdb;
    $d = get_option( 'gep_privado_fallos', array() );
    if ( ! is_array( $d ) || empty( $d['desde'] ) || time() - (int) $d['desde'] > GEP_PRIVADO_MINUTOS_PAUSA * MINUTE_IN_SECONDS ) {
        update_option( 'gep_privado_fallos', array( 'desde' => time() ), false );
        update_option( 'gep_privado_fallos_n', 0, false );
    }
    if ( false === get_option( 'gep_privado_fallos_n', false ) ) {
        add_option( 'gep_privado_fallos_n', 0, '', 'no' );
    }
    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s", 'gep_privado_fallos_n' ) );
    wp_cache_delete( 'gep_privado_fallos_n', 'options' );
    wp_cache_delete( 'notoptions', 'options' );
    return (int) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'gep_privado_fallos_n' ) );
}

function gep_privado_reiniciar_fallos() {
    delete_option( 'gep_privado_fallos' );
    delete_option( 'gep_privado_fallos_n' );
}

/* Control en cada página del sitio público (se ejecuta al cargar WordPress). */
add_action( 'wp_loaded', function () {
    if ( ! gep_privado_encendido() || ! gep_privado_es_sitio_publico() ) {
        return;
    }
    $a = gep_privado_ajustes();
    list( $u, $p ) = gep_privado_credenciales();
    // v10.0.2: tras muchos intentos fallidos seguidos (de cualquier origen,
    // porque detrás del proxy todas las visitas llegan con la misma IP) se deja
    // de revisar la clave por unos minutos. Frena a quien prueba claves al azar.
    if ( '' !== $u && gep_privado_fallos_actuales() >= GEP_PRIVADO_MAX_FALLOS ) {
        gep_privado_pedir_clave( true );
    }
    if ( '' !== $u && hash_equals( (string) $a['usuario'], $u ) && wp_check_password( $p, (string) $a['hash'] ) ) {
        header( 'Cache-Control: private, no-store' );
        return;
    }
    if ( '' !== $u ) {
        $fallos = gep_privado_sumar_fallo();
        if ( GEP_PRIVADO_MAX_FALLOS === $fallos && function_exists( 'gep_registro' ) ) {
            gep_registro( 'acceso', sprintf( 'Acceso al sitio: %d claves equivocadas seguidas. Se pausó la revisión de claves por %d minutos.', GEP_PRIVADO_MAX_FALLOS, GEP_PRIVADO_MINUTOS_PAUSA ) );
        }
        sleep( 1 );
    }
    gep_privado_pedir_clave();
}, 1 );

/* ---- Pantalla «Acceso al sitio» ------------------------------------------ */

add_action( 'admin_menu', function () {
    add_submenu_page( 'gep-content-editor', 'Acceso al sitio', 'Acceso al sitio', 'manage_options', 'gep-acceso-sitio', 'gep_privado_pantalla' );
}, 20 );

add_action( 'admin_post_gep_privado_guardar', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Solo un administrador puede cambiar el acceso al sitio.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'gep_privado_guardar', 'gep_privado_nonce' );
    $a       = gep_privado_ajustes();
    $activo  = ! empty( $_POST['gep_privado_activo'] );
    $usuario = isset( $_POST['gep_privado_usuario'] ) ? sanitize_user( wp_unslash( $_POST['gep_privado_usuario'] ), true ) : '';
    $clave   = isset( $_POST['gep_privado_clave'] ) ? (string) wp_unslash( $_POST['gep_privado_clave'] ) : ''; // No se sanea: puede llevar cualquier símbolo.
    $aviso   = 'guardado';

    if ( '' !== $clave && strlen( $clave ) < 8 ) {
        $aviso = 'corta';
        if ( '' !== $usuario && $usuario !== $a['usuario'] ) {
            $a['usuario'] = $usuario; // Se conserva lo escrito; el interruptor no cambia.
            update_option( GEP_PRIVADO_OPCION, $a, false );
        }
    } else {
        if ( '' !== $usuario ) {
            $a['usuario'] = $usuario;
        }
        if ( '' !== $clave ) {
            $a['hash'] = wp_hash_password( $clave );
        }
        if ( $activo && ( '' === $a['usuario'] || '' === $a['hash'] ) ) {
            $aviso  = 'faltan';
            $activo = false;
        }
        $a['activo'] = $activo;
        gep_privado_reiniciar_fallos(); // Una clave nueva o un cambio levanta la pausa.
        $a['cambio'] = time();
        $a['por']    = get_current_user_id();
        update_option( GEP_PRIVADO_OPCION, $a, false );
        if ( function_exists( 'gep_acceso_registrar' ) ) {
            gep_acceso_registrar( 'ajustes', '', $activo ? 'Sitio público: se pide usuario y clave.' : 'Sitio público: abierto a todos.' );
        }
    }
    wp_safe_redirect( add_query_arg( array( 'page' => 'gep-acceso-sitio', 'gep_aviso' => $aviso ), admin_url( 'admin.php' ) ) );
    exit;
} );

function gep_privado_pantalla() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $a      = gep_privado_ajustes();
    $on     = gep_privado_encendido();
    $aviso  = isset( $_GET['gep_aviso'] ) ? sanitize_key( $_GET['gep_aviso'] ) : '';
    $textos = array(
        'guardado' => array( 'success', 'Listo. ' . ( $on ? 'El sitio ahora pide usuario y clave.' : 'El sitio ahora es público.' ) ),
        'corta'    => array( 'error', 'No se guardó: la clave debe tener al menos 8 caracteres.' ),
        'faltan'   => array( 'error', 'No se encendió: falta escribir el usuario o la clave.' ),
    );
    $sitio  = function_exists( 'gep_editor_public_base_url' ) ? trailingslashit( gep_editor_public_base_url() ) : home_url( '/' );
    ?>
    <div class="wrap gep-privado">
        <h1>Acceso al sitio</h1>
        <?php if ( isset( $textos[ $aviso ] ) ) : ?>
            <div class="notice notice-<?php echo esc_attr( $textos[ $aviso ][0] ); ?> is-dismissible"><p><?php echo esc_html( $textos[ $aviso ][1] ); ?></p></div>
        <?php endif; ?>
        <?php if ( defined( 'GEP_SITIO_PRIVADO_APAGADO' ) && GEP_SITIO_PRIVADO_APAGADO ) : ?>
            <div class="notice notice-warning"><p>wp-config.php tiene <code>GEP_SITIO_PRIVADO_APAGADO</code>: el sitio es público aunque el interruptor esté encendido.</p></div>
        <?php endif; ?>

        <div class="gep-privado-estado <?php echo $on ? 'is-on' : 'is-off'; ?>">
            <span class="dashicons <?php echo $on ? 'dashicons-lock' : 'dashicons-unlock'; ?>" aria-hidden="true"></span>
            <div>
                <strong><?php echo $on ? 'Ahora: el sitio pide usuario y clave' : 'Ahora: el sitio es público'; ?></strong>
                <span><?php echo $on ? 'Solo entra quien tenga el usuario y la clave de abajo.' : 'Cualquier persona puede ver'; ?> <a href="<?php echo esc_url( $sitio ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $sitio ); ?></a></span>
                <?php if ( $a['cambio'] ) : ?>
                    <span class="gep-privado-meta">Último cambio: <?php echo esc_html( wp_date( 'd-m-Y H:i', (int) $a['cambio'] ) ); ?><?php echo $a['por'] && get_userdata( $a['por'] ) ? ' · ' . esc_html( get_userdata( $a['por'] )->display_name ) : ''; ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gep-privado-form">
            <input type="hidden" name="action" value="gep_privado_guardar">
            <?php wp_nonce_field( 'gep_privado_guardar', 'gep_privado_nonce' ); ?>
            <label class="gep-privado-switch">
                <input type="checkbox" name="gep_privado_activo" value="1" <?php checked( ! empty( $a['activo'] ) ); ?>>
                <span><strong>Pedir usuario y clave para ver el sitio</strong><br>Desmárcalo para volver el sitio público.</span>
            </label>
            <p><label for="gep_privado_usuario"><strong>Usuario</strong></label><br>
                <input type="text" id="gep_privado_usuario" name="gep_privado_usuario" value="<?php echo esc_attr( $a['usuario'] ); ?>" autocomplete="off" class="regular-text"></p>
            <p><label for="gep_privado_clave"><strong>Clave</strong></label><br>
                <input type="password" id="gep_privado_clave" name="gep_privado_clave" value="" autocomplete="new-password" class="regular-text" placeholder="<?php echo '' !== $a['hash'] ? 'Déjalo vacío para mantener la clave actual' : 'Mínimo 8 caracteres'; ?>">
                <br><span class="description">Se guarda cifrada: nadie puede verla después, ni siquiera un administrador. Si la olvidas, escribe una nueva. Usa una clave distinta a la de tu cuenta de WordPress.</span></p>
            <p><button type="submit" class="button button-primary">Guardar</button></p>
        </form>
        <div class="notice notice-info inline"><p><strong>Qué no protege:</strong> las fotos, videos y PDF de la Mediateca se pueden abrir sin clave si alguien tiene su dirección exacta (el servidor los entrega directo). No subas a la Mediateca documentos que no puedan ser públicos. El editor (/gep/motor/) usa su propio inicio de sesión.</p></div>
    </div>
    <style>
        .gep-privado{max-width:720px}
        .gep-privado-estado{display:flex;gap:12px;align-items:flex-start;margin:16px 0;padding:14px 16px;border-radius:10px;border:1px solid #c9dccf;background:#eef6f0;color:#173c2a}
        .gep-privado-estado.is-on{background:#fff5e5;border-color:#f0cf96;color:#5a3a00}
        .gep-privado-estado>div{display:grid;gap:2px}
        .gep-privado-estado .dashicons{margin-top:2px}
        .gep-privado-meta{font-size:12px;opacity:.8}
        .gep-privado-form{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px 20px}
        .gep-privado-switch{display:flex;gap:10px;align-items:flex-start;margin-bottom:12px}
        .gep-privado-switch input{margin-top:3px}
        body.gep-theme-dark .gep-privado-form{background:#16201c;border-color:#2c3a34;color:#e7efea}
    </style>
    <?php
}
