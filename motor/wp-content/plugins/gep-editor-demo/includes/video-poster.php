<?php
/**
 * v9.23.0 · Imagen de respaldo automática para los videos de cabecera.
 *
 * El sitio público muestra NOMBRE-respaldo.jpg mientras el video NOMBRE.mp4
 * carga (ver includes/hero-media.php del sitio). Sin esa imagen se ve un
 * cuadro verde la primera vez que alguien entra.
 *
 * Desde esta versión nadie tiene que preparar esa imagen: al abrir el editor,
 * el navegador toma el primer cuadro de cada video de cabecera y lo sube a la
 * Mediateca con el nombre correcto, en la misma carpeta del video.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Datos de un video de la Mediateca a partir de su URL (sin importar el dominio).
 * Devuelve array( dir, subdir, name, path ) o null si no es un video subido.
 */
function gep_poster_video_info( $url ) {
    $url  = trim( (string) $url );
    $path = (string) wp_parse_url( $url, PHP_URL_PATH );
    if ( '' === $path || ! preg_match( '/\.(mp4|m4v|mov|webm)$/i', $path ) ) {
        return null;
    }
    $up      = wp_upload_dir( null, false );
    $baseurl = (string) wp_parse_url( $up['baseurl'], PHP_URL_PATH );
    if ( '' !== $baseurl && 0 === strpos( $path, trailingslashit( $baseurl ) ) ) {
        $rel = substr( $path, strlen( trailingslashit( $baseurl ) ) );
    } elseif ( preg_match( '#/uploads/(.+)$#', $path, $m ) ) {
        // Guardado con otra ruta o dominio (por ejemplo antes de un cambio de servidor).
        $rel = $m[1];
    } else {
        return null;
    }
    $rel  = rawurldecode( $rel );
    if ( false !== strpos( $rel, '..' ) ) {
        return null;
    }
    $file = trailingslashit( $up['basedir'] ) . $rel;
    if ( ! is_file( $file ) ) {
        return null;
    }
    $subdir = trim( dirname( $rel ), '/.' );
    return array(
        'dir'    => dirname( $file ),
        'subdir' => $subdir,
        'name'   => pathinfo( $rel, PATHINFO_FILENAME ),
        'path'   => trailingslashit( $baseurl ) . str_replace( '%2F', '/', rawurlencode( $rel ) ),
        'file'   => $file,
    );
}

/**
 * ¿Ya existe NOMBRE-respaldo.* para este video? Mismo criterio que el sitio.
 */
function gep_poster_exists( $info ) {
    return gep_poster_sibling_exists( $info, 'respaldo', array( 'jpg', 'jpeg', 'webp', 'png' ) );
}

/**
 * v9.26.0 · ¿Existe NOMBRE-<sufijo>.<ext> junto al video o en la Mediateca?
 * Lo usan la imagen de respaldo y la versión para teléfono (NOMBRE-movil.mp4).
 */
function gep_poster_sibling_exists( $info, $suffix, $exts ) {
    $names = array( $info['name'] );
    $base  = preg_replace( '/-(pc|web|hd|liviano|1080p?|720p?)$/i', '', $info['name'] );
    if ( '' !== $base && $base !== $info['name'] ) {
        $names[] = $base;
    }
    global $wpdb;
    foreach ( $names as $n ) {
        foreach ( $exts as $ext ) {
            $f = $n . '-' . $suffix . '.' . $ext;
            if ( is_file( trailingslashit( $info['dir'] ) . $f ) ) {
                return true;
            }
            $found = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND (meta_value=%s OR meta_value LIKE %s) LIMIT 1", $f, '%/' . $wpdb->esc_like( $f ) ) );
            if ( $found ) {
                return true;
            }
        }
    }
    return false;
}

function gep_poster_ajax_status() {
    check_ajax_referer( 'gep_poster', 'nonce' );
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
    }
    $urls = isset( $_POST['urls'] ) && is_array( $_POST['urls'] ) ? array_map( 'esc_url_raw', wp_unslash( $_POST['urls'] ) ) : array();
    $out    = array();
    $states = array();
    foreach ( array_slice( array_unique( $urls ), 0, 12 ) as $url ) {
        $info = gep_poster_video_info( $url );
        // v9.26.0: estado de cada video para el aviso bajo el selector.
        if ( ! $info ) {
            $states[] = array( 'url' => $url, 'poster' => 'external', 'mobile' => false );
            continue;
        }
        $has_poster = gep_poster_exists( $info );
        $states[]   = array( 'url' => $url, 'poster' => $has_poster ? 'ok' : 'missing', 'mobile' => gep_poster_sibling_exists( $info, 'movil', array( 'mp4', 'webm' ) ) );
        if ( $has_poster ) {
            continue;
        }
        // Se devuelve la ruta (mismo dominio que el editor) para que el
        // navegador pueda leer el cuadro sin bloqueos de seguridad.
        $out[] = array( 'url' => $url, 'path' => $info['path'] );
    }
    wp_send_json_success( array( 'missing' => $out, 'states' => $states ) );
}
add_action( 'wp_ajax_gep_poster_status', 'gep_poster_ajax_status' );

