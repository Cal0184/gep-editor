<?php
/**
 * GEP Editor 10.0.0 · Diagnóstico (Contenido GEP → Diagnóstico).
 *
 * Solo administradores (manage_options). Solo lee: no cambia nada, salvo el
 * botón «Vaciar registro técnico». Revisa en un minuto lo que antes había que
 * pedirle al desarrollador:
 *   - versiones y requisitos (WordPress, PHP, base de datos, plugins GEP);
 *   - ubicación del CMS, HTTPS, instalador bloqueado, sitio privado;
 *   - las 9 páginas del editor y que su contenido se pueda leer (JSON sano);
 *   - fotos y archivos que una página usa pero ya no existen;
 *   - direcciones antiguas (/gep/wordpress/, gepdev) que sigan guardadas;
 *   - carpeta de subidas con permiso de escritura y espacio libre;
 *   - fecha del último respaldo descargado;
 *   - registro técnico de errores.
 * El botón «Copiar informe» copia un resumen sin rutas del servidor, claves ni
 * contenido, para enviarlo a quien dé soporte.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', function () {
    add_submenu_page( 'gep-content-editor', 'Diagnóstico', 'Diagnóstico', 'manage_options', 'gep-diagnostico', 'gep_diag_pantalla' );
}, 21 );

/* Fecha del último respaldo descargado (lo marca backup.php). */
function gep_diag_marcar_respaldo() {
    update_option( 'gep_ultimo_respaldo', array( 't' => time(), 'user' => get_current_user_id() ), false );
}

add_action( 'admin_post_gep_diag_vaciar', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Sin permisos.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'gep_diag_vaciar' );
    if ( function_exists( 'gep_registro_vaciar' ) ) {
        gep_registro_vaciar();
    }
    wp_safe_redirect( add_query_arg( array( 'page' => 'gep-diagnostico', 'vaciado' => 1 ), admin_url( 'admin.php' ) ) );
    exit;
} );

/** Fila: [nivel ok|aviso|riesgo|info, título, detalle]. */
function gep_diag_fila( $nivel, $titulo, $detalle = '' ) {
    return array( $nivel, $titulo, $detalle );
}

function gep_diag_sistema() {
    global $wpdb;
    $r   = array();
    $php = PHP_VERSION;
    $r[] = version_compare( $php, '8.1', '>=' ) ? gep_diag_fila( 'ok', 'PHP ' . $php, '' ) : gep_diag_fila( 'aviso', 'PHP ' . $php, 'GEP se probó con PHP 8.1 a 8.4.' );
    $wpv = get_bloginfo( 'version' );
    $r[] = version_compare( $wpv, '6.5', '>=' ) ? gep_diag_fila( 'ok', 'WordPress ' . $wpv, '' ) : gep_diag_fila( 'aviso', 'WordPress ' . $wpv, 'GEP requiere WordPress 6.5 o superior.' );
    $db  = method_exists( $wpdb, 'db_server_info' ) ? (string) $wpdb->db_server_info() : (string) $wpdb->db_version();
    $r[] = gep_diag_fila( 'ok', 'Base de datos conectada', preg_replace( '/^5\.5\.5-/', '', $db ) . ' · prefijo de tablas ' . $wpdb->prefix );

    $plug = array();
    $plug[] = 'GEP Editor ' . GEP_EDITOR_VERSION;
    $plug[] = defined( 'GEP_ACCESO_VERSION' ) ? 'gep-acceso ' . GEP_ACCESO_VERSION : 'gep-acceso no está';
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $todos = get_plugins();
    $plug[] = isset( $todos['gep-media-organizer/gep-media-organizer.php'] )
        ? 'Media Organizer ' . $todos['gep-media-organizer/gep-media-organizer.php']['Version'] . ( is_plugin_active( 'gep-media-organizer/gep-media-organizer.php' ) ? '' : ' (inactivo)' )
        : 'Media Organizer no está';
    $r[] = gep_diag_fila( defined( 'GEP_ACCESO_VERSION' ) ? 'ok' : 'aviso', 'Componentes GEP', implode( ' · ', $plug ) );

    $ext = array();
    foreach ( array( 'mysqli', 'json', 'mbstring', 'gd', 'curl', 'zip' ) as $e ) {
        if ( ! extension_loaded( $e ) && ! ( 'gd' === $e && extension_loaded( 'imagick' ) ) ) {
            $ext[] = $e;
        }
    }
    $r[] = $ext ? gep_diag_fila( 'aviso', 'Faltan extensiones de PHP', implode( ', ', $ext ) . ' (gd: fotos livianas; curl: búsqueda por DOI).' ) : gep_diag_fila( 'ok', 'Extensiones de PHP completas', 'mysqli, json, mbstring, gd, curl, zip' );
    return $r;
}

