<?php
/**
 * GEP · Acceso seguro — panel «Herramientas → Seguridad GEP».
 * Solo administradores (manage_options). Todas las acciones con nonce.
 * La revisión de configuración y de archivos es de SOLO LECTURA: no modifica
 * wp-config.php ni borra archivos.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GEP_ACCESO_PAGINA', 'gep-seguridad' );

add_action( 'admin_menu', function () {
    add_management_page( 'Seguridad GEP', 'Seguridad GEP', 'manage_options', GEP_ACCESO_PAGINA, 'gep_acceso_panel_pintar' );
} );

function gep_acceso_panel_url( $pestana = 'estado', $extra = array() ) {
    return add_query_arg( array_merge( array( 'page' => GEP_ACCESO_PAGINA, 'pestana' => $pestana ), $extra ), admin_url( 'tools.php' ) );
}

/* ---- Acciones ------------------------------------------------------------ */

function gep_acceso_panel_verificar( $accion ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'No tienes permiso.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( $accion );
    if ( gep_acceso_apagado_por_constante() ) {
        wp_die( 'Seguridad GEP está apagado con GEP_ACCESO_APAGADO en wp-config.php. Quita esa línea para cambiar ajustes.', '', array( 'response' => 403 ) );
    }
}

add_action( 'admin_post_gep_acceso_guardar', function () {
    gep_acceso_panel_verificar( 'gep_acceso_guardar' );
    $entrada = isset( $_POST['gep_acceso'] ) && is_array( $_POST['gep_acceso'] ) ? wp_unslash( $_POST['gep_acceso'] ) : array();
    $cambios = gep_acceso_guardar_ajustes( $entrada );
    if ( $cambios ) {
        gep_acceso_registrar( 'ajustes', gep_acceso_usuario_actual_nombre_panel(), implode( ' ', $cambios ) );
    }
    wp_safe_redirect( gep_acceso_panel_url( 'ajustes', array( 'hecho' => 'guardado' ) ) );
    exit;
} );

add_action( 'admin_post_gep_acceso_desbloquear', function () {
    gep_acceso_panel_verificar( 'gep_acceso_desbloquear' );
    $clave = isset( $_POST['clave'] ) ? sanitize_text_field( wp_unslash( $_POST['clave'] ) ) : '';
    if ( 'todo' === $clave ) {
        delete_option( 'gep_acceso_contadores' );
        gep_acceso_registrar( 'bloqueo', gep_acceso_usuario_actual_nombre_panel(), 'todos los bloqueos levantados a mano' );
    } elseif ( '' !== $clave ) {
        $c        = gep_acceso_contadores_leer();
        $etiqueta = isset( $c[ $clave ]['k'] ) ? $c[ $clave ]['k'] : '';
        gep_acceso_limpiar_clave( $clave );
        gep_acceso_registrar( 'bloqueo', gep_acceso_usuario_actual_nombre_panel(), 'bloqueo levantado a mano: ' . $etiqueta );
    }
    wp_safe_redirect( gep_acceso_panel_url( 'estado', array( 'hecho' => 'desbloqueado' ) ) );
    exit;
} );

add_action( 'admin_post_gep_acceso_limpiar_registro', function () {
    gep_acceso_panel_verificar( 'gep_acceso_limpiar_registro' );
    delete_option( 'gep_acceso_registro_fallos' );
    delete_option( 'gep_acceso_registro' );
    gep_acceso_registrar( 'ajustes', gep_acceso_usuario_actual_nombre_panel(), 'registro vaciado a mano' );
    wp_safe_redirect( gep_acceso_panel_url( 'registro', array( 'hecho' => 'vaciado' ) ) );
    exit;
} );

function gep_acceso_usuario_actual_nombre_panel() {
    $u = wp_get_current_user();
    return $u && $u->exists() ? $u->user_login : '';
}

/* ---- Revisión de configuración (solo lectura) ---------------------------- */

