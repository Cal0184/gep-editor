<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function gep_demo_ensure_new_content_pages() {
    if ( ! is_admin() || ! current_user_can( 'edit_pages' ) ) return;
    $pages = array(
        'comunicaciones' => 'Comunicaciones',
        'noticias'       => 'Noticias',
    );
    foreach ( $pages as $slug => $title ) {
        if ( get_page_by_path( $slug, OBJECT, 'page' ) ) continue;
        wp_insert_post( array(
            'post_type'   => 'page',
            'post_status' => 'draft',
            'post_title'  => $title,
            'post_name'   => $slug,
        ) );
    }
}
// v9.16 Beta: no crear páginas automáticamente. Se conservan las funciones para una instalación asistida futura.
// add_action( 'admin_init', 'gep_demo_ensure_new_content_pages', 5 );

function gep_demo_hub_types( $context ) {
    return 'communications' === $context
        ? array( 'prensa' => 'Prensa', 'eventos' => 'Eventos', 'videos' => 'Videos' )
        : array( 'entrevistas' => 'Entrevistas', 'noticias' => 'Noticias' );
}

function gep_demo_hub_defaults( $context ) {
    $is_comms = 'communications' === $context;
    return array(
        'hero_eyebrow' => $is_comms ? 'Difusión' : 'Actualidad',
        'hero_title'   => $is_comms ? 'Comunicaciones' : 'Noticias',
        'hero_text'    => $is_comms
            ? 'Prensa, eventos y videos vinculados al trabajo del laboratorio.'
            : 'Entrevistas y noticias relacionadas con el GEP y su trabajo.',
        'hero_image'   => '',
        'hero_video'   => '',
        'records'      => array(),
    );
}

/**
 * Contenido inicial real para Comunicaciones y Noticias.
 * Las entradas se basan en publicaciones oficiales de MapBiomas Chile y
 * Universidad de Chile. Se pueden editar o eliminar libremente desde el editor.
 */
