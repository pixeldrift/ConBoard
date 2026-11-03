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

		// Show or hide fields that depend on another field of the same row (data-show-field / data-show-in)
		function updateVisibility( row ) {
			row.querySelectorAll( '[data-show-field]' ).forEach( function ( wrap ) {
				var source = row.querySelector( '[data-field="' + wrap.getAttribute( 'data-show-field' ) + '"] select, [data-field="' + wrap.getAttribute( 'data-show-field' ) + '"] input' );
				var allowed = wrap.getAttribute( 'data-show-in' ).split( ',' );

				wrap.hidden = ! ( source && allowed.indexOf( source.value ) !== -1 );

				// A hidden field is not submitted. Rows list the fields of every slide type, so a long
				// board would otherwise exceed PHP's max_input_vars and lose its last slides on save.
				wrap.querySelectorAll( 'input, select, textarea' ).forEach( function ( input ) {
					input.disabled = wrap.hidden;
				} );
			} );
		}

		function updateAllVisibility() {
			rows().forEach( updateVisibility );
		}

		function add() {
			var holder = document.createElement( 'ul' );
			holder.innerHTML = template.innerHTML;
			var row = holder.firstElementChild;

			if ( row ) {
				list.appendChild( row );
				renumber();
				updateVisibility( row );
				var first = row.querySelector( 'select, input' );
				if ( first ) { first.focus(); }
			}
		}

		updateAllVisibility();

		box.querySelector( '.conboard-rows-add' ).addEventListener( 'click', add );

		list.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button' );
			var row = event.target.closest( '.conboard-row' );

			if ( ! button || ! row ) {
				return;
			}

			if ( button.classList.contains( 'conboard-row-remove' ) ) {
				list.removeChild( row );
			} else if ( button.classList.contains( 'conboard-row-duplicate' ) ) {
				var copy = row.cloneNode( true );
				var from = row.querySelectorAll( 'select, textarea, input' );
				var to = copy.querySelectorAll( 'select, textarea, input' );

				for ( var i = 0; i < from.length; i++ ) {
					if ( from[ i ].type === 'checkbox' ) {
						to[ i ].checked = from[ i ].checked;
					} else if ( from[ i ].multiple ) {
						for ( var o = 0; o < from[ i ].options.length; o++ ) {
							to[ i ].options[ o ].selected = from[ i ].options[ o ].selected;
						}
					} else {
						to[ i ].value = from[ i ].value;
					}
				}

				list.insertBefore( copy, row.nextElementSibling );
			} else if ( button.classList.contains( 'conboard-row-up' ) && row.previousElementSibling ) {
				list.insertBefore( row, row.previousElementSibling );
			} else if ( button.classList.contains( 'conboard-row-down' ) && row.nextElementSibling ) {
				list.insertBefore( row.nextElementSibling, row );
			}

			renumber();
			updateAllVisibility();
		} );

		list.addEventListener( 'change', function ( event ) {
			var row = event.target.closest( '.conboard-row' );

			if ( row ) {
				updateVisibility( row );
			}
		} );

		// Image fields use the WordPress media library
		list.addEventListener( 'click', function ( event ) {
			var choose = event.target.closest( '.conboard-row-image-choose' );
			var clear = event.target.closest( '.conboard-row-image-clear' );
			var holder = ( choose || clear ) && ( choose || clear ).closest( '.conboard-row-image' );

			if ( ! holder ) {
				return;
			}

			var input = holder.querySelector( 'input' );
			var preview = holder.querySelector( '.conboard-row-image-preview' );

			if ( clear ) {
				input.value = '';
				preview.innerHTML = '';
				return;
			}

			if ( ! window.wp || ! window.wp.media ) {
				return;
			}

			var frame = window.wp.media( { title: 'Choose image', multiple: false, library: { type: 'image' } } );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var sizes = attachment.sizes || {};
				var src = ( sizes.thumbnail || sizes.full || attachment ).url;

				input.value = attachment.id;
				preview.innerHTML = '';

				var img = document.createElement( 'img' );
				img.src = src;
				img.alt = '';
				preview.appendChild( img );
			} );

			frame.open();
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
