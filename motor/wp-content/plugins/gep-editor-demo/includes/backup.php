<?php
/**
 * GEP Editor · Respaldo completo del contenido
 *
 * Descarga en un solo archivo JSON todo el contenido que administra GEP Editor
 * (las nueve pestañas) y lo restaura desde ese archivo. Las páginas se
 * identifican por su pestaña (inicio, quienes-somos, …) y no por su número
 * interno, así el respaldo sirve también en una instalación nueva de WordPress.
 *
 * Las imágenes y videos no van dentro del archivo: quedan en la Mediateca
 * (wp-content/uploads), que cubre el respaldo del servidor. El archivo trae la
 * lista de los que usa el contenido para poder revisarlos.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GEP_DEMO_BACKUP_FORMAT', 'GEP Editor full backup' );
define( 'GEP_DEMO_BACKUP_MAX_BYTES', 20 * MB_IN_BYTES );

function gep_demo_backup_context_map() {
    return array(
        'inicio'         => 'home',
        'quienes-somos'  => 'directory',
        'docencia'       => 'docencia',
        'proyectos'      => 'projects',
        'servicios'      => 'services',
        'publicaciones'  => 'publications',
        'comunicaciones' => 'communications',
        'noticias'       => 'news',
        'contacto'       => 'contact',
    );
}

/** Junta las URLs de archivos de la Mediateca que aparecen en el contenido. */
function gep_demo_backup_collect_media( $value, &$found ) {
    if ( is_array( $value ) ) {
        foreach ( $value as $item ) {
            gep_demo_backup_collect_media( $item, $found );
        }
        return;
    }
    if ( ! is_string( $value ) || false === strpos( $value, '/uploads/' ) ) {
        return;
    }
    if ( preg_match_all( '#https?://[^\s"\'<>\\\\]+/uploads/[^\s"\'<>\\\\]+#i', $value, $matches ) ) {
        foreach ( $matches[0] as $url ) {
            $found[ $url ] = true;
        }
    }
}

function gep_demo_backup_build_payload() {
    $navigation = gep_demo_get_page_navigation();
    $pages      = array();
    $media      = array();

    foreach ( gep_demo_backup_context_map() as $key => $context ) {
        if ( empty( $navigation[ $key ]['page'] ) ) {
            continue;
        }
        $page     = $navigation[ $key ]['page'];
        $snapshot = gep_demo_history_capture_snapshot( (int) $page->ID );
        gep_demo_backup_collect_media( $snapshot, $media );

        $pages[ $key ] = array(
            'label'  => $navigation[ $key ]['label'],
            'title'  => $page->post_title,
            'slug'   => $page->post_name,
            'status' => $page->post_status,
            'fields' => count( $snapshot ),
            'meta'   => $snapshot,
        );
    }

    $media = array_keys( $media );
    sort( $media, SORT_STRING );

    return array(
        'format'     => GEP_DEMO_BACKUP_FORMAT,
        'format_rev' => 1,
        'plugin'     => 'GEP Editor Beta',
        'version'    => GEP_EDITOR_VERSION,
        'created_at' => current_time( DATE_ATOM ),
        'site'       => home_url( '/' ),
        'author'     => wp_get_current_user()->display_name,
        'pages'      => $pages,
        'media'      => $media,
    );
}

function gep_demo_backup_handle_export() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'No tienes permisos para descargar el respaldo.' );
    }
    check_admin_referer( 'gep_demo_backup_export' );

    $payload  = gep_demo_backup_build_payload();
    if ( function_exists( 'gep_diag_marcar_respaldo' ) ) {
        gep_diag_marcar_respaldo(); // v10.0.0: Diagnóstico muestra la fecha del último respaldo.
    }
    $filename = 'gep-respaldo-completo-' . current_time( 'Y-m-d-Hi' ) . '.json';

    nocache_headers();
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    exit;
}
add_action( 'admin_post_gep_demo_backup_export', 'gep_demo_backup_handle_export' );

/** Limpia un valor que viene de un archivo subido antes de guardarlo. */
function gep_demo_backup_clean_value( $value ) {
    if ( is_array( $value ) ) {
        $clean = array();
        foreach ( $value as $k => $v ) {
            $clean[ is_int( $k ) ? $k : sanitize_text_field( (string) $k ) ] = gep_demo_backup_clean_value( $v );
        }
        return $clean;
    }
    if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
        return $value;
    }
    // Solo se filtra el HTML; el texto normal (con &, comillas, etc.) queda idéntico.
    $value = (string) $value;
    return false === strpos( $value, '<' ) ? $value : wp_kses_post( $value );
}

