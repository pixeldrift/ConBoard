// assets/js/sponsor_levels.js
//
// The sponsor level table on the ConBoard Settings page: edit, cancel, delete and add levels.
// Nothing is saved until "Save Settings" is pressed.

( function () {
	'use strict';

	var table = document.getElementById( 'conboard-sponsor-levels' );
	var addButton = document.getElementById( 'conboard-add-level' );

	if ( ! table ) {
		return;
	}

	table.addEventListener( 'click', function ( e ) {

		var row = e.target.closest( 'tr' );

		if ( ! row || ! row.parentNode || row.parentNode.tagName !== 'TBODY' ) {
			return;
		}

		if ( e.target.classList.contains( 'conboard-edit' ) ) {
			row.dataset.editing = 'true';
		}

		if ( e.target.classList.contains( 'conboard-cancel' ) ) {
			// A level that has not been saved yet simply goes away
			if ( row.dataset.isNew === 'true' ) {
				row.remove();
			} else {
				row.dataset.editing = 'false';
			}
		}

		if ( e.target.classList.contains( 'conboard-delete' ) ) {
			if ( window.confirm( 'Remove this sponsor level? Sponsors using it will be unassigned when you save.' ) ) {
				row.remove();
			}
		}
	} );

	if ( addButton ) {
		addButton.addEventListener( 'click', function () {

			var id = 'new_' + Date.now();
			var tbody = table.querySelector( 'tbody' );
			var tr = document.createElement( 'tr' );

			tr.dataset.key = id;
			tr.dataset.editing = 'true';
			tr.dataset.isNew = 'true';

			tr.innerHTML =
				'<td class="edit"><input name="conboard_sponsor_levels[' + id + '][label]" placeholder="Label"></td>' +
				'<td class="edit"><input name="conboard_sponsor_levels[' + id + '][singular]" placeholder="Gold Sponsor"></td>' +
				'<td class="edit"><input name="conboard_sponsor_levels[' + id + '][plural]" placeholder="Gold Sponsors"></td>' +
				'<td class="edit"><input name="conboard_sponsor_levels[' + id + '][slug]" placeholder="slug (optional)"></td>' +
				'<td class="edit">new</td>' +
				'<td class="edit"><button type="button" class="button button-secondary conboard-cancel">Cancel</button></td>';

			tbody.appendChild( tr );

			var first = tr.querySelector( 'input' );
			if ( first ) {
				first.focus();
			}
		} );
	}
} )();
