<?php
/**
 * Gestión modular de apartados de página.
 *
 * Mantiene los bloques históricos del plugin intactos y añade una capa de
 * estructura: ordenar, ocultar/restaurar y crear apartados reutilizables.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function gep_demo_section_catalog() {
    return array(
        'text' => array(
            'label'       => 'Texto',
            'description' => 'Título y párrafos.',
            'icon'        => 'dashicons-editor-alignleft',
        ),
        'image_text' => array(
            'label'       => 'Imagen + texto',
            'description' => 'Contenido editorial con fotografía.',
            'icon'        => 'dashicons-align-pull-left',
        ),
        'cards' => array(
            'label'       => 'Tarjetas',
            'description' => 'Grupo de tarjetas con título, texto e imagen.',
            'icon'        => 'dashicons-screenoptions',
        ),
        'stats' => array(
            'label'       => 'Cifras',
            'description' => 'Indicadores o números destacados.',
            'icon'        => 'dashicons-chart-bar',
        ),
        'video' => array(
            'label'       => 'Video',
            'description' => 'Video desde WordPress, YouTube o Vimeo.',
            'icon'        => 'dashicons-video-alt3',
        ),
        'cta' => array(
            'label'       => 'Llamado / CTA',
            'description' => 'Mensaje final con botón opcional.',
            'icon'        => 'dashicons-megaphone',
        ),
    );
}

function gep_demo_section_definitions() {
    return array(
        'home' => array(
            'hero'        => array( 'label' => 'Hero principal',       'type' => 'Hero',              'edit_selector' => '[data-home-preview="hero"]' ),
            'intro'       => array( 'label' => 'Laboratorio GEP',      'type' => 'Texto',             'edit_selector' => '[data-home-preview="intro"]' ),
            'stats'       => array( 'label' => 'GEP en cifras',        'type' => 'Cifras',            'edit_selector' => '[data-home-preview="stats"]' ),
            'definitions' => array( 'label' => 'Conceptos clave',      'type' => 'Tarjetas',          'edit_selector' => '[data-home-preview="definitions"]' ),
            'field'       => array( 'label' => 'Registro visual',      'type' => 'Imagen + texto',    'edit_selector' => '[data-home-preview="field"]' ),
            'research'    => array( 'label' => 'Líneas de trabajo',    'type' => 'Tarjetas + imagen', 'edit_selector' => '[data-home-preview="research"]' ),
            'portal'      => array( 'label' => 'Explorar GEP',         'type' => 'Enlaces',           'edit_selector' => '[data-home-preview="portal"]' ),
            'landscape'   => array( 'label' => 'Mensaje de paisaje',   'type' => 'Franja',            'edit_selector' => '[data-home-preview="landscape"]' ),
            'resources'   => array( 'label' => 'Recursos destacados',  'type' => 'Tarjetas',          'edit_selector' => '[data-home-preview="resources"]' ),
            'topics'      => array( 'label' => 'Biblioteca temática',  'type' => 'Lista de temas',         'edit_selector' => '[data-home-preview="topics"]' ),
            'network'     => array( 'label' => 'Herramientas y datos', 'type' => 'Imagen + enlaces',  'edit_selector' => '[data-home-preview="network"]' ),
            'final'       => array( 'label' => 'Nota y CTA final',     'type' => 'CTA',               'edit_selector' => '[data-home-preview="final"]' ),
        ),
        'directory' => array(
            'hero'      => array( 'label' => 'Cabecera',               'type' => 'Hero',      'edit_selector' => '[data-preview="header"]' ),
            'team'      => array( 'label' => 'Equipo y disciplinas',   'type' => 'Tarjetas',  'edit_selector' => '[data-preview="team"]' ),
            'directory' => array( 'label' => 'Personas y grupos',      'type' => 'Directorio','edit_selector' => '[data-preview="directory"]' ),
        ),
        'contact' => array(
            'hero'    => array( 'label' => 'Cabecera',                'type' => 'Hero',             'edit_selector' => '[data-contact-preview="hero"]' ),
            'contact' => array( 'label' => 'Contacto y ubicación',    'type' => 'Ficha + ubicación','edit_selector' => '[data-contact-preview="card"]' ),
            'cta'     => array( 'label' => 'Llamado final',           'type' => 'CTA',              'edit_selector' => '[data-contact-preview="cta"]' ),
        ),
        'docencia' => array(
            'hero'    => array( 'label' => 'Cabecera',                'type' => 'Hero',          'edit_selector' => '[data-docencia-preview="hero"]' ),
            'courses' => array( 'label' => 'Ruta, cursos y programas','type' => 'Cursos',        'edit_selector' => '[data-docencia-preview="learning"]' ),
            'fields'  => array( 'label' => 'Campos de formación',     'type' => 'Imagen + texto','edit_selector' => '[data-docencia-preview="fields"]' ),
        ),
        'projects' => array(
            'hero'       => array( 'label' => 'Cabecera',             'type' => 'Hero',      'edit_selector' => '[data-project-preview="hero"]' ),
            'features'   => array( 'label' => 'Proyectos vigentes',   'type' => 'Tarjetas',  'edit_selector' => '[data-project-preview="features"]' ),
            'trajectory' => array( 'label' => 'Trayectoria',          'type' => 'Listado',   'edit_selector' => '[data-project-preview="trajectory"]' ),
            'areas'      => array( 'label' => 'Áreas de trabajo',     'type' => 'Tarjetas',  'edit_selector' => '[data-project-preview="areas"]' ),
        ),
        'publications' => array(
            'hero'    => array( 'label' => 'Cabecera',                 'type' => 'Hero',     'edit_selector' => '[data-publication-preview="hero"]' ),
            // v9.24.0: el buscador se edita como apartado propio. "content" conserva su id
            // para no tocar los datos guardados; "search" se inserta antes de él.
            'search'  => array( 'label' => 'Buscador',                 'type' => 'Búsqueda y filtros', 'edit_selector' => '[data-publication-preview="search"]', 'pinned_before' => 'content' ),
            'content' => array( 'label' => 'Publicaciones',            'type' => 'Listado',  'edit_selector' => '[data-publication-preview="records"]' ),
        ),
        'communications' => array(
            'hero'    => array( 'label' => 'Cabecera',                 'type' => 'Hero',       'edit_selector' => '[data-hub-preview="hero"]' ),
            'content' => array( 'label' => 'Comunicaciones',           'type' => 'Contenido',  'edit_selector' => '[data-hub-preview="content"]' ),
        ),
        'news' => array(
            'hero'    => array( 'label' => 'Cabecera',                 'type' => 'Hero',       'edit_selector' => '[data-hub-preview="hero"]' ),
            'content' => array( 'label' => 'Noticias y entrevistas',   'type' => 'Contenido',  'edit_selector' => '[data-hub-preview="content"]' ),
        ),
        'services' => array(
            'hero'     => array( 'label' => 'Cabecera',                'type' => 'Hero',       'edit_selector' => '[data-service-preview="hero"]' ),
            'services' => array( 'label' => 'Servicios',               'type' => 'Bloques',    'edit_selector' => '[data-service-preview="services"]' ),
            'steps'    => array( 'label' => 'Cómo trabajamos',         'type' => 'Pasos',      'edit_selector' => '[data-service-preview="steps"]' ),
            'cta'      => array( 'label' => 'Llamado final',           'type' => 'CTA',        'edit_selector' => '[data-service-preview="cta"]' ),
        ),
    );
}

function gep_demo_section_context_key( $context ) {
    $context = sanitize_key( (string) $context );
    $aliases = array(
        'inicio'        => 'home',
        'quienes-somos' => 'directory',
        'contacto'      => 'contact',
        'proyectos'     => 'projects',
        'publicaciones' => 'publications',
        'comunicaciones'=> 'communications',
        'noticias'      => 'news',
        'servicios'     => 'services',
    );
    return isset( $aliases[ $context ] ) ? $aliases[ $context ] : $context;
}

function gep_demo_section_structure_meta_key( $context ) {
    return 'gep_page_structure_' . gep_demo_section_context_key( $context ) . '_json';
}

function gep_demo_section_custom_meta_key( $context ) {
    return 'gep_custom_sections_' . gep_demo_section_context_key( $context ) . '_json';
}

function gep_demo_default_page_structure( $context ) {
    $definitions = gep_demo_section_definitions();
    $context     = gep_demo_section_context_key( $context );
    $rows        = array();
    if ( empty( $definitions[ $context ] ) ) {
        return $rows;
    }
    foreach ( $definitions[ $context ] as $id => $definition ) {
        $rows[] = array(
            'id'      => sanitize_key( $id ),
            'kind'    => 'builtin',
            'enabled' => true,
        );
    }
    return $rows;
}

function gep_demo_get_custom_sections( $post_id, $context ) {
    $raw = get_post_meta( $post_id, gep_demo_section_custom_meta_key( $context ), true );
    if ( '' === $raw ) {
        return array();
    }
    $decoded = json_decode( $raw, true );
    return is_array( $decoded ) ? $decoded : array();
}

function gep_demo_get_page_structure( $post_id, $context ) {
    $context     = gep_demo_section_context_key( $context );
    $definitions = gep_demo_section_definitions();
    $defaults    = gep_demo_default_page_structure( $context );
    $customs     = gep_demo_get_custom_sections( $post_id, $context );
    $raw         = get_post_meta( $post_id, gep_demo_section_structure_meta_key( $context ), true );
    $saved       = '' !== $raw ? json_decode( $raw, true ) : null;

    if ( ! is_array( $saved ) ) {
        return $defaults;
    }

    $normalized = array();
    $seen       = array();
    foreach ( $saved as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }
        $id   = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
        $kind = isset( $row['kind'] ) && 'custom' === $row['kind'] ? 'custom' : 'builtin';
        if ( '' === $id || isset( $seen[ $id ] ) ) {
            continue;
        }
        if ( 'builtin' === $kind && empty( $definitions[ $context ][ $id ] ) ) {
            continue;
        }
        if ( 'custom' === $kind && empty( $customs[ $id ] ) ) {
            continue;
        }
        $normalized[] = array(
            'id'      => $id,
            'kind'    => $kind,
            'enabled' => ! array_key_exists( 'enabled', $row ) || (bool) $row['enabled'],
        );
        $seen[ $id ] = true;
    }

    // Las actualizaciones pueden incorporar nuevos bloques nativos. Se añaden
    // al final sin alterar el orden que el usuario ya guardó. v9.24.0: un bloque
    // con "pinned_before" (Buscador) se coloca justo antes de su bloque asociado.
    if ( ! empty( $definitions[ $context ] ) ) {
        foreach ( $definitions[ $context ] as $id => $definition ) {
            if ( isset( $seen[ $id ] ) ) {
                continue;
            }
            $normalized[] = array( 'id' => $id, 'kind' => 'builtin', 'enabled' => true );
            $seen[ $id ]  = true;
        }
    }
    $normalized = gep_demo_section_apply_pins( $normalized, $context );

    // Conserva apartados personalizados que existan pero que todavía no estén
    // en el layout (por ejemplo, tras una recuperación de historial).
    foreach ( $customs as $id => $section ) {
        $clean_id = sanitize_key( $id );
        if ( '' === $clean_id || isset( $seen[ $clean_id ] ) ) {
            continue;
        }
        $normalized[] = array( 'id' => $clean_id, 'kind' => 'custom', 'enabled' => true );
        $seen[ $clean_id ] = true;
    }

    return $normalized;
}

/**
 * v9.24.0 · Mantiene cada bloque "anclado" justo antes de su bloque asociado
 * (por ejemplo, Buscador antes de Publicaciones), porque en la página pública
 * ambos viven en la misma sección.
 */