function gep_demo_backup_clean_meta( $key, $value ) {
    // Los campos *_json guardan texto JSON: se limpia por dentro y se vuelve a armar igual.
    if ( is_string( $value ) && '_json' === substr( $key, -5 ) ) {
        $decoded = json_decode( $value, true );
        if ( JSON_ERROR_NONE === json_last_error() ) {
            return wp_json_encode( gep_demo_backup_clean_value( $decoded ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        }
    }
    return gep_demo_backup_clean_value( $value );
}

function gep_demo_backup_redirect( $notice, $extra = array() ) {
    if ( in_array( $notice, array( 'backup-invalid', 'backup-nofile' ), true ) && function_exists( 'gep_registro' ) ) {
        gep_registro( 'respaldo', 'backup-invalid' === $notice ? 'Se intentó restaurar un archivo de respaldo no válido o demasiado grande.' : 'Se intentó restaurar sin archivo o la subida falló.' );
    }
    wp_safe_redirect( gep_demo_history_safe_return_url( array_merge( array( 'gep_history_notice' => $notice ), $extra ) ) );
    exit;
}

function gep_demo_backup_handle_import() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'No tienes permisos para restaurar un respaldo.' );
    }
    check_admin_referer( 'gep_demo_backup_import', 'gep_backup_nonce' );

    if ( empty( $_POST['gep_backup_confirm'] ) ) {
        gep_demo_backup_redirect( 'backup-confirm' );
    }

    $file = isset( $_FILES['gep_backup_file'] ) ? $_FILES['gep_backup_file'] : null;
    if ( ! $file || ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
        gep_demo_backup_redirect( 'backup-nofile' );
    }
    if ( (int) $file['size'] > GEP_DEMO_BACKUP_MAX_BYTES ) {
        gep_demo_backup_redirect( 'backup-invalid' );
    }

    $data = json_decode( (string) file_get_contents( $file['tmp_name'] ), true );
    if ( ! gep_demo_backup_is_valid( $data ) ) {
        gep_demo_backup_redirect( 'backup-invalid' );
    }

    $result = gep_demo_backup_restore( $data );
    gep_demo_backup_redirect( $result['restored'] || $result['created'] ? 'backup-restored' : 'backup-same', array( 'restored' => $result['restored'] ) );
}
add_action( 'admin_post_gep_demo_backup_import', 'gep_demo_backup_handle_import' );

function gep_demo_backup_is_valid( $data ) {
    return is_array( $data ) && isset( $data['format'] ) && GEP_DEMO_BACKUP_FORMAT === $data['format'] && ! empty( $data['pages'] ) && is_array( $data['pages'] );
}

