<?php
/**
 * GEP Editor · v9.26.0 · Editor visual de botones (principal / secundario).
 *
 * Un componente común (gep_buttons_render_control) para los botones de
 * Inicio (cabecera y llamado final) y Servicios (llamado final):
 *  - tarjeta con una muestra del botón (principal = relleno, secundario = borde);
 *  - interruptor «Mostrar» para activar o desactivar cada botón sin perder
 *    su texto ni su destino (meta gep_*_off = '1');
 *  - «¿A dónde lleva?»: lista de páginas y apartados del sitio, o un enlace
 *    personalizado / externo. El campo de enlace de siempre sigue existiendo
 *    (mismo nombre y mismo id), así los enlaces guardados no cambian.
 * Comportamiento en el navegador: assets/button-editor.js.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Metas de "botón oculto" que se aceptan al guardar. */
function gep_buttons_off_keys() {
    return apply_filters( 'gep_buttons_off_keys', array(
        'gep_home_hero_primary_off',
        'gep_home_hero_secondary_off',
        'gep_home_cta_off',
        'gep_services_cta_off',
    ) );
}

/** Guarda los interruptores (lo llama gep_demo_handle_admin_save tras el nonce). */
function gep_buttons_save( $post_id, $source ) {
    if ( empty( $source['gep_button_off'] ) || ! is_array( $source['gep_button_off'] ) ) return;
    $allowed = gep_buttons_off_keys();
    foreach ( wp_unslash( $source['gep_button_off'] ) as $key => $value ) {
        $key = sanitize_key( $key );
        if ( ! in_array( $key, $allowed, true ) ) continue;
        $off = '1' === (string) $value ? '1' : '';
        if ( '' === $off && ! metadata_exists( 'post', $post_id, $key ) ) continue;
        update_post_meta( $post_id, $key, $off );
    }
}

/** Destinos que ofrece la lista: páginas del sitio y sus apartados. */
function gep_buttons_destinations() {
    return apply_filters( 'gep_buttons_destinations', array(
        'Páginas del sitio' => array(
            'index.php'          => 'Inicio',
            'quienes-somos.php'  => 'Quiénes somos',
            'docencia.php'       => 'Docencia',
            'proyectos.php'      => 'Proyectos',
            'servicios.php'      => 'Servicios',
            'publicaciones.php'  => 'Publicaciones',
            'comunicaciones.php' => 'Comunicaciones',
            'noticias.php'       => 'Noticias',
            'contactanos.php'    => 'Contacto',
        ),
        'Apartados de otras páginas' => array(
            'proyectos.php#vigentes'         => 'Proyectos › Proyectos vigentes',
            'proyectos.php#trayectoria'      => 'Proyectos › Trayectoria',
            'proyectos.php#areas'            => 'Proyectos › Áreas de trabajo',
            'servicios.php#servicios'        => 'Servicios › Servicios',
            'servicios.php#como-trabajamos'  => 'Servicios › Cómo trabajamos',
            'quienes-somos.php#investigadores' => 'Quiénes somos › Investigadores',
            'publicaciones.php#publicaciones'  => 'Publicaciones › Publicaciones',
            'docencia.php#campos'            => 'Docencia › Campos de formación',
        ),
    ) );
}

/**
 * Tarjeta de un botón.
 * $a: role (primary|secondary), title, hint, text_name, text_id, text_value,
 *     url_name, url_id, url_value, off_key, off_value, anchors (['#id' => 'Etiqueta']),
 *     preview (selector del botón en la vista previa), text_label.
 */