function gep_demo_section_apply_pins( $rows, $context ) {
    $definitions = gep_demo_section_definitions();
    $context     = gep_demo_section_context_key( $context );
    if ( empty( $definitions[ $context ] ) || ! is_array( $rows ) ) {
        return $rows;
    }
    foreach ( $definitions[ $context ] as $id => $definition ) {
        if ( empty( $definition['pinned_before'] ) ) {
            continue;
        }
        $anchor = $definition['pinned_before'];
        $pinned = null;
        foreach ( $rows as $index => $row ) {
            if ( isset( $row['id'] ) && $id === $row['id'] && 'builtin' === $row['kind'] ) {
                $pinned = $row;
                unset( $rows[ $index ] );
                break;
            }
        }
        $rows = array_values( $rows );
        if ( null === $pinned ) {
            continue;
        }
        $position = count( $rows );
        foreach ( $rows as $index => $row ) {
            if ( isset( $row['id'] ) && $anchor === $row['id'] ) {
                $position = $index;
                break;
            }
        }
        array_splice( $rows, $position, 0, array( $pinned ) );
    }
    return $rows;
}

function gep_demo_clean_custom_section_items( $items, $type ) {
    $clean = array();
    $items = is_array( $items ) ? $items : array();
    foreach ( $items as $item ) {
        if ( ! is_array( $item ) ) continue;
        if ( 'stats' === $type ) {
            $row = array(
                'value' => sanitize_text_field( isset( $item['value'] ) ? $item['value'] : '' ),
                'label' => sanitize_text_field( isset( $item['label'] ) ? $item['label'] : '' ),
                'text'  => sanitize_text_field( isset( $item['text'] ) ? $item['text'] : '' ),
            );
            if ( '' !== $row['value'] || '' !== $row['label'] || '' !== $row['text'] ) $clean[] = $row;
        } else {
            $row = array(
                'title'  => sanitize_text_field( isset( $item['title'] ) ? $item['title'] : '' ),
                'text'   => sanitize_textarea_field( isset( $item['text'] ) ? $item['text'] : '' ),
                'image'  => esc_url_raw( isset( $item['image'] ) ? $item['image'] : '' ),
                'url'    => esc_url_raw( isset( $item['url'] ) ? $item['url'] : '' ),
                'button' => sanitize_text_field( isset( $item['button'] ) ? $item['button'] : '' ),
            );
            if ( '' !== $row['title'] || '' !== $row['text'] || '' !== $row['image'] || '' !== $row['url'] ) $clean[] = $row;
        }
    }
    return array_slice( $clean, 0, 20 );
}

