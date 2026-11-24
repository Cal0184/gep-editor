<?php
/**
 * GEP Editor v9.21.0 · Importar publicaciones por DOI.
 *
 * En Publicaciones > Registros bibliográficos, el botón "Importar por DOI" busca
 * cada DOI en Crossref (y, si no está ahí, en doi.org, que cubre DataCite/Zenodo)
 * y arma un registro con el mismo formato que los existentes:
 * "Apellido, N., Apellido, N. & Apellido, N. Título. Revista 35(3):269-278."
 * Nada se guarda solo: los registros entran a la lista y se guardan con
 * "Guardar cambios", como cualquier otra edición.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Devuelve el DOI limpio (10.xxxx/...) o '' si el texto no es un DOI. */
function gep_demo_doi_normalize( $raw ) {
    $doi = trim( wp_strip_all_tags( (string) $raw ) );
    $doi = preg_replace( '#^(?:https?://)?(?:dx\.)?doi\.org/#i', '', $doi );
    $doi = preg_replace( '#^doi:\s*#i', '', $doi );
    $doi = rawurldecode( $doi );
    $doi = rtrim( $doi, " \t.,;" );
    if ( ! preg_match( '#^10\.\d{4,9}/\S+$#', $doi ) || strlen( $doi ) > 200 ) {
        return '';
    }
    return $doi;
}

/** Consulta Crossref y, si no lo encuentra, doi.org (CSL-JSON). Cachea 30 días. */
function gep_demo_doi_fetch( $doi ) {
    $cache_key = 'gep_doi_' . md5( strtolower( $doi ) );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $path = implode( '/', array_map( 'rawurlencode', explode( '/', $doi ) ) );
    $ua   = 'GEP-Editor/' . GEP_EDITOR_VERSION . ' (WordPress; +' . home_url( '/' ) . ')';
    $network_error = false;

    $response = wp_remote_get( 'https://api.crossref.org/works/' . $path, array( 'timeout' => 12, 'user-agent' => $ua ) );
    if ( is_wp_error( $response ) ) {
        $network_error = true;
    } elseif ( 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( is_array( $body ) && isset( $body['message'] ) && is_array( $body['message'] ) ) {
            set_transient( $cache_key, $body['message'], 30 * DAY_IN_SECONDS );
            return $body['message'];
        }
    }

    $response = wp_remote_get( 'https://doi.org/' . $path, array(
        'timeout'    => 12,
        'user-agent' => $ua,
        'headers'    => array( 'Accept' => 'application/vnd.citationstyles.csl+json' ),
    ) );
    if ( is_wp_error( $response ) ) {
        $network_error = true;
    } elseif ( 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( is_array( $body ) && ( ! empty( $body['title'] ) || ! empty( $body['DOI'] ) ) ) {
            set_transient( $cache_key, $body, 30 * DAY_IN_SECONDS );
            return $body;
        }
    }

    if ( $network_error ) {
        return new WP_Error( 'gep_doi_network', 'No se pudo conectar con el servicio de DOI. Revisa la conexión del servidor e inténtalo de nuevo.' );
    }
    return new WP_Error( 'gep_doi_not_found', 'No se encontró este DOI. Revisa que esté bien escrito.' );
}

/** Texto plano de un campo que puede venir como lista (Crossref) o texto (CSL). */
function gep_demo_doi_text( $value ) {
    if ( is_array( $value ) ) {
        $value = reset( $value );
    }
    if ( ! is_string( $value ) ) {
        return '';
    }
    $value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    return trim( preg_replace( '/\s+/u', ' ', $value ) );
}

/** "Carlos Patricio" -> "C.P.", "Jean-Luc" -> "J.-L.", "H. J." -> "H.J." */
function gep_demo_doi_initials( $given ) {
    $out = '';
    foreach ( preg_split( '/[\s.]+/u', (string) $given, -1, PREG_SPLIT_NO_EMPTY ) as $part ) {
        $pieces = array();
        foreach ( explode( '-', $part ) as $piece ) {
            if ( '' !== $piece ) {
                $pieces[] = mb_strtoupper( mb_substr( $piece, 0, 1, 'UTF-8' ), 'UTF-8' ) . '.';
            }
        }
        $out .= implode( '-', $pieces );
    }
    return $out;
}

