<?php
/**
 * GEP Editor · Historial de cambios
 *
 * Capa de revisiones propia para el contenido administrado por el plugin.
 * No modifica la web pública ni depende del sistema de revisiones de WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'GEP_DEMO_HISTORY_LIMIT_PER_PAGE' ) ) {
    define( 'GEP_DEMO_HISTORY_LIMIT_PER_PAGE', 100 );
}

function gep_demo_history_register_post_type() {
    register_post_type(
        'gep_change',
        array(
            'labels' => array(
                'name'          => 'Historial GEP',
                'singular_name' => 'Cambio GEP',
            ),
            'public'              => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
            'supports'            => array( 'title', 'editor', 'author' ),
            'capability_type'      => 'post',
            'map_meta_cap'         => true,
            'rewrite'              => false,
            'query_var'            => false,
        )
    );
}
add_action( 'init', 'gep_demo_history_register_post_type' );

/**
 * Captura únicamente los metadatos de contenido administrados por GEP.
 */
function gep_demo_history_capture_snapshot( $post_id ) {
    $all      = get_post_meta( $post_id );
    $snapshot = array();

    foreach ( $all as $key => $values ) {
        if ( 0 !== strpos( (string) $key, 'gep_' ) ) {
            continue;
        }

        // Reservado para metadatos operativos si se agregan en el futuro.
        if ( 0 === strpos( (string) $key, 'gep_history_' ) ) {
            continue;
        }

        $snapshot[ $key ] = get_post_meta( $post_id, $key, true );
    }

    ksort( $snapshot, SORT_STRING );
    return $snapshot;
}

function gep_demo_history_canonicalize( $value ) {
    if ( ! is_array( $value ) ) {
        return $value;
    }

    $keys       = array_keys( $value );
    $is_indexed = $keys === range( 0, count( $keys ) - 1 );

    if ( ! $is_indexed ) {
        ksort( $value, SORT_STRING );
    }

    foreach ( $value as $key => $child ) {
        $value[ $key ] = gep_demo_history_canonicalize( $child );
    }

    return $value;
}

function gep_demo_history_snapshot_json( $snapshot ) {
    return wp_json_encode(
        gep_demo_history_canonicalize( is_array( $snapshot ) ? $snapshot : array() ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}

function gep_demo_history_snapshot_hash( $snapshot ) {
    return hash( 'sha256', (string) gep_demo_history_snapshot_json( $snapshot ) );
}

/**
 * Empaqueta snapshots grandes. En Publicaciones reduce de forma importante
 * el espacio utilizado por cada revisión sin requerir una tabla propia.
 */
function gep_demo_history_pack( $value ) {
    $json = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    if ( false === $json ) {
        $json = '{}';
    }

    if ( function_exists( 'gzdeflate' ) ) {
        $compressed = @gzdeflate( $json, 6 );
        if ( false !== $compressed ) {
            return 'gz:' . base64_encode( $compressed );
        }
    }

    return 'js:' . $json;
}

function gep_demo_history_unpack( $packed ) {
    if ( ! is_string( $packed ) || '' === $packed ) {
        return array();
    }

    $json = '';
    if ( 0 === strpos( $packed, 'gz:' ) ) {
        $binary = base64_decode( substr( $packed, 3 ), true );
        if ( false !== $binary && function_exists( 'gzinflate' ) ) {
            $json = @gzinflate( $binary );
        }
    } elseif ( 0 === strpos( $packed, 'js:' ) ) {
        $json = substr( $packed, 3 );
    } else {
        // Compatibilidad futura con valores JSON sin prefijo.
        $json = $packed;
    }

    if ( ! is_string( $json ) || '' === $json ) {
        return array();
    }

    $decoded = json_decode( $json, true );
    return is_array( $decoded ) ? $decoded : array();
}

function gep_demo_history_decode_meta_value( $key, $value ) {
    if ( is_string( $value ) && ( substr( (string) $key, -5 ) === '_json' || ( isset( $value[0] ) && in_array( $value[0], array( '[', '{' ), true ) ) ) ) {
        $decoded = json_decode( $value, true );
        if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
            return $decoded;
        }
    }
    return $value;
}

function gep_demo_history_token_label( $token ) {
    $labels = array(
        'home' => 'Inicio', 'hero' => 'Portada', 'intro' => 'Introducción', 'field' => 'Trabajo en terreno',
        'research' => 'Investigación', 'portal' => 'Explorar', 'landscape' => 'Paisaje', 'resources' => 'Recursos',
        'topics' => 'Biblioteca temática', 'network' => 'Herramientas y datos', 'cta' => 'Llamada a la acción',
        'contact' => 'Contacto', 'location' => 'Ubicación', 'primary' => 'Principal', 'email' => 'Correo', 'button' => 'Botón',
        'docencia' => 'Docencia', 'courses' => 'Cursos', 'course' => 'Curso', 'steps' => 'Pasos', 'step' => 'Paso',
        'projects' => 'Proyectos', 'project' => 'Proyecto', 'featured' => 'Destacados', 'trajectory' => 'Trayectoria', 'areas' => 'Áreas',
        'publications' => 'Publicaciones', 'publication' => 'Publicación', 'records' => 'Registros', 'sections' => 'Apartados',
        'communications' => 'Comunicaciones', 'communication' => 'Comunicación', 'news' => 'Noticias', 'interviews' => 'Entrevistas',
        'directory' => 'Directorio', 'team' => 'Equipo', 'cards' => 'Tarjetas', 'items' => 'Elementos', 'groups' => 'Grupos',
        'title' => 'Título', 'subtitle' => 'Subtítulo', 'eyebrow' => 'Etiqueta', 'kicker' => 'Etiqueta', 'badge' => 'Insignia',
        'text' => 'Texto', 'description' => 'Descripción', 'label' => 'Etiqueta', 'value' => 'Valor', 'name' => 'Nombre',
        'image' => 'Imagen', 'image_url' => 'Imagen', 'map' => 'Mapa', 'url' => 'Enlace', 'alt' => 'Texto alternativo',
        'year' => 'Año / período', 'status' => 'Estado', 'group' => 'Programa / grupo', 'specialization' => 'Especialización',
        'legend' => 'Leyenda', 'note' => 'Nota', 'enabled' => 'Activo', 'replace' => 'Reemplazo', 'static' => 'estático',
        'icon' => 'Icono', 'kind' => 'Tipo', 'open' => 'Abierto', 'pills' => 'Temas destacados', 'buttons' => 'Botones', 'button' => 'Botón', 'target' => 'Cómo se abre', 'style' => 'Estilo', 'focus' => 'Encuadre', 'fit' => 'Ajuste de imagen', 'stats' => 'Indicadores',
        'definitions' => 'Definiciones', 'links' => 'Enlaces', 'rows' => 'Filas', 'index' => 'Índice', 'geo' => 'Coordenada',
        'a' => 'A', 'b' => 'B', 'p1' => 'Párrafo 1', 'p2' => 'Párrafo 2', 'primary_email' => 'Correo principal',
        'role' => 'Cargo', 'video' => 'Video', 'primary_text' => 'Botón principal', 'primary_url' => 'Enlace del botón principal',
        'secondary_text' => 'Botón secundario', 'secondary_url' => 'Enlace del botón secundario',
        // v9.26.0
        'frames' => 'Encuadre de fotos', 'off' => 'Oculto', 'primary_off' => 'Botón principal oculto', 'secondary_off' => 'Botón secundario oculto', 'x' => 'Horizontal', 'y' => 'Vertical', 'z' => 'Acercamiento', 'mx' => 'Horizontal (teléfono)', 'my' => 'Vertical (teléfono)',
    );

    if ( isset( $labels[ $token ] ) ) {
        return $labels[ $token ];
    }

    return ucwords( str_replace( array( '-', '_' ), ' ', (string) $token ) );
}

function gep_demo_history_meta_label( $key ) {
    $key = preg_replace( '/^gep_/', '', (string) $key );
    $key = preg_replace( '/_json$/', '', $key );

    // Protege términos compuestos antes de separar tokens.
    $key = str_replace(
        array( 'image_url', 'primary_email', 'primary_text', 'primary_url', 'secondary_text', 'secondary_url' ),
        array( 'image-url', 'primary-email', 'primary-text', 'primary-url', 'secondary-text', 'secondary-url' ),
        $key
    );

    $tokens = array_values( array_filter( explode( '_', $key ), 'strlen' ) );
    $out    = array();
    foreach ( $tokens as $token ) {
        $token = str_replace( '-', '_', $token );
        $out[] = gep_demo_history_token_label( $token );
    }

    return implode( ' › ', $out );
}

function gep_demo_history_child_label( $key ) {
    if ( is_int( $key ) || ctype_digit( (string) $key ) ) {
        return 'Elemento ' . ( (int) $key + 1 );
    }
    return gep_demo_history_token_label( (string) $key );
}

function gep_demo_history_value_kind( $path, $value ) {
    $path = strtolower( (string) $path );
    if ( false !== strpos( $path, 'imagen' ) || false !== strpos( $path, 'image' ) ) {
        return 'image';
    }
    if ( false !== strpos( $path, 'enlace' ) || false !== strpos( $path, 'url' ) ) {
        return 'link';
    }
    if ( is_bool( $value ) || in_array( (string) $value, array( '0', '1' ), true ) && ( false !== strpos( $path, 'activo' ) || false !== strpos( $path, 'abierto' ) ) ) {
        return 'boolean';
    }
    if ( is_numeric( $value ) && false !== strpos( $path, 'año' ) ) {
        return 'number';
    }
    return 'text';
}

function gep_demo_history_scalar_for_compare( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? '1' : '0';
    }
    if ( null === $value ) {
        return '';
    }
    if ( is_scalar( $value ) ) {
        return (string) $value;
    }
    return wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

function gep_demo_history_diff_walk( $before, $after, $label, $path, &$changes ) {
    $before_is_array = is_array( $before );
    $after_is_array  = is_array( $after );

    if ( $before_is_array || $after_is_array ) {
        $before = $before_is_array ? $before : array();
        $after  = $after_is_array ? $after : array();
        $keys   = array_values( array_unique( array_merge( array_keys( $before ), array_keys( $after ) ), SORT_REGULAR ) );

        foreach ( $keys as $key ) {
            $has_before = array_key_exists( $key, $before );
            $has_after  = array_key_exists( $key, $after );
            $child      = gep_demo_history_child_label( $key );
            $child_label = '' === $label ? $child : $label . ' › ' . $child;
            $child_path  = '' === $path ? (string) $key : $path . '.' . $key;

            gep_demo_history_diff_walk(
                $has_before ? $before[ $key ] : null,
                $has_after ? $after[ $key ] : null,
                $child_label,
                $child_path,
                $changes
            );
        }
        return;
    }

    $old = gep_demo_history_scalar_for_compare( $before );
    $new = gep_demo_history_scalar_for_compare( $after );
    if ( $old === $new ) {
        return;
    }

    $type = 'changed';
    if ( '' === $old && '' !== $new ) {
        $type = 'added';
    } elseif ( '' !== $old && '' === $new ) {
        $type = 'removed';
    }

    $changes[] = array(
        'path'  => $path,
        'label' => $label,
        'old'   => $old,
        'new'   => $new,
        'type'  => $type,
        'kind'  => gep_demo_history_value_kind( $label . ' ' . $path, '' !== $new ? $new : $old ),
    );
}

function gep_demo_history_diff_snapshots( $before, $after ) {
    $before = is_array( $before ) ? $before : array();
    $after  = is_array( $after ) ? $after : array();
    $keys   = array_values( array_unique( array_merge( array_keys( $before ), array_keys( $after ) ) ) );
    sort( $keys, SORT_STRING );

    $changes = array();
    foreach ( $keys as $key ) {
        $has_before = array_key_exists( $key, $before );
        $has_after  = array_key_exists( $key, $after );
        $old        = $has_before ? gep_demo_history_decode_meta_value( $key, $before[ $key ] ) : null;
        $new        = $has_after ? gep_demo_history_decode_meta_value( $key, $after[ $key ] ) : null;
        gep_demo_history_diff_walk( $old, $new, gep_demo_history_meta_label( $key ), $key, $changes );
    }

    return $changes;
}

function gep_demo_history_context_label( $context ) {
    $labels = array(
        'home'         => 'Inicio',
        'directory'    => 'Quiénes somos',
        'docencia'     => 'Docencia',
        'projects'     => 'Proyectos',
        'publications' => 'Publicaciones',
        'communications' => 'Comunicaciones',
        'news'         => 'Noticias',
        'services'     => 'Servicios',
        'contact'      => 'Contacto',
    );
    return isset( $labels[ $context ] ) ? $labels[ $context ] : 'Contenido GEP';
}

function gep_demo_history_context_icon( $context ) {
    $icons = array(
        'home'         => 'dashicons-admin-home',
        'directory'    => 'dashicons-groups',
        'docencia'     => 'dashicons-welcome-learn-more',
        'projects'     => 'dashicons-portfolio',
        'publications' => 'dashicons-media-document',
        'communications' => 'dashicons-megaphone',
        'news'         => 'dashicons-welcome-write-blog',
        'services'     => 'dashicons-admin-tools',
        'contact'      => 'dashicons-email-alt',
    );
    return isset( $icons[ $context ] ) ? $icons[ $context ] : 'dashicons-edit';
}

function gep_demo_history_normalize_context( $post_id, $context = '', $navigation = null ) {
    $context = sanitize_key( (string) $context );
    if ( in_array( $context, array( 'home', 'directory', 'docencia', 'projects', 'publications', 'communications', 'news', 'services', 'contact' ), true ) ) {
        return $context;
    }

    if ( null === $navigation && function_exists( 'gep_demo_get_page_navigation' ) ) {
        $navigation = gep_demo_get_page_navigation();
    }

    if ( function_exists( 'gep_demo_is_home_page' ) && gep_demo_is_home_page( $post_id, $navigation ) ) {
        return 'home';
    }
    if ( function_exists( 'gep_demo_is_hub_page' ) && gep_demo_is_hub_page( $post_id, 'communications', $navigation ) ) {
        return 'communications';
    }
    if ( function_exists( 'gep_demo_is_hub_page' ) && gep_demo_is_hub_page( $post_id, 'news', $navigation ) ) {
        return 'news';
    }
    if ( function_exists( 'gep_demo_is_services_page' ) && gep_demo_is_services_page( $post_id, $navigation ) ) {
        return 'services';
    }
    if ( function_exists( 'gep_demo_is_publications_page' ) && gep_demo_is_publications_page( $post_id, $navigation ) ) {
        return 'publications';
    }
    if ( function_exists( 'gep_demo_is_projects_page' ) && gep_demo_is_projects_page( $post_id, $navigation ) ) {
        return 'projects';
    }
    if ( function_exists( 'gep_demo_is_docencia_page' ) && gep_demo_is_docencia_page( $post_id, $navigation ) ) {
        return 'docencia';
    }
    if ( function_exists( 'gep_demo_is_contact_page' ) && gep_demo_is_contact_page( $post_id, $navigation ) ) {
        return 'contact';
    }
    return 'directory';
}

function gep_demo_history_action_label( $action ) {
    $labels = array(
        'baseline'   => 'Punto inicial',
        'save'       => 'Guardado',
        'rollback'   => 'Rollback',
        'checkpoint' => 'Punto de seguridad',
        'restore'    => 'Respaldo restaurado',
        'migration'  => 'Traslado del CMS', // v9.27.1: Operación Motor.
    );
    return isset( $labels[ $action ] ) ? $labels[ $action ] : ucfirst( (string) $action );
}

function gep_demo_history_substr( $value, $start, $length = null ) {
    $value = (string) $value;
    if ( function_exists( 'mb_substr' ) ) {
        return null === $length ? mb_substr( $value, $start ) : mb_substr( $value, $start, $length );
    }
    return null === $length ? substr( $value, $start ) : substr( $value, $start, $length );
}

function gep_demo_history_strlen( $value ) {
    return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) : strlen( (string) $value );
}

