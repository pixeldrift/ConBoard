<?php
// includes/admin/admin_dashboard.php

if ( ! defined('ABSPATH') ) exit;

/**
 * Render the ConBoard admin dashboard page.
 */
function conboard_admin_dashboard(): void {

    // Add cards for Settings and About pages
    $cards = array_merge(
        conboard_get_dashboard_cards(),
        conboard_get_cards_for_pages([
            'conboard_settings',
            'conboard_about'
        ])
    );

    echo '<div class="wrap">';
        
    echo '<center><img src="' . esc_url(CONBOARD_PLUGIN_URL) . '/assets/images/icon-128x128.png" style="width:128px;height:128px;margin-bottom:16px;"/>';

    echo '<h1>ConBoard Dashboard</h1>';

    echo '<div class="conboard-dashboard-cards">';
    ?>

            <?php foreach ( $cards as $card ) : ?>
                <a class="conboard-card" href="<?php echo esc_url( $card['url'] ); ?>">

                    <span class="conboard-card-icon">
                        <?php echo conboard_dashicon( $card['icon_code'] ); ?>
                    </span>

                    <div class="conboard-card-body">
                        <h3><?php echo esc_html( $card['title'] ); ?></h3>

                        <?php if ( isset( $card['count'] ) ) : ?>
                            <div class="conboard-card-count">
                                <?php echo intval( $card['count'] ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( isset( $card['subtitle'] ) ) : ?>
                            <p class="conboard-card-desc">
                                <?php echo esc_html( $card['subtitle'] ); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ( ! empty( $card['slug'] ) ) : ?>
                            <button
                                class="conboard-card-add"
                                type="button"
                                onclick="event.stopPropagation(); window.location.href='<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $card['slug'] ) ); ?>';"
                            >
                                <?php echo 'Add New ' . esc_html( $card['singular_name'] ); ?>
                            </button>
                        <?php endif; ?>

                    </div>

                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php

add_action('admin_menu', function () {
    global $submenu;

    if ( empty( $submenu['conboard'] ) ) {
        return;
    }

    foreach ( $submenu['conboard'] as $index => $item ) {
        if ( $item[2] === 'conboard_dashboard' ) {
            // Inject CSS class into the 5th slot
            $submenu['conboard'][ $index ][4] = 'conboard-hide-dashboard';
            break;
        }
    }
}, 100);

} // end function