function gep_demo_hub_starter_records( $context ) {
    if ( 'communications' === $context ) {
        return array(
            array(
                'type' => 'prensa',
                'title' => 'MapBiomas Chile: innovadora plataforma es pionera en mostrar datos sobre la cobertura y uso del suelo',
                'date' => '7 abril 2025',
                'source' => 'Universidad de Chile',
                'place' => '',
                'image' => '',
                'image_alt' => '',
                'description' => 'Reportaje de la Universidad de Chile sobre MapBiomas Chile, sus datos abiertos y el trabajo colaborativo entre la U. de Chile, la U. de Concepción y la U. de La Frontera. Incluye declaraciones de Jaime Hernández, director del GEP y coordinador general de MapBiomas Chile.',
                'url' => 'https://uchile.cl/noticias/226936/mapbiomas-chile-plataforma-es-pionera-en-mostrar-datos-sobre-el-suelo',
                'button' => 'Leer reportaje ↗',
            ),
            array(
                'type' => 'prensa',
                'title' => 'MapBiomas Chile: en la zona Centro-Sur del país se perdieron 500 mil hectáreas de bosque nativo en dos décadas',
                'date' => '23 abril 2024',
                'source' => 'MapBiomas Chile',
                'place' => '',
                'image' => '',
                'image_alt' => '',
                'description' => 'Publicación de MapBiomas Chile basada en la primera colección de mapas anuales de cobertura y uso del suelo. El análisis describe cambios territoriales entre 2000 y 2022 y destaca la pérdida de bosque nativo en la zona centro-sur.',
                'url' => 'https://chile.mapbiomas.org/2024/04/23/mapbiomas-chile-en-la-zona-centro-sur-del-pais-se-perdieron-500-mil-hectareas-de-bosque-nativo-en-dos-decadas/',
                'button' => 'Leer publicación ↗',
            ),
            array(
                'type' => 'eventos',
                'title' => 'Lanzamiento de la Colección 2 de MapBiomas Chile',
                'date' => '13 octubre 2025',
                'source' => 'MapBiomas Chile',
                'place' => 'Chile',
                'image' => '',
                'image_alt' => '',
                'description' => 'Presentación de la segunda colección de mapas anuales de cobertura y uso del suelo de Chile para el período 1999–2024, desarrollada en colaboración por GEP, LEP y LEPCON.',
                'url' => 'https://chile.mapbiomas.org/2025/10/13/lanzamiento-de-la-coleccion-2-de-mapbiomas-chile/',
                'button' => 'Ver evento ↗',
            ),
            array(
                'type' => 'videos',
                'title' => 'Lanzamiento MapBiomas Chile — Colección 2',
                'date' => '13 octubre 2025',
                'source' => 'YouTube · MapBiomas Chile',
                'place' => '',
                'image' => '',
                'image_alt' => '',
                'description' => 'Registro audiovisual del lanzamiento de la Colección 2 de MapBiomas Chile y de sus mapas anuales de cobertura y uso del suelo.',
                'url' => 'https://youtube.com/live/fl3C2b1owk4?feature=share',
                'button' => 'Ver video ↗',
            ),
            array(
                'type' => 'videos',
                'title' => 'MapBiomas Chile: Lanzamiento Colección 1',
                'date' => '23 abril 2024',
                'source' => 'YouTube · MapBiomas Chile',
                'place' => '',
                'image' => '',
                'image_alt' => '',
                'description' => 'Registro del lanzamiento de la primera colección nacional de mapas anuales de cobertura y uso del suelo de MapBiomas Chile.',
                'url' => 'https://www.youtube.com/watch?v=9RxTgB9rrNU',
                'button' => 'Ver video ↗',
            ),
        );
    }

    return array(
        array(
            'type' => 'noticias',
            'title' => 'MapBiomas Agua Chile · Primera Colección Nacional de Glaciares (1998–2025)',
            'date' => '16 julio 2026',
            'source' => 'MapBiomas Chile',
            'place' => '',
            'image' => '',
            'image_alt' => '',
            'description' => 'MapBiomas Chile presentó información nacional sobre la evolución de glaciares a partir de una serie temporal construida con imágenes satelitales entre 1998 y 2025.',
            'url' => 'https://chile.mapbiomas.org/2026/07/16/mapbiomas-agua-chile-%C2%B7-primera-coleccion-nacional-de-glaciares-1998-2025/',
            'button' => 'Leer noticia ↗',
        ),
        array(
            'type' => 'noticias',
            'title' => 'Colección 2 de MapBiomas Chile: mapas anuales de cobertura y uso del suelo 1999–2024',
            'date' => '13 octubre 2025',
            'source' => 'MapBiomas Chile',
            'place' => '',
            'image' => '',
            'image_alt' => '',
            'description' => 'La segunda colección actualiza el monitoreo anual de la cobertura y uso del suelo de Chile para el período 1999–2024 y consolida el trabajo colaborativo entre GEP, LEP y LEPCON.',
            'url' => 'https://chile.mapbiomas.org/2025/10/13/lanzamiento-de-la-coleccion-2-de-mapbiomas-chile/',
            'button' => 'Conocer la Colección 2 ↗',
        ),
        array(
            'type' => 'entrevistas',
            'title' => 'MapBiomas Chile: datos abiertos para comprender la transformación del territorio',
            'date' => '7 abril 2025',
            'source' => 'Universidad de Chile',
            'place' => '',
            'image' => '',
            'image_alt' => '',
            'description' => 'Reportaje de la Universidad de Chile con declaraciones de Jaime Hernández y otros integrantes de la iniciativa sobre el valor de contar con información territorial abierta para investigación y toma de decisiones.',
            'url' => 'https://uchile.cl/noticias/226936/mapbiomas-chile-plataforma-es-pionera-en-mostrar-datos-sobre-el-suelo',
            'button' => 'Leer entrevista ↗',
        ),
        array(
            'type' => 'noticias',
            'title' => 'MapBiomas Chile registra una pérdida de 500 mil hectáreas de bosque nativo en la zona Centro-Sur',
            'date' => '23 abril 2024',
            'source' => 'MapBiomas Chile',
            'place' => '',
            'image' => '',
            'image_alt' => '',
            'description' => 'Resultados de la primera colección mostraron cambios importantes en el paisaje chileno entre 2000 y 2022, incluyendo una disminución significativa del bosque nativo en la zona centro-sur.',
            'url' => 'https://chile.mapbiomas.org/2024/04/23/mapbiomas-chile-en-la-zona-centro-sur-del-pais-se-perdieron-500-mil-hectareas-de-bosque-nativo-en-dos-decadas/',
            'button' => 'Leer noticia ↗',
        ),
    );
}

/**
 * Rellena las nuevas secciones con contenido inicial únicamente cuando aún no
 * existen registros guardados. Nunca sobreescribe contenido del usuario.
 */