/** Aplica un respaldo ya leído. Devuelve cuántas pestañas se restauraron y cuántas páginas se crearon. */
function gep_demo_backup_restore( $data ) {
    $navigation = gep_demo_get_page_navigation();
    $map        = gep_demo_backup_context_map();
    $created_on = isset( $data['created_at'] ) ? strtotime( (string) $data['created_at'] ) : 0;
    $date_label = $created_on ? wp_date( 'd/m/Y H:i', $created_on ) : 'fecha desconocida';
    $restored   = 0;
    $created    = 0;

    foreach ( $map as $key => $context ) {
        if ( empty( $data['pages'][ $key ]['meta'] ) || ! is_array( $data['pages'][ $key ]['meta'] ) ) {
            continue;
        }
        $entry = $data['pages'][ $key ];

        $post_id = ! empty( $navigation[ $key ]['page'] ) ? (int) $navigation[ $key ]['page']->ID : 0;
        if ( ! $post_id ) {
            // Instalación nueva: se crea la página que falta con el nombre del respaldo.
            if ( ! current_user_can( 'publish_pages' ) ) {
                continue;
            }
            $post_id = wp_insert_post(
                array(
                    'post_type'   => 'page',
                    'post_status' => 'publish',
                    'post_title'  => sanitize_text_field( isset( $entry['title'] ) && '' !== $entry['title'] ? $entry['title'] : $navigation[ $key ]['label'] ),
                    'post_name'   => sanitize_title( isset( $entry['slug'] ) && '' !== $entry['slug'] ? $entry['slug'] : $key ),
                ),
                true
            );
            if ( is_wp_error( $post_id ) || ! $post_id ) {
                continue;
            }
            $created++;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            continue;
        }

        $target = array();
        foreach ( $entry['meta'] as $meta_key => $value ) {
            $meta_key = (string) $meta_key;
            if ( 0 !== strpos( $meta_key, 'gep_' ) || 0 === strpos( $meta_key, 'gep_history_' ) || sanitize_key( $meta_key ) !== $meta_key ) {
                continue;
            }
            $target[ $meta_key ] = gep_demo_backup_clean_meta( $meta_key, $value );
            // v9.27.1 · Un respaldo anterior al traslado trae /gep/wordpress/: se escribe con la ubicación actual.
            if ( function_exists( 'gep_cms_normalize_deep' ) ) {
                $target[ $meta_key ] = gep_cms_normalize_deep( $target[ $meta_key ] );
            }
        }
        if ( ! $target ) {
            continue;
        }

        gep_demo_history_ensure_baseline( $post_id, $context );
        $before = gep_demo_history_capture_snapshot( $post_id );
        if ( gep_demo_history_snapshot_hash( $before ) === gep_demo_history_snapshot_hash( $target ) ) {
            continue;
        }

        gep_demo_history_create_entry( $post_id, $context, 'checkpoint', $before, $before, array( 'note' => 'Punto de seguridad automático antes de restaurar el respaldo completo.' ) );

        foreach ( array_keys( $before ) as $meta_key ) {
            if ( ! array_key_exists( $meta_key, $target ) ) {
                delete_post_meta( $post_id, $meta_key );
            }
        }
        foreach ( $target as $meta_key => $value ) {
            update_post_meta( $post_id, $meta_key, wp_slash( $value ) );
        }

        $after = gep_demo_history_capture_snapshot( $post_id );
        gep_demo_history_create_entry( $post_id, $context, 'restore', $before, $after, array( 'note' => sprintf( 'Restaurado desde el respaldo completo del %s.', $date_label ) ) );
        $restored++;
    }

    return array( 'restored' => $restored, 'created' => $created );
}

/** Tarjeta "Respaldo completo" dentro de Historial de cambios. */
function gep_demo_backup_render_panel() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        return;
    }
    $export_url = wp_nonce_url( admin_url( 'admin-post.php?action=gep_demo_backup_export' ), 'gep_demo_backup_export' );
    ?>
    <details class="gep-hx-card gep-hx-backup">
        <summary>
            <span class="gep-hx-backup-icon"><span class="dashicons dashicons-download"></span></span>
            <span class="gep-hx-backup-copy"><strong>Respaldo completo</strong><small>Descarga todo el contenido del editor en un archivo, o vuelve a cargarlo.</small></span>
            <span class="gep-hx-backup-toggle dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
        </summary>
        <div class="gep-hx-backup-body">
            <div class="gep-hx-backup-col">
                <h3>Descargar</h3>
                <p>Guarda en tu computador un archivo con los textos y la configuración de las nueve pestañas. Hazlo antes de cambios grandes y guárdalo fuera del servidor.</p>
                <a class="button button-primary" href="<?php echo esc_url( $export_url ); ?>"><span class="dashicons dashicons-download"></span> Descargar respaldo</a>
                <p class="gep-hx-backup-note">Las imágenes y videos no van dentro del archivo: siguen en la Mediateca. El archivo trae la lista de los que se usan.</p>
            </div>
            <form class="gep-hx-backup-col" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <h3>Restaurar</h3>
                <p>Reemplaza el contenido de todas las pestañas por el del archivo. Antes de cambiar nada se guarda un punto de seguridad en este historial, así que se puede deshacer.</p>
                <input type="hidden" name="action" value="gep_demo_backup_import">
                <?php wp_nonce_field( 'gep_demo_backup_import', 'gep_backup_nonce' ); ?>
                <input type="file" name="gep_backup_file" accept=".json,application/json" required>
                <label class="gep-hx-backup-check"><input type="checkbox" name="gep_backup_confirm" value="1" required> Entiendo que el contenido actual se reemplaza por el del archivo.</label>
                <button type="submit" class="button"><span class="dashicons dashicons-upload"></span> Restaurar respaldo</button>
            </form>
        </div>
    </details>
    <?php
}