function gep_diag_configuracion() {
    $r   = array();
    $wp  = basename( untrailingslashit( ABSPATH ) );
    $car = defined( 'GEP_CMS_CARPETA' ) ? GEP_CMS_CARPETA : 'motor';
    $r[] = $wp === $car ? gep_diag_fila( 'ok', 'WordPress está en la carpeta «' . $car . '»', site_url( '/' ) ) : gep_diag_fila( 'riesgo', 'WordPress no está en la carpeta «' . $car . '»', 'Está en «' . $wp . '». Revisa la guía de Operación Motor.' );
    $r[] = defined( 'GEP_INSTALADOR_BLOQUEADO' ) ? gep_diag_fila( 'ok', 'Instalador de WordPress bloqueado', 'install.php y setup-config.php responden «no disponible».' ) : gep_diag_fila( 'riesgo', 'Instalador de WordPress sin bloquear', 'Sube gep-config-migracion.php 10.0.0 a /gep/motor/.' );
    $r[] = is_ssl() ? gep_diag_fila( 'ok', 'HTTPS reconocido', '' ) : gep_diag_fila( 'aviso', 'Esta pantalla no se ve como HTTPS', 'En el servidor de la Universidad, GEP_PROXY_HTTPS debe ser true.' );
    $pub = function_exists( 'gep_editor_public_base_url' ) ? gep_editor_public_base_url() : '';
    $r[] = gep_diag_fila( 'info', 'Dirección del sitio público', $pub );
    if ( function_exists( 'gep_privado_encendido' ) ) {
        $r[] = gep_privado_encendido() ? gep_diag_fila( 'info', 'Sitio privado: pide usuario y clave', 'Se cambia en Contenido GEP → Acceso al sitio.' ) : gep_diag_fila( 'info', 'Sitio público: abierto a todos', 'Se cambia en Contenido GEP → Acceso al sitio.' );
    }
    $debug_vis = defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY );
    $r[] = $debug_vis ? gep_diag_fila( 'riesgo', 'Los errores de PHP se muestran en pantalla', "En wp-config.php: define( 'WP_DEBUG', false );" ) : gep_diag_fila( 'ok', 'Los errores de PHP no se muestran a visitantes', '' );
    return $r;
}

function gep_diag_paginas_y_contenido() {
    $r       = array();
    $nav     = function_exists( 'gep_demo_get_page_navigation' ) ? gep_demo_get_page_navigation() : array();
    $faltan  = array();
    $borr    = array();
    $malos   = array();
    $campos  = 0;
    foreach ( $nav as $item ) {
        if ( empty( $item['page'] ) ) {
            $faltan[] = $item['label'];
            continue;
        }
        if ( 'publish' !== $item['page']->post_status ) {
            $borr[] = $item['label'] . ' (' . $item['page']->post_status . ')';
        }
        foreach ( (array) get_post_meta( $item['page']->ID ) as $clave => $valores ) {
            if ( 0 !== strpos( $clave, 'gep_' ) || '_json' !== substr( $clave, -5 ) ) {
                continue;
            }
            $campos++;
            $v = (string) maybe_unserialize( $valores[0] );
            if ( '' === trim( $v ) ) {
                continue;
            }
            json_decode( $v );
            if ( JSON_ERROR_NONE !== json_last_error() ) {
                json_decode( wp_unslash( $v ) );
                if ( JSON_ERROR_NONE !== json_last_error() ) {
                    $malos[] = $item['label'] . ' → ' . $clave;
                }
            }
        }
    }
    $r[] = $faltan ? gep_diag_fila( 'riesgo', 'Faltan páginas del editor', implode( ', ', $faltan ) . '. Crea la página en Páginas → Añadir con ese nombre.' ) : gep_diag_fila( 'ok', 'Las ' . count( $nav ) . ' páginas del editor existen', '' );
    if ( $borr ) {
        $r[] = gep_diag_fila( 'aviso', 'Páginas sin publicar', implode( ', ', $borr ) );
    }
    $r[] = $malos ? gep_diag_fila( 'riesgo', 'Contenido que no se puede leer', implode( '; ', $malos ) . '. Restaura una versión anterior en Historial de cambios.' ) : gep_diag_fila( 'ok', 'Contenido íntegro', $campos . ' bloques de contenido revisados, todos legibles.' );
    return $r;
}

