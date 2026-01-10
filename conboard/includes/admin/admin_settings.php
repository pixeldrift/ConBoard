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
            'type'              => 'array',
            'sanitize_callback' => 'conboard_sanitize_sponsor_levels',
            'default'           => conboard_get_sponsor_levels(),
        ]
    );

});

// 
function conboard_sanitize_sponsor_levels( $input ) {

    $output = [];

    if ( ! is_array( $input ) ) {
        return conboard_get_sponsor_levels();
    }

    $order = 1;

    foreach ( $input as $key => $level ) {

        $slug = sanitize_key( $key );

        if ( empty( $slug ) ) {
            continue;
        }

        $output[ $slug ] = [
            'singular' => sanitize_text_field( $level['singular'] ?? '' ),
            'plural'   => sanitize_text_field( $level['plural'] ?? '' ),
            'label'    => sanitize_text_field( $level['label'] ?? '' ),
            'order'    => $order++,
        ];
    }

    return $output;
}


/**
 * Render the ConBoard settings page.
 */
function conboard_admin_settings(): void {

    echo '<div class="wrap">';

    echo '<center><img src="' . esc_url(CONBOARD_PLUGIN_URL) . '/assets/images/icon-128x128.png" style="width:128px;height:128px;margin-bottom:16px;"/>';

    echo '<h1>ConBoard – Dashboard</h1>';

    $levels = conboard_get_sponsor_levels_from_settings();

?>

    <h2>Sponsor Levels</h2>

    <table class="form-table">
    <tr>
    <td>

    <div id="conboard-sponsor-levels">

    <?php foreach ( $levels as $slug => $level ) : ?>
        <div class="conboard-level-row" data-key="<?php echo esc_attr($slug); ?>" data-editing="false">

          <!-- DISPLAY MODE -->
          <div class="level-display">
            <strong><?php echo esc_html( $level['label'] ); ?></strong>
            <span class="muted">(<?php echo esc_html($slug); ?>)</span><br>
            <?php echo esc_html( $level['singular'] ); ?> /
            <?php echo esc_html( $level['plural'] ); ?>

            <div class="actions">
              <button type="button" class="button button-small conboard-edit-level">Edit</button>
              <button type="button" class="button button-small conboard-remove-level">Delete</button>
            </div>
          </div>

          <!-- EDIT MODE -->
          <div class="level-edit">
            <input type="text"
              name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][label]"
              value="<?php echo esc_attr( $level['label'] ); ?>" />

            <input type="text"
              name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][singular]"
              value="<?php echo esc_attr( $level['singular'] ); ?>" />

            <input type="text"
              name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][plural]"
              value="<?php echo esc_attr( $level['plural'] ); ?>" />

            <input type="hidden"
              name="conboard_sponsor_levels[<?php echo esc_attr($slug); ?>][key]"
              value="<?php echo esc_attr($slug); ?>" />

            <button type="button" class="button button-secondary conboard-cancel-edit">Cancel</button>
          </div>

        </div>
        <?php endforeach; ?>


    </div>

    <button type="button" class="button" id="conboard-add-sponsor-level">
        Add Sponsor Level
    </button>

    </td>
    </tr>
    </table>

<?php
}


