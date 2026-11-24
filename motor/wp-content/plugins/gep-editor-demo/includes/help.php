<?php
/**
 * GEP Editor · Centro de ayuda visual (v9.17).
 *
 * - Página "Ayuda" con buscador, tutoriales de 1 minuto, mapa del editor
 *   y problemas frecuentes.
 * - Ayuda contextual dentro del editor: botón "?" en cada apartado y un
 *   recorrido de bienvenida que se abre solo cuando se pide (Ayuda > Ver recorrido).
 *
 * Reemplaza la Ayuda antigua sin borrarla del archivo principal: toma el
 * mismo submenú (gep-content-help) y cambia su función de dibujo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Tutoriales cortos. Cada paso señala una zona del esquema del editor:
 * tabs, list, list2, field, add, prev, dev, save.
 */
function gep_help_tutorials() {
    $tutorials = array(
        array(
            'id'    => 'texto',
            'title' => 'Cambiar un texto',
            'keys'  => 'texto titulo parrafo descripcion editar escribir',
            'steps' => array(
                array( 'tabs', 'Elige la página en el menú de páginas (a la izquierda en PC).' ),
                array( 'field', 'Abre el apartado y cambia el texto.' ),
                array( 'prev', 'Mira el cambio en la vista previa.' ),
                array( 'save', 'Pulsa Guardar cambios.' ),
            ),
        ),
        array(
            'id'    => 'portada',
            'title' => 'Foto o video de portada',
            'keys'  => 'foto imagen video portada cabecera hero fondo',
            'steps' => array(
                array( 'list', 'Abre el apartado 1, la cabecera de la página.' ),
                array( 'field', 'En Fondo de la cabecera elige Imagen o Video y pulsa Seleccionar.' ),
                array( 'field', 'Con Video no hay que hacer nada más: el editor crea solo la imagen de respaldo (el primer cuadro del video).' ),
                array( 'save', 'Revisa la vista previa y guarda.' ),
            ),
        ),
        // v9.26.0
        array(
            'id'    => 'encuadre',
            'title' => 'Encuadre de la foto',
            'keys'  => 'encuadre foto imagen recorte recortada centrar zoom acercar mover posicion movil telefono',
            'steps' => array(
                array( 'field', 'Junto a la imagen, pulsa Encuadre de la foto.' ),
                array( 'field', 'Arrastra la foto en las vistas PC, Tablet o Teléfono, o pulsa en la foto completa el punto que siempre debe verse. Acercamiento agranda la foto (no en cabeceras).' ),
                array( 'field', 'Si en teléfonos debe verse otra parte, marca Encuadre distinto en teléfonos. Restablecer vuelve al original.' ),
                array( 'save', 'Pulsa Aplicar encuadre y después Guardar cambios.' ),
            ),
        ),
        array(
            'id'    => 'botones',
            'title' => 'Botones principal y secundario',
            'keys'  => 'boton botones enlace link destino principal secundario ocultar mostrar llamado',
            'steps' => array(
                array( 'list', 'Abre el apartado que tiene el botón (por ejemplo, la cabecera de Inicio).' ),
                array( 'field', 'Cambia el texto y elige en ¿A dónde lleva? una página o apartado del sitio. Para otro sitio web, elige Enlace personalizado o externo y pega la dirección.' ),
                array( 'field', 'El interruptor Visible / Oculto quita el botón de la página sin borrar su texto.' ),
                array( 'save', 'Guarda.' ),
            ),
        ),
        array(
            'id'    => 'proyectos',
            'title' => 'Proyectos vigentes y trayectoria',
            'keys'  => 'proyecto proyectos vigente vigentes trayectoria finalizado cerrado estado mover',
            'steps' => array(
                array( 'list', 'En Proyectos abre «Proyectos vigentes» o «Trayectoria»: ambos muestran la misma lista de proyectos.' ),
                array( 'field', 'Usa Todos / Vigentes / Trayectoria para filtrar. La etiqueta de cada proyecto (Vigente o Trayectoria) se pulsa para cambiarlo de lugar.' ),
                array( 'save', 'Guarda: el proyecto aparece arriba (vigente) o en la línea de tiempo (finalizado).' ),
            ),
        ),
        array(
            'id'    => 'anadir',
            'title' => 'Añadir un registro',
            'keys'  => 'añadir agregar nuevo persona publicacion proyecto curso tarjeta registro',
            'steps' => array(
                array( 'add', 'Pulsa el botón Añadir de la lista (Añadir persona, Añadir proyecto…).' ),
                array( 'field', 'Completa los campos marcados como Obligatorio.' ),
                array( 'save', 'Guarda y revisa la vista previa.' ),
            ),
        ),
        array(
            'id'    => 'orden',
            'title' => 'Ordenar u ocultar apartados',
            'keys'  => 'ordenar mover arrastrar ocultar eliminar quitar apartado seccion orden',
            'steps' => array(
                array( 'list', 'Arrastra un apartado desde su asa ≡ (el recuadro de la izquierda). También puedes pulsar el asa y usar las flechas ↑ ↓.' ),
                array( 'list2', 'Eliminar lo oculta de la página. Restaurar lo trae de vuelta.' ),
                array( 'save', 'Guarda para aplicar el cambio.' ),
            ),
        ),
        array(
            'id'    => 'enfoque',
            'title' => 'Editar un apartado sin perderte',
            'keys'  => 'editar seccion apartado enfoque lista volver todas las secciones vista general barra fija',
            'steps' => array(
                array( 'list', 'Pulsa un apartado y luego Editar sección: la lista se pliega y queda a la vista solo ese apartado.' ),
                array( 'tabs', 'Arriba aparece una tira con todos los apartados: pulsa otro para saltar a él.' ),
                array( 'list2', 'Pulsa otra vez el apartado (o Todas las secciones) para volver a la lista.' ),
                array( 'prev', 'La vista previa empieza en General, con la página completa, y te acompaña al bajar. Cambia a Apartado para ver solo uno.' ),
                array( 'save', 'En PC, la barra Estado de la página queda fija arriba y puedes guardar sin volver al inicio. En tablet y celular usa el botón Guardar de abajo.' ),
            ),
        ),
        array(
            'id'    => 'movil',
            'title' => 'Revisar en móvil',
            'keys'  => 'movil celular telefono tablet responsive vista previa pantalla',
            'steps' => array(
                array( 'dev', 'Cambia la vista previa a Móvil o Tablet.' ),
                array( 'prev', 'Revisa textos largos y fotos.' ),
                array( 'dev', 'Vuelve a PC. Esto no cambia la web.' ),
            ),
        ),
        array(
            'id'    => 'abrir-cerrar',
            'title' => 'Abrir y cerrar una ficha',
            'keys'  => 'abrir cerrar ficha flecha editar elemento persona proyecto publicacion x',
            'steps' => array(
                array( 'list', 'Pulsa un elemento de la lista para abrir su ficha. La flecha apunta hacia abajo.' ),
                array( 'field', 'Edita los campos. Los cambios se ven al instante en la vista previa.' ),
                array( 'list2', 'Pulsa el mismo elemento otra vez para cerrar la ficha.' ),
            ),
        ),
        array(
            'id'    => 'personas-grupos',
            'title' => 'Personas y grupos (Quiénes somos)',
            'keys'  => 'quienes somos persona investigador estudiante grupo programa magister doctorado apartado duplicar eliminar',
            'steps' => array(
                array( 'list', 'En Quiénes somos abre Personas y grupos y pulsa Editar. Luego abre el apartado, por ejemplo Investigadores.' ),
                array( 'add', 'Pulsa Añadir persona. Para separar por programa usa Organizar en grupos.' ),
                array( 'field', 'Para cambiar a alguien de grupo, abre su ficha y elige Grupo / programa, o arrástralo al otro grupo.' ),
                array( 'list2', 'Título, Duplicar y Eliminar del apartado están al final, en Ajustes del apartado.' ),
                array( 'save', 'Guarda los cambios.' ),
            ),
        ),
        array(
            'id'    => 'fechas',
            'title' => 'Noticias y fechas',
            'keys'  => 'noticia comunicacion fecha calendario orden reciente',
            'steps' => array(
                array( 'add', 'En Noticias o Comunicaciones pulsa Añadir.' ),
                array( 'field', 'Elige la fecha en el calendario.' ),
                array( 'save', 'Al guardar, la lista se ordena sola: lo más reciente primero, también en la web.' ),
            ),
        ),
        array(
            'id'    => 'dos-personas',
            'title' => 'Dos personas editando a la vez',
            'keys'  => 'dos personas otra persona editando aviso guardar igual recargar conflicto',
            'steps' => array(
                array( 'tabs', 'Si alguien más tiene la misma página abierta, arriba aparece un aviso con su nombre.' ),
                array( 'save', 'Si esa persona guardó antes que tú, al guardar se abre una ventana: Seguir editando, Recargar sin guardar o Guardar igual.' ),
                array( 'list', 'Si algo se reemplazó por error, Historial de cambios guarda ambas versiones.' ),
            ),
        ),
        array(
            'id'    => 'deshacer',
            'title' => 'Deshacer un cambio',
            'keys'  => 'deshacer historial restaurar rollback error volver version anterior',
            'steps' => array(
                array( 'tabs', 'Abre Historial de cambios en el menú Contenido GEP.' ),
                array( 'list', 'Elige la versión y pulsa Ver cambios visualmente.' ),
                array( 'save', 'Pulsa Restaurar versión. Lo actual queda guardado.' ),
            ),
        ),
        // v10.0.0
        array(
            'id'    => 'guardado-seguro',
            'title' => 'Si se corta la sesión o Internet',
            'keys'  => 'sesion vencida caducado expiro internet conexion wifi perdi cambios no guardo error guardar',
            'steps' => array(
                array( 'save', 'Al pulsar Guardar cambios, el editor primero comprueba tu sesión y la conexión.' ),
                array( 'save', 'Si algo falla, aparece un aviso y no se envía nada: tus cambios siguen en pantalla. No cierres ni recargues la pestaña.' ),
                array( 'save', 'Sesión terminada: pulsa Iniciar sesión (se abre otra pestaña), entra y vuelve para pulsar Ya entré, guardar. Sin Internet: revisa la conexión y pulsa Reintentar.' ),
            ),
        ),
        array(
            'id'    => 'archivos-en-uso',
            'title' => 'Fotos y archivos en uso',
            'keys'  => 'borrar eliminar foto imagen archivo mediateca en uso se usa en no se puede borrar',
            'steps' => array(
                array( 'list', 'En Medios, al abrir un archivo aparece Se usa en: con las páginas que lo muestran.' ),
                array( 'field', 'Un archivo en uso no se puede borrar: aparece un aviso que dice dónde está.' ),
                array( 'save', 'Para borrarlo, primero cámbialo o quítalo en esa página, guarda, y luego bórralo de Medios.' ),
            ),
        ),
        array(
            'id'    => 'administracion',
            'title' => 'Diagnóstico y acceso al sitio (administradores)',
            'keys'  => 'diagnostico error problema revisar administrador acceso clave usuario privado publico contraseña sitio',
            'steps' => array(
                array( 'tabs', 'Contenido GEP → Diagnóstico revisa páginas, contenido, archivos, espacio y respaldo. Copiar informe lo deja listo para enviarlo a soporte.' ),
                array( 'tabs', 'Contenido GEP → Acceso al sitio: marca Pedir usuario y clave para que el sitio sea privado, o desmárcalo para volverlo público.' ),
                array( 'save', 'Estas dos pantallas solo aparecen para administradores.' ),
            ),
        ),
    );

    /**
     * Permite que otros módulos (por ejemplo Servicios) añadan tutoriales.
     */
    return apply_filters( 'gep_help_tutorials', $tutorials );
}