function gep_demo_history_next_revision( $post_id ) {
    $query = new WP_Query(
        array(
            'post_type'      => 'gep_change',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array( 'key' => '_gep_history_page_id', 'value' => absint( $post_id ) ),
            ),
            'meta_key'       => '_gep_history_revision',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        )
    );
    if ( empty( $query->posts ) ) {
        return 1;
    }
    return max( 1, (int) get_post_meta( $query->posts[0], '_gep_history_revision', true ) + 1 );
}

function gep_demo_history_build_search_text( $changes, $context, $action ) {
    $parts = array( gep_demo_history_context_label( $context ), gep_demo_history_action_label( $action ) );
    foreach ( array_slice( is_array( $changes ) ? $changes : array(), 0, 80 ) as $change ) {
        $parts[] = isset( $change['label'] ) ? $change['label'] : '';
        $parts[] = isset( $change['old'] ) ? gep_demo_history_substr( (string) $change['old'], 0, 160 ) : '';
        $parts[] = isset( $change['new'] ) ? gep_demo_history_substr( (string) $change['new'], 0, 160 ) : '';
    }
    return gep_demo_history_substr( implode( "\n", array_filter( $parts, 'strlen' ) ), 0, 10000 );
}

function gep_demo_history_category_counts( $changes ) {
    $counts = array( 'text' => 0, 'image' => 0, 'link' => 0, 'structure' => 0 );
    foreach ( is_array( $changes ) ? $changes : array() as $change ) {
        $kind = isset( $change['kind'] ) ? $change['kind'] : 'text';
        if ( 'image' === $kind ) {
            $counts['image']++;
        } elseif ( 'link' === $kind ) {
            $counts['link']++;
        } elseif ( in_array( isset( $change['type'] ) ? $change['type'] : '', array( 'added', 'removed' ), true ) && false !== strpos( isset( $change['path'] ) ? $change['path'] : '', '.' ) ) {
            $counts['structure']++;
        } else {
            $counts['text']++;
        }
    }
    return $counts;
}

function gep_demo_history_create_entry( $post_id, $context, $action, $before, $after, $args = array() ) {
    $post_id = absint( $post_id );
    if ( ! $post_id || 'page' !== get_post_type( $post_id ) ) {
        return 0;
    }

    $context = gep_demo_history_normalize_context( $post_id, $context );
    $before  = is_array( $before ) ? $before : array();
    $after   = is_array( $after ) ? $after : array();
    $changes = gep_demo_history_diff_snapshots( $before, $after );

    $defaults = array(
        'note'          => '',
        'protected'     => false,
        'rollback_from' => 0,
    );
    $args = wp_parse_args( $args, $defaults );

    $user_id  = get_current_user_id();
    $revision = gep_demo_history_next_revision( $post_id );
    $title   = sprintf(
        '%s · %s · %s',
        gep_demo_history_context_label( $context ),
        gep_demo_history_action_label( $action ),
        current_time( 'd/m/Y H:i' )
    );

    $entry_id = wp_insert_post(
        array(
            'post_type'    => 'gep_change',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => gep_demo_history_build_search_text( $changes, $context, $action ),
            'post_author'  => $user_id,
        ),
        true
    );

    if ( is_wp_error( $entry_id ) || ! $entry_id ) {
        return 0;
    }

    $hash = gep_demo_history_snapshot_hash( $after );
    update_post_meta( $entry_id, '_gep_history_page_id', $post_id );
    update_post_meta( $entry_id, '_gep_history_revision', $revision );
    update_post_meta( $entry_id, '_gep_history_context', $context );
    update_post_meta( $entry_id, '_gep_history_action', sanitize_key( $action ) );
    update_post_meta( $entry_id, '_gep_history_user_id', $user_id );
    update_post_meta( $entry_id, '_gep_history_snapshot', gep_demo_history_pack( $after ) );
    update_post_meta( $entry_id, '_gep_history_changes', gep_demo_history_pack( $changes ) );
    update_post_meta( $entry_id, '_gep_history_changed_count', count( $changes ) );
    update_post_meta( $entry_id, '_gep_history_hash', $hash );
    update_post_meta( $entry_id, '_gep_history_note', sanitize_textarea_field( $args['note'] ) );
    update_post_meta( $entry_id, '_gep_history_protected', $args['protected'] ? '1' : '0' );
    update_post_meta( $entry_id, '_gep_history_rollback_from', absint( $args['rollback_from'] ) );
    update_post_meta( $entry_id, '_gep_history_categories', gep_demo_history_pack( gep_demo_history_category_counts( $changes ) ) );

    gep_demo_history_prune( $post_id );
    return (int) $entry_id;
}

function gep_demo_history_has_any( $post_id ) {
    $query = new WP_Query(
        array(
            'post_type'      => 'gep_change',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_key'       => '_gep_history_page_id',
            'meta_value'     => absint( $post_id ),
        )
    );
    return ! empty( $query->posts );
}

function gep_demo_history_ensure_baseline( $post_id, $context = '' ) {
    $post_id = absint( $post_id );
    if ( ! $post_id || gep_demo_history_has_any( $post_id ) ) {
        return 0;
    }

    $snapshot = gep_demo_history_capture_snapshot( $post_id );
    return gep_demo_history_create_entry(
        $post_id,
        $context,
        'baseline',
        $snapshot,
        $snapshot,
        array(
            'note'      => 'Punto inicial creado automáticamente al activar el historial.',
            'protected' => true,
        )
    );
}

function gep_demo_history_record_save( $post_id, $context, $before, $after ) {
    if ( gep_demo_history_snapshot_hash( $before ) === gep_demo_history_snapshot_hash( $after ) ) {
        return 0;
    }
    return gep_demo_history_create_entry( $post_id, $context, 'save', $before, $after );
}