/** Lista de personas en el estilo de los registros GEP: "A, B. & C, D." */
function gep_demo_doi_people( $people, $max = 15 ) {
    $names = array();
    foreach ( is_array( $people ) ? $people : array() as $person ) {
        if ( ! is_array( $person ) ) {
            continue;
        }
        $family = isset( $person['family'] ) ? gep_demo_doi_text( $person['family'] ) : '';
        $given  = isset( $person['given'] ) ? gep_demo_doi_text( $person['given'] ) : '';
        if ( '' !== $family ) {
            $initials = gep_demo_doi_initials( $given );
            $names[]  = '' !== $initials ? $family . ', ' . $initials : $family;
        } elseif ( ! empty( $person['name'] ) || ! empty( $person['literal'] ) ) {
            $names[] = gep_demo_doi_text( ! empty( $person['name'] ) ? $person['name'] : $person['literal'] );
        }
    }
    if ( ! $names ) {
        return '';
    }
    if ( count( $names ) > $max ) {
        return implode( ', ', array_slice( $names, 0, $max ) ) . ' et al.';
    }
    if ( 1 === count( $names ) ) {
        return $names[0];
    }
    $last = array_pop( $names );
    return implode( ', ', $names ) . ' & ' . $last;
}

/** Categoría del editor a partir del tipo Crossref/CSL. */
function gep_demo_doi_category( $type ) {
    $type = strtolower( (string) $type );
    if ( in_array( $type, array( 'journal-article', 'article-journal', 'article' ), true ) ) return 'articulos';
    if ( in_array( $type, array( 'book', 'monograph', 'edited-book', 'reference-book', 'book-set', 'book-series' ), true ) ) return 'libros';
    if ( in_array( $type, array( 'book-chapter', 'chapter', 'book-section', 'book-part', 'reference-entry', 'entry' ), true ) ) return 'capitulos';
    if ( in_array( $type, array( 'proceedings-article', 'paper-conference', 'proceedings', 'proceedings-series' ), true ) ) return 'congresos';
    return 'otros';
}

/** Año de publicación (issued, luego impreso/en línea/creado). */
function gep_demo_doi_year( $data ) {
    foreach ( array( 'issued', 'published-print', 'published-online', 'published', 'created' ) as $key ) {
        if ( ! empty( $data[ $key ]['date-parts'][0][0] ) ) {
            return (string) (int) $data[ $key ]['date-parts'][0][0];
        }
    }
    return '';
}

/** Une partes de una referencia cuidando que no queden puntos dobles. */
function gep_demo_doi_join( $parts ) {
    $out = '';
    foreach ( $parts as $part ) {
        $part = trim( (string) $part );
        if ( '' === $part ) {
            continue;
        }
        $out .= ( '' === $out ? '' : ' ' ) . rtrim( $part, '.' ) . '.';
    }
    return $out;
}

