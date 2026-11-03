<?php
// includes/links.php
//
// Social media links and web links for presenters, sponsors and staff.
// Edited with ConBoard's own row editor (no paid ACF repeater needed).
//
// Stored on the post as:
//   _conboard_socials  list of [ 'platform' => string, 'url' => string, 'label' => string ]
//   _conboard_links    list of [ 'url' => string, 'label' => string ]
// Posts saved by older versions (ACF repeaters named socials, links or presenter_websites)
// are still read until the post is saved again, which converts them.

if ( ! defined( 'ABSPATH' ) ) exit;

// Post types that have social and web links
function conboard_link_post_types(): array {
	return [ 'presenter', 'sponsor', 'staff' ];
}

function conboard_get_social_platforms(): array {
	return apply_filters( 'conboard_social_platforms', [
		'instagram'	=> 'Instagram',
		'youtube'	=> 'YouTube',
		'tiktok'	=> 'TikTok',
		'facebook'	=> 'Facebook',
		'twitter'	=> 'Twitter / X',
		'linkedin'	=> 'LinkedIn',
		'threads'	=> 'Threads',
		'bluesky'	=> 'Bluesky',
		'mastodon'	=> 'Mastodon',
		'twitch'	=> 'Twitch',
		'discord'	=> 'Discord',
	] );
}

// Rows written by the old ACF repeaters, read straight from post meta
function conboard_read_legacy_link_rows( int $post_id, string $base, array $sub_keys ): array {

	$count = (int) get_post_meta( $post_id, $base, true );
	$rows  = [];

	for ( $i = 0; $i < $count; $i++ ) {
		$row = [];
		foreach ( $sub_keys as $sub ) {
			$row[ $sub ] = (string) get_post_meta( $post_id, $base . '_' . $i . '_' . $sub, true );
		}
		$rows[] = $row;
	}

	return $rows;
}

function conboard_clean_link_rows( array $rows, bool $with_platform ): array {

	$platforms = conboard_get_social_platforms();
	$out       = [];

	foreach ( $rows as $row ) {
		$url = esc_url_raw( trim( (string) ( $row['url'] ?? '' ) ) );

		if ( $url === '' ) {
			continue;
		}

		$entry = [ 'url' => $url, 'label' => trim( (string) ( $row['label'] ?? '' ) ) ];

		if ( $with_platform ) {
			$platform = (string) ( $row['platform'] ?? '' );
			$entry['platform'] = isset( $platforms[ $platform ] ) ? $platform : '';
		}

		$out[] = $entry;
	}

	return $out;
}

// Social links of a post: [ [ 'platform', 'url', 'label' ], ... ] (label always filled in)
function conboard_get_socials( int $post_id ): array {

	$stored = get_post_meta( $post_id, '_conboard_socials', true );

	if ( ! is_array( $stored ) ) {
		$stored = conboard_read_legacy_link_rows( $post_id, 'socials', [ 'platform', 'url', 'label' ] );
	}

	$platforms = conboard_get_social_platforms();
	$out       = [];

	foreach ( conboard_clean_link_rows( $stored, true ) as $row ) {
		if ( $row['label'] === '' ) {
			$row['label'] = $platforms[ $row['platform'] ] ?? ( wp_parse_url( $row['url'], PHP_URL_HOST ) ?: $row['url'] );
		}
		$out[] = $row;
	}

	return $out;
}

// Web links of a post: [ [ 'url', 'label' ], ... ] (label always filled in)
function conboard_get_links( int $post_id ): array {

	$stored = get_post_meta( $post_id, '_conboard_links', true );

	if ( ! is_array( $stored ) ) {
		$stored = array_merge(
			conboard_read_legacy_link_rows( $post_id, 'links', [ 'url', 'label' ] ),
			conboard_read_legacy_link_rows( $post_id, 'presenter_websites', [ 'url', 'label' ] )
		);
	}

	$out = [];

	foreach ( conboard_clean_link_rows( $stored, false ) as $row ) {
		if ( $row['label'] === '' ) {
			$row['label'] = wp_parse_url( $row['url'], PHP_URL_HOST ) ?: $row['url'];
		}
		$out[] = $row;
	}

	return $out;
}

// Save a post's links (rows come from conboard_sanitize_rows)
function conboard_save_links( int $post_id, array $socials, array $links ): void {

	update_post_meta( $post_id, '_conboard_socials', conboard_clean_link_rows( $socials, true ) );
	update_post_meta( $post_id, '_conboard_links', conboard_clean_link_rows( $links, false ) );

	conboard_delete_legacy_links( $post_id );
}

// Remove the old repeaters' meta so removed links do not come back
function conboard_delete_legacy_links( int $post_id ): void {

	global $wpdb;

	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta}
		 WHERE post_id = %d
		   AND meta_key REGEXP '^_?(socials|links|presenter_websites)(_[0-9]+_(platform|url|label))?$'",
		$post_id
	) );

	wp_cache_delete( $post_id, 'post_meta' );
}

// Every social and web link of a post as a simple list, for templates
// ( $extra_class is added to the <ul> ). Returns '' when there are none.
function conboard_render_post_links( int $post_id, string $class = 'conboard-links' ): string {

	$items = array_merge( conboard_get_socials( $post_id ), conboard_get_links( $post_id ) );

	if ( empty( $items ) ) {
		return '';
	}

	$html = '<ul class="' . esc_attr( $class ) . '">';

	foreach ( $items as $item ) {
		$html .= '<li><a href="' . esc_url( $item['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $item['label'] ) . '</a></li>';
	}

	return $html . '</ul>';
}
