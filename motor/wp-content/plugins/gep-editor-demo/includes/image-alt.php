<?php
/**
 * GEP Editor v9.21.0 · Descripción de imágenes (texto alternativo).
 *
 * Contenido GEP > Descripción de imágenes lista las fotos de la Biblioteca de
 * medios que usa el sitio, dice en qué páginas aparecen y permite escribir su
 * descripción. Se guarda en el campo "Texto alternativo" de la Biblioteca de
 * medios (_wp_attachment_image_alt), así la misma descripción sirve en todas
 * las páginas. El sitio la lee con gep_demo_alt_for_url().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** ID de la imagen en la Biblioteca de medios a partir de su URL (también tamaños -300x200 y -scaled). */
function gep_demo_alt_attachment_id( $url ) {
    static $cache = array();
    $url = trim( (string) $url );
    if ( '' === $url ) {
        return 0;
    }
    if ( isset( $cache[ $url ] ) ) {
        return $cache[ $url ];
    }
    $clean = preg_replace( '/[?#].*$/', '', $url );
    $id    = attachment_url_to_postid( $clean );
    if ( ! $id ) {
        $base = preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $clean );
        if ( $base !== $clean ) {
            $id = attachment_url_to_postid( $base );
        }
        if ( ! $id ) {
            $scaled = preg_replace( '/(\.[a-z0-9]+)$/i', '-scaled$1', $base );
            $id     = attachment_url_to_postid( $scaled );
        }
    }
    if ( ! $id && preg_match( '#/uploads/(.+)$#', (string) wp_parse_url( $clean, PHP_URL_PATH ), $m ) ) {
        // v9.23.0: URL guardada con otro dominio o ruta (por ejemplo antes de un
        // cambio de servidor): se busca por el archivo dentro de la Mediateca.
        global $wpdb;
        $file = rawurldecode( $m[1] );
        $try  = array_unique( array( $file, preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $file ) ) );
        foreach ( $try as $f ) {
            $scaled = preg_replace( '/(\.[a-z0-9]+)$/i', '-scaled$1', $f );
            $id     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value IN (%s,%s) LIMIT 1", $f, $scaled ) );
            if ( $id ) {
                break;
            }
        }
    }
    $cache[ $url ] = $id && wp_attachment_is_image( $id ) ? (int) $id : 0;
    return $cache[ $url ];
}

/** Descripción guardada en la Biblioteca de medios para esa imagen, o ''. */
function gep_demo_alt_for_url( $url ) {
    $id = gep_demo_alt_attachment_id( $url );
    if ( ! $id ) {
        return '';
    }
    return trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
}

/** ¿Es una URL de imagen? */
function gep_demo_alt_is_image_url( $value ) {
    return is_string( $value ) && preg_match( '#\.(jpe?g|png|webp|gif|avif)(\?.*)?$#i', trim( $value ) ) && false !== strpos( $value, '/' );
}

/** Campos que son fondos (cabeceras, paisaje): no necesitan descripción. */
function gep_demo_alt_is_background_key( $key ) {
    return (bool) preg_match( '/hero_image|hero_video|landscape|background|poster|_bg$/i', (string) $key );
}

/** Recorre los valores de una página y anota cada imagen visible. */
function gep_demo_alt_walk( $data, $page_label, &$found, $parent_key = '' ) {
    if ( ! is_array( $data ) ) {
        return;
    }
    foreach ( $data as $key => $value ) {
        $field = is_int( $key ) ? $parent_key : (string) $key;
        if ( is_array( $value ) ) {
            gep_demo_alt_walk( $value, $page_label, $found, $field );
            continue;
        }
        if ( ! gep_demo_alt_is_image_url( $value ) || gep_demo_alt_is_background_key( $field ) || gep_demo_alt_is_background_key( $parent_key ) ) {
            continue;
        }
        $id = gep_demo_alt_attachment_id( $value );
        if ( ! $id ) {
            continue;
        }
        if ( ! isset( $found[ $id ] ) ) {
            $found[ $id ] = array( 'pages' => array(), 'own' => array() );
        }
        $found[ $id ]['pages'][ $page_label ] = true;
        $own_key = isset( $data['image_alt'] ) ? 'image_alt' : ( isset( $data['alt'] ) ? 'alt' : '' );
        if ( '' !== $own_key && is_string( $data[ $own_key ] ) ) {
            $own = trim( $data[ $own_key ] );
            if ( '' !== $own && ! preg_match( '/^Imagen territorial (referencial|asociada)|imagen referencial/iu', $own ) ) {
                $found[ $id ]['own'][ $own ] = $page_label;
            }
        }
    }
}