function gep_demo_history_prune( $post_id ) {
    $limit = max( 10, (int) apply_filters( 'gep_demo_history_limit_per_page', GEP_DEMO_HISTORY_LIMIT_PER_PAGE, $post_id ) );

    $query = new WP_Query(
        array(
            'post_type'      => 'gep_change',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'AND',
                array( 'key' => '_gep_history_page_id', 'value' => absint( $post_id ) ),
                array(
                    'relation' => 'OR',
                    array( 'key' => '_gep_history_protected', 'compare' => 'NOT EXISTS' ),
                    array( 'key' => '_gep_history_protected', 'value' => '1', 'compare' => '!=' ),
                ),
            ),
            'no_found_rows' => true,
        )
    );

    $ids = is_array( $query->posts ) ? $query->posts : array();
    if ( count( $ids ) <= $limit ) {
        return;
    }

    foreach ( array_slice( $ids, $limit ) as $entry_id ) {
        wp_delete_post( $entry_id, true );
    }
}

function gep_demo_history_get_entry_snapshot( $entry_id ) {
    return gep_demo_history_unpack( get_post_meta( $entry_id, '_gep_history_snapshot', true ) );
}

function gep_demo_history_get_entry_changes( $entry_id ) {
    return gep_demo_history_unpack( get_post_meta( $entry_id, '_gep_history_changes', true ) );
}

function gep_demo_history_get_entry_categories( $entry_id ) {
    $cats = gep_demo_history_unpack( get_post_meta( $entry_id, '_gep_history_categories', true ) );
    return wp_parse_args( is_array( $cats ) ? $cats : array(), array( 'text' => 0, 'image' => 0, 'link' => 0, 'structure' => 0 ) );
}

function gep_demo_history_apply_snapshot( $post_id, $snapshot ) {
    $post_id  = absint( $post_id );
    $snapshot = is_array( $snapshot ) ? $snapshot : array();
    $current  = gep_demo_history_capture_snapshot( $post_id );

    // Elimina campos GEP creados después de la versión objetivo.
    foreach ( array_keys( $current ) as $key ) {
        if ( ! array_key_exists( $key, $snapshot ) ) {
            delete_post_meta( $post_id, $key );
        }
    }

    foreach ( $snapshot as $key => $value ) {
        if ( 0 !== strpos( (string) $key, 'gep_' ) || 0 === strpos( (string) $key, 'gep_history_' ) ) {
            continue;
        }
        // wp_slash: update_post_meta quita barras invertidas y rompería los campos JSON.
        update_post_meta( $post_id, $key, wp_slash( $value ) );
    }
}

function gep_demo_history_safe_return_url( $args = array() ) {
    return add_query_arg( $args, admin_url( 'admin.php?page=gep-content-history' ) );
}

function gep_demo_history_handle_rollback() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'No tienes permisos para restaurar versiones.' );
    }

    $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
    if ( ! $entry_id || 'gep_change' !== get_post_type( $entry_id ) ) {
        wp_die( 'La versión seleccionada no es válida.' );
    }

    check_admin_referer( 'gep_demo_history_rollback_' . $entry_id, 'gep_history_nonce' );

    $post_id = absint( get_post_meta( $entry_id, '_gep_history_page_id', true ) );
    if ( ! $post_id || 'page' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( 'No tienes permisos para modificar esta sección.' );
    }

    $packed = (string) get_post_meta( $entry_id, '_gep_history_snapshot', true );
    $target = gep_demo_history_get_entry_snapshot( $entry_id );
    $hash   = (string) get_post_meta( $entry_id, '_gep_history_hash', true );
    if ( '' === $packed || '' === $hash || ! hash_equals( $hash, gep_demo_history_snapshot_hash( $target ) ) ) {
        wp_die( 'No se pudo verificar la integridad de esta versión. El rollback fue cancelado.' );
    }
    // v9.27.1 · La instantánea guardada no se modifica (su huella sigue valiendo). Lo que se
    // escribe al restaurar lleva las direcciones de la ubicación actual del CMS (/gep/motor/).
    if ( function_exists( 'gep_cms_normalize_deep' ) ) {
        $target = gep_cms_normalize_deep( $target );
    }

    $context = (string) get_post_meta( $entry_id, '_gep_history_context', true );
    $target_revision = (int) get_post_meta( $entry_id, '_gep_history_revision', true );
    $current = gep_demo_history_capture_snapshot( $post_id );
    if ( gep_demo_history_snapshot_hash( $current ) === gep_demo_history_snapshot_hash( $target ) ) {
        wp_safe_redirect( gep_demo_history_safe_return_url( array( 'gep_history_notice' => 'already-current', 'focus' => $entry_id ) ) );
        exit;
    }

    // Punto de seguridad: garantiza que un rollback también pueda deshacerse.
    gep_demo_history_create_entry(
        $post_id,
        $context,
        'checkpoint',
        $current,
        $current,
        array( 'note' => sprintf( 'Punto de seguridad automático antes de restaurar la versión v%d.', $target_revision ) )
    );

    gep_demo_history_apply_snapshot( $post_id, $target );
    if ( function_exists( 'gep_lock_mark_saved' ) ) gep_lock_mark_saved( $post_id );
    $after = gep_demo_history_capture_snapshot( $post_id );
    $reason = isset( $_POST['rollback_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['rollback_reason'] ) ) : '';
    $note   = sprintf( 'Restauración de la versión v%d.', $target_revision );
    if ( '' !== trim( $reason ) ) {
        $note .= ' Motivo: ' . trim( $reason );
    }

    $rollback_id = gep_demo_history_create_entry(
        $post_id,
        $context,
        'rollback',
        $current,
        $after,
        array(
            'note'          => $note,
            'rollback_from' => $entry_id,
        )
    );

    wp_safe_redirect( gep_demo_history_safe_return_url( array( 'gep_history_notice' => 'rolled-back', 'focus' => $rollback_id ) ) );
    exit;
}
add_action( 'admin_post_gep_demo_history_rollback', 'gep_demo_history_handle_rollback' );

function gep_demo_history_handle_note() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'No tienes permisos para editar notas.' );
    }
    $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
    check_admin_referer( 'gep_demo_history_note_' . $entry_id, 'gep_history_nonce' );
    if ( ! $entry_id || 'gep_change' !== get_post_type( $entry_id ) ) {
        wp_die( 'La versión seleccionada no es válida.' );
    }
    $note = isset( $_POST['history_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['history_note'] ) ) : '';
    update_post_meta( $entry_id, '_gep_history_note', $note );
    wp_safe_redirect( gep_demo_history_safe_return_url( array( 'gep_history_notice' => 'note-saved', 'focus' => $entry_id ) ) );
    exit;
}
add_action( 'admin_post_gep_demo_history_note', 'gep_demo_history_handle_note' );

function gep_demo_history_handle_protect() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'No tienes permisos para proteger versiones.' );
    }
    $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
    check_admin_referer( 'gep_demo_history_protect_' . $entry_id, 'gep_history_nonce' );
    if ( ! $entry_id || 'gep_change' !== get_post_type( $entry_id ) ) {
        wp_die( 'La versión seleccionada no es válida.' );
    }

    $action = (string) get_post_meta( $entry_id, '_gep_history_action', true );
    if ( 'baseline' === $action ) {
        update_post_meta( $entry_id, '_gep_history_protected', '1' );
    } else {
        $current = '1' === get_post_meta( $entry_id, '_gep_history_protected', true );
        update_post_meta( $entry_id, '_gep_history_protected', $current ? '0' : '1' );
    }

    wp_safe_redirect( gep_demo_history_safe_return_url( array( 'gep_history_notice' => 'protect-updated', 'focus' => $entry_id ) ) );
    exit;
}
add_action( 'admin_post_gep_demo_history_protect', 'gep_demo_history_handle_protect' );

function gep_demo_history_handle_export() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'No tienes permisos para exportar versiones.' );
    }
    $entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
    check_admin_referer( 'gep_demo_history_export_' . $entry_id );
    if ( ! $entry_id || 'gep_change' !== get_post_type( $entry_id ) ) {
        wp_die( 'La versión seleccionada no es válida.' );
    }

    $post_id  = absint( get_post_meta( $entry_id, '_gep_history_page_id', true ) );
    $context  = (string) get_post_meta( $entry_id, '_gep_history_context', true );
    $snapshot = gep_demo_history_get_entry_snapshot( $entry_id );
    $payload  = array(
        'format'       => 'GEP Editor history snapshot',
        'plugin'       => 'GEP Editor Beta',
        'version'      => defined( 'GEP_EDITOR_VERSION' ) ? GEP_EDITOR_VERSION : '9.16.0',
        'history_id'   => $entry_id,
        'revision'     => (int) get_post_meta( $entry_id, '_gep_history_revision', true ),
        'page_id'      => $post_id,
        'section'      => gep_demo_history_context_label( $context ),
        'action'       => gep_demo_history_action_label( get_post_meta( $entry_id, '_gep_history_action', true ) ),
        'created_at'   => get_post_time( DATE_ATOM, false, $entry_id ),
        'author'       => get_the_author_meta( 'display_name', (int) get_post_meta( $entry_id, '_gep_history_user_id', true ) ),
        'integrity'    => get_post_meta( $entry_id, '_gep_history_hash', true ),
        'note'         => get_post_meta( $entry_id, '_gep_history_note', true ),
        'snapshot'     => $snapshot,
    );

    nocache_headers();
    $export_revision = (int) get_post_meta( $entry_id, '_gep_history_revision', true );
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="gep-' . sanitize_title( gep_demo_history_context_label( $context ) ) . '-v' . $export_revision . '.json"' );
    echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    exit;
}
add_action( 'admin_post_gep_demo_history_export', 'gep_demo_history_handle_export' );

function gep_demo_history_format_value( $value, $kind = 'text' ) {
    $value = (string) $value;
    if ( '' === $value ) {
        return '<span class="gep-history-empty">Vacío</span>';
    }

    if ( 'boolean' === $kind ) {
        return in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ? 'Sí' : 'No';
    }

    if ( 'image' === $kind && filter_var( $value, FILTER_VALIDATE_URL ) ) {
        return '<span class="gep-history-media-preview"><img src="' . esc_url( $value ) . '" alt=""><code>' . esc_html( $value ) . '</code></span>';
    }

    if ( 'link' === $kind && filter_var( $value, FILTER_VALIDATE_URL ) ) {
        return '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener">' . esc_html( $value ) . ' <span aria-hidden="true">↗</span></a>';
    }

    $display = $value;
    if ( gep_demo_history_strlen( $display ) > 2200 ) {
        $display = gep_demo_history_substr( $display, 0, 2200 ) . "\n… [contenido largo recortado en esta vista]";
    }
    return nl2br( esc_html( $display ) );
}

/* ==========================================================================
   Presentación del historial (rework visual 9.17).
   Solo cambia cómo se muestra el historial. Guardado, integridad, notas,
   protección, exportación y rollback siguen usando las funciones de arriba.
   ========================================================================== */

function gep_demo_history_label_parts( $label ) {
    return array_values( array_filter( array_map( 'trim', explode( '›', (string) $label ) ), 'strlen' ) );
}

function gep_demo_history_friendly_label( $label, $context = '' ) {
    $parts = gep_demo_history_label_parts( $label );
    if ( count( $parts ) > 1 && $context && gep_demo_history_context_label( $context ) === $parts[0] ) {
        array_shift( $parts );
    }
    return implode( ' › ', $parts );
}

function gep_demo_history_lower( $value ) {
    return function_exists( 'mb_strtolower' ) ? mb_strtolower( (string) $value, 'UTF-8' ) : strtolower( (string) $value );
}