/**
 * v9.25.0 · Botones visuales de los apartados: texto, enlace, cómo se abre y estilo.
 * Máximo 4. Un botón sin texto o sin enlace no se guarda.
 */
function gep_demo_clean_custom_section_buttons( $buttons ) {
    $clean   = array();
    $buttons = is_array( $buttons ) ? $buttons : array();
    foreach ( $buttons as $button ) {
        if ( ! is_array( $button ) ) continue;
        $text = sanitize_text_field( isset( $button['text'] ) ? $button['text'] : '' );
        $url  = isset( $button['url'] ) ? trim( (string) $button['url'] ) : '';
        // Un correo escrito tal cual se convierte en enlace de correo.
        if ( '' !== $url && is_email( $url ) ) $url = 'mailto:' . $url;
        $url  = esc_url_raw( $url );
        if ( '' === $text || '' === $url ) continue;
        $clean[] = array(
            'text'   => function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 60 ) : substr( $text, 0, 60 ),
            'url'    => $url,
            'target' => isset( $button['target'] ) && 'new' === $button['target'] ? 'new' : 'same',
            'style'  => isset( $button['style'] ) && 'secondary' === $button['style'] ? 'secondary' : 'primary',
        );
        if ( count( $clean ) >= 4 ) break;
    }
    return $clean;
}