/** Valores que ve cada página pública (lo guardado o, si falta, el contenido de partida). */
function gep_demo_alt_page_values( $key, $post_id ) {
    $values = array();
    switch ( $key ) {
        case 'inicio':
            $values = function_exists( 'gep_demo_home_values' ) ? gep_demo_home_values( $post_id ) : array();
            break;
        case 'quienes-somos':
            $values = array(
                function_exists( 'gep_demo_default_values' ) ? gep_demo_default_values( $post_id ) : array(),
                function_exists( 'gep_demo_get_sections' ) ? gep_demo_get_sections( $post_id ) : array(),
                function_exists( 'gep_demo_get_team_cards' ) ? gep_demo_get_team_cards( $post_id ) : array(),
            );
            break;
        case 'docencia':
            $values = function_exists( 'gep_demo_docencia_values' ) ? gep_demo_docencia_values( $post_id ) : array();
            break;
        case 'proyectos':
            $values = function_exists( 'gep_demo_projects_values' ) ? gep_demo_projects_values( $post_id ) : array();
            break;
        case 'servicios':
            $values = function_exists( 'gep_demo_services_values' ) ? gep_demo_services_values( $post_id ) : array();
            break;
        case 'publicaciones':
            $values = function_exists( 'gep_demo_publications_values' ) ? gep_demo_publications_values( $post_id ) : array();
            break;
        case 'comunicaciones':
            $values = function_exists( 'gep_demo_hub_values' ) ? gep_demo_hub_values( $post_id, 'communications' ) : array();
            break;
        case 'noticias':
            $values = function_exists( 'gep_demo_hub_values' ) ? gep_demo_hub_values( $post_id, 'news' ) : array();
            break;
        case 'contacto':
            $values = function_exists( 'gep_demo_contact_values' ) ? gep_demo_contact_values( $post_id ) : array();
            break;
    }
    return is_array( $values ) ? $values : array();
}

/** Todas las imágenes de la Biblioteca que aparecen en el sitio, con su descripción. */
function gep_demo_alt_collect() {
    $found = array();
    foreach ( gep_demo_get_page_navigation() as $key => $item ) {
        if ( empty( $item['page'] ) ) {
            continue;
        }
        gep_demo_alt_walk( gep_demo_alt_page_values( $key, (int) $item['page']->ID ), $item['label'], $found );
    }
    // También el resto de la Biblioteca, para dejar descritas las fotos antes de usarlas.
    $library = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => 500, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC' ) );
    foreach ( $library as $id ) {
        if ( ! isset( $found[ $id ] ) ) {
            $found[ $id ] = array( 'pages' => array(), 'own' => array() );
        }
    }
    $images = array();
    foreach ( $found as $id => $info ) {
        $thumb    = wp_get_attachment_image_src( $id, 'medium' );
        $full     = wp_get_attachment_url( $id );
        $images[] = array(
            'id'       => (int) $id,
            'thumb'    => $thumb ? $thumb[0] : $full,
            'url'      => $full,
            'file'     => wp_basename( (string) get_attached_file( $id ) ),
            'alt'      => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
            'pages'    => array_keys( $info['pages'] ),
            'own'      => $info['own'],
            'edit_url' => get_edit_post_link( $id, 'raw' ),
        );
    }
    // Primero las que están en el sitio, luego las que faltan, luego por nombre de archivo.
    usort( $images, function ( $a, $b ) {
        if ( empty( $a['pages'] ) !== empty( $b['pages'] ) ) {
            return empty( $a['pages'] ) ? 1 : -1;
        }
        if ( ( '' === $a['alt'] ) !== ( '' === $b['alt'] ) ) {
            return '' === $a['alt'] ? -1 : 1;
        }
        return strcmp( $a['file'], $b['file'] );
    } );
    return $images;
}

