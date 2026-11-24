<?php
/**
 * v9.23.0 · Dos personas editando la misma página.
 *
 * WordPress guarda la pestaña completa de una vez, así que si dos personas
 * editan la misma página, el último guardado reemplaza al anterior. Para que
 * eso no pase sin querer:
 *
 * 1. Mientras alguien tiene abierta una página en el editor, quien la abra
 *    después ve un aviso con su nombre ("María también está editando…").
 * 2. Si otra persona guardó la página después de que tú la abriste, al pulsar
 *    Guardar aparece una ventana que lo explica y deja elegir entre recargar
 *    (para ver sus cambios) o guardar igual.
 * 3. Si igual se reemplaza algo, el Historial de cambios conserva las dos
 *    versiones y se puede restaurar.
 *
 * Funciona con el "latido" (Heartbeat) de WordPress: cada 15 segundos el
 * navegador avisa que sigue en la página y pregunta si alguien guardó.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const GEP_LOCK_REV_KEY     = '_gep_save_rev';
const GEP_LOCK_EDITORS_KEY = '_gep_editing_now';
const GEP_LOCK_TIMEOUT     = 60; // segundos sin latido para dar a alguien por ido.

/**
 * Marca un guardado: nueva "revisión", quién y cuándo.
 */
function gep_lock_mark_saved( $post_id ) {
    $post_id = absint( $post_id );
    if ( ! $post_id ) {
        return;
    }
    update_post_meta(
        $post_id,
        GEP_LOCK_REV_KEY,
        array(
            'rev'  => wp_generate_password( 12, false ),
            'user' => get_current_user_id(),
            'time' => time(),
        )
    );
}

/**
 * Revisión actual de una página (texto vacío si nunca se guardó con 9.23).
 */
function gep_lock_get_rev( $post_id ) {
    $data = get_post_meta( absint( $post_id ), GEP_LOCK_REV_KEY, true );
    return is_array( $data ) ? $data : array( 'rev' => '', 'user' => 0, 'time' => 0 );
}

function gep_lock_user_name( $user_id ) {
    $user = get_userdata( (int) $user_id );
    if ( ! $user ) {
        return 'Otra persona';
    }
    return '' !== trim( (string) $user->display_name ) ? $user->display_name : $user->user_login;
}

/**
 * Registra que la persona actual sigue en la página y devuelve a las demás.
 */
function gep_lock_touch( $post_id ) {
    $post_id = absint( $post_id );
    $me      = get_current_user_id();
    $now     = time();
    $list    = get_post_meta( $post_id, GEP_LOCK_EDITORS_KEY, true );
    $list    = is_array( $list ) ? $list : array();
    $others  = array();

    foreach ( $list as $user_id => $seen ) {
        if ( $now - (int) $seen > GEP_LOCK_TIMEOUT ) {
            unset( $list[ $user_id ] );
            continue;
        }
        if ( (int) $user_id !== $me ) {
            $others[] = gep_lock_user_name( $user_id );
        }
    }
    $list[ $me ] = $now;
    update_post_meta( $post_id, GEP_LOCK_EDITORS_KEY, $list );

    return $others;
}

function gep_lock_release( $post_id ) {
    $post_id = absint( $post_id );
    $list    = get_post_meta( $post_id, GEP_LOCK_EDITORS_KEY, true );
    if ( is_array( $list ) && isset( $list[ get_current_user_id() ] ) ) {
        unset( $list[ get_current_user_id() ] );
        update_post_meta( $post_id, GEP_LOCK_EDITORS_KEY, $list );
    }
}

/**
 * Estado que se envía al navegador.
 */
function gep_lock_state( $post_id, $loaded_rev ) {
    $rev   = gep_lock_get_rev( $post_id );
    $state = array(
        'editors' => gep_lock_touch( $post_id ),
        'changed' => false,
    );
    if ( '' !== $rev['rev'] && (string) $loaded_rev !== $rev['rev'] && (int) $rev['user'] !== get_current_user_id() ) {
        $state['changed'] = true;
        $state['by']      = gep_lock_user_name( $rev['user'] );
        $state['at']      = wp_date( 'H:i', (int) $rev['time'] );
    }
    return $state;
}

/**
 * Latido de WordPress: el navegador envía { post_id, rev } y recibe el estado.
 */
