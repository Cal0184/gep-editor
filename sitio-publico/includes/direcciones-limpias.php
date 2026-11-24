<?php
/**
 * GEP 9.29.0 · Direcciones limpias (sin «.php»).
 *
 * Cada página pública vive en su carpeta con un index.php de 3 líneas:
 *   /gep/proyectos/      → muestra proyectos.php
 *   /gep/proyecto/?proyecto=slug → muestra proyecto.php
 * El Inicio sigue siendo /gep/ (index.php).
 *
 * Por qué carpetas: el servidor (nginx, sin .htaccess) abre index.php dentro de
 * una carpeta, pero no tiene «comodín» que mande otras direcciones a PHP
 * (probado en el servidor real el 17-11 con la herramienta 9.29.0).
 *
 * Este archivo hace tres cosas:
 *   1. gep_direccion_limpia_preparar(): lo llaman las carpetas. Hace que la página
 *      se comporte EXACTAMENTE como antes (SCRIPT_NAME = /gep/proyectos.php), así
 *      el editor, los apartados ocultos y los datos para Google siguen igual.
 *   2. gep_direcciones_redirigir(): quien abre una dirección antigua «…/proyectos.php»
 *      va a «…/proyectos/» (301), conservando ?parámetros y #anclas del navegador.
 *      Solo si la carpeta existe: subir este archivo antes que las carpetas no rompe nada.
 *   3. gep_direcciones_filtrar_html(): al mostrar la página, cambia los enlaces
 *      «proyectos.php» por «/gep/proyectos/» y las rutas relativas («assets/…») por
 *      rutas desde la raíz («/gep/assets/…»), para que funcionen dentro de las carpetas.
 *      No cambia nada en WordPress: los botones guardados como «proyectos.php» siguen
 *      sirviendo.
 *
 * Desactivar sin borrar nada: define( 'GEP_DIRECCIONES_LIMPIAS', false ); en
 * includes/wp-bootstrap.php (antes del require de este archivo). Las carpetas siguen
 * funcionando; solo vuelven los enlaces «.php» y se dejan de redirigir.
 */