function gep_demo_alt_register_menu() {
    add_submenu_page(
        'gep-content-editor',
        'Descripción de imágenes',
        'Descripción de imágenes',
        'edit_pages',
        'gep-content-images',
        'gep_demo_render_alt_page'
    );
}
add_action( 'admin_menu', 'gep_demo_alt_register_menu', 11 );

/** Deja "Descripción de imágenes" antes de Historial y Ayuda en el submenú. */
function gep_demo_alt_menu_order() {
    global $submenu;
    if ( empty( $submenu['gep-content-editor'] ) ) {
        return;
    }
    $items = $submenu['gep-content-editor'];
    $mine  = null;
    foreach ( $items as $i => $row ) {
        if ( isset( $row[2] ) && 'gep-content-images' === $row[2] ) {
            $mine = $row;
            unset( $items[ $i ] );
        }
    }
    if ( ! $mine ) {
        return;
    }
    $items = array_values( $items );
    array_splice( $items, 1, 0, array( $mine ) );
    $submenu['gep-content-editor'] = $items; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}
add_action( 'admin_menu', 'gep_demo_alt_menu_order', 998 );

function gep_demo_alt_admin_assets() {
    $screen = get_current_screen();
    if ( ! $screen || 'contenido-gep_page_gep-content-images' !== $screen->id ) {
        return;
    }
    $base = plugin_dir_url( dirname( __FILE__ ) );
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style( 'gep-demo-history', $base . 'assets/history.css', array( 'dashicons' ), gep_demo_asset_version( 'assets/history.css' ) );
    wp_enqueue_style( 'gep-image-alt', $base . 'assets/image-alt.css', array( 'gep-demo-history' ), gep_demo_asset_version( 'assets/image-alt.css' ) );
    wp_enqueue_script( 'gep-image-alt', $base . 'assets/image-alt.js', array( 'jquery' ), gep_demo_asset_version( 'assets/image-alt.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'gep_demo_alt_admin_assets' );

function gep_demo_render_alt_page() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'No tienes permisos para ver esta pantalla.' );
    }
    $images  = gep_demo_alt_collect();
    $used    = array_filter( $images, function ( $img ) { return ! empty( $img['pages'] ); } );
    $missing = count( array_filter( $used, function ( $img ) { return '' === $img['alt']; } ) );
    $done    = count( $used ) - $missing;
    $unused  = count( $images ) - count( $used );
    $pages   = array();
    foreach ( $images as $img ) {
        foreach ( $img['pages'] as $label ) {
            $pages[ $label ] = isset( $pages[ $label ] ) ? $pages[ $label ] + 1 : 1;
        }
    }
    $editor_url = admin_url( 'admin.php?page=gep-content-editor' );
    $help_url   = admin_url( 'admin.php?page=gep-content-help' );
    ?>
    <div class="wrap gep-history-wrap gep-hx gep-alt" id="gep-alt-app" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'gep_alt_save' ) ); ?>">
        <header class="gep-hx-heading">
            <nav class="gep-hx-breadcrumb"><span class="dashicons dashicons-admin-home"></span> Contenido GEP <i>›</i> <strong>Descripción de imágenes</strong></nav>
            <div class="gep-hx-title-row">
                <div><h1>Descripción de imágenes</h1><p>Escribe en una frase qué muestra cada foto. La leen los lectores de pantalla de personas ciegas y la usa Google para entender el sitio. Se guarda una vez y vale en todas las páginas donde aparece la foto.</p></div>
                <div class="gep-hx-title-actions">
                    <a class="button" href="<?php echo esc_url( $help_url ); ?>"><span class="dashicons dashicons-editor-help"></span> Ayuda</a>
                    <a class="button button-primary" href="<?php echo esc_url( $editor_url ); ?>"><span class="dashicons dashicons-edit"></span> Ir al editor</a>
                </div>
            </div>
        </header>

        <section class="gep-hx-card gep-hx-summary">
            <div class="gep-hx-summary-icon"><span class="dashicons dashicons-format-image"></span></div>
            <div class="gep-hx-summary-copy">
                <strong id="gep-alt-summary-title"><?php echo $missing ? esc_html( sprintf( '%d de %d fotos del sitio sin descripción', $missing, count( $used ) ) ) : esc_html( 'Todas las fotos del sitio tienen descripción' ); ?></strong>
                <small>Las fotos de fondo de las cabeceras no aparecen aquí porque son decorativas. Más abajo están también las fotos de la Biblioteca que todavía no usas, por si quieres dejarlas descritas.</small>
            </div>
            <div class="gep-hx-summary-stats">
                <span><b id="gep-alt-count-done"><?php echo esc_html( $done ); ?></b> con descripción</span>
                <span><b id="gep-alt-count-missing"><?php echo esc_html( $missing ); ?></b> pendientes</span>
            </div>
        </section>

        <section class="gep-hx-card gep-hx-tools">
            <div class="gep-hx-search gep-alt-search">
                <label class="gep-hx-search-box"><span class="dashicons dashicons-search"></span><input type="search" id="gep-alt-search" placeholder="Buscar por nombre de archivo, descripción o página…" aria-label="Buscar imágenes"></label>
            </div>
            <nav class="gep-hx-chips" aria-label="Filtrar imágenes">
                <button type="button" class="gep-hx-chip is-active" data-alt-filter="used">En el sitio <b><?php echo esc_html( count( $used ) ); ?></b></button>
                <button type="button" class="gep-hx-chip" data-alt-filter="missing">Sin descripción <b data-alt-chip-missing><?php echo esc_html( $missing ); ?></b></button>
                <button type="button" class="gep-hx-chip" data-alt-filter="done">Con descripción <b data-alt-chip-done><?php echo esc_html( $done ); ?></b></button>
                <button type="button" class="gep-hx-chip" data-alt-filter="unused">Sin usar todavía <b><?php echo esc_html( $unused ); ?></b></button>
                <?php foreach ( $pages as $label => $n ) : ?>
                    <button type="button" class="gep-hx-chip" data-alt-filter="page" data-alt-page="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?> <b><?php echo esc_html( $n ); ?></b></button>
                <?php endforeach; ?>
            </nav>
        </section>

        <section class="gep-hx-log">
            <?php if ( ! $images ) : ?>
                <div class="gep-hx-card gep-hx-empty-state"><span class="dashicons dashicons-format-image"></span><strong>Todavía no hay fotos de la Biblioteca de medios en el sitio.</strong><p>Cuando elijas imágenes en el editor aparecerán aquí.</p></div>
            <?php else : ?>
                <div class="gep-hx-rows gep-alt-rows" id="gep-alt-rows">
                    <?php foreach ( $images as $img ) : ?>
                        <article class="gep-hx-row gep-alt-row<?php echo empty( $img['pages'] ) ? ' is-unused' : ''; ?>"<?php echo empty( $img['pages'] ) ? ' hidden' : ''; ?> data-alt-id="<?php echo esc_attr( $img['id'] ); ?>" data-alt-pages="<?php echo esc_attr( wp_json_encode( $img['pages'] ) ); ?>" data-alt-search="<?php echo esc_attr( strtolower( $img['file'] . ' ' . $img['alt'] . ' ' . implode( ' ', $img['pages'] ) ) ); ?>">
                            <button type="button" class="gep-alt-open" aria-expanded="false">
                                <span class="gep-alt-thumb"><img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" loading="lazy"></span>
                                <span class="gep-hx-row-main">
                                    <span class="gep-hx-row-title gep-alt-text"><?php echo '' !== $img['alt'] ? esc_html( $img['alt'] ) : '<em>Sin descripción</em>'; ?></span>
                                    <span class="gep-hx-row-meta">
                                        <span class="gep-alt-file"><?php echo esc_html( $img['file'] ); ?></span>
                                        <?php foreach ( $img['pages'] as $label ) : ?><span class="gep-hx-pill is-protected"><?php echo esc_html( $label ); ?></span><?php endforeach; ?>
                                        <?php if ( empty( $img['pages'] ) ) : ?><span class="gep-hx-pill">Sin usar todavía</span><?php endif; ?>
                                        <span class="gep-hx-pill gep-alt-state <?php echo '' !== $img['alt'] ? 'is-current' : 'is-changed'; ?>"><?php echo '' !== $img['alt'] ? 'Con descripción' : 'Pendiente'; ?></span>
                                    </span>
                                </span>
                                <span class="dashicons dashicons-arrow-right-alt2 gep-alt-arrow" aria-hidden="true"></span>
                            </button>
                            <div class="gep-alt-editor" hidden>
                                <img class="gep-alt-preview" src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" loading="lazy">
                                <div class="gep-alt-form">
                                    <label for="gep-alt-input-<?php echo esc_attr( $img['id'] ); ?>">¿Qué se ve en la foto?</label>
                                    <textarea id="gep-alt-input-<?php echo esc_attr( $img['id'] ); ?>" rows="2" maxlength="250" placeholder="Ej.: Equipo del GEP midiendo vegetación en un bosque nativo de la Región del Maule"><?php echo esc_textarea( $img['alt'] ); ?></textarea>
                                    <p class="gep-alt-tip">Una frase corta y concreta. No hace falta empezar con "Imagen de" ni repetir el título de la página.</p>
                                    <?php if ( $img['own'] ) : ?>
                                        <p class="gep-alt-own"><span class="dashicons dashicons-info-outline"></span> En <?php echo esc_html( implode( ', ', array_unique( array_values( $img['own'] ) ) ) ); ?> esta foto ya tiene su propio texto en la ficha («<?php echo esc_html( implode( '», «', array_keys( $img['own'] ) ) ); ?>»), y ahí se usa ese.</p>
                                    <?php endif; ?>
                                    <div class="gep-alt-actions">
                                        <button type="button" class="button button-primary gep-alt-save">Guardar descripción</button>
                                        <button type="button" class="button gep-alt-cancel">Cancelar</button>
                                        <span class="gep-alt-status" aria-live="polite"></span>
                                        <a class="gep-alt-media-link" href="<?php echo esc_url( $img['url'] ); ?>" target="_blank" rel="noopener">Ver imagen completa ↗</a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="gep-hx-card gep-hx-empty-state" id="gep-alt-empty" hidden><span class="dashicons dashicons-search"></span><strong>No hay imágenes con este filtro.</strong></div>
            <?php endif; ?>
        </section>
    </div>
    <?php
}