/** @return array lista de array( nivel, título, detalle ) — nivel: ok|aviso|riesgo|info */
function gep_acceso_revision_config() {
    $r = array();

    // Depuración.
    $debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
    $r[]   = $debug ? array( 'aviso', 'WP_DEBUG está activado', 'En producción debe ser false en wp-config.php.' ) : array( 'ok', 'WP_DEBUG desactivado', '' );
    if ( $debug && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ) ) {
        $r[] = array( 'riesgo', 'Los errores se muestran en pantalla', "Agrega define( 'WP_DEBUG_DISPLAY', false ); o pon WP_DEBUG en false." );
    }
    if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        $ruta = is_string( WP_DEBUG_LOG ) ? WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log';
        $publ = 0 === strpos( wp_normalize_path( $ruta ), wp_normalize_path( dirname( ABSPATH ) ) );
        $r[]  = array( $publ ? 'riesgo' : 'aviso', 'WP_DEBUG_LOG activo', $publ ? 'El archivo ' . $ruta . ' está dentro de la carpeta web: nginx lo entregaría a cualquiera. Desactívalo o usa una ruta fuera de la web.' : 'Registro en ' . $ruta . ' (fuera de la web).' );
    }

    // Edición de archivos.
    $r[] = ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT )
        ? array( 'ok', 'Editor de archivos de WordPress desactivado', '' )
        : array( 'aviso', 'El editor de archivos de WordPress está disponible', "Agrega en wp-config.php: define( 'DISALLOW_FILE_EDIT', true ); (no afecta a GEP Editor)." );
    $r[] = ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS )
        ? array( 'info', 'DISALLOW_FILE_MODS activo', 'No se pueden instalar ni actualizar plugins, temas ni WordPress desde el panel (tampoco las actualizaciones automáticas).' )
        : array( 'info', 'DISALLOW_FILE_MODS no está activo', 'Recomendado así por ahora: permite instalar Two Factor y recibir actualizaciones de seguridad. Ver guía.' );

    // HTTPS.
    $r[] = is_ssl() ? array( 'ok', 'Esta página se sirve por HTTPS', '' ) : array( 'riesgo', 'Esta página NO se ve como HTTPS', 'Revisa GEP_PROXY_HTTPS en gep-config-migracion.php.' );

    // Salts.
    $malas = array();
    foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $k ) {
        if ( ! defined( $k ) || strlen( (string) constant( $k ) ) < 32 || false !== strpos( (string) constant( $k ), 'put your unique phrase' ) ) {
            $malas[] = $k;
        }
    }
    $r[] = $malas ? array( 'riesgo', 'Claves secretas (salts) débiles o por defecto', implode( ', ', $malas ) . '. Reemplázalas en wp-config.php (ver guía).' ) : array( 'ok', 'Claves secretas (salts) definidas y largas', 'Si se compartieron alguna vez (chat, correo, ZIP), conviene regenerarlas.' );

    // Usuarios.
    $r[] = get_option( 'users_can_register' ) ? array( 'riesgo', 'Cualquiera puede registrarse', 'Ajustes → Generales → desmarca «Cualquiera puede registrarse».' ) : array( 'ok', 'Registro de usuarios cerrado', '' );
    if ( 'administrator' === get_option( 'default_role' ) ) {
        $r[] = array( 'riesgo', 'El rol por defecto es Administrador', 'Ajustes → Generales → Perfil por defecto: Suscriptor.' );
    }
    if ( get_user_by( 'login', 'admin' ) ) {
        $r[] = array( 'aviso', 'Existe una cuenta llamada «admin»', 'Es el primer nombre que prueban los ataques.' );
    }

    // Contraseñas de aplicación existentes.
    if ( class_exists( 'WP_Application_Passwords' ) ) {
        $con = 0;
        foreach ( get_users( array( 'fields' => 'ID', 'number' => 200 ) ) as $id ) {
            if ( WP_Application_Passwords::get_user_application_passwords( $id ) ) {
                $con++;
            }
        }
        if ( $con ) {
            $r[] = array( 'riesgo', $con . ' cuenta(s) tienen contraseñas de aplicación', 'Permiten entrar sin segundo factor. Bórralas en Usuarios → perfil → Contraseñas de aplicación.' );
        }
    }
    $r[] = wp_is_application_passwords_available() ? array( 'aviso', 'Contraseñas de aplicación disponibles', 'Se cierran al activar «Exigir segundo factor».' ) : array( 'ok', 'Contraseñas de aplicación cerradas', '' );

    // Comentarios.
    $r[] = 'open' === get_option( 'default_comment_status' ) ? array( 'info', 'Comentarios abiertos por defecto en WordPress', 'El módulo «Superficie» impide comentarios nuevos sin borrar los existentes.' ) : array( 'ok', 'Comentarios cerrados por defecto', '' );

    // Plugin Two Factor.
    if ( gep_acceso_two_factor_activo() ) {
        $r[] = array( 'ok', 'Plugin Two Factor activo', '' );
    } elseif ( is_file( WP_PLUGIN_DIR . '/two-factor/two-factor.php' ) ) {
        $r[] = array( 'aviso', 'Plugin Two Factor instalado pero inactivo', 'Actívalo en Plugins.' );
    } else {
        $r[] = array( 'aviso', 'Plugin Two Factor no instalado', 'Plugins → Añadir nuevo → buscar «Two Factor» (autor: WordPress.org Contributors).' );
    }

    // Mu-plugin anterior.
    $r[] = is_file( WPMU_PLUGIN_DIR . '/gep-seguridad.php' ) ? array( 'ok', 'gep-seguridad.php (1.0.0) presente', 'Se mantiene: este módulo lo complementa sin repetirlo.' ) : array( 'info', 'gep-seguridad.php no está', 'Sin él, la lista de usuarios por API y ?author= quedan abiertas.' );

    // Actualizaciones pendientes (datos que WordPress ya tiene guardados; no consulta Internet).
    $core = get_site_transient( 'update_core' );
    if ( $core && ! empty( $core->updates ) && 'upgrade' === $core->updates[0]->response ) {
        $r[] = array( 'aviso', 'Hay una actualización de WordPress', 'Versión ' . esc_html( $core->updates[0]->current ) . ' disponible (haz respaldo antes).' );
    }
    $plug = get_site_transient( 'update_plugins' );
    if ( $plug && ! empty( $plug->response ) ) {
        $r[] = array( 'aviso', count( $plug->response ) . ' plugin(s) con actualización', implode( ', ', array_keys( $plug->response ) ) );
    }
    $r[] = array( 'info', 'Versiones', 'WordPress ' . get_bloginfo( 'version' ) . ' · PHP ' . PHP_VERSION );

    // Permisos.
    foreach ( array( ABSPATH . 'wp-config.php', dirname( ABSPATH ) . '/wp-config.php', ABSPATH . 'gep-config-migracion.php' ) as $f ) {
        if ( ! is_file( $f ) ) {
            continue;
        }
        $p   = fileperms( $f ) & 0777;
        $txt = basename( $f ) . ': ' . substr( sprintf( '%o', $p ), -3 );
        if ( $p & 0002 ) {
            $r[] = array( 'riesgo', $txt . ' — cualquiera puede escribirlo', 'Cámbialo a 640 (o 644 si el sitio deja de funcionar) en FileZilla → Permisos de archivo.' );
        } elseif ( $p & 0020 ) {
            $r[] = array( 'aviso', $txt, 'Recomendado 640 o 644.' );
        } else {
            $r[] = array( 'ok', $txt, ( $p & 0004 ) ? '644 es aceptable; 640 es mejor si el hosting lo permite (pruébalo y vuelve atrás si falla).' : '' );
        }
    }
    return $r;
}

