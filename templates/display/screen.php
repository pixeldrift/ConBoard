<?php
// templates/display/screen.php
//
// Standalone Screen Mode page: no theme header, footer or admin bar.
// Content comes from includes/display/display_screens.php.

if ( ! defined( 'ABSPATH' ) ) exit;

conboard_register_screen_assets();

$ctx     = conboard_get_display_context();
$title   = conboard_screen_title();
$content = conboard_screen_render_content();

// Server time lets the clock stay right even if the display computer's clock is off
$tz_offset = (int) round( wp_timezone()->getOffset( new DateTime( 'now', wp_timezone() ) ) / 60 );

$config = [
	'orientation'	=> $ctx['orientation'],
	'rotate'		=> (int) $ctx['rotate'],
	'flip'			=> $ctx['flip'],
	'interval'		=> (int) $ctx['interval'],
	'refresh'		=> (int) $ctx['refresh'],
	'clock'			=> (bool) $ctx['clock'],
	'qr'			=> (bool) $ctx['qr'],
];

wp_add_inline_script( 'conboard-signage', 'window.ConBoardScreen = ' . wp_json_encode( $config ) . ';', 'before' );

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $title . ' – ' . get_bloginfo( 'name' ) ); ?></title>
	<?php wp_print_styles( 'conboard-screen' ); ?>
</head>
<body class="cb-screen">

	<div id="cb-viewport">

		<div id="cb-stage" class="is-<?php echo esc_attr( $ctx['orientation'] ); ?><?php echo $ctx['clock'] ? '' : ' no-clock'; ?><?php echo $ctx['qr'] ? '' : ' no-qr'; ?>">

			<header class="cb-header">
				<h1 id="cb-title"><?php echo esc_html( $title ); ?></h1>
				<div id="cb-clock"
					data-server-time="<?php echo esc_attr( time() * 1000 ); ?>"
					data-offset="<?php echo esc_attr( $tz_offset ); ?>">
					<div class="cb-clock-time"></div>
					<div class="cb-clock-date"></div>
				</div>
			</header>

			<main id="cb-content">
				<?php echo $content; // Escaped in display_screens.php ?>
			</main>

			<footer class="cb-footer">
				<div class="cb-site"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
				<div id="cb-qr" data-url="<?php echo esc_url( $ctx['standard_url'] ); ?>">
					<div id="cb-qr-code"></div>
					<div class="cb-qr-label">Scan for details</div>
				</div>
			</footer>

		</div>

	</div>

	<div id="cb-menu">
		<button type="button" id="cb-menu-toggle" aria-label="Screen settings" aria-expanded="false">&#9881;</button>
		<div id="cb-menu-panel" hidden>
			<button type="button" data-action="fullscreen">Fullscreen</button>
			<button type="button" data-action="rotate-left">Rotate left</button>
			<button type="button" data-action="rotate-right">Rotate right</button>
			<button type="button" data-action="flip-horizontal">Flip horizontal</button>
			<button type="button" data-action="flip-vertical">Flip vertical</button>
			<button type="button" data-action="orientation">Portrait / Landscape</button>
			<button type="button" data-action="clock">Show / hide clock</button>
			<button type="button" data-action="qr">Show / hide QR code</button>
			<button type="button" data-action="reset">Reset to defaults</button>
		</div>
	</div>

	<?php wp_print_scripts( 'conboard-signage' ); ?>
</body>
</html>