/** AJAX: guarda la descripción en la Biblioteca de medios. */
function gep_demo_ajax_alt_save() {
    check_ajax_referer( 'gep_alt_save', 'nonce' );
    if ( ! current_user_can( 'edit_pages' ) || ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error( array( 'message' => 'No tienes permisos para cambiar imágenes.' ), 403 );
    }
    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id || 'attachment' !== get_post_type( $id ) || ! wp_attachment_is_image( $id ) ) {
        wp_send_json_error( array( 'message' => 'No se encontró esa imagen.' ) );
    }
    $alt = isset( $_POST['alt'] ) ? sanitize_text_field( wp_unslash( $_POST['alt'] ) ) : '';
    $alt = function_exists( 'mb_substr' ) ? mb_substr( $alt, 0, 250 ) : substr( $alt, 0, 250 );
    update_post_meta( $id, '_wp_attachment_image_alt', $alt );
    wp_send_json_success( array( 'alt' => $alt ) );
}
add_action( 'wp_ajax_gep_alt_save', 'gep_demo_ajax_alt_save' );

/** Tutorial en Ayuda. */
function gep_demo_alt_help_tutorial( $tutorials ) {
    $tutorials[] = array(
        'id'    => 'descripcion-imagenes',
        'title' => 'Describir las fotos',
        'keys'  => 'texto alternativo alt descripcion foto imagen accesibilidad google lector pantalla',
        'steps' => array(
            array( 'tabs', 'En el menú Contenido GEP abre Descripción de imágenes.' ),
            array( 'list', 'Toca una foto marcada como Pendiente.' ),
            array( 'field', 'Escribe en una frase qué se ve en la foto.' ),
            array( 'save', 'Pulsa Guardar descripción. Vale en todas las páginas.' ),
        ),
    );
    return $tutorials;
}
add_filter( 'gep_help_tutorials', 'gep_demo_alt_help_tutorial' );