/** Archivos sobrantes que nginx entregaría directamente (PHP no puede protegerlos). */
function gep_acceso_revision_archivos() {
    $r    = array();
    $wp   = untrailingslashit( ABSPATH );
    $pub  = dirname( $wp );
    $rel  = function ( $f ) use ( $pub ) {
        return ltrim( str_replace( wp_normalize_path( dirname( $pub ) ), '', wp_normalize_path( $f ) ), '/' );
    };
    foreach ( array( $wp . '/readme.html', $wp . '/license.txt' ) as $f ) {
        if ( is_file( $f ) ) {
            $r[] = array( 'aviso', $rel( $f ), 'Revela la versión de WordPress. Se puede borrar (vuelve con cada actualización).' );
        }
    }
    if ( is_file( $wp . '/wp-config-sample.php' ) ) {
        $r[] = array( 'info', $rel( $wp . '/wp-config-sample.php' ), 'Inofensivo (no tiene datos). Se puede borrar.' );
    }
    foreach ( array_unique( array_merge( (array) glob( $pub . '/gep-diagnostico*.php' ), (array) glob( $wp . '/gep-diagnostico*.php' ), (array) glob( $pub . '/*diagnostic*.php' ) ) ) as $f ) {
        if ( $f && is_file( $f ) ) {
            $r[] = array( 'riesgo', $rel( $f ), 'Archivo de diagnóstico de la migración: bórralo con FileZilla.' );
        }
    }
    // v9.27.1 · Operación Motor: una sola instalación del CMS, en la carpeta «motor».
    if ( defined( 'GEP_CMS_CARPETA' ) && basename( $wp ) !== GEP_CMS_CARPETA ) {
        $r[] = array( 'riesgo', $rel( $wp ) . '/', 'WordPress no está en la carpeta oficial «' . GEP_CMS_CARPETA . '». Revisa la guía de Operación Motor.' );
    }
    foreach ( (array) glob( $pub . '/*/wp-load.php' ) as $f ) {
        if ( $f && is_file( $f ) && wp_normalize_path( dirname( $f ) ) !== wp_normalize_path( $wp ) ) {
            $r[] = array( 'riesgo', $rel( dirname( $f ) ) . '/', 'Otra copia de WordPress junto al sitio (carpeta antigua o respaldo). No debe quedar en el servidor: descárgala y bórrala con FileZilla.' );
        }
    }
    if ( is_dir( $pub . '/wordpress' ) && ! is_file( $pub . '/wordpress/wp-load.php' ) && wp_normalize_path( $pub . '/wordpress' ) !== wp_normalize_path( $wp ) ) {
        $r[] = array( 'aviso', $rel( $pub . '/wordpress' ) . '/', 'Queda la carpeta antigua del CMS. Si ya verificaste la Operación Motor, bórrala con FileZilla (guía, fase F).' );
    }
    if ( is_file( WP_CONTENT_DIR . '/mu-plugins/gep-operacion-motor.php' ) ) {
        $r[] = array( 'aviso', 'mu-plugins/gep-operacion-motor.php', 'Herramienta temporal de la Operación Motor. Cuando termines, elimínala desde la propia herramienta o con FileZilla.' );
    }
    // v10.0.0: herramientas temporales 9.28.0 (quitar clave) y 9.29.0 (prueba de direcciones).
    foreach ( array_merge( (array) glob( $wp . '/gep-quitar-clave-*.php' ), (array) glob( $wp . '/gep-prueba-direcciones-*.php' ), (array) glob( $pub . '/includes/*.gepqc-off.php' ) ) as $f ) {
        if ( $f && is_file( $f ) ) {
            $r[] = array( 'aviso', $rel( $f ), 'Herramienta o copia temporal de una versión anterior. Ya cumplió su función: bórrala con FileZilla.' );
        }
    }
    foreach ( (array) glob( $pub . '/gep-prueba-url-*', GLOB_ONLYDIR ) as $f ) {
        if ( $f && is_dir( $f ) ) {
            $r[] = array( 'aviso', $rel( $f ) . '/', 'Carpeta de prueba de direcciones (9.29.0): bórrala con FileZilla.' );
        }
    }
    foreach ( array_merge( (array) glob( $pub . '/README-BETA*' ), (array) glob( $pub . '/htaccess-example.txt' ) ) as $f ) {
        if ( $f && is_file( $f ) ) {
            $r[] = array( 'info', $rel( $f ), 'Nota interna de versiones beta del sitio público; se puede borrar.' );
        }
    }
    if ( is_dir( $pub . '/gep-prueba-htaccess' ) ) {
        $r[] = array( 'aviso', $rel( $pub . '/gep-prueba-htaccess' ) . '/', 'Carpeta de prueba de la migración: bórrala con FileZilla.' );
    }
    if ( is_file( WP_CONTENT_DIR . '/debug.log' ) ) {
        $r[] = array( 'riesgo', $rel( WP_CONTENT_DIR . '/debug.log' ), 'Registro de errores público (puede mostrar rutas y datos). Descárgalo si lo necesitas y bórralo.' );
    }
    $patrones = array( '*.sql', '*.sql.gz', '*.zip', '*.tar', '*.tar.gz', '*.tgz', '*.bak', '*.old', '*.orig', '*.save', '*.swp', '*~' );
    foreach ( array( $pub, $wp, WP_CONTENT_DIR, WP_CONTENT_DIR . '/uploads' ) as $dir ) {
        foreach ( $patrones as $pat ) {
            foreach ( (array) glob( $dir . '/' . $pat ) as $f ) {
                if ( $f && is_file( $f ) ) {
                    $r[] = array( 'riesgo', $rel( $f ), 'Respaldo o archivo comprimido descargable por cualquiera. Muévelo fuera del servidor.' );
                }
            }
        }
    }
    foreach ( (array) glob( WP_PLUGIN_DIR . '/*/README-BETA*' ) as $f ) {
        if ( $f && is_file( $f ) ) {
            $r[] = array( 'info', $rel( $f ), 'Nota interna de versión beta; se puede borrar.' );
        }
    }
    // PHP dentro de uploads (no debería haber ninguno).
    $up = wp_get_upload_dir();
    if ( ! empty( $up['basedir'] ) && is_dir( $up['basedir'] ) ) {
        $n = 0;
        $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $up['basedir'], FilesystemIterator::SKIP_DOTS ) );
        foreach ( $it as $f ) {
            if ( ++$n > 20000 ) {
                break;
            }
            if ( $f->isFile() && preg_match( '/\.(php\d?|phtml|phar)$/i', $f->getFilename() ) && 'index.php' !== $f->getFilename() ) {
                $r[] = array( 'riesgo', $rel( $f->getPathname() ), 'Archivo PHP dentro de uploads: no debería existir. Descárgalo para revisarlo y luego bórralo con FileZilla.' );
            }
        }
    }
    if ( ! $r ) {
        $r[] = array( 'ok', 'No se encontraron archivos sobrantes conocidos', '' );
    }
    return $r;
}

