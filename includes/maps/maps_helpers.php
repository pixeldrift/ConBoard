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

// -------------------------
// Checkbox layer logic
// -------------------------
add_filter(
	'acf/load_field/name=' . CONBOARD_LOCATION_MAP_LAYER_FIELD,
	function ( $field ) {

		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
		if ( ! $post_id ) return $field;

		// Populate choices
		$field['choices'] = conboard_get_location_map_layers( $post_id );

		// If no value saved yet, inherit from parent
		$current = get_post_meta( $post_id, CONBOARD_LOCATION_MAP_LAYER_FIELD, true );
		if ( empty( $current ) ) {
			$field['value'] = conboard_get_inherited_map_layers( $post_id );
		}

		return $field;
	},
	100
);

/**
 * Show a preview of the assigned map (or inherited map) below the field.
 */
add_action('acf/render_field/key=field_location_map', function($field){
	$post_id = get_the_ID();
	$map_field = get_field('location_map', $post_id);

	if (!$map_field) {
		$source_id = conboard_get_location_map_source($post_id);
		$map_field = $source_id ? get_field('location_map', $source_id) : null;
	}

	if ($map_field) {
		$url = is_numeric($map_field)
			? wp_get_attachment_url($map_field)
			: (is_array($map_field) && isset($map_field['url']) ? $map_field['url'] : $map_field);

		echo '<div class="conboard-map-layout">';

		echo '<div class="conboard-map-preview">';
		echo '<strong>Map Preview</strong><br>';
		echo '<img src="' . esc_url($url) . '" />';
		echo '</div>';

		echo '<div class="conboard-map-layers">';
		echo '<strong>Layers for this Location</strong>';
		echo '<p style="margin:6px 0 10px;color:#666;">Select which SVG layers are visible for this location.</p>';
		// DO NOT CLOSE DIV YET, must insert checkbox list first
	}
});

add_action('acf/render_field/key=field_location_map_layer', function($field){

	// Let ACF render the checkboxes normally
	// Then close the layout wrapper

	echo '</div>'; // .conboard-map-layers
	echo '</div>'; // .conboard-map-layout
});



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
 * Front-end map for a location.
 *
 * - Uses the location's own map, or inherits the nearest parent's map.
 * - SVG maps are output inline and sanitized, showing only the layers assigned to this
 *   location (inherited from a parent if none are assigned). If no assigned layer exists
 *   in the file (for example the SVG was replaced), the whole map is shown.
 * - Image maps (PNG/JPG) are output as a normal image.
 */
function conboard_get_location_map_html( int $location_id ): string {

	$source_id = conboard_get_location_map_source( $location_id );
	if ( ! $source_id ) return '';

	$map_id = (int) get_post_meta( $source_id, CONBOARD_LOCATION_MAP_FIELD, true );
	if ( ! $map_id ) return '';

	$label = esc_attr( sprintf( 'Map of %s', get_the_title( $location_id ) ) );

	// Raster maps
	if ( get_post_mime_type( $map_id ) !== 'image/svg+xml' ) {
		$img = wp_get_attachment_image( $map_id, 'large', false, [ 'class' => 'conboard-map-image', 'alt' => $label ] );
		return $img ? '<div class="conboard-location-map">' . $img . '</div>' : '';
	}

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

	// Show only the layers assigned to this location
	$layer_nodes = [];
	foreach ( $root->childNodes as $node ) {
		if ( $node instanceof DOMElement && strtolower( $node->localName ) === 'g' && $node->getAttribute( 'id' ) !== '' ) {
			$layer_nodes[ $node->getAttribute( 'id' ) ] = $node;
		}
	}

	$selected = array_map( 'strval', conboard_get_inherited_map_layers( $location_id ) );
	$selected = array_intersect( $selected, array_keys( $layer_nodes ) );

	if ( ! empty( $selected ) ) {
		foreach ( $layer_nodes as $id => $node ) {
			if ( ! in_array( (string) $id, $selected, true ) ) {
				$node->setAttribute( 'style', trim( $node->getAttribute( 'style' ) . ';display:none', ';' ) );
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
	$root->setAttribute( 'style', trim( $root->getAttribute( 'style' ) . ';max-width:100%;height:auto', ';' ) );
	$root->setAttribute( 'role', 'img' );
	$root->setAttribute( 'aria-label', sanitize_text_field( sprintf( 'Map of %s', get_the_title( $location_id ) ) ) );

	return '<div class="conboard-location-map">' . $dom->saveXML( $root ) . '</div>';
}
