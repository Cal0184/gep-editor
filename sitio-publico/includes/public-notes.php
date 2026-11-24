<?php
/**
 * GEP v9.21.2 · Notas informativas al pie de cada página.
 * Las notas internas de la migración ("debe validarse antes de la publicación…")
 * no se muestran al público aunque sigan guardadas en la base de datos.
 * Una nota escrita por el equipo se muestra igual que antes.
 */
if ( ! function_exists( 'gep_site_public_note' ) ) {
    function gep_site_public_note( $text ) {
        $text  = trim( (string) $text );
        $plain = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
        $plain = preg_replace( '/\s+/u', ' ', $plain );
        $markers = array(
            'antes de la publicación',
            'antes de su publicación',
            'antes de una publicación institucional',
            'antes de publicar el nuevo sitio',
            'antes de habilitar la versión final',
        );
        foreach ( $markers as $marker ) {
            if ( false !== strpos( $plain, $marker ) ) {
                return '';
            }
        }
        return $text;
    }
}
