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

	register_setting(
		'conboard_settings',
		'conboard_event_days',
		[
			'type'				=> 'array',
			'sanitize_callback'	=> 'conboard_sanitize_event_days',
			'default'			=> conboard_get_event_days_settings(),
		]
	);
});

// The script for the sponsor level table (only on this page)
add_action( 'admin_enqueue_scripts', function ( $hook ) {

	if ( strpos( (string) $hook, 'conboard_settings' ) === false ) {
		return;
	}

	wp_enqueue_script( 'conboard-sponsor-levels', CONBOARD_PLUGIN_URL . 'assets/js/sponsor_levels.js', [], '0.2.0', true );
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

		// A new level without a slug gets one from its label
		if ( empty( $slug ) ) {
			$slug = sanitize_key( sanitize_title( $row['label'] ?? '' ) );
		}

		if ( empty( $slug ) ) {
			continue;
		}

		$output[ $slug ] = [
			'label'		=> sanitize_text_field( $row['label'] ?? '' ),
			'singular'	=> sanitize_text_field( $row['singular'] ?? '' ) ?: sanitize_text_field( $row['label'] ?? '' ),
			'plural'	=> sanitize_text_field( $row['plural'] ?? '' ) ?: sanitize_text_field( $row['label'] ?? '' ),
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

	$levels = conboard_get_sponsor_levels_from_settings();
	$event  = conboard_get_event_days_settings();

	echo '<div class="wrap">';
	echo '<center><img src="' . esc_url( CONBOARD_PLUGIN_URL ) . '/assets/images/icon-128x128.png" style="width:128px;height:128px;margin-bottom:16px;"/>';
	echo '<h1>ConBoard – Settings</h1></center>';

	settings_errors();

	?>
	<form method="post" action="options.php">
		<?php settings_fields( 'conboard_settings' ); ?>

		<h2>Event Days</h2>
		<p class="description">Used to write dates the way your event does, for example "Day 2 – Monday – Jan 25". Schedule slides can also use <code>day1</code>, <code>day2</code>, and so on as their date.</p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="conboard-event-start">First day</label></th>
				<td><input type="date" id="conboard-event-start" name="conboard_event_days[start]" value="<?php echo esc_attr( $event['start'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="conboard-event-days">Number of days</label></th>
				<td><input type="number" id="conboard-event-days" name="conboard_event_days[days]" min="1" max="30" value="<?php echo esc_attr( (string) $event['days'] ); ?>" class="small-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="conboard-event-format">How to write a day</label></th>
				<td>
					<select id="conboard-event-format" name="conboard_event_days[format]">
						<?php foreach ( conboard_get_event_day_formats() as $key => $format ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $event['format'], $key ); ?>><?php echo esc_html( $format['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>

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
				<?php $name = 'conboard_sponsor_levels[' . esc_attr( $slug ) . ']'; ?>
				<tr data-key="<?php echo esc_attr( $slug ); ?>" data-editing="false">

					<td class="display"><?php echo esc_html( $level['label'] ); ?></td>
					<td class="display"><?php echo esc_html( $level['singular'] ); ?></td>
					<td class="display"><?php echo esc_html( $level['plural'] ); ?></td>
					<td class="display"><code><?php echo esc_html( $slug ); ?></code></td>
					<td class="display"><?php echo esc_html( (string) $level['order'] ); ?></td>
					<td class="display"><button type="button" class="button button-small conboard-edit">Edit</button></td>
					<td><button type="button" class="button button-small conboard-delete">Delete</button></td>

					<td class="edit"><input name="<?php echo $name; ?>[label]" value="<?php echo esc_attr( $level['label'] ); ?>"></td>
					<td class="edit"><input name="<?php echo $name; ?>[singular]" value="<?php echo esc_attr( $level['singular'] ); ?>"></td>
					<td class="edit"><input name="<?php echo $name; ?>[plural]" value="<?php echo esc_attr( $level['plural'] ); ?>"></td>
					<td class="edit">
						<input name="<?php echo $name; ?>[slug]" value="<?php echo esc_attr( $slug ); ?>">
						<input type="hidden" name="<?php echo $name; ?>[_original]" value="<?php echo esc_attr( $slug ); ?>">
					</td>
					<td class="edit"><?php echo esc_html( (string) $level['order'] ); ?></td>
					<td class="edit"><button type="button" class="button button-secondary conboard-cancel">Cancel</button></td>

				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<p class="description">Levels are listed in this order on the website and screens. Deleting a level removes it from the sponsors that used it.</p>

		<p>
			<button type="button" class="button" id="conboard-add-level">Add Sponsor Level</button>
		</p>

		<?php submit_button( 'Save Settings' ); ?>
	</form>
	</div>
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
