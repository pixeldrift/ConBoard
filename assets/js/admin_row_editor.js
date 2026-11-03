/**
 * Behaviour for the admin "list of rows" editor (see includes/admin/admin_row_editor.php).
 * Add, remove, move up/down and drag to reorder. Input names are renumbered after every
 * change so rows are submitted in the order they appear.
 */
( function () {
	'use strict';

	function init( box ) {
		var name = box.getAttribute( 'data-name' );
		var list = box.querySelector( '.conboard-rows-list' );
		var template = box.querySelector( '.conboard-rows-template' );
		var empty = box.querySelector( '.conboard-rows-empty' );
		var dragged = null;

		function rows() {
			return Array.prototype.slice.call( list.children );
		}

		function renumber() {
			rows().forEach( function ( row, index ) {
				row.querySelectorAll( '[name]' ).forEach( function ( input ) {
					var field = input.getAttribute( 'name' ).replace( /^.*?\[[^\]]*\]/, '' );
					input.setAttribute( 'name', name + '[' + index + ']' + field );
				} );
			} );

			if ( empty ) {
				empty.hidden = rows().length > 0;
			}
		}

		function add() {
			var holder = document.createElement( 'ul' );
			holder.innerHTML = template.innerHTML;
			var row = holder.firstElementChild;

			if ( row ) {
				list.appendChild( row );
				renumber();
				var first = row.querySelector( 'select, input' );
				if ( first ) { first.focus(); }
			}
		}

		box.querySelector( '.conboard-rows-add' ).addEventListener( 'click', add );

		list.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button' );
			var row = event.target.closest( '.conboard-row' );

			if ( ! button || ! row ) {
				return;
			}

			if ( button.classList.contains( 'conboard-row-remove' ) ) {
				list.removeChild( row );
			} else if ( button.classList.contains( 'conboard-row-up' ) && row.previousElementSibling ) {
				list.insertBefore( row, row.previousElementSibling );
			} else if ( button.classList.contains( 'conboard-row-down' ) && row.nextElementSibling ) {
				list.insertBefore( row.nextElementSibling, row );
			}

			renumber();
		} );

		list.addEventListener( 'dragstart', function ( event ) {
			if ( ! event.target.classList || ! event.target.classList.contains( 'conboard-row-handle' ) ) {
				return;
			}

			dragged = event.target.closest( '.conboard-row' );

			if ( event.dataTransfer ) {
				event.dataTransfer.effectAllowed = 'move';
				event.dataTransfer.setData( 'text/plain', '' );
			}

			if ( dragged ) { dragged.classList.add( 'is-dragging' ); }
		} );

		list.addEventListener( 'dragover', function ( event ) {
			if ( ! dragged ) {
				return;
			}

			event.preventDefault();

			var over = event.target.closest( '.conboard-row' );

			if ( ! over || over === dragged ) {
				return;
			}

			var box = over.getBoundingClientRect();
			var after = event.clientY > box.top + box.height / 2;

			list.insertBefore( dragged, after ? over.nextElementSibling : over );
		} );

		list.addEventListener( 'dragend', function () {
			if ( dragged ) { dragged.classList.remove( 'is-dragging' ); }
			dragged = null;
			renumber();
		} );
	}

	function ready() {
		document.querySelectorAll( '.conboard-rows' ).forEach( init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
} )();
