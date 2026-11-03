<?php
// includes/links.php
//
// Links (website, social media, anything else) for presenters, sponsors and staff.
// One list per post. Each link has a type (Website, Instagram, ...), a URL and an optional label.
// Edited with ConBoard's own row editor (no paid ACF repeater needed).
//
// Stored on the post as _conboard_links: a list of [ 'platform' => string, 'url' => string, 'label' => string ].
// Posts saved by older versions (ACF repeaters named socials, links or presenter_websites)
// are still read until the post is saved again, which converts them.

if ( ! defined( 'ABSPATH' ) ) exit;

// Post types that have links
function conboard_link_post_types(): array {
	return [ 'presenter', 'sponsor', 'staff' ];
}

// Link types: key => label
function conboard_get_link_types(): array {
	return apply_filters( 'conboard_link_types', [
		'website'	=> 'Website',
		'facebook'	=> 'Facebook',
		'instagram'	=> 'Instagram',
		'youtube'	=> 'YouTube',
		'tiktok'	=> 'TikTok',
		'twitter'	=> 'Twitter / X',
		'bluesky'	=> 'Bluesky',
		'linkedin'	=> 'LinkedIn',
		'substack'	=> 'Substack',
		'threads'	=> 'Threads',
		'mastodon'	=> 'Mastodon',
		'twitch'	=> 'Twitch',
		'discord'	=> 'Discord',
		'other'		=> 'Other',
	] );
}

// The blank links suggested on a post that has none saved yet
function conboard_get_suggested_link_types(): array {
	return apply_filters( 'conboard_suggested_link_types', [
		'website', 'facebook', 'instagram', 'youtube', 'tiktok', 'twitter', 'bluesky', 'linkedin', 'substack',
	] );
}