function gep_lock_heartbeat( $response, $data ) {
    if ( empty( $data['gep_lock'] ) || ! is_array( $data['gep_lock'] ) ) {
        return $response;
    }
    $post_id = isset( $data['gep_lock']['post_id'] ) ? absint( $data['gep_lock']['post_id'] ) : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        return $response;
    }
    $loaded = isset( $data['gep_lock']['rev'] ) ? sanitize_text_field( (string) $data['gep_lock']['rev'] ) : '';
    $response['gep_lock'] = gep_lock_state( $post_id, $loaded );
    return $response;
}
add_filter( 'heartbeat_received', 'gep_lock_heartbeat', 10, 2 );

/**
 * Consulta inmediata justo antes de guardar (no espera al próximo latido).
 */
function gep_lock_ajax_check() {
    check_ajax_referer( 'gep_lock', 'nonce' );
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
    }
    $loaded = isset( $_POST['rev'] ) ? sanitize_text_field( wp_unslash( $_POST['rev'] ) ) : '';
    // v10.0.0: claves de seguridad recién creadas. Si la pestaña llevaba muchas
    // horas abierta, el formulario se envía con una clave vigente.
    wp_send_json_success( array_merge( gep_lock_state( $post_id, $loaded ), gep_lock_fresh_nonces( $post_id ) ) );
}
add_action( 'wp_ajax_gep_lock_check', 'gep_lock_ajax_check' );

/**
 * v10.0.0 · Guardado seguro. Claves nuevas para el formulario de una página.
 */
function gep_lock_fresh_nonces( $post_id ) {
    return array(
        'lock_nonce' => wp_create_nonce( 'gep_lock' ),
        'save_nonce' => wp_create_nonce( 'gep_demo_save_content_' . absint( $post_id ) ),
    );
}

/**
 * v10.0.0 · Renovar claves tras volver a iniciar sesión (sin perder lo escrito).
 *
 * Al vencer la sesión, las claves que tiene la página dejan de servir. Esta
 * consulta solo responde a una persona con sesión iniciada y permiso sobre la
 * página, y solo devuelve claves nuevas (no cambia nada). Otro sitio no puede
 * leer la respuesta, como ocurre con el «latido» de WordPress.
 */
function gep_lock_ajax_refresh() {
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
    }
    nocache_headers();
    wp_send_json_success( gep_lock_fresh_nonces( $post_id ) );
}
add_action( 'wp_ajax_gep_lock_refresh', 'gep_lock_ajax_refresh' );

/**
 * Al salir de la página, deja de figurar como "editando".
 */
function gep_lock_ajax_leave() {
    check_ajax_referer( 'gep_lock', 'nonce' );
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
        gep_lock_release( $post_id );
    }
    wp_send_json_success();
}
add_action( 'wp_ajax_gep_lock_leave', 'gep_lock_ajax_leave' );

/**
 * Página que se está editando: la del parámetro post_id o la que el editor
 * abre por defecto.
 */
function gep_lock_current_post_id() {
    if ( isset( $_GET['post_id'] ) ) {
        return absint( $_GET['post_id'] );
    }
    return 0; // El script la toma del formulario (input name="post_id").
}

function gep_lock_enqueue( $hook ) {
    if ( 'toplevel_page_gep-content-editor' !== $hook ) {
        return;
    }
    $base = plugin_dir_url( GEP_EDITOR_PLUGIN_FILE );
    wp_enqueue_style( 'gep-edit-lock', $base . 'assets/edit-lock.css', array(), gep_demo_asset_version( 'assets/edit-lock.css' ) );
    wp_enqueue_script( 'gep-edit-lock', $base . 'assets/edit-lock.js', array( 'jquery', 'heartbeat' ), gep_demo_asset_version( 'assets/edit-lock.js' ), true );

    // Revisión de cada página GEP en el momento de abrir el editor.
    $revs = array();
    if ( function_exists( 'gep_demo_get_page_navigation' ) ) {
        foreach ( (array) gep_demo_get_page_navigation() as $item ) {
            if ( ! empty( $item['page']->ID ) ) {
                $info = gep_lock_get_rev( $item['page']->ID );
                $revs[ (int) $item['page']->ID ] = $info['rev'];
            }
        }
    }
    $post_id = gep_lock_current_post_id();
    if ( $post_id && ! isset( $revs[ $post_id ] ) ) {
        $info             = gep_lock_get_rev( $post_id );
        $revs[ $post_id ] = $info['rev'];
    }

    wp_add_inline_script(
        'gep-edit-lock',
        'window.gepLockData = ' . wp_json_encode(
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'gep_lock' ),
                'revs'    => (object) $revs,
                'loginUrl' => wp_login_url(),
            )
        ) . ';',
        'before'
    );
}
add_action( 'admin_enqueue_scripts', 'gep_lock_enqueue', 40 );
