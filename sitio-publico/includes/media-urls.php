<?php
/**
 * GEP v9.21.4 · Direcciones independientes del dominio e imágenes livianas.
 *
 * 1) El editor guarda las fotos, videos y enlaces internos con la dirección
 *    completa (p. ej. https://gepdev.bonfyr.cl/wordpress/wp-content/...).
 *    Al mostrar cada página, las direcciones de dominios propios antiguos
 *    se cambian por el dominio actual de WordPress. Así un cambio de dominio
 *    no rompe nada. Los dominios antiguos se recuerdan solos (opción
 *    gep_known_hosts) y se pueden agregar más con la constante
 *    GEP_SITE_OLD_HOSTS en wp-config.php ('dominio1.cl,dominio2.cl').
 *
 * 2) Si una foto de la Mediateca se muestra en su tamaño original (1-1,5 MB),
 *    se cambia por la copia reducida que WordPress ya generó al subirla:
 *    "large" (1024 px) en imágenes y "1536x1536" en fondos de cabecera.
 *    No hace falta subir versiones livianas.
 *
 * Todo ocurre al final, sobre el HTML ya armado: las plantillas no cambian.
 *
 * v9.26.1 · Migración a gep.uchile.cl/gep/: además del dominio se corrige la
 * ruta (/wordpress/... -> /gep/wordpress/...) cuando WordPress quedó dentro de
 * una carpeta base nueva. No modifica la base de datos.
 *
 * v9.27.1 · Operación Motor: antes del paso 1 se aplican las reglas de traslado
 * del CMS de GEP Editor (gep_cms_normalize_string), solo como red de seguridad.
 */

