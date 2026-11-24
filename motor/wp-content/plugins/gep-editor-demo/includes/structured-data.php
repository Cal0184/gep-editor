<?php
/**
 * GEP Editor v9.21.0 · Datos para Google (JSON-LD).
 *
 * El sitio (includes/structured-data.php del sitio) arma la ficha del
 * laboratorio con lo que ya está en Contacto: correo, teléfono y dirección.
 * Así, si cambian en el editor, Google ve el dato nuevo sin tocar código.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Correo, teléfono y dirección del laboratorio según la pestaña Contacto. */
function gep_demo_structured_org_data() {
    $data = array( 'email' => '', 'telephone' => '', 'street' => '', 'locality' => '', 'region' => '' );
    if ( ! function_exists( 'gep_demo_get_page_navigation' ) || ! function_exists( 'gep_demo_contact_values' ) ) {
        return $data;
    }
    $navigation = gep_demo_get_page_navigation();
    if ( empty( $navigation['contacto']['page'] ) ) {
        return $data;
    }
    $values = gep_demo_contact_values( (int) $navigation['contacto']['page']->ID );
    if ( ! empty( $values['primary_email'] ) && is_email( $values['primary_email'] ) ) {
        $data['email'] = sanitize_email( $values['primary_email'] );
    }
    foreach ( isset( $values['rows'] ) && is_array( $values['rows'] ) ? $values['rows'] : array() as $row ) {
        if ( ! is_array( $row ) || empty( $row['value'] ) ) {
            continue;
        }
        $label = function_exists( 'remove_accents' ) ? strtolower( remove_accents( (string) ( $row['label'] ?? '' ) ) ) : strtolower( (string) ( $row['label'] ?? '' ) );
        $value = trim( wp_strip_all_tags( (string) $row['value'] ) );
        if ( '' === $data['telephone'] && ( false !== strpos( $label, 'telefono' ) || ( isset( $row['kind'] ) && 'phone' === $row['kind'] ) ) ) {
            // "(56 2) 978 5877" -> "+56 2 978 5877"
            $phone = trim( preg_replace( '/\s+/', ' ', str_replace( array( '(', ')' ), ' ', $value ) ) );
            $data['telephone'] = preg_match( '/^56\b/', $phone ) ? '+' . $phone : $phone;
        } elseif ( '' === $data['street'] && false !== strpos( $label, 'direccion' ) ) {
            // "Av. Santa Rosa #11315 · La Pintana · Santiago de Chile"
            $parts = array_values( array_filter( array_map( 'trim', preg_split( '/\s*[·|]\s*/u', $value ) ) ) );
            $data['street']   = isset( $parts[0] ) ? str_replace( '#', '', $parts[0] ) : '';
            $data['locality'] = isset( $parts[1] ) ? $parts[1] : '';
            $data['region']   = isset( $parts[2] ) ? $parts[2] : '';
        }
        if ( '' === $data['email'] && isset( $row['kind'] ) && 'email' === $row['kind'] && is_email( $value ) ) {
            $data['email'] = sanitize_email( $value );
        }
    }
    return $data;
}
