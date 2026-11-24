<?php
/**
 * GEP v9.21.0 · Datos para Google (JSON-LD), enlace canónico y vista previa al compartir.
 * No cambia nada visible: se agrega dentro de <head>.
 * Cada página puede definir $pageSchema (lista de nodos extra) antes de incluir header.php.
 */

if ( ! function_exists( 'gep_site_base_url' ) ) {
    /** URL de la carpeta del sitio (p. ej. https://gepdev.bonfyr.cl/GEP_LEP/). */
    function gep_site_base_url() {
        $https = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) )
            || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] ) )
            || ( isset( $_SERVER['SERVER_PORT'] ) && 443 === (int) $_SERVER['SERVER_PORT'] );
        $host = isset( $_SERVER['HTTP_HOST'] ) ? preg_replace( '/[^A-Za-z0-9.:\-]/', '', (string) $_SERVER['HTTP_HOST'] ) : 'localhost';
        $dir  = isset( $_SERVER['SCRIPT_NAME'] ) ? str_replace( '\\', '/', dirname( (string) $_SERVER['SCRIPT_NAME'] ) ) : '/';
        $dir  = '/' . trim( $dir, '/' );
        return ( $https ? 'https' : 'http' ) . '://' . $host . rtrim( $dir, '/' ) . '/';
    }
}

if ( ! function_exists( 'gep_site_current_url' ) ) {
    /** URL canónica de la página actual (solo conserva ?proyecto= en la ficha de proyecto). */
    function gep_site_current_url() {
        $file = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) $_SERVER['SCRIPT_NAME'] ) : 'index.php';
        $url  = gep_site_base_url() . ( 'index.php' === $file ? '' : $file );
        if ( 'proyecto.php' === $file && isset( $_GET['proyecto'] ) && '' !== trim( (string) $_GET['proyecto'] ) ) {
            $url .= '?proyecto=' . rawurlencode( trim( (string) $_GET['proyecto'] ) );
        }
        return $url;
    }
}

if ( ! function_exists( 'gep_site_publications_schema' ) ) {
    /** Cada publicación como obra académica (con su DOI cuando lo tiene). */
    function gep_site_publications_schema( $records ) {
        $types = array( 'articulos' => 'ScholarlyArticle', 'congresos' => 'ScholarlyArticle', 'libros' => 'Book', 'capitulos' => 'Chapter', 'otros' => 'CreativeWork' );
        $parts = array();
        foreach ( is_array( $records ) ? $records : array() as $r ) {
            if ( ! is_array( $r ) ) continue;
            $title = trim( (string) ( $r['title'] ?? '' ) );
            $text  = trim( (string) ( $r['text'] ?? '' ) );
            $name  = '' !== $title ? $title : $text;
            if ( '' === $name ) continue;
            if ( function_exists( 'mb_strlen' ) && mb_strlen( $name ) > 300 ) $name = mb_substr( $name, 0, 297 ) . '…';
            $node = array( '@type' => $types[ $r['category'] ?? 'otros' ] ?? 'CreativeWork', 'name' => $name );
            if ( '' !== $title && '' !== $text && $text !== $title ) $node['description'] = $text;
            if ( preg_match( '/(19|20)\d{2}/', (string) ( $r['year'] ?? '' ), $m ) ) $node['datePublished'] = $m[0];
            $url = trim( (string) ( $r['url'] ?? '' ) );
            if ( preg_match( '#^https?://#i', $url ) ) $node['url'] = $url;
            $doi = trim( (string) ( $r['doi'] ?? '' ) );
            if ( '' === $doi && preg_match( '#doi\.org/(10\.\d{4,9}/\S+)#i', $url, $dm ) ) $doi = $dm[1];
            if ( '' !== $doi ) {
                $node['identifier'] = array( '@type' => 'PropertyValue', 'propertyID' => 'DOI', 'value' => $doi );
                $node['sameAs']     = 'https://doi.org/' . $doi;
            }
            $parts[] = $node;
        }
        return $parts ? array( array( '@type' => 'ItemList', '@id' => gep_site_current_url() . '#publicaciones', 'name' => 'Publicaciones del Laboratorio GEP', 'numberOfItems' => count( $parts ), 'itemListElement' => array_map( function ( $node, $i ) { return array( '@type' => 'ListItem', 'position' => $i + 1, 'item' => $node ); }, $parts, array_keys( $parts ) ) ) ) : array();
    }
}