/** Direcciones de archivos subidos que usan las páginas GEP. */
function gep_diag_urls_de_subidas() {
    global $wpdb;
    $ids = array();
    foreach ( (array) ( function_exists( 'gep_demo_get_page_navigation' ) ? gep_demo_get_page_navigation() : array() ) as $item ) {
        if ( ! empty( $item['page']->ID ) ) {
            $ids[ (int) $item['page']->ID ] = $item['label'];
        }
    }
    if ( ! $ids ) {
        return array( array(), $ids );
    }
    $ids_sql = implode( ',', array_map( 'intval', array_keys( $ids ) ) );
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $filas = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($ids_sql) AND meta_key LIKE 'gep\\_%' AND meta_value LIKE '%wp-content%uploads%'" );
    $urls  = array();
    foreach ( (array) $filas as $f ) {
        $v = str_replace( '\\/', '/', (string) $f->meta_value );
        if ( preg_match_all( '~wp-content/uploads/([^"\'\s<>?#\\\\]+\.[a-z0-9]{2,5})~i', $v, $m ) ) {
            foreach ( $m[1] as $rel ) {
                $urls[ rawurldecode( $rel ) ][ (int) $f->post_id ] = $ids[ (int) $f->post_id ];
            }
        }
    }
    return array( $urls, $ids );
}

