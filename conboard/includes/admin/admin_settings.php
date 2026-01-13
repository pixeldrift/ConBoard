<?php
// includes/admin/admin_settings.php

if ( ! defined('ABSPATH') ) exit;


/**
 * Bootstrap sponsor levels into options (once)
 */
function conboard_get_sponsor_levels_from_settings() {

	$stored = get_option( 'conboard_sponsor_levels' );

	if ( is_array( $stored ) && ! empty( $stored ) ) {
		return $stored;
	}

	// Fallback to hardcoded defaults (existing behavior)
	return conboard_get_sponsor_levels();
}

/**
 * Register plugin settings.
 */
add_action( 'admin_init', function () {

	register_setting(
		'conboard_settings',
		'conboard_sponsor_levels',
		[
			'type'				=> 'array',
			'sanitize_callback'	=> 'conboard_sanitize_sponsor_levels',
			'default'			=> conboard_get_sponsor_levels(),
		]
	);

});

// 
function conboard_sanitize_sponsor_levels( $input ) {

	$output = [];
	$order  = 1;

	if ( ! is_array( $input ) ) {
		return conboard_get_sponsor_levels();
	}

	foreach ( $input as $row ) {

		$original = sanitize_key( $row['_original'] ?? '' );
		$slug	 = sanitize_key( $row['slug'] ?? $original );

		if ( empty( $slug ) ) {
			continue;
		}

		$output[ $slug ] = [
			'label'		=> sanitize_text_field( $row['label'] ?? '' ),
			'singular'	=> sanitize_text_field( $row['singular'] ?? '' ),
			'plural'	=> sanitize_text_field( $row['plural'] ?? '' ),
			'order'		=> $order++,
		];

		// Handle slug change: reassign sponsors
		if ( $original && $original !== $slug ) {
			conboard_migrate_sponsor_level_slug( $original, $slug );
		}
	}

	return $output;
}

function conboard_migrate_sponsor_level_slug( $old, $new ) {

	$query = new WP_Query([
		'post_type'	  => 'sponsor',
		'posts_per_page'	=> -1,
		'meta_query'		=> [
			[
				'key'	=> 'sponsor_level',
				'value'	=> $old,
			],
		],
	]);

	foreach ( $query->posts as $post ) {
		update_post_meta( $post->ID, 'sponsor_level', $new );
	}
}



/**
 * Render the ConBoard settings page.
 */
function conboard_admin_settings(): void {

	echo '<div class="wrap">';

	echo '<center><img src="' . esc_url(CONBOARD_PLUGIN_URL) . '/assets/images/icon-128x128.png" style="width:128px;height:128px;margin-bottom:16px;"/>';

	echo '<h1>ConBoard – Dashboard</h1>';

	$levels = conboard_get_sponsor_levels_from_settings();


// Activate the Javascript interactive portion
add_action('admin_enqueue_scripts', function ($hook) {

	if ($hook !== 'toplevel_page_conboard') {
		return;
	}

	wp_enqueue_script(
		'conboard-admin',
		CONBOARD_PLUGIN_URL . '/assets/js/admin_settings.js',
		[],
		'1.0',
		true
	);
});



?>

<hr>
<h2>Sponsor Levels</h2>

<table class="widefat striped" id="conboard-sponsor-levels">
	<thead>
		<tr>
			<th>Label</th>
			<th>Singular</th>
			<th>Plural</th>
			<th>Slug</th>
			<th>Order</th>
			<th colspan="2">Actions</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $levels as $slug => $level ) : ?>
		<tr data-key="<?php echo esc_attr( $slug ); ?>" data-editing="false">

		<!-- DISPLAY MODE -->
			<td class="display"><?php echo esc_html( $level['label'] ); ?></td>
			<td class="display"><?php echo esc_html( $level['singular'] ); ?></td>
			<td class="display"><?php echo esc_html( $level['plural'] ); ?></td>
			<td class="display"><code><?php echo esc_html( $slug ); ?></code></td>
			<td class="display"><?php echo esc_html( $level['order'] ) ?></td>
			<td class="display">
				<button type="button" class="button button-small conboard-edit">Edit</button>
			</td>
			<td>
				<button type="button" class="button button-small conboard-delete">Delete</button>
			</td>

		<!-- EDIT MODE -->
			<td class="edit">
				<input name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][label]"
				 value="<?php echo esc_attr( $level['label'] ); ?>">
			</td>

			<td class="edit">
				<input name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][singular]"
				 value="<?php echo esc_attr( $level['singular'] ); ?>">
			</td>

			<td class="edit">
				<input name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][plural]"
				 value="<?php echo esc_attr( $level['plural'] ); ?>">
			</td>

			<td class="edit">
				<input name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][slug]"
				value="<?php echo esc_attr( $slug ); ?>">
			</td>
			<td class="edit">
				<input size="3" name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][order]"
				value="<?php echo esc_attr( $level['order'] ); ?>">
			<td class="edit">
				<button type="button" class="button button-secondary conboard-cancel">Cancel</button>
			</td>

	  </tr>
	<?php endforeach; ?>
 	</tbody>
</table>

<p>
	<button type="button" class="button" id="conboard-add-level">
		Add Sponsor Level
	</button>
</p>


<?php
}

add_action('admin_head', function () {
	echo '
	<style>
		h1 {
			margin-bottom: 40px;
		}
		h2 {
			margin: 20px 0 20px 0;
		}
		.conboard-edit::before {
			font-family: dashicons;
			content: "\f464";
			margin-right: 6px;
			font-size: 16px;
			vertical-align: middle;
			opacity: 0.9;
			display: inline-block;
			width: 16px;
			line-height: 1;
		}

		.conboard-delete::before {
			font-family: dashicons;
			content: "\f182";
			margin-right: 6px;
			font-size: 16px;
			vertical-align: middle;
			opacity: 0.9;
			display: inline-block;
			width: 16px;
			line-height: 1;

		}

		#conboard-add-level::before {
			font-family: dashicons;
			content: "\f132";
			margin-right: 6px;
			font-size: 16px;
			vertical-align: middle;
			opacity: 0.9;
			display: inline-block;
			width: 16px;
			line-height: 1;
		}
	</style>';

});