function gep_demo_seed_hub_content_v982() {
    if ( ! is_admin() || ! current_user_can( 'edit_pages' ) ) return;

    $targets = array(
        'communications' => array( 'slug' => 'comunicaciones', 'prefix' => 'gep_communications_' ),
        'news'           => array( 'slug' => 'noticias',       'prefix' => 'gep_news_' ),
    );

    foreach ( $targets as $context => $target ) {
        $page = get_page_by_path( $target['slug'], OBJECT, 'page' );
        if ( ! $page || empty( $page->ID ) ) continue;

        $meta_key = $target['prefix'] . 'records_json';
        if ( metadata_exists( 'post', $page->ID, $meta_key ) ) continue;

        $records = gep_demo_clean_hub_records( gep_demo_hub_starter_records( $context ), $context );
        update_post_meta( $page->ID, $meta_key, wp_slash( wp_json_encode( $records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
    }
}
// v9.16 Beta: Noticias/Comunicaciones no recuperan contenido borrado por su cuenta.
// add_action( 'admin_init', 'gep_demo_seed_hub_content_v982', 8 );

function gep_demo_hub_values( $post_id, $context ) {
    $values = gep_demo_hub_defaults( $context );
    $prefix = 'communications' === $context ? 'gep_communications_' : 'gep_news_';
    foreach ( array( 'hero_eyebrow', 'hero_title', 'hero_text', 'hero_image', 'hero_video' ) as $key ) {
        if ( metadata_exists( 'post', $post_id, $prefix . $key ) ) {
            $values[ $key ] = get_post_meta( $post_id, $prefix . $key, true );
        }
    }
    // v9.20.0: sin registros guardados, la página muestra los mismos registros
    // iniciales que ya se veían en la preview del editor.
    if ( ! metadata_exists( 'post', $post_id, $prefix . 'records_json' ) ) {
        $values['records'] = gep_demo_clean_hub_records( gep_demo_hub_starter_records( $context ), $context );
        return $values;
    }
    $json = get_post_meta( $post_id, $prefix . 'records_json', true );
    if ( '' !== $json ) {
        $records = json_decode( $json, true );
        if ( is_array( $records ) ) $values['records'] = $records;
    }
    return $values;
}

function gep_demo_is_hub_page( $post_id, $context, $navigation = null ) {
    if ( ! $post_id ) return false;
    if ( null === $navigation ) $navigation = gep_demo_get_page_navigation();
    $nav_key = 'communications' === $context ? 'comunicaciones' : 'noticias';
    if ( ! empty( $navigation[ $nav_key ]['page'] ) && (int) $navigation[ $nav_key ]['page']->ID === (int) $post_id ) return true;
    $post = get_post( $post_id );
    return $post && $nav_key === gep_demo_normalize_page_name( $post->post_name );
}

function gep_demo_hub_public_url( $post_id, $context ) {
    $filename = 'communications' === $context ? 'comunicaciones.php' : 'noticias.php';
    return function_exists( 'gep_editor_public_page_url' )
        ? gep_editor_public_page_url( $filename, $post_id )
        : ( $post_id ? get_permalink( $post_id ) : home_url( '/' ) );
}

/**
 * v9.23.0: fecha de un registro como marca de tiempo. Acepta 2026-10-27
 * (calendario del editor), 27/10/2026 y textos antiguos como "7 abril 2025".
 */
function gep_demo_hub_date_ts( $value ) {
    $raw = trim( (string) $value );
    if ( '' === $raw ) return false;
    if ( preg_match( '/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/', $raw, $m ) ) return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? gmmktime( 12, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1] ) : false;
    if ( preg_match( '/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})/', $raw, $m ) ) return checkdate( (int) $m[2], (int) $m[1], (int) $m[3] ) ? gmmktime( 12, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3] ) : false;
    $s = remove_accents( function_exists( 'mb_strtolower' ) ? mb_strtolower( $raw, 'UTF-8' ) : strtolower( $raw ) );
    $s = str_replace( array( ',', ' de ' ), ' ', $s );
    $months = array( 'enero' => 1, 'ene' => 1, 'febrero' => 2, 'feb' => 2, 'marzo' => 3, 'mar' => 3, 'abril' => 4, 'abr' => 4, 'mayo' => 5, 'may' => 5, 'junio' => 6, 'jun' => 6, 'julio' => 7, 'jul' => 7, 'agosto' => 8, 'ago' => 8, 'septiembre' => 9, 'setiembre' => 9, 'sept' => 9, 'sep' => 9, 'octubre' => 10, 'oct' => 10, 'noviembre' => 11, 'nov' => 11, 'diciembre' => 12, 'dic' => 12 );
    if ( preg_match( '/\b(\d{1,2})\s+([a-z]+)\s+(\d{4})\b/', $s, $m ) && isset( $months[ $m[2] ] ) && checkdate( $months[ $m[2] ], (int) $m[1], (int) $m[3] ) ) return gmmktime( 12, 0, 0, $months[ $m[2] ], (int) $m[1], (int) $m[3] );
    return false;
}

/** Fecha en formato del calendario (2026-10-27), o '' si no se reconoce. */
function gep_demo_hub_date_iso( $value ) {
    $ts = gep_demo_hub_date_ts( $value );
    return false === $ts ? '' : gmdate( 'Y-m-d', $ts );
}

/** Más recientes primero; lo que no tiene fecha queda al final, en su orden. */
function gep_demo_sort_hub_records( $records ) {
    $rows = array();
    foreach ( array_values( (array) $records ) as $i => $r ) {
        $ts     = is_array( $r ) ? gep_demo_hub_date_ts( isset( $r['date'] ) ? $r['date'] : '' ) : false;
        $rows[] = array( false === $ts ? null : $ts, $i, $r );
    }
    usort( $rows, function ( $a, $b ) {
        if ( null === $a[0] && null === $b[0] ) return $a[1] <=> $b[1];
        if ( null === $a[0] ) return 1;
        if ( null === $b[0] ) return -1;
        return ( $b[0] <=> $a[0] ) ?: ( $a[1] <=> $b[1] );
    } );
    return array_map( function ( $x ) { return $x[2]; }, $rows );
}

function gep_demo_clean_hub_records( $raw, $context ) {
    $types = array_keys( gep_demo_hub_types( $context ) );
    $fallback_type = reset( $types );
    $clean = array();
    foreach ( is_array( $raw ) ? $raw : array() as $row ) {
        if ( ! is_array( $row ) ) continue;
        $type = isset( $row['type'] ) ? sanitize_key( $row['type'] ) : $fallback_type;
        if ( ! in_array( $type, $types, true ) ) $type = $fallback_type;
        $item = array(
            'type'        => $type,
            'title'       => isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '',
            'date'        => isset( $row['date'] ) ? sanitize_text_field( $row['date'] ) : '',
            'source'      => isset( $row['source'] ) ? sanitize_text_field( $row['source'] ) : '',
            'place'       => isset( $row['place'] ) ? sanitize_text_field( $row['place'] ) : '',
            'image'       => isset( $row['image'] ) ? esc_url_raw( $row['image'] ) : '',
            'image_alt'   => isset( $row['image_alt'] ) ? sanitize_text_field( $row['image_alt'] ) : '',
            'description' => isset( $row['description'] ) ? sanitize_textarea_field( $row['description'] ) : '',
            'url'         => isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '',
            'button'      => isset( $row['button'] ) ? sanitize_text_field( $row['button'] ) : '',
        );
        if ( '' !== $item['title'] || '' !== $item['description'] || '' !== $item['url'] || '' !== $item['image'] ) $clean[] = $item;
    }
    return $clean;
}

function gep_demo_save_hub_fields( $post_id, $source, $context ) {
    $prefix = 'communications' === $context ? 'gep_communications_' : 'gep_news_';
    foreach ( array( 'hero_eyebrow', 'hero_title' ) as $key ) {
        if ( isset( $source[ $prefix . $key ] ) ) update_post_meta( $post_id, $prefix . $key, sanitize_text_field( wp_unslash( $source[ $prefix . $key ] ) ) );
    }
    if ( isset( $source[ $prefix . 'hero_text' ] ) ) update_post_meta( $post_id, $prefix . 'hero_text', sanitize_textarea_field( wp_unslash( $source[ $prefix . 'hero_text' ] ) ) );
    if ( isset( $source[ $prefix . 'hero_image' ] ) ) update_post_meta( $post_id, $prefix . 'hero_image', esc_url_raw( wp_unslash( $source[ $prefix . 'hero_image' ] ) ) );
    if ( isset( $source[ $prefix . 'hero_video' ] ) ) update_post_meta( $post_id, $prefix . 'hero_video', esc_url_raw( wp_unslash( $source[ $prefix . 'hero_video' ] ) ) );
    $raw = isset( $source[ $prefix . 'records' ] ) && is_array( $source[ $prefix . 'records' ] ) ? wp_unslash( $source[ $prefix . 'records' ] ) : array();
    update_post_meta( $post_id, $prefix . 'records_json', wp_slash( wp_json_encode( gep_demo_sort_hub_records( gep_demo_clean_hub_records( $raw, $context ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
}

function gep_demo_render_hub_row( $context, $key, $row = array() ) {
    $prefix = 'communications' === $context ? 'gep_communications_' : 'gep_news_';
    $types = gep_demo_hub_types( $context );
    $row = is_array( $row ) ? $row : array();
    $type_keys = array_keys( $types );
    $type = isset( $row['type'] ) && isset( $types[ $row['type'] ] ) ? $row['type'] : reset( $type_keys );
    $name = $prefix . 'records[' . $key . ']';
    $title = isset( $row['title'] ) ? $row['title'] : '';
    $image = isset( $row['image'] ) ? $row['image'] : '';
    ?>
    <details class="gep-item-row gep-hub-row" data-hub-key="<?php echo esc_attr( $key ); ?>">
      <summary><span class="gep-grip dashicons dashicons-menu-alt2"></span><span class="gep-item-summary-main"><span class="gep-item-summary-badge"><?php echo esc_html( $types[ $type ] ); ?></span><strong class="gep-item-summary-name"><?php echo esc_html( $title ? $title : 'Nuevo contenido' ); ?></strong></span><span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span></summary>
      <div class="gep-item-content">
        <div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-hub-up">Subir</button><button type="button" class="button button-small gep-hub-down">Bajar</button><button type="button" class="button button-small gep-hub-duplicate"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-hub-remove"><span class="dashicons dashicons-trash"></span> Eliminar</button></div>
        <div class="gep-grid-2"><label>Tipo<select name="<?php echo esc_attr( $name ); ?>[type]" class="gep-hub-type"><?php foreach ( $types as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><?php $gep_date_raw = isset( $row['date'] ) ? (string) $row['date'] : ''; $gep_date_iso = gep_demo_hub_date_iso( $gep_date_raw ); ?><label>Fecha <span class="gep-optional">(ordena la lista: lo más reciente primero)</span><?php if ( '' === $gep_date_raw || '' !== $gep_date_iso ) : ?><input type="date" class="gep-hub-date" name="<?php echo esc_attr( $name ); ?>[date]" value="<?php echo esc_attr( $gep_date_iso ); ?>"><?php else : ?><input type="text" class="gep-hub-date" name="<?php echo esc_attr( $name ); ?>[date]" value="<?php echo esc_attr( $gep_date_raw ); ?>" placeholder="29 septiembre 2026"><?php endif; ?></label></div>
        <label>Título <span class="gep-required-dot">Obligatorio</span><input class="gep-hub-title" type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $title ); ?>"></label>
        <div class="gep-grid-2"><label>Medio / fuente<input type="text" name="<?php echo esc_attr( $name ); ?>[source]" value="<?php echo esc_attr( isset( $row['source'] ) ? $row['source'] : '' ); ?>" placeholder="Universidad de Chile"></label><label class="gep-hub-place-field">Lugar <span class="gep-optional">(solo eventos)</span><input type="text" name="<?php echo esc_attr( $name ); ?>[place]" value="<?php echo esc_attr( isset( $row['place'] ) ? $row['place'] : '' ); ?>" placeholder="Santiago"></label></div>
        <label>Descripción / extracto<textarea name="<?php echo esc_attr( $name ); ?>[description]"><?php echo esc_textarea( isset( $row['description'] ) ? $row['description'] : '' ); ?></textarea></label>
        <div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen / miniatura <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_image_control( $name . '[image]', $image, 'Seleccionar / cambiar imagen' ); ?></div>
        <label>Texto alternativo<input type="text" name="<?php echo esc_attr( $name ); ?>[image_alt]" value="<?php echo esc_attr( isset( $row['image_alt'] ) ? $row['image_alt'] : '' ); ?>"></label>
        <div class="gep-grid-2"><label>Enlace<input type="url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( isset( $row['url'] ) ? $row['url'] : '' ); ?>" placeholder="https://..."></label><label>Texto del enlace<input type="text" name="<?php echo esc_attr( $name ); ?>[button]" value="<?php echo esc_attr( isset( $row['button'] ) ? $row['button'] : '' ); ?>" placeholder="Ver más ↗"></label></div>
      </div>
    </details>
    <?php
}

function gep_demo_render_hub_editor( $post_id, $values, $context ) {
    $is_comms = 'communications' === $context;
    $prefix = $is_comms ? 'gep_communications_' : 'gep_news_';
    $label = $is_comms ? 'Comunicaciones' : 'Noticias';
    $icon = $is_comms ? 'dashicons-megaphone' : 'dashicons-welcome-write-blog';
    $public_url = gep_demo_hub_public_url( $post_id, $context );
    $records = isset( $values['records'] ) && is_array( $values['records'] ) ? $values['records'] : array();
    $records_meta_key = $prefix . 'records_json';
    $using_starters = ! metadata_exists( 'post', $post_id, $records_meta_key );
    if ( $using_starters ) $records = gep_demo_hub_starter_records( $context );
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gep-content-form" class="gep-hub-form" data-hub-context="<?php echo esc_attr( $context ); ?>" novalidate>
      <input type="hidden" name="action" value="gep_demo_save_content"><input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>"><input type="hidden" name="gep_editor_context" value="<?php echo esc_attr( $context ); ?>"><?php wp_nonce_field( 'gep_demo_save_content_' . $post_id, 'gep_demo_admin_nonce' ); ?>
      <div class="gep-contact-commandbar"><div class="gep-contact-commandbar-copy"><span class="gep-contact-command-icon"><span class="dashicons <?php echo esc_attr( $icon ); ?>"></span></span><div><strong>Editor de <?php echo esc_html( $label ); ?></strong><small>Encabezado, contenidos, imágenes, filtros y enlaces desde un solo lugar.</small></div><span class="gep-inline-save-state is-clean gep-hub-inline-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span></div><div class="gep-contact-commandbar-actions"><?php /* v9.25.0: "Ver página real" se quitó de esta barra; el acceso queda en "Sitio público" del menú lateral. */ ?><button type="submit" class="button button-primary gep-save-button" disabled><span class="dashicons dashicons-saved"></span> Guardar cambios</button></div></div>
      <div class="gep-editor-layout gep-editor-layout-hub"><div class="gep-questionnaire">
        <section class="gep-step-card" data-hub-preview="hero"><div class="gep-step-heading"><span class="gep-step-number">1</span><div><h3>Encabezado</h3><p>Presentación pública de <?php echo esc_html( strtolower( $label ) ); ?>.</p></div></div><div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" name="<?php echo esc_attr( $prefix ); ?>hero_eyebrow" value="<?php echo esc_attr( $values['hero_eyebrow'] ); ?>"></div><div class="gep-admin-field"><label>Título</label><input type="text" name="<?php echo esc_attr( $prefix ); ?>hero_title" value="<?php echo esc_attr( $values['hero_title'] ); ?>"></div></div><div class="gep-admin-field"><label>Descripción</label><textarea name="<?php echo esc_attr( $prefix ); ?>hero_text" rows="3"><?php echo esc_textarea( $values['hero_text'] ); ?></textarea></div><div class="gep-admin-field gep-media-field gep-hub-hero-field"><span class="gep-field-label">Imagen de cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_image_control( $prefix . 'hero_image', isset( $values['hero_image'] ) ? $values['hero_image'] : '', 'Seleccionar / cambiar imagen de cabecera' ); ?></div><div class="gep-admin-field gep-media-field gep-hub-hero-field gep-hero-video-field"><span class="gep-field-label">Video de cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_video_control( $prefix . 'hero_video', isset( $values['hero_video'] ) ? $values['hero_video'] : '', 'Seleccionar / cambiar video de cabecera' ); ?></div></section>
        <section class="gep-step-card" data-hub-preview="content"><div class="gep-step-heading"><span class="gep-step-number">2</span><div><h3>Contenido</h3><p><?php echo $is_comms ? 'Prensa, eventos y videos.' : 'Entrevistas y noticias con enlaces.'; ?></p></div></div><?php if ( $using_starters ) : ?><div class="gep-hub-starter-note"><span class="dashicons dashicons-lightbulb"></span><div><strong>Te dejamos contenido real editable.</strong><span>Son registros iniciales basados en fuentes oficiales. Puedes modificarlos o eliminarlos libremente.</span></div></div><?php endif; ?><div class="gep-team-builder-head"><div><strong id="gep-hub-count"><?php echo count( $records ); ?> registros</strong><p class="gep-muted">Arrastra para ordenar o usa Subir/Bajar.</p></div><button type="button" class="button gep-hub-add"><span class="dashicons dashicons-plus-alt2"></span> Añadir contenido</button></div><div class="gep-hub-builder" id="gep-hub-records"><?php foreach ( $records as $k => $row ) gep_demo_render_hub_row( $context, $k, $row ); ?></div><div class="gep-empty-builder <?php echo $records ? 'is-hidden' : ''; ?>" id="gep-hub-empty"><span class="dashicons <?php echo esc_attr( $icon ); ?>"></span><strong>No hay contenidos todavía.</strong><span>Añade el primero cuando quieras.</span></div></section>
        <?php /* v9.26.0: se quitó el botón inferior "Guardar cambios"; se guarda desde la barra superior. */ ?>
      </div>
      <aside class="gep-preview-column">
        <div class="gep-preview-card gep-parent-preview-card" id="gep-hub-preview-shell" data-public-url="<?php echo esc_url( $public_url ); ?>">
          <div class="gep-preview-heading gep-directory-preview-heading"><span class="gep-preview-icon"><span class="dashicons dashicons-visibility"></span></span><div class="gep-preview-heading-copy"><div class="gep-preview-heading-title-row"><span class="gep-preview-zone-badge" id="gep-hub-preview-zone">Zona 1</span><h3 id="gep-hub-preview-heading">Encabezado</h3></div><p class="gep-muted">Preview de <?php echo esc_html( $label ); ?></p></div><div class="gep-preview-device-switcher"><button type="button" class="gep-directory-device-button gep-hub-device-button is-active" data-device="desktop"><span class="dashicons dashicons-desktop"></span><span>PC</span></button><button type="button" class="gep-directory-device-button gep-hub-device-button" data-device="tablet"><span class="dashicons dashicons-tablet"></span><span>Tablet</span></button><button type="button" class="gep-directory-device-button gep-hub-device-button" data-device="mobile"><span class="dashicons dashicons-smartphone"></span><span>Móvil</span></button></div></div>
          <div class="gep-directory-device-stage gep-hub-device-stage"><div class="gep-directory-browser gep-hub-browser" id="gep-hub-live-preview"><div class="gep-directory-live-toolbar"><span></span><span></span><span></span><strong><?php echo esc_html( strtolower( $label ) ); ?>.php</strong><em id="gep-hub-device-label">Vista escritorio</em></div><div class="gep-preview-stage"><div class="gep-hub-live-hero" id="gep-hub-live-hero"><span id="gep-hub-live-eyebrow"><?php echo esc_html( $values['hero_eyebrow'] ); ?></span><h4 id="gep-hub-live-title"><?php echo esc_html( $values['hero_title'] ); ?></h4><p id="gep-hub-live-text"><?php echo esc_html( $values['hero_text'] ); ?></p></div><div class="gep-hub-live-nav" id="gep-hub-live-nav"></div><div class="gep-hub-live-content"><div class="gep-hub-live-section-head"><span><?php echo $is_comms ? 'CONTENIDO MULTIMEDIA' : 'ACTUALIDAD GEP'; ?></span><strong><?php echo $is_comms ? 'Prensa, eventos y videos' : 'Entrevistas y noticias'; ?></strong></div><div class="gep-hub-live-grid" id="gep-hub-preview"></div></div></div></div></div>
        </div>
      </aside></div>
      <script type="text/html" id="gep-hub-row-template"><?php gep_demo_render_hub_row( $context, '__K__', array() ); ?></script>
    </form>
    <script>
    jQuery(function($){
      var $form=$('.gep-hub-form'), counter=1000, isComms=$form.data('hub-context')==='communications'; if(!$form.length)return;
      var types=isComms?['Prensa','Eventos','Videos']:['Entrevistas','Noticias'];
      function field(s){return $.trim($form.find(s).first().val()||'');}
      function resolveImage(url){url=$.trim(url||'');if(!url||/^(?:https?:)?\/\//i.test(url)||/^(?:data:|blob:)/i.test(url)||url.charAt(0)==='/')return url;var base=$('#gep-hub-preview-shell').data('public-url')||'';try{return base?new URL(url,base).href:url;}catch(e){return url;}}
      function setHeroMedia($hero,image,video){video=resolveImage(video||'');var hasVideo=!!video&&!!window.gepPreviewPlaceHeroVideo&&window.gepPreviewPlaceHeroVideo($hero,video);if(!video)$hero.children('.gep-admin-hero-video').remove();$hero.toggleClass('has-preview-video',hasVideo).css('background-image',image?(hasVideo?'linear-gradient(90deg,rgba(6,31,19,.62),rgba(20,71,45,.38)),url("':'linear-gradient(90deg,rgba(6,31,19,.86),rgba(20,71,45,.58)),url("')+image.replace(/"/g,'%22')+'")':'');}
      function fallbackImage(d,index){return'';}
      function setDevice(device){var labels={desktop:'Vista escritorio',tablet:'Vista tablet',mobile:'Vista móvil'},$b=$('#gep-hub-live-preview');$b.removeClass('is-device-tablet is-device-mobile');if(device==='tablet')$b.addClass('is-device-tablet');if(device==='mobile')$b.addClass('is-device-mobile');$('.gep-hub-device-button').removeClass('is-active').filter('[data-device="'+device+'"]').addClass('is-active');$('#gep-hub-device-label').text(labels[device]||labels.desktop);}
      function setZone(zone){var hero=zone==='hero';$('#gep-hub-preview-zone').text(hero?'Zona 1':'Zona 2');$('#gep-hub-preview-heading').text(hero?'Encabezado':'Contenido');$('.gep-hub-form [data-hub-preview]').removeClass('is-preview-source');$('.gep-hub-form [data-hub-preview="'+zone+'"]').addClass('is-preview-source');}
      function rowData($r){return{type:$r.find('.gep-hub-type option:selected').text()||'',typeValue:$r.find('.gep-hub-type').val()||'',title:$.trim($r.find('.gep-hub-title').val()||'')||'Nuevo contenido',date:$.trim($r.find('[name$="[date]"]').val()||''),source:$.trim($r.find('[name$="[source]"]').val()||''),place:$.trim($r.find('[name$="[place]"]').val()||''),description:$.trim($r.find('[name$="[description]"]').val()||''),image:$.trim($r.find('.gep-image-url').val()||'')};}
      function dateLabel(v){var m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(v||''),months=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];return m?(parseInt(m[3],10)+' '+months[parseInt(m[2],10)-1]+' '+m[1]):v;}
      function dateValue(v){var m=/^(\d{4})-(\d{2})-(\d{2})/.exec(v||'');if(m)return +m[1]*10000+ +m[2]*100+ +m[3];var months={enero:1,ene:1,febrero:2,feb:2,marzo:3,mar:3,abril:4,abr:4,mayo:5,may:5,junio:6,jun:6,julio:7,jul:7,agosto:8,ago:8,septiembre:9,setiembre:9,sept:9,sep:9,octubre:10,oct:10,noviembre:11,nov:11,diciembre:12,dic:12},s=String(v||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/,| de /g,' ');m=/(\d{1,2})\s+([a-z]+)\s+(\d{4})/.exec(s);if(m&&months[m[2]])return +m[3]*10000+months[m[2]]*100+ +m[1];m=/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})/.exec(s);return m?+m[3]*10000+ +m[2]*100+ +m[1]:null;}
      function sortedRows(){var rows=$('#gep-hub-records>.gep-hub-row').get().map(function(el,i){return{el:el,i:i,v:dateValue($(el).find('[name$="[date]"]').val())};});rows.sort(function(a,b){if(a.v===null&&b.v===null)return a.i-b.i;if(a.v===null)return 1;if(b.v===null)return -1;return(b.v-a.v)||(a.i-b.i);});return $($.map(rows,function(r){return r.el;}));}
      function updateConditional($r){if(!isComms){$r.find('.gep-hub-place-field').hide();return;}$r.find('.gep-hub-place-field').toggle(($r.find('.gep-hub-type').val()||'')==='eventos');}
      function update(){
        var n=$('#gep-hub-records>.gep-hub-row').length;$('#gep-hub-count').text(n+' '+(n===1?'registro':'registros'));$('#gep-hub-empty').toggleClass('is-hidden',n>0);
        var eyebrow=field('[name="<?php echo esc_js( $prefix ); ?>hero_eyebrow"]')||(isComms?'Difusión':'Actualidad'),title=field('[name="<?php echo esc_js( $prefix ); ?>hero_title"]')||'<?php echo esc_js( $label ); ?>',desc=field('[name="<?php echo esc_js( $prefix ); ?>hero_text"]'),heroImage=resolveImage(field('[name="<?php echo esc_js( $prefix ); ?>hero_image"]')),heroVideo=resolveImage(field('[name="<?php echo esc_js( $prefix ); ?>hero_video"]'));
        $('#gep-hub-live-eyebrow').text(eyebrow);$('#gep-hub-live-title').text(title);$('#gep-hub-live-text').text(desc);setHeroMedia($('#gep-hub-live-hero'),heroImage,heroVideo);
        var $nav=$('#gep-hub-live-nav').empty();$('<b>Todos</b>').appendTo($nav);$.each(types,function(_,t){$('<span></span>').text(t).appendTo($nav);});
        var $p=$('#gep-hub-preview').empty(),shown=0;sortedRows().each(function(){var $r=$(this),d=rowData($r);updateConditional($r);if(shown>=6)return;var $card=$('<div class="gep-hub-preview-card"><div class="gep-hub-preview-media"></div><div class="gep-hub-preview-copy"><small></small><strong></strong><p></p></div></div>'),previewImage=resolveImage(d.image||fallbackImage(d,shown));if(previewImage)$card.find('.gep-hub-preview-media').css('background-image','url("'+previewImage.replace(/"/g,'%22')+'")').addClass('has-image');else $card.find('.gep-hub-preview-media').append($('<span></span>').text(d.type||'GEP'));$card.find('small').text((d.type||'')+(d.date?' · '+dateLabel(d.date):''));$card.find('strong').text(d.title);$card.find('p').text(d.description||d.source||d.place||'Contenido listo para completar.');$p.append($card);shown++;});if(!shown)$p.append('<div class="gep-hub-preview-empty">Añade contenido para verlo aquí.</div>');
      }
      function renumber($row,key){$row.find('[name]').each(function(){var n=$(this).attr('name');if(n)n=n.replace(/records\[[^\]]+\]/,'records['+key+']');$(this).attr('name',n);});$row.attr('data-hub-key',key);}
      function initSort(){var $r=$('#gep-hub-records');if($r.data('hub-sort'))return;$r.data('hub-sort',1).sortable({items:'>.gep-hub-row',handle:'.gep-grip',placeholder:'gep-docencia-sort-placeholder',forcePlaceholderSize:true,stop:function(){update();if(window.gepMarkDirty)window.gepMarkDirty();else $form.find(':input').first().trigger('change');}});}
      $(document).on('click','.gep-hub-device-button',function(e){e.preventDefault();setDevice($(this).data('device')||'desktop');});
      $(document).on('click','.gep-hub-add',function(){var key='h'+(++counter),html=$('#gep-hub-row-template').html().replaceAll('__K__',key),$r=$(html);$('#gep-hub-records').append($r);renumber($r,key);$r.attr('open',true);update();initSort();setZone('content');$form.find(':input').first().trigger('change');});
      $(document).on('click','.gep-hub-remove',function(){if(!confirm('¿Eliminar este contenido?'))return;$(this).closest('.gep-hub-row').remove();update();$form.find(':input').first().trigger('change');});
      $(document).on('click','.gep-hub-duplicate',function(){var $src=$(this).closest('.gep-hub-row'),key='h'+(++counter),$copy=$src.clone(false);renumber($copy,key);$copy.find('.gep-image-preview img').each(function(){var src=$(this).attr('src');$(this).closest('.gep-image-preview').toggle(!!src);});$copy.insertAfter($src).attr('open',true);update();initSort();$form.find(':input').first().trigger('change');});
      $(document).on('click','.gep-hub-up',function(){var $r=$(this).closest('.gep-hub-row'),$p=$r.prev('.gep-hub-row');if($p.length)$r.insertBefore($p);update();$form.find(':input').first().trigger('change');});
      $(document).on('click','.gep-hub-down',function(){var $r=$(this).closest('.gep-hub-row'),$n=$r.next('.gep-hub-row');if($n.length)$r.insertAfter($n);update();$form.find(':input').first().trigger('change');});
      $form.on('input change','.gep-hub-row :input',function(){var $r=$(this).closest('.gep-hub-row');$r.find('.gep-item-summary-name').text($.trim($r.find('.gep-hub-title').val()||'')||'Nuevo contenido');$r.find('.gep-item-summary-badge').text($r.find('.gep-hub-type option:selected').text());update();});
      $form.on('input change','[name$="hero_eyebrow"],[name$="hero_title"],[name$="hero_text"],[name$="hero_image"],[name$="hero_video"]',update);
      $form.on('focusin click','[data-hub-preview] input,[data-hub-preview] textarea,[data-hub-preview] select,[data-hub-preview] button,[data-hub-preview] .gep-step-heading',function(){setZone($(this).closest('[data-hub-preview]').data('hub-preview')||'hero');});
      var hubHoverTimer=null;
      $form.on('mouseenter.gepHubHoverPreview','[data-hub-preview]',function(){var zone=$(this).data('hub-preview')||'hero';clearTimeout(hubHoverTimer);hubHoverTimer=setTimeout(function(){setZone(zone);},70);});
      $(document).on('click','.gep-select-image,.gep-remove-image',function(){setTimeout(update,180);});
      initSort();setDevice('desktop');setZone('hero');update();
      // v9.21.3: admin.js define gepPreviewPlaceHeroVideo después de este bloque; se repinta una vez cuando ya existe.
      if(!window.gepPreviewPlaceHeroVideo)setTimeout(update,0);
    });
    </script>
    <?php
}