function gep_diag_archivos() {
    global $wpdb;
    $r  = array();
    $up = wp_get_upload_dir();
    list( $urls, $ids ) = gep_diag_urls_de_subidas();
    $faltan = array();
    foreach ( $urls as $rel => $paginas ) {
        if ( ! is_file( trailingslashit( $up['basedir'] ) . $rel ) ) {
            $faltan[] = $rel . ' (' . implode( ', ', $paginas ) . ')';
        }
    }
    $r[] = $faltan
        ? gep_diag_fila( 'riesgo', count( $faltan ) . ' archivo(s) usados en páginas que no existen', implode( '; ', array_slice( $faltan, 0, 15 ) ) . ( count( $faltan ) > 15 ? ' …' : '' ) . '. Elige de nuevo la foto en esa página.' )
        : gep_diag_fila( 'ok', 'Todos los archivos usados existen', count( $urls ) . ' archivos revisados.' );

    // Direcciones antiguas aún guardadas (se corrigen solas al mostrarse, pero conviene saberlo).
    if ( $ids ) {
        $ids_sql = implode( ',', array_map( 'intval', array_keys( $ids ) ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $viejas = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id IN ($ids_sql) AND meta_key LIKE 'gep\\_%' AND ( meta_value LIKE '%gepdev.bonfyr.cl%' OR meta_value LIKE '%/gep/wordpress/%' OR meta_value LIKE '%\\\\/gep\\\\/wordpress\\\\/%' )" );
        $r[] = $viejas
            ? gep_diag_fila( 'info', $viejas . ' campo(s) con direcciones antiguas', 'Contienen /gep/wordpress/ o gepdev.bonfyr.cl. El sitio las corrige al mostrarlas; se actualizan solas al guardar esa página.' )
            : gep_diag_fila( 'ok', 'Sin direcciones antiguas guardadas', '' );
    }

    // Subidas.
    if ( ! empty( $up['error'] ) ) {
        $r[] = gep_diag_fila( 'riesgo', 'Carpeta de subidas con error', gep_registro_limpiar( $up['error'] ) );
    } else {
        $r[] = wp_is_writable( $up['basedir'] ) ? gep_diag_fila( 'ok', 'Se pueden subir archivos', 'La carpeta de subidas acepta archivos nuevos.' ) : gep_diag_fila( 'riesgo', 'No se pueden subir archivos', 'La carpeta wp-content/uploads no tiene permiso de escritura (FileZilla → Permisos: 755).' );
        $libre = function_exists( 'disk_free_space' ) ? @disk_free_space( $up['basedir'] ) : false;
        if ( false !== $libre ) {
            $gb  = $libre / 1073741824;
            $r[] = $gb < 1 ? gep_diag_fila( 'riesgo', sprintf( 'Queda poco espacio: %.1f GB', $gb ), 'Borra respaldos o videos viejos del servidor.' ) : gep_diag_fila( 'ok', sprintf( 'Espacio libre: %.1f GB', $gb ), '' );
        }
    }
    $adj = wp_count_posts( 'attachment' );
    $r[] = gep_diag_fila( 'info', 'Archivos en la Mediateca', (int) ( isset( $adj->inherit ) ? $adj->inherit : 0 ) . ' archivos.' );
    return $r;
}

function gep_diag_respaldo() {
    $u = get_option( 'gep_ultimo_respaldo', array() );
    if ( empty( $u['t'] ) ) {
        return array( gep_diag_fila( 'aviso', 'No hay registro de un respaldo descargado', 'Historial de cambios → Respaldo completo → Descargar. Guárdalo fuera del servidor (ver Manual de respaldo).' ) );
    }
    $dias = floor( ( time() - (int) $u['t'] ) / DAY_IN_SECONDS );
    $txt  = 'Último: ' . wp_date( 'd-m-Y H:i', (int) $u['t'] ) . ( ! empty( $u['user'] ) && get_userdata( $u['user'] ) ? ' por ' . get_userdata( $u['user'] )->display_name : '' ) . '.';
    return array( $dias > 7 ? gep_diag_fila( 'aviso', 'El último respaldo tiene ' . $dias . ' días', $txt . ' Se recomienda uno por semana.' ) : gep_diag_fila( 'ok', 'Respaldo al día', $txt ) );
}

function gep_diag_insignia( $nivel ) {
    $t = array( 'ok' => 'Bien', 'aviso' => 'Revisar', 'riesgo' => 'Corregir', 'info' => 'Info' );
    return '<span class="gep-diag-b is-' . esc_attr( $nivel ) . '">' . esc_html( isset( $t[ $nivel ] ) ? $t[ $nivel ] : $nivel ) . '</span>';
}

function gep_diag_pantalla() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $grupos = array(
        'Sistema y requisitos'         => gep_diag_sistema(),
        'Configuración'                => gep_diag_configuracion(),
        'Páginas y contenido'          => gep_diag_paginas_y_contenido(),
        'Archivos y almacenamiento'    => gep_diag_archivos(),
        'Respaldo'                     => gep_diag_respaldo(),
    );
    $informe = array( 'Diagnóstico GEP · ' . wp_date( 'd-m-Y H:i' ) );
    $cuenta  = array( 'riesgo' => 0, 'aviso' => 0 );
    foreach ( $grupos as $titulo => $filas ) {
        $informe[] = '';
        $informe[] = '== ' . $titulo;
        foreach ( $filas as $f ) {
            if ( isset( $cuenta[ $f[0] ] ) ) {
                $cuenta[ $f[0] ]++;
            }
            $informe[] = '[' . strtoupper( $f[0] ) . '] ' . $f[1] . ( '' !== $f[2] ? ' — ' . $f[2] : '' );
        }
    }
    $registro = function_exists( 'gep_registro_leer' ) ? gep_registro_leer() : array();
    $informe[] = '';
    $informe[] = '== Registro técnico (' . count( $registro ) . ')';
    foreach ( array_slice( $registro, 0, 20 ) as $e ) {
        $informe[] = wp_date( 'd-m-Y H:i', (int) $e['t'] ) . ' · ' . $e['tipo'] . ' · ' . $e['msg'] . ( ! empty( $e['n'] ) ? ' (x' . (int) $e['n'] . ')' : '' );
    }
    $texto = gep_registro_limpiar_multilinea( implode( "\n", $informe ) );
    ?>
    <div class="wrap gep-diag">
        <h1>Diagnóstico</h1>
        <p class="description">Revisión automática del editor y del sitio. Solo lee: no cambia nada. Pantalla visible solo para administradores.</p>
        <?php if ( ! empty( $_GET['vaciado'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Registro técnico vaciado.</p></div>
        <?php endif; ?>
        <div class="gep-diag-resumen <?php echo $cuenta['riesgo'] ? 'is-riesgo' : ( $cuenta['aviso'] ? 'is-aviso' : 'is-ok' ); ?>">
            <strong><?php echo $cuenta['riesgo'] ? esc_html( $cuenta['riesgo'] . ' punto(s) por corregir' ) : ( $cuenta['aviso'] ? esc_html( $cuenta['aviso'] . ' punto(s) por revisar' ) : 'Todo en orden' ); ?></strong>
            <span>Seguridad (contraseñas, segundo factor, archivos sobrantes): <a href="<?php echo esc_url( function_exists( 'gep_acceso_panel_url' ) ? gep_acceso_panel_url( 'revision' ) : admin_url( 'tools.php' ) ); ?>">Herramientas → Seguridad GEP → Revisión</a>.</span>
            <button type="button" class="button gep-diag-copiar">Copiar informe</button>
        </div>
        <?php foreach ( $grupos as $titulo => $filas ) : ?>
            <h2><?php echo esc_html( $titulo ); ?></h2>
            <table class="widefat striped gep-diag-tabla"><tbody>
            <?php foreach ( $filas as $f ) : ?>
                <tr><td class="gep-diag-n"><?php echo gep_diag_insignia( $f[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td><td><strong><?php echo esc_html( $f[1] ); ?></strong><?php if ( '' !== $f[2] ) : ?><br><span class="description"><?php echo esc_html( $f[2] ); ?></span><?php endif; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endforeach; ?>

        <h2>Registro técnico</h2>
        <p class="description">Últimos problemas del editor (máximo 100). No guarda contraseñas, contenido ni rutas del servidor. Los intentos de inicio de sesión están en el registro de seguridad.</p>
        <?php if ( ! $registro ) : ?>
            <p><?php echo gep_diag_insignia( 'ok' ); // phpcs:ignore ?> Sin errores registrados.</p>
        <?php else : ?>
            <table class="widefat striped gep-diag-tabla"><thead><tr><th>Fecha</th><th>Tipo</th><th>Qué pasó</th><th>Persona</th></tr></thead><tbody>
            <?php foreach ( $registro as $e ) : $u = ! empty( $e['user'] ) ? get_userdata( (int) $e['user'] ) : null; ?>
                <tr><td><?php echo esc_html( wp_date( 'd-m-Y H:i', (int) $e['t'] ) ); ?></td><td><?php echo esc_html( $e['tipo'] ); ?></td><td><?php echo esc_html( $e['msg'] ); ?><?php echo '' !== $e['donde'] ? '<br><span class="description">' . esc_html( $e['donde'] ) . '</span>' : ''; ?><?php echo ! empty( $e['n'] ) ? ' <span class="description">(' . (int) $e['n'] . ' veces)</span>' : ''; ?></td><td><?php echo $u ? esc_html( $u->display_name ) : '—'; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px">
                <input type="hidden" name="action" value="gep_diag_vaciar"><?php wp_nonce_field( 'gep_diag_vaciar' ); ?>
                <button type="submit" class="button">Vaciar registro técnico</button>
            </form>
        <?php endif; ?>
        <textarea class="gep-diag-texto" readonly hidden><?php echo esc_textarea( $texto ); ?></textarea>
    </div>
    <style>
        .gep-diag{max-width:980px}
        .gep-diag h2{margin-top:26px}
        .gep-diag-resumen{display:flex;flex-wrap:wrap;gap:8px 16px;align-items:center;margin:16px 0;padding:14px 16px;border-radius:10px;border:1px solid #c9dccf;background:#eef6f0;color:#173c2a}
        .gep-diag-resumen.is-aviso{background:#fff5e5;border-color:#f0cf96;color:#5a3a00}
        .gep-diag-resumen.is-riesgo{background:#fdecec;border-color:#f1b5b5;color:#6b1212}
        .gep-diag-resumen .button{margin-left:auto}
        .gep-diag-n{width:90px}
        .gep-diag-b{display:inline-block;min-width:64px;text-align:center;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#e6ece8;color:#33423a}
        .gep-diag-b.is-ok{background:#dcefe3;color:#1f5a3b}.gep-diag-b.is-aviso{background:#fdebc8;color:#7a4b00}.gep-diag-b.is-riesgo{background:#f9d4d4;color:#8a1616}
        body.gep-theme-dark .gep-diag-resumen{background:#16201c;border-color:#2c3a34;color:#e7efea}
    </style>
    <script>
    (function(){
        var b = document.querySelector('.gep-diag-copiar'), t = document.querySelector('.gep-diag-texto');
        if (!b || !t) return;
        b.addEventListener('click', function(){
            var listo = function(){ b.textContent = 'Copiado'; setTimeout(function(){ b.textContent = 'Copiar informe'; }, 2000); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t.value).then(listo); return; }
            t.hidden = false; t.select(); try { document.execCommand('copy'); listo(); } catch (e) {} t.hidden = true;
        });
    })();
    </script>
    <?php
}

/** Igual que gep_registro_limpiar pero conserva los saltos de línea del informe. */
function gep_registro_limpiar_multilinea( $texto ) {
    return implode( "\n", array_map( function ( $l ) {
        return '' === trim( $l ) ? '' : gep_registro_limpiar( $l );
    }, explode( "\n", (string) $texto ) ) );
}