function gep_buttons_render_control( $a ) {
    $a = wp_parse_args( $a, array(
        'role' => 'primary', 'title' => 'Botón', 'hint' => '', 'text_name' => '', 'text_id' => '', 'text_value' => '',
        'url_name' => '', 'url_id' => '', 'url_value' => '', 'off_key' => '', 'off_value' => '', 'anchors' => array(), 'preview' => '',
        'text_label' => 'Texto del botón',
    ) );
    $role  = 'secondary' === $a['role'] ? 'secondary' : 'primary';
    $off   = '1' === (string) $a['off_value'];
    $dests = gep_buttons_destinations();
    $url   = (string) $a['url_value'];
    $known = false;
    foreach ( $a['anchors'] as $k => $l ) if ( $k === $url ) $known = true;
    foreach ( $dests as $group ) foreach ( $group as $k => $l ) if ( $k === $url ) $known = true;
    ?>
    <div class="gep-btn-card is-<?php echo esc_attr( $role ); ?><?php echo $off ? ' is-off' : ''; ?>" data-gep-btn data-preview="<?php echo esc_attr( $a['preview'] ); ?>">
        <div class="gep-btn-card-head">
            <span class="gep-btn-sample is-<?php echo esc_attr( $role ); ?>" aria-hidden="true"><?php echo esc_html( '' !== trim( (string) $a['text_value'] ) ? $a['text_value'] : 'Botón' ); ?></span>
            <div class="gep-btn-card-title"><strong><?php echo esc_html( $a['title'] ); ?></strong><?php if ( $a['hint'] ) : ?><small><?php echo esc_html( $a['hint'] ); ?></small><?php endif; ?></div>
            <?php if ( $a['off_key'] ) : ?>
            <label class="gep-btn-switch"><input type="checkbox" class="gep-btn-on"<?php checked( ! $off ); ?>><span class="gep-btn-switch-track" aria-hidden="true"></span><span class="gep-btn-switch-text"><?php echo $off ? 'Oculto' : 'Visible'; ?></span></label>
            <input type="hidden" class="gep-btn-off" name="gep_button_off[<?php echo esc_attr( $a['off_key'] ); ?>]" value="<?php echo $off ? '1' : '0'; ?>">
            <?php endif; ?>
        </div>
        <div class="gep-btn-card-body">
            <label class="gep-btn-field"><span><?php echo esc_html( $a['text_label'] ); ?></span><input type="text" class="gep-btn-text" id="<?php echo esc_attr( $a['text_id'] ); ?>" name="<?php echo esc_attr( $a['text_name'] ); ?>" value="<?php echo esc_attr( $a['text_value'] ); ?>"></label>
            <label class="gep-btn-field"><span>¿A dónde lleva?</span>
                <select class="gep-link-picker">
                    <?php if ( $a['anchors'] ) : ?><optgroup label="En esta página"><?php foreach ( $a['anchors'] as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $url, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></optgroup><?php endif; ?>
                    <?php foreach ( $dests as $label => $group ) : ?><optgroup label="<?php echo esc_attr( $label ); ?>"><?php foreach ( $group as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $url, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></optgroup><?php endforeach; ?>
                    <optgroup label="Otro"><option value="__custom"<?php selected( ! $known ); ?>>Enlace personalizado o externo…</option></optgroup>
                </select>
            </label>
            <label class="gep-btn-field gep-link-custom"<?php echo $known ? ' hidden' : ''; ?>><span>Enlace</span><input type="text" class="gep-btn-url" id="<?php echo esc_attr( $a['url_id'] ); ?>" name="<?php echo esc_attr( $a['url_name'] ); ?>" value="<?php echo esc_attr( $url ); ?>" placeholder="https://… o proyecto.php?proyecto=…"><small class="gep-muted">Pega una dirección completa (https://…) o una página del sitio.</small></label>
        </div>
    </div>
    <?php
}

/** Pareja principal + secundario (cabecera de Inicio). */
function gep_buttons_render_pair( $primary, $secondary ) {
    echo '<div class="gep-btn-pair"><div class="gep-btn-pair-head"><span class="gep-field-label">Botones de la cabecera</span><p class="gep-muted">El principal es el más destacado. Puedes ocultar cualquiera sin perder su texto.</p></div><div class="gep-btn-pair-grid">';
    gep_buttons_render_control( $primary );
    gep_buttons_render_control( $secondary );
    echo '</div></div>';
}

function gep_buttons_enqueue() {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'toplevel_page_gep-content-editor' !== $screen->id ) return;
    $base = plugin_dir_url( GEP_EDITOR_PLUGIN_FILE );
    wp_enqueue_style( 'gep-editor-tools', $base . 'assets/editor-tools.css', array( 'gep-editor-theme' ), gep_demo_asset_version( 'assets/editor-tools.css' ) );
    wp_enqueue_script( 'gep-button-editor', $base . 'assets/button-editor.js', array( 'jquery' ), gep_demo_asset_version( 'assets/button-editor.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'gep_buttons_enqueue', 45 );
