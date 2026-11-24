<?php
/**
 * GEP Editor · Servicios
 *
 * Pestaña "Servicios": cabecera, lista de servicios (lista primero, editar uno),
 * pasos de trabajo y llamado final. Se guarda como metadatos gep_services_* de la
 * página "servicios" y lo lee el sitio público en servicios.php.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function gep_demo_services_item_fields() {
    return array( 'slug', 'visible', 'tag', 'nav_label', 'title', 'summary', 'description', 'image', 'image_alt', 'deliverables', 'applications', 'equipment', 'button', 'url' );
}

function gep_demo_services_defaults() {
    return array(
        'hero_eyebrow'  => 'Trabajo aplicado',
        'hero_title'    => 'Servicios',
        'hero_text'     => 'Levantamiento y análisis de información territorial para el manejo de bosques, vegetación y paisaje.',
        'hero_image'    => '',
        'hero_video'    => '',
        'intro_eyebrow' => 'Qué hacemos',
        'intro_title'   => 'Datos de terreno y desde el aire, listos para decidir.',
        'intro_text'    => 'Combinamos trabajo de campo, sensores remotos y análisis espacial. Cada servicio se adapta al objetivo, la escala y los plazos de cada proyecto.',
        'items'         => array(
            array(
                'slug'         => 'inventarios-forestales',
                'visible'      => '1',
                'tag'          => 'Terreno + SIG',
                'nav_label'    => 'Inventarios forestales',
                'title'        => 'Inventarios forestales',
                'summary'      => 'Medición y estimación de variables forestales en terreno, con diseño de muestreo y análisis espacial.',
                'description'  => 'Diseñamos y ejecutamos inventarios forestales para conocer la composición, estructura y existencias de un bosque o plantación. Los datos de terreno se integran con cartografía y sensores remotos para obtener estimaciones confiables a escala de rodal o predio.',
                'image'        => '',
                'image_alt'    => '',
                'deliverables' => "Diseño de muestreo y parcelas\nBase de datos dendrométrica\nEstimación de volumen, biomasa y carbono\nCartografía de rodales y coberturas\nInforme técnico",
                'applications' => "Planes de manejo\nLíneas base ambientales\nMonitoreo de restauración\nEstudios de biomasa y carbono",
                'equipment'    => '',
                'button'       => 'Solicitar este servicio',
                'url'          => 'contactanos.php',
            ),
            array(
                'slug'         => 'dron-multiespectral',
                'visible'      => '1',
                'tag'          => 'Sensor aéreo',
                'nav_label'    => 'Dron multiespectral',
                'title'        => 'Levantamientos con dron multiespectral',
                'summary'      => 'Ortomosaicos e índices espectrales de alta resolución para evaluar el estado y los cambios de la vegetación.',
                'description'  => 'Planificamos y ejecutamos vuelos con cámaras multiespectrales para obtener imágenes de muy alta resolución. Con ellas calculamos índices de vegetación y mapas que permiten detectar diferencias de vigor, estrés o daño en la cobertura vegetal.',
                'image'        => '',
                'image_alt'    => '',
                'deliverables' => "Ortomosaico RGB y multiespectral\nÍndices de vegetación (NDVI, NDRE y otros)\nMapas de vigor y estrés\nModelo digital de superficie\nInforme con interpretación",
                'applications' => "Seguimiento de plantaciones y restauración\nDetección de estrés y daño\nClasificación de coberturas\nValidación de imágenes satelitales",
                'equipment'    => '',
                'button'       => 'Solicitar este servicio',
                'url'          => 'contactanos.php',
            ),
            array(
                'slug'         => 'lidar-uav',
                'visible'      => '1',
                'tag'          => 'Sensor aéreo 3D',
                'nav_label'    => 'LiDAR UAV',
                'title'        => 'Levantamientos con LiDAR UAV',
                'summary'      => 'Nubes de puntos 3D para describir la estructura de la vegetación y el terreno bajo el dosel.',
                'description'  => 'El LiDAR montado en dron registra millones de puntos que atraviesan el dosel. Con esa nube de puntos modelamos el terreno, la altura de la vegetación y su estructura vertical con un detalle que no es posible obtener solo con imágenes.',
                'image'        => '',
                'image_alt'    => '',
                'deliverables' => "Nube de puntos clasificada\nModelo digital de terreno (MDT) y de superficie (MDS)\nModelo de altura de dosel\nMétricas de estructura: altura, cobertura y densidad\nDetección de árboles individuales",
                'applications' => "Inventario forestal de precisión\nBiomasa y carbono\nTopografía e hidrología\nEvaluación de hábitat",
                'equipment'    => '',
                'button'       => 'Solicitar este servicio',
                'url'          => 'contactanos.php',
            ),
        ),
        'steps_eyebrow' => 'Cómo trabajamos',
        'steps_title'   => 'De la consulta a la entrega.',
        'steps'         => array(
            array( 'title' => 'Consulta', 'text' => 'Conversamos sobre el objetivo, el área de estudio y los plazos.' ),
            array( 'title' => 'Planificación', 'text' => 'Definimos el método, el muestreo, los vuelos y los permisos necesarios.' ),
            array( 'title' => 'Terreno', 'text' => 'Levantamos los datos en campo y controlamos su calidad.' ),
            array( 'title' => 'Entrega', 'text' => 'Entregamos datos procesados, cartografía e informe.' ),
        ),
        'cta_eyebrow'   => 'Trabajemos juntos',
        'cta_title'     => '¿Necesitas alguno de estos servicios?',
        'cta_button'    => 'Contactar al GEP',
        'cta_url'       => 'contactanos.php',
    );
}

function gep_demo_services_text_keys() {
    return array( 'hero_eyebrow', 'hero_title', 'hero_text', 'hero_image', 'hero_video', 'intro_eyebrow', 'intro_title', 'intro_text', 'steps_eyebrow', 'steps_title', 'cta_eyebrow', 'cta_title', 'cta_button', 'cta_url' );
}

function gep_demo_services_values( $post_id ) {
    $values = gep_demo_services_defaults();
    foreach ( gep_demo_services_text_keys() as $key ) {
        if ( metadata_exists( 'post', $post_id, 'gep_services_' . $key ) ) {
            $values[ $key ] = get_post_meta( $post_id, 'gep_services_' . $key, true );
        }
    }
    $values['cta_off'] = (string) get_post_meta( $post_id, 'gep_services_cta_off', true ); // v9.26.0: botón oculto
    foreach ( array( 'items', 'steps' ) as $list ) {
        $json = get_post_meta( $post_id, 'gep_services_' . $list . '_json', true );
        if ( '' !== $json ) {
            $decoded = json_decode( $json, true );
            if ( is_array( $decoded ) ) $values[ $list ] = $decoded;
        }
    }
    return $values;
}

function gep_demo_is_services_page( $post_id, $navigation = null ) {
    if ( ! $post_id ) return false;
    if ( null === $navigation ) $navigation = gep_demo_get_page_navigation();
    if ( ! empty( $navigation['servicios']['page'] ) && (int) $navigation['servicios']['page']->ID === (int) $post_id ) return true;
    $post = get_post( $post_id );
    return $post && in_array( gep_demo_normalize_page_name( $post->post_name ), array( 'servicios', 'servicio' ), true );
}

function gep_demo_services_public_url( $post_id = 0 ) {
    return gep_editor_public_page_url( 'servicios.php', $post_id );
}

function gep_demo_services_clean_url( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) return '';
    // Permite rutas relativas del sitio (contactanos.php, #ancla) además de URLs completas.
    if ( preg_match( '#^[a-z0-9_\-]+\.php([?\#].*)?$#i', $value ) || 0 === strpos( $value, '#' ) ) {
        return sanitize_text_field( $value );
    }
    return esc_url_raw( $value );
}

function gep_demo_services_clean_lines( $value ) {
    $lines = preg_split( '/[\r\n]+/', (string) $value );
    $out   = array();
    foreach ( $lines as $line ) {
        $line = trim( sanitize_text_field( $line ) );
        if ( '' !== $line ) $out[] = $line;
    }
    return implode( "\n", $out );
}

function gep_demo_services_clean_items( $raw ) {
    $clean = array();
    $used  = array();
    foreach ( is_array( $raw ) ? $raw : array() as $row ) {
        if ( ! is_array( $row ) ) continue;
        $item = array(
            'slug'         => isset( $row['slug'] ) ? sanitize_title( $row['slug'] ) : '',
            'visible'      => ! empty( $row['visible'] ) ? '1' : '0',
            'tag'          => isset( $row['tag'] ) ? sanitize_text_field( $row['tag'] ) : '',
            'nav_label'    => isset( $row['nav_label'] ) ? sanitize_text_field( $row['nav_label'] ) : '',
            'title'        => isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '',
            'summary'      => isset( $row['summary'] ) ? sanitize_textarea_field( $row['summary'] ) : '',
            'description'  => isset( $row['description'] ) ? sanitize_textarea_field( $row['description'] ) : '',
            'image'        => isset( $row['image'] ) ? esc_url_raw( $row['image'] ) : '',
            'image_alt'    => isset( $row['image_alt'] ) ? sanitize_text_field( $row['image_alt'] ) : '',
            'deliverables' => isset( $row['deliverables'] ) ? gep_demo_services_clean_lines( $row['deliverables'] ) : '',
            'applications' => isset( $row['applications'] ) ? gep_demo_services_clean_lines( $row['applications'] ) : '',
            'equipment'    => isset( $row['equipment'] ) ? sanitize_text_field( $row['equipment'] ) : '',
            'button'       => isset( $row['button'] ) ? sanitize_text_field( $row['button'] ) : '',
            'url'          => isset( $row['url'] ) ? gep_demo_services_clean_url( $row['url'] ) : '',
        );
        if ( '' === $item['title'] && '' === $item['summary'] && '' === $item['description'] ) continue;
        if ( '' === $item['slug'] ) $item['slug'] = sanitize_title( $item['nav_label'] ? $item['nav_label'] : $item['title'] );
        if ( '' === $item['slug'] ) $item['slug'] = 'servicio';
        $base = $item['slug'];
        $n    = 2;
        while ( isset( $used[ $item['slug'] ] ) ) { $item['slug'] = $base . '-' . $n; $n++; }
        $used[ $item['slug'] ] = true;
        $clean[] = $item;
    }
    return $clean;
}

function gep_demo_services_clean_steps( $raw ) {
    $clean = array();
    foreach ( is_array( $raw ) ? $raw : array() as $row ) {
        if ( ! is_array( $row ) ) continue;
        $step = array(
            'title' => isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '',
            'text'  => isset( $row['text'] ) ? sanitize_textarea_field( $row['text'] ) : '',
        );
        if ( '' !== $step['title'] || '' !== $step['text'] ) $clean[] = $step;
    }
    return $clean;
}

function gep_demo_save_services_fields( $post_id, $source ) {
    foreach ( gep_demo_services_text_keys() as $key ) {
        $name = 'gep_services_' . $key;
        if ( ! isset( $source[ $name ] ) ) continue;
        $raw = wp_unslash( $source[ $name ] );
        if ( in_array( $key, array( 'hero_image', 'hero_video' ), true ) ) {
            $value = esc_url_raw( $raw );
        } elseif ( 'cta_url' === $key ) {
            $value = gep_demo_services_clean_url( $raw );
        } elseif ( in_array( $key, array( 'hero_text', 'intro_text' ), true ) ) {
            $value = sanitize_textarea_field( $raw );
        } else {
            $value = sanitize_text_field( $raw );
        }
        update_post_meta( $post_id, $name, $value );
    }
    $items = isset( $source['gep_services_items'] ) && is_array( $source['gep_services_items'] ) ? wp_unslash( $source['gep_services_items'] ) : array();
    update_post_meta( $post_id, 'gep_services_items_json', wp_slash( wp_json_encode( gep_demo_services_clean_items( $items ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
    $steps = isset( $source['gep_services_steps'] ) && is_array( $source['gep_services_steps'] ) ? wp_unslash( $source['gep_services_steps'] ) : array();
    update_post_meta( $post_id, 'gep_services_steps_json', wp_slash( wp_json_encode( gep_demo_services_clean_steps( $steps ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
}

function gep_demo_render_service_row( $key, $row = array() ) {
    $row     = wp_parse_args( is_array( $row ) ? $row : array(), array_fill_keys( gep_demo_services_item_fields(), '' ) );
    $name    = 'gep_services_items[' . $key . ']';
    $visible = '0' !== (string) $row['visible'];
    $title   = '' !== $row['title'] ? $row['title'] : 'Nuevo servicio';
    ?>
    <div class="gep-service-row<?php echo $visible ? '' : ' is-hidden-service'; ?>" data-service-key="<?php echo esc_attr( $key ); ?>">
        <div class="gep-service-row-head">
            <span class="gep-grip dashicons dashicons-menu-alt2" title="Arrastrar para ordenar"></span>
            <span class="gep-service-row-number"></span>
            <span class="gep-service-row-copy"><strong class="gep-service-row-title"><?php echo esc_html( $title ); ?></strong><small class="gep-service-row-tag"><?php echo esc_html( $row['tag'] ); ?></small></span>
            <span class="gep-service-row-state"><?php echo $visible ? 'Visible' : 'Oculto'; ?></span>
            <button type="button" class="button gep-service-open"><span class="dashicons dashicons-edit"></span> Editar</button>
        </div>
        <div class="gep-service-row-body" hidden>
            <div class="gep-publication-single-head"><div><span>Editando servicio</span><strong class="gep-service-active-title"><?php echo esc_html( $title ); ?></strong></div><button type="button" class="button-link gep-service-close" aria-label="Cerrar editor"><span class="dashicons dashicons-no-alt"></span></button></div>
            <div class="gep-item-topline"><div class="gep-card-order-actions"><button type="button" class="button button-small gep-service-up">Subir</button><button type="button" class="button button-small gep-service-down">Bajar</button><button type="button" class="button button-small gep-service-duplicate"><span class="dashicons dashicons-admin-page"></span> Duplicar</button></div><button type="button" class="button-link-delete gep-service-remove"><span class="dashicons dashicons-trash"></span> Eliminar servicio</button></div>
            <label class="gep-service-visible-toggle"><input type="hidden" name="<?php echo esc_attr( $name ); ?>[visible]" value="0"><input type="checkbox" class="gep-service-visible" name="<?php echo esc_attr( $name ); ?>[visible]" value="1" <?php checked( $visible ); ?>> Mostrar este servicio en la página pública</label>
            <label>Nombre del servicio <span class="gep-required-dot">Obligatorio</span><input type="text" class="gep-service-title" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $row['title'] ); ?>"></label>
            <div class="gep-grid-2"><label>Etiqueta<input type="text" class="gep-service-tag" name="<?php echo esc_attr( $name ); ?>[tag]" value="<?php echo esc_attr( $row['tag'] ); ?>" placeholder="Sensor aéreo"></label><label>Nombre corto en el menú<input type="text" name="<?php echo esc_attr( $name ); ?>[nav_label]" value="<?php echo esc_attr( $row['nav_label'] ); ?>" placeholder="LiDAR UAV"></label></div>
            <label>Resumen <span class="gep-optional">(una o dos frases)</span><textarea rows="2" name="<?php echo esc_attr( $name ); ?>[summary]"><?php echo esc_textarea( $row['summary'] ); ?></textarea></label>
            <label>Descripción<textarea rows="4" name="<?php echo esc_attr( $name ); ?>[description]"><?php echo esc_textarea( $row['description'] ); ?></textarea></label>
            <div class="gep-grid-2"><label>Qué entregamos <span class="gep-optional">(uno por línea)</span><textarea rows="5" name="<?php echo esc_attr( $name ); ?>[deliverables]"><?php echo esc_textarea( $row['deliverables'] ); ?></textarea></label><label>Aplicaciones <span class="gep-optional">(una por línea)</span><textarea rows="5" name="<?php echo esc_attr( $name ); ?>[applications]"><?php echo esc_textarea( $row['applications'] ); ?></textarea></label></div>
            <label>Equipo o tecnología <span class="gep-optional">(opcional)</span><input type="text" name="<?php echo esc_attr( $name ); ?>[equipment]" value="<?php echo esc_attr( $row['equipment'] ); ?>" placeholder="Ej.: dron con sensor multiespectral de 5 bandas"></label>
            <div class="gep-admin-field gep-media-field"><span class="gep-field-label">Imagen <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_image_control( $name . '[image]', $row['image'], 'Seleccionar / cambiar imagen' ); ?></div>
            <label>Texto alternativo de la imagen<input type="text" name="<?php echo esc_attr( $name ); ?>[image_alt]" value="<?php echo esc_attr( $row['image_alt'] ); ?>"></label>
            <div class="gep-grid-2"><label>Texto del botón<input type="text" name="<?php echo esc_attr( $name ); ?>[button]" value="<?php echo esc_attr( $row['button'] ); ?>" placeholder="Solicitar este servicio"></label><label>Enlace del botón<input type="text" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="contactanos.php"></label></div>
            <input type="hidden" name="<?php echo esc_attr( $name ); ?>[slug]" value="<?php echo esc_attr( $row['slug'] ); ?>">
            <div class="gep-service-row-done"><button type="button" class="button button-primary gep-service-close"><span class="dashicons dashicons-yes"></span> Listo</button></div>
        </div>
    </div>
    <?php
}

function gep_demo_render_services_editor( $post_id, $values ) {
    $public_url = gep_demo_services_public_url( $post_id );
    $items      = isset( $values['items'] ) && is_array( $values['items'] ) ? $values['items'] : array();
    $steps      = isset( $values['steps'] ) && is_array( $values['steps'] ) ? array_values( $values['steps'] ) : array();
    $steps      = array_pad( $steps, 4, array( 'title' => '', 'text' => '' ) );
    $using_defaults = ! metadata_exists( 'post', $post_id, 'gep_services_items_json' );
    $f = function( $key ) use ( $values ) { return isset( $values[ $key ] ) ? $values[ $key ] : ''; };
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gep-content-form" class="gep-services-form" novalidate>
      <input type="hidden" name="action" value="gep_demo_save_content"><input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>"><input type="hidden" name="gep_editor_context" value="services"><?php wp_nonce_field( 'gep_demo_save_content_' . $post_id, 'gep_demo_admin_nonce' ); ?>
      <div class="gep-contact-commandbar"><div class="gep-contact-commandbar-copy"><span class="gep-contact-command-icon"><span class="dashicons dashicons-admin-tools"></span></span><div><strong>Editor de Servicios</strong><small>Cabecera, servicios, forma de trabajo y llamado final.</small></div><span class="gep-inline-save-state is-clean gep-hub-inline-status"><span class="dashicons dashicons-yes-alt"></span> Todo guardado</span></div><div class="gep-contact-commandbar-actions"><?php /* v9.25.0: "Ver página real" se quitó de esta barra; el acceso queda en "Sitio público" del menú lateral. */ ?><button type="submit" class="button button-primary gep-save-button" disabled><span class="dashicons dashicons-saved"></span> Guardar cambios</button></div></div>
      <div class="gep-editor-layout gep-editor-layout-hub gep-editor-layout-services"><div class="gep-questionnaire">

        <section class="gep-step-card" data-service-preview="hero"><div class="gep-step-heading"><span class="gep-step-number">1</span><div><h3>Encabezado</h3><p>Presentación de la página de servicios.</p></div></div>
          <div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" name="gep_services_hero_eyebrow" value="<?php echo esc_attr( $f( 'hero_eyebrow' ) ); ?>"></div><div class="gep-admin-field"><label>Título <span class="gep-required-dot">Obligatorio</span></label><input type="text" name="gep_services_hero_title" value="<?php echo esc_attr( $f( 'hero_title' ) ); ?>" data-gep-service-required="1"></div></div>
          <div class="gep-admin-field"><label>Descripción</label><textarea name="gep_services_hero_text" rows="3"><?php echo esc_textarea( $f( 'hero_text' ) ); ?></textarea></div>
          <div class="gep-admin-field gep-media-field gep-hub-hero-field"><span class="gep-field-label">Imagen de cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_image_control( 'gep_services_hero_image', $f( 'hero_image' ), 'Seleccionar / cambiar imagen de cabecera' ); ?></div>
          <div class="gep-admin-field gep-media-field gep-hub-hero-field gep-hero-video-field"><span class="gep-field-label">Video de cabecera <span class="gep-optional">(opcional)</span></span><?php gep_demo_render_video_control( 'gep_services_hero_video', $f( 'hero_video' ), 'Seleccionar / cambiar video de cabecera' ); ?></div>
        </section>

        <section class="gep-step-card" data-service-preview="services"><div class="gep-step-heading"><span class="gep-step-number">2</span><div><h3>Servicios</h3><p>Abre un servicio para editarlo. El orden de la lista es el orden de la página.</p></div></div>
          <div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" name="gep_services_intro_eyebrow" value="<?php echo esc_attr( $f( 'intro_eyebrow' ) ); ?>"></div><div class="gep-admin-field"><label>Título del bloque</label><input type="text" name="gep_services_intro_title" value="<?php echo esc_attr( $f( 'intro_title' ) ); ?>"></div></div>
          <div class="gep-admin-field"><label>Texto introductorio</label><textarea name="gep_services_intro_text" rows="2"><?php echo esc_textarea( $f( 'intro_text' ) ); ?></textarea></div>
          <?php if ( $using_defaults ) : ?><div class="gep-hub-starter-note"><span class="dashicons dashicons-lightbulb"></span><div><strong>Textos de ejemplo.</strong><span>Reemplázalos por la información real del laboratorio antes de publicar. Se guardan cuando pulses "Guardar cambios".</span></div></div><?php endif; ?>
          <div class="gep-team-builder-head"><div><strong id="gep-service-count"><?php echo count( $items ); ?> servicios</strong><p class="gep-muted">Arrastra o usa Subir/Bajar para ordenar.</p></div><button type="button" class="button gep-service-add"><span class="dashicons dashicons-plus-alt2"></span> Añadir servicio</button></div>
          <div class="gep-service-list" id="gep-service-list"><?php foreach ( $items as $k => $row ) gep_demo_render_service_row( 's' . $k, $row ); ?></div>
          <div class="gep-empty-builder <?php echo $items ? 'is-hidden' : ''; ?>" id="gep-service-empty"><span class="dashicons dashicons-admin-tools"></span><strong>No hay servicios todavía.</strong><span>Añade el primero cuando quieras.</span></div>
        </section>

        <section class="gep-step-card" data-service-preview="steps"><div class="gep-step-heading"><span class="gep-step-number">3</span><div><h3>Cómo trabajamos</h3><p>Hasta cuatro pasos. Deja un paso vacío para ocultarlo.</p></div></div>
          <div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" name="gep_services_steps_eyebrow" value="<?php echo esc_attr( $f( 'steps_eyebrow' ) ); ?>"></div><div class="gep-admin-field"><label>Título</label><input type="text" name="gep_services_steps_title" value="<?php echo esc_attr( $f( 'steps_title' ) ); ?>"></div></div>
          <div class="gep-service-steps-grid">
            <?php foreach ( array_slice( $steps, 0, 4 ) as $i => $step ) : ?>
              <div class="gep-service-step-field"><span class="gep-service-step-no"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><label>Paso<input type="text" name="gep_services_steps[<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr( isset( $step['title'] ) ? $step['title'] : '' ); ?>"></label><label>Texto<textarea rows="2" name="gep_services_steps[<?php echo (int) $i; ?>][text]"><?php echo esc_textarea( isset( $step['text'] ) ? $step['text'] : '' ); ?></textarea></label></div>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="gep-step-card" data-service-preview="cta"><div class="gep-step-heading"><span class="gep-step-number">4</span><div><h3>Llamado final</h3><p>Franja verde al final de la página.</p></div></div>
          <div class="gep-grid-2"><div class="gep-admin-field"><label>Texto superior</label><input type="text" name="gep_services_cta_eyebrow" value="<?php echo esc_attr( $f( 'cta_eyebrow' ) ); ?>"></div><div class="gep-admin-field"><label>Título</label><input type="text" name="gep_services_cta_title" value="<?php echo esc_attr( $f( 'cta_title' ) ); ?>"></div></div>
          <?php if ( function_exists( 'gep_buttons_render_control' ) ) { gep_buttons_render_control( array( 'role' => 'primary', 'title' => 'Botón del llamado final', 'hint' => 'Botón principal, con relleno', 'text_name' => 'gep_services_cta_button', 'text_id' => 'gep_services_cta_button', 'text_value' => $f( 'cta_button' ), 'url_name' => 'gep_services_cta_url', 'url_id' => 'gep_services_cta_url', 'url_value' => $f( 'cta_url' ), 'off_key' => 'gep_services_cta_off', 'off_value' => $f( 'cta_off' ), 'anchors' => array( '#servicios' => 'Servicios › Servicios (más arriba)', '#como-trabajamos' => 'Servicios › Cómo trabajamos' ), 'preview' => '#gep-service-live-cta-button' ) ); } ?>
        </section>

        <?php /* v9.26.0: se quitó el botón inferior "Guardar cambios"; se guarda desde la barra superior. */ ?>
      </div>
      <aside class="gep-preview-column">
        <div class="gep-preview-card gep-parent-preview-card" id="gep-service-preview-shell" data-public-url="<?php echo esc_url( $public_url ); ?>">
          <div class="gep-preview-heading gep-directory-preview-heading"><span class="gep-preview-icon"><span class="dashicons dashicons-visibility"></span></span><div class="gep-preview-heading-copy"><div class="gep-preview-heading-title-row"><span class="gep-preview-zone-badge" id="gep-service-preview-zone">Zona 1</span><h3 id="gep-service-preview-heading">Encabezado</h3></div><p class="gep-muted">Preview de Servicios</p></div><div class="gep-preview-device-switcher"><button type="button" class="gep-directory-device-button gep-service-device-button is-active" data-device="desktop"><span class="dashicons dashicons-desktop"></span><span>PC</span></button><button type="button" class="gep-directory-device-button gep-service-device-button" data-device="tablet"><span class="dashicons dashicons-tablet"></span><span>Tablet</span></button><button type="button" class="gep-directory-device-button gep-service-device-button" data-device="mobile"><span class="dashicons dashicons-smartphone"></span><span>Móvil</span></button></div></div>
          <div class="gep-directory-device-stage gep-hub-device-stage"><div class="gep-directory-browser gep-hub-browser gep-service-browser" id="gep-service-live-preview"><div class="gep-directory-live-toolbar"><span></span><span></span><span></span><strong>servicios.php</strong><em id="gep-service-device-label">Vista escritorio</em></div>
            <div class="gep-preview-stage">
              <div class="gep-hub-live-hero" id="gep-service-live-hero" data-service-live="hero"><span id="gep-service-live-eyebrow"></span><h4 id="gep-service-live-title"></h4><p id="gep-service-live-text"></p></div>
              <div class="gep-hub-live-nav" id="gep-service-live-nav"></div>
              <div class="gep-hub-live-content" data-service-live="services"><div class="gep-hub-live-section-head"><span id="gep-service-live-intro-eyebrow"></span><strong id="gep-service-live-intro-title"></strong></div><div class="gep-service-live-list" id="gep-service-live-list"></div></div>
              <div class="gep-hub-live-content gep-service-live-steps-wrap" data-service-live="steps"><div class="gep-hub-live-section-head"><span id="gep-service-live-steps-eyebrow"></span><strong id="gep-service-live-steps-title"></strong></div><div class="gep-service-live-steps" id="gep-service-live-steps"></div></div>
              <div class="gep-service-live-cta" data-service-live="cta"><div><span id="gep-service-live-cta-eyebrow"></span><strong id="gep-service-live-cta-title"></strong></div><b id="gep-service-live-cta-button"></b></div>
            </div>
          </div></div>
        </div>
      </aside></div>
      <script type="text/html" id="gep-service-row-template"><?php gep_demo_render_service_row( '__K__', array( 'visible' => '1', 'button' => 'Solicitar este servicio', 'url' => 'contactanos.php' ) ); ?></script>
    </form>
    <?php
}

