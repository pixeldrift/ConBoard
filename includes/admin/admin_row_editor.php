<?php
// includes/admin/admin_row_editor.php
//
// A small, reusable "list of rows" editor for admin metaboxes (add, remove, duplicate,
// reorder). It does the job of ACF's paid repeater field with no extra dependencies, and
// is meant to be used anywhere ConBoard needs a repeating group (session presenters,
// social links, web links, Display Board slides).
//
// Usage:
//   $fields = [
//       'presenter' => [ 'label' => 'Presenter', 'type' => 'select', 'choices' => [ 1 => 'Ann' ], 'required' => true ],
//       'role'      => [ 'label' => 'Role',      'type' => 'select', 'choices' => [ ... ] ],
//       'note'      => [ 'label' => 'Note',      'type' => 'text' ],      // also: 'url'
//   ];
//   conboard_render_row_editor( 'my_rows', $rows, $fields, [ 'add_label' => 'Add row' ] );
//   $clean = conboard_sanitize_rows( wp_unslash( $_POST['my_rows'] ?? [] ), $fields );

if ( ! defined( 'ABSPATH' ) ) exit;


// ---------------------------------------------------------------------------
// Saving
// ---------------------------------------------------------------------------

// Clean submitted rows. Unknown fields are dropped, selects must be one of their choices,
// and rows missing a required field are skipped. Returns a list with sequential numeric keys.
function conboard_sanitize_rows( $raw, array $fields ): array {

	if ( ! is_array( $raw ) ) {
		return [];
	}

	$out = [];

	foreach ( $raw as $row ) {

		if ( ! is_array( $row ) ) {
			continue;
		}

		$clean = [];

		foreach ( $fields as $key => $field ) {

			$value = $row[ $key ] ?? '';
			$type  = $field['type'] ?? 'text';

			if ( is_array( $value ) && $type === 'multiselect' ) {
				$allowed = array_map( 'strval', array_keys( $field['choices'] ?? [] ) );
				$value   = implode( ',', array_values( array_intersect( array_map( 'strval', $value ), $allowed ) ) );
			} elseif ( is_array( $value ) ) {
				$value = '';
			}

			$value = (string) $value;

			if ( $type === 'select' ) {
				$choices = array_map( 'strval', array_keys( $field['choices'] ?? [] ) );
				$value   = in_array( $value, $choices, true ) ? $value : ( ! empty( $field['strict'] ) ? '' : (string) ( $field['default'] ?? '' ) );
			} elseif ( $type === 'url' ) {
				$value = esc_url_raw( trim( $value ) );
			} elseif ( $type === 'multiselect' ) {
				// already cleaned above
			} elseif ( $type === 'textarea' ) {
				$value = sanitize_textarea_field( $value );
			} elseif ( $type === 'checkbox' ) {
				$value = $value === '1' ? '1' : '0';
			} elseif ( $type === 'number' ) {
				$value = $value === '' ? '' : (string) max( (int) ( $field['min'] ?? 0 ), min( (int) ( $field['max'] ?? 100000 ), (int) $value ) );
			} elseif ( $type === 'image' ) {
				$value = (string) absint( $value );
				$value = ( $value !== '0' && get_post_type( (int) $value ) === 'attachment' ) ? $value : '';
			} else {
				$value = sanitize_text_field( $value );
			}

			$clean[ $key ] = $value;
		}

		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['required'] ) && $clean[ $key ] === '' ) {
				continue 2;
			}
		}

		$out[] = $clean;
	}

	return $out;
}


// ---------------------------------------------------------------------------
// Display
// ---------------------------------------------------------------------------

function conboard_get_row_editor_field_html( string $name, $index, string $key, array $field, string $value ): string {

	$type  = $field['type'] ?? 'text';
	$input = esc_attr( $name . '[' . $index . '][' . $key . ']' );

	if ( $type === 'select' ) {
		$html = '<select name="' . $input . '">';

		foreach ( ( $field['choices'] ?? [] ) as $choice => $label ) {
			$html .= '<option value="' . esc_attr( (string) $choice ) . '"' . selected( (string) $choice, $value, false ) . '>' . esc_html( (string) $label ) . '</option>';
		}

		return $html . '</select>';
	}

	if ( $type === 'multiselect' ) {
		$chosen = $value === '' ? [] : explode( ',', $value );
		$html   = '<select multiple size="' . (int) ( $field['size'] ?? 4 ) . '" name="' . $input . '[]">';

		foreach ( ( $field['choices'] ?? [] ) as $choice => $label ) {
			$html .= '<option value="' . esc_attr( (string) $choice ) . '"' . ( in_array( (string) $choice, $chosen, true ) ? ' selected' : '' ) . '>' . esc_html( (string) $label ) . '</option>';
		}

		return $html . '</select><span class="description">Leave empty for all. Hold Ctrl/Cmd to pick several.</span>';
	}

	if ( $type === 'textarea' ) {
		return '<textarea name="' . $input . '" rows="' . (int) ( $field['rows'] ?? 3 ) . '" class="widefat">' . esc_textarea( $value ) . '</textarea>';
	}

	if ( $type === 'checkbox' ) {
		return '<input type="hidden" name="' . $input . '" value="0"><input type="checkbox" name="' . $input . '" value="1"' . ( $value === '1' ? ' checked' : '' ) . '>';
	}

	if ( $type === 'number' ) {
		$attrs = ' min="' . (int) ( $field['min'] ?? 0 ) . '"' . ( isset( $field['max'] ) ? ' max="' . (int) $field['max'] . '"' : '' );
		return '<input type="number" name="' . $input . '" value="' . esc_attr( $value ) . '"' . $attrs . ' class="small-text">';
	}

	if ( $type === 'image' ) {
		$id      = (int) $value;
		$preview = $id ? wp_get_attachment_image( $id, 'thumbnail' ) : '';

		return '<span class="conboard-row-image">'
			. '<input type="hidden" name="' . $input . '" value="' . esc_attr( $value ) . '">'
			. '<span class="conboard-row-image-preview">' . $preview . '</span>'
			. '<button type="button" class="button conboard-row-image-choose">Choose image</button> '
			. '<button type="button" class="button-link conboard-row-image-clear">Remove</button>'
			. '</span>';
	}

	return '<input type="' . ( $type === 'url' ? 'url' : 'text' ) . '" name="' . $input . '" value="' . esc_attr( $value ) . '" class="widefat">';
}

