<?php
/**
 * GEP Editor 9.27.1 · Operación Motor: ubicación del CMS.
 *
 * WordPress pasó de /gep/wordpress/ a /gep/motor/. Este archivo es la ÚNICA
 * fuente de las reglas que convierten direcciones de ubicaciones anteriores
 * del CMS a la ubicación actual. Lo usan:
 *   - el migrador de datos (mu-plugin gep-operacion-motor.php), que corrige
 *     la base de datos una sola vez;
 *   - la restauración del Historial y del Respaldo completo, para que una
 *     versión antigua no vuelva a escribir direcciones antiguas;
 *   - la red de seguridad de pantalla del editor (domain-urls.php) y del
 *     sitio público (media-urls.php), que solo actúa si queda algún dato
 *     sin migrar (por ejemplo, una instantánea antigua en la comparación).
 *
 * Qué se considera una ubicación anterior del CMS (host + carpeta):
 *   gep.uchile.cl/gep/wordpress   ubicación hasta 9.27.0
 *   gep.uchile.cl/wordpress       forma rota que pudo dejar la 9.21.4 (sin /gep)
 *   gepdev.bonfyr.cl/wordpress    servidor de desarrollo anterior
 * Se pueden agregar más con la constante GEP_CMS_UBICACIONES_ANTERIORES
 * ('host/carpeta,host/carpeta') o con el filtro gep_cms_ubicaciones_anteriores.
 *
 * Las reglas solo cambian el PRINCIPIO de una dirección completa
 * (https://host/carpeta), su forma dentro de JSON (https:\/\/host\/carpeta),
 * su forma codificada (https%3A%2F%2Fhost%2Fcarpeta) y la forma relativa
 * /gep/wordpress/ cuando va entre comillas, paréntesis o tras «=». Nunca
 * tocan otros dominios (por ejemplo www.gep.uchile.cl o wordpress.org).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'GEP_CMS_CARPETA' ) ) {
    define( 'GEP_CMS_CARPETA', 'motor' );
}

/** Convierte 'https://Host/a/b/' en array( 'host' => 'host', 'path' => '/a/b' ) o null. */
function gep_cms_parse_location( $value ) {
    $value = strtolower( trim( (string) $value ) );
    $value = preg_replace( '#^(?:https?:)?//#', '', $value );
    $value = rtrim( $value, '/' );
    if ( ! preg_match( '#^([a-z0-9.\-]+(?::\d+)?)(/[a-z0-9._\-/]+)$#', $value, $m ) ) {
        return null;
    }
    return array( 'host' => $m[1], 'path' => '/' . trim( $m[2], '/' ) );
}

/** Base actual del CMS (esquema + host + carpeta), sin barra final. */
function gep_cms_current_base() {
    return untrailingslashit( set_url_scheme( site_url( '/' ) ) );
}

/** Lista de ubicaciones anteriores del CMS (sin la base indicada). */
function gep_cms_previous_locations( $target_base = null ) {
    $list = array( 'gep.uchile.cl/gep/wordpress', 'gep.uchile.cl/wordpress', 'gepdev.bonfyr.cl/wordpress', 'www.gepdev.bonfyr.cl/wordpress' );
    if ( defined( 'GEP_CMS_UBICACIONES_ANTERIORES' ) && GEP_CMS_UBICACIONES_ANTERIORES ) {
        $list = array_merge( $list, explode( ',', (string) GEP_CMS_UBICACIONES_ANTERIORES ) );
    }
    $list   = (array) apply_filters( 'gep_cms_ubicaciones_anteriores', $list );
    $target = gep_cms_parse_location( null === $target_base ? gep_cms_current_base() : $target_base );
    $out    = array();
    foreach ( $list as $item ) {
        $loc = gep_cms_parse_location( $item );
        if ( ! $loc ) {
            continue;
        }
        if ( $target && $loc['host'] === $target['host'] && $loc['path'] === $target['path'] ) {
            continue; // Nunca se convierte la ubicación actual en sí misma.
        }
        $out[ $loc['host'] . $loc['path'] ] = $loc;
    }
    // Primero las más largas: gep.uchile.cl/gep/wordpress antes que gep.uchile.cl/wordpress.
    uasort( $out, function ( $a, $b ) {
        return strlen( $b['host'] . $b['path'] ) - strlen( $a['host'] . $a['path'] );
    } );
    return array_values( $out );
}

/** Ubicaciones anteriores del sitio público (solo del servidor de desarrollo). */
function gep_cms_previous_public_locations() {
    return array(
        array( 'host' => 'gepdev.bonfyr.cl', 'path' => '/gep_lep' ),
        array( 'host' => 'www.gepdev.bonfyr.cl', 'path' => '/gep_lep' ),
    );
}

/**
 * Reglas compiladas para una base de destino. Cada regla: patrón + función.
 * Se cachean por destino.
 */
