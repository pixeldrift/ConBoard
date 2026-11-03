<?php
// includes/maps/maps_helpers.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Map helpers for ConBoard
 */

/**
 * Extract top-level SVG layers (<g id="..."> only)
 */
function conboard_get_location_map_layers( int $location_id ): array {

	$source_id = conboard_get_location_map_source( $location_id );
	if ( ! $source_id ) return [];

	$map_id = get_post_meta( $source_id, CONBOARD_LOCATION_MAP_FIELD, true );
	if ( ! $map_id ) return [];

	$svg_path = get_attached_file( (int) $map_id );
	if ( ! $svg_path || ! file_exists( $svg_path ) ) return [];

	$svg = file_get_contents( $svg_path );
	if ( ! $svg ) return [];

	libxml_use_internal_errors( true );
	$xml = simplexml_load_string( $svg );
	if ( ! $xml ) return [];

	$layers = [];

	// ONLY direct children of <svg>
	foreach ( $xml->children() as $node ) {
		if ( $node->getName() !== 'g' ) continue;

		$id = trim( (string) $node['id'] );
		if ( $id === '' ) continue;

		$layers[ $id ] = $id;
	}

	return $layers;
}

/**
 * Human-readable label for a select field value (e.g. location_type "room" -> "Room")
 */
function conboard_get_field_choice_label( string $field_name, $value, int $post_id ): string {
	if ( $value === '' || $value === null || $value === false ) {
		return '';
	}

	$field = get_field_object( $field_name, $post_id );

	if ( is_array( $field ) && isset( $field['choices'][ $value ] ) ) {
		return (string) $field['choices'][ $value ];
	}

	return ucwords( str_replace( '_', ' ', (string) $value ) );
}

/**
 * Sanitized inline markup for an SVG attachment.
 *
 * @param int        $map_id    SVG attachment id
 * @param string     $label     accessible label
 * @param array|null $selected  layer ids to show; null leaves every layer visible
 *                              (used by the admin preview, which toggles them in JS).
 *                              If none of the ids exist in the file, the whole map shows.
 */
function conboard_get_svg_markup( int $map_id, string $label, ?array $selected = null ): string {

	$path = get_attached_file( $map_id );
	if ( ! $path || ! file_exists( $path ) ) return '';

	$svg = file_get_contents( $path );
	if ( ! $svg || stripos( $svg, '<!ENTITY' ) !== false ) return '';

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$loaded = $dom->loadXML( $svg, LIBXML_NONET );
	libxml_clear_errors();

	if ( ! $loaded || ! $dom->documentElement || strtolower( $dom->documentElement->localName ) !== 'svg' ) {
		return '';
	}

	$root = $dom->documentElement;

	// Sanitize: drop anything that can run script or load other content
	$blocked = [ 'script', 'foreignobject', 'iframe', 'object', 'embed', 'audio', 'video', 'canvas', 'animate', 'set', 'animatetransform', 'animatemotion' ];

	foreach ( $blocked as $tag ) {
		$nodes = $dom->getElementsByTagName( $tag );
		while ( $nodes->length ) {
			$nodes->item( 0 )->parentNode->removeChild( $nodes->item( 0 ) );
		}
	}

	$xpath = new DOMXPath( $dom );
	foreach ( $xpath->query( '//@*' ) as $attr ) {
		$name  = strtolower( $attr->nodeName );
		$value = trim( $attr->nodeValue );

		$is_event   = strpos( $name, 'on' ) === 0;
		$is_link    = $name === 'href' || substr( $name, -5 ) === ':href';
		$bad_scheme = preg_match( '/^(javascript|data|vbscript):/i', $value );

		if ( $is_event || ( $is_link && $bad_scheme ) ) {
			$attr->ownerElement->removeAttributeNode( $attr );
		}
	}

	// Show only the chosen layers
	if ( $selected !== null ) {
		$layer_nodes = [];
		foreach ( $root->childNodes as $node ) {
			if ( $node instanceof DOMElement && strtolower( $node->localName ) === 'g' && $node->getAttribute( 'id' ) !== '' ) {
				$layer_nodes[ $node->getAttribute( 'id' ) ] = $node;
			}
		}

		$selected = array_intersect( array_map( 'strval', $selected ), array_map( 'strval', array_keys( $layer_nodes ) ) );

		if ( ! empty( $selected ) ) {
			foreach ( $layer_nodes as $id => $node ) {
				if ( ! in_array( (string) $id, $selected, true ) ) {
					$node->setAttribute( 'style', trim( $node->getAttribute( 'style' ) . ';display:none', ';' ) );
				}
			}
		}
	}

	// Make it scale
	if ( ! $root->hasAttribute( 'viewBox' ) && $root->hasAttribute( 'width' ) && $root->hasAttribute( 'height' ) ) {
		$w = (float) $root->getAttribute( 'width' );
		$h = (float) $root->getAttribute( 'height' );
		if ( $w > 0 && $h > 0 ) {
			$root->setAttribute( 'viewBox', "0 0 $w $h" );
		}
	}
	$root->removeAttribute( 'width' );
	$root->removeAttribute( 'height' );
	$root->setAttribute( 'style', trim( $root->getAttribute( 'style' ) . ';max-width:100%;height:auto;display:block', ';' ) );
	$root->setAttribute( 'role', 'img' );
	$root->setAttribute( 'aria-label', sanitize_text_field( $label ) );

	return $dom->saveXML( $root );
}

