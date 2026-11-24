<?php
/**
 * GEP Editor 10.0.0 · Archivos en uso.
 *
 * 1. En la Mediateca, cada archivo muestra «Se usa en: Inicio, Servicios…»
 *    (o «No se usa en ninguna página GEP»).
 * 2. WordPress no deja borrar un archivo que alguna página GEP está usando.
 *    Primero hay que quitarlo (o reemplazarlo) en esa página y guardar.
 *
 * Cómo se busca: el nombre del archivo (con su carpeta de año/mes) dentro de
 * los campos «gep_…» de las 9 páginas del editor. También encuentra los
 * tamaños reducidos (foto-300x200.jpg) y las versiones -movil / -respaldo
 * de los videos. No mira el Historial de cambios ni los respaldos, porque
 * esas copias antiguas no se muestran en el sitio.
 *
 * Para apagarlo sin tocar código: define( 'GEP_PROTEGER_ARCHIVOS', false );
 * en wp-config.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function gep_uso_activo() {
    return ! defined( 'GEP_PROTEGER_ARCHIVOS' ) || GEP_PROTEGER_ARCHIVOS;
}

/** ID => nombre de las páginas que maneja el editor. */
function gep_uso_paginas() {
    $paginas = array();
    if ( function_exists( 'gep_demo_get_page_navigation' ) ) {
        foreach ( (array) gep_demo_get_page_navigation() as $item ) {
            if ( ! empty( $item['page']->ID ) ) {
                $paginas[ (int) $item['page']->ID ] = (string) $item['label'];
            }
        }
    }
    return $paginas;
}

/**
 * Páginas GEP donde aparece el archivo.
 *
 * @return array ID de página => nombre.
 */
function gep_uso_de_archivo( $attachment_id ) {
    global $wpdb;
    $attachment_id = absint( $attachment_id );
    $archivo       = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );
    if ( '' === $archivo ) {
        return array();
    }
    $paginas = gep_uso_paginas();
    if ( ! $paginas ) {
        return array();
    }

    // «2026/10/foto-scaled.jpg» -> base «2026/10/foto».
    $sin_ext = preg_replace( '/\.[a-z0-9]{2,5}$/i', '', $archivo );
    $base    = preg_replace( '/-scaled$/', '', $sin_ext );
    $nombre  = basename( $base );
    $carpeta = trim( dirname( $base ), './' );
    if ( '' === $nombre ) {
        return array();
    }

    // Formas en que puede estar guardado: con «/» normal o «\/» (JSON).
    $patrones = array();
    foreach ( array( '/', '\\/' ) as $barra ) {
        $ruta = ( '' !== $carpeta ? str_replace( '/', $barra, $carpeta ) . $barra : '' ) . $nombre;
        $patrones[] = '%' . $wpdb->esc_like( $ruta . '.' ) . '%';
        $patrones[] = '%' . $wpdb->esc_like( $ruta . '-' ) . '%';
    }
    // Un archivo enlazado por su número (algunos campos guardan el ID).
    $id_texto = (string) $attachment_id;

    $ids_sql  = implode( ',', array_map( 'intval', array_keys( $paginas ) ) );
    $like_sql = implode( ' OR ', array_fill( 0, count( $patrones ), 'meta_value LIKE %s' ) );
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- IDs enteros y placeholders.
    $filas = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($ids_sql) AND meta_key LIKE 'gep\\_%%' AND ( $like_sql OR meta_value = %s )", array_merge( $patrones, array( $id_texto ) ) ) );

    $uso = array();
    foreach ( (array) $filas as $fila ) {
        // Confirmación exacta: «foto-2.jpg» no cuenta como uso de «foto.jpg».
        $valor = str_replace( '\\/', '/', (string) $fila->meta_value );
        $ruta  = ( '' !== $carpeta ? $carpeta . '/' : '' ) . $nombre;
        $ok    = $valor === $id_texto
            || preg_match( '#' . preg_quote( $ruta, '#' ) . '(-scaled|-\d+x\d+|-movil|-pc|-respaldo|-web)?\.[a-z0-9]{2,5}#i', $valor );
        if ( $ok ) {
            $uso[ (int) $fila->post_id ] = $paginas[ (int) $fila->post_id ];
        }
    }
    return $uso;
}

