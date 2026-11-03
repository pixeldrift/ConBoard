<?php
// includes/display/display_params.php
//
// URL parameters understood by Screen Mode, e.g.
//   ?display=screen&orientation=portrait&rotate=90&flip=none

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Layout parameters. Each has a default and a list of allowed values.
 * (Older names are still accepted: rotate=left/right/none, orientation=horizontal/vertical.)
 */
function conboard_display_param_schema(): array {
	return [
		'display'		=> [ 'default' => 'screen',		'values' => [ 'screen' ] ],
		'orientation'	=> [ 'default' => 'landscape',	'values' => [ 'landscape', 'portrait' ] ],
		'rotate'		=> [ 'default' => '0',			'values' => [ '0', '90', '180', '270' ] ],
		'flip'			=> [ 'default' => 'none',		'values' => [ 'none', 'horizontal', 'vertical' ] ],
	];
}

/**
 * Extra one-off overrides (not part of the layout schema):
 *   interval=10   seconds each page of a looping screen stays up (3-300)
 *   refresh=60    seconds between live data refreshes (15-3600)
 *   clock=yes|no  show the clock
 *   qr=yes|no     show the QR code
 */
function conboard_display_override_names(): array {
	return [ 'interval', 'refresh', 'clock', 'qr' ];
}

// Every query parameter Screen Mode adds to a URL (used to build the "standard page" link)
function conboard_display_all_param_names(): array {
	return array_merge( array_keys( conboard_display_param_schema() ), conboard_display_override_names() );
}

/**
 * Clean one layout parameter value. Returns null when the value is not allowed.
 */
function conboard_normalize_display_param( string $key, $value ): ?string {

	$schema = conboard_display_param_schema();

	if ( ! isset( $schema[ $key ] ) || ! is_scalar( $value ) ) {
		return null;
	}

	$value = strtolower( trim( (string) $value ) );

	$aliases = [
		'orientation'	=> [ 'horizontal' => 'landscape', 'vertical' => 'portrait' ],
		'rotate'		=> [ 'none' => '0', 'left' => '270', 'right' => '90' ],
	];

	if ( isset( $aliases[ $key ][ $value ] ) ) {
		$value = $aliases[ $key ][ $value ];
	}

	return in_array( $value, $schema[ $key ]['values'], true ) ? $value : null;
}

// Whole number clamped to a range, or null when missing or not a number
function conboard_clamp_display_int( $value, int $min, int $max ): ?int {

	if ( ! is_scalar( $value ) || ! is_numeric( $value ) ) {
		return null;
	}

	return max( $min, min( $max, (int) $value ) );
}

// yes/no style value -> true/false, or null when missing or unclear
function conboard_parse_display_bool( $value ): ?bool {

	if ( ! is_scalar( $value ) ) {
		return null;
	}

	$value = strtolower( trim( (string) $value ) );

	if ( in_array( $value, [ '1', 'yes', 'true', 'on' ], true ) ) return true;
	if ( in_array( $value, [ '0', 'no', 'false', 'off' ], true ) ) return false;

	return null;
}