/** Convierte la respuesta de Crossref/CSL en un registro de Publicaciones. */
function gep_demo_doi_to_record( $data, $doi ) {
    $category  = gep_demo_doi_category( isset( $data['type'] ) ? $data['type'] : '' );
    $title     = gep_demo_doi_text( isset( $data['title'] ) ? $data['title'] : '' );
    $container = gep_demo_doi_text( isset( $data['container-title'] ) ? $data['container-title'] : '' );
    $publisher = gep_demo_doi_text( isset( $data['publisher'] ) ? $data['publisher'] : '' );
    $event     = '';
    if ( ! empty( $data['event'] ) ) {
        $event = is_array( $data['event'] ) ? gep_demo_doi_text( isset( $data['event']['name'] ) ? $data['event']['name'] : '' ) : gep_demo_doi_text( $data['event'] );
    }
    $volume  = gep_demo_doi_text( isset( $data['volume'] ) ? $data['volume'] : '' );
    $issue   = gep_demo_doi_text( isset( $data['issue'] ) ? $data['issue'] : '' );
    $pages   = str_replace( array( '–', '—' ), '-', gep_demo_doi_text( isset( $data['page'] ) ? $data['page'] : '' ) );
    $article = gep_demo_doi_text( isset( $data['article-number'] ) ? $data['article-number'] : '' );
    $authors = gep_demo_doi_people( isset( $data['author'] ) ? $data['author'] : array() );
    $editors = gep_demo_doi_people( isset( $data['editor'] ) ? $data['editor'] : array() );
    $found   = ! empty( $data['DOI'] ) ? gep_demo_doi_normalize( $data['DOI'] ) : '';
    $doi     = '' !== $found ? $found : $doi;

    if ( '' === $authors && '' !== $editors && 'libros' === $category ) {
        $authors = $editors . ' (Eds.)';
        $editors = '';
    }

    switch ( $category ) {
        case 'articulos':
            $where = $container;
            if ( '' !== $volume ) {
                $where .= ' ' . $volume . ( '' !== $issue ? '(' . $issue . ')' : '' );
            }
            if ( '' !== $pages ) {
                $where .= ( '' !== $volume ? ':' : ', ' ) . $pages;
            } elseif ( '' !== $article ) {
                $where .= ( '' !== $volume ? ':' : ', ' ) . $article;
            }
            $text = gep_demo_doi_join( array( $authors, $title, trim( $where ) ) );
            break;
        case 'capitulos':
            $book = trim( ( '' !== $editors ? $editors . ' (Eds.) ' : '' ) . $container );
            $tail = $publisher . ( '' !== $pages ? ( '' !== $publisher ? ', ' : '' ) . 'pp. ' . $pages : '' );
            $text = gep_demo_doi_join( array( $authors, $title, '' !== $book ? 'En: ' . $book : '', $tail ) );
            break;
        case 'congresos':
            $where = '' !== $container ? $container : $event;
            $tail  = '' !== $pages ? 'pp. ' . $pages : '';
            $text  = gep_demo_doi_join( array( $authors, $title, '' !== $where ? 'En: ' . $where : '', $tail ) );
            break;
        case 'libros':
            $text = gep_demo_doi_join( array( $authors, $title, $publisher ) );
            break;
        default:
            $text = gep_demo_doi_join( array( $authors, $title, '' !== $container ? $container : $publisher ) );
    }

    $labels = gep_demo_publication_category_options();

    return array(
        'category' => $category,
        'year'     => gep_demo_doi_year( $data ),
        'title'    => '',
        'text'     => $text,
        'url'      => 'https://doi.org/' . $doi,
        'button'   => 'Ver DOI ↗',
        'doi'      => $doi,
        'preview'  => array(
            'title'          => $title,
            'authors'        => $authors,
            'container'      => '' !== $container ? $container : ( '' !== $event ? $event : $publisher ),
            'category_label' => isset( $labels[ $category ] ) ? $labels[ $category ] : $category,
        ),
    );
}

/** AJAX: busca un DOI y devuelve el registro listo para la lista. */
function gep_demo_ajax_doi_lookup() {
    check_ajax_referer( 'gep_doi_lookup', 'nonce' );
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_send_json_error( array( 'message' => 'No tienes permisos para importar publicaciones.' ), 403 );
    }
    $raw = isset( $_POST['doi'] ) ? sanitize_text_field( wp_unslash( $_POST['doi'] ) ) : '';
    $doi = gep_demo_doi_normalize( $raw );
    if ( '' === $doi ) {
        wp_send_json_error( array( 'message' => 'No parece un DOI. Debe empezar con 10. (por ejemplo 10.4067/S0717-92002014000300002).' ) );
    }
    $data = gep_demo_doi_fetch( $doi );
    if ( is_wp_error( $data ) ) {
        if ( function_exists( 'gep_registro' ) && 'gep_doi_not_found' !== $data->get_error_code() ) {
            gep_registro( 'doi', 'Búsqueda de DOI falló: ' . $data->get_error_message(), $doi );
        }
        wp_send_json_error( array( 'message' => $data->get_error_message(), 'doi' => $doi ) );
    }
    $record = gep_demo_doi_to_record( $data, $doi );
    if ( '' === $record['text'] ) {
        wp_send_json_error( array( 'message' => 'El DOI existe, pero no trae datos suficientes para armar la referencia.', 'doi' => $doi ) );
    }
    wp_send_json_success( $record );
}
add_action( 'wp_ajax_gep_doi_lookup', 'gep_demo_ajax_doi_lookup' );