function gep_demo_history_join_list( $items, $max = 3 ) {
    $items = array_values( array_unique( array_filter( array_map( 'strval', (array) $items ), 'strlen' ) ) );
    $extra = count( $items ) - $max;
    if ( $extra > 0 ) {
        $items   = array_slice( $items, 0, $max );
        $items[] = 1 === $extra ? '1 campo más' : $extra . ' campos más';
    }
    if ( count( $items ) < 2 ) {
        return implode( '', $items );
    }
    $last = array_pop( $items );
    return implode( ', ', $items ) . ' y ' . $last;
}

/**
 * Devuelve la ruta del elemento de lista al que pertenece un cambio
 * (ej. "gep_publications_records_json.3"), o '' si no es parte de una lista.
 */
function gep_demo_history_item_root( $path ) {
    $segments = explode( '.', (string) $path );
    $root     = -1;
    foreach ( $segments as $i => $segment ) {
        if ( $i > 0 && ctype_digit( (string) $segment ) ) {
            $root = $i;
        }
    }
    return $root > 0 ? implode( '.', array_slice( $segments, 0, $root + 1 ) ) : '';
}

/** Busca un título legible ("title", "name"…) para el elemento de lista de una ruta. */
function gep_demo_history_item_title( $snapshot, $path ) {
    $root = gep_demo_history_item_root( $path );
    if ( '' === $root || ! is_array( $snapshot ) ) {
        return '';
    }
    $segments = explode( '.', $root );
    $key      = array_shift( $segments );
    if ( ! array_key_exists( $key, $snapshot ) ) {
        return '';
    }
    $node = gep_demo_history_decode_meta_value( $key, $snapshot[ $key ] );
    foreach ( $segments as $segment ) {
        if ( ! is_array( $node ) || ! array_key_exists( $segment, $node ) ) {
            return '';
        }
        $node = $node[ $segment ];
    }
    if ( ! is_array( $node ) ) {
        return '';
    }
    foreach ( array( 'title', 'name', 'titulo', 'nombre', 'label' ) as $field ) {
        if ( isset( $node[ $field ] ) && is_scalar( $node[ $field ] ) && '' !== trim( (string) $node[ $field ] ) ) {
            $title = wp_strip_all_tags( (string) $node[ $field ] );
            return gep_demo_history_strlen( $title ) > 60 ? gep_demo_history_substr( $title, 0, 58 ) . '…' : $title;
        }
    }
    return '';
}

/** Frase corta y legible que describe una versión. */
function gep_demo_history_entry_summary( $entry_id, $changes = null ) {
    $action  = (string) get_post_meta( $entry_id, '_gep_history_action', true );
    $context = (string) get_post_meta( $entry_id, '_gep_history_context', true );

    if ( 'baseline' === $action ) {
        return 'Punto de partida guardado automáticamente';
    }
    if ( 'checkpoint' === $action ) {
        return 'Copia de seguridad automática antes de restaurar';
    }
    if ( 'rollback' === $action ) {
        $from = absint( get_post_meta( $entry_id, '_gep_history_rollback_from', true ) );
        $rev  = $from ? (int) get_post_meta( $from, '_gep_history_revision', true ) : 0;
        if ( $rev ) {
            $date = get_post( $from ) ? ' (' . get_the_date( 'j \d\e F', $from ) . ')' : '';
            return sprintf( 'Volvió a la versión %d%s', $rev, $date );
        }
        return 'Restauró una versión anterior';
    }
    if ( 'restore' === $action ) {
        return 'Restauró el respaldo completo';
    }
    if ( 'migration' === $action ) {
        return 'Direcciones de fotos y videos actualizadas al traslado del CMS (/gep/motor/)';
    }

    $changes = null === $changes ? gep_demo_history_get_entry_changes( $entry_id ) : $changes;
    if ( empty( $changes ) || ! is_array( $changes ) ) {
        return 'Guardó sin cambios de contenido';
    }

    $fields   = array();
    $areas    = array();
    $items    = array();
    foreach ( $changes as $change ) {
        $path = isset( $change['path'] ) ? (string) $change['path'] : '';
        $root = gep_demo_history_item_root( $path );
        if ( '' !== $root ) {
            $items[ $root ][] = $change;
            continue;
        }
        $parts    = gep_demo_history_label_parts( gep_demo_history_friendly_label( isset( $change['label'] ) ? $change['label'] : '', $context ) );
        $fields[] = gep_demo_history_lower( end( $parts ) );
        $areas[]  = count( $parts ) > 1 ? $parts[ count( $parts ) - 2 ] : '';
    }

    $phrases = array();
    if ( $fields ) {
        $areas  = array_values( array_unique( $areas ) );
        $suffix = ( 1 === count( $areas ) && '' !== $areas[0] ) ? ' en ' . $areas[0] : '';
        $phrases[] = 'Cambió ' . gep_demo_history_join_list( $fields ) . $suffix;
    }

    $added = array();
    $removed = array();
    $edited = array();
    $snapshot = null;
    foreach ( $items as $list ) {
        $types = array_values( array_unique( wp_list_pluck( $list, 'type' ) ) );
        if ( array( 'added' ) === $types || array( 'removed' ) === $types ) {
            $is_added = array( 'added' ) === $types;
            $title    = '';
            foreach ( $list as $change ) {
                $segments = explode( '.', (string) $change['path'] );
                if ( in_array( end( $segments ), array( 'title', 'name', 'titulo', 'nombre' ), true ) ) {
                    $title = wp_strip_all_tags( (string) ( $is_added ? $change['new'] : $change['old'] ) );
                    break;
                }
            }
            if ( gep_demo_history_strlen( $title ) > 48 ) {
                $title = gep_demo_history_substr( $title, 0, 46 ) . '…';
            }
            if ( $is_added ) {
                $added[] = $title;
            } else {
                $removed[] = $title;
            }
            continue;
        }
        if ( null === $snapshot ) {
            $snapshot = gep_demo_history_get_entry_snapshot( $entry_id );
        }
        $names = array();
        foreach ( $list as $change ) {
            $parts   = gep_demo_history_label_parts( isset( $change['label'] ) ? $change['label'] : '' );
            $names[] = gep_demo_history_lower( end( $parts ) );
        }
        $title    = gep_demo_history_item_title( $snapshot, $list[0]['path'] );
        $edited[] = gep_demo_history_join_list( $names, 2 ) . ( '' !== $title ? ' de «' . $title . '»' : '' );
    }
    if ( $edited ) {
        $phrases[] = ( $phrases ? 'también ' : 'Cambió ' ) . gep_demo_history_join_list( $edited, 2 );
    }
    foreach ( array( 'agregó' => $added, 'quitó' => $removed ) as $verb => $titles ) {
        if ( ! $titles ) {
            continue;
        }
        $verb   = $phrases ? $verb : ( 'agregó' === $verb ? 'Agregó' : 'Quitó' );
        $named  = array_filter( $titles, 'strlen' );
        if ( count( $named ) === count( $titles ) && count( $titles ) <= 2 ) {
            $phrases[] = $verb . ' ' . gep_demo_history_join_list( array_map( function ( $t ) { return '«' . $t . '»'; }, $titles ), 2 );
        } else {
            $phrases[] = $verb . ' ' . ( 1 === count( $titles ) ? '1 elemento' : count( $titles ) . ' elementos' );
        }
    }

    return $phrases ? implode( '; ', $phrases ) : 'Guardó cambios';
}

/** Primer cambio de imagen con URLs válidas, para las miniaturas de la lista. */
function gep_demo_history_first_image_change( $changes ) {
    foreach ( is_array( $changes ) ? $changes : array() as $change ) {
        if ( 'image' !== ( isset( $change['kind'] ) ? $change['kind'] : '' ) ) {
            continue;
        }
        $old = filter_var( (string) $change['old'], FILTER_VALIDATE_URL ) ? (string) $change['old'] : '';
        $new = filter_var( (string) $change['new'], FILTER_VALIDATE_URL ) ? (string) $change['new'] : '';
        if ( $old || $new ) {
            return array( 'old' => $old, 'new' => $new );
        }
    }
    return array();
}

function gep_demo_history_initials( $name ) {
    $words = preg_split( '/\s+/u', trim( (string) $name ), -1, PREG_SPLIT_NO_EMPTY );
    $out   = '';
    foreach ( array_slice( $words, 0, 2 ) as $word ) {
        $out .= function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1, 'UTF-8' ) : substr( $word, 0, 1 );
    }
    return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $out, 'UTF-8' ) : strtoupper( $out );
}

function gep_demo_history_user_name( $user_id ) {
    $user_id = absint( $user_id );
    $name    = $user_id ? get_the_author_meta( 'display_name', $user_id ) : '';
    return '' !== (string) $name ? (string) $name : 'Sistema';
}

/** IDs de versiones de una página, de la más nueva a la más antigua (con caché por petición). */
function gep_demo_history_page_versions( $page_id ) {
    static $cache = array();
    $page_id = absint( $page_id );
    if ( ! isset( $cache[ $page_id ] ) ) {
        $query = new WP_Query(
            array(
                'post_type'      => 'gep_change',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'orderby'        => array( 'date' => 'DESC', 'ID' => 'DESC' ),
                'meta_key'       => '_gep_history_page_id',
                'meta_value'     => $page_id,
                'no_found_rows'  => true,
            )
        );
        $cache[ $page_id ] = array_map( 'absint', $query->posts );
    }
    return $cache[ $page_id ];
}

function gep_demo_history_previous_entry( $entry_id, $page_id ) {
    $ids   = gep_demo_history_page_versions( $page_id );
    $index = array_search( absint( $entry_id ), $ids, true );
    return ( false !== $index && isset( $ids[ $index + 1 ] ) ) ? $ids[ $index + 1 ] : 0;
}

/** Hash del contenido actual de una página (con caché por petición). */
function gep_demo_history_current_hash( $page_id ) {
    static $cache = array();
    $page_id = absint( $page_id );
    if ( ! isset( $cache[ $page_id ] ) ) {
        $cache[ $page_id ] = gep_demo_history_snapshot_hash( gep_demo_history_capture_snapshot( $page_id ) );
    }
    return $cache[ $page_id ];
}

function gep_demo_history_is_current( $entry_id, $page_id ) {
    $hash = (string) get_post_meta( $entry_id, '_gep_history_hash', true );
    return '' !== $hash && hash_equals( $hash, gep_demo_history_current_hash( $page_id ) );
}

function gep_demo_history_compare_url( $a_id, $b_id, $extra = array() ) {
    return add_query_arg( array_merge( array( 'page' => 'gep-content-history', 'compare_a' => absint( $a_id ), 'compare_b' => absint( $b_id ) ), $extra ), admin_url( 'admin.php' ) );
}

/**
 * Diferencia palabra por palabra. Devuelve HTML ya escapado para la columna
 * "antes", la columna "después" y una vista combinada.
 */