/** Botones de un apartado; los CTA antiguos (un solo botón) se leen como el primero. */
function gep_demo_custom_section_buttons( $section ) {
    if ( ! empty( $section['buttons'] ) && is_array( $section['buttons'] ) ) {
        return $section['buttons'];
    }
    if ( ! empty( $section['button'] ) && ! empty( $section['url'] ) ) {
        return array( array( 'text' => $section['button'], 'url' => $section['url'], 'target' => 'same', 'style' => 'primary' ) );
    }
    return array();
}

function gep_demo_render_custom_section_buttons( $section, $dark = false ) {
    $buttons = gep_demo_custom_section_buttons( $section );
    if ( empty( $buttons ) ) return '';
    $html = '<div class="gep-custom-actions">';
    foreach ( $buttons as $button ) {
        if ( empty( $button['text'] ) || empty( $button['url'] ) ) continue;
        $secondary = isset( $button['style'] ) && 'secondary' === $button['style'];
        $class     = $dark ? ( $secondary ? 'btn btn-ghost' : 'btn btn-primary' ) : ( $secondary ? 'btn btn-outline' : 'btn btn-green' );
        $new       = isset( $button['target'] ) && 'new' === $button['target'];
        $html     .= '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $button['url'] ) . '"' . ( $new ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $button['text'] ) . '</a>';
    }
    return $html . '</div>';
}

/** Páginas del sitio para elegir el destino de un botón sin escribir direcciones. */
function gep_demo_section_link_pages() {
    return array(
        array( 'label' => 'Inicio',          'url' => 'index.php' ),
        array( 'label' => 'Quiénes somos',   'url' => 'quienes-somos.php' ),
        array( 'label' => 'Docencia',        'url' => 'docencia.php' ),
        array( 'label' => 'Proyectos',       'url' => 'proyectos.php' ),
        array( 'label' => 'Servicios',       'url' => 'servicios.php' ),
        array( 'label' => 'Publicaciones',   'url' => 'publicaciones.php' ),
        array( 'label' => 'Comunicaciones',  'url' => 'comunicaciones.php' ),
        array( 'label' => 'Noticias',        'url' => 'noticias.php' ),
        array( 'label' => 'Contacto',        'url' => 'contactanos.php' ),
    );
}

function gep_demo_clean_custom_sections( $raw_sections ) {
    $catalog = gep_demo_section_catalog();
    $raw_sections = is_array( $raw_sections ) ? $raw_sections : array();
    $clean = array();
    foreach ( $raw_sections as $id => $section ) {
        if ( ! is_array( $section ) ) continue;
        $clean_id = sanitize_key( $id );
        $type     = isset( $section['type'] ) ? sanitize_key( $section['type'] ) : 'text';
        if ( '' === $clean_id || 0 !== strpos( $clean_id, 'custom_' ) || ! isset( $catalog[ $type ] ) ) continue;
        $clean[ $clean_id ] = array(
            'id'         => $clean_id,
            'type'       => $type,
            'eyebrow'    => sanitize_text_field( isset( $section['eyebrow'] ) ? $section['eyebrow'] : '' ),
            'title'      => sanitize_text_field( isset( $section['title'] ) ? $section['title'] : '' ),
            'text'       => sanitize_textarea_field( isset( $section['text'] ) ? $section['text'] : '' ),
            'image'      => esc_url_raw( isset( $section['image'] ) ? $section['image'] : '' ),
            'image_alt'  => sanitize_text_field( isset( $section['image_alt'] ) ? $section['image_alt'] : '' ),
            'image_side' => isset( $section['image_side'] ) && 'left' === $section['image_side'] ? 'left' : 'right',
            'video_url'  => esc_url_raw( isset( $section['video_url'] ) ? $section['video_url'] : '' ),
            'button'     => sanitize_text_field( isset( $section['button'] ) ? $section['button'] : '' ),
            'url'        => esc_url_raw( isset( $section['url'] ) ? $section['url'] : '' ),
            'items'      => gep_demo_clean_custom_section_items( isset( $section['items'] ) ? $section['items'] : array(), $type ),
            'buttons'    => gep_demo_clean_custom_section_buttons( isset( $section['buttons'] ) ? $section['buttons'] : array() ),
        );
        // Compatibilidad: el primer botón también queda en "button"/"url".
        if ( ! empty( $clean[ $clean_id ]['buttons'] ) ) {
            $clean[ $clean_id ]['button'] = $clean[ $clean_id ]['buttons'][0]['text'];
            $clean[ $clean_id ]['url']    = $clean[ $clean_id ]['buttons'][0]['url'];
        } elseif ( isset( $section['buttons'] ) && is_array( $section['buttons'] ) ) {
            // La lista existe y quedó vacía: se quitaron los botones a propósito.
            $clean[ $clean_id ]['button'] = '';
            $clean[ $clean_id ]['url']    = '';
        }
    }
    return $clean;
}