function gep_poster_ajax_upload() {
    check_ajax_referer( 'gep_poster', 'nonce' );
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
    }
    $url  = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
    $info = gep_poster_video_info( $url );
    if ( ! $info ) {
        wp_send_json_error( array( 'message' => 'El video no está en la Mediateca.' ) );
    }
    if ( gep_poster_exists( $info ) ) {
        wp_send_json_success( array( 'created' => false ) );
    }
    if ( empty( $_FILES['poster']['tmp_name'] ) || ! is_uploaded_file( $_FILES['poster']['tmp_name'] ) ) {
        wp_send_json_error( array( 'message' => 'No llegó la imagen.' ) );
    }
    $tmp  = $_FILES['poster']['tmp_name'];
    $size = @getimagesize( $tmp );
    if ( ! $size || IMAGETYPE_JPEG !== $size[2] || filesize( $tmp ) > 4 * MB_IN_BYTES ) {
        wp_send_json_error( array( 'message' => 'La imagen no es válida.' ) );
    }

    $filename = wp_unique_filename( $info['dir'], sanitize_file_name( $info['name'] . '-respaldo.jpg' ) );
    $dest     = trailingslashit( $info['dir'] ) . $filename;
    if ( ! @move_uploaded_file( $tmp, $dest ) ) {
        if ( function_exists( 'gep_registro' ) ) {
            gep_registro( 'imagen', 'No se pudo escribir la imagen de respaldo del video en la carpeta de subidas (revisar permisos de uploads).', $info['name'] );
        }
        wp_send_json_error( array( 'message' => 'No se pudo guardar la imagen en el servidor.' ) );
    }
    @chmod( $dest, 0644 );

    $up       = wp_upload_dir( null, false );
    $relative = ( '' !== $info['subdir'] ? $info['subdir'] . '/' : '' ) . $filename;
    $attach   = wp_insert_attachment(
        array(
            'post_mime_type' => 'image/jpeg',
            'post_title'     => $info['name'] . ' (imagen de respaldo)',
            'post_content'   => '',
            'post_status'    => 'inherit',
            'guid'           => trailingslashit( $up['baseurl'] ) . $relative,
        ),
        $dest
    );
    if ( is_wp_error( $attach ) || ! $attach ) {
        if ( function_exists( 'gep_registro' ) ) {
            gep_registro( 'imagen', 'La imagen de respaldo del video se guardó pero no se pudo registrar en la Mediateca.', $info['name'] );
        }
        wp_send_json_error( array( 'message' => 'No se pudo registrar la imagen.' ) );
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata( $attach, wp_generate_attachment_metadata( $attach, $dest ) );
    update_post_meta( $attach, '_wp_attachment_image_alt', '' );

    // Misma carpeta de GEP Media Organizer que el video, si la tiene.
    if ( taxonomy_exists( 'gep_media_folder' ) ) {
        $video_id = attachment_url_to_postid( trailingslashit( $up['baseurl'] ) . ltrim( substr( $info['file'], strlen( trailingslashit( $up['basedir'] ) ) ), '/' ) );
        if ( $video_id ) {
            $terms = wp_get_object_terms( $video_id, 'gep_media_folder', array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $terms ) && $terms ) {
                wp_set_object_terms( $attach, $terms, 'gep_media_folder' );
            }
        }
    }

    wp_send_json_success( array( 'created' => true, 'url' => wp_get_attachment_url( $attach ) ) );
}
add_action( 'wp_ajax_gep_poster_upload', 'gep_poster_ajax_upload' );

function gep_poster_enqueue( $hook ) {
    if ( 'toplevel_page_gep-content-editor' !== $hook || ! current_user_can( 'upload_files' ) ) {
        return;
    }
    $base = plugin_dir_url( GEP_EDITOR_PLUGIN_FILE );
    wp_enqueue_script( 'gep-video-poster', $base . 'assets/video-poster.js', array( 'jquery' ), gep_demo_asset_version( 'assets/video-poster.js' ), true );
    wp_add_inline_script(
        'gep-video-poster',
        'window.gepPosterData = ' . wp_json_encode(
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'gep_poster' ),
            )
        ) . ';',
        'before'
    );
}
add_action( 'admin_enqueue_scripts', 'gep_poster_enqueue', 40 );