function gep_demo_history_word_diff( $old, $new ) {
    $old = (string) $old;
    $new = (string) $new;
    if ( gep_demo_history_strlen( $old ) > 2200 ) {
        $old = gep_demo_history_substr( $old, 0, 2200 ) . ' …';
    }
    if ( gep_demo_history_strlen( $new ) > 2200 ) {
        $new = gep_demo_history_substr( $new, 0, 2200 ) . ' …';
    }

    $a = preg_split( '/\s+/u', trim( $old ), -1, PREG_SPLIT_NO_EMPTY );
    $b = preg_split( '/\s+/u', trim( $new ), -1, PREG_SPLIT_NO_EMPTY );
    $a = is_array( $a ) ? $a : array();
    $b = is_array( $b ) ? $b : array();
    $n = count( $a );
    $m = count( $b );

    if ( $n * $m > 250000 ) {
        $old_html = $n ? '<del>' . esc_html( $old ) . '</del>' : '';
        $new_html = $m ? '<ins>' . esc_html( $new ) . '</ins>' : '';
        return array( 'old' => $old_html, 'new' => $new_html, 'inline' => trim( $old_html . ' ' . $new_html ) );
    }

    $lcs = array_fill( 0, $n + 1, array_fill( 0, $m + 1, 0 ) );
    for ( $i = $n - 1; $i >= 0; $i-- ) {
        for ( $j = $m - 1; $j >= 0; $j-- ) {
            $lcs[ $i ][ $j ] = $a[ $i ] === $b[ $j ] ? $lcs[ $i + 1 ][ $j + 1 ] + 1 : max( $lcs[ $i + 1 ][ $j ], $lcs[ $i ][ $j + 1 ] );
        }
    }

    $old_out = array();
    $new_out = array();
    $inline  = array();
    $i = 0;
    $j = 0;
    while ( $i < $n || $j < $m ) {
        if ( $i < $n && $j < $m && $a[ $i ] === $b[ $j ] ) {
            $word      = esc_html( $a[ $i ] );
            $old_out[] = $word;
            $new_out[] = $word;
            $inline[]  = $word;
            $i++;
            $j++;
        } elseif ( $j >= $m || ( $i < $n && $lcs[ $i + 1 ][ $j ] >= $lcs[ $i ][ $j + 1 ] ) ) {
            $word      = '<del>' . esc_html( $a[ $i ] ) . '</del>';
            $old_out[] = $word;
            $inline[]  = $word;
            $i++;
        } else {
            $word      = '<ins>' . esc_html( $b[ $j ] ) . '</ins>';
            $new_out[] = $word;
            $inline[]  = $word;
            $j++;
        }
    }

    $join = function ( $words ) {
        return str_replace( array( '</del> <del>', '</ins> <ins>' ), ' ', implode( ' ', $words ) );
    };
    return array( 'old' => $join( $old_out ), 'new' => $join( $new_out ), 'inline' => $join( $inline ) );
}

function gep_demo_history_render_change( $change, $context = '' ) {
    $type  = isset( $change['type'] ) ? $change['type'] : 'changed';
    $kind  = isset( $change['kind'] ) ? $change['kind'] : 'text';
    $old   = isset( $change['old'] ) ? (string) $change['old'] : '';
    $new   = isset( $change['new'] ) ? (string) $change['new'] : '';
    $label = gep_demo_history_friendly_label( isset( $change['label'] ) ? $change['label'] : 'Campo', $context );
    $type_labels = array( 'changed' => 'Modificado', 'added' => 'Añadido', 'removed' => 'Eliminado' );
    $empty = '<span class="gep-hx-empty">Vacío</span>';
    $is_image_pair = 'image' === $kind && filter_var( $old, FILTER_VALIDATE_URL ) && filter_var( $new, FILTER_VALIDATE_URL );
    $is_text       = 'text' === $kind || 'number' === $kind;
    ?>
    <article class="gep-hx-field is-<?php echo esc_attr( $type ); ?>">
        <header class="gep-hx-field-head">
            <strong><?php echo esc_html( $label ); ?></strong>
            <span class="gep-hx-pill is-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( isset( $type_labels[ $type ] ) ? $type_labels[ $type ] : 'Modificado' ); ?></span>
        </header>
        <?php if ( $is_image_pair ) : ?>
            <div class="gep-hx-slider" data-gep-hx-slider>
                <img class="is-a" src="<?php echo esc_url( $old ); ?>" alt="Imagen antes del cambio" loading="lazy">
                <img class="is-b" src="<?php echo esc_url( $new ); ?>" alt="Imagen después del cambio" loading="lazy">
                <span class="gep-hx-slider-tag is-a">Antes</span>
                <span class="gep-hx-slider-tag is-b">Después</span>
                <i class="gep-hx-slider-handle" aria-hidden="true"></i>
                <input type="range" min="0" max="100" value="50" aria-label="Deslizar para comparar la imagen antes y después">
            </div>
        <?php elseif ( $is_text ) : $diff = gep_demo_history_word_diff( $old, $new ); ?>
            <div class="gep-hx-sides has-inline">
                <div class="gep-hx-side is-a"><span class="gep-hx-side-label">Antes</span><div class="gep-hx-side-value"><?php echo '' === $old ? wp_kses_post( $empty ) : wp_kses_post( $diff['old'] ); ?></div></div>
                <div class="gep-hx-side is-b"><span class="gep-hx-side-label">Después</span><div class="gep-hx-side-value"><?php echo '' === $new ? wp_kses_post( $empty ) : wp_kses_post( $diff['new'] ); ?></div></div>
            </div>
            <div class="gep-hx-inline"><?php echo wp_kses_post( $diff['inline'] ); ?></div>
        <?php else : ?>
            <div class="gep-hx-sides">
                <div class="gep-hx-side is-a"><span class="gep-hx-side-label">Antes</span><div class="gep-hx-side-value"><?php echo wp_kses_post( gep_demo_history_format_value( $old, $kind ) ); ?></div></div>
                <div class="gep-hx-side is-b"><span class="gep-hx-side-label">Después</span><div class="gep-hx-side-value"><?php echo wp_kses_post( gep_demo_history_format_value( $new, $kind ) ); ?></div></div>
            </div>
        <?php endif; ?>
    </article>
    <?php
}

function gep_demo_history_integrity_ok( $entry_id ) {
    $stored   = (string) get_post_meta( $entry_id, '_gep_history_hash', true );
    $snapshot = gep_demo_history_get_entry_snapshot( $entry_id );
    return $stored && hash_equals( $stored, gep_demo_history_snapshot_hash( $snapshot ) );
}

function gep_demo_history_get_latest_hash_for_page( $post_id ) {
    $query = new WP_Query(
        array(
            'post_type'      => 'gep_change',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_key'       => '_gep_history_page_id',
            'meta_value'     => absint( $post_id ),
            'no_found_rows'  => true,
        )
    );
    if ( empty( $query->posts ) ) {
        return '';
    }
    return (string) get_post_meta( $query->posts[0], '_gep_history_hash', true );
}

