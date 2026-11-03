// assets/js/sponsor_levels.js

document.addEventListener('click', e => {

	const row = e.target.closest('tr');
	if (!row) return;

	// Edit
	if (e.target.classList.contains('conboard-edit')) {
		row.dataset.editing = 'true';
	}

	// Cancel
	if (e.target.classList.contains('conboard-cancel')) {
		row.dataset.editing = 'false';
	}

	// Delete
	if (e.target.classList.contains('conboard-delete')) {
		row.remove();
	}
});

document.getElementById('conboard-add-level').addEventListener('click', () => {

	const slug = 'level_' + Date.now();
	const tbody = document.querySelector('#conboard-sponsor-levels tbody');

	const tr = document.createElement('tr');
	tr.dataset.key = slug;
	tr.dataset.editing = 'true';

	tr.innerHTML = `
		<td class="edit"><input name="conboard_sponsor_levels[${slug}][label]"></td>
		<td class="edit"><input name="conboard_sponsor_levels[${slug}][singular]"></td>
		<td class="edit"><input name="conboard_sponsor_levels[${slug}][plural]"></td>
		<td class="edit"><code>${slug}</code>
			<input type="hidden" name="conboard_sponsor_levels[${slug}][key]" value="${slug}">
		</td>
		<td class="edit">
			<button type="button" class="button button-secondary conboard-cancel">Cancel</button>
		</td>
	`;

	tbody.appendChild(tr);
});


function conboard_unlink_sponsor_level( string $slug ): void {

	$query = new WP_Query([
		'post_type'			=> 'sponsor',
		'posts_per_page' => -1,
		'meta_query'		 => [
			[
				'key'	 => 'sponsor_level',
				'value' => $slug,
			],
		],
		'fields' => 'ids',
	]);

	foreach ( $query->posts as $post_id ) {
		delete_post_meta( $post_id, 'sponsor_level' );
		delete_post_meta( $post_id, 'sponsor_level_order' );
	}
}

container.addEventListener('click', e => {

	const row = e.target.closest('.conboard-level-row');
	if (!row) return;

	if (e.target.classList.contains('conboard-edit-level')) {
		row.dataset.editing = 'true';
	}

	if (e.target.classList.contains('conboard-cancel-edit')) {
		row.dataset.editing = 'false';
	}

	if (e.target.classList.contains('conboard-remove-level')) {
		if (confirm('Remove this sponsor level? Existing sponsors will be unassigned.')) {
			row.remove();
		}
	}
});