/* ---- Pintar -------------------------------------------------------------- */

function gep_acceso_insignia( $nivel ) {
    $t = array( 'ok' => 'Bien', 'aviso' => 'Revisar', 'riesgo' => 'Riesgo', 'info' => 'Info' );
    return '<span class="gep-acc-b gep-acc-' . esc_attr( $nivel ) . '">' . esc_html( isset( $t[ $nivel ] ) ? $t[ $nivel ] : $nivel ) . '</span>';
}

function gep_acceso_tabla_revision( $filas ) {
    echo '<table class="widefat striped"><tbody>';
    foreach ( $filas as $f ) {
        echo '<tr><td style="width:90px">' . gep_acceso_insignia( $f[0] ) . '</td><td><strong>' . esc_html( $f[1] ) . '</strong>' . ( '' !== $f[2] ? '<br><span class="description">' . esc_html( $f[2] ) . '</span>' : '' ) . '</td></tr>';
    }
    echo '</tbody></table>';
}

function gep_acceso_panel_pintar() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $pestanas = array( 'estado' => 'Estado', 'ajustes' => 'Ajustes', 'registro' => 'Registro', 'revision' => 'Revisión' );
    $actual   = isset( $_GET['pestana'] ) && isset( $pestanas[ $_GET['pestana'] ] ) ? sanitize_key( $_GET['pestana'] ) : 'estado';
    $avisos   = array( 'guardado' => 'Ajustes guardados.', 'desbloqueado' => 'Bloqueo levantado.', 'vaciado' => 'Registro vaciado.' );
    ?>
    <div class="wrap gep-acc">
        <style>
            .gep-acc .gep-acc-b{display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600}
            .gep-acc .gep-acc-ok{background:#e7f5ea;color:#1e6b34}.gep-acc .gep-acc-aviso{background:#fff4d6;color:#7a5600}
            .gep-acc .gep-acc-riesgo{background:#fde7e7;color:#a11}.gep-acc .gep-acc-info{background:#eef1f5;color:#3c4a5c}
            .gep-acc .gep-acc-sec{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 20px;margin:16px 0;max-width:980px}
            .gep-acc .gep-acc-sec h2{margin-top:0}
            .gep-acc label.gep-acc-op{display:block;margin:10px 0 2px;font-weight:600}
            .gep-acc .gep-acc-op + .description{display:block;margin:0 0 6px 24px}
        </style>
        <h1>Seguridad GEP <span class="description">v<?php echo esc_html( GEP_ACCESO_VERSION ); ?></span></h1>
        <?php if ( gep_acceso_apagado_por_constante() ) : ?>
            <div class="notice notice-error"><p><strong>Todo Seguridad GEP está apagado</strong> por <code>GEP_ACCESO_APAGADO</code> en wp-config.php (modo de emergencia). Quita esa línea para volver a activarlo.</p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['hecho'] ) && isset( $avisos[ $_GET['hecho'] ] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $avisos[ $_GET['hecho'] ] ); ?></p></div>
        <?php endif; ?>
        <nav class="nav-tab-wrapper">
            <?php foreach ( $pestanas as $k => $t ) : ?>
                <a class="nav-tab <?php echo $k === $actual ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( gep_acceso_panel_url( $k ) ); ?>"><?php echo esc_html( $t ); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php
        $fn = 'gep_acceso_pestana_' . $actual;
        $fn();
        ?>
    </div>
    <?php
}

