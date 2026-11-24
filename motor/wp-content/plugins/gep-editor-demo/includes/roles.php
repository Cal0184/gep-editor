<?php
/**
 * GEP Editor · Rol "Editor GEP"
 *
 * Rol para las personas que solo editan contenido: pueden usar Contenido GEP
 * (editor, Historial, Ayuda) y subir imágenes a la Mediateca, pero no pueden
 * instalar plugins, cambiar temas, ajustes ni usuarios, ni borrar páginas.
 * El rol no se asigna solo: se elige en Usuarios > Editar > Perfil.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GEP_DEMO_ROLE', 'gep_editor' );
define( 'GEP_DEMO_ROLE_REV', '1' );

function gep_demo_role_capabilities() {
    return array(
        'read'                 => true,
        'upload_files'         => true,
        'edit_pages'           => true,
        'edit_others_pages'    => true,
        'edit_published_pages' => true,
        'publish_pages'        => true,
    );
}

/** Crea o actualiza el rol. Corre también al actualizar el plugin subiendo el ZIP. */
function gep_demo_role_sync() {
    if ( GEP_DEMO_ROLE_REV === get_option( 'gep_demo_role_rev' ) && get_role( GEP_DEMO_ROLE ) ) {
        return;
    }
    remove_role( GEP_DEMO_ROLE );
    add_role( GEP_DEMO_ROLE, 'Editor GEP', gep_demo_role_capabilities() );
    update_option( 'gep_demo_role_rev', GEP_DEMO_ROLE_REV, false );
}
add_action( 'admin_init', 'gep_demo_role_sync' );

function gep_demo_user_is_gep_editor( $user = null ) {
    $user = $user ? $user : wp_get_current_user();
    return $user instanceof WP_User && in_array( GEP_DEMO_ROLE, (array) $user->roles, true ) && ! user_can( $user, 'manage_options' );
}

/** Al entrar, el Editor GEP llega directo al editor y no al escritorio de WordPress. */
function gep_demo_role_login_redirect( $redirect_to, $requested, $user ) {
    if ( gep_demo_user_is_gep_editor( $user ) && ( '' === $requested || admin_url() === $redirect_to || admin_url( 'index.php' ) === $redirect_to ) ) {
        return admin_url( 'admin.php?page=gep-content-editor' );
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'gep_demo_role_login_redirect', 10, 3 );

/** Menú más simple para el Editor GEP: Contenido GEP, Medios y su perfil. */
function gep_demo_role_simplify_menu() {
    if ( ! gep_demo_user_is_gep_editor() ) {
        return;
    }
    remove_menu_page( 'index.php' );
    remove_menu_page( 'edit.php?post_type=page' );
    remove_menu_page( 'edit-comments.php' );
    remove_menu_page( 'tools.php' );
}
add_action( 'admin_menu', 'gep_demo_role_simplify_menu', 999 );
