/**
 * Builds Screen Mode links in the admin.
 * Each .conboard-screen-link box has option selects (orientation, rotate, flip) that apply to
 * every .conboard-screen-row inside it. Only options that differ from the default are added.
 */
( function () {
	'use strict';

	var defaults = { orientation: 'landscape', rotate: '0', flip: 'none' };

	function init( box ) {
		var selects = box.querySelectorAll( 'select[data-param]' );
		var rows = box.querySelectorAll( '.conboard-screen-row' );

		function query() {
			var parts = [ 'display=screen' ];

			selects.forEach( function ( select ) {
				var key = select.getAttribute( 'data-param' );

				if ( select.value !== defaults[ key ] ) {
					parts.push( key + '=' + encodeURIComponent( select.value ) );
				}
			} );

			return parts.join( '&' );
		}

		function build() {
			var params = query();

			rows.forEach( function ( row ) {
				var base = row.getAttribute( 'data-base' ) || '';
				var url = base + ( base.indexOf( '?' ) === -1 ? '?' : '&' ) + params;
				var input = row.querySelector( '.conboard-screen-url' );
				var open = row.querySelector( '.conboard-screen-open' );
				var exportLink = row.querySelector( '.conboard-screen-export' );

				if ( input ) { input.value = url; }
				if ( open ) { open.href = url; }
				if ( exportLink ) { exportLink.href = url + '&export=jpg'; }
			} );
		}

		function copyRow( row ) {
			var input = row.querySelector( '.conboard-screen-url' );
			var button = row.querySelector( '.conboard-screen-copy' );

			if ( ! input || ! button ) {
				return;
			}

			input.select();

			function done() {
				button.textContent = 'Copied!';
				window.setTimeout( function () { button.textContent = 'Copy link'; }, 1500 );
			}

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( input.value ).then( done );
			} else {
				document.execCommand( 'copy' );
				done();
			}
		}

		selects.forEach( function ( select ) {
			select.addEventListener( 'change', build );
		} );

		rows.forEach( function ( row ) {
			var button = row.querySelector( '.conboard-screen-copy' );

			if ( button ) {
				button.addEventListener( 'click', function () { copyRow( row ); } );
			}
		} );

		build();
	}

	function ready() {
		document.querySelectorAll( '.conboard-screen-link' ).forEach( init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
} )();