function gep_acceso_pestana_estado() {
    $a       = gep_acceso_ajustes();
    $apagado = gep_acceso_apagado_por_constante();
    $mods    = array(
        array( 'Límite de intentos y mensajes genéricos', $a['login'] && is_file( GEP_ACCESO_DIR . 'login.php' ) ),
        array( 'Segundo factor obligatorio (administradores)', $a['exigir_2fa'] && gep_acceso_two_factor_activo() ),
        array( 'Segundo factor obligatorio (Editor GEP)', $a['exigir_2fa_editor'] && gep_acceso_two_factor_activo() ),
        array( 'WordPress público cerrado (→ /gep/)', $a['publico'] && is_file( GEP_ACCESO_DIR . 'publico.php' ) ),
        array( 'Reducción de superficie', $a['superficie'] && is_file( GEP_ACCESO_DIR . 'superficie.php' ) ),
        array( 'API REST solo con sesión', $a['rest_anonimo'] && is_file( GEP_ACCESO_DIR . 'superficie.php' ) ),
    );
    echo '<div class="gep-acc-sec"><h2>Qué está activo</h2><table class="widefat striped"><tbody>';
    foreach ( $mods as $m ) {
        $on = $m[1] && ! $apagado;
        echo '<tr><td style="width:90px">' . gep_acceso_insignia( $on ? 'ok' : 'info' ) . '</td><td>' . esc_html( $m[0] ) . ' — <strong>' . ( $on ? 'activo' : 'apagado' ) . '</strong></td></tr>';
    }
    echo '</tbody></table>';
    if ( ( $a['exigir_2fa'] || $a['exigir_2fa_editor'] ) && ! gep_acceso_two_factor_activo() ) {
        echo '<p>' . gep_acceso_insignia( 'riesgo' ) . ' Se pidió exigir segundo factor, pero el plugin Two Factor no está activo: <strong>hoy NO se exige</strong>.</p>';
    }
    echo '</div>';

    // Segundo factor por persona.
    echo '<div class="gep-acc-sec"><h2>Segundo factor por cuenta</h2>';
    if ( ! gep_acceso_two_factor_activo() ) {
        echo '<p>' . gep_acceso_insignia( 'aviso' ) . ' El plugin Two Factor no está activo, así que no se puede saber ni exigir. Ninguna cuenta tiene segundo factor por ahora.</p>';
    }
    $users = get_users( array( 'role__in' => array( 'administrator', 'gep_editor', 'editor' ), 'number' => 100 ) );
    echo '<table class="widefat striped"><thead><tr><th>Cuenta</th><th>Rol</th><th>Segundo factor</th><th>¿Se le exige?</th></tr></thead><tbody>';
    foreach ( $users as $u ) {
        $tiene = gep_acceso_two_factor_activo() && Two_Factor_Core::is_user_using_two_factor( $u->ID );
        $prov  = '';
        if ( $tiene && method_exists( 'Two_Factor_Core', 'get_enabled_providers_for_user' ) ) {
            $prov = implode( ', ', array_map( function ( $p ) {
                return preg_replace( '/^Two_Factor_/', '', is_object( $p ) ? get_class( $p ) : (string) $p );
            }, (array) Two_Factor_Core::get_enabled_providers_for_user( $u ) ) );
        }
        $exige = gep_acceso_usuario_requiere_2fa( $u ) && gep_acceso_two_factor_activo();
        echo '<tr><td><a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html( $u->user_login ) . '</a></td><td>' . esc_html( implode( ', ', $u->roles ) ) . '</td><td>' . gep_acceso_insignia( $tiene ? 'ok' : ( $exige ? 'riesgo' : 'aviso' ) ) . ' ' . esc_html( $tiene ? 'Sí' . ( $prov ? ' (' . $prov . ')' : '' ) : 'No' ) . '</td><td>' . ( $exige ? 'Sí' : 'No' ) . '</td></tr>';
    }
    echo '</tbody></table></div>';

    // Bloqueos.
    $bloq = gep_acceso_bloqueos_vigentes();
    echo '<div class="gep-acc-sec"><h2>Bloqueos vigentes</h2>';
    if ( ! $bloq ) {
        echo '<p>No hay bloqueos.</p>';
    } else {
        $tipos = array( 'c' => 'Cuenta (equipos no reconocidos)', 'd' => 'Cuenta en un equipo reconocido', 'n' => 'Nombre inexistente', 'i' => 'Dirección IP' );
        echo '<table class="widefat striped"><thead><tr><th>Qué</th><th>Tipo</th><th>Quedan</th><th></th></tr></thead><tbody>';
        foreach ( $bloq as $k => $e ) {
            $min = (int) ceil( ( (int) $e['h'] - time() ) / 60 );
            echo '<tr><td>' . esc_html( isset( $e['k'] ) ? $e['k'] : '' ) . '</td><td>' . esc_html( isset( $tipos[ $k[0] ] ) ? $tipos[ $k[0] ] : '' ) . '</td><td>' . (int) $min . ' min</td><td>';
            gep_acceso_boton_desbloquear( $k, 'Levantar' );
            echo '</td></tr>';
        }
        echo '</tbody></table><p>';
        gep_acceso_boton_desbloquear( 'todo', 'Levantar todos' );
        echo '</p>';
    }
    echo '</div>';

    // IP.
    $ip = gep_acceso_ip();
    echo '<div class="gep-acc-sec"><h2>Dirección IP (diagnóstico)</h2><table class="widefat striped"><tbody>';
    echo '<tr><td style="width:260px">REMOTE_ADDR (quién conecta a PHP)</td><td><code>' . esc_html( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' ) . '</code></td></tr>';
    foreach ( array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_PROTO' ) as $h ) {
        if ( isset( $_SERVER[ $h ] ) ) {
            echo '<tr><td>' . esc_html( $h ) . '</td><td><code>' . esc_html( substr( (string) $_SERVER[ $h ], 0, 120 ) ) . '</code></td></tr>';
        }
    }
    echo '<tr><td>IP usada para el límite por IP</td><td>' . ( $ip['fiable'] ? gep_acceso_insignia( 'ok' ) . ' <code>' . esc_html( $ip['ip'] ) . '</code> (vía proxy de confianza)' : gep_acceso_insignia( 'info' ) . ' No fiable: el límite por IP no se aplica; se protege por cuenta y por equipo.' ) . '</td></tr>';
    echo '</tbody></table><p class="description">Si REMOTE_ADDR es siempre la misma dirección en distintos computadores, es la del proxy de la Universidad: no sirve para bloquear por IP. Deja el límite por IP apagado: el límite por cuenta ya protege el inicio de sesión.</p></div>';
}

function gep_acceso_boton_desbloquear( $clave, $texto ) {
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
    wp_nonce_field( 'gep_acceso_desbloquear' );
    echo '<input type="hidden" name="action" value="gep_acceso_desbloquear"><input type="hidden" name="clave" value="' . esc_attr( $clave ) . '">';
    echo '<button class="button button-small" type="submit">' . esc_html( $texto ) . '</button></form>';
}

function gep_acceso_pestana_ajustes() {
    $a  = gep_acceso_ajustes();
    $ck = function ( $k, $titulo, $ayuda ) use ( $a ) {
        echo '<label class="gep-acc-op"><input type="checkbox" name="gep_acceso[' . esc_attr( $k ) . ']" value="1" ' . checked( ! empty( $a[ $k ] ), true, false ) . '> ' . esc_html( $titulo ) . '</label><span class="description">' . wp_kses( $ayuda, array( 'strong' => array(), 'code' => array() ) ) . '</span>';
    };
    $nu = function ( $k, $titulo, $min, $max ) use ( $a ) {
        echo '<p><label>' . esc_html( $titulo ) . ' <input type="number" class="small-text" name="gep_acceso[' . esc_attr( $k ) . ']" min="' . (int) $min . '" max="' . (int) $max . '" value="' . (int) $a[ $k ] . '"></label></p>';
    };
    $dis = gep_acceso_apagado_por_constante() ? ' disabled' : '';
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'gep_acceso_guardar' ); ?>
        <input type="hidden" name="action" value="gep_acceso_guardar">
        <fieldset<?php echo $dis; ?>>
        <div class="gep-acc-sec"><h2>Etapa B · Inicio de sesión</h2>
            <?php
            $ck( 'login', 'Límite de intentos y mensajes genéricos', 'Tras varios fallos la cuenta se detiene unos minutos en equipos no reconocidos. Tu computador habitual sigue entrando. El mensaje de error no dice si existe la cuenta. «¿Olvidaste tu contraseña?» tampoco lo revela.' );
            $nu( 'intentos', 'Fallos permitidos', 3, 20 );
            $nu( 'ventana_min', 'Dentro de (minutos)', 5, 120 );
            $nu( 'bloqueo_min', 'Primer bloqueo (minutos; se duplica si se repite)', 1, 120 );
            $nu( 'bloqueo_max_min', 'Bloqueo máximo (minutos)', 5, 1440 );
            ?>
        </div>
        <div class="gep-acc-sec"><h2>Etapa B · Segundo factor</h2>
            <p><?php echo gep_acceso_two_factor_activo() ? gep_acceso_insignia( 'ok' ) . ' Plugin Two Factor activo.' : gep_acceso_insignia( 'riesgo' ) . ' Plugin Two Factor NO activo: marcar estas casillas no tendrá efecto hasta instalarlo.'; ?></p>
            <?php
            $ck( 'exigir_2fa', 'Exigir segundo factor a los administradores', 'Quien aún no lo configuró entra con su contraseña y solo ve su perfil hasta configurarlo. También cierra las «contraseñas de aplicación».' );
            $ck( 'exigir_2fa_editor', 'Exigirlo también al rol Editor GEP (y Editor)', 'Recomendado cuando cada editor tenga teléfono con app de autenticación.' );
            ?>
        </div>
        <div class="gep-acc-sec"><h2>Etapa C · WordPress público</h2>
            <?php
            $ck( 'publico', 'Enviar a /gep/ a quien visite el WordPress del tema', 'Afecta solo a ' . esc_html( (string) wp_parse_url( site_url( '/' ), PHP_URL_PATH ) ) . ' (portada, ?p=, ?page_id=, búsqueda, feeds, sitemaps). No afecta al panel, al inicio de sesión, a los medios ni a las páginas del GEP. Con sesión iniciada se sigue viendo.' );
            $ck( 'publico_301', 'Redirección definitiva (301)', 'Déjalo apagado (302) mientras pruebas. Actívalo cuando todo esté confirmado: los navegadores recuerdan el 301.' );
            ?>
        </div>
        <div class="gep-acc-sec"><h2>Etapa D · Superficie</h2>
            <?php
            $ck( 'superficie', 'Reducción de superficie', 'XML-RPC responde 403; no se aceptan comentarios nuevos (los existentes se conservan); se quita la versión de WordPress del HTML; oEmbed, feeds, archivos de autor y sitemap de usuarios dejan de mostrar nombres de usuario.' );
            $ck( 'rest_anonimo', 'API REST solo con sesión (opcional)', 'El GEP no usa la API para visitantes. Actívalo al final y prueba el sitio y el editor; si algo falla, apágalo.' );
            ?>
        </div>
        <div class="gep-acc-sec"><h2>Avanzado · Límite por IP (déjalo apagado si no conoces el proxy)</h2>
            <p class="description">Apagado por defecto. Solo funciona si REMOTE_ADDR es el proxy indicado aquí; si no, se ignora. Nunca se confía en cabeceras enviadas directamente por el navegador.</p>
            <p><label>Cabecera con la IP real
                <select name="gep_acceso[ip_cabecera]">
                    <?php foreach ( array( '' => '(ninguna)', 'HTTP_X_FORWARDED_FOR' => 'X-Forwarded-For', 'HTTP_X_REAL_IP' => 'X-Real-IP', 'HTTP_CF_CONNECTING_IP' => 'CF-Connecting-IP' ) as $v => $t ) : ?>
                        <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $a['ip_cabecera'], $v ); ?>><?php echo esc_html( $t ); ?></option>
                    <?php endforeach; ?>
                </select></label></p>
            <p><label>IP(s) del proxy de confianza <input type="text" class="regular-text" name="gep_acceso[ip_proxies]" value="<?php echo esc_attr( $a['ip_proxies'] ); ?>" placeholder="10.0.0.5, 10.0.0.6"></label></p>
            <?php $nu( 'ip_limite', 'Fallos por IP antes de bloquear esa IP (0 = no)', 0, 500 ); ?>
        </div>
        </fieldset>
        <?php submit_button( 'Guardar ajustes', 'primary', 'submit', true, $dis ? array( 'disabled' => 'disabled' ) : null ); ?>
    </form>
    <?php
}