function gep_demo_clean_page_structure( $raw_layout, $context, $customs ) {
    $context     = gep_demo_section_context_key( $context );
    $definitions = gep_demo_section_definitions();
    $rows        = is_array( $raw_layout ) ? $raw_layout : array();
    $clean       = array();
    $seen        = array();
    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) continue;
        $id   = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
        $kind = isset( $row['kind'] ) && 'custom' === $row['kind'] ? 'custom' : 'builtin';
        if ( '' === $id || isset( $seen[ $id ] ) ) continue;
        if ( 'builtin' === $kind && empty( $definitions[ $context ][ $id ] ) ) continue;
        if ( 'custom' === $kind && empty( $customs[ $id ] ) ) continue;
        $clean[] = array(
            'id'      => $id,
            'kind'    => $kind,
            'enabled' => ! empty( $row['enabled'] ),
        );
        $seen[ $id ] = true;
    }
    if ( ! empty( $definitions[ $context ] ) ) {
        foreach ( $definitions[ $context ] as $id => $definition ) {
            if ( isset( $seen[ $id ] ) ) continue;
            $clean[] = array( 'id' => $id, 'kind' => 'builtin', 'enabled' => true );
            $seen[ $id ] = true;
        }
    }
    foreach ( $customs as $id => $section ) {
        if ( isset( $seen[ $id ] ) ) continue;
        $clean[] = array( 'id' => $id, 'kind' => 'custom', 'enabled' => true );
        $seen[ $id ] = true;
    }
    $clean = gep_demo_section_apply_pins( $clean, $context );
    return $clean;
}