/**
 * Markup for one map layer image (SVG inline, raster as <img>)
 */
function conboard_get_map_base_markup( int $map_id, string $label, ?array $selected = null ): string {

	if ( get_post_mime_type( $map_id ) === 'image/svg+xml' ) {
		return conboard_get_svg_markup( $map_id, $label, $selected );
	}

	return (string) wp_get_attachment_image( $map_id, 'large', false, [ 'class' => 'conboard-map-image', 'alt' => esc_attr( $label ) ] );
}

/**
 * A decorative image laid over the base (highlights, labels)
 */
function conboard_get_map_overlay_img( int $attachment_id, string $class ): string {
	$url = wp_get_attachment_url( $attachment_id );
	if ( ! $url ) return '';
	return '<img class="conboard-map-overlay ' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="" aria-hidden="true" />';
}

/**
 * Front-end map for a location.
 *
 * - Uses the location's own map, or inherits the nearest parent's map. A deleted map file is
 *   skipped, so the parent's map is used.
 * - SVG maps are output inline and sanitized, showing only the layers assigned to this
 *   location (inherited from a parent if none are assigned). If no assigned layer exists
 *   in the file (for example the SVG was replaced), the whole map is shown.
 * - A location can instead lay a transparent PNG over its parent's map ("overlay"), and a
 *   labels image is drawn on top of everything, so highlighted rooms sit under the labels.
 * - Locations with "Show Map" turned off return nothing.
 *
 * @param array $args  'ignore_show_map' (bool) skip the Show Map toggle (admin preview)
 */
function conboard_get_location_map_html( int $location_id, array $args = [] ): string {

	if ( empty( $args['ignore_show_map'] ) && ! conboard_location_shows_map( $location_id ) ) {
		return '';
	}

	$stack = conboard_get_location_map_stack( $location_id );
	if ( ! $stack || ! $stack['base'] ) return '';

	$label = sprintf( 'Map of %s', get_the_title( $location_id ) );

	$base_is_svg = get_post_mime_type( $stack['base'] ) === 'image/svg+xml';
	$selected    = $base_is_svg ? conboard_get_inherited_map_layers( $location_id ) : null;

	$base = conboard_get_map_base_markup( $stack['base'], $label, $selected );
	if ( $base === '' ) return '';

	$layers = '';
	if ( $stack['highlight'] ) $layers .= conboard_get_map_overlay_img( $stack['highlight'], 'conboard-map-highlight' );
	if ( $stack['labels'] )    $layers .= conboard_get_map_overlay_img( $stack['labels'], 'conboard-map-labels' );

	if ( $layers === '' ) {
		return '<div class="conboard-location-map">' . $base . '</div>';
	}

	return '<div class="conboard-location-map conboard-map-stack">' . $base . $layers . '</div>';
}
