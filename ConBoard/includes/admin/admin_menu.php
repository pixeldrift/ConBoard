<?php 
// admin/admin_menu.php

add_action('admin_menu', function () {
    add_menu_page(
        'ConBoard',
        'ConBoard',
        'manage_options',
        'conboard',
        'conboard_admin_overview',
        'dashicons-calendar-alt',
        25
    );
});
