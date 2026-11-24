<?php
/**
 * GEP · Contáctanos
 * Compatible con sitio PHP externo + WordPress detectado automáticamente.
 * Lee el contenido del plugin GEP Editor Beta v7 cuando WordPress está disponible.
 * Si WordPress/plugin no están disponibles, conserva el contenido original.
 */

/* --------------------------------------------------------------------------
 * 1) Cargar WordPress de forma segura
 * -------------------------------------------------------------------------- */
require_once __DIR__ . '/includes/wp-bootstrap.php';
gep_site_bootstrap_wordpress();

/* --------------------------------------------------------------------------
 * 2) Helpers locales: la página nunca depende de esc_html()/esc_url() para vivir
 * -------------------------------------------------------------------------- */
function gep_contact_h( $value ) {
    return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function gep_contact_url( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    // v9.21.2: esc_url_raw, porque todo se imprime después con gep_contact_h();
    // esc_url + gep_contact_h escapaba dos veces el "&" y rompía el enlace del mapa.
    if ( function_exists( 'esc_url_raw' ) ) {
        return esc_url_raw( $value );
    }

    return filter_var( $value, FILTER_SANITIZE_URL );
}

function gep_contact_email( $value ) {
    $value = trim( (string) $value );
    if ( function_exists( 'sanitize_email' ) ) {
        return sanitize_email( $value );
    }

    $email = filter_var( $value, FILTER_SANITIZE_EMAIL );
    return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
}

/* --------------------------------------------------------------------------
 * 3) Valores originales de respaldo
 * -------------------------------------------------------------------------- */
$gep_contact_defaults = array(
    'hero_eyebrow'      => 'Universidad de Chile',
    'hero_title'        => 'Contáctanos',
    'hero_text'         => 'Campus Antumapu · Facultad de Ciencias Forestales y de la Conservación de la Naturaleza · Universidad de Chile.',
    'hero_image'        => '',
    'hero_video'        => '',
    'card_eyebrow'      => 'Contacto GEP',
    'card_title'        => 'Laboratorio de Geomática y Ecología del Paisaje',
    'primary_email'     => 'jhernand@uchile.cl',
    'email_button_text' => 'Escribir por correo',
    'map_button_text'   => 'Ver dirección ↗',
    'map_url'           => 'https://www.google.com/maps/search/?api=1&query=Av.+Santa+Rosa+11315+La+Pintana+Santiago+Chile',
    'location_eyebrow'  => 'Ubicación',
    'location_title'    => 'Campus Antumapu',
    'location_text'     => 'La Pintana · Santiago de Chile',
    'map_image_url'     => '',
    'note'              => '',
    'cta_title'         => '¿Necesitas información sobre el laboratorio, sus proyectos o su docencia?',
    'cta_button_text'   => 'Contactar al GEP',
    'rows'              => array(
        array( 'kind' => 'text',  'label' => 'Laboratorio',           'value' => 'Laboratorio de Geomática y Ecología del Paisaje (GEP)' ),
        array( 'kind' => 'text',  'label' => 'Facultad',              'value' => 'Facultad de Ciencias Forestales y de la Conservación de la Naturaleza' ),
        array( 'kind' => 'text',  'label' => 'Institución',           'value' => 'Universidad de Chile' ),
        array( 'kind' => 'text',  'label' => 'Campus',                'value' => 'Campus Sur (Antumapu)' ),
        array( 'kind' => 'text',  'label' => 'Dirección',             'value' => 'Av. Santa Rosa #11315 · La Pintana · Santiago de Chile' ),
        array( 'kind' => 'text',  'label' => 'Casilla',               'value' => 'Casilla 9206 · Santiago' ),
        array( 'kind' => 'text',  'label' => 'Teléfono',              'value' => '(56 2) 978 5877' ),
        array( 'kind' => 'email', 'label' => 'Jaime Hernández Palma', 'value' => 'jhernand@uchile.cl' ),
        array( 'kind' => 'email', 'label' => 'M. Paz Acuña',          'value' => 'mpacuna@u.uchile.cl' ),
    ),
);

$gep_contact = $gep_contact_defaults;

/* --------------------------------------------------------------------------
 * 4) Encontrar la página de Contacto dentro de WordPress
 * -------------------------------------------------------------------------- */
$gep_contact_post_id = 0;

// La forma preferida: usar la navegación que ya conoce el plugin.
if ( function_exists( 'gep_demo_get_page_navigation' ) ) {
    $gep_navigation = gep_demo_get_page_navigation();
    if ( ! empty( $gep_navigation['contacto']['page']->ID ) ) {
        $gep_contact_post_id = (int) $gep_navigation['contacto']['page']->ID;
    }
}

// Respaldo: buscar directamente por los slugs aceptados por el plugin.
if ( ! $gep_contact_post_id && function_exists( 'get_page_by_path' ) ) {
    foreach ( array( 'contactanos', 'contacto', 'contactenos' ) as $gep_contact_slug ) {
        $gep_page = get_page_by_path( $gep_contact_slug, OBJECT, 'page' );
        if ( $gep_page && ! empty( $gep_page->ID ) ) {
            $gep_contact_post_id = (int) $gep_page->ID;
            break;
        }
    }
}

// Leer los datos del plugin si está activo y encontró la página correcta.
if ( $gep_contact_post_id && function_exists( 'gep_demo_contact_values' ) ) {
    $gep_saved_contact = gep_demo_contact_values( $gep_contact_post_id );
    if ( is_array( $gep_saved_contact ) ) {
        $gep_contact = array_merge( $gep_contact_defaults, $gep_saved_contact );

        // array_merge reemplaza rows correctamente, pero aseguramos siempre un array.
        if ( ! isset( $gep_contact['rows'] ) || ! is_array( $gep_contact['rows'] ) ) {
            $gep_contact['rows'] = $gep_contact_defaults['rows'];
        }
    }
}

$gep_primary_email = gep_contact_email( isset( $gep_contact['primary_email'] ) ? $gep_contact['primary_email'] : '' );
$gep_map_image = ! empty( $gep_contact['map_image_url'] ) ? gep_contact_url( $gep_contact['map_image_url'] ) : '';

/* --------------------------------------------------------------------------
 * 5) Cabecera del sitio PHP
 * -------------------------------------------------------------------------- */
$pageTitle = 'GEP · Contáctanos';
$pageDescription = 'Contacto del Laboratorio GEP · Universidad de Chile.';
$bodyClass = 'page-contactanos';
$activePage = 'contactanos';
$isHome = false;
require __DIR__ . '/includes/header.php';
?>
<main>
    <?php $gep_contact_hero_style=!empty($gep_contact['hero_image'])?' style="background-image:url(' . gep_contact_h(gep_contact_url($gep_contact['hero_image'])) . ');"':''; ?>
    <section data-gep-section="hero" class="page-hero hero-contacto<?php echo !empty($gep_contact['hero_video'])?' has-video-bg':''; ?>"<?php echo $gep_contact_hero_style; ?>><?php gep_theme_render_hero_video($gep_contact['hero_video']??'',$gep_contact['hero_image']??'','Video de cabecera de Contacto'); ?>
        <div class="container page-hero-content">
            <p class="eyebrow"><?php echo gep_contact_h( $gep_contact['hero_eyebrow'] ); ?></p>
            <h1><?php echo gep_contact_h( $gep_contact['hero_title'] ); ?></h1>
            <p><?php echo gep_contact_h( $gep_contact['hero_text'] ); ?></p>
        </div>
    </section>

    <section data-gep-section="contact" class="section">
        <div class="container">
            <div class="contact-grid">
                <section class="contact-card">
                    <p class="eyebrow"><?php echo gep_contact_h( $gep_contact['card_eyebrow'] ); ?></p>
                    <h2><?php echo gep_contact_h( $gep_contact['card_title'] ); ?></h2>

                    <ul class="contact-list">
                        <?php foreach ( $gep_contact['rows'] as $gep_row ) : ?>
                            <?php
                            if ( ! is_array( $gep_row ) ) {
                                continue;
                            }

                            $gep_label = isset( $gep_row['label'] ) ? trim( (string) $gep_row['label'] ) : '';
                            $gep_value = isset( $gep_row['value'] ) ? trim( (string) $gep_row['value'] ) : '';
                            $gep_kind  = isset( $gep_row['kind'] ) ? (string) $gep_row['kind'] : 'text';

                            if ( '' === $gep_label && '' === $gep_value ) {
                                continue;
                            }
                            ?>
                            <li>
                                <span class="contact-label"><?php echo gep_contact_h( $gep_label ); ?></span>
                                <span class="contact-main">
                                    <?php if ( 'email' === $gep_kind && '' !== $gep_value ) : ?>
                                        <?php $gep_row_email = gep_contact_email( $gep_value ); ?>
                                        <?php if ( $gep_row_email ) : ?>
                                            <a href="mailto:<?php echo gep_contact_h( $gep_row_email ); ?>"><?php echo gep_contact_h( $gep_value ); ?></a>
                                        <?php else : ?>
                                            <?php echo gep_contact_h( $gep_value ); ?>
                                        <?php endif; ?>
                                    <?php elseif ( 'tel' === $gep_kind && '' !== $gep_value ) : ?>
                                        <a href="tel:<?php echo gep_contact_h( preg_replace( '/[^0-9+]/', '', $gep_value ) ); ?>"><?php echo gep_contact_h( $gep_value ); ?></a>
                                    <?php elseif ( 'url' === $gep_kind && '' !== $gep_value ) : ?>
                                        <a href="<?php echo gep_contact_h( gep_contact_url( $gep_value ) ); ?>" target="_blank" rel="noopener"><?php echo gep_contact_h( $gep_value ); ?></a>
                                    <?php else : ?>
                                        <?php echo gep_contact_h( $gep_value ); ?>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="contact-actions">
                        <?php if ( $gep_primary_email && ! empty( $gep_contact['email_button_text'] ) ) : ?>
                            <a class="btn btn-green" href="mailto:<?php echo gep_contact_h( $gep_primary_email ); ?>"><?php echo gep_contact_h( $gep_contact['email_button_text'] ); ?></a>
                        <?php endif; ?>

                        <?php if ( ! empty( $gep_contact['map_url'] ) && ! empty( $gep_contact['map_button_text'] ) ) : ?>
                            <a class="btn btn-outline" href="<?php echo gep_contact_h( gep_contact_url( $gep_contact['map_url'] ) ); ?>" rel="noopener" target="_blank"><?php echo gep_contact_h( $gep_contact['map_button_text'] ); ?></a>
                        <?php endif; ?>
                    </div>
                </section>

                <div class="contact-map-visual geo-contact-card">
                    <div class="geo-contact-label">
                        <span class="eyebrow light"><?php echo gep_contact_h( $gep_contact['location_eyebrow'] ); ?></span>
                        <strong><?php echo gep_contact_h( $gep_contact['location_title'] ); ?></strong>
                        <small><?php echo gep_contact_h( $gep_contact['location_text'] ); ?></small>
                    </div>
                    <?php if ( $gep_map_image ) : ?><img alt="<?php echo gep_contact_h( gep_site_image_alt( $gep_map_image, '', 'Mapa de Chile' ) ); ?>" src="<?php echo gep_contact_h( $gep_map_image ); ?>"><?php endif; ?>
                </div>
            </div>

            <?php $gep_contact['note'] = gep_site_public_note( $gep_contact['note'] ); if ( ! empty( $gep_contact['note'] ) ) : ?>
                <div class="source-note">
                    <span class="source-note-icon">i</span>
                    <p><?php echo gep_contact_h( $gep_contact['note'] ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section data-gep-section="cta" class="cta-band">
        <div class="container cta-inner">
            <h2><?php echo gep_contact_h( $gep_contact['cta_title'] ); ?></h2>
            <?php if ( $gep_primary_email && ! empty( $gep_contact['cta_button_text'] ) ) : ?>
                <a class="btn btn-primary" href="mailto:<?php echo gep_contact_h( $gep_primary_email ); ?>"><?php echo gep_contact_h( $gep_contact['cta_button_text'] ); ?></a>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
