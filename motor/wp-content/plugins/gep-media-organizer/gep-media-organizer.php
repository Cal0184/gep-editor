<?php
/**
 * Plugin Name: GEP Media Organizer
 * Description: Carpetas virtuales para organizar los archivos de WordPress sin cambiar sus URL ni ubicación física.
 * Version: 1.6.4
 * Author: Proyecto GEP
 * Text Domain: gep-media-organizer
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) { exit; }

final class GEP_Media_Organizer {
    const VERSION = '1.6.4';
    const TAX = 'gep_media_folder';
    const NONCE = 'gep_media_nonce';

    public static function boot() {
        add_action('init', array(__CLASS__, 'taxonomy'));
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('wp_enqueue_media', array(__CLASS__, 'picker_assets'));
        add_action('add_attachment', array(__CLASS__, 'assign_upload'));
        add_filter('attachment_fields_to_edit', array(__CLASS__, 'folder_field'), 10, 2);
        add_filter('attachment_fields_to_save', array(__CLASS__, 'save_folder_field'), 10, 2);
        add_filter('gep_help_tutorials', array(__CLASS__, 'help_tutorial'));
        add_filter('ajax_query_attachments_args', array(__CLASS__, 'filter_media_query'));
        add_action('wp_ajax_gep_media_folders', array(__CLASS__, 'ajax_folders'));
        add_action('wp_ajax_gep_media_create', array(__CLASS__, 'ajax_create'));
        add_action('wp_ajax_gep_media_rename', array(__CLASS__, 'ajax_rename'));
        add_action('wp_ajax_gep_media_reparent', array(__CLASS__, 'ajax_reparent'));
        add_action('wp_ajax_gep_media_delete', array(__CLASS__, 'ajax_delete'));
        add_action('wp_ajax_gep_media_move', array(__CLASS__, 'ajax_move'));
        add_action('wp_ajax_gep_media_items', array(__CLASS__, 'ajax_items'));
        // v1.6.4: "Ordenar fotos del GEP" se quitó de la pantalla (no se usaba).
    }
    public static function taxonomy() {
        register_taxonomy(self::TAX, array('attachment'), array(
            'hierarchical' => true,
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => false,
            'show_admin_column' => false,
            'query_var' => false,
            'rewrite' => false,
            'update_count_callback' => '_update_generic_term_count',
            'labels' => array('name' => 'Carpetas de medios', 'singular_name' => 'Carpeta de medios'),
        ));
    }
    public static function activate() {
        self::taxonomy();
        if (!get_option('gep_media_initialized')) {
            $root = term_exists('GEP', self::TAX);
            if (!$root) { $root = wp_insert_term('GEP', self::TAX); }
            $root_id = is_array($root) ? (int) $root['term_id'] : 0;
            if ($root_id) {
                foreach (array('Logos','Headers','Personas','Docencia','Proyectos','Publicaciones','Noticias','Comunicaciones','Mapas','Videos') as $name) {
                    if (!term_exists($name, self::TAX, $root_id)) {
                        wp_insert_term($name, self::TAX, array('parent' => $root_id));
                    }
                }
            }
            update_option('gep_media_initialized', '1');
        }
    }
    public static function menu() {
        add_media_page('Carpetas GEP', 'Carpetas GEP', 'upload_files', 'gep-media-organizer', array(__CLASS__, 'page'));
    }
    private static function asset_version($file) {
        $path = plugin_dir_path(__FILE__) . $file;
        return file_exists($path) ? self::VERSION . '.' . filemtime($path) : self::VERSION;
    }
    public static function assets($hook) {
        if (!current_user_can('upload_files') || $hook !== 'media_page_gep-media-organizer') { return; }
        // The organizer page opens the media picker; the picker hook below adds the folder panel.
        wp_enqueue_media();
        wp_enqueue_script('gep-media-admin', plugins_url('assets/admin.js', __FILE__), array('jquery', 'media-views', 'gep-media-filter'), self::asset_version('assets/admin.js'), true);
    }
    /** Folder panel for every WordPress media picker (GEP Editor, pages, Media Library). */
    public static function picker_assets() {
        if (!is_admin() || !current_user_can('upload_files') || wp_script_is('gep-media-filter', 'enqueued')) { return; }
        wp_enqueue_script('gep-media-filter', plugins_url('assets/media-filter.js', __FILE__), array('jquery', 'media-views'), self::asset_version('assets/media-filter.js'), true);
        wp_localize_script('gep-media-filter', 'GEP_MEDIA', array(
            'ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce(self::NONCE),
            'taxonomy' => self::TAX, 'organizer' => admin_url('upload.php?page=gep-media-organizer'),
            'folders' => self::folder_data(), 'gep' => self::gep_available(),
        ));
        wp_enqueue_style('gep-media-style', plugins_url('assets/admin.css', __FILE__), array(), self::asset_version('assets/admin.css'));
    }
    public static function page() {
        if (!current_user_can('upload_files')) { wp_die('No tienes permiso para gestionar medios.'); }
        ?>
        <div class="wrap gep-mo" id="gep-media-organizer">
            <h1>Organizador de medios GEP <span class="gep-version">v<?php echo esc_html(self::VERSION); ?></span></h1>
            <p class="description">Organiza imágenes, documentos y videos sin mover archivos reales ni cambiar los enlaces existentes.</p>
            <div class="gep-layout">
                <aside class="gep-sidebar" aria-label="Carpetas de medios">
                    <div class="gep-side-title"><strong>Carpetas</strong><button type="button" class="button button-small" id="gep-new-folder">+ Nueva</button></div>
                    <div id="gep-folders" role="tree" aria-label="Carpetas"></div>
                    <div class="gep-folder-actions"><button class="button" id="gep-rename-folder" disabled>Renombrar</button><button class="button" id="gep-reparent-folder" disabled>Mover carpeta</button><button class="button" id="gep-delete-folder" disabled>Eliminar</button></div>
                </aside>
                <main class="gep-main">
                    <div class="gep-toolbar">
                        <div><h2 id="gep-active-title">Todos los archivos</h2><span id="gep-total"></span></div>
                        <div class="gep-toolbar-controls">
                            <label class="screen-reader-text" for="gep-search">Buscar archivos</label>
                            <input id="gep-search" type="search" placeholder="Buscar archivos…" />
                            <button type="button" class="button button-primary" id="gep-add-media">Añadir archivos</button>
                        </div>
                    </div>
                    <div class="gep-bulk" id="gep-bulk" hidden><span id="gep-selected-count">0 seleccionados</span><button class="button button-primary" id="gep-move-selected">Mover a carpeta…</button><button class="button" id="gep-clear-selection">Deseleccionar</button></div>
                    <p class="gep-hint">Selecciona varios archivos para moverlos. También puedes arrastrarlos sobre una carpeta a la izquierda. Lo que subas con Añadir archivos queda en la carpeta abierta.</p>
                    <div id="gep-items" class="gep-grid" aria-live="polite"></div>
                    <div class="gep-pagination"><button type="button" class="button" id="gep-load-more" hidden>Cargar más</button></div>
                    <p id="gep-notice" class="gep-notice" role="status" aria-live="polite"></p>
                </main>
            </div>
        </div>
        <?php
    }
    private static function authorize() {
        check_ajax_referer(self::NONCE, 'nonce');
        if (!current_user_can('upload_files')) { wp_send_json_error(array('message' => 'Sin permisos.'), 403); }
    }
    private static function term($id) {
        $term = get_term((int)$id, self::TAX);
        return ($term && !is_wp_error($term)) ? $term : false;
    }
    private static function folder_data() {
        $terms = get_terms(array('taxonomy' => self::TAX, 'hide_empty' => false));
        if (is_wp_error($terms)) { return array(); }
        return array_map(function($t) {
            return array('id' => (int)$t->term_id, 'parent' => (int)$t->parent, 'name' => $t->name, 'count' => (int)$t->count);
        }, $terms);
    }
    public static function ajax_folders() {
        self::authorize();
        wp_send_json_success(array('folders' => self::folder_data()));
    }
    public static function ajax_create() {
        self::authorize();
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $parent = isset($_POST['parent']) ? absint($_POST['parent']) : 0;
        if ('' === trim($name) || strlen($name) > 300 || ($parent && !self::term($parent))) {
            wp_send_json_error(array('message' => 'Nombre o carpeta superior inválidos.'), 400);
        }
        if (term_exists($name, self::TAX, $parent)) { wp_send_json_error(array('message' => 'Ya existe una carpeta con ese nombre en este nivel.'), 400); }
        $result = wp_insert_term($name, self::TAX, array('parent' => $parent));
        if (is_wp_error($result)) { wp_send_json_error(array('message' => $result->get_error_message()), 400); }
        wp_send_json_success(array('id' => (int)$result['term_id']));
    }
    public static function ajax_rename() {
        self::authorize();
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $term = self::term($id);
        if (!$term || '' === trim($name) || strlen($name) > 300) { wp_send_json_error(array('message' => 'Carpeta o nombre inválido.'), 400); }
        $result = wp_update_term($id, self::TAX, array('name' => $name));
        if (is_wp_error($result)) { wp_send_json_error(array('message' => $result->get_error_message()), 400); }
        wp_send_json_success();
    }
    public static function ajax_reparent() {
        self::authorize();
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $parent = isset($_POST['parent']) ? absint($_POST['parent']) : 0;
        if (!self::term($id) || ($parent && !self::term($parent))) {
            wp_send_json_error(array('message' => 'Carpeta inválida.'), 400);
        }
        $cursor = $parent;
        $visited = array();
        while ($cursor) {
            if ($cursor === $id || isset($visited[$cursor])) {
                wp_send_json_error(array('message' => 'No puedes mover una carpeta dentro de sí misma o una subcarpeta.'), 400);
            }
            $visited[$cursor] = true;
            $ancestor = self::term($cursor);
            $cursor = $ancestor ? (int) $ancestor->parent : 0;
        }
        $result = wp_update_term($id, self::TAX, array('parent' => $parent));
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 400);
        }
        wp_send_json_success();
    }
    public static function ajax_delete() {
        self::authorize();
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        if (!self::term($id)) { wp_send_json_error(array('message' => 'Carpeta no encontrada.'), 404); }
        // Child folders are reparented and attached files become unclassified.
        $children = get_terms(array('taxonomy' => self::TAX, 'parent' => $id, 'hide_empty' => false));
        $term = self::term($id);
        if (!is_wp_error($children)) {
            foreach ($children as $child) { wp_update_term($child->term_id, self::TAX, array('parent' => (int)$term->parent)); }
        }
        $result = wp_delete_term($id, self::TAX);
        if (!$result || is_wp_error($result)) { wp_send_json_error(array('message' => 'No se pudo eliminar la carpeta.'), 500); }
        wp_send_json_success();
    }
    public static function ajax_move() {
        self::authorize();
        $ids = isset($_POST['ids']) ? array_map('absint', (array)$_POST['ids']) : array();
        $ids = array_values(array_unique(array_filter($ids)));
        $folder = isset($_POST['folder']) ? (int)$_POST['folder'] : 0;
        if ($folder < 0 || ($folder && !self::term($folder)) || !$ids || count($ids) > 200) {
            wp_send_json_error(array('message' => 'Selecciona entre 1 y 200 archivos y una carpeta válida.'), 400);
        }
        $moved = 0; $skipped = 0;
        foreach ($ids as $id) {
            // Folders only change the organization, never the file, so upload_files (checked in authorize) is enough.
            // edit_post would block the "Editor GEP" role from organizing images uploaded by other people.
            if (get_post_type($id) !== 'attachment') { $skipped++; continue; }
            $r = wp_set_object_terms($id, $folder ? array($folder) : array(), self::TAX, false);
            if (is_wp_error($r)) { $skipped++; } else { $moved++; }
        }
        wp_send_json_success(array('moved' => $moved, 'skipped' => $skipped));
    }
    private static function apply_folder_filter(&$args, $folder) {
        if ($folder === 'all' || $folder === '') { return; }
        if ($folder === 'unassigned') {
            $args['tax_query'][] = array('taxonomy' => self::TAX, 'operator' => 'NOT EXISTS');
        } elseif (ctype_digit((string)$folder) && self::term((int)$folder)) {
            $args['tax_query'][] = array('taxonomy' => self::TAX, 'field' => 'term_id', 'terms' => array((int)$folder), 'include_children' => false);
        }
    }
    public static function filter_media_query($args) {
        if (!current_user_can('upload_files')) { return $args; }
        if (!isset($_REQUEST['query']['gep_folder'])) { return $args; }
        $folder = sanitize_text_field(wp_unslash($_REQUEST['query']['gep_folder']));
        self::apply_folder_filter($args, $folder);
        return $args;
    }
    public static function ajax_items() {
        self::authorize();
        $folder = isset($_POST['folder']) ? sanitize_text_field(wp_unslash($_POST['folder'])) : 'all';
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $page = isset($_POST['page']) ? max(1, absint($_POST['page'])) : 1;
        $args = array('post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 48, 'paged' => $page, 'orderby' => 'date', 'order' => 'DESC', 's' => $search);
        self::apply_folder_filter($args, $folder);
        $q = new WP_Query($args);
        $items = array();
        foreach ($q->posts as $a) {
            $image = wp_get_attachment_image_src($a->ID, 'thumbnail');
            $mime = get_post_mime_type($a->ID);
            $items[] = array('id' => (int)$a->ID, 'title' => get_the_title($a->ID), 'thumb' => $image ? $image[0] : wp_mime_type_icon($a->ID), 'mime' => $mime, 'edit' => get_edit_post_link($a->ID, 'raw'));
        }
        wp_send_json_success(array('items' => $items, 'total' => (int)$q->found_posts, 'hasMore' => $page < (int)$q->max_num_pages));
    }

    /** Files uploaded while a folder is open go straight into that folder. */
    public static function assign_upload($id) {
        if (!isset($_REQUEST['gep_folder']) || !current_user_can('upload_files')) { return; }
        $folder = absint(wp_unslash($_REQUEST['gep_folder']));
        if ($folder && self::term($folder)) { wp_set_object_terms($id, array($folder), self::TAX, false); }
    }
    private static function folder_options() {
        $terms = get_terms(array('taxonomy' => self::TAX, 'hide_empty' => false, 'orderby' => 'name'));
        $by_parent = array();
        if (!is_wp_error($terms)) { foreach ($terms as $t) { $by_parent[(int)$t->parent][] = $t; } }
        $out = array(0 => 'Sin clasificar');
        $walk = function($parent, $depth) use (&$walk, &$out, $by_parent) {
            if (empty($by_parent[$parent])) { return; }
            foreach ($by_parent[$parent] as $t) { $out[(int)$t->term_id] = str_repeat('— ', $depth) . $t->name; $walk((int)$t->term_id, $depth + 1); }
        };
        $walk(0, 0);
        return $out;
    }
    /** "Carpeta GEP" field in the attachment details (media picker and Edit Media screen). */
    public static function folder_field($fields, $post) {
        if (!current_user_can('upload_files')) { return $fields; }
        $current = wp_get_object_terms($post->ID, self::TAX, array('fields' => 'ids'));
        $current = (!is_wp_error($current) && $current) ? (int)$current[0] : 0;
        $html = '<select name="attachments[' . (int)$post->ID . '][gep_folder]" id="attachments-' . (int)$post->ID . '-gep_folder" class="gep-folder-field">';
        foreach (self::folder_options() as $id => $label) {
            $html .= '<option value="' . esc_attr($id) . '"' . selected($current, $id, false) . '>' . esc_html($label) . '</option>';
        }
        $html .= '</select>';
        $fields['gep_folder'] = array('label' => 'Carpeta GEP', 'input' => 'html', 'html' => $html, 'helps' => 'Solo ordena la Biblioteca: la URL de la imagen no cambia.');
        return $fields;
    }
    public static function save_folder_field($post, $attachment) {
        if (!isset($attachment['gep_folder']) || !current_user_can('upload_files') || empty($post['ID'])) { return $post; }
        $folder = absint($attachment['gep_folder']);
        if (!$folder || self::term($folder)) { wp_set_object_terms((int)$post['ID'], $folder ? array($folder) : array(), self::TAX, false); }
        return $post;
    }

    /* ---------- Ordenar fotos del GEP (needs GEP Editor 9.21+) ---------- */
    private static function gep_available() {
        return function_exists('gep_demo_get_page_navigation') && function_exists('gep_demo_alt_page_values');
    }
    /** Attachment ID for an uploads URL, including resized (-300x200) and -scaled copies. */
    private static function url_to_id($url) {
        static $cache = array();
        $clean = preg_replace('/[?#].*$/', '', trim((string)$url));
        if ('' === $clean) { return 0; }
        if (isset($cache[$clean])) { return $cache[$clean]; }
        $id = attachment_url_to_postid($clean);
        if (!$id) {
            $base = preg_replace('/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $clean);
            if ($base !== $clean) { $id = attachment_url_to_postid($base); }
            if (!$id) { $id = attachment_url_to_postid(preg_replace('/(\.[a-z0-9]+)$/i', '-scaled$1', $base)); }
        }
        return $cache[$clean] = (int)$id;
    }
    private static function collect_urls($data, &$urls) {
        if (!is_array($data)) { return; }
        foreach ($data as $value) {
            if (is_array($value)) { self::collect_urls($value, $urls); continue; }
            // Plain URL fields and URLs inside HTML text.
            if (is_string($value) && preg_match_all('#https?://[^\s"\'()<>]+/uploads/[^\s"\'()<>]+?\.(?:jpe?g|png|webp|gif|avif|svg|mp4|webm|mov|pdf)#i', $value, $m)) {
                foreach ($m[0] as $url) { $urls[] = $url; }
            }
        }
    }
    /** Plan: unclassified attachment ID => folder name (one folder per GEP page; videos go to "Videos"). */
    private static function auto_plan() {
        $plan = array();
        foreach (gep_demo_get_page_navigation() as $key => $item) {
            if (empty($item['page'])) { continue; }
            $urls = array();
            self::collect_urls(gep_demo_alt_page_values($key, (int)$item['page']->ID), $urls);
            foreach (array_unique($urls) as $url) {
                $id = self::url_to_id($url);
                if (!$id || isset($plan[$id])) { continue; }
                $assigned = wp_get_object_terms($id, self::TAX, array('fields' => 'ids'));
                if (!is_wp_error($assigned) && $assigned) { continue; }
                $mime = (string)get_post_mime_type($id);
                $plan[$id] = (0 === strpos($mime, 'video/')) ? 'Videos' : $item['label'];
            }
        }
        return $plan;
    }
    private static function gep_folder_id($name) {
        $root = term_exists('GEP', self::TAX, 0);
        if (!$root) { $root = wp_insert_term('GEP', self::TAX); }
        if (is_wp_error($root)) { return 0; }
        $root_id = (int)$root['term_id'];
        $children = get_terms(array('taxonomy' => self::TAX, 'parent' => $root_id, 'hide_empty' => false));
        if (!is_wp_error($children)) {
            foreach ($children as $child) { if (0 === strcasecmp(remove_accents($child->name), remove_accents($name))) { return (int)$child->term_id; } }
        }
        $new = wp_insert_term($name, self::TAX, array('parent' => $root_id));
        return is_wp_error($new) ? 0 : (int)$new['term_id'];
    }
    public static function ajax_auto() {
        self::authorize();
        if (!self::gep_available()) { wp_send_json_error(array('message' => 'Esta función necesita GEP Editor 9.21 o superior activo.'), 400); }
        $plan = self::auto_plan();
        $groups = array();
        foreach ($plan as $folder) { $groups[$folder] = isset($groups[$folder]) ? $groups[$folder] + 1 : 1; }
        if (empty($_POST['apply'])) { wp_send_json_success(array('total' => count($plan), 'groups' => $groups)); }
        $moved = 0; $ids = array();
        foreach ($plan as $id => $folder) {
            if (!isset($ids[$folder])) { $ids[$folder] = self::gep_folder_id($folder); }
            if ($ids[$folder] && !is_wp_error(wp_set_object_terms($id, array($ids[$folder]), self::TAX, false))) { $moved++; }
        }
        wp_send_json_success(array('moved' => $moved, 'groups' => $groups));
    }
    /** Tutorial in Contenido GEP > Ayuda. */
    public static function help_tutorial($tutorials) {
        $tutorials[] = array(
            'id'    => 'carpetas-imagenes',
            'title' => 'Encontrar fotos por carpeta',
            'keys'  => 'carpeta carpetas foto imagen biblioteca medios ordenar organizar buscar subir',
            'steps' => array(
                array('field', 'Al pulsar Seleccionar imagen, usa la lista Carpetas de la izquierda.'),
                array('list', 'Abre la carpeta de la página, por ejemplo Docencia.'),
                array('add', 'Lo que subas ahí queda guardado en esa carpeta.'),
                array('tabs', 'Para subir u ordenar muchas fotos pulsa Carpetas de imágenes, arriba a la derecha del editor.'),
                array('list2', 'Con Seleccionar varios marcas varias fotos y las mueves juntas a otra carpeta.'),
            ),
        );
        return $tutorials;
    }
}
GEP_Media_Organizer::boot();
register_activation_hook(__FILE__, array('GEP_Media_Organizer', 'activate'));