if ( ! function_exists( 'gep_site_render_head_meta' ) ) {
    function gep_site_render_head_meta( $page_title, $page_description, $active_page, $logo_path, $extra = array() ) {
        $base      = gep_site_base_url();
        $url       = gep_site_current_url();
        $org_id    = $base . '#organizacion';
        $site_id   = $base . '#sitio';
        $logo      = $logo_path ? $base . ltrim( (string) $logo_path, '/' ) : '';
        $page_name = trim( preg_replace( '/^GEP\s*·\s*/u', '', (string) $page_title ) );

        $org = array(
            '@type'         => array( 'ResearchOrganization', 'Organization' ),
            '@id'           => $org_id,
            'name'          => 'Laboratorio de Geomática y Ecología del Paisaje (GEP)',
            'alternateName' => 'GEP',
            'url'           => $base,
            'parentOrganization' => array(
                '@type' => 'EducationalOrganization',
                'name'  => 'Facultad de Ciencias Forestales y de la Conservación de la Naturaleza',
                'parentOrganization' => array( '@type' => 'CollegeOrUniversity', 'name' => 'Universidad de Chile', 'url' => 'https://www.uchile.cl/' ),
            ),
            'sameAs'        => array( 'https://chile.mapbiomas.org/', 'https://www.instagram.com/mapbiomas_chile/' ),
        );
        if ( $logo ) $org['logo'] = $logo;
        $contact = function_exists( 'gep_demo_structured_org_data' ) ? gep_demo_structured_org_data() : array();
        if ( ! empty( $contact['email'] ) ) $org['email'] = $contact['email'];
        if ( ! empty( $contact['telephone'] ) ) $org['telephone'] = $contact['telephone'];
        if ( ! empty( $contact['street'] ) ) {
            $org['address'] = array_filter( array(
                '@type'           => 'PostalAddress',
                'streetAddress'   => $contact['street'],
                'addressLocality' => $contact['locality'] ?? '',
                'addressRegion'   => $contact['region'] ?? '',
                'addressCountry'  => 'CL',
            ) );
        }

        $graph = array(
            $org,
            array( '@type' => 'WebSite', '@id' => $site_id, 'url' => $base, 'name' => 'GEP · Geomática y Ecología del Paisaje', 'inLanguage' => 'es-CL', 'publisher' => array( '@id' => $org_id ) ),
        );
        $page = array( '@type' => 'WebPage', '@id' => $url . '#pagina', 'url' => $url, 'name' => (string) $page_title, 'description' => (string) $page_description, 'inLanguage' => 'es-CL', 'isPartOf' => array( '@id' => $site_id ), 'about' => array( '@id' => $org_id ) );
        if ( 'inicio' !== $active_page && '' !== $page_name ) {
            $crumbs = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => $base ) );
            if ( isset( $_SERVER['SCRIPT_NAME'] ) && 'proyecto.php' === basename( (string) $_SERVER['SCRIPT_NAME'] ) ) {
                $crumbs[] = array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Proyectos', 'item' => $base . 'proyectos.php' );
            }
            $crumbs[] = array( '@type' => 'ListItem', 'position' => count( $crumbs ) + 1, 'name' => $page_name, 'item' => $url );
            $page['breadcrumb'] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs );
        }
        foreach ( is_array( $extra ) ? $extra : array() as $node ) {
            if ( ! is_array( $node ) ) continue;
            if ( isset( $node['@type'] ) && 'ItemList' === $node['@type'] ) $page['mainEntity'] = array( '@id' => $node['@id'] );
            $graph[] = $node;
        }
        $graph[] = $page;

        $json = json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP );
        $h    = function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); };
        echo '<link rel="canonical" href="' . $h( $url ) . '"/>' . "\n";
        echo '    <meta property="og:type" content="website"/>' . "\n";
        echo '    <meta property="og:locale" content="es_CL"/>' . "\n";
        echo '    <meta property="og:site_name" content="GEP · Geomática y Ecología del Paisaje"/>' . "\n";
        echo '    <meta property="og:title" content="' . $h( $page_title ) . '"/>' . "\n";
        echo '    <meta property="og:description" content="' . $h( $page_description ) . '"/>' . "\n";
        echo '    <meta property="og:url" content="' . $h( $url ) . '"/>' . "\n";
        // v9.23.0: imagen para WhatsApp, Facebook, LinkedIn, etc. (1200×630). El logo
        // solo (transparente y angosto) hacía que WhatsApp no mostrara ninguna imagen.
        $share = is_file( dirname( __DIR__ ) . '/assets/images/branding/compartir.jpg' ) ? $base . 'assets/images/branding/compartir.jpg' : $logo;
        if ( $share ) {
            echo '    <meta property="og:image" content="' . $h( $share ) . '"/>' . "\n";
            if ( $share !== $logo ) {
                echo '    <meta property="og:image:type" content="image/jpeg"/>' . "\n";
                echo '    <meta property="og:image:width" content="1200"/>' . "\n";
                echo '    <meta property="og:image:height" content="630"/>' . "\n";
            }
            echo '    <meta property="og:image:alt" content="GEP · Laboratorio de Geomática y Ecología del Paisaje, Universidad de Chile"/>' . "\n";
            echo '    <meta name="twitter:card" content="summary_large_image"/>' . "\n";
            echo '    <meta name="twitter:image" content="' . $h( $share ) . '"/>' . "\n";
        }
        if ( $json ) echo '    <script type="application/ld+json">' . $json . '</script>' . "\n";
    }
}