function gep_demo_history_collect_filter_data() {
    $query = new WP_Query(
        array(
            'post_type'      => 'gep_change',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );

    $users = array();
    $pages = array();
    foreach ( $query->posts as $id ) {
        $uid = absint( get_post_meta( $id, '_gep_history_user_id', true ) );
        $pid = absint( get_post_meta( $id, '_gep_history_page_id', true ) );
        if ( $uid ) {
            $users[ $uid ] = get_the_author_meta( 'display_name', $uid );
        }
        if ( $pid ) {
            $context = (string) get_post_meta( $id, '_gep_history_context', true );
            $pages[ $pid ] = gep_demo_history_context_label( $context );
        }
    }
    asort( $users, SORT_NATURAL | SORT_FLAG_CASE );
    asort( $pages, SORT_NATURAL | SORT_FLAG_CASE );
    return array( 'users' => $users, 'pages' => $pages, 'all_ids' => $query->posts );
}

/** Ventana de confirmación para restaurar una versión (reemplaza al aviso del navegador). */
function gep_demo_history_render_restore_dialog( $entry_id ) {
    $entry_id = absint( $entry_id );
    $context  = (string) get_post_meta( $entry_id, '_gep_history_context', true );
    $revision = (int) get_post_meta( $entry_id, '_gep_history_revision', true );
    $section  = gep_demo_history_context_label( $context );
    $date     = get_the_date( 'j \d\e F \a \l\a\s H:i', $entry_id );
    ?>
    <dialog class="gep-hx-dialog" id="gep-hx-restore-<?php echo esc_attr( $entry_id ); ?>" aria-labelledby="gep-hx-restore-title-<?php echo esc_attr( $entry_id ); ?>">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gep-hx-restore-form">
            <input type="hidden" name="action" value="gep_demo_history_rollback">
            <input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>">
            <?php wp_nonce_field( 'gep_demo_history_rollback_' . $entry_id, 'gep_history_nonce' ); ?>
            <div class="gep-hx-dialog-icon"><span class="dashicons dashicons-backup"></span></div>
            <h2 id="gep-hx-restore-title-<?php echo esc_attr( $entry_id ); ?>">¿Volver a la versión <?php echo esc_html( $revision ); ?> de <?php echo esc_html( $section ); ?>?</h2>
            <p class="gep-hx-dialog-date">Guardada el <?php echo esc_html( $date ); ?> por <?php echo esc_html( gep_demo_history_user_name( get_post_meta( $entry_id, '_gep_history_user_id', true ) ) ); ?>.</p>
            <ul>
                <li>El contenido de <?php echo esc_html( $section ); ?> vuelve a como estaba en esa versión.</li>
                <li>Antes de cambiar nada se guarda una copia de cómo está ahora.</li>
                <li>Si te arrepientes, esa copia aparece arriba en el historial y se puede restaurar igual.</li>
            </ul>
            <label class="gep-hx-dialog-reason"><span>Motivo <em>(opcional)</em></span><input type="text" name="rollback_reason" maxlength="300" placeholder="Ej: la foto nueva se veía oscura en el celular"></label>
            <div class="gep-hx-dialog-actions">
                <button type="button" class="button" data-gep-hx-close>Cancelar</button>
                <button type="submit" class="button button-primary"><span class="dashicons dashicons-undo"></span> Sí, restaurar</button>
            </div>
        </form>
    </dialog>
    <?php
}

/** Índice "ruta => cambio" con todos los campos de una versión. */
function gep_demo_history_flatten( $snapshot ) {
    $out = array();
    foreach ( gep_demo_history_diff_snapshots( array(), $snapshot ) as $field ) {
        $out[ $field['path'] ] = $field;
    }
    return $out;
}

/** Vista previa por bloques: muestra cada bloque que cambió en A y en B, marcando lo distinto. */
function gep_demo_history_render_block_preview( $a_snap, $b_snap, $changes, $context, $a_caption, $b_caption ) {
    $flat_a  = gep_demo_history_flatten( $a_snap );
    $flat_b  = gep_demo_history_flatten( $b_snap );
    $changed = array_flip( wp_list_pluck( $changes, 'path' ) );

    $area_of = function ( $field ) use ( $context ) {
        $parts = gep_demo_history_label_parts( gep_demo_history_friendly_label( $field['label'], $context ) );
        array_pop( $parts );
        return implode( ' › ', $parts );
    };

    $areas = array();
    foreach ( $changes as $change ) {
        $areas[ $area_of( $change ) ] = true;
    }
    $areas = array_slice( array_keys( $areas ), 0, 8 );

    $render_mini = function ( $flat, $area, $side, $caption ) use ( $area_of, $changed ) {
        $image = '';
        $image_changed = false;
        $title = null;
        $texts = array();
        foreach ( $flat as $path => $field ) {
            if ( $area_of( $field ) !== $area ) {
                continue;
            }
            $value = (string) $field['new'];
            $is_changed = isset( $changed[ $path ] );
            if ( 'image' === $field['kind'] ) {
                if ( '' === $image && filter_var( $value, FILTER_VALIDATE_URL ) ) {
                    $image = $value;
                    $image_changed = $is_changed;
                }
                continue;
            }
            if ( 'link' === $field['kind'] || 'boolean' === $field['kind'] ) {
                continue;
            }
            $parts = gep_demo_history_label_parts( $field['label'] );
            $last  = end( $parts );
            if ( null === $title && in_array( $last, array( 'Título', 'Nombre' ), true ) ) {
                $title = array( 'value' => $value, 'changed' => $is_changed );
                continue;
            }
            $texts[] = array( 'label' => $last, 'value' => $value, 'changed' => $is_changed );
        }
        $texts = array_slice( $texts, 0, 4 );
        ?>
        <div class="gep-hx-mini is-<?php echo esc_attr( $side ); ?>">
            <span class="gep-hx-mini-caption"><?php echo esc_html( $caption ); ?></span>
            <div class="gep-hx-mini-body<?php echo $image ? ' has-image' : ''; ?>">
                <?php if ( $image ) : ?><img class="gep-hx-mini-img" src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy"><?php if ( $image_changed ) : ?><span class="gep-hx-mini-flag">Imagen distinta</span><?php endif; ?><?php endif; ?>
                <div class="gep-hx-mini-copy">
                    <?php if ( $title && '' !== $title['value'] ) : ?><strong class="<?php echo $title['changed'] ? 'is-changed' : ''; ?>"><?php echo esc_html( $title['value'] ); ?></strong><?php endif; ?>
                    <?php foreach ( $texts as $text ) : if ( '' === $text['value'] ) { continue; } ?>
                        <p class="<?php echo $text['changed'] ? 'is-changed' : ''; ?>"><?php echo esc_html( gep_demo_history_strlen( $text['value'] ) > 220 ? gep_demo_history_substr( $text['value'], 0, 218 ) . '…' : $text['value'] ); ?></p>
                    <?php endforeach; ?>
                    <?php if ( ! $image && ( ! $title || '' === $title['value'] ) && ! array_filter( wp_list_pluck( $texts, 'value' ), 'strlen' ) ) : ?><p class="gep-hx-empty">Este bloque está vacío en esta versión.</p><?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    };
    ?>
    <div class="gep-hx-blocks">
        <?php foreach ( $areas as $area ) : ?>
            <section class="gep-hx-block">
                <h3><?php echo esc_html( '' !== $area ? $area : gep_demo_history_context_label( $context ) ); ?></h3>
                <div class="gep-hx-block-pair">
                    <?php $render_mini( $flat_a, $area, 'a', $a_caption ); ?>
                    <?php $render_mini( $flat_b, $area, 'b', $b_caption ); ?>
                </div>
            </section>
        <?php endforeach; ?>
        <p class="gep-hx-hint"><span class="dashicons dashicons-info-outline"></span> Vista simplificada de los bloques que cambiaron. Lo distinto aparece con borde punteado. Para ver la página completa usa la vista previa del editor.</p>
    </div>
    <?php
}

function gep_demo_history_render_compare_panel( $a_id, $b_id ) {
    $a_id = absint( $a_id );
    $b_id = absint( $b_id );
    $back = admin_url( 'admin.php?page=gep-content-history' );
    if ( ! $a_id || ! $b_id || 'gep_change' !== get_post_type( $a_id ) || 'gep_change' !== get_post_type( $b_id ) ) {
        echo '<div class="gep-hx-banner is-warn"><span class="dashicons dashicons-warning"></span><p>No encontramos esas versiones. Puede que se hayan borrado por el límite del historial. <a href="' . esc_url( $back ) . '">Volver al historial</a></p></div>';
        return;
    }

    $page_id = absint( get_post_meta( $a_id, '_gep_history_page_id', true ) );
    if ( $page_id !== absint( get_post_meta( $b_id, '_gep_history_page_id', true ) ) ) {
        echo '<div class="gep-hx-banner is-warn"><span class="dashicons dashicons-warning"></span><p>Solo se pueden comparar dos versiones de la misma pestaña. <a href="' . esc_url( $back ) . '">Volver al historial</a></p></div>';
        return;
    }

    // A siempre es la más antigua de las dos.
    $versions = gep_demo_history_page_versions( $page_id );
    $pos_a    = array_search( $a_id, $versions, true );
    $pos_b    = array_search( $b_id, $versions, true );
    if ( false !== $pos_a && false !== $pos_b && $pos_a < $pos_b ) {
        list( $a_id, $b_id ) = array( $b_id, $a_id );
    }

    $context  = (string) get_post_meta( $a_id, '_gep_history_context', true );
    $section  = gep_demo_history_context_label( $context );
    $a_snap   = gep_demo_history_get_entry_snapshot( $a_id );
    $b_snap   = gep_demo_history_get_entry_snapshot( $b_id );
    $changes  = gep_demo_history_diff_snapshots( $a_snap, $b_snap );
    $a_rev    = (int) get_post_meta( $a_id, '_gep_history_revision', true );
    $b_rev    = (int) get_post_meta( $b_id, '_gep_history_revision', true );
    $a_label  = 'A · v' . $a_rev . ' · ' . get_the_date( 'j M H:i', $a_id );
    $b_label  = 'B · v' . $b_rev . ' · ' . get_the_date( 'j M H:i', $b_id );

    $strip   = array_reverse( array_slice( $versions, 0, 40 ) );
    ?>
    <section class="gep-hx-compare" id="gep-history-compare-result" data-mode="side">
        <div class="gep-hx-card gep-hx-picker">
            <div class="gep-hx-picker-head">
                <div>
                    <strong>Versiones de <?php echo esc_html( $section ); ?></strong>
                    <small>Toca una versión para compararla. La más antigua queda como <b class="is-a">A (antes)</b> y la más nueva como <b class="is-b">B (después)</b>.</small>
                </div>
            </div>
            <div class="gep-hx-strip">
                <?php foreach ( $strip as $index => $vid ) :
                    $is_a = $vid === $a_id;
                    $is_b = $vid === $b_id;
                    $v_action = (string) get_post_meta( $vid, '_gep_history_action', true );
                    $href = $is_a || $is_b ? '#' : gep_demo_history_compare_url( $vid, $b_id );
                    ?>
                    <a class="gep-hx-version<?php echo $is_a ? ' is-a' : ''; ?><?php echo $is_b ? ' is-b' : ''; ?><?php echo gep_demo_history_is_current( $vid, $page_id ) ? ' is-current' : ''; ?>" href="<?php echo esc_url( $href ); ?>" data-id="<?php echo esc_attr( $vid ); ?>" data-index="<?php echo esc_attr( $index ); ?>" title="<?php echo esc_attr( gep_demo_history_entry_summary( $vid ) ); ?>">
                        <span><?php echo esc_html( 'v' . (int) get_post_meta( $vid, '_gep_history_revision', true ) ); ?><?php echo $is_a ? ' · A' : ''; ?><?php echo $is_b ? ' · B' : ''; ?></span>
                        <small><?php echo esc_html( get_the_date( 'j M H:i', $vid ) ); ?><?php echo 'save' !== $v_action ? ' · ' . esc_html( gep_demo_history_action_label( $v_action ) ) : ''; ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="gep-hx-versus">
            <?php foreach ( array( 'a' => $a_id, 'b' => $b_id ) as $side => $vid ) : ?>
                <div class="gep-hx-versus-card is-<?php echo esc_attr( $side ); ?>">
                    <span class="gep-hx-versus-letter"><?php echo 'a' === $side ? 'A' : 'B'; ?></span>
                    <div>
                        <strong><?php echo esc_html( 'Versión ' . (int) get_post_meta( $vid, '_gep_history_revision', true ) . ' · ' . get_the_date( 'j \d\e F, H:i', $vid ) ); ?></strong>
                        <small><?php echo esc_html( gep_demo_history_user_name( get_post_meta( $vid, '_gep_history_user_id', true ) ) . ' · ' . gep_demo_history_entry_summary( $vid ) ); ?></small>
                    </div>
                    <?php if ( gep_demo_history_is_current( $vid, $page_id ) ) : ?><span class="gep-hx-pill is-current">Publicada ahora</span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ( empty( $changes ) ) : ?>
            <div class="gep-hx-card gep-hx-empty-state"><span class="dashicons dashicons-yes-alt"></span><strong>Estas dos versiones tienen el mismo contenido.</strong><p>Elige otra versión en la tira de arriba.</p></div>
        <?php else : ?>
            <div class="gep-hx-modebar">
                <div class="gep-hx-segmented" role="group" aria-label="Forma de comparar">
                    <button type="button" class="is-active" data-gep-hx-mode="side" aria-pressed="true"><span class="dashicons dashicons-columns"></span> Lado a lado</button>
                    <button type="button" data-gep-hx-mode="inline" aria-pressed="false"><span class="dashicons dashicons-editor-strikethrough"></span> Solo diferencias</button>
                    <button type="button" data-gep-hx-mode="blocks" aria-pressed="false"><span class="dashicons dashicons-visibility"></span> Vista previa</button>
                </div>
                <span class="gep-hx-count"><?php echo esc_html( 1 === count( $changes ) ? '1 diferencia' : count( $changes ) . ' diferencias' ); ?></span>
            </div>
            <div class="gep-hx-fields">
                <?php foreach ( $changes as $change ) { gep_demo_history_render_change( $change, $context ); } ?>
            </div>
            <div class="gep-hx-blocks-wrap">
                <?php gep_demo_history_render_block_preview( $a_snap, $b_snap, $changes, $context, $a_label, $b_label ); ?>
            </div>
        <?php endif; ?>

        <?php
        foreach ( array( 'a' => $a_id, 'b' => $b_id ) as $side => $vid ) :
            if ( gep_demo_history_is_current( $vid, $page_id ) ) {
                continue;
            }
            $v_rev = (int) get_post_meta( $vid, '_gep_history_revision', true );
            ?>
            <div class="gep-hx-restore-bar is-<?php echo esc_attr( $side ); ?>">
                <span class="dashicons dashicons-backup"></span>
                <p><strong>¿Prefieres la versión <?php echo esc_html( $v_rev ); ?> (<?php echo 'a' === $side ? 'A' : 'B'; ?>)?</strong> Puedes volver a ella. Antes se guarda una copia de cómo está ahora.</p>
                <?php if ( gep_demo_history_integrity_ok( $vid ) ) : ?>
                    <button type="button" class="button button-primary" data-gep-hx-open="gep-hx-restore-<?php echo esc_attr( $vid ); ?>"><span class="dashicons dashicons-undo"></span> Restaurar versión <?php echo esc_html( $v_rev ); ?></button>
                    <?php gep_demo_history_render_restore_dialog( $vid ); ?>
                <?php else : ?>
                    <span class="gep-hx-pill is-removed">No se puede restaurar: la copia guardada está dañada</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
    <?php
}

function gep_demo_render_history_page() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'No tienes permisos para ver el historial.' );
    }

    // Garantiza un punto recuperable aunque el historial se abra antes del primer guardado.
    if ( function_exists( 'gep_demo_get_page_navigation' ) ) {
        foreach ( gep_demo_get_page_navigation() as $key => $item ) {
            if ( empty( $item['page'] ) ) {
                continue;
            }
            $context_map = array( 'inicio' => 'home', 'quienes-somos' => 'directory', 'docencia' => 'docencia', 'proyectos' => 'projects', 'publicaciones' => 'publications', 'comunicaciones' => 'communications', 'noticias' => 'news', 'servicios' => 'services', 'contacto' => 'contact' );
            gep_demo_history_ensure_baseline( (int) $item['page']->ID, isset( $context_map[ $key ] ) ? $context_map[ $key ] : '' );
        }
    }

    $editor_url = admin_url( 'admin.php?page=gep-content-editor' );
    $help_url   = admin_url( 'admin.php?page=gep-content-help' );
    $base_url   = admin_url( 'admin.php?page=gep-content-history' );
    $compare_a  = isset( $_GET['compare_a'] ) ? absint( $_GET['compare_a'] ) : 0;
    $compare_b  = isset( $_GET['compare_b'] ) ? absint( $_GET['compare_b'] ) : 0;

    if ( $compare_a && $compare_b ) {
        ?>
        <div class="wrap gep-history-wrap gep-hx">
            <header class="gep-hx-heading">
                <nav class="gep-hx-breadcrumb"><span class="dashicons dashicons-admin-home"></span> Contenido GEP <i>›</i> <a href="<?php echo esc_url( $base_url ); ?>">Historial de cambios</a> <i>›</i> <strong>Comparar</strong></nav>
                <div class="gep-hx-title-row">
                    <div><h1>Comparar versiones</h1><p>Lo tachado en rojo es lo que había antes. Lo marcado en verde es lo nuevo.</p></div>
                    <a class="button" href="<?php echo esc_url( $base_url ); ?>"><span class="dashicons dashicons-arrow-left-alt"></span> Volver al historial</a>
                </div>
            </header>
            <?php gep_demo_history_render_compare_panel( $compare_a, $compare_b ); ?>
        </div>
        <?php
        return;
    }

    $filter_data = gep_demo_history_collect_filter_data();
    $page_id     = isset( $_GET['section'] ) ? absint( $_GET['section'] ) : 0;
    $user_id     = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
    $action      = isset( $_GET['change_action'] ) ? sanitize_key( wp_unslash( $_GET['change_action'] ) ) : '';
    $search      = isset( $_GET['history_search'] ) ? sanitize_text_field( wp_unslash( $_GET['history_search'] ) ) : '';
    $date_from   = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
    $date_to     = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
    $paged       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $date_from   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ? $date_from : '';
    $date_to     = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ? $date_to : '';

    $meta_query = array();
    if ( $page_id ) {
        $meta_query[] = array( 'key' => '_gep_history_page_id', 'value' => $page_id );
    }
    if ( $user_id ) {
        $meta_query[] = array( 'key' => '_gep_history_user_id', 'value' => $user_id );
    }
    if ( $action ) {
        $meta_query[] = array( 'key' => '_gep_history_action', 'value' => $action );
    }

    $date_query = array();
    if ( $date_from ) {
        $date_query['after'] = $date_from . ' 00:00:00';
        $date_query['inclusive'] = true;
    }
    if ( $date_to ) {
        $date_query['before'] = $date_to . ' 23:59:59';
        $date_query['inclusive'] = true;
    }

    $args = array(
        'post_type'      => 'gep_change',
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'paged'          => $paged,
        'orderby'        => array( 'date' => 'DESC', 'ID' => 'DESC' ),
        's'              => $search,
    );
    if ( $meta_query ) {
        $args['meta_query'] = $meta_query;
    }
    if ( $date_query ) {
        $args['date_query'] = array( $date_query );
    }
    $history_query = new WP_Query( $args );

    $total_count     = count( $filter_data['all_ids'] );
    $protected_count = 0;
    $unique_users    = array();
    $page_counts     = array();
    foreach ( $filter_data['all_ids'] as $hid ) {
        if ( '1' === get_post_meta( $hid, '_gep_history_protected', true ) ) {
            $protected_count++;
        }
        $uid = absint( get_post_meta( $hid, '_gep_history_user_id', true ) );
        if ( $uid ) {
            $unique_users[ $uid ] = true;
        }
        $pid = absint( get_post_meta( $hid, '_gep_history_page_id', true ) );
        $page_counts[ $pid ] = isset( $page_counts[ $pid ] ) ? $page_counts[ $pid ] + 1 : 1;
    }

    $latest_id    = ! empty( $filter_data['all_ids'] ) ? (int) $filter_data['all_ids'][0] : 0;
    $notice       = isset( $_GET['gep_history_notice'] ) ? sanitize_key( wp_unslash( $_GET['gep_history_notice'] ) ) : '';
    $focus        = isset( $_GET['focus'] ) ? absint( $_GET['focus'] ) : 0;
    $has_advanced = $user_id || $action || $date_from || $date_to;
    $keep_args    = array_filter( array( 'history_search' => $search, 'user' => $user_id, 'change_action' => $action, 'date_from' => $date_from, 'date_to' => $date_to ) );
    ?>
    <div class="wrap gep-history-wrap gep-hx" data-focus-entry="<?php echo esc_attr( $focus ); ?>">
        <header class="gep-hx-heading">
            <nav class="gep-hx-breadcrumb"><span class="dashicons dashicons-admin-home"></span> Contenido GEP <i>›</i> <strong>Historial de cambios</strong></nav>
            <div class="gep-hx-title-row">
                <div><h1>Historial de cambios</h1><p>Cada vez que alguien guarda, queda una versión. Aquí ves qué cambió, comparas versiones y puedes volver atrás.</p></div>
                <div class="gep-hx-title-actions">
                    <a class="button" href="<?php echo esc_url( $help_url ); ?>"><span class="dashicons dashicons-editor-help"></span> Ayuda</a>
                    <a class="button button-primary" href="<?php echo esc_url( $editor_url ); ?>"><span class="dashicons dashicons-edit"></span> Ir al editor</a>
                </div>
            </div>
        </header>

        <?php if ( $notice ) :
            $messages = array(
                'rolled-back'     => array( 'ok', 'Listo, la versión se restauró. La copia de cómo estaba antes quedó guardada en el historial por si quieres deshacerlo.' ),
                'already-current' => array( 'info', 'Esa versión ya es la que está publicada. No hubo que cambiar nada.' ),
                'note-saved'      => array( 'ok', 'Nota guardada.' ),
                'protect-updated' => array( 'ok', 'Listo, se actualizó la protección de la versión.' ),
                'backup-restored' => array( 'ok', 'Listo, se restauró el respaldo completo. Antes de cada pestaña quedó un punto de seguridad en este historial por si quieres deshacerlo.' ),
                'backup-same'     => array( 'info', 'El contenido del respaldo es igual al que está publicado. No hubo que cambiar nada.' ),
                'backup-invalid'  => array( 'info', 'Ese archivo no es un respaldo completo de GEP Editor. Usa el archivo que descargaste con “Descargar respaldo”.' ),
                'backup-nofile'   => array( 'info', 'No llegó ningún archivo. Elige el respaldo y vuelve a intentarlo.' ),
                'backup-confirm'  => array( 'info', 'Para restaurar, marca la casilla que confirma que el contenido actual se reemplaza.' ),
            );
            $message = isset( $messages[ $notice ] ) ? $messages[ $notice ] : array( 'ok', 'Operación completada.' );
            ?>
            <div class="gep-hx-banner is-<?php echo esc_attr( $message[0] ); ?>"><span class="dashicons <?php echo 'ok' === $message[0] ? 'dashicons-yes-alt' : 'dashicons-info-outline'; ?>"></span><p><?php echo esc_html( $message[1] ); ?></p></div>
        <?php endif; ?>

        <section class="gep-hx-card gep-hx-summary">
            <div class="gep-hx-summary-icon"><span class="dashicons dashicons-backup"></span></div>
            <div class="gep-hx-summary-copy">
                <strong><?php echo esc_html( number_format_i18n( $total_count ) ); ?> versiones guardadas</strong>
                <?php if ( $latest_id ) : ?>
                    <small>Último cambio: <?php echo esc_html( gep_demo_history_entry_summary( $latest_id ) ); ?> · <?php echo esc_html( gep_demo_history_user_name( get_post_meta( $latest_id, '_gep_history_user_id', true ) ) ); ?>, hace <?php echo esc_html( human_time_diff( (int) get_post_time( 'U', true, $latest_id ), time() ) ); ?></small>
                <?php else : ?>
                    <small>Todavía no hay cambios guardados.</small>
                <?php endif; ?>
            </div>
            <div class="gep-hx-summary-stats">
                <span><b><?php echo esc_html( count( $unique_users ) ); ?></b> <?php echo 1 === count( $unique_users ) ? 'persona editó' : 'personas editaron'; ?></span>
                <span><b><?php echo esc_html( $protected_count ); ?></b> protegidas</span>
            </div>
        </section>

        <?php if ( function_exists( 'gep_demo_backup_render_panel' ) ) { gep_demo_backup_render_panel(); } ?>

        <section class="gep-hx-card gep-hx-tools">
            <form method="get" class="gep-hx-search">
                <input type="hidden" name="page" value="gep-content-history">
                <?php if ( $page_id ) : ?><input type="hidden" name="section" value="<?php echo esc_attr( $page_id ); ?>"><?php endif; ?>
                <label class="gep-hx-search-box"><span class="dashicons dashicons-search"></span><input type="search" name="history_search" value="<?php echo esc_attr( $search ); ?>" placeholder="Buscar: un título, un nombre, una palabra…" aria-label="Buscar en el historial"></label>
                <details class="gep-hx-advanced" <?php echo $has_advanced ? 'open' : ''; ?>>
                    <summary><span class="dashicons dashicons-filter"></span> Más filtros<?php echo $has_advanced ? ' <b>activos</b>' : ''; ?></summary>
                    <div class="gep-hx-advanced-grid">
                        <label><span>Tipo</span><select name="change_action"><option value="">Todos</option><?php foreach ( array( 'save' => 'Guardados', 'rollback' => 'Restauraciones', 'restore' => 'Respaldos restaurados', 'checkpoint' => 'Copias de seguridad', 'baseline' => 'Puntos de partida', 'migration' => 'Traslados del CMS' ) as $a => $a_label ) : ?><option value="<?php echo esc_attr( $a ); ?>" <?php selected( $action, $a ); ?>><?php echo esc_html( $a_label ); ?></option><?php endforeach; ?></select></label>
                        <label><span>Persona</span><select name="user"><option value="0">Todas</option><?php foreach ( $filter_data['users'] as $uid => $name ) : ?><option value="<?php echo esc_attr( $uid ); ?>" <?php selected( $user_id, $uid ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select></label>
                        <label><span>Desde</span><input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>"></label>
                        <label><span>Hasta</span><input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>"></label>
                    </div>
                </details>
                <button class="button button-primary" type="submit">Buscar</button>
                <?php if ( $search || $has_advanced || $page_id ) : ?><a class="button" href="<?php echo esc_url( $base_url ); ?>">Limpiar</a><?php endif; ?>
            </form>
            <nav class="gep-hx-chips" aria-label="Filtrar por pestaña">
                <a class="gep-hx-chip<?php echo ! $page_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( $keep_args, $base_url ) ); ?>">Todas <b><?php echo esc_html( $total_count ); ?></b></a>
                <?php foreach ( $filter_data['pages'] as $pid => $label ) : ?>
                    <a class="gep-hx-chip<?php echo $page_id === (int) $pid ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array_merge( $keep_args, array( 'section' => $pid ) ), $base_url ) ); ?>"><?php echo esc_html( $label ); ?> <b><?php echo esc_html( isset( $page_counts[ $pid ] ) ? $page_counts[ $pid ] : 0 ); ?></b></a>
                <?php endforeach; ?>
            </nav>
        </section>

        <section class="gep-hx-log">
            <?php if ( ! $history_query->have_posts() ) : ?>
                <div class="gep-hx-card gep-hx-empty-state"><span class="dashicons dashicons-search"></span><strong>No hay cambios con estos filtros.</strong><p>Prueba con otra palabra o toca "Limpiar".</p></div>
            <?php else : ?>
                <?php
                $last_day  = '';
                $today     = current_time( 'Y-m-d' );
                $yesterday = gmdate( 'Y-m-d', strtotime( $today . ' -1 day' ) );
                while ( $history_query->have_posts() ) :
                    $history_query->the_post();
                    $entry_id     = get_the_ID();
                    $day          = get_the_date( 'Y-m-d' );
                    $pid          = absint( get_post_meta( $entry_id, '_gep_history_page_id', true ) );
                    $context      = (string) get_post_meta( $entry_id, '_gep_history_context', true );
                    $entry_action = (string) get_post_meta( $entry_id, '_gep_history_action', true );
                    $uid          = absint( get_post_meta( $entry_id, '_gep_history_user_id', true ) );
                    $changes      = gep_demo_history_get_entry_changes( $entry_id );
                    $changes      = is_array( $changes ) ? $changes : array();
                    $note         = (string) get_post_meta( $entry_id, '_gep_history_note', true );
                    $protected    = '1' === get_post_meta( $entry_id, '_gep_history_protected', true );
                    $integrity    = gep_demo_history_integrity_ok( $entry_id );
                    $is_current   = gep_demo_history_is_current( $entry_id, $pid );
                    $revision     = (int) get_post_meta( $entry_id, '_gep_history_revision', true );
                    $versions     = gep_demo_history_page_versions( $pid );
                    $latest_page  = $versions ? $versions[0] : 0;
                    $previous     = gep_demo_history_previous_entry( $entry_id, $pid );
                    $is_system    = in_array( $entry_action, array( 'baseline', 'checkpoint' ), true );
                    $image        = gep_demo_history_first_image_change( $changes );
                    $user_name    = gep_demo_history_user_name( $uid );
                    $export_url   = wp_nonce_url( admin_url( 'admin-post.php?action=gep_demo_history_export&entry_id=' . $entry_id ), 'gep_demo_history_export_' . $entry_id );

                    if ( $day !== $last_day ) {
                        if ( '' !== $last_day ) {
                            echo '</div>';
                        }
                        $day_label = $day === $today ? 'Hoy' : ( $day === $yesterday ? 'Ayer' : get_the_date( 'l j \d\e F' ) );
                        echo '<h2 class="gep-hx-day">' . esc_html( $day_label ) . '</h2><div class="gep-hx-rows">';
                        $last_day = $day;
                    }
                    ?>
                    <article class="gep-hx-row<?php echo $is_system ? ' is-system' : ''; ?><?php echo $focus === $entry_id ? ' is-focus' : ''; ?>" id="gep-history-entry-<?php echo esc_attr( $entry_id ); ?>">
                        <span class="gep-hx-avatar" aria-hidden="true"><?php echo $is_system ? '<span class="dashicons dashicons-shield"></span>' : esc_html( gep_demo_history_initials( $user_name ) ); ?></span>
                        <div class="gep-hx-row-main">
                            <p class="gep-hx-row-title"><strong><?php echo esc_html( $user_name ); ?></strong> · <?php echo esc_html( gep_demo_history_entry_summary( $entry_id, $changes ) ); ?></p>
                            <div class="gep-hx-row-meta">
                                <span class="gep-hx-tag"><span class="dashicons <?php echo esc_attr( gep_demo_history_context_icon( $context ) ); ?>"></span> <?php echo esc_html( gep_demo_history_context_label( $context ) ); ?></span>
                                <span>Versión <?php echo esc_html( $revision ); ?></span>
                                <span><?php echo esc_html( get_the_date( 'H:i' ) ); ?></span>
                                <?php if ( $changes && ! $is_system ) : ?><span><?php echo esc_html( 1 === count( $changes ) ? '1 cambio' : count( $changes ) . ' cambios' ); ?></span><?php endif; ?>
                                <?php if ( $is_current ) : ?><span class="gep-hx-pill is-current"><span class="dashicons dashicons-yes"></span> Así está publicado ahora</span><?php endif; ?>
                                <?php if ( in_array( $entry_action, array( 'rollback', 'restore' ), true ) ) : ?><span class="gep-hx-pill is-restore"><span class="dashicons dashicons-undo"></span> Restauración</span><?php endif; ?>
                                <?php if ( $protected ) : ?><span class="gep-hx-pill is-protected"><span class="dashicons dashicons-lock"></span> Protegida</span><?php endif; ?>
                                <?php if ( ! $integrity ) : ?><span class="gep-hx-pill is-removed"><span class="dashicons dashicons-warning"></span> Copia dañada</span><?php endif; ?>
                            </div>
                            <?php if ( '' !== $note && ! $is_system ) : ?><p class="gep-hx-row-note"><span class="dashicons dashicons-format-quote"></span> <?php echo esc_html( $note ); ?></p><?php endif; ?>
                        </div>
                        <?php if ( $image ) : ?>
                            <div class="gep-hx-thumbs" aria-label="Imagen antes y después">
                                <?php if ( $image['old'] ) : ?><img src="<?php echo esc_url( $image['old'] ); ?>" alt="Antes" loading="lazy"><?php endif; ?>
                                <?php if ( $image['old'] && $image['new'] ) : ?><span class="dashicons dashicons-arrow-right-alt"></span><?php endif; ?>
                                <?php if ( $image['new'] ) : ?><img src="<?php echo esc_url( $image['new'] ); ?>" alt="Después" loading="lazy"><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="gep-hx-row-actions">
                            <?php if ( ! $is_system && $previous ) : ?>
                                <a class="button" href="<?php echo esc_url( gep_demo_history_compare_url( $previous, $entry_id ) ); ?>"><span class="dashicons dashicons-visibility"></span> Ver qué cambió</a>
                            <?php elseif ( ! $is_current && $latest_page && $latest_page !== $entry_id ) : ?>
                                <a class="button" href="<?php echo esc_url( gep_demo_history_compare_url( $entry_id, $latest_page ) ); ?>"><span class="dashicons dashicons-visibility"></span> Comparar con ahora</a>
                            <?php endif; ?>
                            <?php if ( ! $is_current && $integrity ) : ?>
                                <button type="button" class="button gep-hx-restore-btn" data-gep-hx-open="gep-hx-restore-<?php echo esc_attr( $entry_id ); ?>"><span class="dashicons dashicons-undo"></span> Restaurar</button>
                            <?php endif; ?>
                            <details class="gep-hx-more">
                                <summary class="button" aria-label="Más opciones"><span class="dashicons dashicons-ellipsis"></span></summary>
                                <div class="gep-hx-more-menu">
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gep-hx-note-form">
                                        <input type="hidden" name="action" value="gep_demo_history_note"><input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>"><?php wp_nonce_field( 'gep_demo_history_note_' . $entry_id, 'gep_history_nonce' ); ?>
                                        <label><span>Nota de esta versión</span><textarea name="history_note" rows="2" placeholder="Ej: ajuste pedido en la reunión del lunes"><?php echo esc_textarea( $note ); ?></textarea></label>
                                        <button class="button" type="submit"><span class="dashicons dashicons-saved"></span> Guardar nota</button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                        <input type="hidden" name="action" value="gep_demo_history_protect"><input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>"><?php wp_nonce_field( 'gep_demo_history_protect_' . $entry_id, 'gep_history_nonce' ); ?>
                                        <button class="gep-hx-menu-item" type="submit" <?php disabled( 'baseline', $entry_action ); ?>><span class="dashicons <?php echo $protected ? 'dashicons-unlock' : 'dashicons-lock'; ?>"></span> <?php echo $protected ? 'Quitar protección' : 'Proteger (no se borra nunca)'; ?></button>
                                    </form>
                                    <a class="gep-hx-menu-item" href="<?php echo esc_url( $export_url ); ?>"><span class="dashicons dashicons-download"></span> Descargar copia (JSON)</a>
                                </div>
                            </details>
                        </div>
                        <?php if ( ! $is_current && $integrity ) { gep_demo_history_render_restore_dialog( $entry_id ); } ?>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
                </div>

                <?php if ( $history_query->max_num_pages > 1 ) : ?>
                    <div class="gep-hx-pagination"><?php echo wp_kses_post( paginate_links( array( 'total' => $history_query->max_num_pages, 'current' => $paged, 'format' => '&paged=%#%', 'prev_text' => '← Más nuevos', 'next_text' => 'Más antiguos →' ) ) ); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
    <?php
}

function gep_demo_history_admin_assets( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen || 'contenido-gep_page_gep-content-history' !== $screen->id ) {
        return;
    }

    // Versión por fecha de archivo: evita que el navegador use estilos viejos tras actualizar el ZIP.
    $dir = plugin_dir_path( GEP_DEMO_PLUGIN_FILE ) . 'assets/';
    $css = file_exists( $dir . 'history.css' ) ? GEP_EDITOR_VERSION . '.' . filemtime( $dir . 'history.css' ) : GEP_EDITOR_VERSION;
    $js  = file_exists( $dir . 'history.js' ) ? GEP_EDITOR_VERSION . '.' . filemtime( $dir . 'history.js' ) : GEP_EDITOR_VERSION;

    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style( 'gep-demo-history', plugin_dir_url( GEP_DEMO_PLUGIN_FILE ) . 'assets/history.css', array( 'dashicons' ), $css );
    wp_enqueue_script( 'gep-demo-history', plugin_dir_url( GEP_DEMO_PLUGIN_FILE ) . 'assets/history.js', array(), $js, true );
}
add_action( 'admin_enqueue_scripts', 'gep_demo_history_admin_assets' );