function gep_demo_save_page_structure( $post_id, $context, $source ) {
    $context = gep_demo_section_context_key( $context );
    if ( ! isset( $source['gep_page_structure_json'] ) && ! isset( $source['gep_custom_sections_json'] ) ) {
        return;
    }

    $custom_json = isset( $source['gep_custom_sections_json'] ) ? wp_unslash( $source['gep_custom_sections_json'] ) : '{}';
    $custom_raw  = json_decode( $custom_json, true );
    $customs     = gep_demo_clean_custom_sections( is_array( $custom_raw ) ? $custom_raw : array() );

    $layout_json = isset( $source['gep_page_structure_json'] ) ? wp_unslash( $source['gep_page_structure_json'] ) : '[]';
    $layout_raw  = json_decode( $layout_json, true );
    $layout      = gep_demo_clean_page_structure( is_array( $layout_raw ) ? $layout_raw : array(), $context, $customs );

    update_post_meta( $post_id, gep_demo_section_custom_meta_key( $context ), wp_slash( wp_json_encode( $customs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
    update_post_meta( $post_id, gep_demo_section_structure_meta_key( $context ), wp_slash( wp_json_encode( $layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
}

function gep_demo_section_context_for_post( $post_id, $navigation = null ) {
    if ( null === $navigation && function_exists( 'gep_demo_get_page_navigation' ) ) {
        $navigation = gep_demo_get_page_navigation();
    }
    if ( function_exists( 'gep_demo_is_home_page' ) && gep_demo_is_home_page( $post_id, $navigation ) ) return 'home';
    if ( function_exists( 'gep_demo_is_hub_page' ) && gep_demo_is_hub_page( $post_id, 'communications', $navigation ) ) return 'communications';
    if ( function_exists( 'gep_demo_is_hub_page' ) && gep_demo_is_hub_page( $post_id, 'news', $navigation ) ) return 'news';
    if ( function_exists( 'gep_demo_is_services_page' ) && gep_demo_is_services_page( $post_id, $navigation ) ) return 'services';
    if ( function_exists( 'gep_demo_is_publications_page' ) && gep_demo_is_publications_page( $post_id, $navigation ) ) return 'publications';
    if ( function_exists( 'gep_demo_is_projects_page' ) && gep_demo_is_projects_page( $post_id, $navigation ) ) return 'projects';
    if ( function_exists( 'gep_demo_is_docencia_page' ) && gep_demo_is_docencia_page( $post_id, $navigation ) ) return 'docencia';
    if ( function_exists( 'gep_demo_is_contact_page' ) && gep_demo_is_contact_page( $post_id, $navigation ) ) return 'contact';
    if ( function_exists( 'gep_demo_is_directory_page' ) && gep_demo_is_directory_page( $post_id, $navigation ) ) return 'directory';
    return '';
}

function gep_demo_section_admin_payload( $post_id ) {
    $navigation = function_exists( 'gep_demo_get_page_navigation' ) ? gep_demo_get_page_navigation() : array();
    $context    = gep_demo_section_context_for_post( $post_id, $navigation );
    $defs       = gep_demo_section_definitions();
    if ( '' === $context || empty( $defs[ $context ] ) ) return array();
    return array(
        'context'     => $context,
        'layout'      => gep_demo_get_page_structure( $post_id, $context ),
        'builtins'    => $defs[ $context ],
        'customs'     => gep_demo_get_custom_sections( $post_id, $context ),
        'catalog'     => gep_demo_section_catalog(),
        'pages'       => gep_demo_section_link_pages(),
        'strings'     => array(
            'deleteConfirm' => '¿Eliminar este apartado de la página? Sus datos se conservarán y podrás restaurarlo antes o después de guardar.',
        ),
    );
}

function gep_demo_section_public_context_from_script() {
    $script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
    $map = array(
        'index.php'           => 'home',
        'quienes-somos.php'   => 'directory',
        'contactanos.php'     => 'contact',
        'contacto.php'        => 'contact',
        'docencia.php'        => 'docencia',
        'proyectos.php'       => 'projects',
        'publicaciones.php'   => 'publications',
        'comunicaciones.php'  => 'communications',
        'noticias.php'        => 'news',
        'servicios.php'       => 'services',
    );
    return isset( $map[ $script ] ) ? $map[ $script ] : '';
}

function gep_demo_section_post_id_for_context( $context ) {
    if ( ! function_exists( 'gep_demo_get_page_navigation' ) ) return 0;
    $navigation = gep_demo_get_page_navigation();
    $key_map = array(
        'home'           => 'inicio',
        'directory'      => 'quienes-somos',
        'contact'        => 'contacto',
        'docencia'       => 'docencia',
        'projects'       => 'proyectos',
        'publications'   => 'publicaciones',
        'communications' => 'comunicaciones',
        'news'           => 'noticias',
        'services'       => 'servicios',
    );
    $nav_key = isset( $key_map[ $context ] ) ? $key_map[ $context ] : '';
    return $nav_key && ! empty( $navigation[ $nav_key ]['page']->ID ) ? (int) $navigation[ $nav_key ]['page']->ID : 0;
}

function gep_demo_section_video_embed_html( $url, $title ) {
    $url = esc_url( $url );
    if ( ! $url ) return '';
    $host = wp_parse_url( $url, PHP_URL_HOST );
    $host = strtolower( (string) $host );
    $embed = '';
    if ( false !== strpos( $host, 'youtube.com' ) || false !== strpos( $host, 'youtu.be' ) ) {
        $id = '';
        if ( false !== strpos( $host, 'youtu.be' ) ) {
            $id = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
        } else {
            parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
            if ( ! empty( $query['v'] ) ) $id = $query['v'];
            if ( ! $id && preg_match( '#/(?:embed|shorts)/([A-Za-z0-9_-]+)#', $url, $m ) ) $id = $m[1];
        }
        $id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $id );
        if ( $id ) $embed = 'https://www.youtube-nocookie.com/embed/' . $id;
    } elseif ( false !== strpos( $host, 'vimeo.com' ) && preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $url, $m ) ) {
        $embed = 'https://player.vimeo.com/video/' . $m[1];
    }
    if ( $embed ) {
        return '<div class="gep-custom-video-frame"><iframe src="' . esc_url( $embed ) . '" title="' . esc_attr( $title ? $title : 'Video' ) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
    }
    $path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
    if ( preg_match( '/\.(mp4|m4v|webm|ogv|ogg|mov)$/', $path ) ) {
        return '<div class="gep-custom-video-frame"><video controls preload="metadata" src="' . esc_url( $url ) . '"></video></div>';
    }
    return '<div class="gep-custom-video-link"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Abrir video ↗</a></div>';
}

function gep_demo_render_custom_section_html( $section ) {
    if ( ! is_array( $section ) ) return '';
    $id      = isset( $section['id'] ) ? sanitize_key( $section['id'] ) : '';
    $type    = isset( $section['type'] ) ? sanitize_key( $section['type'] ) : 'text';
    $eyebrow = isset( $section['eyebrow'] ) ? $section['eyebrow'] : '';
    $title   = isset( $section['title'] ) ? $section['title'] : '';
    $text    = isset( $section['text'] ) ? $section['text'] : '';
    if ( ! $id ) return '';
    // v9.25.0: título y descripción opcionales; sin textos no se deja espacio vacío.
    $has_head = ( '' !== trim( (string) $eyebrow ) || '' !== trim( (string) $title ) || '' !== trim( (string) $text ) );
    $buttons_light = gep_demo_render_custom_section_buttons( $section, false );

    ob_start();
    if ( 'stats' === $type ) :
        $items = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();
        ?>
        <section class="gep-custom-public-section gep-custom-stats" data-gep-custom-section="<?php echo esc_attr( $id ); ?>">
            <div class="container">
                <?php if ( $eyebrow || $title ) : ?><div class="gep-custom-head"><?php if ( $eyebrow ) : ?><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?><?php if ( $title ) : ?><h2><?php echo esc_html( $title ); ?></h2><?php endif; ?></div><?php endif; ?>
                <div class="gep-custom-stats-grid"><?php foreach ( $items as $item ) : ?><article><strong><?php echo esc_html( isset( $item['value'] ) ? $item['value'] : '' ); ?></strong><span><?php echo esc_html( isset( $item['label'] ) ? $item['label'] : '' ); ?></span><?php if ( ! empty( $item['text'] ) ) : ?><small><?php echo esc_html( $item['text'] ); ?></small><?php endif; ?></article><?php endforeach; ?></div>
                <?php echo $buttons_light; // phpcs:ignore WordPress.Security.EscapeOutput -- escapado en gep_demo_render_custom_section_buttons(). ?>
            </div>
        </section>
        <?php
    elseif ( 'cards' === $type ) :
        $items = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();
        ?>
        <section class="section gep-custom-public-section gep-custom-cards" data-gep-custom-section="<?php echo esc_attr( $id ); ?>"><div class="container">
            <?php if ( $has_head ) : ?><div class="gep-custom-head"><?php if ( $eyebrow ) : ?><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?><?php if ( $title ) : ?><h2><?php echo esc_html( $title ); ?></h2><?php endif; ?><?php if ( $text ) : ?><p><?php echo nl2br( esc_html( $text ) ); ?></p><?php endif; ?></div><?php endif; ?>
            <div class="gep-custom-card-grid"><?php foreach ( $items as $item ) : ?><article><?php if ( ! empty( $item['image'] ) ) : ?><img src="<?php echo esc_url( $item['image'] ); ?>" alt="" loading="lazy"><?php endif; ?><div><?php if ( ! empty( $item['title'] ) ) : ?><h3><?php echo esc_html( $item['title'] ); ?></h3><?php endif; ?><?php if ( ! empty( $item['text'] ) ) : ?><p><?php echo nl2br( esc_html( $item['text'] ) ); ?></p><?php endif; ?><?php if ( ! empty( $item['url'] ) ) : ?><a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo 0 === strpos( $item['url'], 'http' ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( ! empty( $item['button'] ) ? $item['button'] : 'Ver más →' ); ?></a><?php endif; ?></div></article><?php endforeach; ?></div>
            <?php echo $buttons_light; // phpcs:ignore WordPress.Security.EscapeOutput ?>
        </div></section>
        <?php
    elseif ( 'image_text' === $type ) :
        $image_side = isset( $section['image_side'] ) && 'left' === $section['image_side'] ? ' is-image-left' : '';
        ?>
        <section class="section gep-custom-public-section gep-custom-image-text<?php echo esc_attr( $image_side ); ?>" data-gep-custom-section="<?php echo esc_attr( $id ); ?>"><div class="container gep-custom-split">
            <div class="gep-custom-copy"><?php if ( $eyebrow ) : ?><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?><?php if ( $title ) : ?><h2><?php echo esc_html( $title ); ?></h2><?php endif; ?><?php if ( $text ) : ?><p><?php echo nl2br( esc_html( $text ) ); ?></p><?php endif; ?><?php echo $buttons_light; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
            <?php if ( ! empty( $section['image'] ) ) : ?><figure><img src="<?php echo esc_url( $section['image'] ); ?>" alt="<?php echo esc_attr( isset( $section['image_alt'] ) ? $section['image_alt'] : '' ); ?>" loading="lazy"></figure><?php endif; ?>
        </div></section>
        <?php
    elseif ( 'video' === $type ) :
        ?>
        <section class="section gep-custom-public-section gep-custom-video" data-gep-custom-section="<?php echo esc_attr( $id ); ?>"><div class="container"><?php if ( $has_head ) : ?><div class="gep-custom-head"><?php if ( $eyebrow ) : ?><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?><?php if ( $title ) : ?><h2><?php echo esc_html( $title ); ?></h2><?php endif; ?><?php if ( $text ) : ?><p><?php echo nl2br( esc_html( $text ) ); ?></p><?php endif; ?></div><?php endif; ?><?php echo gep_demo_section_video_embed_html( isset( $section['video_url'] ) ? $section['video_url'] : '', $title ); ?><?php echo $buttons_light; // phpcs:ignore WordPress.Security.EscapeOutput ?></div></section>
        <?php
    elseif ( 'cta' === $type ) :
        ?>
        <section class="cta-band gep-custom-public-section gep-custom-cta" data-gep-custom-section="<?php echo esc_attr( $id ); ?>"><div class="container gep-custom-cta-inner"><div><?php if ( $eyebrow ) : ?><p class="eyebrow light"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?><?php if ( $title ) : ?><h2><?php echo esc_html( $title ); ?></h2><?php endif; ?><?php if ( $text ) : ?><p><?php echo nl2br( esc_html( $text ) ); ?></p><?php endif; ?></div><?php echo gep_demo_render_custom_section_buttons( $section, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div></section>
        <?php
    else :
        ?>
        <section class="section gep-custom-public-section gep-custom-text" data-gep-custom-section="<?php echo esc_attr( $id ); ?>"><div class="container"><div class="gep-custom-text-inner"><?php if ( $eyebrow ) : ?><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?><?php if ( $title ) : ?><h2><?php echo esc_html( $title ); ?></h2><?php endif; ?><?php if ( $text ) : ?><p><?php echo nl2br( esc_html( $text ) ); ?></p><?php endif; ?><?php echo $buttons_light; // phpcs:ignore WordPress.Security.EscapeOutput ?></div></div></section>
        <?php
    endif;
    return trim( ob_get_clean() );
}

function gep_demo_render_page_structure_bridge() {
    if ( is_admin() ) return;
    $context = gep_demo_section_public_context_from_script();
    if ( ! $context ) return;
    $post_id = gep_demo_section_post_id_for_context( $context );
    if ( ! $post_id ) return;
    $layout  = gep_demo_get_page_structure( $post_id, $context );
    $customs = gep_demo_get_custom_sections( $post_id, $context );
    $html    = array();
    foreach ( $customs as $id => $section ) {
        $html[ $id ] = gep_demo_render_custom_section_html( $section );
    }
    // v9.24.0: el Buscador de Publicaciones vive dentro de la sección pública de
    // registros; si se oculta en el editor se quitan sus controles y su acceso.
    $remove = array();
    foreach ( $layout as $row ) {
        if ( 'publications' === $context && 'search' === $row['id'] && 'builtin' === $row['kind'] && empty( $row['enabled'] ) ) {
            $remove[] = '.publication-tools';
            $remove[] = '.section-nav a[href="#publicaciones"]';
        }
    }
    $payload = array( 'context' => $context, 'layout' => $layout, 'customHtml' => $html, 'remove' => $remove );
    ?>
    <style id="gep-page-structure-css">
    .gep-custom-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:22px}.gep-custom-cta-inner .gep-custom-actions{margin-top:0;flex:0 0 auto}.gep-custom-head:empty{display:none}
    .gep-custom-public-section{position:relative}.gep-custom-head{max-width:850px;margin-bottom:26px}.gep-custom-head h2,.gep-custom-text-inner h2,.gep-custom-copy h2{margin:8px 0 14px}.gep-custom-head>p:not(.eyebrow),.gep-custom-text-inner>p:not(.eyebrow),.gep-custom-copy>p:not(.eyebrow){max-width:780px;line-height:1.75}.gep-custom-text-inner{max-width:900px}.gep-custom-split{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.9fr);gap:48px;align-items:center}.gep-custom-split figure{margin:0;min-height:260px}.gep-custom-split img{display:block;width:100%;height:100%;max-height:520px;object-fit:cover;border-radius:18px}.gep-custom-image-text.is-image-left .gep-custom-copy{order:2}.gep-custom-image-text.is-image-left figure{order:1}.gep-custom-card-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.gep-custom-card-grid article{overflow:hidden;border:1px solid rgba(31,90,59,.12);border-radius:16px;background:#fff}.gep-custom-card-grid img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover}.gep-custom-card-grid article>div{padding:20px}.gep-custom-card-grid h3{margin:0 0 9px}.gep-custom-card-grid p{margin:0 0 14px;line-height:1.6}.gep-custom-card-grid a{font-weight:700;text-decoration:none;color:#1f5a3b}.gep-custom-stats{background:#1f5a3b;color:#fff;padding:42px 0}.gep-custom-stats .eyebrow,.gep-custom-stats h2{color:#fff}.gep-custom-stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1px;background:rgba(255,255,255,.16)}.gep-custom-stats-grid article{padding:22px;background:#1f5a3b}.gep-custom-stats-grid strong,.gep-custom-stats-grid span,.gep-custom-stats-grid small{display:block}.gep-custom-stats-grid strong{font-size:30px}.gep-custom-stats-grid span{margin-top:5px;font-weight:700}.gep-custom-stats-grid small{margin-top:6px;opacity:.76}.gep-custom-video-frame{position:relative;aspect-ratio:16/9;overflow:hidden;border-radius:18px;background:#0d1711}.gep-custom-video-frame iframe,.gep-custom-video-frame video{position:absolute;inset:0;width:100%;height:100%;border:0}.gep-custom-video-link a{font-weight:800;color:#1f5a3b}.gep-custom-cta-inner{display:flex;gap:32px;align-items:center;justify-content:space-between}.gep-custom-cta-inner>div{max-width:800px}.gep-custom-cta-inner p:not(.eyebrow){color:inherit;opacity:.85}.gep-custom-cta-inner .btn{white-space:nowrap}
    @media(max-width:900px){.gep-custom-split{grid-template-columns:1fr;gap:26px}.gep-custom-image-text.is-image-left .gep-custom-copy,.gep-custom-image-text.is-image-left figure{order:initial}.gep-custom-card-grid{grid-template-columns:1fr 1fr}.gep-custom-stats-grid{grid-template-columns:1fr 1fr}.gep-custom-cta-inner{align-items:flex-start;flex-direction:column}}
    @media(max-width:620px){.gep-custom-card-grid,.gep-custom-stats-grid{grid-template-columns:1fr}.gep-custom-split figure{min-height:0}}
    </style>
    <script id="gep-page-structure-js">
    (function(){
      var data=<?php echo wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
      function apply(){
        var main=document.querySelector('main');
        if(!main||!data||!Array.isArray(data.layout))return;
        var groups={};
        data.layout.forEach(function(row){
          if(!row||!row.id)return;
          if(row.kind==='custom'){
            if(row.enabled && data.customHtml && data.customHtml[row.id]){
              var tpl=document.createElement('template'); tpl.innerHTML=data.customHtml[row.id].trim();
              groups[row.id]=Array.prototype.slice.call(tpl.content.children);
            }else groups[row.id]=[];
          }else{
            groups[row.id]=Array.prototype.slice.call(main.querySelectorAll('[data-gep-section="'+row.id.replace(/"/g,'')+'"]'));
          }
        });
        (data.remove||[]).forEach(function(sel){Array.prototype.forEach.call(main.querySelectorAll(sel),function(node){if(node.parentNode)node.parentNode.removeChild(node);});});
        data.layout.forEach(function(row){
          var nodes=groups[row.id]||[];
          if(!row.enabled){nodes.forEach(function(node){if(node&&node.parentNode)node.parentNode.removeChild(node);});return;}
          nodes.forEach(function(node){main.appendChild(node);});
        });
      }
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',apply);else apply();
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'gep_demo_render_page_structure_bridge', 8 );