if ( ! function_exists( 'gep_site_media_start' ) ) {

    /** Dominio actual (esquema + host + puerto) según la configuración de WordPress. */
    function gep_site_media_current_origin() {
        static $origin = null;
        if ( null !== $origin ) return $origin;
        $origin = '';
        if ( function_exists( 'home_url' ) ) {
            $p = wp_parse_url( home_url( '/' ) );
            if ( is_array( $p ) && ! empty( $p['host'] ) ) {
                $origin = ( ! empty( $p['scheme'] ) ? $p['scheme'] : 'https' ) . '://' . $p['host'] . ( ! empty( $p['port'] ) ? ':' . (int) $p['port'] : '' );
            }
        }
        return $origin;
    }

    /** Hosts propios antiguos (sin el actual), en minúsculas. */
    function gep_site_media_old_hosts() {
        static $hosts = null;
        if ( null !== $hosts ) return $hosts;
        $list = array( 'gepdev.bonfyr.cl' );
        if ( defined( 'GEP_SITE_OLD_HOSTS' ) ) $list = array_merge( $list, explode( ',', (string) GEP_SITE_OLD_HOSTS ) );
        if ( function_exists( 'get_option' ) ) {
            $saved = get_option( 'gep_known_hosts', array() );
            if ( is_array( $saved ) ) $list = array_merge( $list, $saved );
        }
        $current = strtolower( (string) wp_parse_url( gep_site_media_current_origin(), PHP_URL_HOST ) );
        $hosts   = array();
        foreach ( $list as $h ) {
            $h = strtolower( trim( (string) $h ) );
            $h = preg_replace( '#^https?://#', '', $h );
            $h = preg_replace( '#[/:].*$#', '', $h );
            if ( '' === $h || $h === $current ) continue;
            $hosts[ $h ] = true;
            // www. y sin www. cuentan como el mismo sitio.
            $hosts[ 0 === strpos( $h, 'www.' ) ? substr( $h, 4 ) : 'www.' . $h ] = true;
        }
        unset( $hosts[ $current ] );
        $hosts = array_keys( $hosts );
        return $hosts;
    }

    /** Recuerda el dominio actual para reconocerlo si algún día cambia. */
    function gep_site_media_remember_host() {
        if ( ! function_exists( 'get_option' ) ) return;
        $host = strtolower( (string) wp_parse_url( gep_site_media_current_origin(), PHP_URL_HOST ) );
        if ( '' === $host || in_array( $host, array( 'localhost', '127.0.0.1' ), true ) ) return;
        $saved = get_option( 'gep_known_hosts', array() );
        $saved = is_array( $saved ) ? $saved : array();
        if ( ! in_array( $host, $saved, true ) ) {
            $saved[] = $host;
            update_option( 'gep_known_hosts', array_slice( $saved, -10 ), false );
        }
    }

    /**
     * v9.26.1 · Carpeta base actual cuando WordPress vive en una subcarpeta más
     * profunda que antes. Ej.: antes https://gepdev.bonfyr.cl/wordpress, ahora
     * https://gep.uchile.cl/gep/wordpress -> devuelve "/gep". Vacío si WordPress
     * está en una carpeta del primer nivel (como en el servidor antiguo).
     */
    function gep_site_media_base_path() {
        static $base = null;
        if ( null !== $base ) return $base;
        $base = '';
        if ( function_exists( 'site_url' ) ) {
            $path = trim( (string) wp_parse_url( site_url( '/' ), PHP_URL_PATH ), '/' );
            if ( '' !== $path && false !== strpos( $path, '/' ) ) {
                $base = '/' . substr( $path, 0, strrpos( $path, '/' ) );
            }
        }
        return $base;
    }

    /** v9.26.1 · Carpetas que se movieron junto con la base: la de WordPress y la del sitio público. */
    function gep_site_media_moved_folders() {
        static $folders = null;
        if ( null !== $folders ) return $folders;
        $folders = array();
        if ( function_exists( 'site_url' ) ) {
            $path = trim( (string) wp_parse_url( site_url( '/' ), PHP_URL_PATH ), '/' );
            if ( '' !== $path ) $folders[] = basename( $path );
        }
        $folders[] = basename( dirname( __DIR__ ) ); // carpeta del sitio público (GEP_LEP)
        $folders = array_values( array_unique( array_filter( $folders ) ) );
        return $folders;
    }

    /** Cambia el dominio de una dirección propia antigua por el actual. */
    function gep_site_media_fix_url( $url ) {
        $origin = gep_site_media_current_origin();
        if ( '' === $origin ) return $url;
        $hosts = gep_site_media_old_hosts();
        if ( ! $hosts ) return $url;
        if ( ! preg_match( '#^(?:https?:)?//([^/:?\#]+)(?::\d+)?#i', $url, $m ) ) return $url;
        if ( ! in_array( strtolower( $m[1] ), $hosts, true ) ) return $url;
        $rest = substr( $url, strlen( $m[0] ) );
        // v9.26.1: /wordpress/... del servidor antiguo -> /gep/wordpress/... si ahora hay carpeta base.
        $base = gep_site_media_base_path();
        if ( '' !== $base && 0 !== stripos( $rest . '/', $base . '/' ) ) {
            foreach ( gep_site_media_moved_folders() as $folder ) {
                if ( preg_match( '#^/' . preg_quote( $folder, '#' ) . '(?=[/?\#]|$)#i', $rest ) ) {
                    $rest = $base . $rest;
                    break;
                }
            }
        }
        return $origin . $rest;
    }

    /** ID de la Mediateca para una dirección de uploads (acepta copias -300x200 y -scaled). */
    function gep_site_media_attachment_id( $url ) {
        static $cache = array();
        if ( ! function_exists( 'attachment_url_to_postid' ) ) return 0;
        $clean = preg_replace( '/[?#].*$/', '', $url );
        if ( isset( $cache[ $clean ] ) ) return $cache[ $clean ];
        $id = attachment_url_to_postid( $clean );
        if ( ! $id ) {
            $base = preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $clean );
            if ( $base !== $clean ) $id = attachment_url_to_postid( $base );
            if ( ! $id && ! preg_match( '/-scaled\.[a-z0-9]+$/i', $base ) ) {
                $id = attachment_url_to_postid( preg_replace( '/(\.[a-z0-9]+)$/i', '-scaled$1', $base ) );
            }
        }
        return $cache[ $clean ] = (int) $id;
    }

    /**
     * Copia reducida de una foto de la Mediateca. Solo reemplaza el original
     * (o su versión -scaled); si ya es una copia reducida o no es de la
     * Mediateca, devuelve la dirección tal cual.
     */
    function gep_site_media_light_url( $url, $size = 'large' ) {
        if ( ! function_exists( 'wp_get_upload_dir' ) ) return $url;
        $uploads = wp_get_upload_dir();
        $base    = isset( $uploads['baseurl'] ) ? set_url_scheme( $uploads['baseurl'] ) : '';
        $check   = set_url_scheme( $url );
        if ( '' === $base || 0 !== strpos( $check, $base . '/' ) ) return $url;
        if ( ! preg_match( '/\.(?:jpe?g|png|webp)$/i', preg_replace( '/[?#].*$/', '', $url ) ) ) return $url;
        if ( preg_match( '/-\d+x\d+\.[a-z0-9]+$/i', preg_replace( '/[?#].*$/', '', $url ) ) ) return $url;
        $id = gep_site_media_attachment_id( $url );
        if ( ! $id ) return $url;
        $src = wp_get_attachment_image_src( $id, $size );
        if ( ! is_array( $src ) || empty( $src[0] ) || empty( $src[3] ) ) return $url; // [3] = es una copia reducida
        return $src[0];
    }

    /** Filtro final del HTML. */
    function gep_site_media_filter_html( $html ) {
        if ( ! is_string( $html ) || '' === $html || ! function_exists( 'home_url' ) ) return $html;

        // 0) v9.27.1 · Red de seguridad del traslado /gep/wordpress/ -> /gep/motor/ (reglas en
        //    GEP Editor, includes/cms-location.php). Tras el migrador no encuentra nada que cambiar.
        if ( function_exists( 'gep_cms_normalize_string' ) ) $html = gep_cms_normalize_string( $html );

        // 1) Dominio: direcciones normales y las que van dentro de JSON (https:\/\/...).
        if ( gep_site_media_old_hosts() ) {
            // v9.26.1: también toma la primera carpeta (/wordpress, /GEP_LEP) para corregir la ruta.
            $html = preg_replace_callback( '#(?:https?:)?//[A-Za-z0-9.\-]+(?::\d+)?(?:/[A-Za-z0-9._\-]+)?(?=[/"\'\s?\#<)]|$)#', function ( $m ) {
                return gep_site_media_fix_url( $m[0] );
            }, $html );
            $html = preg_replace_callback( '#https?:\\\\/\\\\/[A-Za-z0-9.\-]+(?::\d+)?(?:\\\\/[A-Za-z0-9._\-]+)?(?=\\\\/)#', function ( $m ) {
                return str_replace( '/', '\\/', gep_site_media_fix_url( str_replace( '\\/', '/', $m[0] ) ) );
            }, $html );
        }

        // 2) Fotos <img> en tamaño original -> copia "large".
        $html = preg_replace_callback( '#<img\b[^>]*>#i', function ( $m ) {
            $tag = $m[0];
            if ( false !== stripos( $tag, 'srcset=' ) ) return $tag;
            return preg_replace_callback( '#(\ssrc=")([^"]+)(")#i', function ( $s ) {
                $url = html_entity_decode( $s[2], ENT_QUOTES, 'UTF-8' );
                $new = gep_site_media_light_url( $url, 'large' );
                return $new === $url ? $s[0] : $s[1] . htmlspecialchars( $new, ENT_QUOTES, 'UTF-8' ) . $s[3];
            }, $tag, 1 );
        }, $html );

        // 2b) Foto ampliada de la galería de proyectos -> copia 1536 px.
        $html = preg_replace_callback( '#<a\b[^>]*data-project-lightbox[^>]*>#i', function ( $m ) {
            return preg_replace_callback( '#(\shref=")([^"]+)(")#i', function ( $s ) {
                $url = html_entity_decode( $s[2], ENT_QUOTES, 'UTF-8' );
                $new = gep_site_media_light_url( $url, '1536x1536' );
                return $new === $url ? $s[0] : $s[1] . htmlspecialchars( $new, ENT_QUOTES, 'UTF-8' ) . $s[3];
            }, $m[0], 1 );
        }, $html );

        // 3) Fondos de cabecera (style="background-image:url(...)") -> copia 1536 px.
        $html = preg_replace_callback( '#(url\(\s*[\'"]?)([^\'")\s]+)([\'"]?\s*\))#i', function ( $m ) {
            $url = html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' );
            $new = gep_site_media_light_url( $url, '1536x1536' );
            return $new === $url ? $m[0] : $m[1] . htmlspecialchars( $new, ENT_QUOTES, 'UTF-8' ) . $m[3];
        }, $html );

        // 4) v9.26.0 · Encuadre de la foto elegido en el editor (includes/image-frame.php).
        if ( function_exists( 'gep_site_frames_filter_html' ) ) $html = gep_site_frames_filter_html( $html );

        return $html;
    }

    /** Se llama al cargar WordPress; el filtro corre cuando WordPress cierra la página. */
    function gep_site_media_start() {
        static $started = false;
        if ( $started || ! function_exists( 'home_url' ) ) return;
        $started = true;
        gep_site_media_remember_host();
        ob_start( 'gep_site_media_filter_html' );
    }
}