if ( ! function_exists( 'gep_direcciones_paginas' ) ) {

    /** Páginas con dirección limpia: archivo => carpeta ('' = raíz del sitio). */
    function gep_direcciones_paginas() {
        return array(
            'index.php'          => '',
            'quienes-somos.php'  => 'quienes-somos',
            'docencia.php'       => 'docencia',
            'proyectos.php'      => 'proyectos',
            'proyecto.php'       => 'proyecto',
            'servicios.php'      => 'servicios',
            'publicaciones.php'  => 'publicaciones',
            'comunicaciones.php' => 'comunicaciones',
            'noticias.php'       => 'noticias',
            'contactanos.php'    => 'contactanos',
        );
    }

    function gep_direcciones_activas() {
        return ! defined( 'GEP_DIRECCIONES_LIMPIAS' ) || GEP_DIRECCIONES_LIMPIAS;
    }

    /** Carpeta del sitio en el disco (/…/gep). */
    function gep_direcciones_raiz() {
        return dirname( __DIR__ );
    }

    /** Ruta pública del sitio, sin barra final («/gep», o «» si está en la raíz). */
    function gep_direcciones_base() {
        static $base = null;
        if ( null === $base ) {
            $script = isset( $_SERVER['SCRIPT_NAME'] ) ? str_replace( '\\', '/', (string) $_SERVER['SCRIPT_NAME'] ) : '/index.php';
            $base   = rtrim( str_replace( '\\', '/', dirname( $script ) ), '/.' );
            $base   = preg_replace( '#[^A-Za-z0-9/_\-.~%]#', '', $base );
        }
        return $base;
    }

    /** ¿Existe la carpeta limpia de esta página? */
    function gep_direcciones_carpeta_lista( $carpeta ) {
        return '' === $carpeta || is_file( gep_direcciones_raiz() . '/' . $carpeta . '/index.php' );
    }

    /**
     * 1. Lo llama /gep/<carpeta>/index.php. Devuelve la ruta del archivo de la página
     *    para hacer require en el ámbito global (las páginas usan variables globales).
     */
    function gep_direccion_limpia_preparar( $archivo ) {
        $paginas = gep_direcciones_paginas();
        if ( ! isset( $paginas[ $archivo ] ) || ! is_file( gep_direcciones_raiz() . '/' . $archivo ) ) {
            http_response_code( 404 );
            exit;
        }
        if ( ! defined( 'GEP_DIRECCION_LIMPIA' ) ) {
            define( 'GEP_DIRECCION_LIMPIA', $archivo );
        }
        // /gep/proyectos/index.php  →  /gep/proyectos.php  (como antes).
        $script = isset( $_SERVER['SCRIPT_NAME'] ) ? str_replace( '\\', '/', (string) $_SERVER['SCRIPT_NAME'] ) : '';
        $sitio  = rtrim( str_replace( '\\', '/', dirname( dirname( $script ) ) ), '/.' );
        $_SERVER['SCRIPT_NAME']     = $sitio . '/' . $archivo;
        $_SERVER['PHP_SELF']        = $sitio . '/' . $archivo;
        $_SERVER['SCRIPT_FILENAME'] = gep_direcciones_raiz() . '/' . $archivo;
        chdir( gep_direcciones_raiz() );
        // Enlaces y rutas correctas dentro de la carpeta, aunque wp-bootstrap.php sea el anterior.
        gep_direcciones_inicio();
        return gep_direcciones_raiz() . '/' . $archivo;
    }

    /** Dirección limpia de una página: «/gep/proyectos/», «/gep/». */
    function gep_direcciones_limpia( $archivo ) {
        $paginas = gep_direcciones_paginas();
        $carpeta = isset( $paginas[ $archivo ] ) ? $paginas[ $archivo ] : null;
        if ( null === $carpeta ) {
            return null;
        }
        return gep_direcciones_base() . '/' . ( '' === $carpeta ? '' : $carpeta . '/' );
    }

    /** 2. Dirección antigua «…/proyectos.php» → 301 a «…/proyectos/». */
    function gep_direcciones_redirigir() {
        if ( ! gep_direcciones_activas() || defined( 'GEP_DIRECCION_LIMPIA' ) || headers_sent() || PHP_SAPI === 'cli' ) {
            return;
        }
        $metodo = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
        if ( 'GET' !== $metodo && 'HEAD' !== $metodo ) {
            return;
        }
        $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        $ruta = (string) parse_url( $uri, PHP_URL_PATH );
        $paginas = gep_direcciones_paginas();
        $archivo = basename( $ruta );
        // Solo si la dirección pedida termina en el mismo archivo que se está ejecutando.
        $script  = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) $_SERVER['SCRIPT_NAME'] ) : '';
        if ( '' === $archivo || $archivo !== $script || ! isset( $paginas[ $archivo ] ) ) {
            return;
        }
        if ( ! gep_direcciones_carpeta_lista( $paginas[ $archivo ] ) ) {
            return; // Aún no se subió la carpeta: la página sigue funcionando con .php.
        }
        $destino = gep_direcciones_limpia( $archivo );
        $query   = (string) parse_url( $uri, PHP_URL_QUERY );
        if ( '' !== $query ) {
            $destino .= '?' . $query;
        }
        // Ruta desde la raíz (sin dominio ni puerto): el navegador la completa con https://gep.uchile.cl.
        header( 'Location: ' . $destino, true, 301 );
        header( 'Cache-Control: max-age=86400' );
        exit;
    }

    /** ¿Es una ruta relativa al sitio («assets/x.png», «proyectos.php»)? */
    function gep_direcciones_es_relativa( $v ) {
        return '' !== $v && ! preg_match( '#^(?:[a-z][a-z0-9+.\-]*:|//|/|\#|\?|\{|<|\$|%7B)#i', $v );
    }

    /** Convierte un valor de href/src. */
    function gep_direcciones_valor( $v ) {
        $orig = $v;
        if ( gep_direcciones_es_relativa( $v ) ) {
            $v = ltrim( preg_replace( '#^(\./)+#', '', $v ), '' );
            $v = gep_direcciones_base() . '/' . $v;
        }
        // «/gep/proyectos.php?x#y» → «/gep/proyectos/?x#y»
        $base = gep_direcciones_base();
        if ( preg_match( '#^' . preg_quote( $base, '#' ) . '/([a-z\-]+\.php)([?\#].*)?$#', $v, $m ) ) {
            $paginas = gep_direcciones_paginas();
            if ( isset( $paginas[ $m[1] ] ) && gep_direcciones_carpeta_lista( $paginas[ $m[1] ] ) ) {
                $v = gep_direcciones_limpia( $m[1] ) . ( isset( $m[2] ) ? $m[2] : '' );
            }
        }
        return '' === $v ? $orig : $v;
    }

    /** 3. Filtro de la página: enlaces limpios y rutas desde la raíz. */
    function gep_direcciones_filtrar_html( $html ) {
        if ( ! is_string( $html ) || '' === $html || ! gep_direcciones_activas() ) {
            return $html;
        }
        foreach ( headers_list() as $h ) {
            if ( 0 === stripos( $h, 'Content-Type:' ) && false === stripos( $h, 'html' ) ) {
                return $html; // JSON, texto, etc.: no se toca.
            }
        }
        if ( false === stripos( $html, '<html' ) && false === stripos( $html, '<body' ) ) {
            return $html;
        }
        // a) href, src y poster (incluye <link>, <script>, <img>, <video>, <source>, <a>).
        $html = preg_replace_callback(
            '#(\s(?:href|src|poster|data-src|data-href)\s*=\s*)(["\'])([^"\']*)\2#i',
            function ( $m ) {
                return $m[1] . $m[2] . gep_direcciones_valor( $m[3] ) . $m[2];
            },
            $html
        );
        // b) srcset: lista «url ancho, url ancho».
        $html = preg_replace_callback(
            '#(\ssrcset\s*=\s*)(["\'])([^"\']*)\2#i',
            function ( $m ) {
                $partes = array_map( 'trim', explode( ',', $m[3] ) );
                foreach ( $partes as $i => $p ) {
                    $t = preg_split( '#\s+#', $p, 2 );
                    if ( '' !== $t[0] && gep_direcciones_es_relativa( $t[0] ) ) {
                        $partes[ $i ] = gep_direcciones_valor( $t[0] ) . ( isset( $t[1] ) ? ' ' . $t[1] : '' );
                    }
                }
                return $m[1] . $m[2] . implode( ', ', $partes ) . $m[2];
            },
            $html
        );
        // c) url(...) relativos en estilos dentro de la página.
        $html = preg_replace_callback(
            '#url\(\s*(&quot;|["\']?)([^)"\'&]*)\1\s*\)#i',
            function ( $m ) {
                return gep_direcciones_es_relativa( $m[2] ) ? 'url(' . $m[1] . gep_direcciones_valor( $m[2] ) . $m[1] . ')' : $m[0];
            },
            $html
        );
        // d) Direcciones completas del propio sitio (canonical, og:url, datos para Google),
        //    también con barras escapadas de JSON: https:\/\/gep.uchile.cl\/gep\/proyectos.php
        $host = isset( $_SERVER['HTTP_HOST'] ) ? preg_replace( '/[^A-Za-z0-9.:\-]/', '', (string) $_SERVER['HTTP_HOST'] ) : '';
        if ( '' !== $host ) {
            $s    = '(?:/|\\\\/)';
            $segs = array_filter( explode( '/', gep_direcciones_base() ), 'strlen' );
            $base = '';
            foreach ( $segs as $seg ) {
                $base .= $s . preg_quote( $seg, '#' );
            }
            $paginas = gep_direcciones_paginas();
            $html = preg_replace_callback(
                '#(https?:' . $s . $s . preg_quote( $host, '#' ) . $base . ')(' . $s . ')([a-z\-]+)\.php(?![A-Za-z0-9_\-])#i',
                function ( $m ) use ( $paginas ) {
                    $archivo = strtolower( $m[3] ) . '.php';
                    if ( ! isset( $paginas[ $archivo ] ) || ! gep_direcciones_carpeta_lista( $paginas[ $archivo ] ) ) {
                        return $m[0];
                    }
                    $carpeta = $paginas[ $archivo ];
                    return $m[1] . $m[2] . ( '' === $carpeta ? '' : $carpeta . $m[2] );
                },
                $html
            );
        }
        return $html;
    }

    /** Arranca el filtro una sola vez (lo llama wp-bootstrap.php). */
    function gep_direcciones_inicio() {
        static $iniciado = false;
        if ( $iniciado || ! gep_direcciones_activas() || PHP_SAPI === 'cli' ) {
            return;
        }
        $iniciado = true;
        ob_start( 'gep_direcciones_filtrar_html' );
    }
}