// Guess a link type from its address (used for links saved by older versions)
function conboard_guess_link_type( string $url ): string {

	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$host = preg_replace( '/^(www|m|mobile)\./', '', $host );

	$map = [
		'facebook.com'	=> 'facebook',
		'fb.com'		=> 'facebook',
		'instagram.com'	=> 'instagram',
		'youtube.com'	=> 'youtube',
		'youtu.be'		=> 'youtube',
		'tiktok.com'	=> 'tiktok',
		'twitter.com'	=> 'twitter',
		'x.com'			=> 'twitter',
		'bsky.app'		=> 'bluesky',
		'linkedin.com'	=> 'linkedin',
		'threads.net'	=> 'threads',
		'twitch.tv'		=> 'twitch',
		'discord.gg'	=> 'discord',
		'discord.com'	=> 'discord',
	];

	if ( isset( $map[ $host ] ) ) {
		return $map[ $host ];
	}

	if ( substr( $host, -13 ) === '.substack.com' ) {
		return 'substack';
	}

	return 'website';
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

// Clean a list of rows: valid type, safe URL; rows without a URL are dropped
function conboard_clean_link_rows( array $rows ): array {

	$types = conboard_get_link_types();
	$out   = [];

	foreach ( $rows as $row ) {
		$url = esc_url_raw( trim( (string) ( $row['url'] ?? '' ) ) );

		if ( $url === '' ) {
			continue;
		}

		$platform = (string) ( $row['platform'] ?? '' );

		if ( ! isset( $types[ $platform ] ) ) {
			$platform = conboard_guess_link_type( $url );
		}

		$out[] = [ 'platform' => $platform, 'url' => $url, 'label' => trim( (string) ( $row['label'] ?? '' ) ) ];
	}

	return $out;
}

// What is stored for a post, or null when nothing has ever been saved for it
function conboard_get_stored_links( int $post_id ): ?array {

	$stored = get_post_meta( $post_id, '_conboard_links', true );

	if ( is_array( $stored ) ) {
		return $stored;
	}

	// Links saved by older versions
	$legacy = array_merge(
		conboard_read_legacy_link_rows( $post_id, 'socials', [ 'platform', 'url', 'label' ] ),
		conboard_read_legacy_link_rows( $post_id, 'links', [ 'url', 'label' ] ),
		conboard_read_legacy_link_rows( $post_id, 'presenter_websites', [ 'url', 'label' ] )
	);

	return empty( $legacy ) ? null : $legacy;
}

// The rows shown in the editor: saved links, or the suggested blank ones for a new post
function conboard_get_links_for_editor( int $post_id ): array {

	$stored = conboard_get_stored_links( $post_id );

	if ( $stored !== null ) {
		return conboard_clean_link_rows( $stored );
	}

	return array_map(
		fn( $type ) => [ 'platform' => $type, 'url' => '', 'label' => '' ],
		conboard_get_suggested_link_types()
	);
}

// The text shown for a link: its label if it has one, otherwise an @handle for sites that
// use them, otherwise the site name or address
function conboard_get_link_text( string $platform, string $url, string $label = '' ): string {

	if ( $label !== '' ) {
		return $label;
	}

	$parts    = wp_parse_url( $url );
	$host     = preg_replace( '/^www\./', '', strtolower( (string) ( $parts['host'] ?? '' ) ) );
	$segments = array_values( array_filter( explode( '/', (string) ( $parts['path'] ?? '' ) ), 'strlen' ) );
	$handle   = '';

	switch ( $platform ) {

		case 'instagram':
		case 'twitter':
		case 'tiktok':
		case 'threads':
		case 'youtube':
			// Profile pages look like /name or /@name. YouTube channels by ID have no handle.
			$first = $segments[0] ?? '';
			if ( $platform === 'youtube' && strpos( $first, '@' ) !== 0 ) {
				break;
			}
			if ( $first !== '' && ! in_array( strtolower( ltrim( $first, '@' ) ), [ 'p', 'reel', 'explore', 'share', 'intent', 'i', 'home', 'watch' ], true ) ) {
				$handle = '@' . ltrim( $first, '@' );
			}
			break;

		case 'bluesky':
			// bsky.app/profile/name.bsky.social
			if ( ( $segments[0] ?? '' ) === 'profile' && ! empty( $segments[1] ) ) {
				$handle = '@' . ltrim( $segments[1], '@' );
			}
			break;

		case 'mastodon':
			// server/@name
			if ( strpos( $segments[0] ?? '', '@' ) === 0 ) {
				$handle = $segments[0] . '@' . $host;
			}
			break;
	}

	if ( $handle !== '' ) {
		return $handle;
	}

	$types = conboard_get_link_types();

	if ( in_array( $platform, [ 'website', 'substack', 'other', '' ], true ) ) {
		return $host !== '' ? $host : $url;
	}

	return $types[ $platform ] ?? ( $host !== '' ? $host : $url );
}

// Links of a post, ready to show: [ [ 'platform', 'url', 'label', 'text' ], ... ]
function conboard_get_links( int $post_id ): array {

	$out = [];

	foreach ( conboard_clean_link_rows( conboard_get_stored_links( $post_id ) ?? [] ) as $row ) {
		$row['text'] = conboard_get_link_text( $row['platform'], $row['url'], $row['label'] );
		$out[]       = $row;
	}

	return $out;
}

// Save a post's links (rows from conboard_sanitize_rows). Blank rows are ignored.
function conboard_save_links( int $post_id, array $rows ): void {

	update_post_meta( $post_id, '_conboard_links', conboard_clean_link_rows( $rows ) );

	conboard_delete_legacy_links( $post_id );
}

// Remove the old repeaters' meta (and the short-lived _conboard_socials) so removed links do not come back
function conboard_delete_legacy_links( int $post_id ): void {

	global $wpdb;

	delete_post_meta( $post_id, '_conboard_socials' );

	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta}
		 WHERE post_id = %d
		   AND meta_key REGEXP '^_?(socials|links|presenter_websites)(_[0-9]+_(platform|url|label))?$'",
		$post_id
	) );

	wp_cache_delete( $post_id, 'post_meta' );
}

// A post's links as a simple list for templates. Returns '' when there are none.
function conboard_render_post_links( int $post_id, string $class = 'conboard-links' ): string {

	$links = conboard_get_links( $post_id );

	if ( empty( $links ) ) {
		return '';
	}

	$html = '<ul class="' . esc_attr( $class ) . '">';

	foreach ( $links as $link ) {
		$html .= '<li><a href="' . esc_url( $link['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $link['text'] ) . '</a></li>';
	}

	return $html . '</ul>';
}
