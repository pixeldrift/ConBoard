<?php
// includes/admin/admin_helpers.php

if ( ! defined('ABSPATH') ) exit;

/**
 * Returns dashboard cards for the ConBoard admin dashboard.
 */
function conboard_get_dashboard_cards(): array {

	$cards = [];

	// Insert our CPTs as cards
	foreach ( CONBOARD_CPTS as $post_type ) {

		$obj = get_post_type_object( $post_type );
		if ( ! $obj ) {
			continue;
		}

		$count = wp_count_posts( $post_type );
		$total = $count ? (int) $count->publish : 0;

		$cards[] = [
			'title'			=> $obj->labels->name,
			'icon_code'		=> $obj-> icon_code,
			'url'			=> admin_url( 'edit.php?post_type=' . $post_type ),
			'subtitle'		=> $obj->description ?: '',
			'count'			=> $total,
			'slug'			=> $post_type,
			'singular_name'	=> $obj->labels->singular_name,
		];
	}


/**
 * Returns card data for specific admin pages.
 *
 * @param string|string[] $slugs One or more page slugs from CONBOARD_ADMIN_PAGES
 * @return array Array of dashboard card data
 */
function conboard_get_cards_for_pages( $slugs ): array {

	$cards = [];

	// Normalize to array
	$slugs = (array) $slugs;

	foreach ( $slugs as $slug ) {

		if ( ! isset( CONBOARD_ADMIN_PAGES[ $slug ] ) ) {
			continue;
		}

		$page = CONBOARD_ADMIN_PAGES[ $slug ];

		$cards[] = [
			'title'		=> $page['title'],
			'icon_code'	=> $page['icon_code'],
			'url'		=> admin_url( $page['url'] ),
			'subtitle'	=> $page['description'] ?? '',
		];
	}

	return $cards;
}


/**
** Allow extensions to add dashboard cards.
**/
	return apply_filters( 'conboard_dashboard_cards', $cards );
}

// Get the post type description from the custom post type definition
function conboard_get_post_type_description( $post_type ) {
	$obj = get_post_type_object( $post_type );

	if ( ! $obj ) {
		return '';
	}
	return $obj->description ?? '';
}


// Rename post title columns
function conboard_rename_title_column( string $post_type, string $label ) {
	add_filter("manage_{$post_type}_posts_columns", function ( $columns ) use ( $label ) {
		if ( isset( $columns['title'] ) ) {
			$columns['title'] = $label;
		}
		return $columns;
	});
}


/**
 * Convert a Dashicons hex code into a renderable glyph.
 */
function conboard_dashicon( string $hex ): string {
	$hex = ltrim( strtolower( $hex ), 'x#' );
	return '&#x' . esc_attr( $hex ) . ';';
}