function conboard_get_row_editor_row_html( string $name, $index, array $fields, array $values ): string {

	$html  = '<li class="conboard-row">';
	$html .= '<span class="conboard-row-handle" draggable="true" title="Drag to reorder" aria-hidden="true">&#8942;&#8942;</span>';

	foreach ( $fields as $key => $field ) {
		$value = (string) ( $values[ $key ] ?? ( $field['default'] ?? '' ) );

		$show = '';
		if ( ! empty( $field['show_when'] ) ) {
			// [ 'field' => 'type', 'in' => [ 'welcome', 'custom' ] ]: only shown when that field has one of these values
			$show = ' data-show-field="' . esc_attr( $field['show_when']['field'] ) . '" data-show-in="' . esc_attr( implode( ',', (array) $field['show_when']['in'] ) ) . '"';
		}

		$html .= '<label class="conboard-row-field' . ( ( $field['type'] ?? '' ) === 'checkbox' ? ' is-checkbox' : '' ) . '" data-field="' . esc_attr( $key ) . '"' . $show . '><span>' . esc_html( $field['label'] ?? $key ) . '</span>';
		$html .= conboard_get_row_editor_field_html( $name, $index, $key, $field, $value );
		$html .= '</label>';
	}

	$html .= '<span class="conboard-row-actions">';
	$html .= '<button type="button" class="button conboard-row-up" title="Move up" aria-label="Move up">&uarr;</button>';
	$html .= '<button type="button" class="button conboard-row-down" title="Move down" aria-label="Move down">&darr;</button>';
	$html .= '<button type="button" class="button conboard-row-duplicate" title="Duplicate" aria-label="Duplicate">&#10064;</button>';
	$html .= '<button type="button" class="button conboard-row-remove" title="Remove" aria-label="Remove">&times;</button>';
	$html .= '</span>';
	$html .= '</li>';

	return $html;
}

function conboard_render_row_editor( string $name, array $rows, array $fields, array $args = [] ): void {

	static $printed_assets = false;

	$add_label = $args['add_label'] ?? 'Add row';
	$empty     = $args['empty'] ?? 'Nothing added yet.';

	if ( ! $printed_assets ) {
		$printed_assets = true;

		wp_enqueue_script(
			'conboard-row-editor',
			CONBOARD_PLUGIN_URL . 'assets/js/admin_row_editor.js',
			[],
			'0.1.0',
			true
		);

		?>
		<style>
			.conboard-rows.is-stacked .conboard-row { flex-wrap: wrap; align-items: flex-start; }
			.conboard-rows.is-stacked .conboard-row-field { flex: 1 1 30%; min-width: 180px; }
			.conboard-rows.is-stacked .conboard-row-field.is-checkbox { flex: 0 0 auto; min-width: 0; }
			.conboard-rows.is-stacked .conboard-row-field[data-field="heading"],
			.conboard-rows.is-stacked .conboard-row-field[data-field="message"],
			.conboard-rows.is-stacked .conboard-row-field[data-field="text"] { flex-basis: 100%; }
			.conboard-row-image-preview img { display: block; max-width: 80px; height: auto; margin-bottom: 4px; }
			.conboard-rows-list { margin: 0 0 10px; padding: 0; list-style: none; }
			.conboard-row { display: flex; gap: 10px; align-items: flex-end; margin: 0 0 6px; padding: 8px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 3px; }
			.conboard-row.is-dragging { opacity: .4; }
			.conboard-row-handle { cursor: grab; color: #787c82; align-self: center; letter-spacing: -2px; user-select: none; }
			.conboard-row-field { flex: 1 1 0; min-width: 0; }
			.conboard-row-field > span { display: block; font-weight: 600; margin-bottom: 2px; }
			.conboard-row-field select, .conboard-row-field input { width: 100%; max-width: none; }
			.conboard-row-actions { display: flex; gap: 4px; }
			.conboard-rows-empty { color: #646970; font-style: italic; }
		</style>
		<?php
	}

	if ( ! empty( $args['stacked'] ) ) {
		wp_enqueue_media();
	}

	echo '<div class="conboard-rows' . ( ! empty( $args['stacked'] ) ? ' is-stacked' : '' ) . '" data-name="' . esc_attr( $name ) . '">';

	echo '<ul class="conboard-rows-list">';
	foreach ( array_values( $rows ) as $i => $row ) {
		echo conboard_get_row_editor_row_html( $name, $i, $fields, (array) $row ); // Escaped inside
	}
	echo '</ul>';

	echo '<p class="conboard-rows-empty"' . ( empty( $rows ) ? '' : ' hidden' ) . '>' . esc_html( $empty ) . '</p>';

	echo '<template class="conboard-rows-template">' . conboard_get_row_editor_row_html( $name, '__INDEX__', $fields, [] ) . '</template>'; // Escaped inside

	echo '<button type="button" class="button conboard-rows-add">' . esc_html( $add_label ) . '</button>';

	echo '</div>';
}