function gep_cms_rules( $target_base = null ) {
    static $cache = array();
    $target_base = untrailingslashit( null === $target_base ? gep_cms_current_base() : (string) $target_base );
    if ( isset( $cache[ $target_base ] ) ) {
        return $cache[ $target_base ];
    }
    $rules = array();
    $t     = wp_parse_url( $target_base );
    if ( ! is_array( $t ) || empty( $t['host'] ) ) {
        return $cache[ $target_base ] = $rules;
    }
    $t_scheme = ! empty( $t['scheme'] ) ? $t['scheme'] : 'https';
    $t_host   = strtolower( $t['host'] ) . ( ! empty( $t['port'] ) ? ':' . (int) $t['port'] : '' );
    $t_path   = isset( $t['path'] ) ? '/' . trim( $t['path'], '/' ) : '';
    $t_segs   = array_values( array_filter( explode( '/', $t_path ), 'strlen' ) );

    $public_base = function_exists( 'gep_editor_public_base_url' ) ? untrailingslashit( gep_editor_public_base_url() ) : '';
    $p           = $public_base ? wp_parse_url( $public_base ) : null;

    $groups = array();
    foreach ( gep_cms_previous_locations( $target_base ) as $loc ) {
        $groups[] = array( $loc, $t_scheme, $t_host, $t_segs, true );
    }
    if ( is_array( $p ) && ! empty( $p['host'] ) ) {
        $p_host = strtolower( $p['host'] ) . ( ! empty( $p['port'] ) ? ':' . (int) $p['port'] : '' );
        $p_segs = array_values( array_filter( explode( '/', isset( $p['path'] ) ? $p['path'] : '' ), 'strlen' ) );
        foreach ( gep_cms_previous_public_locations() as $loc ) {
            $groups[] = array( $loc, ! empty( $p['scheme'] ) ? $p['scheme'] : 'https', $p_host, $p_segs, false );
        }
    }

    foreach ( $groups as $g ) {
        list( $loc, $scheme, $host, $segs, $is_cms ) = $g;
        $old_segs = array_values( array_filter( explode( '/', $loc['path'] ), 'strlen' ) );
        $path_re  = '';
        foreach ( $old_segs as $seg ) {
            $path_re .= '\3' . preg_quote( $seg, '#' );
        }
        // Dirección completa o sin esquema: (https)(:)(/)/host[:puerto]/carpeta  — separador «/», «\/» o «%2F».
        $pattern = '#(?<![A-Za-z0-9.\-])(?:(https?)(:|%3A))?(\\\\*/|%2F)\3' . preg_quote( $loc['host'], '#' ) . '(?::\d+)?' . $path_re . '(?![A-Za-z0-9_\-]|\.[A-Za-z0-9])#i';
        $rules[] = array(
            'pattern'  => $pattern,
            'callback' => function ( $m ) use ( $scheme, $host, $segs ) {
                $sep   = $m[3];
                $colon = isset( $m[2] ) && '' !== $m[2] ? $m[2] : '';
                $head  = '' !== $colon ? $scheme . $colon : '';
                return $head . $sep . $sep . $host . ( $segs ? $sep . implode( $sep, $segs ) : '' );
            },
            'needle'   => strtolower( end( $old_segs ) ),
            'cms'      => $is_cms,
        );
        // Forma relativa (/gep/wordpress/...) solo si la ubicación anterior es del mismo dominio.
        if ( $is_cms && $loc['host'] === $host && $old_segs ) {
            $rel_re  = '';
            foreach ( $old_segs as $i => $seg ) {
                $rel_re .= ( 0 === $i ? '' : '\1' ) . preg_quote( $seg, '#' );
            }
            $rules[] = array(
                'pattern'  => '#(?<![^"\'(=\s,>\[])(\\\\*/|%2F)' . $rel_re . '(?=\1|["\'\s)?\#<]|$)#i',
                'callback' => function ( $m ) use ( $segs ) {
                    $sep = $m[1];
                    return $sep . implode( $sep, $segs );
                },
                'needle'   => strtolower( end( $old_segs ) ),
                'cms'      => true,
            );
        }
    }
    return $cache[ $target_base ] = $rules;
}

/**
 * Convierte las direcciones de ubicaciones anteriores dentro de un texto
 * (texto simple, HTML o JSON sin decodificar). Devuelve el texto tal cual si
 * no hay nada que convertir. $count recibe la cantidad de reemplazos.
 */
function gep_cms_normalize_string( $value, $target_base = null, &$count = 0 ) {
    $count = 0;
    if ( ! is_string( $value ) || '' === $value ) {
        return $value;
    }
    $orig  = $value;
    $lower = strtolower( $value );
    foreach ( gep_cms_rules( $target_base ) as $rule ) {
        if ( '' === $rule['needle'] || false === strpos( $lower, $rule['needle'] ) ) {
            continue;
        }
        $n     = 0;
        $value = preg_replace_callback( $rule['pattern'], $rule['callback'], $value, -1, $n );
        if ( null === $value ) {
            $count = 0;
            return $orig; // Error de expresión: no se toca nada.
        }
        $count += $n;
    }
    return $value;
}

/** Igual que la anterior, pero recorre arreglos (sin tocar sus claves). */
function gep_cms_normalize_deep( $value, $target_base = null, &$count = 0 ) {
    $count = 0;
    if ( is_array( $value ) ) {
        foreach ( $value as $k => $child ) {
            $n           = 0;
            $value[ $k ] = gep_cms_normalize_deep( $child, $target_base, $n );
            $count      += $n;
        }
        return $value;
    }
    if ( is_string( $value ) ) {
        return gep_cms_normalize_string( $value, $target_base, $count );
    }
    return $value;
}
