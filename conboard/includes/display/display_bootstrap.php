<?php
// includes/display/bootstrap.php

add_action('template_redirect', function () {

    if (!conboard_is_display()) {
        return;
    }

    $schema   = conboard_display_param_schema();
    $expected = [];

    foreach ($schema as $key => $config) {
        $expected[$key] = $_GET[$key] ?? $config['default'];
    }

    ksort($expected);

    $current = array_intersect_key($_GET, $expected);
    ksort($current);

    if ($expected !== $current) {
        wp_safe_redirect(add_query_arg($expected), 302);
        exit;
    }
});
