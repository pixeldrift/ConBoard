<?php
// includes/display/display_qr_code.php

function conboard_get_qr_url($post_id) {
    $url = urlencode(get_permalink($post_id));
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$url}";
}
