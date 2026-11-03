<?php
// includes/maps/maps_admin.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Map Preview & Layers" box on the Location editor.
 *
 * Shows the map that visitors will see and, for SVG maps, a checkbox per top-level layer.
 * Ticking boxes updates the preview immediately. Nothing ticked means "show the whole map".
 * Layers are stored in the same meta key as before (location_map_layer).
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'conboard_map_layers',
		'Map Preview & Layers',
		'conboard_render_map_layers_metabox',
		'location',
		'normal',
		'default'
	);
} );

function conboard_render_map_layers_metabox( WP_Post $post ): void {

	wp_nonce_field( 'conboard_map_layers', 'conboard_map_layers_nonce' );
	echo '<input type="hidden" name="conboard_map_layers_present" value="1">';

	$stack = conboard_get_location_map_stack( $post->ID );

	if ( ! $stack || ! $stack['base'] ) {
		echo '<p class="description">No map yet. Add a Map File above (or to a parent location) and save to preview it here.</p>';
		return;
	}

	$from_parent = $stack['source'] !== $post->ID;
	if ( $from_parent ) {
		printf(
			'<p class="description">Using the map from <strong>%s</strong>.</p>',
			esc_html( get_the_title( $stack['source'] ) )
		);
	}
	if ( ! conboard_location_shows_map( $post->ID ) ) {
		echo '<p class="description"><strong>Show Map is off</strong> for this location, so visitors will not see this map.</p>';
	}

	$is_svg = get_post_mime_type( $stack['base'] ) === 'image/svg+xml';

	$available = $is_svg ? conboard_get_location_map_layers( $post->ID ) : [];
	$saved     = get_post_meta( $post->ID, CONBOARD_LOCATION_MAP_LAYER_FIELD, true );
	$saved     = is_array( $saved ) ? array_map( 'strval', $saved ) : [];
	$effective = $saved ? $saved : array_map( 'strval', conboard_get_inherited_map_layers( $post->ID ) );

	$missing = array_values( array_diff( $effective, array_map( 'strval', array_keys( $available ) ) ) );
	$checked = array_values( array_intersect( $effective, array_map( 'strval', array_keys( $available ) ) ) );

	$label  = sprintf( 'Map of %s', get_the_title( $post->ID ) );
	$base   = conboard_get_map_base_markup( $stack['base'], $label, null );
	$layers = '';
	if ( $stack['highlight'] ) $layers .= conboard_get_map_overlay_img( $stack['highlight'], 'conboard-map-highlight' );
	if ( $stack['labels'] )    $layers .= conboard_get_map_overlay_img( $stack['labels'], 'conboard-map-labels' );

	echo '<div class="conboard-map-layout">';

	echo '<div class="conboard-map-preview"><div class="conboard-location-map conboard-map-stack" id="conboard-map-preview">' . $base . $layers . '</div></div>';

	echo '<div class="conboard-map-layers">';

	if ( $is_svg && $available ) {
		echo '<input type="hidden" name="conboard_map_layers_listed" value="1">';
		echo '<strong>Layers for this location</strong>';
		echo '<p class="description">Tick the layers visitors should see. Nothing ticked shows the whole map.</p>';

		foreach ( $available as $id => $name ) {
			printf(
				'<label><input type="checkbox" name="conboard_map_layers[]" value="%1$s" %2$s data-layer="%1$s"> %3$s</label>',
				esc_attr( $id ),
				checked( in_array( (string) $id, $checked, true ), true, false ),
				esc_html( $name )
			);
		}

		if ( $missing ) {
			echo '<p class="notice notice-warning inline" style="padding:8px 10px"><strong>Missing from the map file:</strong> '
				. esc_html( implode( ', ', $missing ) )
				. '. These were removed or renamed in the SVG and are ignored. Saving will drop them.</p>';
		}
	} elseif ( $is_svg ) {
		echo '<p class="description">This SVG has no top-level groups with an id, so there are no layers to choose. The whole map is shown. (In your drawing program, group the rooms and give each group an id.)</p>';
	} else {
		echo '<p class="description">Layers apply to SVG maps. This map is an image.</p>';
	}

	echo '<p class="description" style="margin-top:12px">After changing the Map File, click Update to refresh the layer list.</p>';

	echo '</div></div>';
}

add_action( 'save_post_location', function ( int $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( empty( $_POST['conboard_map_layers_present'] ) ) return;
	if ( ! isset( $_POST['conboard_map_layers_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['conboard_map_layers_nonce'] ) ), 'conboard_map_layers' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	// The box only has checkboxes when an SVG with layers is in use; otherwise leave the meta alone
	if ( empty( $_POST['conboard_map_layers_listed'] ) ) return;

	$layers = isset( $_POST['conboard_map_layers'] ) ? (array) wp_unslash( $_POST['conboard_map_layers'] ) : [];
	$layers = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $layers ), 'strlen' ) ) );

	if ( $layers ) {
		update_post_meta( $post_id, CONBOARD_LOCATION_MAP_LAYER_FIELD, $layers );
	} else {
		delete_post_meta( $post_id, CONBOARD_LOCATION_MAP_LAYER_FIELD );
	}
}, 5 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {

	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'location' ) return;

	wp_enqueue_script(
		'conboard-admin-map-preview',
		CONBOARD_PLUGIN_URL . '/assets/js/admin_map_preview.js',
		[],
		defined( 'CONBOARD_VERSION' ) ? CONBOARD_VERSION : '1',
		true
	);
} );

add_action( 'admin_head', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || $screen->post_type !== 'location' ) return;
	?>
	<style>
		.conboard-map-layout { display:flex; gap:20px; align-items:flex-start; margin-top:6px; }
		.conboard-map-preview { flex:0 0 min(600px, 60%); }
		.conboard-map-layers { flex:1; max-height:480px; overflow-y:auto; padding:6px 4px 6px 16px; border-left:1px solid #ddd; }
		.conboard-map-layers label { display:block; margin-bottom:6px; }
		.conboard-map-preview .conboard-location-map { border:1px solid #ccc; background:#fff; }
		.conboard-map-stack { position:relative; }
		.conboard-map-stack > .conboard-map-overlay { position:absolute; inset:0; width:100%; height:100%; object-fit:contain; pointer-events:none; }
		.conboard-map-preview svg, .conboard-map-preview img.conboard-map-image { max-width:100%; height:auto; display:block; }
	</style>
	<?php
} );