/**
 * Problemas frecuentes: síntoma → qué hacer.
 */
function gep_help_fixes() {
    return array(
        array( 'Guardar cambios no se activa', 'Falta un campo Obligatorio o no hay cambios.' ),
        array( 'Guardé algo por error', 'Historial de cambios › Restaurar versión.' ),
        array( 'La web no muestra el cambio', 'Guarda y abre «Sitio público» en el menú lateral.' ),
        array( 'No encuentro un registro', 'Borra el texto del buscador de la lista.' ),
        array( '¿Cómo cierro una ficha?', 'Pulsa otra vez el mismo elemento de la lista.' ),
        array( 'No veo Duplicar ni Eliminar de un apartado', 'Están al final del apartado, en Ajustes del apartado.' ),
        array( 'Aviso: otra persona está editando', 'Pónganse de acuerdo. Si la otra persona ya guardó, recarga antes de seguir.' ),
        array( 'No encuentro una foto', 'Pulsa Carpetas de imágenes y busca por nombre o por carpeta.' ),
    );
}

/**
 * Cambia la función de dibujo del submenú Ayuda por la nueva.
 */
function gep_help_take_over_menu() {
    $hook = get_plugin_page_hookname( 'gep-content-help', 'gep-content-editor' );
    if ( ! $hook ) {
        return;
    }
    remove_action( $hook, 'gep_demo_render_help_page' );
    add_action( $hook, 'gep_help_render_page' );
}
add_action( 'admin_menu', 'gep_help_take_over_menu', 20 );

