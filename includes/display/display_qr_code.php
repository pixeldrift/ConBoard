<?php
// includes/display/display_qr_code.php
//
// QR codes are drawn in the browser by the bundled library (assets/js/vendor/qrcode.js),
// so screens never depend on an outside service or the venue's internet connection to a
// third party. The QR code points to the normal page: see conboard_get_screen_standard_url().

if ( ! defined( 'ABSPATH' ) ) exit;
