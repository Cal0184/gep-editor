<?php
/**
 * GEP Editor · v9.24.0 · Comodidad del editor.
 * v9.24.1: la barra y la vista previa fijas son solo CSS con posiciones
 * constantes (sin JavaScript que mida al desplazarse). Para dejarlas
 * siempre en su lugar normal: define( 'GEP_EDITOR_DISABLE_STICKY', true );
 * en wp-config.php, o el filtro 'gep_editor_disable_sticky'.
 *
 * Capa común para todas las pestañas del editor (no cambia datos guardados):
 * - Barra "Estado de la página" fija al desplazarse (compacta cuando se fija).
 * - Vista previa que acompaña el desplazamiento, por debajo de la barra.
 * - Desplazamiento suave hasta lo recién creado.
 * - Zonas de arrastre más grandes, indicador de dónde se soltará y
 *   movimiento con las flechas del teclado.
 *
 * El modo enfoque de "Editar sección" y la vista "General" por defecto viven
 * en assets/sections.js porque dependen del gestor de apartados.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function gep_ux_enqueue() {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'toplevel_page_gep-content-editor' !== $screen->id ) {
        return;
    }
    $base = plugin_dir_url( GEP_EDITOR_PLUGIN_FILE );
    // Después de editor.css y dark.css para que sus reglas manden.
    wp_enqueue_style( 'gep-editor-ux', $base . 'assets/ux.css', array( 'gep-editor-theme' ), gep_demo_asset_version( 'assets/ux.css' ) );
    wp_enqueue_script( 'gep-editor-ux', $base . 'assets/ux.js', array( 'jquery', 'jquery-ui-sortable' ), gep_demo_asset_version( 'assets/ux.js' ), true );
    // v9.25.0: encuadre de la foto de «Mensaje de paisaje» (solo Inicio lo usa).
    wp_enqueue_script( 'gep-editor-landscape-frame', $base . 'assets/landscape-frame.js', array( 'jquery' ), gep_demo_asset_version( 'assets/landscape-frame.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'gep_ux_enqueue', 40 );

/** v9.24.1 · Alternativa estática: sin barra ni vista previa fijas. */
function gep_ux_admin_body_class( $classes ) {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'toplevel_page_gep-content-editor' !== $screen->id ) {
        return $classes;
    }
    $off = defined( 'GEP_EDITOR_DISABLE_STICKY' ) && GEP_EDITOR_DISABLE_STICKY;
    if ( apply_filters( 'gep_editor_disable_sticky', $off ) ) {
        $classes .= ' gep-no-sticky';
    }
    return $classes;
}
add_filter( 'admin_body_class', 'gep_ux_admin_body_class' );
