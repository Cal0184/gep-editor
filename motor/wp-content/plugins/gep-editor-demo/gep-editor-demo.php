<?php
/**
 * Plugin Name: GEP Editor
 * Description: Editor guiado de contenido para GEP. Incluye Inicio, Quiénes somos, Contacto, Docencia, Proyectos, Servicios, Publicaciones, Comunicaciones y Noticias, con historial, preview, validación, filtros, orden drag & drop, duplicado y edición guiada.
 * Version: 10.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'GEP_EDITOR_VERSION' ) ) {
    define( 'GEP_EDITOR_VERSION', '10.0.2' );
}
if ( ! defined( 'GEP_EDITOR_PLUGIN_FILE' ) ) {
    define( 'GEP_EDITOR_PLUGIN_FILE', __FILE__ );
}
// Alias interno conservado para compatibilidad con módulos históricos.
if ( ! defined( 'GEP_DEMO_PLUGIN_FILE' ) ) {
    define( 'GEP_DEMO_PLUGIN_FILE', GEP_EDITOR_PLUGIN_FILE );
}

/**
 * URL base del sitio PHP público. Ya no depende de que WordPress se llame
 * /wordpress/. Por defecto usa la carpeta padre de la instalación de WP.
 * Puede sobrescribirse con GEP_EDITOR_PUBLIC_BASE_URL o con el filtro
 * gep_editor_public_base_url si el servidor usa otra estructura.
 */
function gep_editor_public_base_url() {
    if ( defined( 'GEP_EDITOR_PUBLIC_BASE_URL' ) && GEP_EDITOR_PUBLIC_BASE_URL ) {
        return untrailingslashit( esc_url_raw( GEP_EDITOR_PUBLIC_BASE_URL ) );
    }

    $home = untrailingslashit( home_url( '/' ) );
    $site = untrailingslashit( site_url( '/' ) );
    $base = '';

    // Instalación WordPress en subcarpeta con portada servida desde el nivel superior.
    if ( $home && $site && $home !== $site && 0 === strpos( $site, $home . '/' ) ) {
        $base = $home;
    } else {
        $parts = wp_parse_url( $site );
        if ( is_array( $parts ) && ! empty( $parts['host'] ) ) {
            $scheme = ! empty( $parts['scheme'] ) ? $parts['scheme'] : ( is_ssl() ? 'https' : 'http' );
            $port   = ! empty( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
            $path   = isset( $parts['path'] ) ? trim( $parts['path'], '/' ) : '';
            $parent = $path ? dirname( '/' . $path ) : '';
            if ( '.' === $parent || '/' === $parent ) $parent = '';
            $base = $scheme . '://' . $parts['host'] . $port . rtrim( $parent, '/' );
        }
    }

    // v9.18.1: el sitio público vive en una carpeta hermana de WordPress
    // (por defecto /GEP_LEP/). Si existe junto a WordPress, los enlaces
    // "Ver página real" y las previews apuntan ahí y no a la raíz del dominio.
    $folder = gep_editor_public_site_folder();
    if ( $base && '' !== $folder && ! preg_match( '#/' . preg_quote( $folder, '#' ) . '$#i', $base ) ) {
        $wp_parent = dirname( untrailingslashit( ABSPATH ) );
        if ( is_file( $wp_parent . '/' . $folder . '/index.php' ) ) {
            $base .= '/' . rawurlencode( $folder );
        }
    }

    $base = apply_filters( 'gep_editor_public_base_url', $base );
    return untrailingslashit( (string) $base );
}

/**
 * Carpeta del sitio PHP público junto a WordPress. Se puede cambiar con la
 * constante GEP_EDITOR_PUBLIC_FOLDER (en wp-config.php) o con el filtro
 * gep_editor_public_site_folder. Vacío = el sitio está en la raíz.
 */
function gep_editor_public_site_folder() {
    $folder = defined( 'GEP_EDITOR_PUBLIC_FOLDER' ) ? (string) GEP_EDITOR_PUBLIC_FOLDER : 'GEP_LEP';
    $folder = (string) apply_filters( 'gep_editor_public_site_folder', $folder );
    return preg_replace( '#[^A-Za-z0-9._-]#', '', trim( $folder, '/' ) );
}

function gep_editor_public_page_url( $filename, $post_id = 0 ) {
    $base = gep_editor_public_base_url();
    if ( $base ) {
        return trailingslashit( $base ) . ltrim( (string) $filename, '/' );
    }
    return $post_id ? get_permalink( $post_id ) : home_url( '/' );
}

require_once __DIR__ . '/includes/cms-location.php'; // v9.27.1: Operación Motor (/gep/wordpress/ -> /gep/motor/).
require_once __DIR__ . '/includes/history.php';
require_once __DIR__ . '/includes/content-hubs.php';
require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/sections.php';
require_once __DIR__ . '/includes/help.php';
require_once __DIR__ . '/includes/backup.php';
require_once __DIR__ . '/includes/roles.php';
require_once __DIR__ . '/includes/defaults-v920.php';
require_once __DIR__ . '/includes/doi-import.php';
require_once __DIR__ . '/includes/image-alt.php';
require_once __DIR__ . '/includes/structured-data.php';
require_once __DIR__ . '/includes/theme.php';
require_once __DIR__ . '/includes/domain-urls.php';
require_once __DIR__ . '/includes/edit-lock.php';
require_once __DIR__ . '/includes/video-poster.php';
require_once __DIR__ . '/includes/ux.php'; // v9.24.0: barra fija, vista previa que acompaña, arrastre.
require_once __DIR__ . '/includes/image-frame.php'; // v9.26.0: «Encuadre de la foto» (componente común).
require_once __DIR__ . '/includes/button-editor.php'; // v9.26.0: botones principal/secundario con selector de destino.
// v10.0.0 · versión definitiva.
require_once __DIR__ . '/includes/registro-tecnico.php'; // Registro técnico de errores (sin datos sensibles).
require_once __DIR__ . '/includes/archivos-en-uso.php';  // «Se usa en» y no borrar archivos en uso.
require_once __DIR__ . '/includes/sitio-privado.php';    // Interruptor: pedir usuario y clave para ver el sitio.
require_once __DIR__ . '/includes/diagnostico.php';      // Contenido GEP → Diagnóstico (solo administradores).

/**
 * --------------------------------------------------------------------------
 * Datos y utilidades
 * --------------------------------------------------------------------------
 */

function gep_demo_directory_lookup_key( $value ) {
    $value = remove_accents( trim( (string) $value ) );
    $value = strtolower( $value );
    $value = preg_replace( '/\s+/', ' ', $value );
    return trim( (string) $value );
}

function gep_demo_original_specialization_for_name( $name ) {
    $map = array(
        'jaime hernandez palma'   => 'Geomática y ecología del paisaje aplicada a la evaluación y seguimiento de la vegetación.',
        'patricio corvalan vera'  => 'Mensura forestal.',
        'cristian estades marfan' => 'Ecología de vida silvestre y biología de la conservación.',
        'maria paz acuna ruz'     => 'Ecología del paisaje y movimiento animal.',
        'lissette cortes serey'   => 'Geomática aplicada a recursos naturales.',
        'lissette cortes'         => 'Geomática aplicada a recursos naturales.',
        'taryn fuentes castillo'  => 'Planificación sistemática de la conservación, ecología espacial.',
        'taryn fuentes'           => 'Planificación sistemática de la conservación, ecología espacial.',
    );
    $key = gep_demo_directory_lookup_key( $name );
    return isset( $map[ $key ] ) ? $map[ $key ] : '';
}

function gep_demo_original_becario_year_for_name( $name ) {
    $map = array(
        'maria paz acuna ruz'  => '2007–2008',
        'andrea contreras'     => '2007–2008',
        'catalina hernandez'   => '2010',
        'constanza rodriguez'  => '2011',
    );
    $key = gep_demo_directory_lookup_key( $name );
    return isset( $map[ $key ] ) ? $map[ $key ] : '';
}

function gep_demo_normalize_original_status( $value ) {
    $raw = trim( (string) $value );
    if ( '' === $raw ) {
        return '';
    }

    $key = gep_demo_directory_lookup_key( $raw );
    if ( in_array( $key, array( 'd', 'desarrollo', 'en desarrollo' ), true ) ) {
        return 'D';
    }
    if ( in_array( $key, array( 't', 'terminado', 'terminada' ), true ) ) {
        return 'T';
    }

    // Los becarios usan este mismo campo visual para año/período; cualquier
    // valor distinto de D/T se conserva literalmente.
    return $raw;
}

function gep_demo_enrich_directory_sections( $sections ) {
    if ( ! is_array( $sections ) ) {
        return array();
    }

    foreach ( $sections as &$section ) {
        if ( ! is_array( $section ) ) {
            continue;
        }

        $type        = isset( $section['type'] ) ? (string) $section['type'] : '';
        $title_key   = gep_demo_directory_lookup_key( isset( $section['title'] ) ? $section['title'] : '' );
        $is_becarios = false !== strpos( $title_key, 'becari' );
        $items       = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();

        foreach ( $items as &$item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $name = isset( $item['name'] ) ? (string) $item['name'] : '';

            if ( 'people' === $type ) {
                if ( empty( $item['specialization'] ) ) {
                    $original_specialization = gep_demo_original_specialization_for_name( $name );
                    if ( '' !== $original_specialization ) {
                        $item['specialization'] = $original_specialization;
                    }
                }
                if ( $is_becarios && empty( $item['year'] ) ) {
                    $original_year = gep_demo_original_becario_year_for_name( $name );
                    if ( '' !== $original_year ) {
                        $item['year'] = $original_year;
                    }
                }
            } elseif ( 'status' === $type ) {
                $item['status'] = gep_demo_normalize_original_status( isset( $item['status'] ) ? $item['status'] : '' );
                if ( $is_becarios && ( '' === $item['status'] || in_array( $item['status'], array( 'D', 'T' ), true ) ) ) {
                    $original_year = gep_demo_original_becario_year_for_name( $name );
                    if ( '' !== $original_year ) {
                        $item['status'] = $original_year;
                    }
                }
            }
        }
        unset( $item );

        $section['items'] = $items;
    }
    unset( $section );

    return $sections;
}

function gep_demo_get_sections( $post_id ) {
    // v9.20.0: sin apartados guardados, el editor carga las mismas personas que
    // muestra la página pública para poder editarlas.
    if ( ! metadata_exists( 'post', $post_id, 'gep_directory_sections_json' ) && function_exists( 'gep_demo_directory_original_sections' ) ) {
        return gep_demo_enrich_directory_sections( gep_demo_directory_original_sections() );
    }
    $json     = get_post_meta( $post_id, 'gep_directory_sections_json', true );
    $sections = json_decode( $json, true );
    // v9.23.0: si lo guardado quedó vacío, la web pública muestra el directorio
    // original; el editor carga ese mismo directorio para que se pueda editar.
    if ( ( ! is_array( $sections ) || empty( $sections ) ) && function_exists( 'gep_demo_directory_original_sections' ) ) {
        return gep_demo_enrich_directory_sections( gep_demo_directory_original_sections() );
    }
    return gep_demo_enrich_directory_sections( is_array( $sections ) ? $sections : array() );
}

function gep_demo_default_team_cards() {
    return array(
        array(
            'image'  => '',
            'kicker' => 'GEP · EQUIPO',
            'badge'  => 'IMAGEN REFERENCIAL',
            'title'  => 'Geomática y paisaje',
            'text'   => 'Análisis espacial · Ecología del paisaje',
        ),
        array(
            'image'  => '',
            'kicker' => 'GEP · EQUIPO',
            'badge'  => 'IMAGEN REFERENCIAL',
            'title'  => 'Mensura forestal',
            'text'   => 'Recursos forestales · Inventario',
        ),
        array(
            'image'  => '',
            'kicker' => 'GEP · EQUIPO',
            'badge'  => 'IMAGEN REFERENCIAL',
            'title'  => 'Conservación y territorio',
            'text'   => 'Planificación · Biodiversidad',
        ),
        array(
            'image'  => '',
            'kicker' => 'GEP · EQUIPO',
            'badge'  => 'IMAGEN REFERENCIAL',
            'title'  => 'Investigación en formación',
            'text'   => 'Tesis · Proyectos · Herramientas geoespaciales',
        ),
    );
}

function gep_demo_get_team_cards( $post_id ) {
    $json = get_post_meta( $post_id, 'gep_team_cards_json', true );

    // Si todavía no existe el campo, mostramos las cuatro tarjetas de referencia.
    if ( '' === $json ) {
        return gep_demo_default_team_cards();
    }

    $cards = json_decode( $json, true );
    return is_array( $cards ) ? $cards : array();
}

function gep_demo_default_values( $post_id ) {
    $values = array(
        'team_enabled'     => metadata_exists( 'post', $post_id, 'gep_team_enabled' ) ? get_post_meta( $post_id, 'gep_team_enabled', true ) : '1',
        'team_eyebrow'     => get_post_meta( $post_id, 'gep_team_eyebrow', true ),
        'team_title'       => get_post_meta( $post_id, 'gep_team_title', true ),
        'team_description' => get_post_meta( $post_id, 'gep_team_description', true ),
        'team_cards'       => gep_demo_get_team_cards( $post_id ),
        'eyebrow'          => get_post_meta( $post_id, 'gep_directory_eyebrow', true ),
        'title'            => get_post_meta( $post_id, 'gep_directory_title', true ),
        'description'      => get_post_meta( $post_id, 'gep_directory_description', true ),
        'legend'           => get_post_meta( $post_id, 'gep_directory_legend', true ),
        'hero_image'       => get_post_meta( $post_id, 'gep_about_hero_image', true ),
        'hero_video'       => get_post_meta( $post_id, 'gep_about_hero_video', true ),
        'image_url'        => get_post_meta( $post_id, 'gep_directory_image_url', true ),
        'replace'          => metadata_exists( 'post', $post_id, 'gep_directory_replace_static' ) ? get_post_meta( $post_id, 'gep_directory_replace_static', true ) : '1',
        'sections'         => gep_demo_get_sections( $post_id ),
    );

    if ( '' === $values['team_eyebrow'] ) {
        $values['team_eyebrow'] = 'Equipo y disciplinas';
    }
    if ( '' === $values['team_title'] ) {
        $values['team_title'] = "Personas detrás\nde los datos,\nmapas y paisajes.";
    }
    if ( '' === $values['team_description'] ) {
        $values['team_description'] = 'Estas imágenes son referenciales y se reemplazarán por fotografías institucionales del equipo antes de la publicación definitiva.';
    }
    if ( '' === $values['eyebrow'] ) {
        $values['eyebrow'] = 'Directorio GEP';
    }
    if ( '' === $values['title'] ) {
        $values['title'] = 'Trayectoria de investigadores y estudiantes.';
    }
    if ( '' === $values['description'] ) {
        $values['description'] = 'El directorio mantiene el contenido nominal recuperado, pero lo organiza en grupos desplegables para una lectura más clara y móvil.';
    }
    if ( '' === $values['legend'] ) {
        $values['legend'] = 'Estados del registro original: D = en desarrollo · T = terminado.';
    }

    return $values;
}


function gep_demo_default_contact_rows() {
    return array(
        array( 'kind' => 'text',  'label' => 'Laboratorio',            'value' => 'Laboratorio de Geomática y Ecología del Paisaje (GEP)' ),
        array( 'kind' => 'text',  'label' => 'Facultad',               'value' => 'Facultad de Ciencias Forestales y de la Conservación de la Naturaleza' ),
        array( 'kind' => 'text',  'label' => 'Institución',            'value' => 'Universidad de Chile' ),
        array( 'kind' => 'text',  'label' => 'Campus',                 'value' => 'Campus Sur (Antumapu)' ),
        array( 'kind' => 'text',  'label' => 'Dirección',              'value' => 'Av. Santa Rosa #11315 · La Pintana · Santiago de Chile' ),
        array( 'kind' => 'text',  'label' => 'Casilla',                'value' => 'Casilla 9206 · Santiago' ),
        array( 'kind' => 'text',  'label' => 'Teléfono',               'value' => '(56 2) 978 5877' ),
        array( 'kind' => 'email', 'label' => 'Jaime Hernández Palma',  'value' => 'jhernand@uchile.cl' ),
        array( 'kind' => 'email', 'label' => 'M. Paz Acuña',           'value' => 'mpacuna@u.uchile.cl' ),
    );
}

function gep_demo_get_contact_rows( $post_id ) {
    $json = get_post_meta( $post_id, 'gep_contact_rows_json', true );
    if ( '' === $json ) {
        return gep_demo_default_contact_rows();
    }

    $rows = json_decode( $json, true );
    return is_array( $rows ) ? $rows : array();
}

function gep_demo_contact_defaults() {
    return array(
        'hero_eyebrow'      => 'Universidad de Chile',
        'hero_title'        => 'Contáctanos',
        'hero_text'         => 'Campus Antumapu · Facultad de Ciencias Forestales y de la Conservación de la Naturaleza · Universidad de Chile.',
        'hero_image'        => '',
        'hero_video'        => '',
        'card_eyebrow'      => 'Contacto GEP',
        'card_title'        => 'Laboratorio de Geomática y Ecología del Paisaje',
        'primary_email'     => 'jhernand@uchile.cl',
        'email_button_text' => 'Escribir por correo',
        'map_button_text'   => 'Ver dirección ↗',
        'map_url'           => 'https://www.google.com/maps/search/?api=1&query=Av.+Santa+Rosa+11315+La+Pintana+Santiago+Chile',
        'location_eyebrow'  => 'Ubicación',
        'location_title'    => 'Campus Antumapu',
        'location_text'     => 'La Pintana · Santiago de Chile',
        'map_image_url'     => '',
        'note'              => '',
        'cta_title'         => '¿Necesitas información sobre el laboratorio, sus proyectos o su docencia?',
        'cta_button_text'   => 'Contactar al GEP',
        'rows'              => gep_demo_default_contact_rows(),
    );
}

/**
 * v9.21.2 · Las notas internas de la migración ("debe validarse antes de la
 * publicación…") se tratan como vacías: no salen en el sitio ni en el editor.
 * Una nota escrita por el equipo se conserva.
 */
function gep_demo_clean_internal_note( $text ) {
    $text  = trim( (string) $text );
    $plain = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
    $plain = preg_replace( '/\s+/u', ' ', $plain );
    foreach ( array( 'antes de la publicación', 'antes de su publicación', 'antes de una publicación institucional', 'antes de publicar el nuevo sitio', 'antes de habilitar la versión final' ) as $marker ) {
        if ( false !== strpos( $plain, $marker ) ) return '';
    }
    return $text;
}

function gep_demo_contact_values( $post_id ) {
    $defaults = gep_demo_contact_defaults();

    $meta_map = array(
        'hero_eyebrow'      => 'gep_contact_hero_eyebrow',
        'hero_title'        => 'gep_contact_hero_title',
        'hero_text'         => 'gep_contact_hero_text',
        'hero_image'        => 'gep_contact_hero_image',
        'hero_video'        => 'gep_contact_hero_video',
        'card_eyebrow'      => 'gep_contact_card_eyebrow',
        'card_title'        => 'gep_contact_card_title',
        'primary_email'     => 'gep_contact_primary_email',
        'email_button_text' => 'gep_contact_email_button_text',
        'map_button_text'   => 'gep_contact_map_button_text',
        'map_url'           => 'gep_contact_map_url',
        'location_eyebrow'  => 'gep_contact_location_eyebrow',
        'location_title'    => 'gep_contact_location_title',
        'location_text'     => 'gep_contact_location_text',
        'map_image_url'     => 'gep_contact_map_image_url',
        'note'              => 'gep_contact_note',
        'cta_title'         => 'gep_contact_cta_title',
        'cta_button_text'   => 'gep_contact_cta_button_text',
    );

    $values = $defaults;
    foreach ( $meta_map as $key => $meta_key ) {
        if ( metadata_exists( 'post', $post_id, $meta_key ) ) {
            $values[ $key ] = get_post_meta( $post_id, $meta_key, true );
        }
    }
    $values['rows'] = gep_demo_get_contact_rows( $post_id );
    $values['note'] = gep_demo_clean_internal_note( $values['note'] );

    return $values;
}

function gep_demo_is_contact_page( $post_id, $navigation = null ) {
    if ( ! $post_id ) {
        return false;
    }

    if ( null === $navigation ) {
        $navigation = gep_demo_get_page_navigation();
    }

    if ( ! empty( $navigation['contacto']['page'] ) && (int) $navigation['contacto']['page']->ID === (int) $post_id ) {
        return true;
    }

    if ( get_post_meta( $post_id, 'gep_contact_hero_title', true ) || get_post_meta( $post_id, 'gep_contact_rows_json', true ) ) {
        return true;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        return false;
    }

    $slug = gep_demo_normalize_page_name( $post->post_name );
    return in_array( $slug, array( 'contacto', 'contactanos', 'contactenos' ), true );
}

function gep_demo_contact_public_url( $post_id = 0 ) {
    return gep_editor_public_page_url( 'contactanos.php', $post_id );
}

function gep_demo_directory_public_url( $post_id = 0 ) {
    return gep_editor_public_page_url( 'quienes-somos.php', $post_id );
}

/**
 * Devuelve la carpeta pública donde vive GEP_LEP para que las previews del
 * administrador puedan cargar también las imágenes CSS predeterminadas.
 */
function gep_demo_public_asset_base_url( $public_url = '' ) {
    $public_url = trim( (string) $public_url );
    if ( '' !== $public_url ) {
        $clean = preg_replace( '~[?#].*$~', '', $public_url );
        if ( preg_match( '#/[^/]+\.php$#i', $clean ) ) {
            return trailingslashit( preg_replace( '#[^/]+\.php$#i', '', $clean ) );
        }
    }
    $base = gep_editor_public_base_url();
    return $base ? trailingslashit( $base ) : trailingslashit( home_url( '/' ) );
}


/**
 * --------------------------------------------------------------------------
 * Docencia
 * --------------------------------------------------------------------------
 */

function gep_demo_docencia_default_learning_steps() {
    return array(
        array( 'number' => '01', 'title' => 'Representar', 'text' => 'Cartografía y sistemas de referencia para organizar información territorial.' ),
        array( 'number' => '02', 'title' => 'Analizar', 'text' => 'SIG, teledetección y estadística para transformar datos en evidencia.' ),
        array( 'number' => '03', 'title' => 'Interpretar', 'text' => 'Ecología del paisaje para conectar patrones espaciales, procesos y cambio.' ),
    );
}

function gep_demo_docencia_default_pre_courses() {
    return array(
        array(
            'code'   => 'PRE 01',
            'title'  => 'Cartografía y Teledetección',
            'status' => 'Programa disponible',
            'url'    => 'https://www.gep.uchile.cl/Docencia/Programa%20Cartografia%20y%20teledeteccion%202011.pdf',
            'button' => 'PDF programa ↗',
        ),
        array(
            'code'   => 'PRE 02',
            'title'  => 'Sistemas de Información Geográfica',
            'status' => 'Programa disponible',
            'url'    => 'https://www.gep.uchile.cl/Docencia/Programa%20SIG%202011.pdf',
            'button' => 'PDF programa ↗',
        ),
    );
}

function gep_demo_docencia_default_post_courses() {
    return array(
        array( 'code' => 'ADV 01', 'title' => 'Ecología del Paisaje', 'status' => 'Programa disponible', 'url' => 'https://www.gep.uchile.cl/Docencia/Programa%20EcologiaPaisaje2010.pdf', 'button' => 'Abrir ↗' ),
        array( 'code' => 'ADV 02', 'title' => 'Geomática I - Nivel Básico', 'status' => 'Programa disponible', 'url' => 'https://www.gep.uchile.cl/Docencia/Programa%20Geom%C3%A1tica%20I%20-%202011.pdf', 'button' => 'Abrir ↗' ),
        array( 'code' => 'ADV 03', 'title' => 'Geomática II - Nivel Intermedio', 'status' => 'Programa disponible', 'url' => 'https://www.gep.uchile.cl/Docencia/Programa%20Geom%C3%A1tica%20II%20-%20Intermedio%202010.pdf', 'button' => 'Abrir ↗' ),
        array( 'code' => 'DIP 01', 'title' => 'Diplomado en Geomática Aplicada', 'status' => 'Referencia de programa', 'url' => 'http://www.forestal.uchile.cl/cursos/80155/diplomado-en-geomatica-aplicada', 'button' => 'Abrir ↗' ),
        array( 'code' => 'ADV 05', 'title' => 'Estadística Aplicada con R Project', 'status' => 'Programa disponible', 'url' => 'https://www.gep.uchile.cl/Docencia/Programa%20EARN%202010.pdf', 'button' => 'Abrir ↗' ),
    );
}

function gep_demo_docencia_defaults() {
    $post = gep_demo_docencia_default_post_courses();
    $diploma = array();
    $post_only = array();
    foreach ( $post as $course ) {
        if ( false !== stripos( isset( $course['title'] ) ? $course['title'] : '', 'diplomado' ) ) $diploma[] = $course;
        else $post_only[] = $course;
    }
    return array(
        'hero_eyebrow'=>'Formación','hero_title'=>'Docencia','hero_text'=>'Cursos y programas vinculados a cartografía, SIG, teledetección, geomática, ecología del paisaje y análisis estadístico.',
        'nav_pregrado'=>'Pregrado','nav_postgrado'=>'Postgrado','nav_diplomado'=>'Diplomado','nav_cursos'=>'Cursos y talleres','nav_campos'=>'Campos de formación',
        'learning_steps'=>gep_demo_docencia_default_learning_steps(),
        'pre_eyebrow'=>'Pregrado','pre_title'=>'Bases para leer y representar el territorio.','pre_text'=>'Cursos de pregrado asociados directamente al trabajo del laboratorio.','pre_courses'=>gep_demo_docencia_default_pre_courses(),
        'post_eyebrow'=>'Postgrado','post_title'=>'Profundización y herramientas aplicadas.','post_text'=>'Cursos avanzados vinculados a geomática, ecología del paisaje y análisis territorial.','post_courses'=>$post_only,
        'diploma_eyebrow'=>'Diplomado','diploma_title'=>'Formación profesional especializada.','diploma_text'=>'Programas de diplomado vinculados al trabajo del laboratorio.','diploma_courses'=>$diploma,
        'workshop_eyebrow'=>'Cursos y talleres','workshop_title'=>'Formación breve y actividades aplicadas.','workshop_text'=>'Cursos, talleres y materiales históricos para practicar herramientas geoespaciales.','workshop_courses'=>array(
            array( 'code'=>'TALL 01', 'title'=>'Curso de ArcGIS 9.2', 'status'=>'Material histórico disponible', 'url'=>'https://www.gep.uchile.cl/Publicaciones/Curso%20de%20ArcGIS%209.2%20-%20Hern%C3%A1ndez%20y%20Montaner%202008.pdf', 'button'=>'Abrir material ↗' ),
            array( 'code'=>'TALL 02', 'title'=>'Tratamiento Digital de Imágenes', 'status'=>'Material histórico disponible', 'url'=>'https://www.gep.uchile.cl/Publicaciones/Tratamiento%20Digital%20de%20Im%C3%A1genes%20-%20Jaime%20Hern%C3%A1ndez%202011.pdf', 'button'=>'Abrir material ↗' ),
        ),
        'note'=>'',
        'fields_eyebrow'=>'Campos de formación','fields_title'=>'Cartografía, SIG, teledetección, geomática y ecología del paisaje.','fields_text'=>'La docencia histórica del GEP conecta herramientas de información geográfica con análisis ecológico y territorial.','fields_image_url'=>'','fields_image_alt'=>'Bosque con araucarias como paisaje de referencia para formación en ecología y territorio',
    );
}

function gep_demo_docencia_get_json_meta( $post_id, $meta_key, $fallback ) {
    $json = get_post_meta( $post_id, $meta_key, true );
    if ( '' === $json ) {
        return $fallback;
    }
    $value = json_decode( $json, true );
    return is_array( $value ) ? $value : $fallback;
}

function gep_demo_docencia_values( $post_id ) {
    $values = gep_demo_docencia_defaults();
    if ( ! isset( $values['hero_image'] ) ) $values['hero_image'] = '';
    if ( ! isset( $values['hero_video'] ) ) $values['hero_video'] = '';
    $meta_map = array(
        'hero_eyebrow'=>'gep_docencia_hero_eyebrow','hero_title'=>'gep_docencia_hero_title','hero_text'=>'gep_docencia_hero_text','hero_image'=>'gep_docencia_hero_image','hero_video'=>'gep_docencia_hero_video',
        'nav_pregrado'=>'gep_docencia_nav_pregrado','nav_postgrado'=>'gep_docencia_nav_postgrado','nav_diplomado'=>'gep_docencia_nav_diplomado','nav_cursos'=>'gep_docencia_nav_cursos','nav_campos'=>'gep_docencia_nav_campos',
        'pre_eyebrow'=>'gep_docencia_pre_eyebrow','pre_title'=>'gep_docencia_pre_title','pre_text'=>'gep_docencia_pre_text',
        'post_eyebrow'=>'gep_docencia_post_eyebrow','post_title'=>'gep_docencia_post_title','post_text'=>'gep_docencia_post_text',
        'diploma_eyebrow'=>'gep_docencia_diploma_eyebrow','diploma_title'=>'gep_docencia_diploma_title','diploma_text'=>'gep_docencia_diploma_text',
        'workshop_eyebrow'=>'gep_docencia_workshop_eyebrow','workshop_title'=>'gep_docencia_workshop_title','workshop_text'=>'gep_docencia_workshop_text',
        'note'=>'gep_docencia_note','fields_eyebrow'=>'gep_docencia_fields_eyebrow','fields_title'=>'gep_docencia_fields_title','fields_text'=>'gep_docencia_fields_text','fields_image_url'=>'gep_docencia_fields_image_url','fields_image_alt'=>'gep_docencia_fields_image_alt',
    );
    foreach ( $meta_map as $key=>$meta_key ) if ( metadata_exists( 'post', $post_id, $meta_key ) ) $values[$key]=get_post_meta($post_id,$meta_key,true);
    $values['learning_steps']=gep_demo_docencia_get_json_meta($post_id,'gep_docencia_learning_steps_json',$values['learning_steps']);
    $values['pre_courses']=gep_demo_docencia_get_json_meta($post_id,'gep_docencia_pre_courses_json',$values['pre_courses']);
    $legacy_post=gep_demo_docencia_get_json_meta($post_id,'gep_docencia_post_courses_json',$values['post_courses']);
    if ( metadata_exists('post',$post_id,'gep_docencia_diploma_courses_json') ) {
        $values['post_courses']=$legacy_post;
        $values['diploma_courses']=gep_demo_docencia_get_json_meta($post_id,'gep_docencia_diploma_courses_json',$values['diploma_courses']);
    } elseif ( metadata_exists('post',$post_id,'gep_docencia_post_courses_json') ) {
        // v9.21.2: solo se separan los diplomados de una lista antigua guardada;
        // sin nada guardado se usan los valores por defecto (incluye el Diplomado).
        $values['post_courses']=array(); $values['diploma_courses']=array();
        foreach($legacy_post as $course){ if(false!==stripos(isset($course['title'])?$course['title']:'','diplomado'))$values['diploma_courses'][]=$course;else $values['post_courses'][]=$course; }
    }
    $values['workshop_courses']=gep_demo_docencia_get_json_meta($post_id,'gep_docencia_workshop_courses_json',$values['workshop_courses']);
    $values['note']=gep_demo_clean_internal_note(isset($values['note'])?$values['note']:'');
    foreach(array('pre_courses','post_courses','diploma_courses','workshop_courses') as $list){
        foreach($values[$list] as &$course){ if(!is_array($course))$course=array(); $course+=array('code'=>'','title'=>'','status'=>'','url'=>'','button'=>'','image'=>'','image_alt'=>''); }
        unset($course);
    }
    return $values;
}

function gep_demo_is_docencia_page( $post_id, $navigation = null ) {
    if ( ! $post_id ) {
        return false;
    }
    if ( null === $navigation ) {
        $navigation = gep_demo_get_page_navigation();
    }
    if ( ! empty( $navigation['docencia']['page'] ) && (int) $navigation['docencia']['page']->ID === (int) $post_id ) {
        return true;
    }
    if ( get_post_meta( $post_id, 'gep_docencia_hero_title', true ) || get_post_meta( $post_id, 'gep_docencia_pre_courses_json', true ) ) {
        return true;
    }
    $post = get_post( $post_id );
    return $post && 'docencia' === gep_demo_normalize_page_name( $post->post_name );
}

function gep_demo_docencia_public_url( $post_id = 0 ) {
    return gep_editor_public_page_url( 'docencia.php', $post_id );
}


// v10.0.0: se quitó gep_demo_seed_docencia_workshops_v990() (carga inicial antigua que ya no se ejecutaba).

// v9.16 Beta: contenido provisional disponible, pero nunca se reinyecta automáticamente.
// add_action( 'admin_init', 'gep_demo_seed_docencia_workshops_v990', 9 );

/**
 * --------------------------------------------------------------------------
 * Proyectos
 * --------------------------------------------------------------------------
 */

function gep_demo_project_slug( $row ) {
    $row  = is_array( $row ) ? $row : array();
    $slug = isset( $row['slug'] ) ? sanitize_title( (string) $row['slug'] ) : '';
    if ( '' !== $slug ) return $slug;
    $seed = trim( ( isset( $row['title'] ) ? (string) $row['title'] : '' ) . '-' . ( isset( $row['year'] ) ? (string) $row['year'] : '' ), '-' );
    return '' !== $seed ? sanitize_title( $seed ) : '';
}

function gep_demo_clean_project_gallery( $value ) {
    $parts = is_array( $value ) ? $value : preg_split( '/[\r\n]+/', (string) $value );
    $clean = array();
    foreach ( (array) $parts as $url ) {
        $url = esc_url_raw( trim( (string) $url ) );
        if ( '' !== $url && ! in_array( $url, $clean, true ) ) $clean[] = $url;
    }
    return implode( "\n", $clean );
}

function gep_demo_projects_defaults() {
    $json = <<<'JSON'
{"hero_eyebrow":"Investigación aplicada","hero_title":"Proyectos","hero_text":"Una trayectoria de investigación aplicada en geomática, biodiversidad, conservación, paisaje, teledetección y planificación territorial.","nav_featured":"Vigentes","nav_trajectory":"Trayectoria","nav_areas":"Áreas de trabajo","features":[{"image":"","alt":"Vista satelital de un mosaico rural con caminos, vegetación y asentamientos","kicker":"2014–2016 · FONDECYT 1140319","title":"Vegetation Knowledge-based Indicators for Urban Sustainable Planning","text":"El proyecto más reciente del trayectoria principal del GEP."},{"image":"","alt":"Vista aérea de un paisaje montañoso atravesado por una carretera","kicker":"2006–2016","title":"Una década de investigación aplicada","text":"Conservación, biodiversidad, sensores remotos, biomasa, cambio climático, fauna, arbolado urbano y planificación territorial."}],"trajectory_eyebrow":"2006 — 2016","trajectory_title":"Proyectos cerrados · trayectoria","trajectory_text":"Explora los 16 registros del sitio GEP por período o busca por institución, tema o palabra clave.","projects":[{"year":"2014–2016","era":"2014-2016","theme":"Investigación aplicada","title":"FONDECYT 1140319","text":"Vegetation Knowledge-based Indicators for Urban Sustainable Planning.","url":"https://www.gep.uchile.cl/Proyectos/FONDECYT%20ABSTRACT%20JHP%202014-2016.pdf","button":"Abstract ↗","tag":"Proyecto GEP"},{"year":"2014","era":"2014-2016","theme":"Teledetección","title":"UC Berkeley–Chile Grant","text":"Development of advanced remote sensing methods for mapping and managing plant species diversity in Mediterranean Forests of Chile and California.","url":"https://www.gep.uchile.cl/Proyectos/Brief%20UCB-UC%20proposal%20Biging-Hern%C3%A1ndez.pdf","button":"Brief ↗","tag":"Proyecto GEP"},{"year":"2014–2015","era":"2014-2016","theme":"Ciudad y paisaje","title":"FPA","text":"Bosques de Santiago Andino: Opciones de valoración y recuperación.","url":"https://www.gep.uchile.cl/Proyectos/Ficha%20de%20Proyecto%20-%20Bosques%20de%20Santiago%20Andino%20FPA%202014-2015.pdf","button":"Ficha de proyecto ↗","tag":"Proyecto GEP"},{"year":"2012–2013","era":"2010-2013","theme":"Fauna y biodiversidad","title":"GORE Arica y Parinacota","text":"Diagnóstico Levantamiento Biodiversidad Región Arica y Parinacota.","url":"https://www.gep.uchile.cl/Proyectos/FICHA%20Proyecto%20Arica%20y%20Parinacota.pdf","button":"Ficha de proyecto ↗","tag":"Proyecto GEP"},{"year":"2012–2013","era":"2010-2013","theme":"Fauna y biodiversidad","title":"SAG","text":"Diagnóstico de la ecología poblacional de la vicuña (Vicugna vicugna mensalis), taruka (Hippocamelus anticensis) y guanaco (Lama guanicoe cacsilensis) y medidas de solución al conflicto silvoagropecuario – ungulados silvestres en la Región de Tarapacá.","url":"https://www.gep.uchile.cl/Proyectos/Ficha_Ongulados.pdf","button":"Ficha de proyecto ↗","tag":"Proyecto GEP"},{"year":"2010–2013","era":"2010-2013","theme":"Fauna y biodiversidad","title":"FONDECYT 1100339","text":"Spatial epidemiology of Chagas disease: using environmental variables and reservoirs distribution to predict the distribution of sylvatic foci of Triatoma infestans and Mepraia spinolai.","url":"https://www.gep.uchile.cl/Proyectos/Chagas/Resumen%20FONDECYT%20CHAGAS.pdf","button":"Ficha de proyecto ↗","tag":"Proyecto GEP"},{"year":"2010–2013","era":"2010-2013","theme":"Bosques y biomasa","title":"INNOVA-CORFO · Biocomsa","text":"Evaluación y monitoreo de las existencias de biomasa aérea y optimización de redes de abastecimiento a plantas de biocombustible, a partir de material lignocelulósico.","url":"https://www.gep.uchile.cl/Proyectos/Ficha_proyecto_Biocomsa.pdf","button":"Ficha de proyecto ↗","tag":"Proyecto GEP"},{"year":"2010","era":"2010-2013","theme":"Planificación territorial","title":"Bahía Exploradores · Región de Aysén","text":"Análisis y diagnóstico de componentes ambientales. Plan de manejo y zonificación. Asesoría y gestión Travesías Sociedad por Acciones.","url":"https://www.gep.uchile.cl/Servidor%20de%20mapas/servidos_mapas_Exploradores.html","button":"Ver mapa ↗","tag":"Proyecto GEP"},{"year":"2009–2010","era":"2010-2013","theme":"Fauna y biodiversidad","title":"SAG · Programa de Monitoreo Poblacional del Suri","text":"Programa de Monitoreo Poblacional del Suri (Pterocnemia pennata tarapacensis) en las comunas de Ollagüe, Calama, San Pedro de Atacama y Antofagasta.","url":"https://www.gep.uchile.cl/Proyectos/ESTUDIO%20POBLACIONAL%20DEL%20SURI%20(Pterocnemia%20pennata%20tarapacensis)%20EN%20LAS%20COMUNAS%20DE%20OLLAG%C3%9CE%2c%20SAN%20PEDRO%20DE%20ATACAMA%20Y%20ANTOFAGASTA.pdf","button":"Informe ↗","tag":"Proyecto GEP"},{"year":"2009–2011","era":"2010-2013","theme":"Ciudad y paisaje","title":"FONDECYT 1090199 · Río Mapocho","text":"Corredor fluvial del Río Mapocho siglo XXI: hacia un conjunto de variables para la definición y el manejo de un polígono de intervención multifacética e integral entre Quinta Normal y El Monte. Investigadora Responsable: María Isabel Pavés. El registro histórico incorpora además: 2009 (Marzo–Agosto), Congreso Nacional, Estudio sobre la Gestión Integrada de los Recursos Hídricos en Chile.","url":"https://www.gep.uchile.cl/Servidor%20de%20mapas/servidos_mapocho.html","button":"Ver mapa ↗","tag":"Proyecto GEP"},{"year":"2009 · Ene–Abr","era":"2006-2009","theme":"Planificación territorial","title":"PNUD · Región de Los Ríos","text":"Estudio Diagnóstico y validación: áreas claves de conservación de la depresión intermedia y serranías transversales de la XIV Región de Los Ríos. Co-director.","url":"https://www.gep.uchile.cl/Proyectos/DIAGNOSTICO%20Y%20VALIDACI%C3%93N%20%C3%81REAS%20CLAVES%20REGI%C3%93N%20XIV.pdf","button":"Informe ↗","tag":"Proyecto GEP"},{"year":"2009 · Ene–Abr","era":"2006-2009","theme":"Planificación territorial","title":"CONAMA","text":"Estudio de Valoración de Propuestas de Sistemas de Clasificación de Ecosistemas Terrestres.","url":"","button":"","tag":"Proyecto GEP"},{"year":"2008 · Abr–Oct","era":"2006-2009","theme":"Fauna y biodiversidad","title":"CONAF · Evaluación poblacional del Suri","text":"Evaluación poblacional del Suri (Rhea pennata tarapacensis) en las regiones de Arica y Parinacota, y Tarapacá.","url":"https://www.gep.uchile.cl/Proyectos/EVALUACI%C3%93N%20POBLACIONAL%20DEL%20SURI%20(Pterocnemia%20pennata%20tarapacensis)%20TARAPACA%20y%20PARINACOTA-2008.pdf","button":"Informe ↗","tag":"Proyecto GEP"},{"year":"2008 · May–Nov","era":"2006-2009","theme":"Cambio climático","title":"CONAMA · Co-investigador","text":"Análisis de vulnerabilidad y adaptación del sector silvoagropecuario y de los recursos hídricos y edáficos de Chile frente al cambio climático.","url":"","button":"","tag":"Proyecto GEP"},{"year":"2008–2010","era":"2010-2013","theme":"Fauna y biodiversidad","title":"Celulosa Arauco y Constitución S.A. · Planta Nueva Aldea","text":"Monitoreo de Vegetación, Flora y Fauna en Planta Nueva Aldea.","url":"","button":"","tag":"Proyecto GEP"},{"year":"2006–2007","era":"2006-2009","theme":"Ciudad y paisaje","title":"Municipalidad de San Bernardo","text":"Estudio de Diagnóstico del Estado de Conservación del Arbolado Urbano Viario Céntrico de la Comuna de San Bernardo y Proposición Técnica de Plan de Manejo.","url":"","button":"","tag":"Proyecto GEP"}],"note":"","areas_eyebrow":"Áreas de trabajo","areas_title":"Una investigación conectada por múltiples escalas.","areas_text":"Los proyectos recuperados muestran cruces entre sistemas naturales, infraestructura, ciudades, biodiversidad y herramientas geoespaciales.","areas":[{"icon":"＋","title":"Teledetección","text":"Sensores remotos, cartografía y clasificación de coberturas."},{"icon":"＋","title":"Biodiversidad","text":"Fauna, flora, conservación y monitoreo poblacional."},{"icon":"＋","title":"Bosques y biomasa","text":"Estructura de vegetación, biomasa y carbono."},{"icon":"＋","title":"Ciudad y paisaje","text":"Arbolado urbano, corredores y transformación metropolitana."},{"icon":"＋","title":"Cambio climático","text":"Vulnerabilidad, adaptación y escenarios territoriales."},{"icon":"＋","title":"Planificación","text":"Zonificación, manejo y apoyo a decisiones espaciales."}]}
JSON;
    $data = json_decode( $json, true );
    return is_array( $data ) ? $data : array();
}

function gep_demo_projects_json_meta( $post_id, $meta_key, $fallback ) {
    $json = get_post_meta( $post_id, $meta_key, true );
    if ( '' === $json ) return $fallback;
    $value = json_decode( $json, true );
    return is_array( $value ) ? $value : $fallback;
}

function gep_demo_projects_values( $post_id ) {
    $values=gep_demo_projects_defaults();
    if ( ! isset( $values['hero_image'] ) ) $values['hero_image']='';
    if ( ! isset( $values['hero_video'] ) ) $values['hero_video']='';
    $map=array('hero_eyebrow'=>'gep_projects_hero_eyebrow','hero_title'=>'gep_projects_hero_title','hero_text'=>'gep_projects_hero_text','hero_image'=>'gep_projects_hero_image','hero_video'=>'gep_projects_hero_video','nav_featured'=>'gep_projects_nav_featured','nav_trajectory'=>'gep_projects_nav_trajectory','nav_areas'=>'gep_projects_nav_areas','trajectory_eyebrow'=>'gep_projects_trajectory_eyebrow','trajectory_title'=>'gep_projects_trajectory_title','trajectory_text'=>'gep_projects_trajectory_text','note'=>'gep_projects_note','areas_eyebrow'=>'gep_projects_areas_eyebrow','areas_title'=>'gep_projects_areas_title','areas_text'=>'gep_projects_areas_text');
    foreach($map as $key=>$meta_key)if(metadata_exists('post',$post_id,$meta_key))$values[$key]=get_post_meta($post_id,$meta_key,true);
    $values['features']=gep_demo_projects_json_meta($post_id,'gep_projects_features_json',$values['features']);
    $values['projects']=gep_demo_projects_json_meta($post_id,'gep_projects_records_json',$values['projects']);
    // v9.20.0: sin proyectos guardados se incluyen los vigentes de MapBiomas, en el editor y en la página.
    if(!metadata_exists('post',$post_id,'gep_projects_records_json')&&function_exists('gep_demo_projects_current_starters')){
        $has_current=false; foreach((array)$values['projects'] as $r){if(is_array($r)&&'vigente'===strtolower(trim((string)($r['status']??''))))$has_current=true;}
        if(!$has_current)$values['projects']=array_merge(gep_demo_projects_current_starters(),(array)$values['projects']);
    }
    $values['areas']=gep_demo_projects_json_meta($post_id,'gep_projects_areas_json',$values['areas']);
    if ( isset($values['nav_featured']) && 'Destacados' === trim((string)$values['nav_featured']) ) $values['nav_featured']='Vigentes';
    foreach($values['projects'] as &$r){if(!is_array($r))$r=array();$r+=array('slug'=>'','status'=>'cerrado','image'=>'','image_alt'=>'','details'=>'','institution'=>'','lead'=>'','gallery'=>'','detail_url'=>'');}unset($r);
    $values['note']=gep_demo_clean_internal_note(isset($values['note'])?$values['note']:'');
    return $values;
}

// v10.0.0: se quitó gep_demo_seed_current_projects_v990() (carga inicial antigua que ya no se ejecutaba).

// v9.16 Beta: no completar proyectos automáticamente al abrir el panel.
// add_action( 'admin_init', 'gep_demo_seed_current_projects_v990', 10 );


// v10.0.0: se quitó gep_demo_seed_showcase_content_v9100() (carga inicial antigua que ya no se ejecutaba).

// v9.16 Beta: no regenerar datos de demostración borrados por el usuario.
// add_action( 'admin_init', 'gep_demo_seed_showcase_content_v9100', 12 );

function gep_demo_is_projects_page( $post_id, $navigation = null ) {
    if ( ! $post_id ) return false;
    if ( null === $navigation ) $navigation = gep_demo_get_page_navigation();
    if ( ! empty( $navigation['proyectos']['page'] ) && (int) $navigation['proyectos']['page']->ID === (int) $post_id ) return true;
    if ( get_post_meta( $post_id, 'gep_projects_hero_title', true ) || get_post_meta( $post_id, 'gep_projects_records_json', true ) ) return true;
    $post = get_post( $post_id );
    return $post && in_array( gep_demo_normalize_page_name( $post->post_name ), array( 'proyectos', 'proyecto' ), true );
}

function gep_demo_projects_public_url( $post_id = 0 ) {
    return gep_editor_public_page_url( 'proyectos.php', $post_id );
}


/**
 * --------------------------------------------------------------------------
 * Publicaciones
 * --------------------------------------------------------------------------
 */
function gep_demo_publications_defaults() {
    $json = <<<'JSON'
{"hero_eyebrow":"Producción científica","hero_title":"Publicaciones","hero_text":"Publicaciones, capítulos, libros, congresos y materiales que documentan la trayectoria académica del laboratorio.","nav_search":"Buscar y filtrar","nav_internacionales":"Internacionales","nav_nacionales":"Nacionales","nav_libros":"Libros","nav_congresos":"Congresos","explorer_title":"Explorar publicaciones","note":"","sections":{"libros-free":{"label":"Materiales","eyebrow":"Materiales abiertos","title":"Libros y recursos de consulta"},"internacionales":{"label":"Internacionales","eyebrow":"Revistas","title":"Publicaciones internacionales"},"nacionales":{"label":"Nacionales","eyebrow":"Revistas","title":"Publicaciones nacionales"},"libros":{"label":"Libros y capítulos","eyebrow":"Producción editorial","title":"Libros, capítulos y manuales"},"congresos":{"label":"Congresos","eyebrow":"Divulgación académica","title":"Congresos y encuentros científicos"}},"records":[{"category":"libros-free","year":"2014","title":"Biodiversidad de Arica y Parinacota","text":"Recurso gratuito enlazado desde el sitio histórico.","url":"https://www.gep.uchile.cl/Biodiversidad/img/Biodiversidad%20Terrestre%20de%20la%20Regi%C3%B3n%20de%20Arica%20y%20Parinacota.pdf","button":"Abrir PDF ↗"},{"category":"libros-free","year":"2008","title":"Curso de ArcGIS 9.2","text":"Recurso gratuito enlazado desde el sitio histórico.","url":"https://www.gep.uchile.cl/Publicaciones/Curso%20de%20ArcGIS%209.2%20-%20Hern%C3%A1ndez%20y%20Montaner%202008.pdf","button":"Abrir PDF ↗"},{"category":"libros-free","year":"2011","title":"Tratamiento Digital de Imágenes","text":"Recurso gratuito enlazado desde el sitio histórico.","url":"https://www.gep.uchile.cl/Publicaciones/Tratamiento%20Digital%20de%20Im%C3%A1genes%20-%20Jaime%20Hern%C3%A1ndez%202011.pdf","button":"Abrir PDF ↗"},{"category":"libros-free","year":"2000","title":"Evaluación y Monitoreo de la Flora y la Vegetación","text":"Recurso gratuito enlazado desde el sitio histórico.","url":"https://www.gep.uchile.cl/Publicaciones/Manual%20de%20M%C3%A9todos%20y%20Criterios%20para%20la%20Evaluaci%C3%B3n%20y%20Monitoreo%20%20de%20la%20Flora%20y%20la%20Vegetaci%C3%B3n.pdf","button":"Abrir PDF ↗"},{"category":"libros-free","year":"2011","title":"Tablas de biomasa aérea de pino","text":"Recurso gratuito enlazado desde el sitio histórico.","url":"https://www.gep.uchile.cl/Publicaciones/Tablas%20de%20estimaci%C3%B3n%20de%20biomasa%20a%C3%A9rea%20bruta%20en%20pie%20para%20plantaciones%20de%20Pino%20Insigne%20en%20Chile.pdf","button":"Abrir PDF ↗"},{"category":"libros-free","year":"2012","title":"Tablas de biomasa aérea de eucalipto","text":"Recurso gratuito enlazado desde el sitio histórico.","url":"https://www.gep.uchile.cl/Publicaciones/Tabla%20de%20rendimiento%20en%20biomasa%20a%C3%A9rea%20en%20pie%20para%20plantaciones%20de%20Eucalyptus%20globulus%20en%20Chile.pdf","button":"Abrir PDF ↗"},{"category":"internacionales","year":"2016","title":"","text":"Miranda, A., Hernández, H.J., Bustamante, R., Díaz, M., González, L. & Altamirano, A. Regeneración natural y patrones de distribución espacial de la palma chilena Jubaea chilensis en los bosques mediterráneos de Chile central. Gayana Botánica 73(1): 26-35.","url":"","button":""},{"category":"internacionales","year":"2016","title":"","text":"Escobedo, F., Palmas-Perez, S., Dobbs, C., Gezan, S. & Hernández, H.J. Spatio-Temporal Changes in Structure for a Mediterranean Urban Forest: Santiago, Chile 2002 to 2014. Forest 7, 121. doi:10.3390/f7060121.","url":"","button":""},{"category":"internacionales","year":"2016","title":"","text":"Zhao, Y.; Feng, D.; Yua, L.; Wang, X.; Chen, Y.; Hernández, H.J.; Galleguillos, M.; Estades, C.; Biging, G.; Radke, J. & Gong, P. Detailed dynamic land cover mapping of Chile: accuracy improvement by integrating multi-seasonal land cover data. Remote Sensing of Environment 183, 170–185.","url":"","button":""},{"category":"internacionales","year":"2015","title":"","text":"Lopatin, J., Dolos, K., Hernández, H.J., Galleguillos, M., Fassnacht, F. Comparing generalized linear models and random forest to model vascular plant species richness using LiDAR data in a natural forest in central Chile. Remote Sensing of Environment, Vol. 73: 200–210.","url":"","button":""},{"category":"internacionales","year":"2015","title":"","text":"Ávalos, V. & Hernández, H.J. Distribution shifts of range-restricted Andean birds from Bolivia and southeastern Peru under climate change: representation in protected areas and conservation. Global Ecology and Conservation 4:459-469.","url":"","button":""},{"category":"internacionales","year":"2015","title":"","text":"Maack, J., Kattenborn, T., Fassnacht, F., Hernández, H.J., Corvalán, P. & Koch, B. Modeling forest biomass using Very-High-Resolution and photogrammetric predictors derived from data - Combining textural, spectral spaceborne stereo images. European Journal of Remote Sensing 48:245-261.","url":"","button":""},{"category":"internacionales","year":"2015","title":"","text":"Ceballos, A., Hernández, H.J., Corvalán, P. & Galleguillos, M. Comparison of Airborne LiDAR and Satellite Hyperspectral Remote Sensing to Estimate Vascular Plant Richness in Deciduous Mediterranean Forests of Central Chile. Remote Sensing 7, 2692-2714.","url":"","button":""},{"category":"internacionales","year":"2015","title":"","text":"Latifi, H., Fassnacht, F., Hartig, F., Bergere, C., Hernández, H.J., Corvalán, P. & Koch, B. Stratified aboveground forest biomass estimation by remote sensing data. International Journal of Applied Earth Observation and Geoinformation 38:229–241.","url":"","button":""},{"category":"internacionales","year":"2015","title":"","text":"Lopatin, J., Ceballos, A., Galleguillos, M., Hernández, H.J. Using a multi-structural object-based LiDAR approach to estimate vascular plant richness in Mediterranean forests with complex structure. Geoscience and Remote Sensing Letters 12(5):1008-1012.","url":"","button":""},{"category":"internacionales","year":"2014","title":"","text":"Pérez-Hernández, C.G., Vergara, P.M., Saura, S. & Hernández, H.J. Do corridors promote connectivity for bird-dispersed trees? The case of Persea lingue in Chilean fragmented landscapes. Landscape Ecology 30(1):77-90.","url":"","button":""},{"category":"internacionales","year":"2014","title":"","text":"Fassnacht, F., Hartig, F., Latifi, H., Berger, C., Hernández, H.J., Corvalán, P. & Koch, B. Importance of sample size, data type and prediction method for remote sensing-based estimations of aboveground forest biomass. Remote Sensing of Environment 154:102–114.","url":"","button":""},{"category":"internacionales","year":"2014","title":"","text":"Corvalán, C.P., Galleguillos, M. & Hernández, H.J. Presencia, abundancia y asociatividad de Citronella mucronata en bosques secundarios de Nothofagus obliqua en la precordillera de Curicó, Región del Maule, Chile. Bosque 35(3):269-278.","url":"","button":""},{"category":"internacionales","year":"2014","title":"","text":"Grez, A., Zaviezo, T., Hernández, J., Rodríguez-San Pedro, A. & Acuña, P. The heterogeneity and composition of agricultural landscapes influence native and exotic coccinellids in alfalfa fields. Agricultural and Forest Entomology.","url":"","button":""},{"category":"internacionales","year":"2014","title":"","text":"Mattar, C., Hernández, J., Santamaría-Artigas, A., Durán-Alarcón, C., Olivera-Guerra, M., Inzunza, D., Tapia, D. & Escobar-Lavín, E. A first in-flight absolute calibration of the Chilean Earth Observation Satellite. ISPRS Journal of Photogrammetry and Remote Sensing 92:16–25.","url":"","button":""},{"category":"internacionales","year":"2014","title":"","text":"Cortés, L., Hernández, J., Valencia, D. & Corvalán, P. Estimation of Above-Ground Forest Biomass Using Landsat ETM+, Aster GDEM and LiDAR. Forest Research 3:117.","url":"","button":""},{"category":"internacionales","year":"2013","title":"","text":"Hernández, J.; Nuñez, I.; Bacigalupo, A. & Cattan, P. Modeling the spatial distribution of Chagas disease vectors using environmental variables and people's knowledge. International Journal of Health Geographics 12:29.","url":"","button":""},{"category":"internacionales","year":"2011","title":"","text":"Gallardo, C., Hernández, J., Diniz-Filho, J., Palma, J. & Méndez, M. Relationship between the genetic structure of the Andean toad Rhinella spinulosa (Anura: Bufonidae) and the northern Chile landscape (21°–24° S). Revista Chilena de Historia Natural 84(3):391-406.","url":"","button":""},{"category":"internacionales","year":"2011","title":"","text":"Dobbs, C., Hernández, J. & Escobedo, F. Above ground biomass and leaf area equations for urban trees of two communes in central Chile. Bosque 32(3):287-296.","url":"","button":""},{"category":"internacionales","year":"2011","title":"","text":"Acuña, M.P. & Estades, C.F. Plantation clearcut size and the persistence of early-successional wildlife populations. Biological Conservation 144(5):1577-1584.","url":"","button":""},{"category":"internacionales","year":"2010","title":"","text":"Emery, X. & Hernández, H.J. A computer package for modeling and simulating regionalized count variables. Computers & Geosciences 36(1):24-33.","url":"","button":""},{"category":"internacionales","year":"2009","title":"","text":"Hernández, H.J. & Emery, X. A geostatistical approach to optimize sampling designs for local forest inventories. Canadian Journal of Forest Research 39(8):1465–1474.","url":"","button":""},{"category":"internacionales","year":"2006","title":"","text":"Escobedo, F., Nowak, D., Wagner, D., De la Maza, C., Rodriguez, M., Crane, D. & Hernández, J. The socioeconomics and management of Santiago de Chile’s public urban forest. Urban Forestry & Urban Greening 4:105-114.","url":"","button":""},{"category":"internacionales","year":"2002","title":"","text":"Luz de la Maza, C.; Rodríguez, M.; Bown, H.; Hernández, J. & Escobedo, F. Vegetation diversity in the Santiago de Chile urban ecosystem. Arboricultural Journal 26(4):347-357.","url":"","button":""},{"category":"nacionales","year":"2014","title":"","text":"Valencia, D., Hernández, H.J., Fassnacht, F.E., Cortez, L., Lopatín, J. & Corvalán, P. Estimation of aerial biomass using discrete-wave LiDAR data in combination with different vegetation indices in plantations of Pinus radiata (D. Don), Región del Maule, Chile. Sustainability, Agri, Food and Environmental Research 2(3):30–49.","url":"","button":""},{"category":"nacionales","year":"2013","title":"","text":"Pavéz, M.I., Ferrando, F. & Hernández, J. Río Mapocho Tramo Inferior siglo XXI: avances para la definición y manejo de un polígono multifacético. Revista de Urbanismo N°28:79-93.","url":"","button":""},{"category":"nacionales","year":"2010","title":"","text":"Hernández, H.J. y Gutierrez, M. Patrones espaciales de expansión urbana de Santiago de Chile, período 1975-2003. Revista de Urbanismo.","url":"","button":""},{"category":"nacionales","year":"2008","title":"","text":"Hernández, H.J. La situación del arbolado urbano en Santiago. Revista de Urbanismo N°18, FAU, Universidad de Chile.","url":"","button":""},{"category":"nacionales","year":"2007","title":"","text":"Hernández, H.J. La situación del arbolado urbano de Santiago. Revista Ambiente Forestal 2(2).","url":"","button":""},{"category":"nacionales","year":"2004","title":"","text":"Lastra, O., Corvalán, P., Hernández, J. & Rodríguez, M. Evaluación nutricional de plantaciones de Pinus radiata D.Don en la VI Región. Boletín Nº20. Simposio de Residuos Orgánicos y su Uso en Sistemas Agroforestales, Sociedad Chilena de las Ciencias del Suelo, Universidad de la Frontera, pp.381-387.","url":"","button":""},{"category":"nacionales","year":"2004","title":"","text":"Hernández, J. & Corvalán, P. Conceptos y métodos para identificación y delimitación de árboles para pino radiata a partir de imágenes aéreas. Revista Mundo Forestal Nº3:42-44.","url":"","button":""},{"category":"nacionales","year":"2001","title":"","text":"Faúndez, L., Girón, G. & Hernández, J. Poblational caracterization of A. compacta Phil. (Llareta) on Cerros Pajonal-Llaretas System, El Loa, II Región, Chile. Gayana Botánica 58(1):47-101.","url":"","button":""},{"category":"nacionales","year":"2000","title":"","text":"Hernández, J. & Corvalán, P. Uso de variogramas para la determinación del tamaño medio de las copas de pinos. Ciencias Forestales 14(1-2):3-18.","url":"","button":""},{"category":"nacionales","year":"1999","title":"","text":"Hernández, J. Medición de fotografías aéreas usando fractales. Quebracho Nº7:61-66.","url":"","button":""},{"category":"nacionales","year":"1999","title":"","text":"Corvalán, P. & Hernández, J. Predicción del diámetro normal de individuos de pino insigne usando atributos locales de imágenes aéreas digitales. XIII Silvotecna, Concepción.","url":"","button":""},{"category":"libros","year":"2014","title":"","text":"Hernández, H.J. & Fuentes, T. La Región de Arica y Parinacota. En: Biodiversidad Terrestre de la Región de Arica y Parinacota, pp.14-21. Maval Ltda., Santiago, 412 pp.","url":"","button":""},{"category":"libros","year":"2014","title":"","text":"Hernández, H.J. & Acuña, M.P. Ambientes de la Región de Arica y Parinacota. En: Biodiversidad Terrestre de la Región de Arica y Parinacota, pp.114-121.","url":"","button":""},{"category":"libros","year":"2014","title":"","text":"Hernández, H.J., Estades, C., Faúndez, L. & Fuentes, T. Desafíos y Oportunidades. En: Biodiversidad Terrestre de la Región de Arica y Parinacota, pp.344-353.","url":"","button":""},{"category":"libros","year":"2014","title":"","text":"Lobos, G., Hernández, J. & Jaksic, F. Antropización del paisaje e invasión de vertebrados acuáticos exóticos. En: Invasiones Biológicas en Chile, Ediciones UC, pp.393–410.","url":"","button":""},{"category":"libros","year":"2013","title":"","text":"Hernández, J., Corvalán, P., Valencia, D., Cortés, L. & Acuña, M.P. Tecnología LiDAR en Evaluación y Monitoreo de Recursos Forestales. En: Geoinformación para el Ordenamiento Territorial, pp.158-167.","url":"","button":""},{"category":"libros","year":"2013","title":"","text":"De la Maza, C., Cerda, C., Hernández, H.J. y Rodríguez, M. Chile. En: Ecología Urbana: Experiencias en América Latina.","url":"","button":""},{"category":"libros","year":"2012","title":"","text":"Hernández, J., Corvalán, P., Emery, X., Peña, K. & Donoso, S. Geostatistical Estimation of Biomass Stock in Chilean Native Forests and Plantations. En: Remote Sensing of Biomass: Principles and Applications.","url":"","button":""},{"category":"libros","year":"2010","title":"","text":"Lobos, G., Hernández, H.J., Méndez, M., Cattan, P., Diniz-Filho, J. y Gallardo, C. Atlas de Biodiversidad de Anfibios y Reptiles de la Región Metropolitana de Chile: Una herramienta para la gestión de los recursos naturales. FPA, Ministerio del Medio Ambiente, 74 p.","url":"","button":""},{"category":"libros","year":"2009","title":"","text":"Hernández, H.J. & Montaner, D. Patrones de Respuesta Espectral (Cap. 3). En: Tecnologías Geoespaciales: Experiencias Aplicadas al Estudio y Gestión del Territorio.","url":"","button":""},{"category":"libros","year":"2008","title":"","text":"Hernández, H.J. & Montaner, D. Manual de ArcGIS 9.2. Tutoriales y base de datos. Publicaciones Docentes, Universidad de Chile, 101 págs.","url":"","button":""},{"category":"libros","year":"2007","title":"","text":"Hernández, P. Geomática Forestal. En: Biodiversidad: Manejo y Conservación de Recursos Forestales, pp.101-145.","url":"","button":""},{"category":"libros","year":"2007","title":"","text":"Hernández, J., Serra, M.T. & Araya, J. Manejo de Vegetación Urbana. En: Biodiversidad: Manejo y Conservación de Recursos Forestales, pp.693-719.","url":"","button":""},{"category":"libros","year":"2007","title":"","text":"Corvalán, P. & Hernández, J. Mediciones Forestales, Dendrometría. En: Biodiversidad: Manejo y Conservación de Recursos Forestales, pp.169-222.","url":"","button":""},{"category":"libros","year":"2007","title":"","text":"Corvalán, P. & Hernández, J. Dasometría. En: Biodiversidad: Manejo y Conservación de Recursos Forestales, pp.224-259.","url":"","button":""},{"category":"libros","year":"2006","title":"","text":"Velazquez, R., Hernández, J. & De la Maza, C. Evaluación de parches de áreas verdes en el paisaje urbano de Santiago de Chile: Aspectos de calidad ambiental. En actas del Segundo Seminario Internacional de Planificación Territorial y sus Efectos sobre Contaminación Ambiental.","url":"","button":""},{"category":"libros","year":"2005","title":"","text":"Aguilera, S., Rodriguez, M., Borie, G., Lastra, O., Hernández, J. & Corvalán, P. Desarrollo de Prácticas Sustentables de Aplicación de Biosólidos en Plantaciones Forestales. Pirámide Editions, Santiago de Chile, 205 p.","url":"","button":""},{"category":"libros","year":"2004","title":"","text":"Escobedo, F., Hernández, J., De la Maza, C.L., Rodríguez, M., Nowak, D. & Crane, D. Determinando los efectos del arbolado urbano sobre la calidad de aire. Publicaciones Misceláneas Nº5, Facultad de Cs. Forestales, U. de Chile, pp.19-25.","url":"","button":""},{"category":"libros","year":"2004","title":"","text":"Hernández, J., Bown, H., De la Maza, C.L. & Raby, D. La necesidad de inventariar el arbolado urbano: El caso de la Comuna de La Reina. Publicaciones Misceláneas Nº5, pp.26-49.","url":"","button":""},{"category":"libros","year":"2004","title":"","text":"Hernández, J., Escobedo, F., Pizarro, V. & De la Maza, C.L. Diagnóstico de la gestión municipal del arbolado urbano público en la ciudad de Santiago. Publicaciones Misceláneas Nº5, pp.141-164.","url":"","button":""},{"category":"libros","year":"2004","title":"","text":"Escobedo, F., Hernández, J., De la Maza, C.L., Rodríguez, M., Nowak, D. & Crane, D. El Arbolado urbano de Santiago de Chile. Publicaciones Misceláneas Nº5, pp.50-54.","url":"","button":""},{"category":"libros","year":"2004","title":"","text":"De la Maza, C.L., Hernández, J., Bown, H. & Rodríguez, M. Diversidad vegetacional en Santiago y su relación con niveles socioeconómicos. Publicaciones Misceláneas Nº5, pp.109-120.","url":"","button":""},{"category":"libros","year":"2004","title":"","text":"Lastra, O., Corvalán, P., Hernández, J. & Rodríguez, M. Evaluación nutricional de plantaciones de Pinus radiata D.Don en la VI Región. Boletín Nº20, Simposio Residuos Orgánicos y su uso en sistemas agroforestales, pp.381-387.","url":"","button":""},{"category":"libros","year":"2000","title":"","text":"Hernández, J. Manual de Métodos y Criterios para la Evaluación y Monitoreo de la Flora y la Vegetación. Manual para el Servicio Agrícola y Ganadero, Gobierno de Chile, 37 p.","url":"","button":""},{"category":"libros","year":"1999","title":"","text":"Hernández, J. Análisis de la variación espacial de pinares usando datos de campo, fotografías aéreas 1:6500 e imágenes SPOT HRV. Tesis Doctoral, Ingeniería de Montes, Universidad Politécnica de Madrid, España, 170 p.","url":"","button":""},{"category":"congresos","year":"2014","title":"","text":"Dobbs C., Hernández, H.J. & Cortés, L. Urbanization and its effect on carbon storage and diversity of the urban forest. MEDECOS XIII International Conference, Olmué, 6-9 Octubre 2014.","url":"","button":""},{"category":"congresos","year":"2014","title":"","text":"Bacigalupo A., Arroyo P., Hernández H.J. & Cattan P.E. Modelo predictivo a escala local de la distribución espacial de triatominos silvestres en Chile. III Encuentro Nacional de Investigación en Enfermedades Infecciosas y Medicina Tropical. Quito, Ecuador, 21-23 Julio 2014.","url":"","button":""},{"category":"congresos","year":"2014","title":"","text":"Corvalán P., Galleguillos M. & Hernández H.J. Impacto de la declinación de la precipitación en el crecimiento diametral del roble (Nothofagus obliqua) en la precordillera de Maule, Chile Central. International Expert Symposium Coping with Droughts, UNESCO, Santiago, 19-21 Noviembre 2014.","url":"","button":""},{"category":"congresos","year":"2014","title":"","text":"Fuentes-Castillo T., Moreira-Muñoz A. & Hernández, H.J. Distribución de especies de plantas vasculares pertenecientes a géneros endémicos del altiplano sudamericano y su representatividad en áreas silvestres protegidas en Chile. Puerto Varas, 24-28 Noviembre 2014.","url":"","button":""},{"category":"congresos","year":"2014","title":"","text":"Acuña M., Vukasovic M., Hernández H.J. & Estades C.F. Productividad primaria como factor modelador de la dinámica espacio-temporal de poblaciones de Gaviota de Franklin (Leucophaeus pipixcan) en Chile central. XI Congreso Chileno de Ornitología, La Serena, 13-17 Octubre 2014.","url":"","button":""},{"category":"congresos","year":"2014","title":"","text":"Vallejos G., Campos L., Garrido P., Hernández, H.J. & Gallardo, C. Primer registro de reproducción de Campephilus magellanicus en su límite norte de distribución, Chile central. XI Congreso Chileno de Ornitología, La Serena, 13-17 Octubre 2014.","url":"","button":""},{"category":"congresos","year":"2014","title":"","text":"Abarca, B.M., Magni, C.R. & Hernández, H.J. Modelación de la distribución potencial pasada, presente y futura de Quillaja saponaria Mol. basado en variables bioclimáticas. VI Congreso Chileno de Ciencias Forestales, Temuco, 9-11 Abril 2014.","url":"","button":""},{"category":"congresos","year":"2013","title":"","text":"Lopatin J., Hernández H.J., Galleguillos M. & Mancilla G. An empirical modeling of runoff in small watersheds using LiDAR data. AGU American Geophysical Union, San Francisco, USA, 9-13 Diciembre 2013.","url":"","button":""},{"category":"congresos","year":"2013","title":"","text":"Grez A.A., Zaviezo T., Rodríguez-San Pedro A., Hernández H.J. & Acuña, P. Effects of landscape composition and heterogeneity on the abundance of native coccinellids and biological control of aphids in alfalfa fields. 12th International Symposium Ecology of Aphidophaga, Belgrado, Serbia, 9-13 Septiembre 2013.","url":"","button":""},{"category":"congresos","year":"2013","title":"","text":"Grez, A.A., Zaviezo, T., Rodríguez, A., Hernández, H.J. & Acuña, M.P. Conservation of native coccinellids and biological control of aphids in alfalfa: a landscape perspective. Fourth International Symposium on Biological Control of Arthropods, Pucón, 4-8 Marzo 2013.","url":"","button":""},{"category":"congresos","year":"2013","title":"","text":"Faßnacht F., Hernández H.J., Corvalán P. & Koch B. Combination of EO1-Hyperion and LiDAR to estimate biomass in a highly complex second growth native forest in central Chile. Latin American Remote Sensing Week LARS 2013, Santiago, 23-25 Octubre 2013.","url":"","button":""},{"category":"congresos","year":"2013","title":"","text":"Ceballos A., Galleguillos M., Hernández H.J. Combining LiDAR topographic-structural information and Hyperspectral remote sensing to estimate plant vascular richness of deciduous mediterranean forests of central Chile. LARS 2013, Santiago, 23-25 Octubre 2013.","url":"","button":""},{"category":"congresos","year":"2013","title":"","text":"Cortés L., Valencia D., Hernández H.J. & Corvalán P. Utilidad de datos espectrales ETM+, ASTER GDEM y LiDAR en la estimación de Biomasa Aérea en plantaciones y bosques caducifolios mediterráneos. LARS 2013, Santiago, 23-25 Octubre 2013.","url":"","button":""},{"category":"congresos","year":"2013","title":"","text":"Mattar, C., Hernández, H.J., Santa María-Artigas, A., Durán-Alarcón, C., Olivera-Guerra, M., Inzunza, D., Tapia, D. & Escobar-Lavín, E. First in-flight absolute calibration of the Chilean Earth Observation Satellite. LARS 2013, Santiago, 23-25 Octubre 2013.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Hernández H.J., Núñez I., Bacigalupo A. & Cattan, P.E. Prediction of triatomines presence: using people's knowledge for Chagas disease risk assessment in Central Chile. Frontiers in Spatial Epidemiology, Londres, 5-6 Noviembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Estades C.F., Acuña M.P., González B., Hernández H.J., Villaseñor N. & Vukasovic M. Abundancia y distribución del Suri en el Norte de Chile. X Congreso Internacional de Manejo de Fauna Silvestre en la Amazonía y Latinoamérica, Salta, 14-18 Mayo 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Grez A.A., Zaviezo T., Rodríguez-San Pedro A., Hernández H.J. & Acuña P. Do the heterogeneity and composition of agricultural landscapes in Central Chile influence native and exotic coccinellids in alfalfa fields? IOBC/WPRS Working Group Landscape Management for Functional Biodiversity, Lleida, 7-10 Mayo 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"González R., Bacigalupo A., Hernández H.J. & Cattan P.E. Modelo de riesgo de infestación por triatominos basado en percepción de la población rural en Chile. III Congreso Internacional de Parasitología Neotropical, Lima, 5-11 Noviembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Bacigalupo A., Sandoval P., Escobar M., Hernández H.J. & Cattan, P.E. Investigaciones sobre la dispersión del roedor Octodon sp. en un área que cohabita con vectores de la Enfermedad de Chagas. III Congreso Internacional de Parasitología Neotropical, Lima, 5-11 Noviembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Cornejo B., Bacigalupo A., Hernández H.J. & Cattan P.E. Abundancias y prevalencias a Trypanosoma cruzi de triatominos y micro-mamíferos durante el invierno en Chile. III Congreso Internacional de Parasitología Neotropical, Lima, 5-11 Noviembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Bacigalupo A., Arroyo P., Hernández H.J. & Cattan, P.E. First capture of dispersal adults of Triatoma infestans and Mepraia spinolai by black light traps from sylvatic foci of Chile. II International Workshop on Chagas disease, Cochabamba, 17-20 Septiembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Bacigalupo A., Estadella V., Cornejo B., Hernández, H.J. & Cattan, P.E. Trypanosoma cruzi prevalence variation between summer and winter in the sylvatic vector Mepraia spinolai. II International Workshop on Chagas disease, Cochabamba, 17-20 Septiembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Grez, A.A., Zaviezo, T., Rodríguez, A., Castillo, F., Hernández, H.J. & Acuña, M.P. Heterogeneidad del paisaje agrícola y la composición, abundancia y distribución de coccinélidos nativos y exóticos en Chile central. Octavo Congreso Nacional de Entomología, Bariloche, 17-20 Abril 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Grez, A.A., Zaviezo, T., Rodríguez, A., Hernández, H.J. & Acuña, M.P. La composición del paisaje afecta el ensamble de coccinélidos nativos asociados a alfalfa en Chile central. XXXIV Congreso Nacional de Entomología, Santiago, 28-30 Noviembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Escanilla, V. & Hernández, H.J. Influencia de la escala de análisis en la detección de cambios en la fragmentación de paisajes. IUFRO Landscape Ecology Conference, Concepción, noviembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Bacigalupo A., Estadella V., Hernández H.J. & Cattan, P.E. Asociación entre la densidad relativa del vector silvestre de la enfermedad de Chagas, Mepraia spinolai, y su prevalencia a Trypanosoma cruzi. 17º Congreso Chileno de Medicina Veterinaria, Valdivia, 18-20 Noviembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Bacigalupo, A., Estadella, V., Cornejo, B., Mena, R., Hernández, H.J. y Cattan, P.E. Análisis preliminar de la distribución de vectores y reservorios de Trypanosoma cruzi en focos silvestres de la zona endémica de Chile. XIV Jornadas Anuales de Parasitología, Santiago, 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"González R., Bacigalupo A., Landaeta-Aqueveque C., Hernández, H.J. & Cattan, P.E. Conocimiento de la población rural de la enfermedad de Chagas y propuesta de modelos de riesgo de infestación e infección. Simposio Internacional Enfermedad de Chagas, Santiago, 13 Noviembre 2012.","url":"","button":""},{"category":"congresos","year":"2012","title":"","text":"Cornejo B., Bacigalupo A., Hernández H.J. & Cattan P.E. Evaluación de la abundancia y prevalencia de vectores y reservorios de la enfermedad de Chagas en época invernal. Simposio Internacional Enfermedad de Chagas, Santiago, 13 Noviembre 2012.","url":"","button":""}]}
JSON;
    $data = json_decode( $json, true );
    return gep_demo_normalize_publications_data( is_array( $data ) ? $data : array() );
}
function gep_demo_publications_json_meta( $post_id, $meta_key, $fallback ) {
    $json = get_post_meta( $post_id, $meta_key, true );
    if ( '' === $json ) return $fallback;
    $value = json_decode( $json, true );
    return is_array( $value ) ? $value : $fallback;
}
function gep_demo_publications_values( $post_id ) {
    $values=gep_demo_publications_defaults();
    if ( ! isset( $values['hero_image'] ) ) $values['hero_image']='';
    if ( ! isset( $values['hero_video'] ) ) $values['hero_video']='';
    $map=array('hero_eyebrow'=>'gep_publications_hero_eyebrow','hero_title'=>'gep_publications_hero_title','hero_text'=>'gep_publications_hero_text','hero_image'=>'gep_publications_hero_image','hero_video'=>'gep_publications_hero_video','nav_search'=>'gep_publications_nav_search','explorer_title'=>'gep_publications_explorer_title','note'=>'gep_publications_note');
    foreach($map as $key=>$meta_key)if(metadata_exists('post',$post_id,$meta_key))$values[$key]=get_post_meta($post_id,$meta_key,true);
    $sections=gep_demo_publications_json_meta($post_id,'gep_publications_sections_json',isset($values['sections'])?$values['sections']:array());
    $records=gep_demo_publications_json_meta($post_id,'gep_publications_records_json',isset($values['records'])?$values['records']:array());
    $values['sections']=$sections;$values['records']=$records;
    $values['note']=gep_demo_clean_internal_note(isset($values['note'])?$values['note']:'');
    return gep_demo_normalize_publications_data($values);
}
function gep_demo_is_publications_page( $post_id, $navigation = null ) {
    if ( ! $post_id ) return false;
    if ( null === $navigation ) $navigation = gep_demo_get_page_navigation();
    if ( ! empty( $navigation['publicaciones']['page'] ) && (int) $navigation['publicaciones']['page']->ID === (int) $post_id ) return true;
    if ( get_post_meta( $post_id, 'gep_publications_hero_title', true ) || get_post_meta( $post_id, 'gep_publications_records_json', true ) ) return true;
    $post = get_post( $post_id );
    return $post && in_array( gep_demo_normalize_page_name( $post->post_name ), array( 'publicaciones', 'publicacion' ), true );
}
function gep_demo_publications_public_url( $post_id = 0 ) {
    return gep_editor_public_page_url( 'publicaciones.php', $post_id );
}


/** Inicio / portada v8.5 */
function gep_demo_home_defaults() {
    return json_decode(<<<'JSON'
{
  "hero_eyebrow": "Universidad de Chile · Geomática · Ecología del paisaje",
  "hero_title": "Geomática para comprender el paisaje.",
  "hero_text": "Investigación y formación para leer el territorio, analizar sus transformaciones y conectar datos espaciales con procesos ecológicos.",
  "hero_image": "",
  "hero_primary_text": "Conocer el laboratorio ↓",
  "hero_primary_url": "#laboratorio",
  "hero_secondary_text": "Explorar proyectos",
  "hero_secondary_url": "proyectos.php",
  "hero_geo_a": "GEP / LANDSCAPE SYSTEMS",
  "hero_geo_b": "33° S · 70° W",
  "intro_eyebrow": "Laboratorio GEP",
  "intro_title": "Investigación del territorio desde la geomática y la ecología del paisaje.",
  "intro_p1": "El Laboratorio de Geomática y Ecología del Paisaje de la Facultad de Ciencias Forestales y Conservación de la Naturaleza, de la Universidad de Chile, es un laboratorio de investigación en donde comparten académicos y estudiantes.",
  "intro_p2": "Fue creado el 2005 por Cristián Estades M. y Jaime Hernández P. para ser una estación de investigación en geomática aplicada y ecología del paisaje, en la cual investigadores y estudiantes colaboran en proyectos específicos. Además, el GEP es responsable de un conjunto de cursos de pregrado y postgrado asociados.",
  "pills": [
    "Geomática",
    "Ecología del paisaje",
    "Teledetección",
    "SIG",
    "Análisis espacial",
    "Geoestadística"
  ],
  "stats": [
    {
      "value": "2005",
      "label": "Año de creación",
      "text": "Trayectoria del laboratorio"
    },
    {
      "value": "16",
      "label": "Proyectos registrados",
      "text": "Investigación aplicada 2006–2016"
    },
    {
      "value": "93",
      "label": "Registros de producción",
      "text": "Publicaciones y materiales reunidos"
    },
    {
      "value": "44",
      "label": "Personas registradas",
      "text": "Investigadores, estudiantes y colaboradores"
    }
  ],
  "definitions": [
    {
      "eyebrow": "Concepto clave",
      "title": "Geomática",
      "text": "Conjunto de ciencias que establecen las bases para la captura, tratamiento, análisis, interpretación, difusión y almacenamiento de información geográfica. Incluye elementos de geodesia, fotogrametría, sistemas de posicionamiento global, teledetección, tratamiento digital de imágenes, sistemas de información geográfica, análisis espacial y geoestadística, entre otras."
    },
    {
      "eyebrow": "Concepto clave",
      "title": "Ecología del Paisaje",
      "text": "Se dedica al estudio de la variación o heterogeneidad espacial de los paisajes que se pueden definir a diferentes escalas. Los paisajes contienen patrones espaciales —estructuras— relacionados con procesos ecológicos —funciones— en forma dinámica —cambios—, considerando las causas biofísicas y sociales de dicha heterogeneidad y sus consecuencias."
    }
  ],
  "field_image": "",
  "field_alt": "Vista aérea de un paisaje natural con extensa cobertura vegetal en flor",
  "field_index": "GEP / FIELD 01",
  "field_eyebrow": "Territorio en observación",
  "field_title": "El paisaje también se lee desde el terreno.",
  "field_text": "Registro visual incorporado como parte de la nueva identidad fotográfica del GEP.",
  "research_eyebrow": "Líneas de trabajo",
  "research_title": "Territorio, vegetación y biodiversidad.",
  "research_text": "Una mirada que integra cartografía, percepción remota, biodiversidad, biomasa, conservación, ecología urbana y planificación territorial.",
  "research_cards": [
    {
      "image": "",
      "alt": "Mapa temático de coberturas de suelo",
      "kicker": "01 · Geomática",
      "title": "Análisis espacial y territorial",
      "text": "Cartografía, SIG, geodesia, fotogrametría, geoestadística y bases de datos espaciales."
    },
    {
      "image": "",
      "alt": "Mapa de áreas urbanas y agropecuarias",
      "kicker": "02 · Sensores remotos",
      "title": "Teledetección y coberturas",
      "text": "Landsat, LiDAR, hiperespectral, radiometría de campo y tratamiento digital de imágenes."
    },
    {
      "image": "",
      "alt": "Paisaje montañoso chileno",
      "kicker": "03 · Ecología del paisaje",
      "title": "Patrones, procesos y conservación",
      "text": "Biodiversidad, conectividad, movimiento animal, cambio climático y planificación."
    }
  ],
  "portal_eyebrow": "Explorar GEP",
  "portal_title": "Una entrada clara a cada área del laboratorio.",
  "portal_text": "Docencia, investigación, producción científica, personas y contacto organizados en páginas independientes.",
  "portal_cards": [
    {
      "icon": "D",
      "title": "Docencia",
      "text": "2 cursos de pregrado y 5 registros de postgrado/postítulo.",
      "url": "docencia.php",
      "button": "Ver docencia →"
    },
    {
      "icon": "P",
      "title": "Proyectos",
      "text": "16 proyectos y estudios registrados entre 2006 y 2016.",
      "url": "proyectos.php",
      "button": "Ver proyectos →"
    },
    {
      "icon": "R",
      "title": "Publicaciones",
      "text": "Revistas internacionales, nacionales, libros, capítulos y congresos.",
      "url": "publicaciones.php",
      "button": "Ver publicaciones →"
    },
    {
      "icon": "Q",
      "title": "Quiénes somos",
      "text": "Investigadores, profesionales, estudiantes, colaboradores y becarios.",
      "url": "quienes-somos.php",
      "button": "Conocer el equipo →"
    },
    {
      "icon": "C",
      "title": "Contacto",
      "text": "Dirección, campus y vías de contacto del laboratorio.",
      "url": "contactanos.php",
      "button": "Ver contacto →"
    }
  ],
  "landscape_label": "Escala · procesos · cambio",
  "landscape_title": "Comprender los paisajes para entender sus transformaciones.",
  "landscape_image": "",
  "landscape_focus": "",
  "landscape_fit": "",
  "resources_eyebrow": "Plataformas y proyectos",
  "resources_title": "Recursos destacados del GEP.",
  "resources_text": "Una selección de plataformas, iniciativas y materiales vinculados a la trayectoria del laboratorio.",
  "resources": [
    {
      "kicker": "13–17 noviembre 2017",
      "title": "Taller de Google Earth Engine",
      "url": "https://www.gep.uchile.cl/Proyectos/Summer_School/index.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Proyecto / recurso GEP",
      "title": "ECOURBE",
      "url": "https://www.gep.uchile.cl/ecourbe/index.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Biblioteca espectral GEP",
      "title": "Firmas Espectrales",
      "url": "https://www.gep.uchile.cl/SPEC/firmas.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Cobertura de suelo",
      "title": "Landcover de Chile 2014",
      "url": "https://www.gep.uchile.cl/Landcover_CHILE.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Proyecto y publicación",
      "title": "Biodiversidad Terrestre de Arica y Parinacota",
      "url": "https://www.gep.uchile.cl/Biodiversidad/home.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Proyecto territorial",
      "title": "Piedemonte de Santiago (FPA)",
      "url": "https://www.gep.uchile.cl/Piedemonte_stgo/index.html",
      "button": "Abrir recurso ↗"
    }
  ],
  "topics_eyebrow": "Biblioteca temática",
  "topics_title": "Conocimiento organizado por áreas.",
  "topics_text": "Temas de apoyo a docencia e investigación que forman parte del repositorio recuperado del GEP.",
  "topics": [
    "Ambientes Urbanos",
    "Análisis Espacial",
    "Biodiversidad",
    "Biomasa y Carbono",
    "Ecología del Paisaje",
    "Estadística Espacial",
    "Fotogrametría",
    "Fundamentos de Teledetección",
    "Hiperespectral",
    "LiDAR",
    "Movimiento Animal",
    "Radar",
    "Radiometría de Campo",
    "Sensores Remotos",
    "Sistemas de Información Geográfica",
    "Sistemas de Coordenadas y Geodesia",
    "Softwares SIG y Teledetección",
    "Tratamiento Digital de Imágenes"
  ],
  "network_image": "",
  "network_alt": "MapBiomas Chile",
  "network_eyebrow": "Herramientas y datos",
  "network_title": "Herramientas y fuentes para el análisis geoespacial.",
  "network_text": "Software, sensores, herramientas de biomasa y fuentes de biodiversidad y clima vinculadas al ecosistema de trabajo del GEP.",
  "network_links": [
    {
      "title": "QGIS",
      "url": "https://www.qgis.org/"
    },
    {
      "title": "Landsat 8",
      "url": "https://landsat.gsfc.nasa.gov/satellites/landsat-8/"
    },
    {
      "title": "GlobAllomeTree",
      "url": "http://www.globallometree.org/"
    },
    {
      "title": "Datos climáticos GEP",
      "url": "https://www.gep.uchile.cl/Links/Datos_climaticos.html"
    },
    {
      "title": "InfoNatura / NatureServe",
      "url": "https://www.natureserve.org/"
    },
    {
      "title": "WorldClim",
      "url": "https://www.worldclim.org/"
    },
    {
      "title": "GBIF",
      "url": "https://www.gbif.org/"
    }
  ],
  "note": "",
  "cta_eyebrow": "Trayectoria y conocimiento",
  "cta_title": "Explora los proyectos y la producción científica del GEP.",
  "cta_button": "Ver proyectos",
  "cta_url": "proyectos.php"
}
JSON
    , true);
}
function gep_demo_home_json_meta( $post_id, $key, $fallback ) {
    $raw=get_post_meta($post_id,$key,true); if(''===$raw)return $fallback; $value=json_decode($raw,true); return is_array($value)?$value:$fallback;
}
function gep_demo_home_values( $post_id ) {
    $values=gep_demo_home_defaults();
    if(!isset($values['hero_video']))$values['hero_video']='';
    $map=array(
        'hero_eyebrow'=>'gep_home_hero_eyebrow','hero_title'=>'gep_home_hero_title','hero_text'=>'gep_home_hero_text','hero_image'=>'gep_home_hero_image','hero_video'=>'gep_home_hero_video','hero_primary_text'=>'gep_home_hero_primary_text','hero_primary_url'=>'gep_home_hero_primary_url','hero_secondary_text'=>'gep_home_hero_secondary_text','hero_secondary_url'=>'gep_home_hero_secondary_url','hero_geo_a'=>'gep_home_hero_geo_a','hero_geo_b'=>'gep_home_hero_geo_b',
        'intro_eyebrow'=>'gep_home_intro_eyebrow','intro_title'=>'gep_home_intro_title','intro_p1'=>'gep_home_intro_p1','intro_p2'=>'gep_home_intro_p2',
        'field_image'=>'gep_home_field_image','field_alt'=>'gep_home_field_alt','field_index'=>'gep_home_field_index','field_eyebrow'=>'gep_home_field_eyebrow','field_title'=>'gep_home_field_title','field_text'=>'gep_home_field_text',
        'research_eyebrow'=>'gep_home_research_eyebrow','research_title'=>'gep_home_research_title','research_text'=>'gep_home_research_text',
        'portal_eyebrow'=>'gep_home_portal_eyebrow','portal_title'=>'gep_home_portal_title','portal_text'=>'gep_home_portal_text',
        'landscape_label'=>'gep_home_landscape_label','landscape_title'=>'gep_home_landscape_title','landscape_image'=>'gep_home_landscape_image','landscape_focus'=>'gep_home_landscape_focus','landscape_fit'=>'gep_home_landscape_fit',
        'resources_eyebrow'=>'gep_home_resources_eyebrow','resources_title'=>'gep_home_resources_title','resources_text'=>'gep_home_resources_text',
        'topics_eyebrow'=>'gep_home_topics_eyebrow','topics_title'=>'gep_home_topics_title','topics_text'=>'gep_home_topics_text',
        'network_image'=>'gep_home_network_image','network_alt'=>'gep_home_network_alt','network_eyebrow'=>'gep_home_network_eyebrow','network_title'=>'gep_home_network_title','network_text'=>'gep_home_network_text',
        'note'=>'gep_home_note','cta_eyebrow'=>'gep_home_cta_eyebrow','cta_title'=>'gep_home_cta_title','cta_button'=>'gep_home_cta_button','cta_url'=>'gep_home_cta_url','hero_primary_off'=>'gep_home_hero_primary_off','hero_secondary_off'=>'gep_home_hero_secondary_off','cta_off'=>'gep_home_cta_off'
    );
    foreach($map as $k=>$meta)if(metadata_exists('post',$post_id,$meta))$values[$k]=get_post_meta($post_id,$meta,true);
    foreach(array('pills','stats','definitions','research_cards','portal_cards','resources','topics','network_links') as $key)$values[$key]=gep_demo_home_json_meta($post_id,'gep_home_'.$key.'_json',$values[$key]);
    $values['note']=gep_demo_clean_internal_note(isset($values['note'])?$values['note']:'');
    return $values;
}
function gep_demo_is_home_page( $post_id, $navigation=null ) {
    if(!$post_id)return false; if(null===$navigation)$navigation=gep_demo_get_page_navigation();
    if(!empty($navigation['inicio']['page']) && (int)$navigation['inicio']['page']->ID===(int)$post_id)return true;
    if(get_post_meta($post_id,'gep_home_hero_title',true)||get_post_meta($post_id,'gep_home_stats_json',true))return true;
    $front=(int)get_option('page_on_front'); if($front && $front===(int)$post_id)return true;
    $post=get_post($post_id); if(!$post)return false; $slug=gep_demo_normalize_page_name($post->post_name); return in_array($slug,array('inicio','home','portada'),true);
}
function gep_demo_home_public_url( $post_id = 0 ) {
    return gep_editor_public_page_url( 'index.php', $post_id );
}

function gep_demo_normalize_page_name( $value ) {
    return sanitize_title( remove_accents( (string) $value ) );
}

function gep_demo_get_page_navigation() {
    $pages = get_pages(
        array(
            'post_status' => array( 'publish', 'draft', 'private' ),
            'sort_column' => 'menu_order,post_title',
        )
    );

    $by_slug = array();
    foreach ( $pages as $page ) {
        $by_slug[ gep_demo_normalize_page_name( $page->post_name ) ]  = $page;
        $by_slug[ gep_demo_normalize_page_name( $page->post_title ) ] = $page;
    }

    $front_id = (int) get_option( 'page_on_front' );
    $specs    = array(
        'inicio'        => array( 'label' => 'Inicio', 'icon' => 'dashicons-admin-home', 'candidates' => array( 'inicio', 'home', 'portada' ) ),
        'quienes-somos' => array( 'label' => 'Quiénes somos', 'icon' => 'dashicons-groups', 'candidates' => array( 'quienes-somos', 'quienes-somos-2', 'nosotros' ) ),
        'docencia'      => array( 'label' => 'Docencia', 'icon' => 'dashicons-welcome-learn-more', 'candidates' => array( 'docencia' ) ),
        'proyectos'     => array( 'label' => 'Proyectos', 'icon' => 'dashicons-portfolio', 'candidates' => array( 'proyectos', 'proyecto' ) ),
        'servicios'     => array( 'label' => 'Servicios', 'icon' => 'dashicons-admin-tools', 'candidates' => array( 'servicios', 'servicio' ) ),
        'publicaciones' => array( 'label' => 'Publicaciones', 'icon' => 'dashicons-media-document', 'candidates' => array( 'publicaciones', 'publicacion' ) ),
        'comunicaciones'=> array( 'label' => 'Comunicaciones', 'icon' => 'dashicons-megaphone', 'candidates' => array( 'comunicaciones', 'comunicacion' ) ),
        'noticias'      => array( 'label' => 'Noticias', 'icon' => 'dashicons-welcome-write-blog', 'candidates' => array( 'noticias', 'noticia' ) ),
        'contacto'      => array( 'label' => 'Contacto', 'icon' => 'dashicons-email-alt', 'candidates' => array( 'contacto', 'contactanos', 'contactenos' ) ),
    );

    $result = array();
    foreach ( $specs as $key => $spec ) {
        $matched = null;

        if ( 'inicio' === $key && $front_id ) {
            $front = get_post( $front_id );
            if ( $front && 'page' === $front->post_type ) {
                $matched = $front;
            }
        }

        if ( ! $matched ) {
            foreach ( $spec['candidates'] as $candidate ) {
                $normalized = gep_demo_normalize_page_name( $candidate );
                if ( isset( $by_slug[ $normalized ] ) ) {
                    $matched = $by_slug[ $normalized ];
                    break;
                }
            }
        }

        $result[ $key ] = array(
            'label' => $spec['label'],
            'icon'  => $spec['icon'],
            'page'  => $matched,
        );
    }

    return $result;
}

function gep_demo_get_default_editor_page_id( $navigation ) {
    if ( ! empty( $navigation['inicio']['page'] ) ) {
        return (int) $navigation['inicio']['page']->ID;
    }

    $front_id = (int) get_option( 'page_on_front' );
    if ( $front_id && 'page' === get_post_type( $front_id ) ) {
        return $front_id;
    }

    if ( ! empty( $navigation['quienes-somos']['page'] ) ) {
        return (int) $navigation['quienes-somos']['page']->ID;
    }

    $pages = get_pages( array( 'number' => 1 ) );
    return $pages ? (int) $pages[0]->ID : 0;
}

function gep_demo_is_directory_page( $post_id, $navigation = null ) {
    if ( ! $post_id ) {
        return false;
    }

    if ( null === $navigation ) {
        $navigation = gep_demo_get_page_navigation();
    }

    if ( ! empty( $navigation['quienes-somos']['page'] ) && (int) $navigation['quienes-somos']['page']->ID === (int) $post_id ) {
        return true;
    }

    if ( get_post_meta( $post_id, 'gep_directory_title', true ) || get_post_meta( $post_id, 'gep_directory_sections_json', true ) || get_post_meta( $post_id, 'gep_team_title', true ) || get_post_meta( $post_id, 'gep_team_cards_json', true ) ) {
        return true;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        return false;
    }

    $slug = gep_demo_normalize_page_name( $post->post_name );
    return in_array( $slug, array( 'quienes-somos', 'quienes-somos-2', 'nosotros' ), true );
}

/**
 * --------------------------------------------------------------------------
 * Menú propio: Contenido GEP
 * --------------------------------------------------------------------------
 */

function gep_demo_register_admin_menu() {
    $menu_icon = plugins_url( 'assets/gep-menu-icon.png', __FILE__ );

    add_menu_page(
        'Contenido GEP',
        'Contenido GEP',
        'edit_pages',
        'gep-content-editor',
        'gep_demo_render_admin_page',
        $menu_icon,
        21
    );

    add_submenu_page(
        'gep-content-editor',
        'Todas las secciones',
        'Todas las secciones',
        'edit_pages',
        'gep-content-editor',
        'gep_demo_render_admin_page'
    );

    add_submenu_page(
        'gep-content-editor',
        'Historial de cambios',
        'Historial de cambios',
        'edit_pages',
        'gep-content-history',
        'gep_demo_render_history_page'
    );

    add_submenu_page(
        'gep-content-editor',
        'Ayuda',
        'Ayuda',
        'edit_pages',
        'gep-content-help',
        'gep_demo_render_help_page'
    );
}

add_action( 'admin_menu', 'gep_demo_register_admin_menu' );

/**
 * Mantiene el icono de Contenido GEP dentro del espacio estándar de WordPress.
 * El PNG del menú es independiente del logo grande usado dentro del editor.
 */
function gep_demo_admin_menu_icon_css() {
    echo '<style>#adminmenu .toplevel_page_gep-content-editor .wp-menu-image img{width:20px!important;height:20px!important;object-fit:contain;padding:7px 0 0!important;box-sizing:content-box}</style>';
}
add_action( 'admin_head', 'gep_demo_admin_menu_icon_css' );


function gep_demo_render_help_page() {
    $editor_url  = add_query_arg( array( 'page' => 'gep-content-editor' ), admin_url( 'admin.php' ) );
    $history_url = add_query_arg( array( 'page' => 'gep-content-history' ), admin_url( 'admin.php' ) );
    ?>
    <div class="wrap gep-help-wrap">
        <header class="gep-help-hero">
            <div class="gep-help-hero-copy">
                <span class="gep-help-kicker">GEP Editor · v9.10.2</span>
                <h1>Centro de ayuda</h1>
                <p>Lo necesario para editar, revisar y recuperar contenido sin tocar código.</p>
                <div class="gep-help-hero-actions">
                    <a class="button button-primary button-hero" href="<?php echo esc_url( $editor_url ); ?>"><span class="dashicons dashicons-edit"></span> Abrir editor</a>
                    <a class="button button-hero" href="<?php echo esc_url( $history_url ); ?>"><span class="dashicons dashicons-backup"></span> Abrir historial</a>
                </div>
            </div>
            <div class="gep-help-daily-flow" aria-label="Flujo diario recomendado">
                <span class="gep-help-flow-label">FLUJO DIARIO</span>
                <div class="gep-help-flow-row"><b>1</b><span>Edita</span><i></i><b>2</b><span>Preview</span><i></i><b>3</b><span>Guarda</span></div>
                <small>El historial se crea automáticamente al guardar.</small>
            </div>
        </header>

        <nav class="gep-help-jump" aria-label="Secciones de ayuda">
            <a href="#gep-start">Empezar</a>
            <a href="#gep-controls">Controles</a>
            <a href="#gep-history-help">Historial</a>
            <a href="#gep-lists">Listas</a>
            <a href="#gep-troubleshooting">Dudas rápidas</a>
        </nav>

        <section class="gep-help-section" id="gep-start">
            <div class="gep-help-section-head">
                <span class="gep-help-section-icon"><span class="dashicons dashicons-welcome-learn-more"></span></span>
                <div><span class="gep-help-eyebrow">EMPEZAR</span><h2>Editar una página en 4 pasos</h2></div>
            </div>
            <div class="gep-help-steps is-horizontal">
                <article><span class="gep-help-step-number">1</span><div><h3>Elige página</h3><p>Inicio, Quiénes somos, Docencia, Proyectos, Publicaciones o Contacto.</p></div></article>
                <article><span class="gep-help-step-number">2</span><div><h3>Edita</h3><p>Abre solo el apartado que necesitas y cambia sus campos.</p></div></article>
                <article><span class="gep-help-step-number">3</span><div><h3>Revisa</h3><p>Comprueba la preview en PC, tablet y móvil.</p></div></article>
                <article><span class="gep-help-step-number">4</span><div><h3>Guarda</h3><p>Pulsa Guardar cambios y verifica la página real.</p></div></article>
            </div>
            <div class="gep-help-page-chips" aria-label="Páginas disponibles">
                <span><i class="dashicons dashicons-admin-home"></i> Inicio</span>
                <span><i class="dashicons dashicons-groups"></i> Quiénes somos</span>
                <span><i class="dashicons dashicons-welcome-learn-more"></i> Docencia</span>
                <span><i class="dashicons dashicons-portfolio"></i> Proyectos</span>
                <span><i class="dashicons dashicons-media-document"></i> Publicaciones</span>
                <span><i class="dashicons dashicons-email-alt"></i> Contacto</span>
            </div>
        </section>

        <section class="gep-help-section" id="gep-controls">
            <div class="gep-help-section-head">
                <span class="gep-help-section-icon"><span class="dashicons dashicons-admin-settings"></span></span>
                <div><span class="gep-help-eyebrow">CONTROLES</span><h2>Qué hace cada botón</h2></div>
            </div>
            <div class="gep-help-control-grid is-compact">
                <article><span class="gep-help-control-sample is-green"><span class="dashicons dashicons-saved"></span> Guardar cambios</span><p>Publica lo editado y crea una versión en el historial.</p></article>
                <article><span class="gep-help-control-sample is-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span><p>Indica si existen cambios pendientes.</p></article>
                <article><span class="gep-help-control-sample"><span class="dashicons dashicons-undo"></span> Restaurar</span><p>Devuelve los campos al estado de referencia del editor.</p></article>
                <article><span class="gep-help-control-sample"><span class="dashicons dashicons-external"></span> Sitio público</span><p>En el menú lateral: abre la página publicada en otra pestaña.</p></article>
                <article><span class="gep-help-devices"><span>PC</span><span>Tablet</span><span>Móvil</span></span><p>Cambia únicamente el tamaño de la preview.</p></article>
                <article><span class="gep-help-control-sample"><span class="dashicons dashicons-editor-expand"></span> Expandir / contraer</span><p>Ordena el formulario sin alterar el contenido.</p></article>
            </div>
        </section>

        <section class="gep-help-section gep-help-history-section" id="gep-history-help">
            <div class="gep-help-section-head gep-help-history-head">
                <span class="gep-help-section-icon"><span class="dashicons dashicons-backup"></span></span>
                <div><span class="gep-help-eyebrow">HISTORIAL DE CAMBIOS</span><h2>Revisa qué cambió y vuelve atrás cuando lo necesites</h2><p>Cada guardado genera una versión recuperable con sección, fecha, usuario y diferencias.</p></div>
                <a class="button" href="<?php echo esc_url( $history_url ); ?>">Ir al historial <span class="dashicons dashicons-arrow-right-alt2"></span></a>
            </div>

            <div class="gep-help-history-flow" aria-label="Flujo del historial">
                <div><span class="dashicons dashicons-saved"></span><strong>Guardar</strong><small>Crea versión</small></div>
                <i class="dashicons dashicons-arrow-right-alt2"></i>
                <div><span class="dashicons dashicons-visibility"></span><strong>Revisar</strong><small>Antes / después</small></div>
                <i class="dashicons dashicons-arrow-right-alt2"></i>
                <div><span class="dashicons dashicons-randomize"></span><strong>Comparar</strong><small>Versión A / B</small></div>
                <i class="dashicons dashicons-arrow-right-alt2"></i>
                <div><span class="dashicons dashicons-undo"></span><strong>Restaurar</strong><small>Rollback seguro</small></div>
            </div>

            <div class="gep-help-history-main-grid">
                <article class="is-featured"><span class="dashicons dashicons-visibility"></span><div><strong>Ver cambios</strong><p>Muestra visualmente el valor anterior y el nuevo, incluidos textos, imágenes, enlaces y estructura.</p></div></article>
                <article class="is-featured"><span class="dashicons dashicons-undo"></span><div><strong>Rollback seguro</strong><p>Antes de restaurar una versión antigua, guarda automáticamente el estado actual como punto de seguridad.</p></div></article>
                <article><span class="dashicons dashicons-randomize"></span><div><strong>Comparar versiones</strong><p>Compara dos revisiones de la misma sección.</p></div></article>
                <article><span class="dashicons dashicons-yes-alt"></span><div><strong>Estado actual</strong><p>Marca la revisión que coincide con el contenido publicado.</p></div></article>
                <article><span class="dashicons dashicons-filter"></span><div><strong>Buscar y filtrar</strong><p>Texto, sección, acción, usuario y rango de fechas.</p></div></article>
                <article><span class="dashicons dashicons-edit-page"></span><div><strong>Notas</strong><p>Añade el motivo o contexto de una versión.</p></div></article>
                <article><span class="dashicons dashicons-lock"></span><div><strong>Proteger</strong><p>Evita que una versión importante entre en la limpieza automática.</p></div></article>
                <article><span class="dashicons dashicons-download"></span><div><strong>Exportar JSON</strong><p>Descarga una copia técnica independiente de cualquier revisión.</p></div></article>
            </div>

            <div class="gep-help-history-strip">
                <span><i class="dashicons dashicons-shield-alt"></i><b>SHA-256</b> verifica la integridad antes de restaurar.</span>
                <span><i class="dashicons dashicons-database"></i><b>100 versiones por sección</b> + todas las protegidas.</span>
                <span><i class="dashicons dashicons-flag"></i><b>4 tipos:</b> punto inicial, guardado, rollback y punto de seguridad.</span>
            </div>

            <div class="gep-help-history-safe"><span class="dashicons dashicons-shield"></span><div><strong>Restaurar no borra la historia.</strong><p>El estado que tenías antes del rollback queda guardado y también puede recuperarse.</p></div></div>
        </section>

        <section class="gep-help-section" id="gep-lists">
            <div class="gep-help-section-head">
                <span class="gep-help-section-icon"><span class="dashicons dashicons-list-view"></span></span>
                <div><span class="gep-help-eyebrow">LISTAS GRANDES</span><h2>Personas, proyectos y publicaciones</h2></div>
            </div>
            <div class="gep-help-list-actions">
                <div><span class="dashicons dashicons-search"></span><strong>Buscar</strong><small>Nombre, título o año.</small></div>
                <div><span class="dashicons dashicons-edit"></span><strong>Editar</strong><small>Abre solo un registro.</small></div>
                <div><span class="dashicons dashicons-category"></span><strong>Agrupar</strong><small>Crea grupos y arrastra personas entre ellos.</small></div>
                <div><span class="dashicons dashicons-admin-page"></span><strong>Duplicar</strong><small>Parte de un registro similar.</small></div>
                <div><span class="dashicons dashicons-move"></span><strong>Reordenar</strong><small>Arrastra cuando esté disponible.</small></div>
                <div><span class="dashicons dashicons-trash"></span><strong>Eliminar</strong><small>Revisa la preview antes de guardar.</small></div>
            </div>
        </section>

        <section class="gep-help-section" id="gep-troubleshooting">
            <div class="gep-help-section-head">
                <span class="gep-help-section-icon"><span class="dashicons dashicons-sos"></span></span>
                <div><span class="gep-help-eyebrow">DUDAS RÁPIDAS</span><h2>Si algo no sale como esperabas</h2></div>
            </div>
            <div class="gep-help-faq">
                <details><summary>Guardar cambios está desactivado</summary><p>Normalmente no hay cambios pendientes o falta completar un campo obligatorio.</p></details>
                <details><summary>Guardé algo incorrecto</summary><p>Abre Historial de cambios, revisa la versión anterior y usa Restaurar. Se creará un punto de seguridad antes del rollback.</p></details>
                <details><summary>La preview y la web pública no se ven igual</summary><p>La comprobación final siempre es «Sitio público» (menú lateral). Guarda primero y revisa el resultado publicado.</p></details>
                <details><summary>No encuentro un registro</summary><p>Limpia el buscador y los filtros activos de la lista.</p></details>
            </div>
        </section>

        <footer class="gep-help-footer">
            <div><span class="dashicons dashicons-lock"></span><p><strong>Contenido editable, diseño protegido.</strong> El editor trabaja sobre los datos del sitio sin obligarte a tocar código.</p></div>
            <span class="gep-help-version">GEP Editor Beta <?php echo esc_html( GEP_EDITOR_VERSION ); ?></span>
        </footer>
    </div>
    <?php
}

/**
 * --------------------------------------------------------------------------
 * Pequeña puerta de entrada desde la edición normal de páginas
 * --------------------------------------------------------------------------
 */

function gep_demo_add_meta_box() {
    add_meta_box(
        'gep_demo_editor',
        'Contenido GEP',
        'gep_demo_render_meta_box_launcher',
        'page',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'gep_demo_add_meta_box' );

function gep_demo_render_meta_box_launcher( $post ) {
    $url = add_query_arg(
        array(
            'page'    => 'gep-content-editor',
            'post_id' => $post->ID,
        ),
        admin_url( 'admin.php' )
    );
    ?>
    <div style="padding:4px 0 2px">
        <p style="margin-top:0">Edita el contenido mediante el cuestionario visual del GEP.</p>
        <a href="<?php echo esc_url( $url ); ?>" class="button button-primary" style="width:100%;text-align:center">Abrir Contenido GEP</a>
        <p class="description" style="margin-bottom:0;margin-top:10px">No necesitas editar código ni tocar el diseño de la página.</p>
    </div>
    <?php
}

/**
 * --------------------------------------------------------------------------
 * Controles reutilizables
 * --------------------------------------------------------------------------
 */

function gep_demo_render_image_control( $name, $url, $button_text ) {
    ?>
    <div class="gep-image-control">
        <input type="hidden" class="gep-image-url" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_url( $url ); ?>">
        <div class="gep-image-actions">
            <button type="button" class="button gep-select-image"><span class="dashicons dashicons-format-image"></span><?php echo esc_html( $button_text ); ?></button>
            <button type="button" class="button-link-delete gep-remove-image" <?php echo $url ? '' : 'style="display:none"'; ?>>Quitar imagen</button>
        </div>
        <div class="gep-image-preview" <?php echo $url ? '' : 'style="display:none"'; ?>>
            <img src="<?php echo esc_url( $url ); ?>" alt="Vista previa">
        </div>
    </div>
    <?php
}

/**
 * Selector reutilizable de video para cabeceras.
 * El video tiene prioridad sobre la imagen cuando ambos están configurados.
 */
function gep_demo_render_video_control( $name, $url, $button_text = 'Seleccionar / cambiar video' ) {
    ?>
    <div class="gep-video-control">
        <input type="hidden" class="gep-video-url" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_url( $url ); ?>">
        <div class="gep-video-actions">
            <button type="button" class="button gep-select-video"><span class="dashicons dashicons-video-alt3"></span><?php echo esc_html( $button_text ); ?></button>
            <button type="button" class="button-link-delete gep-remove-video" <?php echo $url ? '' : 'style="display:none"'; ?>>Quitar video</button>
        </div>
        <div class="gep-video-preview" <?php echo $url ? '' : 'style="display:none"'; ?>>
            <video src="<?php echo $url ? esc_url( $url ) . '#t=0.1' : ''; ?>" muted playsinline preload="metadata" controls></video>
        </div>
        <p class="description">Si hay video, reemplaza visualmente la imagen de cabecera. La imagen queda como respaldo/poster.</p>
    </div>
    <?php
}

function gep_demo_type_label( $type ) {
    $labels = array(
        'people' => 'Personas',
        'status' => 'Estados / tesis',
        'photos' => 'Tarjetas con foto',
    );
    return isset( $labels[ $type ] ) ? $labels[ $type ] : 'Personas';
}

function gep_demo_render_team_card( $card_key, $card = array() ) {
    $card   = is_array( $card ) ? $card : array();
    $title  = isset( $card['title'] ) && '' !== $card['title'] ? $card['title'] : 'Nueva tarjeta';
    $kicker = isset( $card['kicker'] ) ? $card['kicker'] : 'GEP · EQUIPO';
    $badge  = isset( $card['badge'] ) ? $card['badge'] : 'IMAGEN REFERENCIAL';
    $text   = isset( $card['text'] ) ? $card['text'] : '';
    $image  = isset( $card['image'] ) ? $card['image'] : '';
    ?>
    <details class="gep-team-card-row" data-card-key="<?php echo esc_attr( $card_key ); ?>">
        <summary>
            <span class="gep-grip dashicons dashicons-menu-alt2"></span>
            <span class="gep-item-summary-main">
                <strong class="gep-team-card-summary-title"><?php echo esc_html( $title ); ?></strong>
                <?php if ( $badge ) : ?><span class="gep-item-summary-badge"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
            </span>
            <span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span>
        </summary>
        <div class="gep-item-content">
            <div class="gep-item-topline">
                <div class="gep-card-order-actions" aria-label="Cambiar orden">
                    <button type="button" class="button button-small gep-team-move-up"><span class="dashicons dashicons-arrow-up-alt2"></span> Subir</button>
                    <button type="button" class="button button-small gep-team-move-down"><span class="dashicons dashicons-arrow-down-alt2"></span> Bajar</button>
                    <button type="button" class="button button-small gep-duplicate-team-card"><span class="dashicons dashicons-admin-page"></span> Duplicar</button>
                </div>
                <button type="button" class="button-link-delete gep-remove-team-card"><span class="dashicons dashicons-trash"></span> Eliminar tarjeta</button>
            </div>

            <div class="gep-admin-field compact">
                <span class="gep-field-label">Imagen de la tarjeta <span class="gep-optional">(opcional)</span></span>
                <p class="gep-muted">Selecciona una fotografía desde la biblioteca de medios. Si está vacía, la página mostrará el marcador referencial.</p>
                <?php gep_demo_render_image_control( 'gep_team_cards[' . $card_key . '][image]', $image, 'Seleccionar / cambiar imagen' ); ?>
            </div>

            <div class="gep-grid-2">
                <label>Etiqueta superior
                    <input type="text" name="gep_team_cards[<?php echo esc_attr( $card_key ); ?>][kicker]" value="<?php echo esc_attr( $kicker ); ?>" placeholder="GEP · EQUIPO">
                </label>
                <label>Etiqueta sobre la imagen
                    <input type="text" name="gep_team_cards[<?php echo esc_attr( $card_key ); ?>][badge]" value="<?php echo esc_attr( $badge ); ?>" placeholder="IMAGEN REFERENCIAL">
                </label>
            </div>
            <label>Título
                <input class="gep-team-card-title-input" type="text" name="gep_team_cards[<?php echo esc_attr( $card_key ); ?>][title]" value="<?php echo esc_attr( isset( $card['title'] ) ? $card['title'] : '' ); ?>" placeholder="Ej: Geomática y paisaje">
            </label>
            <label>Descripción
                <textarea name="gep_team_cards[<?php echo esc_attr( $card_key ); ?>][text]" placeholder="Ej: Análisis espacial · Ecología del paisaje"><?php echo esc_textarea( $text ); ?></textarea>
            </label>
        </div>
    </details>
    <?php
}

function gep_demo_render_item( $section_key, $item_key, $type, $item = array() ) {
    $item  = is_array( $item ) ? $item : array();
    $name  = isset( $item['name'] ) && '' !== $item['name'] ? $item['name'] : 'Nuevo elemento';
    $badge = '';
    if ( 'people' === $type && ( ! empty( $item['specialization'] ) || ! empty( $item['meta'] ) ) ) {
        $badge = ! empty( $item['specialization'] ) ? $item['specialization'] : $item['meta'];
    } elseif ( 'status' === $type && ! empty( $item['status'] ) ) {
        $badge = $item['status'];
    } elseif ( 'photos' === $type && ! empty( $item['label'] ) ) {
        $badge = $item['label'];
    }
    ?>
    <div class="gep-directory-list-row" data-item-key="<?php echo esc_attr( $item_key ); ?>">
        <span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar" aria-hidden="true"></span>
        <button type="button" class="gep-directory-list-open" aria-label="Editar elemento">
            <strong class="gep-item-summary-name"><?php echo esc_html( $name ); ?></strong>
            <span class="gep-item-summary-badge" <?php echo $badge ? '' : 'style="display:none"'; ?>><?php echo esc_html( $badge ); ?></span>
        </button>
        <span class="dashicons dashicons-arrow-right-alt2 gep-directory-list-arrow" aria-hidden="true"></span>
    </div>
    <?php
}

function gep_demo_render_section( $section_key, $section ) {
    $section = is_array( $section ) ? $section : array();
    $type    = isset( $section['type'] ) ? $section['type'] : 'people';
    if ( ! in_array( $type, array( 'people', 'status', 'photos' ), true ) ) {
        $type = 'people';
    }
    $title  = isset( $section['title'] ) ? $section['title'] : '';
    $open   = ! empty( $section['open'] );
    $items  = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();
    $groups = isset( $section['groups'] ) && is_array( $section['groups'] ) ? $section['groups'] : array();

    // Compatibilidad: los sitios anteriores a v9.6 solo guardaban el nombre
    // del grupo dentro de cada persona. Los incorporamos al catálogo de grupos
    // para que aparezcan automáticamente en el nuevo gestor visual.
    if ( in_array( $type, array( 'people', 'status' ), true ) ) {
        $known_groups = array();
        foreach ( $groups as $group_name ) {
            $group_name = trim( (string) $group_name );
            if ( '' === $group_name ) {
                continue;
            }
            $key = gep_demo_directory_lookup_key( $group_name );
            if ( isset( $known_groups[ $key ] ) ) {
                continue;
            }
            $known_groups[ $key ] = true;
        }
        foreach ( $items as $item ) {
            $group_name = is_array( $item ) && isset( $item['group'] ) ? trim( (string) $item['group'] ) : '';
            if ( '' === $group_name ) {
                continue;
            }
            $key = gep_demo_directory_lookup_key( $group_name );
            if ( isset( $known_groups[ $key ] ) ) {
                continue;
            }
            $groups[] = $group_name;
            $known_groups[ $key ] = true;
        }
    }
    ?>
    <div class="gep-section-builder" data-section-key="<?php echo esc_attr( $section_key ); ?>" data-type="<?php echo esc_attr( $type ); ?>">
        <div class="gep-section-head">
            <div class="gep-section-head-main">
                <span class="gep-section-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span>
                <span class="gep-type-chip"><?php echo esc_html( gep_demo_type_label( $type ) ); ?></span>
                <strong class="gep-section-name"><?php echo esc_html( $title ? $title : 'Nuevo apartado' ); ?></strong>
                <span class="gep-section-count"><?php echo esc_html( count( $items ) ); ?> elemento<?php echo 1 === count( $items ) ? '' : 's'; ?></span>
            </div>
            <div class="gep-section-actions"><button type="button" class="button button-small gep-duplicate-section"><span class="dashicons dashicons-admin-page"></span> Duplicar</button><button type="button" class="button-link-delete gep-remove-section"><span class="dashicons dashicons-trash"></span> Eliminar apartado</button></div>
        </div>

        <input type="hidden" name="gep_sections[<?php echo esc_attr( $section_key ); ?>][type]" value="<?php echo esc_attr( $type ); ?>">

        <div class="gep-grid-2 gep-section-settings">
            <label>Título del apartado
                <input type="text" class="gep-section-title-input" name="gep_sections[<?php echo esc_attr( $section_key ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" placeholder="Ej: Investigadores">
            </label>
            <label class="gep-toggle-line">
                <input type="checkbox" name="gep_sections[<?php echo esc_attr( $section_key ); ?>][open]" value="1" <?php checked( $open ); ?>>
                <span>Mostrar abierto por defecto</span>
            </label>
        </div>

        <div class="gep-directory-item-toolbar">
            <label class="gep-directory-item-search"><span class="dashicons dashicons-search"></span><input type="search" class="gep-directory-search-input" placeholder="Buscar en este grupo…"></label>
            <span class="gep-directory-search-count"><?php echo esc_html( count( $items ) ); ?> elementos</span>
        </div>
        <div class="gep-items" data-items-for="<?php echo esc_attr( $section_key ); ?>">
            <?php
            foreach ( $items as $item_key => $item ) {
                gep_demo_render_item( $section_key, $item_key, $type, $item );
            }
            ?>
        </div>
        <div class="gep-directory-editor-parking"></div>

        <button type="button" class="button gep-add-item"><span class="dashicons dashicons-plus-alt2"></span> Añadir elemento</button>
    </div>
    <?php
}

/**
 * --------------------------------------------------------------------------
 * Pantalla principal del editor guiado
 * --------------------------------------------------------------------------
 */


function gep_demo_render_contact_row( $row_key, $row = array() ) {
    $row   = is_array( $row ) ? $row : array();
    $kind  = isset( $row['kind'] ) && in_array( $row['kind'], array( 'text', 'email', 'tel', 'url' ), true ) ? $row['kind'] : 'text';
    $label = isset( $row['label'] ) ? $row['label'] : '';
    $value = isset( $row['value'] ) ? $row['value'] : '';
    ?>
    <details class="gep-item-row gep-contact-row" data-contact-key="<?php echo esc_attr( $row_key ); ?>">
        <summary>
            <span class="gep-grip dashicons dashicons-menu-alt2"></span>
            <span class="gep-item-summary-main">
                <strong class="gep-contact-summary-label"><?php echo esc_html( $label ? $label : 'Nuevo dato' ); ?></strong>
                <span class="gep-item-summary-badge gep-contact-summary-value"><?php echo esc_html( $value ); ?></span>
            </span>
            <span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span>
        </summary>
        <div class="gep-item-content">
            <div class="gep-item-topline">
                <div class="gep-card-order-actions" aria-label="Acciones del dato">
                    <button type="button" class="button button-small gep-contact-move-up"><span class="dashicons dashicons-arrow-up-alt2"></span> Subir</button>
                    <button type="button" class="button button-small gep-contact-move-down"><span class="dashicons dashicons-arrow-down-alt2"></span> Bajar</button>
                    <button type="button" class="button button-small gep-duplicate-contact-row"><span class="dashicons dashicons-admin-page"></span> Duplicar</button>
                </div>
                <button type="button" class="button-link-delete gep-remove-contact-row"><span class="dashicons dashicons-trash"></span> Eliminar dato</button>
            </div>
            <div class="gep-grid-2">
                <label>Etiqueta
                    <input class="gep-contact-label-input" type="text" name="gep_contact_rows[<?php echo esc_attr( $row_key ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" placeholder="Ej: Teléfono">
                </label>
                <label>Tipo de dato
                    <select name="gep_contact_rows[<?php echo esc_attr( $row_key ); ?>][kind]">
                        <option value="text" <?php selected( $kind, 'text' ); ?>>Texto normal</option>
                        <option value="email" <?php selected( $kind, 'email' ); ?>>Correo electrónico</option>
                        <option value="tel" <?php selected( $kind, 'tel' ); ?>>Teléfono</option>
                        <option value="url" <?php selected( $kind, 'url' ); ?>>Enlace web</option>
                    </select>
                </label>
            </div>
            <label>Contenido
                <input class="gep-contact-value-input" type="text" name="gep_contact_rows[<?php echo esc_attr( $row_key ); ?>][value]" value="<?php echo esc_attr( $value ); ?>" placeholder="Información que aparecerá en la página">
            </label>
        </div>
    </details>
    <?php
}

/**
 * Barra común de control de apartados.
 *
 * Se renderiza desde PHP para evitar que el editor cambie de estructura
 * visual después del primer pintado del navegador.
 */
function gep_demo_render_section_visibility_tools() {
    ?>
    <div class="gep-section-visibility-tools">
        <div>
            <span class="dashicons dashicons-list-view"></span>
            <strong>Apartados editables</strong>
            <small>Abre solo la parte que necesitas o controla todas de una vez.</small>
        </div>
        <div class="gep-section-visibility-actions">
            <button type="button" class="button button-small gep-collapse-all-steps"><span class="dashicons dashicons-arrow-up-alt2"></span> Contraer todo</button>
            <button type="button" class="button button-small gep-expand-all-steps"><span class="dashicons dashicons-arrow-down-alt2"></span> Expandir todo</button>
        </div>
    </div>
    <?php
}

function gep_demo_render_contact_editor( $post_id, $values ) {
    $public_url = gep_demo_contact_public_url( $post_id );
    $defaults   = gep_demo_contact_defaults();
    $reset_data = array(
        'fields' => array(
            '#gep_contact_hero_eyebrow'      => $defaults['hero_eyebrow'],
            '#gep_contact_hero_title'        => $defaults['hero_title'],
            '#gep_contact_hero_text'         => $defaults['hero_text'],
            '#gep_contact_card_eyebrow'      => $defaults['card_eyebrow'],
            '#gep_contact_card_title'        => $defaults['card_title'],
            '#gep_contact_primary_email'     => $defaults['primary_email'],
            '#gep_contact_email_button_text' => $defaults['email_button_text'],
            '#gep_contact_map_button_text'   => $defaults['map_button_text'],
            '#gep_contact_map_url'           => $defaults['map_url'],
            '#gep_contact_location_eyebrow'  => $defaults['location_eyebrow'],
            '#gep_contact_location_title'    => $defaults['location_title'],
            '#gep_contact_location_text'     => $defaults['location_text'],
            '#gep_contact_note'              => $defaults['note'],
            '#gep_contact_cta_title'         => $defaults['cta_title'],
            '#gep_contact_cta_button_text'   => $defaults['cta_button_text'],
        ),
        'rows' => $defaults['rows'],
    );
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gep-content-form" novalidate>
        <input type="hidden" name="action" value="gep_demo_save_content">
        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
        <input type="hidden" name="gep_editor_context" value="contact">
        <?php wp_nonce_field( 'gep_demo_save_content_' . $post_id, 'gep_demo_admin_nonce' ); ?>

        <div class="gep-contact-commandbar">
            <div class="gep-contact-commandbar-copy">
                <span class="gep-contact-command-icon"><span class="dashicons dashicons-email-alt"></span></span>
                <div><strong>Editor de Contacto</strong><small>Edita, comprueba la preview y publica cuando esté listo.</small></div>
                <span class="gep-inline-save-state is-clean" id="gep-contact-inline-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span>
            </div>
            <div class="gep-contact-commandbar-actions">
                <button type="button" class="button gep-restore-contact"><span class="dashicons dashicons-image-rotate"></span> Restaurar original</button>
                <?php /* v9.25.0: "Ver página real" se quitó de esta barra; el acceso queda en "Sitio público" del menú lateral. */ ?>
                <button type="submit" class="button button-primary gep-save-button" disabled><span class="dashicons dashicons-saved"></span> Guardar cambios</button>
            </div>
        </div>

        <div class="gep-editor-layout gep-editor-layout-contact">
            <div class="gep-questionnaire">
                <?php gep_demo_render_section_visibility_tools(); ?>
                <section class="gep-step-card" data-contact-preview="hero">
                    <div class="gep-step-heading">
                        <span class="gep-step-number">1</span>
                        <div><h3>Encabezado de Contacto</h3><p>Edita los textos del bloque superior de la página sin modificar su diseño.</p></div>
                    </div>
                    <div class="gep-admin-field"><label for="gep_contact_hero_eyebrow">Texto superior</label><input type="text" id="gep_contact_hero_eyebrow" name="gep_contact_hero_eyebrow" value="<?php echo esc_attr( $values['hero_eyebrow'] ); ?>"></div>
                    <div class="gep-admin-field"><label for="gep_contact_hero_title">Título principal <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_contact_hero_title" name="gep_contact_hero_title" value="<?php echo esc_attr( $values['hero_title'] ); ?>" data-gep-required="1"></div>
                    <div class="gep-admin-field"><label for="gep_contact_hero_text">Texto descriptivo</label><textarea id="gep_contact_hero_text" name="gep_contact_hero_text"><?php echo esc_textarea( $values['hero_text'] ); ?></textarea></div><div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen hero / cabecera</span><?php gep_demo_render_image_control( 'gep_contact_hero_image', $values['hero_image'], 'Seleccionar / cambiar imagen hero' ); ?></div><div class="gep-admin-field gep-media-field gep-hero-video-field"><span class="gep-field-label">Video hero / cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_video_control( 'gep_contact_hero_video', isset($values['hero_video'])?$values['hero_video']:'', 'Seleccionar / cambiar video hero' ); ?></div>
                </section>

                <section class="gep-step-card" data-contact-preview="card">
                    <div class="gep-step-heading">
                        <span class="gep-step-number">2</span>
                        <div><h3>Ficha de contacto</h3><p>Administra la información institucional que aparece en la tarjeta principal.</p></div>
                    </div>
                    <div class="gep-grid-2">
                        <div class="gep-admin-field"><label for="gep_contact_card_eyebrow">Texto superior</label><input type="text" id="gep_contact_card_eyebrow" name="gep_contact_card_eyebrow" value="<?php echo esc_attr( $values['card_eyebrow'] ); ?>"></div>
                        <div class="gep-admin-field"><label for="gep_contact_card_title">Título de la tarjeta <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_contact_card_title" name="gep_contact_card_title" value="<?php echo esc_attr( $values['card_title'] ); ?>" data-gep-required="1"></div>
                    </div>
                    <div class="gep-grid-2">
                        <div class="gep-admin-field"><label for="gep_contact_primary_email">Correo principal <span class="gep-required-dot">Obligatorio</span></label><input type="email" id="gep_contact_primary_email" name="gep_contact_primary_email" value="<?php echo esc_attr( $values['primary_email'] ); ?>" data-gep-required="1"><p class="description">Se usa en los botones de contacto de la página.</p></div>
                        <div class="gep-admin-field"><label for="gep_contact_email_button_text">Texto del botón de correo</label><input type="text" id="gep_contact_email_button_text" name="gep_contact_email_button_text" value="<?php echo esc_attr( $values['email_button_text'] ); ?>"></div>
                    </div>

                    <div class="gep-team-builder-head">
                        <div><strong>Datos mostrados en la ficha</strong><p class="gep-muted">Arrastra usando ☰ para ordenar. También puedes duplicar, editar o eliminar cada fila.</p></div>
                        <button type="button" class="button gep-add-contact-row"><span class="dashicons dashicons-plus-alt2"></span> Añadir dato</button>
                    </div>
                    <div class="gep-items gep-sortable-contact" id="gep-contact-rows">
                        <?php foreach ( $values['rows'] as $row_key => $row ) { gep_demo_render_contact_row( $row_key, $row ); } ?>
                    </div>
                    <div class="gep-empty-builder <?php echo $values['rows'] ? 'is-hidden' : ''; ?>" id="gep-contact-empty">
                        <span class="dashicons dashicons-id"></span><strong>No hay datos de contacto.</strong><span>Pulsa “Añadir dato” para crear una fila.</span>
                    </div>
                </section>

                <section class="gep-step-card" data-contact-preview="location">
                    <div class="gep-step-heading">
                        <span class="gep-step-number">3</span>
                        <div><h3>Ubicación</h3><p>Edita el bloque visual del Campus Antumapu y el enlace externo al mapa.</p></div>
                    </div>
                    <div class="gep-grid-2">
                        <div class="gep-admin-field"><label for="gep_contact_location_eyebrow">Etiqueta superior</label><input type="text" id="gep_contact_location_eyebrow" name="gep_contact_location_eyebrow" value="<?php echo esc_attr( $values['location_eyebrow'] ); ?>"></div>
                        <div class="gep-admin-field"><label for="gep_contact_location_title">Nombre del lugar <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_contact_location_title" name="gep_contact_location_title" value="<?php echo esc_attr( $values['location_title'] ); ?>" data-gep-required="1"></div>
                    </div>
                    <div class="gep-admin-field"><label for="gep_contact_location_text">Texto inferior</label><input type="text" id="gep_contact_location_text" name="gep_contact_location_text" value="<?php echo esc_attr( $values['location_text'] ); ?>"></div>
                    <div class="gep-grid-2">
                        <div class="gep-admin-field"><label for="gep_contact_map_button_text">Texto del botón de mapa</label><input type="text" id="gep_contact_map_button_text" name="gep_contact_map_button_text" value="<?php echo esc_attr( $values['map_button_text'] ); ?>"></div>
                        <div class="gep-admin-field"><label for="gep_contact_map_url">Enlace del mapa</label><input type="url" id="gep_contact_map_url" name="gep_contact_map_url" value="<?php echo esc_attr( $values['map_url'] ); ?>"></div>
                    </div>
                    <div class="gep-admin-field compact gep-media-field">
                        <span class="gep-field-label">Imagen del mapa <span class="gep-optional">(opcional)</span></span>
                        <p class="gep-muted">Usa la biblioteca multimedia de WordPress. Si no eliges ninguna, el bloque se mostrará sin imagen.</p>
                        <?php gep_demo_render_image_control( 'gep_contact_map_image_url', $values['map_image_url'], 'Seleccionar / cambiar imagen' ); ?>
                    </div>
                </section>

                <section class="gep-step-card" data-contact-preview="cta">
                    <div class="gep-step-heading">
                        <span class="gep-step-number">4</span>
                        <div><h3>Nota y llamado final</h3><p>Controla el aviso informativo y la franja final de contacto.</p></div>
                    </div>
                    <div class="gep-admin-field"><label for="gep_contact_note">Nota informativa</label><textarea id="gep_contact_note" name="gep_contact_note"><?php echo esc_textarea( $values['note'] ); ?></textarea><p class="description">Si lo dejas vacío, la nota no aparecerá.</p></div>
                    <div class="gep-admin-field"><label for="gep_contact_cta_title">Texto de la franja final <span class="gep-required-dot">Obligatorio</span></label><textarea id="gep_contact_cta_title" name="gep_contact_cta_title" data-gep-required="1"><?php echo esc_textarea( $values['cta_title'] ); ?></textarea></div>
                    <div class="gep-admin-field"><label for="gep_contact_cta_button_text">Texto del botón final <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_contact_cta_button_text" name="gep_contact_cta_button_text" value="<?php echo esc_attr( $values['cta_button_text'] ); ?>" data-gep-required="1"></div>
                </section>

                <?php /* v9.26.0: se quitó el botón inferior "Guardar cambios"; se guarda desde la barra superior. */ ?>
            </div>

            <aside class="gep-preview-column">
                <div class="gep-preview-card gep-contact-preview-wrap gep-parent-preview-card">
                    <div class="gep-preview-heading gep-directory-preview-heading gep-contact-preview-heading">
                        <span class="gep-preview-icon"><span class="dashicons dashicons-visibility"></span></span>
                        <div class="gep-preview-heading-copy">
                            <div class="gep-preview-heading-title-row">
                                <span class="gep-preview-zone-badge" id="gep-contact-preview-zone-number">Zona 1</span>
                                <h3 id="gep-contact-preview-heading-title">Encabezado principal</h3>
                            </div>
                        </div>
                        <div class="gep-preview-device-switcher" aria-label="Tamaño de la preview">
                            <button type="button" class="gep-directory-device-button gep-device-button is-active" data-device="desktop" title="Escritorio"><span class="dashicons dashicons-desktop"></span><span>PC</span></button>
                            <button type="button" class="gep-directory-device-button gep-device-button" data-device="tablet" title="Tablet"><span class="dashicons dashicons-tablet"></span><span>Tablet</span></button>
                            <button type="button" class="gep-directory-device-button gep-device-button" data-device="mobile" title="Móvil"><span class="dashicons dashicons-smartphone"></span><span>Móvil</span></button>
                        </div>
                    </div>

                    <div class="gep-contact-device-stage">
                        <div class="gep-contact-live-preview is-device-desktop" id="gep-contact-preview-shell" data-public-url="<?php echo esc_url( $public_url ); ?>">
                            <div class="gep-contact-live-toolbar">
                                <span><i></i><i></i><i></i></span>
                                <strong>contactanos.php</strong>
                                <em id="gep-preview-device-label">Vista escritorio</em>
                            </div>

                            <div class="gep-contact-live-page">
                                <section class="gep-contact-live-hero is-live-active" data-live-section="hero">
                                    <span class="gep-contact-live-eyebrow" id="gep-contact-preview-eyebrow"><?php echo esc_html( $values['hero_eyebrow'] ); ?></span>
                                    <h2 id="gep-contact-preview-title"><?php echo esc_html( $values['hero_title'] ); ?></h2>
                                    <p id="gep-contact-preview-text"><?php echo esc_html( $values['hero_text'] ); ?></p>
                                </section>

                                <section class="gep-contact-live-content">
                                    <div class="gep-contact-live-grid">
                                        <article class="gep-contact-live-card" data-live-section="card">
                                            <span class="gep-contact-live-eyebrow" id="gep-contact-preview-card-eyebrow"><?php echo esc_html( $values['card_eyebrow'] ); ?></span>
                                            <h3 id="gep-contact-preview-card-title"><?php echo esc_html( $values['card_title'] ); ?></h3>
                                            <div class="gep-contact-live-rows" id="gep-contact-preview-rows"></div>
                                            <div class="gep-contact-live-actions">
                                                <span class="gep-contact-live-button primary" id="gep-contact-preview-email-button"><?php echo esc_html( $values['email_button_text'] ); ?></span>
                                                <span class="gep-contact-live-button outline" id="gep-contact-preview-map-button"><?php echo esc_html( $values['map_button_text'] ); ?></span>
                                            </div>
                                        </article>

                                        <div class="gep-contact-live-map" data-live-section="location">
                                            <div class="gep-contact-live-map-art">
                                                <span class="dashicons dashicons-location-alt"></span>
                                                <img id="gep-contact-preview-map-image" src="<?php echo esc_url( $values['map_image_url'] ); ?>" alt="" <?php echo $values['map_image_url'] ? '' : 'style="display:none"'; ?>>
                                            </div>
                                            <div class="gep-contact-live-map-copy">
                                                <span id="gep-contact-preview-location-eyebrow"><?php echo esc_html( $values['location_eyebrow'] ); ?></span>
                                                <strong id="gep-contact-preview-location-title"><?php echo esc_html( $values['location_title'] ); ?></strong>
                                                <small id="gep-contact-preview-location-text"><?php echo esc_html( $values['location_text'] ); ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="gep-contact-live-note" id="gep-contact-preview-note-wrap" data-live-section="cta">
                                        <span>i</span><p id="gep-contact-preview-note"><?php echo esc_html( $values['note'] ); ?></p>
                                    </div>
                                </section>

                                <section class="gep-contact-live-cta" data-live-section="cta">
                                    <h3 id="gep-contact-preview-cta-title"><?php echo esc_html( $values['cta_title'] ); ?></h3>
                                    <span class="gep-contact-live-button light" id="gep-contact-preview-cta-button"><?php echo esc_html( $values['cta_button_text'] ); ?></span>
                                </section>
                            </div>
                        </div>
                    </div>

                    <div class="gep-preview-tip"><span class="dashicons dashicons-lightbulb"></span><div><strong>Preview sin guardar.</strong><p>La web pública solo cambia cuando pulses “Guardar cambios”.</p></div></div>
                </div>
            </aside>
        </div>
    </form>

    <script type="application/json" id="gep-contact-defaults"><?php echo wp_json_encode( $reset_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
    <script type="text/html" id="gep-contact-row-template">
        <details class="gep-item-row gep-contact-row" data-contact-key="__R__" open>
            <summary><span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><span class="gep-item-summary-main"><strong class="gep-contact-summary-label">Nuevo dato</strong><span class="gep-item-summary-badge gep-contact-summary-value"></span></span><span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span></summary>
            <div class="gep-item-content">
                <div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-contact-move-up"><span class="dashicons dashicons-arrow-up-alt2"></span> Subir</button><button type="button" class="button button-small gep-contact-move-down"><span class="dashicons dashicons-arrow-down-alt2"></span> Bajar</button><button type="button" class="button button-small gep-duplicate-contact-row"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-remove-contact-row"><span class="dashicons dashicons-trash"></span> Eliminar dato</button></div>
                <div class="gep-grid-2"><label>Etiqueta<input class="gep-contact-label-input" type="text" name="gep_contact_rows[__R__][label]" placeholder="Ej: Teléfono"></label><label>Tipo de dato<select name="gep_contact_rows[__R__][kind]"><option value="text">Texto normal</option><option value="email">Correo electrónico</option><option value="tel">Teléfono</option><option value="url">Enlace web</option></select></label></div>
                <label>Contenido<input class="gep-contact-value-input" type="text" name="gep_contact_rows[__R__][value]" placeholder="Información que aparecerá en la página"></label>
            </div>
        </details>
    </script>
    <?php
}


function gep_demo_render_docencia_learning_row( $row_key, $row = array() ) {
    $row    = is_array( $row ) ? $row : array();
    $number = isset( $row['number'] ) ? $row['number'] : '';
    $title  = isset( $row['title'] ) ? $row['title'] : '';
    $body   = isset( $row['text'] ) ? $row['text'] : '';
    ?>
    <details class="gep-item-row gep-docencia-step-row" data-docencia-key="<?php echo esc_attr( $row_key ); ?>">
        <summary><span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><span class="gep-item-summary-main"><span class="gep-item-summary-badge gep-docencia-step-number-summary"><?php echo esc_html( $number ? $number : '—' ); ?></span><strong class="gep-item-summary-name gep-docencia-step-title-summary"><?php echo esc_html( $title ? $title : 'Nuevo paso' ); ?></strong></span><span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span></summary>
        <div class="gep-item-content">
            <div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-docencia-move-up"><span class="dashicons dashicons-arrow-up-alt2"></span> Subir</button><button type="button" class="button button-small gep-docencia-move-down"><span class="dashicons dashicons-arrow-down-alt2"></span> Bajar</button><button type="button" class="button button-small gep-docencia-duplicate-step"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-docencia-remove-step"><span class="dashicons dashicons-trash"></span> Eliminar</button></div>
            <div class="gep-grid-2"><label>Número<input class="gep-docencia-step-number" type="text" name="gep_docencia_learning_steps[<?php echo esc_attr( $row_key ); ?>][number]" value="<?php echo esc_attr( $number ); ?>" placeholder="01"></label><label>Título <span class="gep-required-dot">Obligatorio</span><input class="gep-docencia-step-title" type="text" name="gep_docencia_learning_steps[<?php echo esc_attr( $row_key ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" data-gep-docencia-required="1"></label></div>
            <label>Descripción<textarea name="gep_docencia_learning_steps[<?php echo esc_attr( $row_key ); ?>][text]" placeholder="Describe qué aprende o desarrolla el estudiante."><?php echo esc_textarea( $body ); ?></textarea></label>
        </div>
    </details>
    <?php
}

function gep_demo_render_docencia_course_row( $group, $row_key, $row = array() ) {
    $row=is_array($row)?$row:array(); $row+=array('code'=>'','title'=>'','status'=>'','url'=>'','button'=>'','image'=>'','image_alt'=>'');
    $name='gep_docencia_'.$group.'_courses['.$row_key.']';
    ?>
    <details class="gep-item-row gep-docencia-course-row" data-docencia-group="<?php echo esc_attr($group); ?>" data-docencia-key="<?php echo esc_attr($row_key); ?>">
      <summary><span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><span class="gep-item-summary-main"><span class="gep-item-summary-badge gep-docencia-course-code-summary"><?php echo esc_html($row['code']?$row['code']:'CURSO'); ?></span><strong class="gep-item-summary-name gep-docencia-course-title-summary"><?php echo esc_html($row['title']?$row['title']:'Nuevo curso'); ?></strong></span><span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span></summary>
      <div class="gep-item-content"><div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-docencia-move-up">Subir</button><button type="button" class="button button-small gep-docencia-move-down">Bajar</button><button type="button" class="button button-small gep-docencia-duplicate-course"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-docencia-remove-course"><span class="dashicons dashicons-trash"></span> Eliminar curso</button></div>
      <div class="gep-grid-2"><label>Sigla<input class="gep-docencia-course-code" type="text" name="<?php echo esc_attr($name); ?>[code]" value="<?php echo esc_attr($row['code']); ?>" placeholder="GEO 101"></label><label>Título <span class="gep-required-dot">Obligatorio</span><input class="gep-docencia-course-title" type="text" name="<?php echo esc_attr($name); ?>[title]" value="<?php echo esc_attr($row['title']); ?>" data-gep-docencia-required="1"></label></div>
      <div class="gep-grid-2"><label>Estado / texto secundario<input type="text" name="<?php echo esc_attr($name); ?>[status]" value="<?php echo esc_attr($row['status']); ?>" placeholder="Programa disponible"></label><label>Texto del botón<input type="text" name="<?php echo esc_attr($name); ?>[button]" value="<?php echo esc_attr($row['button']); ?>" placeholder="Abrir ↗"></label></div>
      <label>Enlace del programa<input class="gep-docencia-course-url" type="url" name="<?php echo esc_attr($name); ?>[url]" value="<?php echo esc_url($row['url']); ?>" placeholder="https://..."></label>
      <div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen del curso <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_image_control($name.'[image]',$row['image'],'Seleccionar / cambiar imagen'); ?></div>
      <label>Texto alternativo de la imagen<input type="text" name="<?php echo esc_attr($name); ?>[image_alt]" value="<?php echo esc_attr($row['image_alt']); ?>" placeholder="Descripción breve de la imagen"></label>
      </div>
    </details><?php
}

function gep_demo_render_docencia_editor( $post_id, $values ) {
    $public_url = gep_demo_docencia_public_url( $post_id );
    $defaults   = gep_demo_docencia_defaults();
    $reset_data = $defaults;
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gep-content-form" novalidate>
        <input type="hidden" name="action" value="gep_demo_save_content">
        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
        <input type="hidden" name="gep_editor_context" value="docencia">
        <?php wp_nonce_field( 'gep_demo_save_content_' . $post_id, 'gep_demo_admin_nonce' ); ?>

        <div class="gep-contact-commandbar gep-docencia-commandbar">
            <div class="gep-contact-commandbar-copy"><span class="gep-contact-command-icon"><span class="dashicons dashicons-welcome-learn-more"></span></span><div><strong>Editor de Docencia</strong><small>Edita cursos, bloques y campos de formación con preview en vivo.</small></div><span class="gep-inline-save-state is-clean" id="gep-docencia-inline-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span></div>
            <div class="gep-contact-commandbar-actions"><button type="button" class="button gep-restore-docencia"><span class="dashicons dashicons-image-rotate"></span> Restaurar original</button><?php /* v9.25.0: "Ver página real" se quitó de esta barra; el acceso queda en "Sitio público" del menú lateral. */ ?><button type="submit" class="button button-primary gep-save-button" disabled><span class="dashicons dashicons-saved"></span> Guardar cambios</button></div>
        </div>

        <div class="gep-editor-layout gep-editor-layout-docencia">
            <div class="gep-questionnaire">
                <?php gep_demo_render_section_visibility_tools(); ?>
                <section class="gep-step-card" data-docencia-preview="hero">
                    <div class="gep-step-heading"><span class="gep-step-number">1</span><div><h3>Encabezado y navegación</h3><p>Textos de presentación y nombres de los accesos rápidos de la página.</p></div></div>
                    <div class="gep-admin-field"><label for="gep_docencia_hero_eyebrow">Texto superior</label><input type="text" id="gep_docencia_hero_eyebrow" name="gep_docencia_hero_eyebrow" value="<?php echo esc_attr( $values['hero_eyebrow'] ); ?>"></div>
                    <div class="gep-admin-field"><label for="gep_docencia_hero_title">Título principal <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_docencia_hero_title" name="gep_docencia_hero_title" value="<?php echo esc_attr( $values['hero_title'] ); ?>" data-gep-docencia-required="1"></div>
                    <div class="gep-admin-field"><label for="gep_docencia_hero_text">Descripción</label><textarea id="gep_docencia_hero_text" name="gep_docencia_hero_text"><?php echo esc_textarea( $values['hero_text'] ); ?></textarea></div><div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen hero / cabecera</span><?php gep_demo_render_image_control( 'gep_docencia_hero_image', $values['hero_image'], 'Seleccionar / cambiar imagen hero' ); ?></div><div class="gep-admin-field gep-media-field gep-hero-video-field"><span class="gep-field-label">Video hero / cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_video_control( 'gep_docencia_hero_video', isset($values['hero_video'])?$values['hero_video']:'', 'Seleccionar / cambiar video hero' ); ?></div>
                    <div class="gep-grid-2"><div class="gep-admin-field"><label for="gep_docencia_nav_pregrado">Navegación: Pregrado</label><input type="text" id="gep_docencia_nav_pregrado" name="gep_docencia_nav_pregrado" value="<?php echo esc_attr( $values['nav_pregrado'] ); ?>"></div><div class="gep-admin-field"><label for="gep_docencia_nav_postgrado">Navegación: Postgrado</label><input type="text" id="gep_docencia_nav_postgrado" name="gep_docencia_nav_postgrado" value="<?php echo esc_attr( $values['nav_postgrado'] ); ?>"></div></div>
                    <div class="gep-grid-2"><div class="gep-admin-field"><label for="gep_docencia_nav_diplomado">Navegación: Diplomado</label><input type="text" id="gep_docencia_nav_diplomado" name="gep_docencia_nav_diplomado" value="<?php echo esc_attr( $values['nav_diplomado'] ); ?>"></div><div class="gep-admin-field"><label for="gep_docencia_nav_cursos">Navegación: Cursos y talleres</label><input type="text" id="gep_docencia_nav_cursos" name="gep_docencia_nav_cursos" value="<?php echo esc_attr( $values['nav_cursos'] ); ?>"></div></div>
                    <div class="gep-admin-field"><label for="gep_docencia_nav_campos">Navegación: Campos de formación</label><input type="text" id="gep_docencia_nav_campos" name="gep_docencia_nav_campos" value="<?php echo esc_attr( $values['nav_campos'] ); ?>"></div>
                </section>

                <section class="gep-step-card" data-docencia-preview="learning">
                    <div class="gep-step-heading"><span class="gep-step-number">2</span><div><h3>Ruta de aprendizaje</h3><p>Añade, duplica, elimina o reordena los pasos de Representar → Analizar → Interpretar.</p></div></div>
                    <div class="gep-team-builder-head"><div><strong>Pasos de aprendizaje</strong><p class="gep-muted">El orden del listado es el mismo que verá el visitante.</p></div><button type="button" class="button gep-docencia-add-step"><span class="dashicons dashicons-plus-alt2"></span> Añadir paso</button></div>
                    <div class="gep-docencia-builder" id="gep-docencia-learning-steps"><?php foreach ( $values['learning_steps'] as $row_key => $row ) { gep_demo_render_docencia_learning_row( $row_key, $row ); } ?></div>
                    <div class="gep-empty-builder <?php echo ! empty( $values['learning_steps'] ) ? 'is-hidden' : ''; ?>" id="gep-docencia-learning-empty"><span class="dashicons dashicons-welcome-learn-more"></span><strong>No hay pasos todavía</strong><span>Añade uno para comenzar.</span></div>
                </section>

                <section class="gep-step-card" data-docencia-preview="pregrado">
                    <div class="gep-step-heading"><span class="gep-step-number">3</span><div><h3>Pregrado</h3><p>Edita el encabezado y los cursos asociados a esta sección.</p></div></div>
                    <div class="gep-grid-2"><div class="gep-admin-field"><label for="gep_docencia_pre_eyebrow">Texto superior</label><input type="text" id="gep_docencia_pre_eyebrow" name="gep_docencia_pre_eyebrow" value="<?php echo esc_attr( $values['pre_eyebrow'] ); ?>"></div><div class="gep-admin-field"><label for="gep_docencia_pre_title">Título <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_docencia_pre_title" name="gep_docencia_pre_title" value="<?php echo esc_attr( $values['pre_title'] ); ?>" data-gep-docencia-required="1"></div></div>
                    <div class="gep-admin-field"><label for="gep_docencia_pre_text">Descripción</label><textarea id="gep_docencia_pre_text" name="gep_docencia_pre_text"><?php echo esc_textarea( $values['pre_text'] ); ?></textarea></div>
                    <div class="gep-team-builder-head"><div><strong>Cursos de pregrado</strong><p class="gep-muted">Cada curso puede enlazar a un PDF o página externa.</p></div><button type="button" class="button gep-docencia-add-course" data-group="pre"><span class="dashicons dashicons-plus-alt2"></span> Añadir curso</button></div>
                    <div class="gep-docencia-builder gep-docencia-courses" id="gep-docencia-pre-courses" data-group="pre"><?php foreach ( $values['pre_courses'] as $row_key => $row ) { gep_demo_render_docencia_course_row( 'pre', $row_key, $row ); } ?></div>
                    <div class="gep-empty-builder <?php echo ! empty( $values['pre_courses'] ) ? 'is-hidden' : ''; ?>" id="gep-docencia-pre-empty"><span class="dashicons dashicons-welcome-learn-more"></span><strong>No hay cursos de pregrado</strong><span>Añade el primer curso.</span></div>
                </section>

                <section class="gep-step-card" data-docencia-preview="postgrado">
                    <div class="gep-step-heading"><span class="gep-step-number">4</span><div><h3>Postgrado</h3><p>Cursos y programas avanzados de postgrado.</p></div></div>
                    <div class="gep-grid-2"><div class="gep-admin-field"><label for="gep_docencia_post_eyebrow">Texto superior</label><input type="text" id="gep_docencia_post_eyebrow" name="gep_docencia_post_eyebrow" value="<?php echo esc_attr( $values['post_eyebrow'] ); ?>"></div><div class="gep-admin-field"><label for="gep_docencia_post_title">Título <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_docencia_post_title" name="gep_docencia_post_title" value="<?php echo esc_attr( $values['post_title'] ); ?>" data-gep-docencia-required="1"></div></div>
                    <div class="gep-admin-field"><label for="gep_docencia_post_text">Descripción</label><textarea id="gep_docencia_post_text" name="gep_docencia_post_text"><?php echo esc_textarea( $values['post_text'] ); ?></textarea></div>
                    <div class="gep-team-builder-head"><div><strong>Cursos avanzados</strong><p class="gep-muted">Puedes ordenar los registros arrastrando el icono ☰.</p></div><button type="button" class="button gep-docencia-add-course" data-group="post"><span class="dashicons dashicons-plus-alt2"></span> Añadir curso</button></div>
                    <div class="gep-docencia-builder gep-docencia-courses" id="gep-docencia-post-courses" data-group="post"><?php foreach ( $values['post_courses'] as $row_key => $row ) { gep_demo_render_docencia_course_row( 'post', $row_key, $row ); } ?></div>
                    <div class="gep-empty-builder <?php echo ! empty( $values['post_courses'] ) ? 'is-hidden' : ''; ?>" id="gep-docencia-post-empty"><span class="dashicons dashicons-welcome-learn-more"></span><strong>No hay cursos avanzados</strong><span>Añade el primer curso.</span></div>
                </section>

                <section class="gep-step-card" data-docencia-preview="diplomado">
                    <div class="gep-step-heading"><span class="gep-step-number">5</span><div><h3>Diplomado</h3><p>Programas de diplomado asociados al laboratorio.</p></div></div>
                    <div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" id="gep_docencia_diploma_eyebrow" name="gep_docencia_diploma_eyebrow" value="<?php echo esc_attr($values['diploma_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input type="text" id="gep_docencia_diploma_title" name="gep_docencia_diploma_title" value="<?php echo esc_attr($values['diploma_title']); ?>"></div></div>
                    <div class="gep-admin-field"><label>Descripción</label><textarea id="gep_docencia_diploma_text" name="gep_docencia_diploma_text"><?php echo esc_textarea($values['diploma_text']); ?></textarea></div>
                    <div class="gep-team-builder-head"><div><strong>Diplomados</strong><p class="gep-muted">La imagen de cada programa es opcional.</p></div><button type="button" class="button gep-docencia-add-course" data-group="diploma"><span class="dashicons dashicons-plus-alt2"></span> Añadir diplomado</button></div>
                    <div class="gep-docencia-builder gep-docencia-courses" id="gep-docencia-diploma-courses" data-group="diploma"><?php foreach($values['diploma_courses'] as $row_key=>$row)gep_demo_render_docencia_course_row('diploma',$row_key,$row); ?></div>
                    <div class="gep-empty-builder <?php echo !empty($values['diploma_courses'])?'is-hidden':''; ?>" id="gep-docencia-diploma-empty"><strong>No hay diplomados.</strong></div>
                </section>
                <section class="gep-step-card" data-docencia-preview="workshop">
                    <div class="gep-step-heading"><span class="gep-step-number">6</span><div><h3>Cursos y talleres</h3><p>Formación breve, talleres y actividades complementarias.</p></div></div>
                    <div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" id="gep_docencia_workshop_eyebrow" name="gep_docencia_workshop_eyebrow" value="<?php echo esc_attr($values['workshop_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input type="text" id="gep_docencia_workshop_title" name="gep_docencia_workshop_title" value="<?php echo esc_attr($values['workshop_title']); ?>"></div></div>
                    <div class="gep-admin-field"><label>Descripción</label><textarea id="gep_docencia_workshop_text" name="gep_docencia_workshop_text"><?php echo esc_textarea($values['workshop_text']); ?></textarea></div>
                    <div class="gep-team-builder-head"><div><strong>Cursos y talleres</strong></div><button type="button" class="button gep-docencia-add-course" data-group="workshop"><span class="dashicons dashicons-plus-alt2"></span> Añadir curso/taller</button></div>
                    <div class="gep-docencia-builder gep-docencia-courses" id="gep-docencia-workshop-courses" data-group="workshop"><?php foreach($values['workshop_courses'] as $row_key=>$row)gep_demo_render_docencia_course_row('workshop',$row_key,$row); ?></div>
                    <div class="gep-empty-builder <?php echo !empty($values['workshop_courses'])?'is-hidden':''; ?>" id="gep-docencia-workshop-empty"><strong>No hay cursos o talleres.</strong></div>
                </section>

                <section class="gep-step-card" data-docencia-preview="fields">
                    <div class="gep-step-heading"><span class="gep-step-number">7</span><div><h3>Nota y campos de formación</h3><p>Edita el cierre de la página y selecciona su imagen desde WordPress.</p></div></div>
                    <div class="gep-admin-field"><label for="gep_docencia_note">Nota informativa</label><textarea id="gep_docencia_note" name="gep_docencia_note"><?php echo esc_textarea( $values['note'] ); ?></textarea></div>
                    <div class="gep-grid-2"><div class="gep-admin-field"><label for="gep_docencia_fields_eyebrow">Texto superior</label><input type="text" id="gep_docencia_fields_eyebrow" name="gep_docencia_fields_eyebrow" value="<?php echo esc_attr( $values['fields_eyebrow'] ); ?>"></div><div class="gep-admin-field"><label for="gep_docencia_fields_title">Título <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_docencia_fields_title" name="gep_docencia_fields_title" value="<?php echo esc_attr( $values['fields_title'] ); ?>" data-gep-docencia-required="1"></div></div>
                    <div class="gep-admin-field"><label for="gep_docencia_fields_text">Descripción</label><textarea id="gep_docencia_fields_text" name="gep_docencia_fields_text"><?php echo esc_textarea( $values['fields_text'] ); ?></textarea></div>
                    <div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen de campos de formación</span><p class="gep-muted">Selecciona la imagen desde la Mediateca de WordPress.</p><?php gep_demo_render_image_control( 'gep_docencia_fields_image_url', $values['fields_image_url'], 'Seleccionar / cambiar imagen' ); ?></div>
                    <div class="gep-admin-field"><label for="gep_docencia_fields_image_alt">Texto alternativo de la imagen</label><input type="text" id="gep_docencia_fields_image_alt" name="gep_docencia_fields_image_alt" value="<?php echo esc_attr( $values['fields_image_alt'] ); ?>"></div>
                </section>
            </div>

            <aside class="gep-preview-column">
                <div class="gep-preview-card gep-docencia-preview-wrap gep-parent-preview-card">
                    <div class="gep-preview-heading gep-directory-preview-heading gep-docencia-preview-heading">
                        <span class="gep-preview-icon"><span class="dashicons dashicons-visibility"></span></span>
                        <div class="gep-preview-heading-copy"><div class="gep-preview-heading-title-row"><span class="gep-preview-zone-badge" id="gep-docencia-preview-zone-number">Zona 1</span><h3 id="gep-docencia-preview-heading-title">Encabezado y navegación</h3></div></div>
                        <div class="gep-preview-device-switcher"><button type="button" class="gep-directory-device-button gep-docencia-device-button is-active" data-device="desktop"><span class="dashicons dashicons-desktop"></span><span>PC</span></button><button type="button" class="gep-directory-device-button gep-docencia-device-button" data-device="tablet"><span class="dashicons dashicons-tablet"></span><span>Tablet</span></button><button type="button" class="gep-directory-device-button gep-docencia-device-button" data-device="mobile"><span class="dashicons dashicons-smartphone"></span><span>Móvil</span></button></div>
                    </div>
                    <div class="gep-docencia-device-stage"><div class="gep-docencia-live-preview is-device-desktop" id="gep-docencia-preview-shell" data-public-url="<?php echo esc_url( $public_url ); ?>"><div class="gep-directory-live-toolbar"><span></span><span></span><span></span><strong>docencia.php</strong><em id="gep-docencia-device-label">Vista escritorio</em></div>
                        <div class="gep-docencia-live-hero" data-docencia-live="hero"><span id="gep-doc-prev-hero-eyebrow"></span><h2 id="gep-doc-prev-hero-title"></h2><p id="gep-doc-prev-hero-text"></p></div>
                        <div class="gep-docencia-live-nav" data-docencia-live="hero"><span id="gep-doc-prev-nav-pre"></span><span id="gep-doc-prev-nav-post"></span><span id="gep-doc-prev-nav-diploma"></span><span id="gep-doc-prev-nav-workshop"></span><span id="gep-doc-prev-nav-fields"></span></div>
                        <div class="gep-docencia-live-section" data-docencia-live="learning"><div class="gep-docencia-preview-steps" id="gep-doc-prev-learning"></div></div>
                        <div class="gep-docencia-live-section" data-docencia-live="pregrado"><div class="gep-docencia-section-head"><div><span id="gep-doc-prev-pre-eyebrow"></span><h3 id="gep-doc-prev-pre-title"></h3></div><p id="gep-doc-prev-pre-text"></p></div><div class="gep-docencia-preview-courses" id="gep-doc-prev-pre-courses"></div></div>
                        <div class="gep-docencia-live-section" data-docencia-live="postgrado"><div class="gep-docencia-section-head"><div><span id="gep-doc-prev-post-eyebrow"></span><h3 id="gep-doc-prev-post-title"></h3></div><p id="gep-doc-prev-post-text"></p></div><div class="gep-docencia-preview-courses" id="gep-doc-prev-post-courses"></div></div>
                        <div class="gep-docencia-live-section" data-docencia-live="diplomado"><div class="gep-docencia-section-head"><div><span id="gep-doc-prev-diploma-eyebrow"></span><h3 id="gep-doc-prev-diploma-title"></h3></div><p id="gep-doc-prev-diploma-text"></p></div><div class="gep-docencia-preview-courses" id="gep-doc-prev-diploma-courses"></div></div>
                        <div class="gep-docencia-live-section" data-docencia-live="workshop"><div class="gep-docencia-section-head"><div><span id="gep-doc-prev-workshop-eyebrow"></span><h3 id="gep-doc-prev-workshop-title"></h3></div><p id="gep-doc-prev-workshop-text"></p></div><div class="gep-docencia-preview-courses" id="gep-doc-prev-workshop-courses"></div><div class="gep-docencia-preview-note" id="gep-doc-prev-note"></div></div>
                        <div class="gep-docencia-live-fields" data-docencia-live="fields"><div><span id="gep-doc-prev-fields-eyebrow"></span><h3 id="gep-doc-prev-fields-title"></h3><p id="gep-doc-prev-fields-text"></p></div><div class="gep-docencia-preview-image"><span class="dashicons dashicons-format-image"></span><img id="gep-doc-prev-fields-image" alt="" src=""></div></div>
                    </div></div>
                    <div class="gep-preview-tip"><span class="dashicons dashicons-lightbulb"></span><div><strong>Todo se actualiza antes de guardar.</strong><p>Prueba añadir, reordenar o duplicar cursos y cambia entre PC, Tablet y Móvil.</p></div></div>
                </div>
            </aside>
        </div>
    </form>

    <script type="application/json" id="gep-docencia-defaults"><?php echo wp_json_encode( $reset_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
    <script type="text/html" id="gep-docencia-step-template"><details class="gep-item-row gep-docencia-step-row" data-docencia-key="__K__" open><summary><span class="gep-grip dashicons dashicons-menu-alt2"></span><span class="gep-item-summary-main"><span class="gep-item-summary-badge gep-docencia-step-number-summary">—</span><strong class="gep-item-summary-name gep-docencia-step-title-summary">Nuevo paso</strong></span><span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span></summary><div class="gep-item-content"><div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-docencia-move-up">↑ Subir</button><button type="button" class="button button-small gep-docencia-move-down">↓ Bajar</button><button type="button" class="button button-small gep-docencia-duplicate-step">Duplicar</button></div><button type="button" class="button-link-delete gep-docencia-remove-step">Eliminar</button></div><div class="gep-grid-2"><label>Número<input class="gep-docencia-step-number" type="text" name="gep_docencia_learning_steps[__K__][number]" placeholder="01"></label><label>Título <span class="gep-required-dot">Obligatorio</span><input class="gep-docencia-step-title" type="text" name="gep_docencia_learning_steps[__K__][title]" data-gep-docencia-required="1"></label></div><label>Descripción<textarea name="gep_docencia_learning_steps[__K__][text]"></textarea></label></div></details></script>
    <script type="text/html" id="gep-docencia-course-template"><?php gep_demo_render_docencia_course_row('__G__','__K__',array()); ?></script>
    <?php
}


function gep_demo_render_project_feature_row( $row_key, $row = array() ) {
    $row = is_array( $row ) ? $row : array();
    $image  = isset( $row['image'] ) ? $row['image'] : '';
    $alt    = isset( $row['alt'] ) ? $row['alt'] : '';
    $kicker = isset( $row['kicker'] ) ? $row['kicker'] : '';
    $title  = isset( $row['title'] ) ? $row['title'] : '';
    $body   = isset( $row['text'] ) ? $row['text'] : '';
    $name   = 'gep_projects_features[' . $row_key . ']';
    ?>
    <details class="gep-item-row gep-project-feature-row" data-project-key="<?php echo esc_attr( $row_key ); ?>">
        <summary><span class="gep-grip dashicons dashicons-menu-alt2"></span><span class="gep-item-summary-main"><span class="gep-item-summary-badge">DESTACADO</span><strong class="gep-item-summary-name gep-project-feature-title-summary"><?php echo esc_html( $title ? $title : 'Nuevo destacado' ); ?></strong></span><span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span></summary>
        <div class="gep-item-content">
            <div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-project-move-up">Subir</button><button type="button" class="button button-small gep-project-move-down">Bajar</button><button type="button" class="button button-small gep-project-duplicate-feature"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-project-remove-feature"><span class="dashicons dashicons-trash"></span> Eliminar</button></div>
            <div class="gep-admin-field compact"><span class="gep-field-label">Imagen</span><?php gep_demo_render_image_control( $name . '[image]', $image, 'Seleccionar / cambiar imagen' ); ?></div>
            <label>Texto alternativo<input type="text" name="<?php echo esc_attr( $name ); ?>[alt]" value="<?php echo esc_attr( $alt ); ?>"></label>
            <label>Etiqueta superior<input type="text" name="<?php echo esc_attr( $name ); ?>[kicker]" value="<?php echo esc_attr( $kicker ); ?>"></label>
            <label>Título <span class="gep-required-dot">Obligatorio</span><input class="gep-project-feature-title" type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $title ); ?>" data-gep-project-required="1"></label>
            <label>Descripción<textarea name="<?php echo esc_attr( $name ); ?>[text]"><?php echo esc_textarea( $body ); ?></textarea></label>
        </div>
    </details>
    <?php
}

function gep_demo_render_project_record_row( $row_key, $row = array() ) {
    $row   = is_array( $row ) ? $row : array();
    $year  = isset( $row['year'] ) ? $row['year'] : '';
    $theme = isset( $row['theme'] ) ? $row['theme'] : '';
    $title = isset( $row['title'] ) ? $row['title'] : '';
    ?>
    <div class="gep-project-record-list-row" data-project-key="<?php echo esc_attr( $row_key ); ?>">
        <span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar" aria-hidden="true"></span>
        <button type="button" class="gep-project-record-open" aria-label="Editar proyecto">
            <span class="gep-item-summary-badge gep-project-year-summary"><?php echo esc_html( $year ? $year : 'AÑO' ); ?></span>
            <strong class="gep-project-title-summary"><?php echo esc_html( $title ? $title : 'Nuevo proyecto' ); ?></strong>
            <span class="gep-project-theme-summary"><?php echo esc_html( $theme ); ?></span>
        </button>
        <span class="dashicons dashicons-arrow-right-alt2 gep-project-record-arrow" aria-hidden="true"></span>
    </div>
    <?php
}

function gep_demo_render_project_area_row( $row_key, $row = array() ) {
    $row = is_array( $row ) ? $row : array();
    $icon = isset( $row['icon'] ) ? $row['icon'] : '＋';
    $title = isset( $row['title'] ) ? $row['title'] : '';
    $body = isset( $row['text'] ) ? $row['text'] : '';
    $name = 'gep_projects_areas[' . $row_key . ']';
    ?>
    <details class="gep-item-row gep-project-area-row" data-project-key="<?php echo esc_attr( $row_key ); ?>">
        <summary><span class="gep-grip dashicons dashicons-menu-alt2"></span><span class="gep-item-summary-main"><span class="gep-item-summary-badge gep-project-area-icon-summary"><?php echo esc_html( $icon ? $icon : '＋' ); ?></span><strong class="gep-item-summary-name gep-project-area-title-summary"><?php echo esc_html( $title ? $title : 'Nueva área' ); ?></strong></span><span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span></summary>
        <div class="gep-item-content"><div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-project-move-up">Subir</button><button type="button" class="button button-small gep-project-move-down">Bajar</button><button type="button" class="button button-small gep-project-duplicate-area"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-project-remove-area"><span class="dashicons dashicons-trash"></span> Eliminar</button></div>
        <div class="gep-grid-2"><label>Icono<input type="text" name="<?php echo esc_attr( $name ); ?>[icon]" value="<?php echo esc_attr( $icon ); ?>" placeholder="＋"></label><label>Título <span class="gep-required-dot">Obligatorio</span><input class="gep-project-area-title" type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $title ); ?>" data-gep-project-required="1"></label></div><label>Descripción<textarea name="<?php echo esc_attr( $name ); ?>[text]"><?php echo esc_textarea( $body ); ?></textarea></label></div>
    </details>
    <?php
}

function gep_demo_render_projects_editor( $post_id, $values ) {
    $public_url = gep_demo_projects_public_url( $post_id );
    $defaults = gep_demo_projects_defaults();
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gep-content-form" novalidate>
        <input type="hidden" name="action" value="gep_demo_save_content"><input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>"><input type="hidden" name="gep_editor_context" value="projects">
        <?php wp_nonce_field( 'gep_demo_save_content_' . $post_id, 'gep_demo_admin_nonce' ); ?>
        <div class="gep-contact-commandbar gep-project-commandbar"><div class="gep-contact-commandbar-copy"><span class="gep-contact-command-icon"><span class="dashicons dashicons-portfolio"></span></span><div><strong>Editor de Proyectos</strong><small>Marca proyectos como vigentes o cerrados y prepara sus futuras fichas.</small></div><span class="gep-inline-save-state is-clean" id="gep-project-inline-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span></div><div class="gep-contact-commandbar-actions"><button type="button" class="button gep-restore-projects"><span class="dashicons dashicons-image-rotate"></span> Restaurar original</button><?php /* v9.25.0: "Ver página real" se quitó de esta barra; el acceso queda en "Sitio público" del menú lateral. */ ?><button type="submit" class="button button-primary gep-save-button" disabled><span class="dashicons dashicons-saved"></span> Guardar cambios</button></div></div>

        <div class="gep-editor-layout gep-editor-layout-projects">
            <div class="gep-questionnaire">
                <?php gep_demo_render_section_visibility_tools(); ?>
                <section class="gep-step-card" data-project-preview="hero"><div class="gep-step-heading"><span class="gep-step-number">1</span><div><h3>Encabezado y navegación</h3><p>Presentación de la página y accesos rápidos.</p></div></div>
                    <div class="gep-admin-field"><label>Texto superior</label><input type="text" id="gep_projects_hero_eyebrow" name="gep_projects_hero_eyebrow" value="<?php echo esc_attr( $values['hero_eyebrow'] ); ?>"></div>
                    <div class="gep-admin-field"><label>Título <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_projects_hero_title" name="gep_projects_hero_title" value="<?php echo esc_attr( $values['hero_title'] ); ?>" data-gep-project-required="1"></div>
                    <div class="gep-admin-field"><label>Descripción</label><textarea id="gep_projects_hero_text" name="gep_projects_hero_text"><?php echo esc_textarea( $values['hero_text'] ); ?></textarea></div><div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen hero / cabecera</span><?php gep_demo_render_image_control( 'gep_projects_hero_image', $values['hero_image'], 'Seleccionar / cambiar imagen hero' ); ?></div><div class="gep-admin-field gep-media-field gep-hero-video-field"><span class="gep-field-label">Video hero / cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_video_control( 'gep_projects_hero_video', isset($values['hero_video'])?$values['hero_video']:'', 'Seleccionar / cambiar video hero' ); ?></div>
                    <div class="gep-grid-2"><div class="gep-admin-field"><label>Navegación: vigentes</label><input type="text" id="gep_projects_nav_featured" name="gep_projects_nav_featured" value="<?php echo esc_attr( $values['nav_featured'] ); ?>"></div><div class="gep-admin-field"><label>Navegación: trayectoria</label><input type="text" id="gep_projects_nav_trajectory" name="gep_projects_nav_trajectory" value="<?php echo esc_attr( $values['nav_trajectory'] ); ?>"></div></div><div class="gep-admin-field"><label>Navegación: áreas</label><input type="text" id="gep_projects_nav_areas" name="gep_projects_nav_areas" value="<?php echo esc_attr( $values['nav_areas'] ); ?>"></div>
                </section>

                <section class="gep-step-card" data-project-preview="features"><div class="gep-step-heading"><span class="gep-step-number">2</span><div><h3>Proyectos vigentes</h3><p>Tarjetas con los proyectos cuyo estado es «Vigente».</p></div></div>
                    <div class="gep-info-note"><span class="dashicons dashicons-info-outline"></span><p><strong>Una sola lista para todos los proyectos.</strong> Vigentes y trayectoria se editan en la misma lista de abajo. El <strong>estado</strong> de cada proyecto decide dónde aparece: «Vigente» arriba, en tarjetas; «Trayectoria» (finalizado) en la línea de tiempo. Pulsa la etiqueta de estado para cambiarlo.</p></div>
                </section>

                <section class="gep-step-card" data-project-preview="trajectory"><div class="gep-step-heading"><span class="gep-step-number">3</span><div><h3>Encabezado de trayectoria</h3><p>Textos que aparecen encima del buscador público.</p></div></div><div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" id="gep_projects_trajectory_eyebrow" name="gep_projects_trajectory_eyebrow" value="<?php echo esc_attr( $values['trajectory_eyebrow'] ); ?>"></div><div class="gep-admin-field"><label>Título <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_projects_trajectory_title" name="gep_projects_trajectory_title" value="<?php echo esc_attr( $values['trajectory_title'] ); ?>" data-gep-project-required="1"></div></div><div class="gep-admin-field"><label>Texto lateral</label><textarea id="gep_projects_trajectory_text" name="gep_projects_trajectory_text"><?php echo esc_textarea( $values['trajectory_text'] ); ?></textarea></div></section>

                <section class="gep-step-card" data-project-preview="records"><div class="gep-step-heading"><span class="gep-step-number">4</span><div><h3>Todos los proyectos</h3><p>Vigentes y trayectoria en una sola lista. Cada proyecto se escribe una vez; su estado decide dónde se muestra.</p></div></div>
                    <div class="gep-project-status-filter" role="group" aria-label="Mostrar proyectos"><button type="button" class="is-active" data-status="all" aria-pressed="true">Todos <b>0</b></button><button type="button" data-status="vigente" aria-pressed="false">Vigentes <b>0</b></button><button type="button" data-status="cerrado" aria-pressed="false">Trayectoria <b>0</b></button></div>
                    <div class="gep-project-record-toolbar"><label class="gep-project-editor-search"><span class="dashicons dashicons-search"></span><input type="search" id="gep-project-editor-search" placeholder="Buscar dentro del editor…"></label><span id="gep-project-editor-count"></span><button type="button" class="button gep-project-sort-year"><span class="dashicons dashicons-sort"></span> Ordenar por año ↓</button><button type="button" class="button gep-project-add-record"><span class="dashicons dashicons-plus-alt2"></span> Añadir proyecto</button></div>
                    <div class="gep-project-record-workspace">
                        <div class="gep-project-builder" id="gep-project-records"><?php foreach ( $values['projects'] as $k => $row ) gep_demo_render_project_record_row( $k, $row ); ?></div>
                        <div id="gep-project-record-editor-parking">
                            <div class="gep-project-single-editor is-hidden" id="gep-project-single-editor">
                                <div class="gep-publication-single-head"><div><span>Editando proyecto</span><strong id="gep-project-active-summary">Proyecto</strong></div><button type="button" class="button-link gep-project-record-close" aria-label="Cerrar editor"><span class="dashicons dashicons-no-alt"></span></button></div>
                                <div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-project-record-up">Subir</button><button type="button" class="button button-small gep-project-record-down">Bajar</button><button type="button" class="button button-small gep-project-record-duplicate"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-project-record-remove"><span class="dashicons dashicons-trash"></span> Eliminar proyecto</button></div>
                                <div class="gep-grid-2"><label>Estado<select data-gep-project-editor-field="status"><option value="vigente">Vigente · en «Proyectos vigentes»</option><option value="cerrado">Finalizado · en «Trayectoria»</option></select></label><label>Fecha / período <span class="gep-required-dot">Obligatorio</span><input type="text" data-gep-project-editor-field="year" placeholder="2014–2016"></label></div><div class="gep-grid-2"><label>Grupo del filtro<input type="text" data-gep-project-editor-field="era" placeholder="2014-2016"><small class="gep-muted">Se usa en la línea de tiempo de proyectos cerrados.</small></label><label>Institución / financiamiento<input type="text" data-gep-project-editor-field="institution" placeholder="FONDECYT / Universidad..."></label></div>
                                <div class="gep-grid-2"><label>Área / tema<input type="text" data-gep-project-editor-field="theme" placeholder="Teledetección"></label><label>Etiqueta lateral<input type="text" data-gep-project-editor-field="tag" placeholder="Proyecto GEP"></label></div>
                                <label>Título / institución <span class="gep-required-dot">Obligatorio</span><input type="text" data-gep-project-editor-field="title"></label>
                                <label>Descripción corta<textarea data-gep-project-editor-field="text"></textarea></label>
                                <div class="gep-grid-2"><label>Investigador/a responsable<input type="text" data-gep-project-editor-field="lead"></label><label>Enlace actual<input type="url" data-gep-project-editor-field="url" placeholder="https://..."></label></div>
                                <label>Texto de la ficha individual<textarea data-gep-project-editor-field="details" placeholder="Escribe aquí la información que se mostrará al abrir la ficha del proyecto."></textarea></label>
                                <div class="gep-info-note"><span class="dashicons dashicons-info-outline"></span><p>La ficha individual se crea automáticamente e incluye descripción, institución, responsable, galería y enlaces. Si no hay imagen principal, usa un placeholder.</p></div>
                                <div class="gep-admin-field"><span class="gep-field-label">Imagen principal <span class="gep-optional">(opcional)</span></span><div class="gep-image-control"><input type="hidden" class="gep-image-url" data-gep-project-editor-field="image" value=""><div class="gep-image-actions"><button type="button" class="button gep-select-image"><span class="dashicons dashicons-format-image"></span>Seleccionar / cambiar imagen</button><button type="button" class="button-link-delete gep-remove-image" style="display:none">Quitar imagen</button></div><div class="gep-image-preview" style="display:none"><img src="" alt="Vista previa"></div></div></div>
                                <label>Texto alternativo de imagen<input type="text" data-gep-project-editor-field="image_alt"></label>
                                <section class="gep-project-gallery-editor">
                                    <div class="gep-project-gallery-heading"><div><span class="gep-field-label">Galería del proyecto</span><small class="gep-muted">Añade imágenes desde la biblioteca multimedia. Puedes quitarlas, ordenarlas o usar cualquiera como imagen principal.</small></div><button type="button" class="button gep-project-gallery-add"><span class="dashicons dashicons-images-alt2"></span> Añadir imágenes</button></div>
                                    <textarea class="gep-project-gallery-value" data-gep-project-editor-field="gallery" hidden></textarea>
                                    <div class="gep-project-gallery-list"></div>
                                    <div class="gep-project-gallery-empty"><span class="dashicons dashicons-format-gallery"></span><span>Sin imágenes en la galería.</span></div>
                                </section>
                                <details class="gep-optional-panel"><summary><span class="dashicons dashicons-admin-generic"></span> Opciones avanzadas de ficha</summary><div class="gep-optional-panel-body"><div class="gep-grid-2"><label>Identificador de ficha <span class="gep-optional">(URL estable)</span><input type="text" data-gep-project-editor-field="slug" placeholder="proyecto-ejemplo-2026"><small class="gep-muted">Se genera automáticamente si lo dejas vacío.</small></label><label>URL de ficha personalizada <span class="gep-optional">(opcional)</span><input type="url" data-gep-project-editor-field="detail_url" placeholder="https://..."></label></div><label>Texto de enlace externo<input type="text" data-gep-project-editor-field="button" placeholder="Ver proyecto →"></label></div></details>
                            </div>
                        </div>
                    </div>
                    <p class="gep-project-filter-empty" id="gep-project-filter-empty" hidden>No hay proyectos con este estado o búsqueda.</p>
                    <div class="gep-empty-builder <?php echo ! empty( $values['projects'] ) ? 'is-hidden' : ''; ?>" id="gep-project-records-empty"><span class="dashicons dashicons-portfolio"></span><strong>No hay proyectos.</strong></div>
                    <div id="gep-project-record-submit-fields"></div>
                    <div class="gep-admin-field" style="margin-top:18px"><label>Nota informativa</label><textarea id="gep_projects_note" name="gep_projects_note"><?php echo esc_textarea( $values['note'] ); ?></textarea></div>
                </section>

                <section class="gep-step-card" data-project-preview="areas"><div class="gep-step-heading"><span class="gep-step-number">5</span><div><h3>Áreas de trabajo</h3><p>Bloque final con las líneas temáticas del laboratorio.</p></div></div><div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" id="gep_projects_areas_eyebrow" name="gep_projects_areas_eyebrow" value="<?php echo esc_attr( $values['areas_eyebrow'] ); ?>"></div><div class="gep-admin-field"><label>Título <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_projects_areas_title" name="gep_projects_areas_title" value="<?php echo esc_attr( $values['areas_title'] ); ?>" data-gep-project-required="1"></div></div><div class="gep-admin-field"><label>Descripción</label><textarea id="gep_projects_areas_text" name="gep_projects_areas_text"><?php echo esc_textarea( $values['areas_text'] ); ?></textarea></div><div class="gep-team-builder-head"><div><strong>Áreas</strong><p class="gep-muted">Puedes reordenarlas como quieras.</p></div><button type="button" class="button gep-project-add-area"><span class="dashicons dashicons-plus-alt2"></span> Añadir área</button></div><div class="gep-project-builder" id="gep-project-areas"><?php foreach ( $values['areas'] as $k => $row ) gep_demo_render_project_area_row( $k, $row ); ?></div><div class="gep-empty-builder <?php echo ! empty( $values['areas'] ) ? 'is-hidden' : ''; ?>" id="gep-project-areas-empty"><span class="dashicons dashicons-networking"></span><strong>No hay áreas.</strong></div></section>
                <?php /* v9.26.0: se quitó el botón inferior "Guardar cambios"; se guarda desde la barra superior. */ ?>
            </div>

            <aside class="gep-preview-column"><div class="gep-preview-card gep-project-preview-wrap gep-parent-preview-card" id="gep-project-preview-shell" data-public-url="<?php echo esc_url( $public_url ); ?>"><div class="gep-preview-heading gep-directory-preview-heading"><span class="gep-preview-icon"><span class="dashicons dashicons-visibility"></span></span><div class="gep-preview-heading-copy"><div class="gep-preview-heading-title-row"><span class="gep-preview-zone-badge" id="gep-project-preview-zone-number">Zona 1</span><h3 id="gep-project-preview-heading-title">Encabezado y navegación</h3></div></div><div class="gep-preview-device-switcher"><button type="button" class="gep-directory-device-button gep-project-device-button is-active" data-device="desktop"><span class="dashicons dashicons-desktop"></span><span>PC</span></button><button type="button" class="gep-directory-device-button gep-project-device-button" data-device="tablet"><span class="dashicons dashicons-tablet"></span><span>Tablet</span></button><button type="button" class="gep-directory-device-button gep-project-device-button" data-device="mobile"><span class="dashicons dashicons-smartphone"></span><span>Móvil</span></button></div></div>
                <div class="gep-project-device-stage"><div class="gep-project-live-preview is-device-desktop" id="gep-project-live-preview"><div class="gep-directory-live-toolbar"><span></span><span></span><span></span><strong>proyectos.php</strong><em id="gep-project-device-label">Vista escritorio</em></div>
                    <div class="gep-project-live-hero" data-project-live="hero"><span id="gep-proj-prev-hero-eyebrow"></span><h2 id="gep-proj-prev-hero-title"></h2><p id="gep-proj-prev-hero-text"></p></div><div class="gep-project-live-nav"><span id="gep-proj-prev-nav-featured"></span><span id="gep-proj-prev-nav-trajectory"></span><span id="gep-proj-prev-nav-areas"></span></div>
                    <div class="gep-project-live-section" data-project-live="features"><div class="gep-project-preview-features" id="gep-proj-prev-features"></div></div>
                    <div class="gep-project-live-section" data-project-live="trajectory"><div class="gep-project-section-head"><div><span id="gep-proj-prev-traj-eyebrow"></span><h3 id="gep-proj-prev-traj-title"></h3></div><p id="gep-proj-prev-traj-text"></p></div></div>
                    <div class="gep-project-live-section" data-project-live="records"><div class="gep-project-preview-filterbox"><div class="gep-project-preview-filtertop"><strong>Filtrar trayectoria</strong><span id="gep-proj-preview-count"></span></div><div class="gep-project-preview-filterrow"><div class="gep-project-preview-tools"><label><span class="dashicons dashicons-search"></span><input id="gep-proj-preview-search" type="search" placeholder="Buscar institución, proyecto o tema…"></label></div><div class="gep-project-preview-pills" id="gep-proj-preview-pills"></div></div></div><div class="gep-project-preview-records" id="gep-proj-preview-records"></div><div class="gep-project-preview-empty" id="gep-proj-preview-empty">No hay proyectos que coincidan con el filtro.</div><div class="gep-project-preview-note" id="gep-proj-prev-note"></div></div>
                    <div class="gep-project-live-section" data-project-live="areas"><div class="gep-project-section-head"><div><span id="gep-proj-prev-areas-eyebrow"></span><h3 id="gep-proj-prev-areas-title"></h3></div><p id="gep-proj-prev-areas-text"></p></div><div class="gep-project-preview-areas" id="gep-proj-preview-areas"></div></div>
                </div></div></div></aside>
        </div>
        <script type="application/json" id="gep-project-defaults-json"><?php echo wp_json_encode( $defaults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
        <script type="application/json" id="gep-project-records-json"><?php echo wp_json_encode( $values['projects'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
        <script type="text/html" id="gep-project-feature-template"><?php gep_demo_render_project_feature_row( '__K__', array() ); ?></script>
        <script type="text/html" id="gep-project-record-template"><?php gep_demo_render_project_record_row( '__K__', array() ); ?></script>
        <script type="text/html" id="gep-project-area-template"><?php gep_demo_render_project_area_row( '__K__', array() ); ?></script>
    </form>
    <?php
}


function gep_demo_publication_category_options() {
    return array('libros'=>'Libros','capitulos'=>'Capítulos de libro','articulos'=>'Artículos','congresos'=>'Congresos','otros'=>'Otros');
}
function gep_demo_publication_normalize_category( $category ) {
    $category=sanitize_key((string)$category);
    if(in_array($category,array('internacionales','nacionales'),true))return 'articulos';
    if('libros-free'===$category)return 'otros';
    if('capitulos-libro'===$category)return 'capitulos';
    return in_array($category,array('libros','capitulos','articulos','congresos','otros'),true)?$category:'otros';
}
function gep_demo_normalize_publications_data( $values ) {
    if(!is_array($values))$values=array();
    $old_sections=isset($values['sections'])&&is_array($values['sections'])?$values['sections']:array();
    $defaults=array(
      'libros'=>array('label'=>'Libros','eyebrow'=>'Producción editorial','title'=>'Libros'),
      'capitulos'=>array('label'=>'Capítulos de libro','eyebrow'=>'Producción editorial','title'=>'Capítulos de libro'),
      'articulos'=>array('label'=>'Artículos','eyebrow'=>'Revistas','title'=>'Artículos científicos'),
      'congresos'=>array('label'=>'Congresos','eyebrow'=>'Divulgación académica','title'=>'Congresos y encuentros científicos'),
      'otros'=>array('label'=>'Otros','eyebrow'=>'Otros formatos','title'=>'Otros materiales y publicaciones'),
    );
    $sections=$defaults;
    if(isset($old_sections['libros'])&&is_array($old_sections['libros']))$sections['libros']=array_merge($sections['libros'],$old_sections['libros']);
    if(isset($old_sections['congresos'])&&is_array($old_sections['congresos']))$sections['congresos']=array_merge($sections['congresos'],$old_sections['congresos']);
    $records=array(); foreach(isset($values['records'])&&is_array($values['records'])?$values['records']:array() as $r){if(!is_array($r))continue;$original=isset($r['category'])?sanitize_key($r['category']):'';$r['category']=gep_demo_publication_normalize_category($original);if('libros'===$original){$hay=(isset($r['title'])?$r['title']:'').' '.(isset($r['text'])?$r['text']:'');if(preg_match('/\bEn:\s|\(Cap\.|Cap[ií]tulo/i',$hay))$r['category']='capitulos';}$records[]=$r;}
    $values['sections']=$sections;$values['records']=$records;
    return $values;
}
function gep_demo_render_publication_record_row( $row_key, $row = array() ) {
    $row      = is_array( $row ) ? $row : array();
    $category = isset( $row['category'] ) ? gep_demo_publication_normalize_category( $row['category'] ) : 'articulos';
    $year     = isset( $row['year'] ) ? $row['year'] : '';
    $title    = isset( $row['title'] ) ? $row['title'] : '';
    $body     = isset( $row['text'] ) ? $row['text'] : '';
    $cats     = gep_demo_publication_category_options();
    $summary  = $title ? $title : wp_trim_words( $body, 13, '…' );
    ?>
    <div class="gep-publication-list-row" data-publication-key="<?php echo esc_attr( $row_key ); ?>">
        <span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar" aria-hidden="true"></span>
        <button type="button" class="gep-publication-list-open" aria-label="Editar publicación">
            <span class="gep-item-summary-badge gep-publication-year-summary"><?php echo esc_html( $year ? $year : 'AÑO' ); ?></span>
            <strong class="gep-publication-title-summary"><?php echo esc_html( $summary ? $summary : 'Nueva publicación' ); ?></strong>
            <span class="gep-publication-category-summary"><?php echo esc_html( isset( $cats[ $category ] ) ? $cats[ $category ] : $category ); ?></span>
        </button>
        <span class="dashicons dashicons-arrow-right-alt2 gep-publication-list-arrow" aria-hidden="true"></span>
    </div>
    <?php
}
function gep_demo_render_publications_editor( $post_id, $values ) {
    $public_url=gep_demo_publications_public_url($post_id);
    $defaults=gep_demo_publications_defaults();
    $cats=gep_demo_publication_category_options();
    $sections=isset($values['sections'])&&is_array($values['sections'])?$values['sections']:array();
    ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="gep-content-form" novalidate>
      <input type="hidden" name="action" value="gep_demo_save_content"><input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>"><input type="hidden" name="gep_editor_context" value="publications"><?php wp_nonce_field('gep_demo_save_content_'.$post_id,'gep_demo_admin_nonce'); ?>
      <div class="gep-contact-commandbar gep-publication-commandbar"><div class="gep-contact-commandbar-copy"><span class="gep-contact-command-icon"><span class="dashicons dashicons-media-document"></span></span><div><strong>Editor de Publicaciones</strong><small>Administra los 93 registros, filtros y textos sin tocar código.</small></div><span class="gep-inline-save-state is-clean" id="gep-publication-inline-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span></div><div class="gep-contact-commandbar-actions"><button type="button" class="button gep-restore-publications"><span class="dashicons dashicons-image-rotate"></span> Restaurar original</button><?php /* v9.25.0: "Ver página real" se quitó de esta barra; el acceso queda en "Sitio público" del menú lateral. */ ?><button type="submit" class="button button-primary gep-save-button" disabled><span class="dashicons dashicons-saved"></span> Guardar cambios</button></div></div>
      <div class="gep-editor-layout gep-editor-layout-publications"><div class="gep-questionnaire">
                <?php gep_demo_render_section_visibility_tools(); ?>
        <section class="gep-step-card" data-publication-preview="hero"><div class="gep-step-heading"><span class="gep-step-number">1</span><div><h3>Encabezado y navegación</h3><p>Presentación y accesos rápidos de Publicaciones.</p></div></div>
          <div class="gep-admin-field"><label>Texto superior</label><input type="text" id="gep_publications_hero_eyebrow" name="gep_publications_hero_eyebrow" value="<?php echo esc_attr($values['hero_eyebrow']); ?>"></div>
          <div class="gep-admin-field"><label>Título <span class="gep-required-dot">Obligatorio</span></label><input type="text" id="gep_publications_hero_title" name="gep_publications_hero_title" value="<?php echo esc_attr($values['hero_title']); ?>" data-gep-publication-required="1"></div>
          <div class="gep-admin-field"><label>Descripción</label><textarea id="gep_publications_hero_text" name="gep_publications_hero_text"><?php echo esc_textarea($values['hero_text']); ?></textarea></div><div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen hero / cabecera</span><?php gep_demo_render_image_control( 'gep_publications_hero_image', $values['hero_image'], 'Seleccionar / cambiar imagen hero' ); ?></div><div class="gep-admin-field gep-media-field gep-hero-video-field"><span class="gep-field-label">Video hero / cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_video_control( 'gep_publications_hero_video', isset($values['hero_video'])?$values['hero_video']:'', 'Seleccionar / cambiar video hero' ); ?></div>
        </section>
        <?php // v9.24.0: el Buscador es un apartado propio (mismos campos y nombres que antes). ?>
        <section class="gep-step-card" data-publication-preview="search"><div class="gep-step-heading"><span class="gep-step-number">2</span><div><h3>Buscador</h3><p>Textos del buscador público y de su acceso en la navegación.</p></div></div>
          <div class="gep-grid-2"><div class="gep-admin-field"><label>Título del buscador público</label><input type="text" id="gep_publications_explorer_title" name="gep_publications_explorer_title" value="<?php echo esc_attr($values['explorer_title']); ?>"></div><div class="gep-admin-field"><label>Navegación: buscador</label><input type="text" id="gep_publications_nav_search" name="gep_publications_nav_search" value="<?php echo esc_attr(isset($values['nav_search'])?$values['nav_search']:'Buscar y filtrar'); ?>"></div></div>
          <p class="gep-muted">El buscador filtra por texto, año y categoría. Orden público fijo de los filtros: <strong>Todos → Libros → Capítulos de libro → Artículos → Congresos → Otros</strong>.</p>
        </section>
        <section class="gep-step-card" data-publication-preview="sections"><div class="gep-step-heading"><span class="gep-step-number">3</span><div><h3>Filtros por categoría</h3><p>Nombre de cada filtro y título de su sección en la lista.</p></div></div><div class="gep-publication-section-settings">
          <?php foreach($cats as $key=>$fallback_label): $meta=isset($sections[$key])&&is_array($sections[$key])?$sections[$key]:array(); $label=isset($meta['label'])?$meta['label']:$fallback_label; $eyebrow=isset($meta['eyebrow'])?$meta['eyebrow']:''; $title=isset($meta['title'])?$meta['title']:''; ?>
          <div class="gep-publication-section-card"><strong><?php echo esc_html($fallback_label); ?></strong><div class="gep-grid-2"><label>Nombre del filtro<input type="text" name="gep_publications_sections[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($label); ?>"></label><label>Texto superior<input type="text" name="gep_publications_sections[<?php echo esc_attr($key); ?>][eyebrow]" value="<?php echo esc_attr($eyebrow); ?>"></label></div><label>Título de sección<input type="text" name="gep_publications_sections[<?php echo esc_attr($key); ?>][title]" value="<?php echo esc_attr($title); ?>"></label></div>
          <?php endforeach; ?>
        </div></section>
        <section class="gep-step-card" data-publication-preview="records"><div class="gep-step-heading"><span class="gep-step-number">4</span><div><h3>Registros bibliográficos</h3><p>Busca, filtra, duplica, ordena o añade publicaciones.</p></div></div>
          <div class="gep-grid-2"><div class="gep-admin-field"><label>Mostrar en esta lista</label><select id="gep-publication-editor-category"><option value="all">Todas</option><?php foreach($cats as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div></div>
          <div class="gep-project-record-toolbar gep-publication-record-toolbar"><label class="gep-project-editor-search"><span class="dashicons dashicons-search"></span><input type="search" id="gep-publication-editor-search" placeholder="Buscar autor, título, año…"></label><span id="gep-publication-editor-count"></span><button type="button" class="button gep-publication-sort-year"><span class="dashicons dashicons-sort"></span> Ordenar por año ↓</button><button type="button" class="button gep-publication-add"><span class="dashicons dashicons-plus-alt2"></span> Añadir publicación</button><button type="button" class="button gep-doi-open" aria-controls="gep-doi-panel" aria-expanded="false"><span class="dashicons dashicons-download"></span> Importar por DOI</button></div>
          <?php if ( function_exists( 'gep_demo_render_doi_panel' ) ) gep_demo_render_doi_panel(); ?>
          <div class="gep-publication-workspace">
            <div class="gep-project-builder gep-publication-builder" id="gep-publication-records"><?php foreach($values['records'] as $k=>$row) gep_demo_render_publication_record_row($k,$row); ?></div>
            <div id="gep-publication-editor-parking" class="gep-publication-editor-parking">
              <div class="gep-publication-single-editor is-hidden" id="gep-publication-single-editor">
                <div class="gep-publication-single-head"><div><span>Editando publicación</span><strong id="gep-publication-active-summary">Publicación</strong></div><button type="button" class="button-link gep-publication-close-editor" aria-label="Cerrar editor"><span class="dashicons dashicons-no-alt"></span></button></div>
                <div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-publication-move-up">Subir</button><button type="button" class="button button-small gep-publication-move-down">Bajar</button><button type="button" class="button button-small gep-publication-duplicate"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-publication-remove"><span class="dashicons dashicons-trash"></span> Eliminar</button></div>
                <div class="gep-grid-2"><label>Categoría<select id="gep-publication-edit-category" data-gep-publication-editor-field="category"><?php foreach($cats as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label><label>Año <span class="gep-required-dot">Obligatorio</span><input id="gep-publication-edit-year" type="text" data-gep-publication-editor-field="year" placeholder="2016"></label></div>
                <label>Título <span class="gep-optional">(opcional)</span><input id="gep-publication-edit-title" type="text" data-gep-publication-editor-field="title" placeholder="Título del recurso"></label>
                <label>Referencia / descripción <span class="gep-required-dot">Obligatorio</span><textarea id="gep-publication-edit-text" data-gep-publication-editor-field="text"></textarea></label>
                <div class="gep-grid-2"><label>Enlace <span class="gep-optional">(opcional)</span><input id="gep-publication-edit-url" type="url" data-gep-publication-editor-field="url" placeholder="https://..."></label><label>Texto del enlace<input id="gep-publication-edit-button" type="text" data-gep-publication-editor-field="button" placeholder="Abrir ↗"></label></div>
                <label>DOI <span class="gep-optional">(opcional)</span><input id="gep-publication-edit-doi" type="text" data-gep-publication-editor-field="doi" placeholder="10.4067/S0717-92002014000300002"></label>
                <p class="gep-muted">La categoría determina automáticamente en qué filtro y sección pública aparecerá el registro.</p>
              </div>
            </div>
            <div class="gep-empty-builder <?php echo !empty($values['records'])?'is-hidden':''; ?>" id="gep-publication-records-empty"><span class="dashicons dashicons-media-document"></span><strong>No hay publicaciones.</strong></div>
          </div>
          <div id="gep-publication-submit-fields" aria-hidden="true"></div>
          <div class="gep-admin-field" style="margin-top:18px"><label>Nota informativa</label><textarea id="gep_publications_note" name="gep_publications_note"><?php echo esc_textarea($values['note']); ?></textarea></div>
        </section>
        <?php /* v9.26.0: se quitó el botón inferior "Guardar cambios"; se guarda desde la barra superior. */ ?>
      </div>
      <aside class="gep-preview-column"><div class="gep-preview-card gep-publication-preview-wrap gep-parent-preview-card" id="gep-publication-preview-shell" data-public-url="<?php echo esc_url( $public_url ); ?>"><div class="gep-preview-heading gep-directory-preview-heading"><span class="gep-preview-icon"><span class="dashicons dashicons-visibility"></span></span><div class="gep-preview-heading-copy"><div class="gep-preview-heading-title-row"><span class="gep-preview-zone-badge" id="gep-publication-preview-zone-number">Zona 1</span><h3 id="gep-publication-preview-heading-title">Encabezado y navegación</h3></div></div><div class="gep-preview-device-switcher"><button type="button" class="gep-directory-device-button gep-publication-device-button is-active" data-device="desktop"><span class="dashicons dashicons-desktop"></span><span>PC</span></button><button type="button" class="gep-directory-device-button gep-publication-device-button" data-device="tablet"><span class="dashicons dashicons-tablet"></span><span>Tablet</span></button><button type="button" class="gep-directory-device-button gep-publication-device-button" data-device="mobile"><span class="dashicons dashicons-smartphone"></span><span>Móvil</span></button></div></div>
        <div class="gep-publication-device-stage"><div class="gep-publication-live-preview is-device-desktop" id="gep-publication-live-preview"><div class="gep-directory-live-toolbar"><span></span><span></span><span></span><strong>publicaciones.php</strong><em id="gep-publication-device-label">Vista escritorio</em></div>
          <div class="gep-publication-live-hero" data-publication-live="hero"><span id="gep-pub-prev-hero-eyebrow"></span><h2 id="gep-pub-prev-hero-title"></h2><p id="gep-pub-prev-hero-text"></p></div><div class="gep-publication-live-nav" data-publication-live="sections"><span id="gep-pub-prev-nav-search"></span><span>Libros</span><span>Capítulos</span><span>Artículos</span><span>Congresos</span><span>Otros</span></div>
          <div class="gep-publication-live-body gep-publication-live-stats" data-publication-live="records-stats"><div class="gep-publication-preview-stats"><div><strong id="gep-pub-stat-total">0</strong><span>registros reunidos</span></div><div><strong id="gep-pub-stat-int">0</strong><span>artículos</span></div><div><strong id="gep-pub-stat-nac">0</strong><span>libros + capítulos</span></div><div><strong id="gep-pub-stat-cong">0</strong><span>congresos</span></div></div></div>
          <div class="gep-publication-live-body gep-publication-live-search" data-publication-live="search"><div class="gep-publication-preview-tools"><div class="gep-publication-preview-top"><strong id="gep-pub-prev-explorer-title"></strong><span id="gep-pub-preview-count"></span></div><div class="gep-publication-preview-pills" id="gep-pub-preview-pills"></div><div class="gep-publication-preview-searchrow"><label><span class="dashicons dashicons-search"></span><input id="gep-pub-preview-search" type="search" placeholder="Buscar autor, título, revista…"></label><select id="gep-pub-preview-year"><option value="all">Todos los años</option></select></div></div></div>
          <div class="gep-publication-live-body gep-publication-live-records" data-publication-live="records"><div id="gep-pub-preview-sections"></div><div class="gep-publication-preview-empty" id="gep-pub-preview-empty">No hay publicaciones que coincidan con la búsqueda.</div><div class="gep-publication-preview-note" id="gep-pub-prev-note"></div>
          </div>
        </div></div>
      </div></aside></div>
      <script type="application/json" id="gep-publications-defaults-json"><?php echo wp_json_encode($defaults,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script><script type="application/json" id="gep-publications-records-json"><?php echo wp_json_encode($values['records'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
    </form>
    <?php
}

function gep_demo_home_row_title( $kind, $row ) {
    if ( 'stat' === $kind ) return !empty($row['label'])?$row['label']:'Nueva cifra';
    if ( 'definition' === $kind ) return !empty($row['title'])?$row['title']:'Nuevo concepto';
    if ( in_array($kind,array('research','portal','resource','network'),true) ) return !empty($row['title'])?$row['title']:'Nuevo elemento';
    return !empty($row['text'])?$row['text']:'Nuevo elemento';
}
function gep_demo_render_home_repeat_row( $kind, $key, $row=array() ) {
    $row=(in_array($kind,array('pill','topic'),true) && !is_array($row))?array('text'=>(string)$row):(is_array($row)?$row:array()); $title=gep_demo_home_row_title($kind,$row); $prefix_map=array('research'=>'research_cards','portal'=>'portal_cards','resource'=>'resources','network'=>'network_links','definition'=>'definitions','stat'=>'stats','pill'=>'pills','topic'=>'topics'); $prefix='gep_home_'.(isset($prefix_map[$kind])?$prefix_map[$kind]:'topics');
    ?>
    <details class="gep-home-repeat-row" data-home-kind="<?php echo esc_attr($kind); ?>" data-home-key="<?php echo esc_attr($key); ?>">
      <summary><span class="gep-grip dashicons dashicons-menu-alt2"></span><strong class="gep-home-row-summary"><?php echo esc_html($title); ?></strong><span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span></summary>
      <div class="gep-home-row-body">
        <div class="gep-home-row-actions"><button type="button" class="button button-small gep-home-up"><span class="dashicons dashicons-arrow-up-alt2"></span></button><button type="button" class="button button-small gep-home-down"><span class="dashicons dashicons-arrow-down-alt2"></span></button><button type="button" class="button button-small gep-home-duplicate"><span class="dashicons dashicons-admin-page"></span> Duplicar</button><button type="button" class="button-link-delete gep-home-remove"><span class="dashicons dashicons-trash"></span> Eliminar</button></div>
        <?php if(in_array($kind,array('pill','topic'),true)): ?><label>Texto<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][text]'); ?>" value="<?php echo esc_attr(isset($row['text'])?$row['text']:''); ?>"></label>
        <?php elseif('stat'===$kind): ?><div class="gep-grid-2"><label>Cifra<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][value]'); ?>" value="<?php echo esc_attr($row['value']??''); ?>"></label><label>Etiqueta<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][label]'); ?>" value="<?php echo esc_attr($row['label']??''); ?>"></label></div><label>Texto<textarea name="<?php echo esc_attr($prefix.'['.$key.'][text]'); ?>"><?php echo esc_textarea($row['text']??''); ?></textarea></label>
        <?php elseif('definition'===$kind): ?><div class="gep-grid-2"><label>Eyebrow<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][eyebrow]'); ?>" value="<?php echo esc_attr($row['eyebrow']??''); ?>"></label><label>Título<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][title]'); ?>" value="<?php echo esc_attr($row['title']??''); ?>"></label></div><label>Descripción<textarea name="<?php echo esc_attr($prefix.'['.$key.'][text]'); ?>"><?php echo esc_textarea($row['text']??''); ?></textarea></label>
        <?php elseif('research'===$kind): ?><div class="gep-grid-2"><label>Etiqueta<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][kicker]'); ?>" value="<?php echo esc_attr($row['kicker']??''); ?>"></label><label>Título<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][title]'); ?>" value="<?php echo esc_attr($row['title']??''); ?>"></label></div><label>Descripción<textarea name="<?php echo esc_attr($prefix.'['.$key.'][text]'); ?>"><?php echo esc_textarea($row['text']??''); ?></textarea></label><div class="gep-grid-2"><div class="gep-admin-field"><span class="gep-field-label">Imagen</span><?php gep_demo_render_image_control($prefix.'['.$key.'][image]',$row['image']??'','Seleccionar imagen'); ?></div><label>Texto alternativo<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][alt]'); ?>" value="<?php echo esc_attr($row['alt']??''); ?>"></label></div>
        <?php elseif('portal'===$kind): ?><div class="gep-grid-2"><label>Icono / letra<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][icon]'); ?>" value="<?php echo esc_attr($row['icon']??''); ?>"></label><label>Título<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][title]'); ?>" value="<?php echo esc_attr($row['title']??''); ?>"></label></div><label>Descripción<textarea name="<?php echo esc_attr($prefix.'['.$key.'][text]'); ?>"><?php echo esc_textarea($row['text']??''); ?></textarea></label><div class="gep-grid-2"><label>Enlace<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][url]'); ?>" value="<?php echo esc_attr($row['url']??''); ?>"></label><label>Texto del enlace<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][button]'); ?>" value="<?php echo esc_attr($row['button']??''); ?>"></label></div>
        <?php elseif('resource'===$kind): ?><div class="gep-grid-2"><label>Etiqueta<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][kicker]'); ?>" value="<?php echo esc_attr($row['kicker']??''); ?>"></label><label>Título<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][title]'); ?>" value="<?php echo esc_attr($row['title']??''); ?>"></label></div><div class="gep-grid-2"><label>Enlace<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][url]'); ?>" value="<?php echo esc_attr($row['url']??''); ?>"></label><label>Texto del enlace<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][button]'); ?>" value="<?php echo esc_attr($row['button']??''); ?>"></label></div>
        <?php elseif('network'===$kind): ?><div class="gep-grid-2"><label>Nombre<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][title]'); ?>" value="<?php echo esc_attr($row['title']??''); ?>"></label><label>Enlace<input type="text" name="<?php echo esc_attr($prefix.'['.$key.'][url]'); ?>" value="<?php echo esc_attr($row['url']??''); ?>"></label></div><?php endif; ?>
      </div>
    </details>
    <?php
}
function gep_demo_render_home_list( $kind, $rows ) { ?><div class="gep-home-repeat-list" id="gep-home-<?php echo esc_attr($kind); ?>-list" data-home-list="<?php echo esc_attr($kind); ?>"><?php foreach((array)$rows as $k=>$r) gep_demo_render_home_repeat_row($kind,$k,$r); ?></div><button type="button" class="button gep-home-add" data-home-add="<?php echo esc_attr($kind); ?>"><span class="dashicons dashicons-plus-alt2"></span> Añadir</button><?php }
function gep_demo_render_home_editor( $post_id, $v ) {
    $defaults=gep_demo_home_defaults(); $page_url=gep_demo_home_public_url($post_id); $asset_base=preg_replace('#/index\.php(?:\?.*)?$#','',$page_url); ?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="gep-content-form" novalidate><input type="hidden" name="action" value="gep_demo_save_content"><input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>"><input type="hidden" name="gep_editor_context" value="home"><?php wp_nonce_field('gep_demo_save_content_'.$post_id,'gep_demo_admin_nonce'); ?>
<div class="gep-contact-commandbar gep-home-commandbar"><div class="gep-contact-commandbar-copy"><span class="gep-contact-command-icon"><span class="dashicons dashicons-admin-home"></span></span><div><strong>Editor de Inicio</strong><small>Edita la portada completa y revisa todos los bloques en la preview.</small></div><span class="gep-inline-save-state is-clean" id="gep-home-inline-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span></div><div class="gep-contact-commandbar-actions"><button type="button" class="button gep-restore-home"><span class="dashicons dashicons-undo"></span> Restaurar original</button><?php /* v9.25.0: "Ver página real" se quitó de esta barra; el acceso queda en "Sitio público" del menú lateral. */ ?><button type="submit" class="button button-primary gep-save-button" disabled><span class="dashicons dashicons-saved"></span> Guardar cambios</button></div></div>
<div class="gep-editor-layout gep-editor-layout-home"><div class="gep-questionnaire">
                <?php gep_demo_render_section_visibility_tools(); ?>
<?php
$sections=array(
 ['hero','1','Hero principal','Título, descripción, botones y etiquetas geográficas.'],['intro','2','Laboratorio GEP','Presentación, párrafos y temas destacados.'],['stats','3','GEP en cifras','Cifras destacadas de la portada.'],['definitions','4','Conceptos clave','Geomática, Ecología del Paisaje y otros conceptos.'],['field','5','Registro visual','Foto territorial y su pie de foto.'],['research','6','Líneas de trabajo','Tarjetas con imagen de las áreas de investigación.'],['portal','7','Explorar GEP','Accesos directos a las páginas del sitio.'],['landscape','8','Mensaje de paisaje','Franja editorial de transición.'],['resources','9','Recursos destacados','Plataformas, iniciativas y materiales.'],['topics','10','Biblioteca temática','Nube de temas de apoyo.'],['network','11','Herramientas y datos','Imagen y enlaces externos de herramientas/fuentes.'],['final','12','Nota y CTA final','Nota institucional y llamada final a la acción.']
);
foreach($sections as $sec){ echo '<details class="gep-step-card gep-home-step" data-home-preview="'.esc_attr($sec[0]).'"><summary class="gep-step-heading"><span class="gep-step-number">'.esc_html($sec[1]).'</span><div><h3>'.esc_html($sec[2]).'</h3><p>'.esc_html($sec[3]).'</p></div><span class="dashicons dashicons-arrow-down-alt2 gep-home-chevron"></span></summary><div class="gep-home-panel-body">';
 if($sec[0]==='hero'){ ?>
<div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow</label><input id="gep_home_hero_eyebrow" type="text" name="gep_home_hero_eyebrow" value="<?php echo esc_attr($v['hero_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_hero_title" type="text" name="gep_home_hero_title" value="<?php echo esc_attr($v['hero_title']); ?>"></div></div><div class="gep-admin-field"><label>Descripción</label><textarea id="gep_home_hero_text" name="gep_home_hero_text"><?php echo esc_textarea($v['hero_text']); ?></textarea></div><div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen hero / cabecera</span><?php gep_demo_render_image_control('gep_home_hero_image',$v['hero_image'],'Seleccionar / cambiar imagen hero'); ?></div><div class="gep-admin-field gep-media-field gep-hero-video-field"><span class="gep-field-label">Video hero / cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_video_control('gep_home_hero_video',isset($v['hero_video'])?$v['hero_video']:'','Seleccionar / cambiar video hero'); ?></div><?php /* v9.26.0: botones con editor visual común (includes/button-editor.php). */ $gep_home_anchors=array('#laboratorio'=>'Inicio › Laboratorio GEP (más abajo)'); gep_buttons_render_pair(
    array('role'=>'primary','title'=>'Botón principal','hint'=>'Destacado, con relleno','text_name'=>'gep_home_hero_primary_text','text_id'=>'gep_home_hero_primary_text','text_value'=>$v['hero_primary_text'],'url_name'=>'gep_home_hero_primary_url','url_id'=>'gep_home_hero_primary_url','url_value'=>$v['hero_primary_url'],'off_key'=>'gep_home_hero_primary_off','off_value'=>isset($v['hero_primary_off'])?$v['hero_primary_off']:'','anchors'=>$gep_home_anchors,'preview'=>'#hp-hero-primary'),
    array('role'=>'secondary','title'=>'Botón secundario','hint'=>'Más discreto, con borde','text_name'=>'gep_home_hero_secondary_text','text_id'=>'gep_home_hero_secondary_text','text_value'=>$v['hero_secondary_text'],'url_name'=>'gep_home_hero_secondary_url','url_id'=>'gep_home_hero_secondary_url','url_value'=>$v['hero_secondary_url'],'off_key'=>'gep_home_hero_secondary_off','off_value'=>isset($v['hero_secondary_off'])?$v['hero_secondary_off']:'','anchors'=>$gep_home_anchors,'preview'=>'#hp-hero-secondary')
); ?><div class="gep-grid-2"><div class="gep-admin-field"><label>Etiqueta geográfica A</label><input id="gep_home_hero_geo_a" type="text" name="gep_home_hero_geo_a" value="<?php echo esc_attr($v['hero_geo_a']); ?>"></div><div class="gep-admin-field"><label>Etiqueta geográfica B</label><input id="gep_home_hero_geo_b" type="text" name="gep_home_hero_geo_b" value="<?php echo esc_attr($v['hero_geo_b']); ?>"></div></div>
<?php } elseif($sec[0]==='intro'){ ?><div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow</label><input id="gep_home_intro_eyebrow" type="text" name="gep_home_intro_eyebrow" value="<?php echo esc_attr($v['intro_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_intro_title" type="text" name="gep_home_intro_title" value="<?php echo esc_attr($v['intro_title']); ?>"></div></div><div class="gep-admin-field"><label>Párrafo 1</label><textarea id="gep_home_intro_p1" name="gep_home_intro_p1"><?php echo esc_textarea($v['intro_p1']); ?></textarea></div><div class="gep-admin-field"><label>Párrafo 2</label><textarea id="gep_home_intro_p2" name="gep_home_intro_p2"><?php echo esc_textarea($v['intro_p2']); ?></textarea></div><span class="gep-field-label">Temas destacados <span class="gep-optional">(se muestran debajo de los párrafos)</span></span><?php gep_demo_render_home_list('pill',$v['pills']); ?>
<?php } elseif($sec[0]==='stats'){ gep_demo_render_home_list('stat',$v['stats']);
} elseif($sec[0]==='definitions'){ gep_demo_render_home_list('definition',$v['definitions']);
} elseif($sec[0]==='field'){ ?><div class="gep-grid-2"><div class="gep-admin-field"><span class="gep-field-label">Imagen</span><?php gep_demo_render_image_control('gep_home_field_image',$v['field_image'],'Seleccionar imagen'); ?></div><div><div class="gep-admin-field"><label>Texto alternativo</label><input id="gep_home_field_alt" type="text" name="gep_home_field_alt" value="<?php echo esc_attr($v['field_alt']); ?>"></div><div class="gep-admin-field"><label>Índice visual</label><input id="gep_home_field_index" type="text" name="gep_home_field_index" value="<?php echo esc_attr($v['field_index']); ?>"></div></div></div><div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow</label><input id="gep_home_field_eyebrow" type="text" name="gep_home_field_eyebrow" value="<?php echo esc_attr($v['field_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_field_title" type="text" name="gep_home_field_title" value="<?php echo esc_attr($v['field_title']); ?>"></div></div><div class="gep-admin-field"><label>Texto</label><textarea id="gep_home_field_text" name="gep_home_field_text"><?php echo esc_textarea($v['field_text']); ?></textarea></div>
<?php } elseif($sec[0]==='research'){ ?><div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow</label><input id="gep_home_research_eyebrow" type="text" name="gep_home_research_eyebrow" value="<?php echo esc_attr($v['research_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_research_title" type="text" name="gep_home_research_title" value="<?php echo esc_attr($v['research_title']); ?>"></div></div><div class="gep-admin-field"><label>Texto</label><textarea id="gep_home_research_text" name="gep_home_research_text"><?php echo esc_textarea($v['research_text']); ?></textarea></div><?php gep_demo_render_home_list('research',$v['research_cards']);
} elseif($sec[0]==='portal'){ ?><div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow</label><input id="gep_home_portal_eyebrow" type="text" name="gep_home_portal_eyebrow" value="<?php echo esc_attr($v['portal_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_portal_title" type="text" name="gep_home_portal_title" value="<?php echo esc_attr($v['portal_title']); ?>"></div></div><div class="gep-admin-field"><label>Texto</label><textarea id="gep_home_portal_text" name="gep_home_portal_text"><?php echo esc_textarea($v['portal_text']); ?></textarea></div><?php gep_demo_render_home_list('portal',$v['portal_cards']);
} elseif($sec[0]==='landscape'){ ?><div class="gep-grid-2"><div class="gep-admin-field"><label>Etiqueta</label><input id="gep_home_landscape_label" type="text" name="gep_home_landscape_label" value="<?php echo esc_attr($v['landscape_label']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_landscape_title" type="text" name="gep_home_landscape_title" value="<?php echo esc_attr($v['landscape_title']); ?>"></div></div><div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen de fondo <span class="gep-optional">(opcional)</span></span><p class="gep-muted">Si no eliges una, se mantiene la foto actual de la franja.</p><?php gep_demo_render_image_control('gep_home_landscape_image',isset($v['landscape_image'])?$v['landscape_image']:'','Seleccionar / cambiar imagen'); ?></div><?php $gep_lf_focus=isset($v['landscape_focus'])?(string)$v['landscape_focus']:'';$gep_lf_fit=isset($v['landscape_fit'])&&'cover'===$v['landscape_fit']?'cover':'auto'; ?><div class="gep-admin-field gep-landscape-frame" data-default-focus="50% 46%"><span class="gep-field-label">Forma de la franja</span><p class="gep-muted">Con una foto propia, la franja toma la forma de la foto para no ampliarla ni recortarla de más. Para elegir qué parte se ve, usa «Encuadre de la foto» junto a la imagen.</p><div class="gep-landscape-picker" tabindex="0" role="button" aria-label="Elegir el punto de la foto que siempre se verá. También puedes usar las flechas del teclado."><img alt="" src=""><span class="gep-landscape-dot" aria-hidden="true"></span><span class="gep-landscape-crop" aria-hidden="true"></span></div><div class="gep-landscape-frame-row"><label>Altura de la franja<select name="gep_home_landscape_fit" id="gep_home_landscape_fit"><option value="auto"<?php selected($gep_lf_fit,'auto'); ?>>Según la forma de la foto (recomendado)</option><option value="cover"<?php selected($gep_lf_fit,'cover'); ?>>Franja fija, como la foto original</option></select></label><button type="button" class="button gep-landscape-center"><span class="dashicons dashicons-image-crop"></span> Centrar</button></div><small class="gep-landscape-hint"></small><input type="hidden" name="gep_home_landscape_focus" id="gep_home_landscape_focus" value="<?php echo esc_attr($gep_lf_focus); ?>"></div>
<?php } elseif($sec[0]==='resources'){ ?><div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow</label><input id="gep_home_resources_eyebrow" type="text" name="gep_home_resources_eyebrow" value="<?php echo esc_attr($v['resources_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_resources_title" type="text" name="gep_home_resources_title" value="<?php echo esc_attr($v['resources_title']); ?>"></div></div><div class="gep-admin-field"><label>Texto</label><textarea id="gep_home_resources_text" name="gep_home_resources_text"><?php echo esc_textarea($v['resources_text']); ?></textarea></div><?php gep_demo_render_home_list('resource',$v['resources']);
} elseif($sec[0]==='topics'){ ?><div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow</label><input id="gep_home_topics_eyebrow" type="text" name="gep_home_topics_eyebrow" value="<?php echo esc_attr($v['topics_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_topics_title" type="text" name="gep_home_topics_title" value="<?php echo esc_attr($v['topics_title']); ?>"></div></div><div class="gep-admin-field"><label>Texto</label><textarea id="gep_home_topics_text" name="gep_home_topics_text"><?php echo esc_textarea($v['topics_text']); ?></textarea></div><?php gep_demo_render_home_list('topic',$v['topics']);
} elseif($sec[0]==='network'){ ?><div class="gep-grid-2"><div class="gep-admin-field"><span class="gep-field-label">Imagen / logo</span><?php gep_demo_render_image_control('gep_home_network_image',$v['network_image'],'Seleccionar imagen'); ?></div><div class="gep-admin-field"><label>Texto alternativo</label><input id="gep_home_network_alt" type="text" name="gep_home_network_alt" value="<?php echo esc_attr($v['network_alt']); ?>"></div></div><div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow</label><input id="gep_home_network_eyebrow" type="text" name="gep_home_network_eyebrow" value="<?php echo esc_attr($v['network_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título</label><input id="gep_home_network_title" type="text" name="gep_home_network_title" value="<?php echo esc_attr($v['network_title']); ?>"></div></div><div class="gep-admin-field"><label>Texto</label><textarea id="gep_home_network_text" name="gep_home_network_text"><?php echo esc_textarea($v['network_text']); ?></textarea></div><?php gep_demo_render_home_list('network',$v['network_links']);
} elseif($sec[0]==='final'){ ?><div class="gep-admin-field"><label>Nota institucional</label><textarea id="gep_home_note" name="gep_home_note"><?php echo esc_textarea($v['note']); ?></textarea></div><div class="gep-grid-2"><div class="gep-admin-field"><label>Eyebrow CTA</label><input id="gep_home_cta_eyebrow" type="text" name="gep_home_cta_eyebrow" value="<?php echo esc_attr($v['cta_eyebrow']); ?>"></div><div class="gep-admin-field"><label>Título CTA</label><input id="gep_home_cta_title" type="text" name="gep_home_cta_title" value="<?php echo esc_attr($v['cta_title']); ?>"></div></div><?php gep_buttons_render_control(array('role'=>'primary','title'=>'Botón del llamado final','hint'=>'Botón principal, con relleno','text_name'=>'gep_home_cta_button','text_id'=>'gep_home_cta_button','text_value'=>$v['cta_button'],'url_name'=>'gep_home_cta_url','url_id'=>'gep_home_cta_url','url_value'=>$v['cta_url'],'off_key'=>'gep_home_cta_off','off_value'=>isset($v['cta_off'])?$v['cta_off']:'','anchors'=>array('#laboratorio'=>'Inicio › Laboratorio GEP'),'preview'=>'#hp-cta-button')); ?><?php }
 echo '</div></details>'; }
?>
<?php /* v9.26.0: se quitó el botón inferior "Guardar cambios"; se guarda desde la barra superior. */ ?></div>
<aside class="gep-preview-column"><div class="gep-preview-card gep-parent-preview-card" id="gep-home-preview-shell" data-public-url="<?php echo esc_url( $page_url ); ?>"><div class="gep-preview-heading gep-directory-preview-heading"><span class="gep-preview-icon"><span class="dashicons dashicons-visibility"></span></span><div class="gep-preview-heading-copy"><div class="gep-preview-heading-title-row"><span class="gep-preview-zone-badge" id="gep-home-preview-zone-number">Zona 1</span><h3 id="gep-home-preview-heading-title">Hero principal</h3></div></div><div class="gep-preview-device-switcher"><button type="button" class="gep-directory-device-button gep-home-device-button is-active" data-device="desktop"><span class="dashicons dashicons-desktop"></span><span>PC</span></button><button type="button" class="gep-directory-device-button gep-home-device-button" data-device="tablet"><span class="dashicons dashicons-tablet"></span><span>Tablet</span></button><button type="button" class="gep-directory-device-button gep-home-device-button" data-device="mobile"><span class="dashicons dashicons-smartphone"></span><span>Móvil</span></button></div></div><div class="gep-home-device-stage"><div class="gep-home-live-preview is-device-desktop" id="gep-home-live-preview"><div class="gep-directory-live-toolbar"><span></span><span></span><span></span><strong>index.php</strong><em id="gep-home-device-label">Vista escritorio</em></div>
<div class="gep-home-prev-hero" data-home-live="hero"><small id="hp-hero-eyebrow"></small><h2 id="hp-hero-title"></h2><p id="hp-hero-text"></p><div><b id="hp-hero-primary"></b><b id="hp-hero-secondary"></b></div><i id="hp-hero-geo-a"></i><i id="hp-hero-geo-b"></i></div>
<div class="gep-home-prev-section gep-home-prev-intro" data-home-live="intro"><div><small id="hp-intro-eyebrow"></small><h3 id="hp-intro-title"></h3></div><div><p id="hp-intro-p1"></p><p id="hp-intro-p2"></p><div id="hp-pills" class="gep-home-prev-pills"></div></div></div>
<div class="gep-home-prev-stats" id="hp-stats" data-home-live="stats"></div><div class="gep-home-prev-grid2" id="hp-definitions" data-home-live="definitions"></div>
<div class="gep-home-prev-field" data-home-live="field"><div class="gep-home-prev-image" id="hp-field-image"></div><div><small id="hp-field-eyebrow"></small><strong id="hp-field-title"></strong><p id="hp-field-text"></p></div></div>
<div class="gep-home-prev-section" data-home-live="research"><div class="gep-home-prev-head"><div><small id="hp-research-eyebrow"></small><h3 id="hp-research-title"></h3></div><p id="hp-research-text"></p></div><div class="gep-home-prev-cards3" id="hp-research-cards"></div></div>
<div class="gep-home-prev-section" data-home-live="portal"><div class="gep-home-prev-head"><div><small id="hp-portal-eyebrow"></small><h3 id="hp-portal-title"></h3></div><p id="hp-portal-text"></p></div><div class="gep-home-prev-portals" id="hp-portal-cards"></div></div>
<div class="gep-home-prev-landscape" data-home-live="landscape"><small id="hp-landscape-label"></small><h3 id="hp-landscape-title"></h3></div>
<div class="gep-home-prev-section" data-home-live="resources"><div class="gep-home-prev-head"><div><small id="hp-resources-eyebrow"></small><h3 id="hp-resources-title"></h3></div><p id="hp-resources-text"></p></div><div class="gep-home-prev-resources" id="hp-resources"></div></div>
<div class="gep-home-prev-section" data-home-live="topics"><div class="gep-home-prev-head"><div><small id="hp-topics-eyebrow"></small><h3 id="hp-topics-title"></h3></div><p id="hp-topics-text"></p></div><div class="gep-home-prev-topics" id="hp-topics"></div></div>
<div class="gep-home-prev-section gep-home-prev-network" data-home-live="network"><div class="gep-home-prev-network-image" id="hp-network-image"></div><div><small id="hp-network-eyebrow"></small><h3 id="hp-network-title"></h3><p id="hp-network-text"></p><div id="hp-network-links" class="gep-home-prev-pills"></div></div></div>
<div class="gep-home-prev-note" id="hp-note" data-home-live="final"></div><div class="gep-home-prev-cta" data-home-live="final"><div><small id="hp-cta-eyebrow"></small><h3 id="hp-cta-title"></h3></div><b id="hp-cta-button"></b></div>
</div></div></div></aside></div>
<script>window.gepHomeAssetBase=<?php echo wp_json_encode($asset_base); ?>;</script><script type="application/json" id="gep-home-defaults-json"><?php echo wp_json_encode($defaults,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
<?php foreach(array('pill','stat','definition','research','portal','resource','topic','network') as $kind): ?><script type="text/html" id="gep-home-template-<?php echo esc_attr($kind); ?>"><?php gep_demo_render_home_repeat_row($kind,'__K__',array()); ?></script><?php endforeach; ?>
</form><?php
}

function gep_demo_render_admin_page() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( esc_html__( 'No tienes permisos para editar páginas.', 'gep-editor-demo' ) );
    }

    $navigation = gep_demo_get_page_navigation();
    $post_id    = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

    if ( ! $post_id || 'page' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
        $post_id = gep_demo_get_default_editor_page_id( $navigation );
    }

    $post = $post_id ? get_post( $post_id ) : null;

    if ( ! $post ) {
        echo '<div class="wrap"><h1>Contenido GEP</h1><div class="notice notice-warning"><p>No se encontró ninguna página de WordPress para editar.</p></div></div>';
        return;
    }

    $is_home         = gep_demo_is_home_page( $post_id, $navigation );
    $is_directory    = gep_demo_is_directory_page( $post_id, $navigation );
    $is_contact      = gep_demo_is_contact_page( $post_id, $navigation );
    $is_docencia     = gep_demo_is_docencia_page( $post_id, $navigation );
    $is_projects     = gep_demo_is_projects_page( $post_id, $navigation );
    $is_publications = gep_demo_is_publications_page( $post_id, $navigation );
    $is_communications = gep_demo_is_hub_page( $post_id, 'communications', $navigation );
    $is_news = gep_demo_is_hub_page( $post_id, 'news', $navigation );
    $is_services = gep_demo_is_services_page( $post_id, $navigation );
    $values          = $is_directory ? gep_demo_default_values( $post_id ) : array();
    $home_values     = $is_home ? gep_demo_home_values( $post_id ) : array();
    $contact_values  = $is_contact ? gep_demo_contact_values( $post_id ) : array();
    $docencia_values = $is_docencia ? gep_demo_docencia_values( $post_id ) : array();
    $projects_values = $is_projects ? gep_demo_projects_values( $post_id ) : array();
    $publications_values = $is_publications ? gep_demo_publications_values( $post_id ) : array();
    $services_values = $is_services ? gep_demo_services_values( $post_id ) : array();
    $hub_values = $is_communications ? gep_demo_hub_values( $post_id, 'communications' ) : ( $is_news ? gep_demo_hub_values( $post_id, 'news' ) : array() );
    $saved           = isset( $_GET['gep_saved'] ) && '1' === $_GET['gep_saved'];
    $page_view_url = get_permalink( $post_id );
    if ( $is_home ) $page_view_url = gep_demo_home_public_url( $post_id );
    elseif ( $is_contact ) $page_view_url = gep_demo_contact_public_url( $post_id );
    elseif ( $is_docencia ) $page_view_url = gep_demo_docencia_public_url( $post_id );
    elseif ( $is_projects ) $page_view_url = gep_demo_projects_public_url( $post_id );
    elseif ( $is_publications ) $page_view_url = gep_demo_publications_public_url( $post_id );
    elseif ( $is_communications ) $page_view_url = gep_demo_hub_public_url( $post_id, 'communications' );
    elseif ( $is_news ) $page_view_url = gep_demo_hub_public_url( $post_id, 'news' );
    elseif ( $is_services ) $page_view_url = gep_demo_services_public_url( $post_id );
    elseif ( $is_directory ) $page_view_url = gep_demo_directory_public_url( $post_id );
    $plugin_page_url = admin_url( 'admin.php?page=gep-content-editor' );
    $logo_url        = plugins_url( 'assets/gep-logo.png', __FILE__ );
    $help_url        = admin_url( 'admin.php?page=gep-content-help' );
    $current_user    = wp_get_current_user();
    $user_name       = $current_user && $current_user->exists() ? $current_user->display_name : 'Usuario';
    $user_initials   = '';
    foreach ( preg_split( '/\s+/', trim( $user_name ) ) as $piece ) {
        if ( '' !== $piece ) {
            $user_initials .= function_exists( 'mb_substr' ) ? mb_substr( $piece, 0, 1 ) : substr( $piece, 0, 1 );
        }
        if ( strlen( $user_initials ) >= 2 ) break;
    }
    $user_initials = strtoupper( $user_initials ?: 'G' );

    if ( $is_home ) $editor_title = 'Inicio';
    elseif ( $is_directory ) $editor_title = 'Quiénes somos';
    elseif ( $is_contact ) $editor_title = 'Contacto';
    elseif ( $is_docencia ) $editor_title = 'Docencia';
    elseif ( $is_projects ) $editor_title = 'Proyectos';
    elseif ( $is_publications ) $editor_title = 'Publicaciones';
    elseif ( $is_communications ) $editor_title = 'Comunicaciones';
    elseif ( $is_news ) $editor_title = 'Noticias';
    elseif ( $is_services ) $editor_title = 'Servicios';
    else $editor_title = get_the_title( $post_id );

    $editor_descriptions = array(
        'Inicio'         => 'Gestiona la portada del sitio GEP. Edita cada sección y revisa cómo se ve en tiempo real.',
        'Quiénes somos'  => 'Administra la presentación, el equipo y el directorio del laboratorio desde un solo lugar.',
        'Docencia'       => 'Gestiona cursos, programas y bloques docentes con vista previa en tiempo real.',
        'Proyectos'      => 'Organiza proyectos, estados, destacados y contenidos de investigación.',
        'Publicaciones'  => 'Administra publicaciones, filtros y contenidos bibliográficos sin tocar código.',
        'Comunicaciones' => 'Gestiona prensa, eventos, videos y recursos de comunicación del laboratorio.',
        'Noticias'       => 'Publica y organiza noticias, entrevistas y novedades del GEP.',
        'Servicios'      => 'Presenta los servicios del laboratorio: qué incluyen, qué se entrega y cómo solicitarlos.',
        'Contacto'       => 'Actualiza la información institucional, ubicación y llamados de contacto.',
    );
    $editor_description = isset( $editor_descriptions[ $editor_title ] ) ? $editor_descriptions[ $editor_title ] : 'Edita el contenido de esta página y revisa los cambios antes de publicar.';
    ?>
    <div class="gep-editor-loader" id="gep-editor-loader" role="status" aria-live="polite" aria-label="Cargando editor GEP">
        <div class="gep-editor-loader-tree" aria-hidden="true">
            <svg viewBox="0 0 96 96" focusable="false">
                <path class="gep-loader-ground" d="M20 78 C35 72 61 72 76 78" />
                <path class="gep-loader-trunk" d="M48 77 C48 63 47 52 49 39 C50 32 51 25 50 18" />
                <path class="gep-loader-branch gep-loader-branch-a" d="M48 55 C40 49 35 43 31 36" />
                <path class="gep-loader-branch gep-loader-branch-b" d="M49 47 C57 42 63 36 67 29" />
                <path class="gep-loader-branch gep-loader-branch-c" d="M49 64 C58 59 65 54 71 48" />
                <circle class="gep-loader-leaf gep-loader-leaf-1" cx="30" cy="34" r="7" />
                <circle class="gep-loader-leaf gep-loader-leaf-2" cx="50" cy="18" r="8" />
                <circle class="gep-loader-leaf gep-loader-leaf-3" cx="68" cy="28" r="7" />
                <circle class="gep-loader-leaf gep-loader-leaf-4" cx="72" cy="47" r="7" />
                <circle class="gep-loader-leaf gep-loader-leaf-5" cx="39" cy="47" r="6" />
            </svg>
        </div>
        <strong>Preparando el editor GEP</strong>
        <span>Cargando contenido y vista previa…</span>
    </div>
    <div class="wrap gep-editor-wrap">
        <div class="gep-app-header">
            <div class="gep-brand">
                <span class="gep-brand-mark gep-brand-logo"><img src="<?php echo esc_url( $logo_url ); ?>" alt="GEP"></span>
                <div class="gep-brand-copy">
                    <span class="gep-brand-mapbiomas">MAPBIOMAS</span>
                    <strong>GEP</strong>
                    <small>Contenido</small>
                </div>
            </div>
            <div class="gep-save-status <?php echo $saved ? 'is-saved' : ''; ?>" id="gep-save-status">
                <span class="dashicons <?php echo $saved ? 'dashicons-yes-alt' : 'dashicons-edit'; ?>"></span>
                <span><strong><?php echo $saved ? 'Cambios guardados' : 'Listo para editar'; ?></strong><small><?php echo $saved ? 'La página fue actualizada correctamente.' : 'Los cambios se guardan al pulsar el botón.'; ?></small></span>
            </div>
        </div>

        <div class="gep-top-utility">
            <?php if ( class_exists( 'GEP_Media_Organizer' ) && current_user_can( 'upload_files' ) ) : // v9.23.5: acceso directo a las carpetas de imágenes. ?>
            <a class="gep-top-help gep-top-folders" href="<?php echo esc_url( admin_url( 'upload.php?page=gep-media-organizer' ) ); ?>" title="Subir imágenes y ordenar carpetas"><span class="dashicons dashicons-category"></span> Carpetas de imágenes</a>
            <?php endif; ?>
            <a class="gep-top-help" href="<?php echo esc_url( $help_url ); ?>"><span class="dashicons dashicons-editor-help"></span> Ayuda</a>
        </div>

        <button type="button" class="gep-wp-drawer-toggle" id="gep-wp-drawer-toggle" aria-expanded="false" aria-controls="adminmenumain" title="Mostrar menú de WordPress">
            <span class="dashicons dashicons-wordpress"></span>
            <span class="gep-wp-drawer-label">WordPress</span>
        </button>

        <nav class="gep-page-tabs" aria-label="Páginas GEP">
            <?php foreach ( $navigation as $nav_item ) : ?>
                <?php
                $nav_page = $nav_item['page'];
                $active   = $nav_page && (int) $nav_page->ID === (int) $post_id;
                ?>
                <?php if ( $nav_page ) : ?>
                    <a class="gep-page-tab <?php echo $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'post_id', $nav_page->ID, $plugin_page_url ) ); ?>">
                        <span class="dashicons <?php echo esc_attr( $nav_item['icon'] ); ?>"></span>
                        <span><?php echo esc_html( $nav_item['label'] ); ?></span>
                    </a>
                <?php else : ?>
                    <span class="gep-page-tab is-disabled" title="No se encontró esta página en WordPress">
                        <span class="dashicons <?php echo esc_attr( $nav_item['icon'] ); ?>"></span>
                        <span><?php echo esc_html( $nav_item['label'] ); ?></span>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ( $page_view_url ) : ?>
                <a class="gep-sidebar-public" href="<?php echo esc_url( $page_view_url ); ?>" target="_blank" rel="noopener">
                    <span class="dashicons dashicons-external"></span>
                    <span><strong>Sitio público</strong><small>Ver esta página publicada</small></span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="gep-page-heading">
            <div class="gep-breadcrumb"><span class="dashicons dashicons-admin-home"></span><span>Contenido GEP</span><i>›</i><strong><?php echo esc_html( $editor_title ); ?></strong></div>
            <div class="gep-title-row gep-title-row-compact">
                <div>
                    <h2>Editar contenido · <?php echo esc_html( $editor_title ); ?></h2>
                    <p><?php echo esc_html( $editor_description ); ?></p>
                </div>
            </div>
        </div>

        <?php if ( $saved ) : ?>
            <div class="gep-save-toast" id="gep-save-toast" role="status"><span class="dashicons dashicons-yes-alt"></span><div><strong>Cambios guardados</strong><small>La página pública ya usa la última versión.</small></div></div>
        <?php endif; ?>

        <?php if ( ! $is_home && ! $is_directory && ! $is_contact && ! $is_docencia && ! $is_projects && ! $is_publications && ! $is_communications && ! $is_news && ! $is_services ) : ?>
            <div class="gep-empty-state">
                <span class="gep-empty-icon dashicons dashicons-forms"></span>
                <h3>Cuestionario de esta página pendiente</h3>
                <p>La nueva interfaz ya reconoce esta página. En esta versión del plugin se ha implementado primero el cuestionario real del <strong>Directorio GEP</strong>, conservando los datos de la versión anterior.</p>
                <?php if ( ! empty( $navigation['quienes-somos']['page'] ) ) : ?>
                    <a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'post_id', $navigation['quienes-somos']['page']->ID, $plugin_page_url ) ); ?>">Abrir Quiénes somos</a>
                <?php endif; ?>
            </div>
            <?php return; ?>
        <?php endif; ?>

        <?php if ( $is_home ) : ?>
            <?php gep_demo_render_home_editor( $post_id, $home_values ); ?>
            <?php return; ?>
        <?php endif; ?>

        <?php if ( $is_services ) : ?>
            <?php gep_demo_render_services_editor( $post_id, $services_values ); ?>
            <?php return; ?>
        <?php endif; ?>

        <?php if ( $is_communications ) : ?>
            <?php gep_demo_render_hub_editor( $post_id, $hub_values, 'communications' ); ?>
            <?php return; ?>
        <?php endif; ?>

        <?php if ( $is_news ) : ?>
            <?php gep_demo_render_hub_editor( $post_id, $hub_values, 'news' ); ?>
            <?php return; ?>
        <?php endif; ?>

        <?php if ( $is_publications ) : ?>
            <?php gep_demo_render_publications_editor( $post_id, $publications_values ); ?>
            <?php return; ?>
        <?php endif; ?>

        <?php if ( $is_projects ) : ?>
            <?php gep_demo_render_projects_editor( $post_id, $projects_values ); ?>
            <?php return; ?>
        <?php endif; ?>

        <?php if ( $is_docencia ) : ?>
            <?php gep_demo_render_docencia_editor( $post_id, $docencia_values ); ?>
            <?php return; ?>
        <?php endif; ?>

        <?php if ( $is_contact ) : ?>
            <?php gep_demo_render_contact_editor( $post_id, $contact_values ); ?>
            <?php return; ?>
        <?php endif; ?>

        <?php
        $directory_asset_base = gep_demo_public_asset_base_url( $page_view_url );
        $directory_fallback_hero = '';
        $directory_preview_hero  = ! empty( $values['hero_image'] ) ? $values['hero_image'] : $directory_fallback_hero;
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gep-content-form" novalidate>
            <input type="hidden" name="action" value="gep_demo_save_content">
            <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
            <input type="hidden" name="gep_editor_context" value="directory">
            <?php wp_nonce_field( 'gep_demo_save_content_' . $post_id, 'gep_demo_admin_nonce' ); ?>

            <div class="gep-contact-commandbar gep-directory-commandbar">
                <div class="gep-contact-commandbar-copy">
                    <span class="gep-contact-command-icon"><span class="dashicons dashicons-groups"></span></span>
                    <div><strong>Editor de Quiénes somos</strong><small>Edita, revisa la preview y publica cuando esté listo.</small></div>
                    <span class="gep-inline-save-state is-clean" id="gep-directory-inline-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span>
                </div>
                <div class="gep-contact-commandbar-actions">
                    <button type="button" class="button gep-restore-directory"><span class="dashicons dashicons-undo"></span> Restaurar guardado</button>
                    <?php /* v9.25.0: "Ver página real" se quitó de esta barra; el acceso queda en "Sitio público" del menú lateral. */ ?>
                    <button type="submit" class="button button-primary gep-save-button" disabled><span class="dashicons dashicons-saved"></span> Guardar cambios</button>
                </div>
            </div>

            <div class="gep-editor-layout gep-editor-layout-directory">
                <div class="gep-questionnaire">
                <?php gep_demo_render_section_visibility_tools(); ?>
                    <section class="gep-step-card" data-preview="team">
                        <div class="gep-step-heading">
                            <span class="gep-step-number">1</span>
                            <div>
                                <h3>Equipo y disciplinas</h3>
                                <p>Edita el bloque visual de tarjetas que aparece antes del directorio.</p>
                            </div>
                        </div>

                        <label class="gep-switch-row gep-team-visibility-switch">
                            <span>
                                <strong>Mostrar esta sección en la página</strong>
                                <small>Si la desactivas, el bloque completo dejará de mostrarse sin borrar sus datos.</small>
                            </span>
                            <span class="gep-switch">
                                <input type="checkbox" name="gep_team_enabled" value="1" <?php checked( $values['team_enabled'], '1' ); ?>>
                                <span class="gep-switch-ui"></span>
                            </span>
                        </label>

                        <div class="gep-admin-field">
                            <label for="gep_team_eyebrow">Texto superior</label>
                            <input type="text" id="gep_team_eyebrow" name="gep_team_eyebrow" value="<?php echo esc_attr( $values['team_eyebrow'] ); ?>">
                        </div>

                        <div class="gep-admin-field">
                            <label for="gep_team_title">Título principal <span class="gep-required-dot">Obligatorio</span></label>
                            <textarea id="gep_team_title" name="gep_team_title" data-gep-required-directory="1"><?php echo esc_textarea( $values['team_title'] ); ?></textarea>
                            <p class="description">Puedes usar saltos de línea para controlar cómo se corta el título.</p>
                        </div>

                        <div class="gep-admin-field">
                            <label for="gep_team_description">Texto lateral</label>
                            <textarea id="gep_team_description" name="gep_team_description"><?php echo esc_textarea( $values['team_description'] ); ?></textarea>
                        </div>

                        <div class="gep-team-builder-head">
                            <div>
                                <strong>Tarjetas del bloque</strong>
                                <p class="gep-muted">Puedes añadir, eliminar, ordenar y cambiar las imágenes desde la biblioteca de medios.</p>
                            </div>
                            <button type="button" class="button gep-add-team-card"><span class="dashicons dashicons-plus-alt2"></span> Añadir tarjeta</button>
                        </div>

                        <div class="gep-team-cards" id="gep-team-cards">
                            <?php
                            foreach ( $values['team_cards'] as $card_key => $card ) {
                                gep_demo_render_team_card( $card_key, $card );
                            }
                            ?>
                        </div>

                        <div class="gep-empty-builder <?php echo $values['team_cards'] ? 'is-hidden' : ''; ?>" id="gep-team-empty">
                            <span class="dashicons dashicons-format-gallery"></span>
                            <strong>Aún no hay tarjetas.</strong>
                            <span>Pulsa “Añadir tarjeta” para crear la primera.</span>
                        </div>
                    </section>

                    <section class="gep-step-card" data-preview="header">
                        <div class="gep-step-heading">
                            <span class="gep-step-number">2</span>
                            <div>
                                <h3>Encabezado del directorio</h3>
                                <p>Define los textos que aparecen en la parte superior de esta sección.</p>
                            </div>
                        </div>

                        <div class="gep-admin-field gep-media-field">
                            <span class="gep-field-label">Imagen hero / cabecera</span>
                            <?php gep_demo_render_image_control( 'gep_about_hero_image', $values['hero_image'], 'Seleccionar / cambiar imagen hero' ); ?>
                        </div>
                        <div class="gep-admin-field gep-media-field gep-hero-video-field">
                            <span class="gep-field-label">Video hero / cabecera <span class="gep-optional">(opcional)</span></span>
                            <?php gep_demo_render_video_control( 'gep_about_hero_video', isset($values['hero_video'])?$values['hero_video']:'', 'Seleccionar / cambiar video hero' ); ?>
                        </div>

                        <div class="gep-admin-field">
                            <label for="gep_directory_eyebrow">Texto superior</label>
                            <input type="text" id="gep_directory_eyebrow" name="gep_directory_eyebrow" value="<?php echo esc_attr( $values['eyebrow'] ); ?>">
                            <p class="description">Se muestra como una pequeña etiqueta encima del título.</p>
                        </div>

                        <div class="gep-admin-field">
                            <label for="gep_directory_title">Título de la sección <span class="gep-required-dot">Obligatorio</span></label>
                            <input type="text" id="gep_directory_title" name="gep_directory_title" data-gep-required-directory="1" value="<?php echo esc_attr( $values['title'] ); ?>">
                        </div>

                        <div class="gep-admin-field">
                            <label for="gep_directory_description">Texto introductorio</label>
                            <textarea id="gep_directory_description" name="gep_directory_description"><?php echo esc_textarea( $values['description'] ); ?></textarea>
                        </div>

                        <input type="hidden" id="gep_directory_legend" name="gep_directory_legend" value="<?php echo esc_attr( $values['legend'] ); ?>">
                        <div class="gep-info-note">
                            <span class="dashicons dashicons-info-outline"></span>
                            <p><strong>Nota:</strong> <?php echo esc_html( $values['legend'] ); ?> Esta leyenda se mantiene automáticamente para que nadie tenga que editar una regla del sitio por accidente.</p>
                        </div>

                        <details class="gep-optional-panel">
                            <summary><span class="dashicons dashicons-format-image"></span> Imagen destacada opcional</summary>
                            <div class="gep-optional-panel-body">
                                <p class="gep-muted">Solo úsala si este bloque necesita una imagen destacada. Si está vacía, el diseño seguirá funcionando normalmente.</p>
                                <?php gep_demo_render_image_control( 'gep_directory_image_url', $values['image_url'], 'Seleccionar / cambiar imagen' ); ?>
                            </div>
                        </details>
                    </section>

                    <section class="gep-step-card" data-preview="directory">
                        <div class="gep-step-heading gep-step-heading-with-actions">
                            <span class="gep-step-number">3</span>
                            <div>
                                <h3>Personas y grupos del directorio</h3>
                                <p>Añade, edita o elimina los apartados que aparecen debajo del encabezado.</p>
                            </div>
                        </div>


                        <div class="gep-sections" id="gep-sections">
                            <?php
                            foreach ( $values['sections'] as $section_key => $section ) {
                                gep_demo_render_section( $section_key, $section );
                            }
                            ?>
                        </div>

                        <div class="gep-builder-toolbar gep-add-section-bar is-collapsed">
                            <button type="button" class="button gep-add-section-toggle" aria-expanded="false"><span class="dashicons dashicons-plus-alt2"></span> Añadir un apartado</button>
                            <span class="gep-toolbar-label">¿Qué tipo de apartado?</span>
                            <button type="button" class="button gep-add-section" data-type="people"><span class="dashicons dashicons-groups"></span><span class="gep-add-section-text"><strong>Personas</strong><small>Nombre, cargo y foto opcional</small></span></button>
                            <button type="button" class="button gep-add-section" data-type="status"><span class="dashicons dashicons-editor-ul"></span><span class="gep-add-section-text"><strong>Estados / tesis</strong><small>Lista con estado D / T</small></span></button>
                            <button type="button" class="button gep-add-section" data-type="photos"><span class="dashicons dashicons-format-gallery"></span><span class="gep-add-section-text"><strong>Tarjetas con foto</strong><small>Foto grande y texto</small></span></button>
                        </div>

                        <div class="gep-empty-builder <?php echo $values['sections'] ? 'is-hidden' : ''; ?>" id="gep-empty-builder">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <strong>Aún no hay apartados creados.</strong>
                            <span>Usa el botón "Añadir un apartado" para empezar.</span>
                        </div>
                    </section>

                    <section class="gep-step-card gep-advanced-card" data-preview="publish">
                        <div class="gep-step-heading">
                            <span class="gep-step-number">4</span>
                            <div>
                                <h3>Publicación del directorio</h3>
                                <p>Controla cuándo WordPress debe reemplazar el contenido fijo del PHP.</p>
                            </div>
                        </div>
                        <label class="gep-switch-row">
                            <span>
                                <strong>Usar el contenido creado en este cuestionario</strong>
                                <small>Déjalo desactivado mientras estés probando. Actívalo cuando quieras que WordPress controle el directorio.</small>
                            </span>
                            <span class="gep-switch">
                                <input type="checkbox" name="gep_directory_replace_static" value="1" <?php checked( $values['replace'], '1' ); ?>>
                                <span class="gep-switch-ui"></span>
                            </span>
                        </label>
                    </section>

                    <?php /* v9.26.0: se quitó el botón inferior "Guardar cambios"; se guarda desde la barra superior. */ ?>
                </div>

                <aside class="gep-preview-column">
                    <div class="gep-preview-card gep-directory-preview-wrap gep-parent-preview-card" id="gep-directory-preview-shell" data-public-url="<?php echo esc_url( $page_view_url ); ?>" data-asset-base="<?php echo esc_url( $directory_asset_base ); ?>" data-hero-fallback="<?php echo esc_url( $directory_fallback_hero ); ?>">
                        <div class="gep-preview-heading gep-directory-preview-heading">
                            <span class="gep-preview-icon"><span class="dashicons dashicons-visibility"></span></span>
                            <div class="gep-preview-heading-copy">
                                <div class="gep-preview-heading-title-row">
                                    <span class="gep-preview-zone-badge" id="gep-preview-zone-number">Zona 1</span>
                                    <h3 id="gep-preview-heading-title">Equipo y disciplinas</h3>
                                </div>
                            </div>
                            <div class="gep-preview-device-switcher" aria-label="Tamaño de la preview">
                                <button type="button" class="gep-directory-device-button is-active" data-device="desktop" title="Escritorio"><span class="dashicons dashicons-desktop"></span><span>PC</span></button>
                                <button type="button" class="gep-directory-device-button" data-device="tablet" title="Tablet"><span class="dashicons dashicons-tablet"></span><span>Tablet</span></button>
                                <button type="button" class="gep-directory-device-button" data-device="mobile" title="Móvil"><span class="dashicons dashicons-smartphone"></span><span>Móvil</span></button>
                            </div>
                        </div>

                        <div class="gep-directory-device-stage">
                            <div class="gep-directory-browser is-device-desktop" id="gep-directory-device-frame">
                                <div class="gep-directory-live-toolbar"><span></span><span></span><span></span><strong>quienes-somos.php</strong><em id="gep-directory-device-label">Vista escritorio</em></div>
                                <div class="gep-preview-stage" id="gep-preview-stage">
                            <div class="gep-preview-pane is-active" data-pane="team">
                                <div class="gep-team-preview-demo">
                                    <span class="gep-demo-kicker" id="gep-preview-team-eyebrow"><?php echo esc_html( $values['team_eyebrow'] ); ?></span>
                                    <div class="gep-team-preview-head">
                                        <h4 id="gep-preview-team-title"><?php echo nl2br( esc_html( $values['team_title'] ) ); ?></h4>
                                        <p id="gep-preview-team-description"><?php echo esc_html( $values['team_description'] ); ?></p>
                                    </div>
                                    <div class="gep-team-preview-grid" id="gep-team-preview-grid"></div>
                                </div>
                            </div>

                            <div class="gep-preview-pane" data-pane="header">
                                <div class="gep-live-preview has-preview-cover" style="background-image:linear-gradient(90deg,rgba(5,24,14,.84),rgba(5,24,14,.48) 58%,rgba(5,24,14,.16)),url(&quot;<?php echo esc_url( $directory_preview_hero ); ?>&quot;);background-size:cover;background-position:center 55%;">
                                    <span class="gep-preview-eyebrow" id="gep-preview-eyebrow"><?php echo esc_html( $values['eyebrow'] ); ?></span>
                                    <h4 id="gep-preview-title"><?php echo esc_html( $values['title'] ); ?></h4>
                                    <p id="gep-preview-description"><?php echo esc_html( $values['description'] ); ?></p>
                                    <figure class="gep-preview-directory-feature" id="gep-preview-directory-feature" style="<?php echo $values['image_url'] ? '' : 'display:none'; ?>"><img src="<?php echo esc_url( $values['image_url'] ); ?>" alt=""></figure>
                                    <span class="gep-preview-legend" id="gep-preview-legend"><?php echo esc_html( $values['legend'] ); ?></span>
                                </div>
                            </div>

                            <div class="gep-preview-pane" data-pane="directory">
                                <div class="gep-section-demo">
                                    <span class="gep-demo-kicker">Vista del bloque</span>
                                    <h4>Grupos del directorio</h4>
                                    <div class="gep-directory-demo-list">
                                        <div><strong>Investigadores</strong><span class="dashicons dashicons-arrow-down-alt2"></span></div>
                                        <div><strong>Estudiantes de postgrado</strong><span class="dashicons dashicons-arrow-down-alt2"></span></div>
                                        <div><strong>Equipo / colaboradores</strong><span class="dashicons dashicons-arrow-down-alt2"></span></div>
                                    </div>
                                    <p class="gep-demo-explain">Cada apartado se muestra como un grupo organizado. Al seleccionar uno aquí en el editor, la referencia cambia al tipo de contenido correspondiente.</p>
                                </div>
                            </div>

                            <div class="gep-preview-pane" data-pane="people">
                                <div class="gep-section-demo">
                                    <span class="gep-demo-kicker">Ejemplo visual</span>
                                    <h4 class="gep-demo-section-title">Investigadores</h4>
                                    <div class="gep-person-demo-card">
                                        <div class="gep-person-demo-photo"><span class="dashicons dashicons-admin-users"></span><img alt="" src=""></div>
                                        <div class="gep-person-demo-copy">
                                            <span class="gep-demo-person-specialization">Especialización</span>
                                            <strong class="gep-demo-person-name">Nombre de la persona</strong>
                                            <span class="gep-demo-person-meta">Formación o cargo</span>
                                            <p class="gep-demo-person-text">Una breve descripción aparecerá junto a sus datos.</p>
                                            <span class="gep-demo-person-email">correo@ejemplo.cl</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="gep-preview-pane" data-pane="status">
                                <div class="gep-section-demo">
                                    <span class="gep-demo-kicker">Ejemplo visual</span>
                                    <h4 class="gep-demo-section-title">Estudiantes de postgrado</h4>
                                    <h5 class="gep-demo-section-group">Doctorado Ciencias Silvoagropecuarias y Veterinarias (CSAV)</h5>
                                    <div class="gep-status-demo-row">
                                        <span class="gep-status-demo-pill">D</span>
                                        <div><strong class="gep-demo-person-name">Nombre del estudiante</strong><p class="gep-demo-person-text">Descripción, programa o tema de tesis.</p></div>
                                    </div>
                                    <div class="gep-status-demo-row is-muted"><span class="gep-status-demo-pill is-done">T</span><div><strong>Ejemplo de segundo registro</strong><p>El diseño mantiene los estados fáciles de reconocer.</p></div></div>
                                </div>
                            </div>

                            <div class="gep-preview-pane" data-pane="photos">
                                <div class="gep-section-demo">
                                    <span class="gep-demo-kicker">Ejemplo visual</span>
                                    <h4 class="gep-demo-section-title">Equipo actual</h4>
                                    <div class="gep-photo-demo-grid">
                                        <div class="gep-photo-demo-card"><div class="gep-photo-demo-image"><span class="dashicons dashicons-format-image"></span><img alt="" src=""></div><strong class="gep-demo-person-name">Nombre</strong><span class="gep-demo-person-meta">Investigador/a</span></div>
                                        <div class="gep-photo-demo-card ghost"><div class="gep-photo-demo-image"><span class="dashicons dashicons-format-image"></span></div><strong>Otra persona</strong><span>Rol</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="gep-preview-pane" data-pane="publish">
                                <div class="gep-publish-demo">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <strong>Contenido preparado para publicar</strong>
                                    <p>Al activar esta opción, el sitio utilizará la información guardada en este cuestionario.</p>
                                </div>
                            </div>
                                </div>
                            </div>
                        </div>

                        <div class="gep-preview-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <div><strong>La preview cambia mientras editas.</strong><p>Usa PC, Tablet o Móvil para revisar el bloque seleccionado antes de guardar. «Sitio público», en el menú lateral, abre la página publicada en otra pestaña.</p></div>
                        </div>
                    </div>
                </aside>
            </div>
            <div id="gep-directory-submit-fields"></div>
            <script type="application/json" id="gep-directory-sections-json"><?php echo wp_json_encode( $values['sections'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
        </form>
    </div>

    <?php gep_demo_render_templates(); ?>
    <?php
}

function gep_demo_render_templates() {
    ?>
    <script type="text/template" id="gep-team-card-template">
        <details class="gep-team-card-row" data-card-key="__C__" open>
            <summary>
                <span class="gep-grip dashicons dashicons-menu-alt2"></span>
                <span class="gep-item-summary-main">
                    <strong class="gep-team-card-summary-title">Nueva tarjeta</strong>
                    <span class="gep-item-summary-badge">IMAGEN REFERENCIAL</span>
                </span>
                <span class="gep-summary-edit"><span class="dashicons dashicons-edit"></span> Editar</span>
            </summary>
            <div class="gep-item-content">
                <div class="gep-item-topline">
                    <div class="gep-card-order-actions" aria-label="Cambiar orden">
                        <button type="button" class="button button-small gep-team-move-up"><span class="dashicons dashicons-arrow-up-alt2"></span> Subir</button>
                        <button type="button" class="button button-small gep-team-move-down"><span class="dashicons dashicons-arrow-down-alt2"></span> Bajar</button>
                        <button type="button" class="button button-small gep-duplicate-team-card"><span class="dashicons dashicons-admin-page"></span> Duplicar</button>
                    </div>
                    <button type="button" class="button-link-delete gep-remove-team-card"><span class="dashicons dashicons-trash"></span> Eliminar tarjeta</button>
                </div>
                <div class="gep-admin-field compact">
                    <span class="gep-field-label">Imagen de la tarjeta <span class="gep-optional">(opcional)</span></span>
                    <p class="gep-muted">Selecciona una fotografía desde la biblioteca de medios. Si está vacía, la página mostrará el marcador referencial.</p>
                    <div class="gep-image-control">
                        <input type="hidden" class="gep-image-url" name="gep_team_cards[__C__][image]" value="">
                        <div class="gep-image-actions">
                            <button type="button" class="button gep-select-image"><span class="dashicons dashicons-format-image"></span>Seleccionar / cambiar imagen</button>
                            <button type="button" class="button-link-delete gep-remove-image" style="display:none">Quitar imagen</button>
                        </div>
                        <div class="gep-image-preview" style="display:none"><img src="" alt="Vista previa"></div>
                    </div>
                </div>
                <div class="gep-grid-2">
                    <label>Etiqueta superior<input type="text" name="gep_team_cards[__C__][kicker]" value="GEP · EQUIPO" placeholder="GEP · EQUIPO"></label>
                    <label>Etiqueta sobre la imagen<input type="text" name="gep_team_cards[__C__][badge]" value="IMAGEN REFERENCIAL" placeholder="IMAGEN REFERENCIAL"></label>
                </div>
                <label>Título<input class="gep-team-card-title-input" type="text" name="gep_team_cards[__C__][title]" placeholder="Ej: Geomática y paisaje"></label>
                <label>Descripción<textarea name="gep_team_cards[__C__][text]" placeholder="Ej: Análisis espacial · Ecología del paisaje"></textarea></label>
            </div>
        </details>
    </script>

    <script type="text/template" id="gep-section-template-people">
        <div class="gep-section-builder" data-section-key="__S__" data-type="people">
            <div class="gep-section-head"><div class="gep-section-head-main"><span class="gep-section-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><span class="gep-type-chip">Personas</span><strong class="gep-section-name">Nuevo apartado</strong><span class="gep-section-count">0 elementos</span></div><div class="gep-section-actions"><button type="button" class="button button-small gep-duplicate-section"><span class="dashicons dashicons-admin-page"></span> Duplicar</button><button type="button" class="button-link-delete gep-remove-section"><span class="dashicons dashicons-trash"></span> Eliminar apartado</button></div></div>
            <input type="hidden" name="gep_sections[__S__][type]" value="people">
            <div class="gep-grid-2 gep-section-settings"><label>Título del apartado<input type="text" class="gep-section-title-input" name="gep_sections[__S__][title]" placeholder="Ej: Investigadores"></label><label class="gep-toggle-line"><input type="checkbox" name="gep_sections[__S__][open]" value="1"><span>Mostrar abierto por defecto</span></label></div>
            <div class="gep-items" data-items-for="__S__"></div>
            <button type="button" class="button gep-add-item"><span class="dashicons dashicons-plus-alt2"></span> Añadir elemento</button>
        </div>
    </script>

    <script type="text/template" id="gep-section-template-status">
        <div class="gep-section-builder" data-section-key="__S__" data-type="status">
            <div class="gep-section-head"><div class="gep-section-head-main"><span class="gep-section-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><span class="gep-type-chip">Estados / tesis</span><strong class="gep-section-name">Nuevo apartado</strong><span class="gep-section-count">0 elementos</span></div><div class="gep-section-actions"><button type="button" class="button button-small gep-duplicate-section"><span class="dashicons dashicons-admin-page"></span> Duplicar</button><button type="button" class="button-link-delete gep-remove-section"><span class="dashicons dashicons-trash"></span> Eliminar apartado</button></div></div>
            <input type="hidden" name="gep_sections[__S__][type]" value="status">
            <div class="gep-grid-2 gep-section-settings"><label>Título del apartado<input type="text" class="gep-section-title-input" name="gep_sections[__S__][title]" placeholder="Ej: Estudiantes de postgrado"></label><label class="gep-toggle-line"><input type="checkbox" name="gep_sections[__S__][open]" value="1"><span>Mostrar abierto por defecto</span></label></div>
            <div class="gep-items" data-items-for="__S__"></div>
            <button type="button" class="button gep-add-item"><span class="dashicons dashicons-plus-alt2"></span> Añadir elemento</button>
        </div>
    </script>

    <script type="text/template" id="gep-section-template-photos">
        <div class="gep-section-builder" data-section-key="__S__" data-type="photos">
            <div class="gep-section-head"><div class="gep-section-head-main"><span class="gep-section-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span><span class="gep-type-chip">Tarjetas con foto</span><strong class="gep-section-name">Nuevo apartado</strong><span class="gep-section-count">0 elementos</span></div><div class="gep-section-actions"><button type="button" class="button button-small gep-duplicate-section"><span class="dashicons dashicons-admin-page"></span> Duplicar</button><button type="button" class="button-link-delete gep-remove-section"><span class="dashicons dashicons-trash"></span> Eliminar apartado</button></div></div>
            <input type="hidden" name="gep_sections[__S__][type]" value="photos">
            <div class="gep-grid-2 gep-section-settings"><label>Título del apartado<input type="text" class="gep-section-title-input" name="gep_sections[__S__][title]" placeholder="Ej: Equipo actual"></label><label class="gep-toggle-line"><input type="checkbox" name="gep_sections[__S__][open]" value="1"><span>Mostrar abierto por defecto</span></label></div>
            <div class="gep-items" data-items-for="__S__"></div>
            <button type="button" class="button gep-add-item"><span class="dashicons dashicons-plus-alt2"></span> Añadir elemento</button>
        </div>
    </script>

    <?php
}

/**
 * --------------------------------------------------------------------------
 * Estilos y comportamiento del administrador
 * --------------------------------------------------------------------------
 */


function gep_demo_help_css() {
    return <<<'CSS'
.gep-help-wrap{--gh-green:#1f5a3b;--gh-green2:#286f4a;--gh-deep:#173f2a;--gh-text:#17251c;--gh-muted:#68766d;--gh-border:#dfe6e1;--gh-soft:#f6f8f6;max-width:1220px;margin:26px 28px 56px 0;color:var(--gh-text)}
.gep-help-wrap *{box-sizing:border-box}.gep-help-wrap .dashicons{width:20px;height:20px;font-size:20px;line-height:20px}.gep-help-hero{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(340px,.7fr);gap:28px;align-items:center;padding:30px 32px;border-radius:18px;background:linear-gradient(135deg,#173f2a 0%,#1f5a3b 65%,#2e704b 100%);color:#fff;box-shadow:0 16px 42px rgba(22,63,42,.15);overflow:hidden;position:relative}.gep-help-hero:after{content:"";position:absolute;width:310px;height:310px;border:1px solid rgba(255,255,255,.08);border-radius:50%;right:-165px;top:-170px}.gep-help-hero-copy,.gep-help-daily-flow{position:relative;z-index:1}.gep-help-kicker,.gep-help-eyebrow{display:block;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.gep-help-kicker{color:#c4e4ce;margin-bottom:8px}.gep-help-hero h1{margin:0 0 9px;color:#fff;font-size:34px;line-height:1.05;letter-spacing:-.025em}.gep-help-hero-copy>p{margin:0;color:#e1ece5;font-size:14px;line-height:1.55}.gep-help-hero-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}.gep-help-hero .button{display:inline-flex;align-items:center;gap:7px;justify-content:center;border-radius:8px;min-height:38px;padding:0 14px}.gep-help-hero .button-primary{background:#fff!important;border-color:#fff!important;color:#17462e!important}.gep-help-hero .button:not(.button-primary){background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.45);color:#fff}.gep-help-daily-flow{padding:17px 18px;border:1px solid rgba(255,255,255,.14);border-radius:13px;background:rgba(255,255,255,.09);backdrop-filter:blur(4px)}.gep-help-flow-label{display:block;color:#c4e4ce;font-size:9px;font-weight:800;letter-spacing:.13em;margin-bottom:11px}.gep-help-flow-row{display:grid;grid-template-columns:24px auto 1fr 24px auto 1fr 24px auto;gap:7px;align-items:center}.gep-help-flow-row b{width:24px;height:24px;border-radius:50%;background:#fff;color:#1f5a3b;display:grid;place-items:center;font-size:10px}.gep-help-flow-row span{font-size:12px;font-weight:700}.gep-help-flow-row i{height:1px;background:rgba(255,255,255,.32)}.gep-help-daily-flow small{display:block;margin-top:10px;color:#d9e9de;font-size:10px}.gep-help-jump{display:flex;gap:6px;flex-wrap:wrap;margin:12px 0 18px;padding:7px;background:#fff;border:1px solid var(--gh-border);border-radius:11px;box-shadow:0 3px 12px rgba(21,49,32,.035)}.gep-help-jump a{padding:7px 10px;border-radius:7px;text-decoration:none;color:#4b5f53;font-size:11px;font-weight:700}.gep-help-jump a:hover{background:#edf5ef;color:var(--gh-green)}
.gep-help-section{background:#fff;border:1px solid var(--gh-border);border-radius:15px;padding:23px;margin:0 0 14px;box-shadow:0 6px 22px rgba(20,48,31,.035);scroll-margin-top:40px}.gep-help-section-head{display:flex;gap:12px;align-items:flex-start;margin-bottom:17px}.gep-help-section-icon{width:38px;height:38px;border-radius:11px;background:#e9f4ec;color:var(--gh-green);display:grid;place-items:center;flex:0 0 auto}.gep-help-section-icon .dashicons{font-size:21px;width:21px;height:21px;line-height:21px}.gep-help-eyebrow{color:#3b7a55;margin:1px 0 4px}.gep-help-section h2{font-size:20px;line-height:1.18;margin:0;color:#183323}.gep-help-section-head p{font-size:12px;color:var(--gh-muted);line-height:1.5;margin:5px 0 0;max-width:760px}
.gep-help-steps.is-horizontal{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}.gep-help-steps.is-horizontal article{position:relative;padding:13px 13px 13px 48px;border:1px solid #e3e9e5;border-radius:10px;background:#fbfcfb;min-height:86px}.gep-help-step-number{position:absolute;left:12px;top:13px;width:27px;height:27px;border-radius:999px;background:var(--gh-green);color:#fff;display:grid;place-items:center;font-size:11px;font-weight:800}.gep-help-steps h3{margin:0 0 4px;color:#203b2b;font-size:13px}.gep-help-steps p{margin:0;color:#67766d;font-size:10.5px;line-height:1.45}.gep-help-page-chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:11px}.gep-help-page-chips span{display:inline-flex;align-items:center;gap:5px;padding:6px 8px;border-radius:999px;background:#f1f5f2;color:#4c6255;font-size:10px;font-weight:700}.gep-help-page-chips .dashicons{font-size:14px;width:14px;height:14px;line-height:14px;color:#2f7049}
.gep-help-control-grid.is-compact{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.gep-help-control-grid article{display:flex;flex-direction:column;gap:8px;align-items:flex-start;border:1px solid #e3e8e5;border-radius:10px;padding:12px;background:#fcfdfc}.gep-help-control-grid p{margin:0;color:#66756c;font-size:10.5px;line-height:1.45}.gep-help-control-sample{display:inline-flex;align-items:center;justify-content:center;gap:5px;border:1px solid #ccd6d0;background:#fff;border-radius:6px;padding:6px 8px;font-size:10px;font-weight:700;color:#43564a;white-space:nowrap}.gep-help-control-sample.is-green{background:var(--gh-green);border-color:var(--gh-green);color:#fff}.gep-help-control-sample.is-status{background:#eaf5ed;border-color:#d4e9da;color:#24633e}.gep-help-control-sample .dashicons{font-size:14px;width:14px;height:14px;line-height:14px}.gep-help-devices{display:flex;gap:4px}.gep-help-devices span{padding:5px 7px;border:1px solid #d5ddd8;border-radius:5px;font-size:9px;color:#59685f}.gep-help-devices span:first-child{border-color:#89aa94;color:#1f5a3b;background:#f5faf6;font-weight:700}
.gep-help-history-section{padding:0;overflow:hidden}.gep-help-history-head{padding:22px 23px 17px;margin:0;background:linear-gradient(180deg,#fbfdfb 0%,#fff 100%);align-items:center}.gep-help-history-head>div{flex:1;min-width:0}.gep-help-history-head>.button{display:inline-flex;align-items:center;gap:5px;white-space:nowrap}.gep-help-history-head>.button .dashicons{font-size:15px;width:15px;height:15px;line-height:15px}.gep-help-history-flow{display:grid;grid-template-columns:1fr 28px 1fr 28px 1fr 28px 1fr;align-items:center;padding:13px 23px;background:#183f2a;color:#fff}.gep-help-history-flow>div{display:grid;grid-template-columns:26px 1fr;column-gap:8px;align-items:center}.gep-help-history-flow>div>.dashicons{grid-row:1/3;width:26px;height:26px;font-size:19px;line-height:26px;color:#bee0c9}.gep-help-history-flow strong{font-size:11px}.gep-help-history-flow small{font-size:9px;color:#cfe2d5}.gep-help-history-flow>i{text-align:center;color:#8db69b;font-size:17px}.gep-help-history-main-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1px;background:#e4e9e5;border-top:1px solid #e4e9e5;border-bottom:1px solid #e4e9e5}.gep-help-history-main-grid article{display:flex;gap:9px;padding:14px;background:#fff;min-height:90px}.gep-help-history-main-grid article.is-featured{background:#f4f9f5}.gep-help-history-main-grid article>.dashicons{color:#2f7750;margin-top:1px;flex:0 0 auto}.gep-help-history-main-grid strong{display:block;font-size:11px;color:#23432f;margin-bottom:4px}.gep-help-history-main-grid p{margin:0;color:#69776e;font-size:9.5px;line-height:1.45}.gep-help-history-strip{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;padding:13px 23px;background:#fafcfb}.gep-help-history-strip span{display:flex;align-items:flex-start;gap:6px;color:#627168;font-size:9.5px;line-height:1.4}.gep-help-history-strip i{color:#2f7750;flex:0 0 auto;font-size:16px;width:16px;height:16px;line-height:16px}.gep-help-history-strip b{color:#314b3c}.gep-help-history-safe{display:flex;gap:10px;align-items:flex-start;padding:13px 23px;border-top:1px solid #d9e6dc;background:#edf6ef;color:#2c6040}.gep-help-history-safe>.dashicons{margin-top:1px}.gep-help-history-safe strong{font-size:11px}.gep-help-history-safe p{margin:2px 0 0;color:#5d7465;font-size:10px;line-height:1.45}
.gep-help-list-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.gep-help-list-actions>div{padding:12px;border:1px solid #e4e9e6;border-radius:9px;background:#fbfcfb}.gep-help-list-actions .dashicons{color:#2f7750;margin-bottom:8px}.gep-help-list-actions strong,.gep-help-list-actions small{display:block}.gep-help-list-actions strong{font-size:11px;color:#294431;margin-bottom:3px}.gep-help-list-actions small{font-size:9.5px;line-height:1.35;color:#718078}.gep-help-faq{display:grid;grid-template-columns:1fr 1fr;gap:7px}.gep-help-faq details{border:1px solid #e0e7e2;border-radius:9px;background:#fbfcfb;padding:0 12px}.gep-help-faq summary{cursor:pointer;padding:11px 1px;font-size:11px;font-weight:700;color:#294331}.gep-help-faq details[open] summary{border-bottom:1px solid #e9edea}.gep-help-faq p{margin:9px 1px 11px;color:#64736a;font-size:10px;line-height:1.5}.gep-help-footer{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:14px 16px;background:#eef4f0;border:1px solid #dbe7de;border-radius:11px;color:#385544}.gep-help-footer>div{display:flex;gap:9px;align-items:flex-start}.gep-help-footer .dashicons{color:#2d7048}.gep-help-footer p{margin:0;font-size:10px;line-height:1.45}.gep-help-version{font-size:10px;font-weight:800;color:#557064;white-space:nowrap}
@media(max-width:1020px){.gep-help-steps.is-horizontal{grid-template-columns:repeat(2,minmax(0,1fr))}.gep-help-control-grid.is-compact{grid-template-columns:repeat(2,minmax(0,1fr))}.gep-help-history-main-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.gep-help-list-actions{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:900px){.gep-help-hero{grid-template-columns:1fr}.gep-help-history-flow{grid-template-columns:1fr 22px 1fr 22px 1fr 22px 1fr}.gep-help-history-strip{grid-template-columns:1fr}.gep-help-history-head{align-items:flex-start;flex-wrap:wrap}.gep-help-history-head>.button{margin-left:50px}}
@media(max-width:782px){.gep-help-wrap{margin-right:10px}.gep-help-hero{padding:23px}.gep-help-hero h1{font-size:28px}.gep-help-jump{display:none}.gep-help-section{padding:18px}.gep-help-history-section{padding:0}.gep-help-history-head{padding:18px}.gep-help-history-head>.button{margin-left:0;width:100%;justify-content:center}.gep-help-history-flow{grid-template-columns:1fr 1fr;gap:8px;padding:13px 18px}.gep-help-history-flow>i{display:none}.gep-help-history-main-grid,.gep-help-control-grid.is-compact,.gep-help-steps.is-horizontal,.gep-help-faq{grid-template-columns:1fr}.gep-help-history-strip,.gep-help-history-safe{padding-left:18px;padding-right:18px}.gep-help-list-actions{grid-template-columns:repeat(2,minmax(0,1fr))}.gep-help-footer{align-items:flex-start;flex-direction:column}}
@media(max-width:480px){.gep-help-flow-row{grid-template-columns:24px auto 24px auto 24px auto}.gep-help-flow-row i{display:none}.gep-help-list-actions{grid-template-columns:1fr}}
CSS;
}

/**
 * Versión de un archivo CSS/JS del plugin: versión del plugin + fecha del archivo,
 * para que el navegador descargue siempre la copia nueva tras actualizar.
 */
function gep_demo_asset_version( $relative_path ) {
    $file = plugin_dir_path( __FILE__ ) . ltrim( $relative_path, '/' );
    return GEP_EDITOR_VERSION . '.' . ( file_exists( $file ) ? (int) filemtime( $file ) : 0 );
}

function gep_demo_admin_assets( $hook ) {
    $screen = get_current_screen();
    $is_help_page = $screen && 'contenido-gep_page_gep-content-help' === $screen->id;

    if ( $is_help_page ) {
        wp_register_style( 'gep-demo-help', false, array(), GEP_EDITOR_VERSION );
        wp_enqueue_style( 'gep-demo-help' );
        wp_add_inline_style( 'gep-demo-help', gep_demo_help_css() );
        return;
    }

    $is_plugin_page = $screen && 'toplevel_page_gep-content-editor' === $screen->id;

    if ( $is_plugin_page ) {
        wp_enqueue_media();
        wp_enqueue_style( 'gep-demo-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.css', array(), gep_demo_asset_version( 'assets/admin.css' ) );
        wp_enqueue_style( 'gep-demo-sections', plugin_dir_url( __FILE__ ) . 'assets/sections.css', array( 'gep-demo-admin' ), gep_demo_asset_version( 'assets/sections.css' ) );
        wp_enqueue_style( 'gep-editor-theme', plugin_dir_url( __FILE__ ) . 'assets/editor.css', array( 'gep-demo-admin', 'gep-demo-sections' ), gep_demo_asset_version( 'assets/editor.css' ) );

        wp_enqueue_script( 'gep-demo-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), gep_demo_asset_version( 'assets/admin.js' ), true );
        // v9.18.0: Imagen o Video en un solo control en los encabezados.
        wp_enqueue_script( 'gep-hero-media', plugin_dir_url( __FILE__ ) . 'assets/hero-media.js', array( 'jquery', 'gep-demo-admin' ), gep_demo_asset_version( 'assets/hero-media.js' ), true );

        $navigation = gep_demo_get_page_navigation();
        $asset_post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
        if ( ! $asset_post_id || 'page' !== get_post_type( $asset_post_id ) ) {
            $asset_post_id = gep_demo_get_default_editor_page_id( $navigation );
        }
        $section_payload = $asset_post_id ? gep_demo_section_admin_payload( $asset_post_id ) : array();
        wp_enqueue_script( 'gep-demo-sections', plugin_dir_url( __FILE__ ) . 'assets/sections.js', array( 'jquery', 'jquery-ui-sortable', 'gep-demo-admin' ), GEP_EDITOR_VERSION . '.' . (int) @filemtime( plugin_dir_path( __FILE__ ) . 'assets/sections.js' ), true );
        wp_add_inline_script( 'gep-demo-sections', 'window.gepSectionManagerData = ' . wp_json_encode( $section_payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';', 'before' );
    }
}
add_action( 'admin_enqueue_scripts', 'gep_demo_admin_assets' );

/**
 * --------------------------------------------------------------------------
 * Guardado desde Contenido GEP
 * --------------------------------------------------------------------------
 */

function gep_demo_clean_team_cards( $raw_cards ) {
    $raw_cards   = is_array( $raw_cards ) ? $raw_cards : array();
    $clean_cards = array();

    foreach ( $raw_cards as $card ) {
        if ( ! is_array( $card ) ) {
            continue;
        }

        $clean = array(
            'image'  => isset( $card['image'] ) ? esc_url_raw( $card['image'] ) : '',
            'kicker' => isset( $card['kicker'] ) ? sanitize_text_field( $card['kicker'] ) : '',
            'badge'  => isset( $card['badge'] ) ? sanitize_text_field( $card['badge'] ) : '',
            'title'  => isset( $card['title'] ) ? sanitize_text_field( $card['title'] ) : '',
            'text'   => isset( $card['text'] ) ? sanitize_textarea_field( $card['text'] ) : '',
        );

        $has_content = false;
        foreach ( $clean as $value ) {
            if ( '' !== $value ) {
                $has_content = true;
                break;
            }
        }

        if ( $has_content ) {
            $clean_cards[] = $clean;
        }
    }

    return $clean_cards;
}

function gep_demo_clean_sections( $raw_sections ) {
    $raw_sections   = is_array( $raw_sections ) ? $raw_sections : array();
    $clean_sections = array();

    foreach ( $raw_sections as $section ) {
        if ( ! is_array( $section ) ) {
            continue;
        }

        $type = isset( $section['type'] ) ? sanitize_key( $section['type'] ) : 'people';
        if ( ! in_array( $type, array( 'people', 'status', 'photos' ), true ) ) {
            continue;
        }

        $clean_section = array(
            'type'  => $type,
            'title' => isset( $section['title'] ) ? sanitize_text_field( $section['title'] ) : '',
            'open'  => ! empty( $section['open'] ) ? 1 : 0,
            'items' => array(),
        );

        if ( in_array( $type, array( 'people', 'status' ), true ) ) {
            $clean_section['groups'] = array();
            $known_groups            = array();
            $raw_groups              = isset( $section['groups'] ) && is_array( $section['groups'] ) ? $section['groups'] : array();
            foreach ( $raw_groups as $group_name ) {
                $group_name = sanitize_text_field( $group_name );
                if ( '' === $group_name ) {
                    continue;
                }
                $group_key = gep_demo_directory_lookup_key( $group_name );
                if ( isset( $known_groups[ $group_key ] ) ) {
                    continue;
                }
                $clean_section['groups'][] = $group_name;
                $known_groups[ $group_key ] = true;
            }
        }

        $raw_items = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();
        foreach ( $raw_items as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            if ( 'people' === $type ) {
                $clean_name           = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
                $clean_specialization = isset( $item['specialization'] ) ? sanitize_text_field( $item['specialization'] ) : '';
                if ( '' === $clean_specialization ) {
                    $clean_specialization = gep_demo_original_specialization_for_name( $clean_name );
                }
                $clean_year = isset( $item['year'] ) ? sanitize_text_field( $item['year'] ) : '';
                $section_key = gep_demo_directory_lookup_key( isset( $section['title'] ) ? $section['title'] : '' );
                if ( '' === $clean_year && false !== strpos( $section_key, 'becari' ) ) {
                    $clean_year = gep_demo_original_becario_year_for_name( $clean_name );
                }
                $clean_item = array(
                    'group'          => isset( $item['group'] ) ? sanitize_text_field( $item['group'] ) : '',
                    'specialization' => $clean_specialization,
                    'year'           => $clean_year,
                    'name'           => $clean_name,
                    'meta'           => isset( $item['meta'] ) ? sanitize_text_field( $item['meta'] ) : '',
                    'text'           => isset( $item['text'] ) ? sanitize_textarea_field( $item['text'] ) : '',
                    'email'          => isset( $item['email'] ) ? sanitize_email( $item['email'] ) : '',
                    'image'          => isset( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
                );
            } elseif ( 'status' === $type ) {
                $clean_name   = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
                $clean_status = gep_demo_normalize_original_status( isset( $item['status'] ) ? sanitize_text_field( $item['status'] ) : '' );
                $section_key  = gep_demo_directory_lookup_key( isset( $section['title'] ) ? $section['title'] : '' );
                if ( false !== strpos( $section_key, 'becari' ) && ( '' === $clean_status || in_array( $clean_status, array( 'D', 'T' ), true ) ) ) {
                    $original_year = gep_demo_original_becario_year_for_name( $clean_name );
                    if ( '' !== $original_year ) {
                        $clean_status = $original_year;
                    }
                }
                $clean_item = array(
                    'group'  => isset( $item['group'] ) ? sanitize_text_field( $item['group'] ) : '',
                    'status' => $clean_status,
                    'name'   => $clean_name,
                    'text'   => isset( $item['text'] ) ? sanitize_textarea_field( $item['text'] ) : '',
                );
            } else {
                $clean_item = array(
                    'name'  => isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '',
                    'label' => isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '',
                    'text'  => isset( $item['text'] ) ? sanitize_textarea_field( $item['text'] ) : '',
                    'image' => isset( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
                );
            }

            $has_content = false;
            foreach ( $clean_item as $value ) {
                if ( '' !== $value ) {
                    $has_content = true;
                    break;
                }
            }

            if ( $has_content ) {
                $clean_section['items'][] = $clean_item;

                if ( in_array( $type, array( 'people', 'status' ), true ) && ! empty( $clean_item['group'] ) ) {
                    $group_key = gep_demo_directory_lookup_key( $clean_item['group'] );
                    if ( ! isset( $known_groups[ $group_key ] ) ) {
                        $clean_section['groups'][] = $clean_item['group'];
                        $known_groups[ $group_key ] = true;
                    }
                }
            }
        }

        if ( '' !== $clean_section['title'] || ! empty( $clean_section['items'] ) || ( in_array( $type, array( 'people', 'status' ), true ) && ! empty( $clean_section['groups'] ) ) ) {
            $clean_sections[] = $clean_section;
        }
    }

    return $clean_sections;
}


function gep_demo_clean_contact_rows( $raw_rows ) {
    $raw_rows   = is_array( $raw_rows ) ? $raw_rows : array();
    $clean_rows = array();

    foreach ( $raw_rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $kind = isset( $row['kind'] ) ? sanitize_key( $row['kind'] ) : 'text';
        if ( ! in_array( $kind, array( 'text', 'email', 'tel', 'url' ), true ) ) {
            $kind = 'text';
        }

        $label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
        $value = isset( $row['value'] ) ? trim( (string) $row['value'] ) : '';

        if ( 'email' === $kind ) {
            $value = sanitize_email( $value );
        } elseif ( 'url' === $kind ) {
            $value = esc_url_raw( $value );
        } else {
            $value = sanitize_text_field( $value );
        }

        if ( '' !== $label || '' !== $value ) {
            $clean_rows[] = array( 'kind' => $kind, 'label' => $label, 'value' => $value );
        }
    }

    return $clean_rows;
}

function gep_demo_save_contact_fields( $post_id, $source ) {
    $text_fields = array(
        'gep_contact_hero_eyebrow',
        'gep_contact_hero_title',
        'gep_contact_card_eyebrow',
        'gep_contact_card_title',
        'gep_contact_email_button_text',
        'gep_contact_map_button_text',
        'gep_contact_location_eyebrow',
        'gep_contact_location_title',
        'gep_contact_location_text',
        'gep_contact_cta_button_text',
    );
    foreach ( $text_fields as $field ) {
        if ( isset( $source[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $source[ $field ] ) ) );
        }
    }

    $textarea_fields = array( 'gep_contact_hero_text', 'gep_contact_note', 'gep_contact_cta_title' );
    foreach ( $textarea_fields as $field ) {
        if ( isset( $source[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_textarea_field( wp_unslash( $source[ $field ] ) ) );
        }
    }

    if ( isset( $source['gep_contact_primary_email'] ) ) {
        update_post_meta( $post_id, 'gep_contact_primary_email', sanitize_email( wp_unslash( $source['gep_contact_primary_email'] ) ) );
    }
    if ( isset( $source['gep_contact_hero_image'] ) ) update_post_meta( $post_id, 'gep_contact_hero_image', esc_url_raw( wp_unslash( $source['gep_contact_hero_image'] ) ) );
    if ( isset( $source['gep_contact_hero_video'] ) ) update_post_meta( $post_id, 'gep_contact_hero_video', esc_url_raw( wp_unslash( $source['gep_contact_hero_video'] ) ) );
    if ( isset( $source['gep_contact_map_url'] ) ) {
        update_post_meta( $post_id, 'gep_contact_map_url', esc_url_raw( wp_unslash( $source['gep_contact_map_url'] ) ) );
    }
    if ( isset( $source['gep_contact_map_image_url'] ) ) {
        update_post_meta( $post_id, 'gep_contact_map_image_url', esc_url_raw( wp_unslash( $source['gep_contact_map_image_url'] ) ) );
    }

    $raw_rows = isset( $source['gep_contact_rows'] ) && is_array( $source['gep_contact_rows'] ) ? wp_unslash( $source['gep_contact_rows'] ) : array();
    $rows     = gep_demo_clean_contact_rows( $raw_rows );
    update_post_meta( $post_id, 'gep_contact_rows_json', wp_slash( wp_json_encode( $rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
}


function gep_demo_clean_docencia_learning_steps( $raw ) {
    $raw = is_array( $raw ) ? $raw : array();
    $clean = array();
    foreach ( $raw as $row ) {
        if ( ! is_array( $row ) ) continue;
        $item = array(
            'number' => isset( $row['number'] ) ? sanitize_text_field( $row['number'] ) : '',
            'title'  => isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '',
            'text'   => isset( $row['text'] ) ? sanitize_textarea_field( $row['text'] ) : '',
        );
        if ( '' !== $item['number'] || '' !== $item['title'] || '' !== $item['text'] ) $clean[] = $item;
    }
    return $clean;
}

function gep_demo_clean_docencia_courses( $raw ) {
    $clean=array(); foreach(is_array($raw)?$raw:array() as $row){ if(!is_array($row))continue; $item=array(
        'code'=>isset($row['code'])?sanitize_text_field($row['code']):'','title'=>isset($row['title'])?sanitize_text_field($row['title']):'','status'=>isset($row['status'])?sanitize_text_field($row['status']):'',
        'url'=>isset($row['url'])?esc_url_raw($row['url']):'','button'=>isset($row['button'])?sanitize_text_field($row['button']):'','image'=>isset($row['image'])?esc_url_raw($row['image']):'','image_alt'=>isset($row['image_alt'])?sanitize_text_field($row['image_alt']):'' );
        if(implode('',$item)!=='')$clean[]=$item;
    } return $clean;
}

function gep_demo_save_docencia_fields( $post_id, $source ) {
    $text_fields=array('gep_docencia_hero_eyebrow','gep_docencia_hero_title','gep_docencia_nav_pregrado','gep_docencia_nav_postgrado','gep_docencia_nav_diplomado','gep_docencia_nav_cursos','gep_docencia_nav_campos','gep_docencia_pre_eyebrow','gep_docencia_pre_title','gep_docencia_post_eyebrow','gep_docencia_post_title','gep_docencia_diploma_eyebrow','gep_docencia_diploma_title','gep_docencia_workshop_eyebrow','gep_docencia_workshop_title','gep_docencia_fields_eyebrow','gep_docencia_fields_title','gep_docencia_fields_image_alt');
    foreach($text_fields as $field)if(isset($source[$field]))update_post_meta($post_id,$field,sanitize_text_field(wp_unslash($source[$field])));
    foreach(array('gep_docencia_hero_text','gep_docencia_pre_text','gep_docencia_post_text','gep_docencia_diploma_text','gep_docencia_workshop_text','gep_docencia_note','gep_docencia_fields_text') as $field)if(isset($source[$field]))update_post_meta($post_id,$field,sanitize_textarea_field(wp_unslash($source[$field])));
    if(isset($source['gep_docencia_hero_image']))update_post_meta($post_id,'gep_docencia_hero_image',esc_url_raw(wp_unslash($source['gep_docencia_hero_image'])));
    if(isset($source['gep_docencia_hero_video']))update_post_meta($post_id,'gep_docencia_hero_video',esc_url_raw(wp_unslash($source['gep_docencia_hero_video'])));
    if(isset($source['gep_docencia_fields_image_url']))update_post_meta($post_id,'gep_docencia_fields_image_url',esc_url_raw(wp_unslash($source['gep_docencia_fields_image_url'])));
    $steps=isset($source['gep_docencia_learning_steps'])&&is_array($source['gep_docencia_learning_steps'])?wp_unslash($source['gep_docencia_learning_steps']):array();
    update_post_meta($post_id,'gep_docencia_learning_steps_json',wp_slash( wp_json_encode(gep_demo_clean_docencia_learning_steps($steps),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ));
    foreach(array('pre','post','diploma','workshop') as $g){$key='gep_docencia_'.$g.'_courses';$raw=isset($source[$key])&&is_array($source[$key])?wp_unslash($source[$key]):array();update_post_meta($post_id,$key.'_json',wp_slash( wp_json_encode(gep_demo_clean_docencia_courses($raw),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ));}
}


function gep_demo_clean_project_features( $raw ) {
    $clean = array(); foreach ( is_array( $raw ) ? $raw : array() as $row ) { if ( ! is_array( $row ) ) continue; $item=array('image'=>isset($row['image'])?esc_url_raw($row['image']):'','alt'=>isset($row['alt'])?sanitize_text_field($row['alt']):'','kicker'=>isset($row['kicker'])?sanitize_text_field($row['kicker']):'','title'=>isset($row['title'])?sanitize_text_field($row['title']):'','text'=>isset($row['text'])?sanitize_textarea_field($row['text']):''); if(implode('',$item)!=='')$clean[]=$item; } return $clean;
}
function gep_demo_clean_project_records( $raw ) {
    $clean = array();
    $used_slugs = array();
    foreach ( is_array( $raw ) ? $raw : array() as $index => $row ) {
        if ( ! is_array( $row ) ) continue;
        $status = isset( $row['status'] ) ? sanitize_key( $row['status'] ) : 'cerrado';
        if ( ! in_array( $status, array( 'vigente', 'cerrado' ), true ) ) $status = 'cerrado';
        $slug = gep_demo_project_slug( $row );
        if ( '' === $slug ) $slug = 'proyecto-' . ( (int) $index + 1 );
        $base_slug = $slug;
        $slug_n = 2;
        while ( isset( $used_slugs[ $slug ] ) ) {
            $slug = $base_slug . '-' . $slug_n;
            $slug_n++;
        }
        $used_slugs[ $slug ] = true;
        $item = array(
            'slug'        => $slug,
            'status'      => $status,
            'year'        => isset( $row['year'] ) ? sanitize_text_field( $row['year'] ) : '',
            'era'         => isset( $row['era'] ) ? sanitize_text_field( $row['era'] ) : '',
            'theme'       => isset( $row['theme'] ) ? sanitize_text_field( $row['theme'] ) : '',
            'title'       => isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '',
            'text'        => isset( $row['text'] ) ? sanitize_textarea_field( $row['text'] ) : '',
            'url'         => isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '',
            'button'      => isset( $row['button'] ) ? sanitize_text_field( $row['button'] ) : '',
            'tag'         => isset( $row['tag'] ) ? sanitize_text_field( $row['tag'] ) : '',
            'image'       => isset( $row['image'] ) ? esc_url_raw( $row['image'] ) : '',
            'image_alt'   => isset( $row['image_alt'] ) ? sanitize_text_field( $row['image_alt'] ) : '',
            'details'     => isset( $row['details'] ) ? sanitize_textarea_field( $row['details'] ) : '',
            'institution' => isset( $row['institution'] ) ? sanitize_text_field( $row['institution'] ) : '',
            'lead'        => isset( $row['lead'] ) ? sanitize_text_field( $row['lead'] ) : '',
            'gallery'     => isset( $row['gallery'] ) ? gep_demo_clean_project_gallery( $row['gallery'] ) : '',
            'detail_url'  => isset( $row['detail_url'] ) ? esc_url_raw( $row['detail_url'] ) : '',
        );
        $has_content = '' !== implode( '', array( $item['year'], $item['theme'], $item['title'], $item['text'], $item['url'], $item['image'], $item['details'], $item['institution'], $item['lead'], $item['gallery'] ) );
        if ( $has_content ) $clean[] = $item;
    }
    return $clean;
}
function gep_demo_clean_project_areas( $raw ) {
    $clean=array();foreach(is_array($raw)?$raw:array() as $row){if(!is_array($row))continue;$item=array('icon'=>isset($row['icon'])?sanitize_text_field($row['icon']):'','title'=>isset($row['title'])?sanitize_text_field($row['title']):'','text'=>isset($row['text'])?sanitize_textarea_field($row['text']):'');if(implode('',$item)!=='')$clean[]=$item;}return $clean;
}
function gep_demo_save_projects_fields( $post_id, $source ) {
    // v9.26.0 · Respaldo previo a la edición unificada de proyectos: la primera vez
    // que se guarda Proyectos con esta versión se copia tal cual lo guardado
    // (registros + antiguos "destacados"). Meta privada (con "_"), fuera del
    // Historial; nunca se sobrescribe. Volver atrás: Historial, o esta copia.
    if ( ! metadata_exists( 'post', $post_id, '_gep_projects_backup_v926' ) ) {
        add_post_meta( $post_id, '_gep_projects_backup_v926', wp_slash( wp_json_encode( array(
            'version'       => GEP_EDITOR_VERSION,
            'time'          => current_time( 'mysql' ),
            'records_json'  => get_post_meta( $post_id, 'gep_projects_records_json', true ),
            'features_json' => get_post_meta( $post_id, 'gep_projects_features_json', true ),
            'had_records'   => metadata_exists( 'post', $post_id, 'gep_projects_records_json' ),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ), true );
    }
    $text=array('gep_projects_hero_eyebrow','gep_projects_hero_title','gep_projects_nav_featured','gep_projects_nav_trajectory','gep_projects_nav_areas','gep_projects_trajectory_eyebrow','gep_projects_trajectory_title','gep_projects_areas_eyebrow','gep_projects_areas_title');foreach($text as $f)if(isset($source[$f]))update_post_meta($post_id,$f,sanitize_text_field(wp_unslash($source[$f])));
    $areas=array('gep_projects_hero_text','gep_projects_trajectory_text','gep_projects_note','gep_projects_areas_text');foreach($areas as $f)if(isset($source[$f]))update_post_meta($post_id,$f,sanitize_textarea_field(wp_unslash($source[$f])));
    if(isset($source['gep_projects_hero_image']))update_post_meta($post_id,'gep_projects_hero_image',esc_url_raw(wp_unslash($source['gep_projects_hero_image'])));
    if(isset($source['gep_projects_hero_video']))update_post_meta($post_id,'gep_projects_hero_video',esc_url_raw(wp_unslash($source['gep_projects_hero_video'])));
    $records=isset($source['gep_projects_records'])&&is_array($source['gep_projects_records'])?wp_unslash($source['gep_projects_records']):array();$work=isset($source['gep_projects_areas'])&&is_array($source['gep_projects_areas'])?wp_unslash($source['gep_projects_areas']):array();
    if(isset($source['gep_projects_features'])&&is_array($source['gep_projects_features'])){$features=wp_unslash($source['gep_projects_features']);update_post_meta($post_id,'gep_projects_features_json',wp_slash( wp_json_encode(gep_demo_clean_project_features($features),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ));}
    update_post_meta($post_id,'gep_projects_records_json',wp_slash( wp_json_encode(gep_demo_clean_project_records($records),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ));update_post_meta($post_id,'gep_projects_areas_json',wp_slash( wp_json_encode(gep_demo_clean_project_areas($work),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ));
}


function gep_demo_clean_publication_sections( $raw ) {
    $allowed=gep_demo_publication_category_options();
    $clean=array();
    foreach($allowed as $key=>$fallback){
        $row=isset($raw[$key])&&is_array($raw[$key])?$raw[$key]:array();
        $clean[$key]=array(
            'label'=>isset($row['label'])?sanitize_text_field($row['label']):$fallback,
            'eyebrow'=>isset($row['eyebrow'])?sanitize_text_field($row['eyebrow']):'',
            'title'=>isset($row['title'])?sanitize_text_field($row['title']):''
        );
    }
    return $clean;
}
function gep_demo_clean_publication_records( $raw ) {
    $clean=array(); $seen_doi=array(); /* v9.23.0: un mismo DOI se guarda una sola vez */ foreach(is_array($raw)?$raw:array() as $row){if(!is_array($row))continue;$item=array('category'=>gep_demo_publication_normalize_category(isset($row['category'])?$row['category']:''),'year'=>isset($row['year'])?sanitize_text_field($row['year']):'','title'=>isset($row['title'])?sanitize_text_field($row['title']):'','text'=>isset($row['text'])?sanitize_textarea_field($row['text']):'','url'=>isset($row['url'])?esc_url_raw($row['url']):'','button'=>isset($row['button'])?sanitize_text_field($row['button']):'');$doi=function_exists('gep_demo_doi_normalize')&&isset($row['doi'])?gep_demo_doi_normalize($row['doi']):'';if($doi!==''){$dk=strtolower($doi);if(isset($seen_doi[$dk]))continue;$seen_doi[$dk]=true;$item['doi']=$doi;}if($item['year']!==''||$item['title']!==''||$item['text']!==''||$item['url']!=='')$clean[]=$item;}return $clean;
}
function gep_demo_save_publications_fields( $post_id, $source ) {
    foreach(array('gep_publications_hero_eyebrow','gep_publications_hero_title','gep_publications_nav_search','gep_publications_explorer_title') as $f)if(isset($source[$f]))update_post_meta($post_id,$f,sanitize_text_field(wp_unslash($source[$f])));
    foreach(array('gep_publications_hero_text','gep_publications_note') as $f)if(isset($source[$f]))update_post_meta($post_id,$f,sanitize_textarea_field(wp_unslash($source[$f])));
    if(isset($source['gep_publications_hero_image']))update_post_meta($post_id,'gep_publications_hero_image',esc_url_raw(wp_unslash($source['gep_publications_hero_image'])));
    if(isset($source['gep_publications_hero_video']))update_post_meta($post_id,'gep_publications_hero_video',esc_url_raw(wp_unslash($source['gep_publications_hero_video'])));
    $sections=isset($source['gep_publications_sections'])&&is_array($source['gep_publications_sections'])?wp_unslash($source['gep_publications_sections']):array();$records=isset($source['gep_publications_records'])&&is_array($source['gep_publications_records'])?wp_unslash($source['gep_publications_records']):array();
    update_post_meta($post_id,'gep_publications_sections_json',wp_slash( wp_json_encode(gep_demo_clean_publication_sections($sections),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ));update_post_meta($post_id,'gep_publications_records_json',wp_slash( wp_json_encode(gep_demo_clean_publication_records($records),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ));
}


function gep_demo_home_clean_list( $raw, $kind ) {
    $out=array(); foreach(is_array($raw)?$raw:array() as $row){if(!is_array($row))continue;
      if(in_array($kind,array('pill','topic'),true)){ $t=isset($row['text'])?sanitize_text_field($row['text']):''; if($t!=='')$out[]=$t; continue; }
      if($kind==='stat')$item=array('value'=>sanitize_text_field($row['value']??''),'label'=>sanitize_text_field($row['label']??''),'text'=>sanitize_textarea_field($row['text']??''));
      elseif($kind==='definition')$item=array('eyebrow'=>sanitize_text_field($row['eyebrow']??''),'title'=>sanitize_text_field($row['title']??''),'text'=>sanitize_textarea_field($row['text']??''));
      elseif($kind==='research')$item=array('image'=>esc_url_raw($row['image']??''),'alt'=>sanitize_text_field($row['alt']??''),'kicker'=>sanitize_text_field($row['kicker']??''),'title'=>sanitize_text_field($row['title']??''),'text'=>sanitize_textarea_field($row['text']??''));
      elseif($kind==='portal')$item=array('icon'=>sanitize_text_field($row['icon']??''),'title'=>sanitize_text_field($row['title']??''),'text'=>sanitize_textarea_field($row['text']??''),'url'=>esc_url_raw($row['url']??''),'button'=>sanitize_text_field($row['button']??''));
      elseif($kind==='resource')$item=array('kicker'=>sanitize_text_field($row['kicker']??''),'title'=>sanitize_text_field($row['title']??''),'url'=>esc_url_raw($row['url']??''),'button'=>sanitize_text_field($row['button']??''));
      else $item=array('title'=>sanitize_text_field($row['title']??''),'url'=>esc_url_raw($row['url']??''));
      if(implode('',array_values($item))!=='')$out[]=$item;
    } return $out;
}
function gep_demo_save_home_fields( $post_id, $source ) {
    $text=array('gep_home_hero_eyebrow','gep_home_hero_title','gep_home_hero_primary_text','gep_home_hero_primary_url','gep_home_hero_secondary_text','gep_home_hero_secondary_url','gep_home_hero_geo_a','gep_home_hero_geo_b','gep_home_intro_eyebrow','gep_home_intro_title','gep_home_field_alt','gep_home_field_index','gep_home_field_eyebrow','gep_home_field_title','gep_home_research_eyebrow','gep_home_research_title','gep_home_portal_eyebrow','gep_home_portal_title','gep_home_landscape_label','gep_home_landscape_title','gep_home_resources_eyebrow','gep_home_resources_title','gep_home_topics_eyebrow','gep_home_topics_title','gep_home_network_alt','gep_home_network_eyebrow','gep_home_network_title','gep_home_cta_eyebrow','gep_home_cta_title','gep_home_cta_button','gep_home_cta_url');
    foreach($text as $f)if(isset($source[$f])){ $val=wp_unslash($source[$f]); if(substr($f,-4)==='_url')update_post_meta($post_id,$f,esc_url_raw($val)); else update_post_meta($post_id,$f,sanitize_text_field($val)); }
    foreach(array('gep_home_hero_text','gep_home_intro_p1','gep_home_intro_p2','gep_home_field_text','gep_home_research_text','gep_home_portal_text','gep_home_resources_text','gep_home_topics_text','gep_home_network_text','gep_home_note') as $f)if(isset($source[$f]))update_post_meta($post_id,$f,sanitize_textarea_field(wp_unslash($source[$f])));
    foreach(array('gep_home_hero_image','gep_home_hero_video','gep_home_field_image','gep_home_network_image','gep_home_landscape_image') as $f)if(isset($source[$f]))update_post_meta($post_id,$f,esc_url_raw(wp_unslash($source[$f])));
    // v9.25.0: encuadre ("x% y%") y ajuste de la foto de «Mensaje de paisaje».
    if(isset($source['gep_home_landscape_focus'])){$focus=trim((string)wp_unslash($source['gep_home_landscape_focus']));if(!preg_match('/^(\d{1,3}(?:\.\d)?)% (\d{1,3}(?:\.\d)?)%$/',$focus,$fm)||(float)$fm[1]>100||(float)$fm[2]>100)$focus='';update_post_meta($post_id,'gep_home_landscape_focus',$focus);}
    if(isset($source['gep_home_landscape_fit']))update_post_meta($post_id,'gep_home_landscape_fit','cover'===$source['gep_home_landscape_fit']?'cover':'auto');
    $lists=array('pills'=>'pill','stats'=>'stat','definitions'=>'definition','research_cards'=>'research','portal_cards'=>'portal','resources'=>'resource','topics'=>'topic','network_links'=>'network');
    foreach($lists as $key=>$kind){$raw=isset($source['gep_home_'.$key])&&is_array($source['gep_home_'.$key])?wp_unslash($source['gep_home_'.$key]):array();update_post_meta($post_id,'gep_home_'.$key.'_json',wp_slash( wp_json_encode(gep_demo_home_clean_list($raw,$kind),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ));}
}

function gep_demo_save_content_fields( $post_id, $source ) {
    update_post_meta( $post_id, 'gep_team_enabled', isset( $source['gep_team_enabled'] ) ? '1' : '0' );

    if ( isset( $source['gep_team_eyebrow'] ) ) {
        update_post_meta( $post_id, 'gep_team_eyebrow', sanitize_text_field( wp_unslash( $source['gep_team_eyebrow'] ) ) );
    }
    if ( isset( $source['gep_team_title'] ) ) {
        update_post_meta( $post_id, 'gep_team_title', sanitize_textarea_field( wp_unslash( $source['gep_team_title'] ) ) );
    }
    if ( isset( $source['gep_team_description'] ) ) {
        update_post_meta( $post_id, 'gep_team_description', sanitize_textarea_field( wp_unslash( $source['gep_team_description'] ) ) );
    }

    $raw_team_cards = isset( $source['gep_team_cards'] ) && is_array( $source['gep_team_cards'] ) ? wp_unslash( $source['gep_team_cards'] ) : array();
    $team_cards     = gep_demo_clean_team_cards( $raw_team_cards );
    update_post_meta( $post_id, 'gep_team_cards_json', wp_slash( wp_json_encode( $team_cards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );

    $text_fields = array( 'gep_directory_eyebrow', 'gep_directory_title', 'gep_directory_legend' );
    foreach ( $text_fields as $field ) {
        if ( isset( $source[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $source[ $field ] ) ) );
        }
    }

    if ( isset( $source['gep_directory_description'] ) ) {
        update_post_meta( $post_id, 'gep_directory_description', sanitize_textarea_field( wp_unslash( $source['gep_directory_description'] ) ) );
    }

    if ( isset( $source['gep_about_hero_image'] ) ) update_post_meta( $post_id, 'gep_about_hero_image', esc_url_raw( wp_unslash( $source['gep_about_hero_image'] ) ) );
    if ( isset( $source['gep_about_hero_video'] ) ) update_post_meta( $post_id, 'gep_about_hero_video', esc_url_raw( wp_unslash( $source['gep_about_hero_video'] ) ) );

    if ( isset( $source['gep_directory_image_url'] ) ) {
        update_post_meta( $post_id, 'gep_directory_image_url', esc_url_raw( wp_unslash( $source['gep_directory_image_url'] ) ) );
    }

    update_post_meta( $post_id, 'gep_directory_replace_static', isset( $source['gep_directory_replace_static'] ) ? '1' : '0' );

    // v9.23.0: solo se reemplaza el directorio si el formulario lo envió con
    // personas. Un guardado sin apartados ya no deja Quiénes somos vacío.
    $raw_sections = isset( $source['gep_sections'] ) && is_array( $source['gep_sections'] ) ? wp_unslash( $source['gep_sections'] ) : array();
    $sections     = gep_demo_clean_sections( $raw_sections );
    if ( ! empty( $sections ) ) {
        update_post_meta( $post_id, 'gep_directory_sections_json', wp_slash( wp_json_encode( $sections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
    }
}

function gep_demo_handle_admin_save() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'gep-editor-demo' ) );
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id || 'page' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( esc_html__( 'La página seleccionada no es válida.', 'gep-editor-demo' ) );
    }

    // v10.0.0: la clave vencida ya no muestra el mensaje genérico de WordPress
    // («El enlace ha caducado»): explica qué pasó y queda en el registro técnico.
    // El guardado seguro del navegador evita llegar aquí casi siempre.
    $nonce = isset( $_POST['gep_demo_admin_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['gep_demo_admin_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'gep_demo_save_content_' . $post_id ) ) {
        if ( function_exists( 'gep_registro' ) ) {
            gep_registro( 'sesion', 'Guardado rechazado: la clave de seguridad del formulario venció.', get_the_title( $post_id ) );
        }
        wp_die(
            '<h1>No se guardó</h1><p>La página del editor llevaba demasiado tiempo abierta o la sesión cambió. Por seguridad, WordPress no aceptó el guardado.</p>'
            . '<p>Vuelve atrás con el botón del navegador: si tus cambios siguen ahí, pulsa <strong>Guardar cambios</strong> otra vez.</p>',
            'No se guardó',
            array( 'response' => 403, 'back_link' => true )
        );
    }

    $navigation = gep_demo_get_page_navigation();
    $context    = isset( $_POST['gep_editor_context'] ) ? sanitize_key( wp_unslash( $_POST['gep_editor_context'] ) ) : '';
    $history_context = function_exists( 'gep_demo_history_normalize_context' ) ? gep_demo_history_normalize_context( $post_id, $context, $navigation ) : $context;
    $history_before  = array();
    if ( function_exists( 'gep_demo_history_ensure_baseline' ) ) {
        gep_demo_history_ensure_baseline( $post_id, $history_context );
        $history_before = gep_demo_history_capture_snapshot( $post_id );
    }
    // v10.0.2: el tipo de página se deduce de la página misma. El campo oculto
    // del formulario solo decide cuando la página no se reconoce (como antes),
    // así un formulario con un contexto equivocado no escribe campos ajenos.
    $detected = '';
    if ( gep_demo_is_home_page( $post_id, $navigation ) ) {
        $detected = 'home';
    } elseif ( gep_demo_is_services_page( $post_id, $navigation ) ) {
        $detected = 'services';
    } elseif ( gep_demo_is_hub_page( $post_id, 'communications', $navigation ) ) {
        $detected = 'communications';
    } elseif ( gep_demo_is_hub_page( $post_id, 'news', $navigation ) ) {
        $detected = 'news';
    } elseif ( gep_demo_is_publications_page( $post_id, $navigation ) ) {
        $detected = 'publications';
    } elseif ( gep_demo_is_projects_page( $post_id, $navigation ) ) {
        $detected = 'projects';
    } elseif ( gep_demo_is_docencia_page( $post_id, $navigation ) ) {
        $detected = 'docencia';
    } elseif ( gep_demo_is_contact_page( $post_id, $navigation ) ) {
        $detected = 'contact';
    }
    if ( '' !== $detected && $detected !== $context ) {
        if ( '' !== $context && function_exists( 'gep_registro' ) ) {
            gep_registro( 'guardado', sprintf( 'El formulario decía «%s» pero la página es «%s»: se guardó como «%s».', $context, $detected, $detected ), get_the_title( $post_id ) );
        }
        $context = $detected;
    }
    $save_as = '' !== $detected ? $detected : $context;

    if ( 'home' === $save_as ) {
        gep_demo_save_home_fields( $post_id, $_POST );
    } elseif ( 'services' === $save_as ) {
        gep_demo_save_services_fields( $post_id, $_POST );
    } elseif ( 'communications' === $save_as ) {
        gep_demo_save_hub_fields( $post_id, $_POST, 'communications' );
    } elseif ( 'news' === $save_as ) {
        gep_demo_save_hub_fields( $post_id, $_POST, 'news' );
    } elseif ( 'publications' === $save_as ) {
        gep_demo_save_publications_fields( $post_id, $_POST );
    } elseif ( 'projects' === $save_as ) {
        gep_demo_save_projects_fields( $post_id, $_POST );
    } elseif ( 'docencia' === $save_as ) {
        gep_demo_save_docencia_fields( $post_id, $_POST );
    } elseif ( 'contact' === $save_as ) {
        gep_demo_save_contact_fields( $post_id, $_POST );
    } else {
        gep_demo_save_content_fields( $post_id, $_POST );
    }

    if ( function_exists( 'gep_demo_save_page_structure' ) ) {
        gep_demo_save_page_structure( $post_id, $context, $_POST );
    }
    // v9.26.0: encuadre de fotos y botones visibles/ocultos (comunes a todas las pestañas).
    if ( function_exists( 'gep_frames_save' ) ) {
        gep_frames_save( $post_id, $_POST );
    }
    if ( function_exists( 'gep_buttons_save' ) ) {
        gep_buttons_save( $post_id, $_POST );
    }

    if ( function_exists( 'gep_demo_history_record_save' ) ) {
        $history_after = gep_demo_history_capture_snapshot( $post_id );
        gep_demo_history_record_save( $post_id, $history_context, $history_before, $history_after );
    }

    // v9.23.0: avisa a quien tenga la misma página abierta que hubo un guardado.
    if ( function_exists( 'gep_lock_mark_saved' ) ) {
        gep_lock_mark_saved( $post_id );
    }

    $redirect = add_query_arg(
        array(
            'page'      => 'gep-content-editor',
            'post_id'   => $post_id,
            'gep_saved' => '1',
        ),
        admin_url( 'admin.php' )
    );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_post_gep_demo_save_content', 'gep_demo_handle_admin_save' );


/**
 * --------------------------------------------------------------------------
 * Front-end: fotos de personas del directorio
 * --------------------------------------------------------------------------
 *
 * El tema actual ya dibuja las fichas del directorio a partir del JSON del
 * plugin, pero fue creado antes de que existiera la clave `image`. Para no
 * obligar a modificar el tema en esta etapa, el plugin completa las fichas en
 * el front-end buscando el nombre exacto de cada persona y añadiendo su foto.
 *
 * Esto mantiene intacta la plantilla existente y desaparece automáticamente
 * si en el futuro el tema empieza a renderizar la imagen por sí mismo.
 */
function gep_demo_get_people_with_images( $post_id ) {
    $people = array();

    foreach ( gep_demo_get_sections( $post_id ) as $section ) {
        if ( ! is_array( $section ) || 'people' !== ( isset( $section['type'] ) ? $section['type'] : '' ) ) {
            continue;
        }

        $items = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();
        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $name           = isset( $item['name'] ) ? trim( (string) $item['name'] ) : '';
            $image          = isset( $item['image'] ) ? esc_url_raw( (string) $item['image'] ) : '';
            $specialization = isset( $item['specialization'] ) ? trim( (string) $item['specialization'] ) : '';
            $year           = isset( $item['year'] ) ? trim( (string) $item['year'] ) : '';
            $section_title  = isset( $section['title'] ) ? gep_demo_directory_lookup_key( $section['title'] ) : '';
            $is_becario     = false !== strpos( $section_title, 'becari' );

            if ( '' === $name || ( '' === $image && '' === $specialization && ( ! $is_becario || '' === $year ) ) ) {
                continue;
            }

            $people[] = array(
                'name'           => $name,
                'image'          => $image,
                'specialization' => $specialization,
                'year'           => $is_becario ? $year : '',
            );
        }
    }

    return $people;
}

function gep_demo_render_public_person_images() {
    if ( is_admin() || ! is_singular( 'page' ) ) {
        return;
    }

    $post_id = (int) get_queried_object_id();
    if ( ! $post_id || ! gep_demo_is_directory_page( $post_id ) ) {
        return;
    }

    $people = gep_demo_get_people_with_images( $post_id );
    if ( empty( $people ) ) {
        return;
    }
    ?>
    <style id="gep-directory-person-images-css">
        .gep-person-auto-photo-card{
            position:relative!important;
            padding-left:calc(var(--gep-photo-base-left, 20px) + 124px)!important;
            min-height:calc(108px + var(--gep-photo-base-top, 20px) + var(--gep-photo-base-bottom, 20px))!important;
        }
        .gep-person-auto-photo{
            position:absolute;
            left:var(--gep-photo-base-left, 20px);
            top:var(--gep-photo-base-top, 20px);
            width:104px;
            height:108px;
            margin:0;
            border-radius:10px;
            overflow:hidden;
            background:#e9efe9;
            border:1px solid rgba(31,90,59,.14);
            box-shadow:0 2px 10px rgba(18,48,31,.08);
        }
        .gep-person-auto-photo img{
            display:block!important;
            width:100%!important;
            height:100%!important;
            max-width:none!important;
            object-fit:cover!important;
            object-position:center!important;
            margin:0!important;
            border:0!important;
            border-radius:0!important;
        }
        .gep-person-auto-year{
            display:inline-flex!important;
            align-items:center;
            justify-content:center;
            min-width:76px;
            margin:0 0 7px!important;
            padding:5px 10px!important;
            border:1px solid rgba(31,90,59,.14);
            border-radius:999px;
            background:#e8f1eb;
            color:#1f5a3b!important;
            font-size:12px!important;
            line-height:1.2!important;
            font-weight:800!important;
            font-variant-numeric:tabular-nums;
        }
        .gep-person-auto-specialization{
            display:block!important;
            width:max-content;
            max-width:100%;
            margin:0 0 6px!important;
            color:#1f5a3b!important;
            font-size:12px!important;
            line-height:1.4!important;
            font-weight:700!important;
            letter-spacing:0;
            text-transform:none;
        }
        @media (max-width:640px){
            .gep-person-auto-photo-card{
                padding-left:var(--gep-photo-base-left, 20px)!important;
                padding-top:calc(var(--gep-photo-base-top, 20px) + 88px)!important;
                min-height:0!important;
            }
            .gep-person-auto-photo{
                width:72px;
                height:72px;
            }
        }
    </style>
    <script id="gep-directory-person-images-js">
    document.addEventListener('DOMContentLoaded', function () {
        var people = <?php echo wp_json_encode( $people, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
        if (!Array.isArray(people) || !people.length) return;

        function clean(value) {
            return String(value || '').replace(/\s+/g, ' ').trim().toLocaleLowerCase('es');
        }

        function findNameElement(name) {
            var target = clean(name);
            var selector = 'h1,h2,h3,h4,h5,h6,strong,b,p,span,a,div';
            var nodes = document.querySelectorAll(selector);
            var best = null;

            for (var i = 0; i < nodes.length; i++) {
                var el = nodes[i];
                if (el.closest('#wpadminbar,header,nav,footer,.gep-editor-wrap')) continue;
                if (clean(el.textContent) !== target) continue;

                // Preferimos elementos de texto pequeños frente a contenedores grandes.
                if (!best || el.children.length < best.children.length) best = el;
                if (el.matches('h1,h2,h3,h4,h5,h6,strong,b')) return el;
            }
            return best;
        }

        function findCard(el, name) {
            if (!el) return null;

            var preferred = el.closest(
                'article,li,[class*="person"],[class*="member"],[class*="card"],[class*="item"],[class*="registro"],[class*="entry"]'
            );
            if (preferred && preferred !== document.body) return preferred;

            var node = el.parentElement;
            var target = clean(name);
            var fallback = node;

            for (var depth = 0; node && depth < 5; depth++, node = node.parentElement) {
                if (node === document.body || node === document.documentElement) break;
                var text = clean(node.innerText || node.textContent);
                if (text.indexOf(target) === -1) continue;
                if (text.length > 1200) break;

                fallback = node;
                var cs = window.getComputedStyle(node);
                var hasCardLook = parseFloat(cs.borderTopWidth || '0') > 0 ||
                    cs.borderRadius !== '0px' ||
                    cs.backgroundColor !== 'rgba(0, 0, 0, 0)';
                if (hasCardLook && depth > 0) return node;
            }
            return fallback;
        }

        people.forEach(function (person) {
            if (!person || !person.name) return;

            var nameEl = findNameElement(person.name);
            if (!nameEl) return;

            var card = findCard(nameEl, person.name);
            if (!card) return;

            if (person.year && clean(card.textContent).indexOf(clean(person.year)) === -1) {
                var year = document.createElement('span');
                year.className = 'gep-person-auto-year';
                year.textContent = person.year;
                if (nameEl.parentNode) nameEl.parentNode.insertBefore(year, nameEl);
            }

            if (person.specialization && clean(card.textContent).indexOf(clean(person.specialization)) === -1) {
                var specialization = document.createElement('span');
                specialization.className = 'gep-person-auto-specialization';
                specialization.textContent = 'Especialización · ' + person.specialization;
                if (nameEl.parentNode) nameEl.parentNode.insertBefore(specialization, nameEl);
            }

            if (!person.image || card.querySelector(':scope > .gep-person-auto-photo')) return;

            // Si la plantilla futura ya muestra la misma imagen, no la duplicamos.
            var existingImages = card.querySelectorAll('img');
            for (var i = 0; i < existingImages.length; i++) {
                var src = existingImages[i].currentSrc || existingImages[i].src || '';
                if (src && src === person.image) return;
            }

            var cs = window.getComputedStyle(card);
            var padLeft = parseFloat(cs.paddingLeft || '0');
            var padTop = parseFloat(cs.paddingTop || '0');
            var padBottom = parseFloat(cs.paddingBottom || '0');

            if (!isFinite(padLeft)) padLeft = 20;
            if (!isFinite(padTop)) padTop = 20;
            if (!isFinite(padBottom)) padBottom = 20;

            card.style.setProperty('--gep-photo-base-left', padLeft + 'px');
            card.style.setProperty('--gep-photo-base-top', padTop + 'px');
            card.style.setProperty('--gep-photo-base-bottom', padBottom + 'px');
            card.classList.add('gep-person-auto-photo-card');

            var figure = document.createElement('figure');
            figure.className = 'gep-person-auto-photo';
            figure.setAttribute('aria-label', 'Foto de ' + person.name);

            var img = document.createElement('img');
            img.src = person.image;
            img.alt = person.name;
            img.loading = 'lazy';
            img.decoding = 'async';

            figure.appendChild(img);
            card.insertBefore(figure, card.firstChild);
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'gep_demo_render_public_person_images', 40 );