/** Panel "Importar por DOI" (oculto hasta pulsar el botón). */
function gep_demo_render_doi_panel() {
    ?>
    <div class="gep-doi-panel is-hidden" id="gep-doi-panel" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'gep_doi_lookup' ) ); ?>">
        <div class="gep-publication-single-head"><div><span>Importar por DOI</span><strong>Agrega publicaciones sin escribir la referencia</strong></div><button type="button" class="button-link gep-doi-close" aria-label="Cerrar importación"><span class="dashicons dashicons-no-alt"></span></button></div>
        <label for="gep-doi-input">DOI o enlaces doi.org</label>
        <textarea id="gep-doi-input" rows="3" placeholder="10.4067/S0717-92002014000300002&#10;https://doi.org/10.3390/rs12010045"></textarea>
        <p class="gep-muted">Uno por línea, hasta 25 a la vez. Se buscan en Crossref y DataCite; revisa cada ficha antes de agregarla.</p>
        <div class="gep-doi-actions"><button type="button" class="button button-primary gep-doi-search"><span class="dashicons dashicons-search"></span> Buscar</button><span class="gep-doi-progress" id="gep-doi-progress" aria-live="polite"></span></div>
        <div class="gep-doi-results" id="gep-doi-results"></div>
        <div class="gep-doi-footer is-hidden" id="gep-doi-footer"><span id="gep-doi-selected-count"></span><button type="button" class="button button-primary gep-doi-add-selected"><span class="dashicons dashicons-plus-alt2"></span> Agregar a la lista</button></div>
    </div>
    <?php
}

/** CSS/JS del importador, solo en el editor de Contenido GEP. */
function gep_demo_doi_assets() {
    $screen = get_current_screen();
    if ( ! $screen || 'toplevel_page_gep-content-editor' !== $screen->id ) {
        return;
    }
    $base = plugin_dir_url( dirname( __FILE__ ) );
    wp_enqueue_style( 'gep-doi-import', $base . 'assets/doi-import.css', array( 'gep-editor-theme' ), gep_demo_asset_version( 'assets/doi-import.css' ) );
    wp_enqueue_script( 'gep-doi-import', $base . 'assets/doi-import.js', array( 'jquery', 'gep-demo-admin' ), gep_demo_asset_version( 'assets/doi-import.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'gep_demo_doi_assets', 20 );

/** Tutorial en Ayuda. */
function gep_demo_doi_help_tutorial( $tutorials ) {
    $tutorials[] = array(
        'id'    => 'doi',
        'title' => 'Importar publicaciones por DOI',
        'keys'  => 'doi importar publicacion articulo referencia crossref datacite',
        'steps' => array(
            array( 'tabs', 'Abre Publicaciones y el apartado Registros bibliográficos.' ),
            array( 'add', 'Pulsa Importar por DOI y pega uno o varios DOI, uno por línea.' ),
            array( 'list', 'Revisa cada ficha. Las que ya están en tu lista vienen sin marcar.' ),
            array( 'save', 'Pulsa Agregar a la lista y luego Guardar cambios.' ),
        ),
    );
    return $tutorials;
}
add_filter( 'gep_help_tutorials', 'gep_demo_doi_help_tutorial' );
