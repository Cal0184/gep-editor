<?php
/**
 * Modo oscuro del editor GEP.
 *
 * Cada persona elige si ve el editor en claro u oscuro. La preferencia se
 * guarda en su perfil (user meta), así que la ve igual en cualquier navegador.
 * Sólo cambia la administración: la web pública y la vista previa del sitio
 * dentro del editor siguen siendo claras.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const GEP_THEME_META_KEY = 'gep_editor_theme';

/**
 * Pantallas del plugin donde se aplica el modo oscuro.
 */
function gep_theme_is_plugin_screen( $screen = null ) {
    if ( null === $screen ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    }
    if ( ! $screen || empty( $screen->id ) ) {
        return false;
    }

    $ids = array(
        'toplevel_page_gep-content-editor',
        'contenido-gep_page_gep-content-history',
        'contenido-gep_page_gep-content-help',
        'contenido-gep_page_gep-content-images',
    );

    if ( in_array( $screen->id, $ids, true ) ) {
        return true;
    }

    // Otras subpáginas del menú "Contenido GEP" que se añadan más adelante.
    return ( false !== strpos( $screen->id, 'gep-content-' ) );
}

/**
 * Tema elegido por la persona que está usando el editor: 'dark' o 'light'.
 */
function gep_theme_current() {
    $value = get_user_meta( get_current_user_id(), GEP_THEME_META_KEY, true );

    return ( 'dark' === $value ) ? 'dark' : 'light';
}

/**
 * Añade la clase al <body> antes de pintar, para que no haya parpadeo blanco.
 */
function gep_theme_body_class( $classes ) {
    if ( gep_theme_is_plugin_screen() && 'dark' === gep_theme_current() ) {
        $classes .= ' gep-theme-dark';
    }

    return $classes;
}
add_filter( 'admin_body_class', 'gep_theme_body_class' );

/**
 * Hoja de estilo oscura + botón para cambiar de modo.
 */
function gep_theme_enqueue() {
    if ( ! gep_theme_is_plugin_screen() ) {
        return;
    }

    $base = plugin_dir_url( GEP_EDITOR_PLUGIN_FILE );

    wp_enqueue_style(
        'gep-editor-dark',
        $base . 'assets/dark.css',
        array(),
        gep_demo_asset_version( 'assets/dark.css' )
    );

    wp_enqueue_script(
        'gep-editor-theme-toggle',
        $base . 'assets/theme-toggle.js',
        array(),
        gep_demo_asset_version( 'assets/theme-toggle.js' ),
        true
    );

    wp_add_inline_script(
        'gep-editor-theme-toggle',
        'window.gepThemeData = ' . wp_json_encode(
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'gep_theme' ),
                'theme'   => gep_theme_current(),
            )
        ) . ';',
        'before'
    );
}
add_action( 'admin_enqueue_scripts', 'gep_theme_enqueue', 30 );

/**
 * Guarda la preferencia de la persona.
 */
function gep_theme_ajax_save() {
    check_ajax_referer( 'gep_theme', 'nonce' );

    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
    }

    $theme = isset( $_POST['theme'] ) ? sanitize_key( wp_unslash( $_POST['theme'] ) ) : 'light';
    $theme = ( 'dark' === $theme ) ? 'dark' : 'light';

    update_user_meta( get_current_user_id(), GEP_THEME_META_KEY, $theme );

    wp_send_json_success( array( 'theme' => $theme ) );
}
add_action( 'wp_ajax_gep_save_theme', 'gep_theme_ajax_save' );