function gep_uso_texto( $uso ) {
    return $uso ? implode( ', ', $uso ) : '';
}

/* ---- 1. «Se usa en» en los detalles del archivo ------------------------- */

add_filter( 'attachment_fields_to_edit', function ( $campos, $post ) {
    if ( ! gep_uso_activo() || ! current_user_can( 'upload_files' ) ) {
        return $campos;
    }
    $uso = gep_uso_de_archivo( $post->ID );
    if ( $uso ) {
        $html = '<span class="gep-uso is-used"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span> '
            . esc_html( gep_uso_texto( $uso ) )
            . '</span><span class="gep-uso-nota">Para borrarlo, primero quítalo de esa página en Contenido GEP y guarda.</span>';
    } else {
        $html = '<span class="gep-uso">No se usa en ninguna página GEP.</span>';
    }
    $campos['gep_uso'] = array(
        'label' => 'Se usa en',
        'input' => 'html',
        'html'  => $html,
    );
    return $campos;
}, 20, 2 );

add_action( 'admin_head', function () {
    if ( ! gep_uso_activo() ) {
        return;
    }
    echo '<style>.compat-field-gep_uso .gep-uso{display:flex;gap:6px;align-items:flex-start;line-height:1.5}.compat-field-gep_uso .gep-uso.is-used{font-weight:600;color:#1f5a3b}.compat-field-gep_uso .gep-uso .dashicons{font-size:16px;width:16px;height:16px;margin-top:2px}.compat-field-gep_uso .gep-uso-nota{display:block;margin-top:4px;color:#646970;font-size:12px}</style>';
} );

/* ---- 2. No borrar archivos en uso --------------------------------------- */

add_filter( 'pre_delete_attachment', function ( $borrar, $post ) {
    if ( null !== $borrar || ! gep_uso_activo() || ! $post ) {
        return $borrar;
    }
    $uso = gep_uso_de_archivo( $post->ID );
    if ( ! $uso ) {
        return $borrar;
    }
    $GLOBALS['gep_uso_bloqueado'] = array( 'titulo' => get_the_title( $post ), 'uso' => $uso );
    if ( function_exists( 'gep_registro' ) ) {
        gep_registro( 'archivo', 'Se evitó borrar un archivo que está en uso en: ' . gep_uso_texto( $uso ) . '.', get_the_title( $post ) );
    }
    return false; // WordPress no borra nada.
}, 10, 2 );

/*
 * La Mediateca en cuadrícula borra por AJAX («delete-post»). Si se bloqueó,
 * se responde con el motivo para que la pantalla lo muestre (ver el script de
 * abajo) en vez de quitar la foto de la vista como si se hubiera borrado.
 */
add_action( 'wp_ajax_delete-post', function () {
    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    if ( ! $id || 'attachment' !== get_post_type( $id ) || ! gep_uso_activo() ) {
        return; // Sigue el borrado normal de WordPress.
    }
    check_ajax_referer( "delete-post_$id" );
    if ( ! current_user_can( 'delete_post', $id ) ) {
        return;
    }
    $uso = gep_uso_de_archivo( $id );
    if ( $uso ) {
        if ( function_exists( 'gep_registro' ) ) {
            gep_registro( 'archivo', 'Se evitó borrar un archivo que está en uso en: ' . gep_uso_texto( $uso ) . '.', get_the_title( $id ) );
        }
        wp_send_json_error( array( 'gepUso' => gep_uso_texto( $uso ), 'titulo' => get_the_title( $id ) ) );
    }
}, 0 );

/*
 * Mediateca en modo lista (upload.php) y pantalla «Editar medio» (post.php):
 * WordPress muestra «Error al borrar» si el borrado se bloquea. Antes de que
 * llegue a eso, se revisa y se vuelve a la lista con un aviso claro, sin
 * borrar ninguno de los archivos elegidos.
 */