function gep_acceso_pestana_registro() {
    $ver   = isset( $_GET['ver'] ) && 'fallos' === $_GET['ver'] ? 'fallos' : 'eventos';
    $lista = get_option( 'fallos' === $ver ? 'gep_acceso_registro_fallos' : 'gep_acceso_registro', array() );
    $lista = is_array( $lista ) ? array_reverse( $lista ) : array();
    $nombres = array( 'acceso' => 'Inicio de sesión', 'bloqueo' => 'Bloqueo', 'fallo' => 'Fallo', 'recuperacion' => 'Contraseña', 'ajustes' => 'Ajustes', 'config' => 'Configuración', 'plugin' => 'Plugin/tema', 'rol' => 'Rol', 'usuario' => 'Usuario', 'dos_factores' => 'Segundo factor' );
    echo '<div class="gep-acc-sec"><h2>Registro de seguridad</h2><p>';
    echo '<a class="button ' . ( 'eventos' === $ver ? 'button-primary' : '' ) . '" href="' . esc_url( gep_acceso_panel_url( 'registro' ) ) . '">Eventos importantes</a> ';
    echo '<a class="button ' . ( 'fallos' === $ver ? 'button-primary' : '' ) . '" href="' . esc_url( gep_acceso_panel_url( 'registro', array( 'ver' => 'fallos' ) ) ) . '">Contraseñas incorrectas</a></p>';
    echo '<p class="description">Últimos 200 de cada tipo, 30 días como máximo. IP recortada (último bloque oculto). Nunca se guardan contraseñas ni códigos.</p>';
    if ( ! $lista ) {
        echo '<p>Sin registros.</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr><th>Fecha</th><th>Tipo</th><th>Cuenta</th><th>Detalle</th><th>IP</th></tr></thead><tbody>';
        foreach ( $lista as $e ) {
            echo '<tr><td>' . esc_html( wp_date( 'Y-m-d H:i:s', (int) $e['t'] ) ) . '</td><td>' . esc_html( isset( $nombres[ $e['e'] ] ) ? $nombres[ $e['e'] ] : $e['e'] ) . '</td><td>' . esc_html( $e['c'] ) . '</td><td>' . esc_html( $e['d'] ) . '</td><td><code>' . esc_html( $e['i'] ) . '</code></td></tr>';
        }
        echo '</tbody></table>';
    }
    if ( ! gep_acceso_apagado_por_constante() ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:12px" onsubmit="return confirm(\'¿Vaciar todo el registro?\')">';
        wp_nonce_field( 'gep_acceso_limpiar_registro' );
        echo '<input type="hidden" name="action" value="gep_acceso_limpiar_registro"><button class="button">Vaciar registro</button></form>';
    }
    echo '</div>';
}

function gep_acceso_pestana_revision() {
    echo '<div class="gep-acc-sec"><h2>Configuración</h2><p class="description">Solo lectura. Nada se cambia automáticamente.</p>';
    gep_acceso_tabla_revision( gep_acceso_revision_config() );
    echo '</div><div class="gep-acc-sec"><h2>Archivos sobrantes</h2><p class="description">nginx entrega estos archivos directamente, sin pasar por WordPress: la única protección es borrarlos o moverlos (FileZilla).</p>';
    gep_acceso_tabla_revision( gep_acceso_revision_archivos() );
    echo '</div>';
}