function gep_demo_services_admin_assets() {
    $screen = get_current_screen();
    if ( ! $screen || 'toplevel_page_gep-content-editor' !== $screen->id ) return;
    $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
    if ( ! $post_id || ! gep_demo_is_services_page( $post_id ) ) return;
    // Versión por fecha de archivo: un ZIP nuevo con la misma versión del plugin no queda en caché.
    $dir     = plugin_dir_path( GEP_EDITOR_PLUGIN_FILE );
    $css_ver = GEP_EDITOR_VERSION . '.' . (int) @filemtime( $dir . 'assets/services.css' );
    $js_ver  = GEP_EDITOR_VERSION . '.' . (int) @filemtime( $dir . 'assets/services.js' );
    wp_enqueue_style( 'gep-demo-services', plugin_dir_url( GEP_EDITOR_PLUGIN_FILE ) . 'assets/services.css', array( 'gep-demo-admin' ), $css_ver );
    wp_enqueue_script( 'gep-demo-services', plugin_dir_url( GEP_EDITOR_PLUGIN_FILE ) . 'assets/services.js', array( 'jquery', 'jquery-ui-sortable', 'gep-demo-admin' ), $js_ver, true );
}
add_action( 'admin_enqueue_scripts', 'gep_demo_services_admin_assets', 20 );

/**
 * Tutorial corto de Servicios en la Ayuda (módulo includes/help.php).
 */
function gep_demo_services_help_tutorial( $tutorials ) {
    $tutorials[] = array(
        'id'    => 'servicios',
        'title' => 'Editar un servicio',
        'keys'  => 'servicio servicios inventario forestal dron multiespectral lidar uav ocultar',
        'steps' => array(
            array( 'tabs', 'Abre la pestaña Servicios.' ),
            array( 'list', 'En el apartado Servicios pulsa Editar en el servicio que quieres cambiar.' ),
            array( 'field', 'Cambia los textos. "Qué entregamos" y "Aplicaciones" llevan un ítem por línea.' ),
            array( 'list2', 'Desmarca "Mostrar este servicio" para ocultarlo sin borrarlo.' ),
            array( 'save', 'Revisa la vista previa y guarda.' ),
        ),
    );
    return $tutorials;
}
add_filter( 'gep_help_tutorials', 'gep_demo_services_help_tutorial' );