function gep_uso_revisar_borrado_pantalla( $ids, $volver ) {
    $bloqueados = array();
    foreach ( array_filter( array_map( 'absint', (array) $ids ) ) as $id ) {
        if ( 'attachment' === get_post_type( $id ) && current_user_can( 'delete_post', $id ) ) {
            $uso = gep_uso_de_archivo( $id );
            if ( $uso ) {
                $bloqueados[] = '«' . get_the_title( $id ) . '» (' . gep_uso_texto( $uso ) . ')';
            }
        }
    }
    if ( $bloqueados ) {
        if ( function_exists( 'gep_registro' ) ) {
            gep_registro( 'archivo', 'Se evitó borrar archivos en uso: ' . implode( '; ', $bloqueados ) );
        }
        wp_safe_redirect( add_query_arg( 'gep_uso_bloqueado', rawurlencode( implode( '; ', $bloqueados ) ), $volver ) );
        exit;
    }
}
add_action( 'load-upload.php', function () {
    $accion = isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ? sanitize_key( $_REQUEST['action'] ) : ( isset( $_REQUEST['action2'] ) ? sanitize_key( $_REQUEST['action2'] ) : '' );
    if ( ! gep_uso_activo() || 'delete' !== $accion || empty( $_REQUEST['media'] ) ) {
        return;
    }
    gep_uso_revisar_borrado_pantalla( wp_unslash( (array) $_REQUEST['media'] ), admin_url( 'upload.php?mode=list' ) );
} );
add_action( 'load-post.php', function () {
    if ( ! gep_uso_activo() || empty( $_GET['action'] ) || 'delete' !== $_GET['action'] || empty( $_GET['post'] ) ) {
        return;
    }
    $id = absint( $_GET['post'] );
    if ( 'attachment' === get_post_type( $id ) ) {
        gep_uso_revisar_borrado_pantalla( array( $id ), get_edit_post_link( $id, 'url' ) );
    }
} );
add_action( 'admin_notices', function () {
    if ( empty( $_GET['gep_uso_bloqueado'] ) ) {
        return;
    }
    $donde = sanitize_text_field( wp_unslash( $_GET['gep_uso_bloqueado'] ) );
    echo '<div class="notice notice-warning is-dismissible"><p><strong>No se borró nada.</strong> Estos archivos se usan en páginas del sitio: ' . esc_html( $donde ) . '. Primero quítalos de esas páginas en Contenido GEP y guarda.</p></div>';
} );

/* Cuadrícula de la Mediateca y selector: aviso en vez de borrar en silencio. */
add_action( 'wp_enqueue_media', function () {
    if ( ! gep_uso_activo() ) {
        return;
    }
    $js = <<<'JS'
(function(){
  if (!window.wp || !wp.media || !wp.media.model || !wp.media.model.Attachment) return;
  var A = wp.media.model.Attachment.prototype, orig = A.sync;
  A.sync = function(method, model, options){
    if (method !== 'delete') return orig.apply(this, arguments);
    var r = orig.apply(this, arguments);
    if (r && r.fail) r.fail(function(res){
      var uso = res && res.gepUso;
      if (!uso) return;
      // Vuelve a mostrar el archivo que la vista ya había quitado.
      try { if (model.collection == null && wp.media.frame && wp.media.frame.state) { var lib = wp.media.frame.state().get('library'); if (lib) lib.add(model); } } catch(e) {}
      var box = document.createElement('div');
      box.className = 'gep-uso-aviso';
      box.setAttribute('role','alert');
      box.innerHTML = '<strong>No se borró «' + String(res.titulo||'').replace(/[<>&"]/g,'') + '».</strong> Se usa en ' + String(uso).replace(/[<>&"]/g,'') + '. Primero quítalo de esa página en Contenido GEP y guarda. <button type="button" class="button-link">Entendido</button>';
      box.style.cssText = 'position:fixed;z-index:200000;left:50%;bottom:24px;transform:translateX(-50%);max-width:560px;width:calc(100% - 32px);background:#fff5e5;color:#5a3a00;border:1px solid #f0cf96;border-radius:10px;padding:12px 16px;box-shadow:0 10px 30px rgba(0,0,0,.18);font-size:14px;line-height:1.5';
      box.querySelector('button').onclick = function(){ box.remove(); };
      document.body.appendChild(box);
      setTimeout(function(){ if (box.parentNode) box.remove(); }, 12000);
    });
    return r;
  };
})();
JS;
    wp_add_inline_script( 'media-models', $js );
} );