/**
 * Datos que usa assets/help.js.
 */
function gep_help_js_data( $context ) {
    $tutorials = array();
    foreach ( gep_help_tutorials() as $tutorial ) {
        $steps = array();
        foreach ( (array) $tutorial['steps'] as $step ) {
            $steps[] = array( sanitize_key( $step[0] ), wp_strip_all_tags( $step[1] ) );
        }
        $tutorials[] = array(
            'id'    => sanitize_key( $tutorial['id'] ),
            'title' => wp_strip_all_tags( $tutorial['title'] ),
            'keys'  => wp_strip_all_tags( $tutorial['keys'] ),
            'steps' => $steps,
        );
    }

    $force_tour = isset( $_GET['gep_tour'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['gep_tour'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    return array(
        'context'   => $context,
        'tutorials' => $tutorials,
        'helpUrl'   => admin_url( 'admin.php?page=gep-content-help' ),
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'gep_help_tour' ),
        // El recorrido solo se abre cuando se pide (Ayuda > Ver recorrido, que agrega &gep_tour=1).
        'showTour'  => 'editor' === $context && $force_tour,
    );
}

/**
 * Carga los estilos y scripts de ayuda en la página Ayuda y en el editor.
 */
function gep_help_enqueue( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }

    $context = '';
    if ( 'contenido-gep_page_gep-content-help' === $screen->id ) {
        $context = 'help';
        // Quita los estilos de la Ayuda anterior.
        wp_dequeue_style( 'gep-demo-help' );
    } elseif ( 'toplevel_page_gep-content-editor' === $screen->id ) {
        $context = 'editor';
    }
    if ( ! $context ) {
        return;
    }

    // Rutas calculadas desde este archivo (includes/ → carpeta del plugin).
    $plugin_dir = dirname( __DIR__ );
    $css_file   = $plugin_dir . '/assets/help.css';
    $js_file    = $plugin_dir . '/assets/help.js';
    $js_url     = plugins_url( 'assets/help.js', $plugin_dir . '/gep-editor-demo.php' );

    // El CSS va en línea, como la Ayuda anterior: no depende de la URL del
    // plugin ni de la caché del navegador o del servidor.
    wp_enqueue_style( 'dashicons' );
    wp_register_style( 'gep-help', false, array(), GEP_EDITOR_VERSION );
    wp_enqueue_style( 'gep-help' );
    if ( is_readable( $css_file ) ) {
        wp_add_inline_style( 'gep-help', (string) file_get_contents( $css_file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    }

    // La versión incluye la fecha del archivo para que cada actualización se descargue de nuevo.
    $js_ver = GEP_EDITOR_VERSION . '.' . ( file_exists( $js_file ) ? filemtime( $js_file ) : '0' );
    wp_enqueue_script( 'gep-help', $js_url, array(), $js_ver, true );
    wp_add_inline_script(
        'gep-help',
        'window.gepHelpData = ' . wp_json_encode( gep_help_js_data( $context ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';',
        'before'
    );
}
add_action( 'admin_enqueue_scripts', 'gep_help_enqueue', 20 );

/**
 * Guarda que el usuario ya vio (o saltó) el recorrido de bienvenida.
 */
function gep_help_ajax_tour_done() {
    check_ajax_referer( 'gep_help_tour', 'nonce' );
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_send_json_error( null, 403 );
    }
    update_user_meta( get_current_user_id(), 'gep_help_tour_done', 1 );
    wp_send_json_success();
}
add_action( 'wp_ajax_gep_help_tour_done', 'gep_help_ajax_tour_done' );

/**
 * Iconos de línea (SVG en el propio HTML, sin peticiones extra).
 */
function gep_help_icon( $name ) {
    $paths = array(
        'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>',
        'sliders'  => '<path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'file'     => '<path d="M14 3H6.5A1.5 1.5 0 0 0 5 4.5v15A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'help'     => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.4 2.4c-.6.3-1 .9-1 1.6v.4"/><path d="M12 17h.01"/>',
        'keyboard' => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><path d="M6.5 10h.01M10 10h.01M14 10h.01M17.5 10h.01M7.5 14h9"/>',
        'bulb'     => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.6 10.8c.7.5 1.1 1.3 1.1 2.2h5c0-.9.4-1.7 1.1-2.2A6 6 0 0 0 12 3z"/>',
        'book'     => '<path d="M3 5.5A1.5 1.5 0 0 1 4.5 4H9a3 3 0 0 1 3 3v13a2 2 0 0 0-2-2H3z"/><path d="M21 5.5A1.5 1.5 0 0 0 19.5 4H15a3 3 0 0 0-3 3v13a2 2 0 0 1 2-2h7z"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'pencil'   => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'history'  => '<path d="M3 12a9 9 0 1 0 2.6-6.4L3 8"/><path d="M3 3v5h5"/><path d="M12 7.5V12l3 2"/>',
        'cap'      => '<path d="M2 9.5 12 5l10 4.5L12 14z"/><path d="M6 11.5V16c0 1.4 2.7 3 6 3s6-1.6 6-3v-4.5"/><path d="M22 9.5V15"/>',
        'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'check'    => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'gear'     => '<circle cx="12" cy="12" r="3"/><path d="M12 2.8v2.4M12 18.8v2.4M2.8 12h2.4M18.8 12h2.4M5.5 5.5l1.7 1.7M16.8 16.8l1.7 1.7M5.5 18.5l1.7-1.7M16.8 7.2l1.7-1.7"/><circle cx="12" cy="12" r="6.5"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'monitor'  => '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/>',
        'tablet'   => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M11 18h2"/>',
        'phone'    => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/>',
        'expand'   => '<path d="M15 3h6v6M9 21H3v-6M21 3l-6.5 6.5M3 21l6.5-6.5M9 3H3v6M15 21h6v-6M3 3l6.5 6.5M21 21l-6.5-6.5"/>',
        'restore'  => '<path d="M3 12a9 9 0 1 0 2.6-6.4L3 8"/><path d="M3 3v5h5"/>',
        'list'     => '<path d="M8.5 6H21M8.5 12H21M8.5 18H21M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'chevron'  => '<path d="m9 6 6 6-6 6"/>',
        'play'     => '<circle cx="12" cy="12" r="9"/><path d="m10 8.5 5 3.5-5 3.5z"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'move'     => '<path d="M12 3v18M3 12h18M9 6l3-3 3 3M9 18l3 3 3-3M6 9l-3 3 3 3M18 9l3 3-3 3"/>',
        'copy'     => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'shield'   => '<path d="M12 3 4.5 6v6c0 4.8 3.3 7.9 7.5 9 4.2-1.1 7.5-4.2 7.5-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'map'      => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2z"/><path d="M9 4v14M15 6v14"/>',
        'compare'  => '<rect x="3" y="4" width="7.5" height="16" rx="1.5"/><rect x="13.5" y="4" width="7.5" height="16" rx="1.5"/><path d="M6 9h2M16.5 9h2M6 13h2M16.5 13h2"/>',
        'note'     => '<path d="M5 4h14v11l-5 5H5z"/><path d="M14 20v-5h5M8.5 9h7M8.5 12.5h4"/>',
    );
    $inner = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['help'];
    return '<svg class="gep-ayuda-ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}

/**
 * Correo de soporte del recuadro "¿Necesitas más ayuda?".
 * v9.24.0: se configura desde la propia Ayuda (solo administradores) y se
 * guarda en la opción gep_help_support_email. Si no hay correo guardado se usa
 * la constante GEP_HELP_SUPPORT_EMAIL o, en su defecto, el correo del
 * administrador de WordPress. El filtro gep_help_support_email sigue activo.
 */
function gep_help_default_support_email() {
    return defined( 'GEP_HELP_SUPPORT_EMAIL' ) ? GEP_HELP_SUPPORT_EMAIL : get_option( 'admin_email' );
}

function gep_help_support_email() {
    $saved = sanitize_email( (string) get_option( 'gep_help_support_email', '' ) );
    $email = ( $saved && is_email( $saved ) ) ? $saved : gep_help_default_support_email();
    return sanitize_email( apply_filters( 'gep_help_support_email', $email ) );
}

/**
 * Guarda el correo de soporte (formulario de la Ayuda).
 */
function gep_help_save_support_email() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos para cambiar el correo de soporte.', 'gep-editor' ), 403 );
    }
    check_admin_referer( 'gep_help_support_email', 'gep_help_support_nonce' );

    $raw    = isset( $_POST['gep_help_support_email'] ) ? trim( wp_unslash( (string) $_POST['gep_help_support_email'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se valida con is_email().
    $status = 'saved';
    if ( '' === $raw ) {
        delete_option( 'gep_help_support_email' );
        $status = 'reset';
    } elseif ( is_email( $raw ) ) {
        update_option( 'gep_help_support_email', sanitize_email( $raw ), false );
    } else {
        $status = 'invalid';
    }

    $back = add_query_arg(
        array(
            'page'        => 'gep-content-help',
            'gep_support' => $status,
        ),
        admin_url( 'admin.php' )
    );
    wp_safe_redirect( $back . '#gep-ayuda-support' );
    exit;
}
add_action( 'admin_post_gep_help_save_support_email', 'gep_help_save_support_email' );

/**
 * Recuadro de soporte de la barra lateral de la Ayuda.
 */
function gep_help_render_support_box( $tour_url, $support, $variant = '' ) {
    $sfx      = $variant ? '-' . sanitize_key( $variant ) : '';
    $can_edit = current_user_can( 'manage_options' );
    $status   = isset( $_GET['gep_support'] ) ? sanitize_key( wp_unslash( $_GET['gep_support'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo muestra un aviso.
    $messages = array(
        'saved'   => array( 'is-ok', 'Correo de soporte guardado.' ),
        'reset'   => array( 'is-ok', 'Se volvió a usar el correo del administrador.' ),
        'invalid' => array( 'is-error', 'Ese correo no tiene un formato válido. No se guardó.' ),
    );
    ?>
    <div class="gep-ayuda-support<?php echo $variant ? ' is-' . esc_attr( sanitize_key( $variant ) ) : ''; ?>" id="gep-ayuda-support<?php echo esc_attr( $sfx ); ?>">
        <div class="gep-ayuda-support-copy">
            <?php echo gep_help_icon( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <div><strong>¿Necesitas más ayuda?</strong><span>Mira el recorrido del editor o escribe a soporte.</span></div>
        </div>
        <a class="gep-ayuda-btn is-ghost is-small" href="<?php echo esc_url( $tour_url ); ?>"><?php echo gep_help_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Ver recorrido</a>
        <?php if ( $support ) : ?>
            <a class="gep-ayuda-btn is-ghost is-small gep-ayuda-support-link" href="<?php echo esc_url( 'mailto:' . $support ); ?>" title="<?php echo esc_attr( $support ); ?>"><?php echo gep_help_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Contactar soporte</a>
            <small class="gep-ayuda-support-email"><?php echo esc_html( $support ); ?></small>
        <?php endif; ?>
        <?php if ( $status && isset( $messages[ $status ] ) && $can_edit ) : ?>
            <p class="gep-ayuda-support-msg <?php echo esc_attr( $messages[ $status ][0] ); ?>" role="status"><?php echo esc_html( $messages[ $status ][1] ); ?></p>
        <?php endif; ?>
        <?php if ( $can_edit ) : ?>
            <details class="gep-ayuda-support-edit"<?php echo 'invalid' === $status ? ' open' : ''; ?>>
                <summary><?php echo gep_help_icon( 'gear' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Cambiar correo de soporte</summary>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gep-ayuda-support-form">
                    <input type="hidden" name="action" value="gep_help_save_support_email">
                    <?php wp_nonce_field( 'gep_help_support_email', 'gep_help_support_nonce' ); ?>
                    <label for="gep-help-support-email<?php echo esc_attr( $sfx ); ?>">Correo de contacto</label>
                    <input type="email" id="gep-help-support-email<?php echo esc_attr( $sfx ); ?>" name="gep_help_support_email" value="<?php echo esc_attr( (string) get_option( 'gep_help_support_email', '' ) ); ?>" placeholder="<?php echo esc_attr( gep_help_default_support_email() ); ?>" autocomplete="email">
                    <small>Déjalo vacío para usar el correo del administrador. Solo lo ven y cambian administradores.</small>
                    <button type="submit" class="gep-ayuda-btn is-primary is-small"><?php echo gep_help_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Guardar correo</button>
                </form>
            </details>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Encabezado común de cada sección.
 */
function gep_help_section_head( $icon, $title, $text, $id ) {
    ?>
    <div class="gep-ayuda-head">
        <span class="gep-ayuda-tile"><?php echo gep_help_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG fijo del plugin. ?></span>
        <div>
            <h2 id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></h2>
            <p><?php echo esc_html( $text ); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Página Ayuda.
 */
function gep_help_render_page() {
    $editor_url  = admin_url( 'admin.php?page=gep-content-editor' );
    $history_url = admin_url( 'admin.php?page=gep-content-history' );
    $tour_url    = add_query_arg( 'gep_tour', '1', $editor_url );
    $support     = gep_help_support_email();
    $version     = GEP_EDITOR_VERSION;

    $nav = array(
        array( 'empezar', 'home', 'Empezar' ),
        array( 'tutoriales', 'play', 'Tutoriales' ),
        array( 'controles', 'sliders', 'Controles' ),
        array( 'historial', 'clock', 'Historial' ),
        array( 'listas', 'file', 'Listas' ),
        array( 'dudas', 'help', 'Dudas rápidas' ),
        array( 'atajos', 'keyboard', 'Atajos' ),
        array( 'consejos', 'bulb', 'Consejos' ),
    );

    $steps = array(
        array( 'file', 'Elige página', 'Inicio, Quiénes somos, Docencia, Proyectos, Servicios, Publicaciones o Contacto.' ),
        array( 'pencil', 'Edita', 'Abre solo el apartado que necesitas y cambia sus campos.' ),
        array( 'eye', 'Revisa', 'Comprueba la vista previa en PC, tablet y móvil.' ),
        array( 'check', 'Guarda', 'Pulsa Guardar cambios y verifica la página real.' ),
    );

    $controls = array(
        array( 'check', 'Guardar cambios', 'Publica lo editado y crea una versión en el historial.', 'is-solid' ),
        array( 'clock', 'Todo guardado', 'Indica si existen cambios pendientes.', '' ),
        array( 'restore', 'Restaurar', 'Devuelve los campos al estado de referencia del editor.', '' ),
        array( 'external', 'Sitio público', 'En el menú lateral: abre la página publicada en otra pestaña.', '' ),
        array( 'devices', 'Vista previa', 'Cambia únicamente el tamaño de la vista previa.', '' ),
        array( 'expand', 'Expandir / contraer', 'Ordena el formulario sin alterar el contenido.', '' ),
    );

    $map = array(
        array( 'tabs', 'Pestañas: elige qué página editar.' ),
        array( 'save', 'Guardar cambios: publica y crea una versión en el historial.' ),
        array( 'list', 'Apartados: ábrelos para editar. Arrastra para ordenar.' ),
        array( 'add', 'Añadir: crea un apartado o registro nuevo.' ),
        array( 'dev', 'PC / Tablet / Móvil: solo cambia el tamaño de la vista previa.' ),
    );

    // Acciones frecuentes: abren un tutorial o saltan a una sección.
    $actions = array(
        array( 'file', 'Cómo editar cada sección', 'Inicio, Quiénes somos, Docencia…', 'tutorial', 'texto' ),
        array( 'list', 'Trabajar con listas', 'Añadir, editar y ordenar elementos.', 'anchor', 'listas' ),
        array( 'history', 'Recuperar versiones', 'Usa el historial de cambios.', 'tutorial', 'deshacer' ),
        array( 'keyboard', 'Atajos de teclado', 'Acelera tu flujo de trabajo.', 'anchor', 'atajos' ),
        array( 'help', 'Solución a problemas comunes', 'Errores, campos obligatorios, vista previa…', 'anchor', 'dudas' ),
        array( 'bulb', 'Consejos de uso', 'Buenas prácticas para mejores resultados.', 'anchor', 'consejos' ),
    );

    $history_flow = array(
        array( 'check', 'Guardar', 'Crea una versión' ),
        array( 'eye', 'Ver cambios', 'Antes y después' ),
        array( 'compare', 'Comparar', 'Versión A y B' ),
        array( 'restore', 'Restaurar', 'Con punto de seguridad' ),
    );

    $lists = array(
        array( 'search', 'Buscar', 'Por nombre, título o año.' ),
        array( 'pencil', 'Editar', 'Abre solo un registro.' ),
        array( 'plus', 'Añadir', 'Crea un registro nuevo.' ),
        array( 'copy', 'Duplicar', 'Parte de uno parecido.' ),
        array( 'move', 'Reordenar', 'Arrastra para cambiar el orden.' ),
        array( 'trash', 'Eliminar', 'Revisa la vista previa antes de guardar.' ),
    );

    $shortcuts = array(
        array( array( 'Ctrl', 'S' ), 'Guardar cambios en el editor (⌘ + S en Mac).' ),
        array( array( 'Ctrl', 'K' ), 'Buscar en esta ayuda (⌘ + K en Mac).' ),
        array( array( 'Esc' ), 'Cerrar un tutorial, el globo ? o el recorrido.' ),
        array( array( '← / →' ), 'Pasar los pasos de un tutorial.' ),
        array( array( 'Enter' ), 'Abrir o cerrar el apartado seleccionado en el editor.' ),
        array( array( '↑ / ↓' ), 'Con el asa ≡ seleccionada, subir o bajar el apartado o elemento.' ),
    );

    $tips = array(
        array( 'phone', 'Revisa en Móvil', 'La mayoría de las visitas llegan desde el celular.' ),
        array( 'check', 'Guarda seguido', 'Cada guardado es un punto al que puedes volver.' ),
        array( 'note', 'Anota los cambios grandes', 'En el historial puedes añadir una nota a la versión.' ),
        array( 'eye', 'Oculta antes de borrar', 'Un apartado oculto se puede restaurar.' ),
    );
    ?>
    <div class="wrap gep-ayuda">
        <aside class="gep-ayuda-side" aria-label="Secciones de ayuda">
            <div class="gep-ayuda-brand">
                <span class="gep-ayuda-brand-mark"><?php echo gep_help_icon( 'cap' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                <strong>GEP Editor</strong>
                <span class="gep-ayuda-pill">v<?php echo esc_html( $version ); ?></span>
            </div>
            <nav class="gep-ayuda-nav">
                <?php foreach ( $nav as $index => $item ) : ?>
                    <a href="#gep-a-<?php echo esc_attr( $item[0] ); ?>" data-target="gep-a-<?php echo esc_attr( $item[0] ); ?>" class="<?php echo 0 === $index ? 'is-active' : ''; ?>"><?php echo gep_help_icon( $item[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $item[2] ); ?></span></a>
                <?php endforeach; ?>
            </nav>
            <?php gep_help_render_support_box( $tour_url, $support ); ?>
        </aside>

        <div class="gep-ayuda-main">
            <header class="gep-ayuda-hero" id="gep-a-empezar-top">
                <div class="gep-ayuda-hero-row">
                    <div>
                        <span class="gep-ayuda-kicker">GEP Editor · v<?php echo esc_html( $version ); ?></span>
                        <h1>Centro de ayuda</h1>
                        <p>Todo lo que necesitas para editar, revisar y recuperar contenido sin tocar código.</p>
                    </div>
                    <div class="gep-ayuda-hero-actions">
                        <a class="gep-ayuda-btn is-primary" href="<?php echo esc_url( $editor_url ); ?>"><?php echo gep_help_icon( 'pencil' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Abrir editor</a>
                        <a class="gep-ayuda-btn is-ghost" href="<?php echo esc_url( $history_url ); ?>"><?php echo gep_help_icon( 'history' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Abrir historial</a>
                    </div>
                </div>
                <label class="gep-ayuda-search" for="gep-ayuda-q">
                    <?php echo gep_help_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <span class="screen-reader-text">Buscar en la ayuda</span>
                    <input id="gep-ayuda-q" type="search" placeholder="Buscar en la ayuda… (ej. historial, listas, video, móvil)" autocomplete="off">
                    <kbd class="gep-ayuda-kbd-hint">Ctrl + K</kbd>
                </label>
                <p class="gep-ayuda-noresults" id="gep-ayuda-noresults" hidden>Nada coincide. Prueba con otra palabra<?php if ( $support ) : ?> o <a href="<?php echo esc_url( 'mailto:' . $support ); ?>">escribe a soporte</a><?php endif; ?>.</p>
            </header>

            <section class="gep-ayuda-card" id="gep-a-empezar" aria-labelledby="gep-a-empezar-h">
                <?php gep_help_section_head( 'cap', 'Editar una página en 4 pasos', 'Sigue estos pasos para editar y publicar contenido rápidamente.', 'gep-a-empezar-h' ); ?>
                <ol class="gep-ayuda-steps">
                    <?php foreach ( $steps as $index => $step ) : ?>
                        <li class="gep-ayuda-item" data-keys="<?php echo esc_attr( $step[1] . ' ' . $step[2] ); ?>">
                            <span class="gep-ayuda-num"><?php echo esc_html( $index + 1 ); ?></span>
                            <span class="gep-ayuda-round"><?php echo gep_help_icon( $step[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                            <strong><?php echo esc_html( $step[1] ); ?></strong>
                            <small><?php echo esc_html( $step[2] ); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <section class="gep-ayuda-card" id="gep-a-tutoriales" aria-labelledby="gep-a-tutoriales-h">
                <?php gep_help_section_head( 'play', 'Tutoriales de 1 minuto', 'Míralos paso a paso. Cada uno señala dónde hacer clic.', 'gep-a-tutoriales-h' ); ?>
                <div class="gep-ayuda-tuts">
                    <?php foreach ( gep_help_tutorials() as $tutorial ) : ?>
                        <button type="button" class="gep-ayuda-tut gep-ayuda-item" data-gep-tutorial="<?php echo esc_attr( $tutorial['id'] ); ?>" data-keys="<?php echo esc_attr( $tutorial['title'] . ' ' . $tutorial['keys'] ); ?>">
                            <span class="gep-ayuda-thumb" data-zone="<?php echo esc_attr( $tutorial['steps'][0][0] ); ?>" aria-hidden="true"><i></i><i></i><i></i><i></i><b></b><em></em></span>
                            <span class="gep-ayuda-tut-meta"><strong><?php echo esc_html( $tutorial['title'] ); ?></strong><small><?php echo esc_html( count( $tutorial['steps'] ) . ' pasos' ); ?></small></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="gep-ayuda-card" id="gep-a-controles" aria-labelledby="gep-a-controles-h">
                <?php gep_help_section_head( 'gear', 'Controles principales', 'Conoce qué hace cada botón del editor y cómo te ayudan en tu trabajo.', 'gep-a-controles-h' ); ?>
                <div class="gep-ayuda-grid3">
                    <?php foreach ( $controls as $control ) : ?>
                        <div class="gep-ayuda-ctrl gep-ayuda-item" data-keys="<?php echo esc_attr( $control[1] . ' ' . $control[2] ); ?>">
                            <?php if ( 'devices' === $control[0] ) : ?>
                                <span class="gep-ayuda-devices"><span><?php echo gep_help_icon( 'monitor' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo gep_help_icon( 'tablet' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo gep_help_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></span>
                            <?php else : ?>
                                <span class="gep-ayuda-tile is-lg <?php echo esc_attr( $control[3] ); ?>"><?php echo gep_help_icon( $control[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                            <?php endif; ?>
                            <div><strong><?php echo esc_html( $control[1] ); ?></strong><small><?php echo esc_html( $control[2] ); ?></small></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <details class="gep-ayuda-map-wrap gep-ayuda-item" data-keys="mapa editor donde esta pestañas guardar apartados añadir vista previa">
                    <summary><?php echo gep_help_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Ver el mapa del editor <?php echo gep_help_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></summary>
                    <div class="gep-ayuda-map">
                        <div class="gep-ayuda-map-ui" aria-hidden="true">
                            <div class="gep-ayuda-map-top"><span class="is-on">Quiénes somos</span><span>Proyectos</span><span>Publicaciones</span><em>Guardar cambios</em></div>
                            <div class="gep-ayuda-map-cols">
                                <div class="gep-ayuda-map-list"><i>Encabezado</i><i>Equipo</i><i>Directorio</i><i class="is-add">+ Añadir</i></div>
                                <div class="gep-ayuda-map-prev"><span><b class="is-on">PC</b><b>Tablet</b><b>Móvil</b></span><i></i></div>
                            </div>
                            <?php foreach ( $map as $index => $spot ) : ?>
                                <button type="button" class="gep-ayuda-hot" data-zone="<?php echo esc_attr( $spot[0] ); ?>" data-index="<?php echo esc_attr( $index ); ?>" tabindex="-1"><?php echo esc_html( $index + 1 ); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <ol class="gep-ayuda-legend">
                            <?php foreach ( $map as $index => $spot ) : ?>
                                <li data-index="<?php echo esc_attr( $index ); ?>"><button type="button"><b><?php echo esc_html( $index + 1 ); ?></b><span><?php echo esc_html( $spot[1] ); ?></span></button></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </details>
            </section>

            <section class="gep-ayuda-card" id="gep-a-acciones" aria-labelledby="gep-a-acciones-h">
                <?php gep_help_section_head( 'bulb', 'Acciones frecuentes', 'Resuelve rápidamente las dudas más comunes sobre el uso de GEP Editor.', 'gep-a-acciones-h' ); ?>
                <div class="gep-ayuda-grid3 is-rows">
                    <?php foreach ( $actions as $action ) : ?>
                        <?php if ( 'tutorial' === $action[3] ) : ?>
                            <button type="button" class="gep-ayuda-row gep-ayuda-item" data-gep-tutorial="<?php echo esc_attr( $action[4] ); ?>" data-keys="<?php echo esc_attr( $action[1] . ' ' . $action[2] ); ?>">
                        <?php else : ?>
                            <a class="gep-ayuda-row gep-ayuda-item" href="#gep-a-<?php echo esc_attr( $action[4] ); ?>" data-keys="<?php echo esc_attr( $action[1] . ' ' . $action[2] ); ?>">
                        <?php endif; ?>
                            <span class="gep-ayuda-tile is-sm"><?php echo gep_help_icon( $action[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                            <span class="gep-ayuda-row-copy"><strong><?php echo esc_html( $action[1] ); ?></strong><small><?php echo esc_html( $action[2] ); ?></small></span>
                            <?php echo gep_help_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        <?php echo 'tutorial' === $action[3] ? '</button>' : '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="gep-ayuda-card" id="gep-a-historial" aria-labelledby="gep-a-historial-h">
                <div class="gep-ayuda-head-row">
                    <?php gep_help_section_head( 'clock', 'Historial de cambios', 'Cada guardado crea una versión que puedes revisar y recuperar.', 'gep-a-historial-h' ); ?>
                    <a class="gep-ayuda-btn is-ghost is-small" href="<?php echo esc_url( $history_url ); ?>">Ir al historial <?php echo gep_help_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
                </div>
                <ol class="gep-ayuda-steps is-flow">
                    <?php foreach ( $history_flow as $index => $flow ) : ?>
                        <li class="gep-ayuda-item" data-keys="<?php echo esc_attr( 'historial version ' . $flow[1] . ' ' . $flow[2] ); ?>">
                            <span class="gep-ayuda-num"><?php echo esc_html( $index + 1 ); ?></span>
                            <span class="gep-ayuda-round"><?php echo gep_help_icon( $flow[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                            <strong><?php echo esc_html( $flow[1] ); ?></strong>
                            <small><?php echo esc_html( $flow[2] ); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <div class="gep-ayuda-note gep-ayuda-item" data-keys="restaurar rollback seguro deshacer">
                    <?php echo gep_help_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <span><strong>Restaurar no borra la historia.</strong> Lo que tenías antes queda guardado como punto de seguridad.</span>
                    <button type="button" class="gep-ayuda-link" data-gep-tutorial="deshacer"><?php echo gep_help_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Ver tutorial</button>
                </div>
            </section>

            <section class="gep-ayuda-card" id="gep-a-listas" aria-labelledby="gep-a-listas-h">
                <?php gep_help_section_head( 'file', 'Listas', 'Personas, proyectos, publicaciones y cursos se manejan igual.', 'gep-a-listas-h' ); ?>
                <div class="gep-ayuda-grid3 is-compact">
                    <?php foreach ( $lists as $item ) : ?>
                        <div class="gep-ayuda-ctrl is-compact gep-ayuda-item" data-keys="<?php echo esc_attr( 'lista ' . $item[1] . ' ' . $item[2] ); ?>">
                            <span class="gep-ayuda-tile is-sm"><?php echo gep_help_icon( $item[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                            <div><strong><?php echo esc_html( $item[1] ); ?></strong><small><?php echo esc_html( $item[2] ); ?></small></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="gep-ayuda-card" id="gep-a-dudas" aria-labelledby="gep-a-dudas-h">
                <?php gep_help_section_head( 'help', 'Dudas rápidas', 'Si algo no sale como esperabas.', 'gep-a-dudas-h' ); ?>
                <ul class="gep-ayuda-fixes">
                    <?php foreach ( gep_help_fixes() as $fix ) : ?>
                        <li class="gep-ayuda-item" data-keys="<?php echo esc_attr( $fix[0] . ' ' . $fix[1] ); ?>"><span><?php echo esc_html( $fix[0] ); ?></span><strong><?php echo gep_help_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $fix[1] ); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="gep-ayuda-card" id="gep-a-atajos" aria-labelledby="gep-a-atajos-h">
                <?php gep_help_section_head( 'keyboard', 'Atajos de teclado', 'Menos clics para las acciones de todos los días.', 'gep-a-atajos-h' ); ?>
                <ul class="gep-ayuda-keys">
                    <?php foreach ( $shortcuts as $shortcut ) : ?>
                        <li class="gep-ayuda-item" data-keys="<?php echo esc_attr( 'atajo teclado ' . implode( ' ', $shortcut[0] ) . ' ' . $shortcut[1] ); ?>">
                            <span class="gep-ayuda-keycaps"><?php foreach ( $shortcut[0] as $i => $key ) { echo ( $i ? '<i>+</i>' : '' ) . '<kbd>' . esc_html( $key ) . '</kbd>'; } // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                            <span><?php echo esc_html( $shortcut[1] ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="gep-ayuda-card" id="gep-a-consejos" aria-labelledby="gep-a-consejos-h">
                <?php gep_help_section_head( 'bulb', 'Consejos de uso', 'Buenas prácticas para mejores resultados.', 'gep-a-consejos-h' ); ?>
                <div class="gep-ayuda-grid2">
                    <?php foreach ( $tips as $tip ) : ?>
                        <div class="gep-ayuda-ctrl is-compact gep-ayuda-item" data-keys="<?php echo esc_attr( 'consejo ' . $tip[1] . ' ' . $tip[2] ); ?>">
                            <span class="gep-ayuda-tile is-sm"><?php echo gep_help_icon( $tip[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                            <div><strong><?php echo esc_html( $tip[1] ); ?></strong><small><?php echo esc_html( $tip[2] ); ?></small></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php // v9.24.0: en tablet y móvil la barra lateral se oculta; el soporte queda al final. ?>
            <div class="gep-ayuda-support-end"><?php gep_help_render_support_box( $tour_url, $support, 'end' ); ?></div>

            <p class="gep-ayuda-version">GEP Editor Beta <?php echo esc_html( $version ); ?></p>
        </div>
    </div>
    <?php
}
