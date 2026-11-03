/**
 * ConBoard carousel: a scroll-snap strip with prev/next buttons and optional autoplay.
 * Autoplay pauses on hover/focus, while the tab is hidden, and is off for visitors
 * who prefer reduced motion.
 */
( function () {
	'use strict';

	function init( root ) {
		var track = root.querySelector( '.conboard-carousel-track' );
		if ( ! track ) {
			return;
		}

		var prev = root.querySelector( '.conboard-carousel-prev' );
		var next = root.querySelector( '.conboard-carousel-next' );
		var speed = parseInt( root.getAttribute( 'data-speed' ), 10 ) || 5000;
		var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		var autoplay = root.getAttribute( 'data-autoplay' ) === '1' && ! reduced;
		var timer = null;

		function stepSize() {
			var item = track.querySelector( '.conboard-carousel-item' );
			return item ? item.getBoundingClientRect().width : track.clientWidth;
		}

		function go( direction ) {
			var max = track.scrollWidth - track.clientWidth;
			var behavior = reduced ? 'auto' : 'smooth';

			if ( direction > 0 && track.scrollLeft >= max - 2 ) {
				track.scrollTo( { left: 0, behavior: behavior } );
			} else if ( direction < 0 && track.scrollLeft <= 2 ) {
				track.scrollTo( { left: max, behavior: behavior } );
			} else {
				track.scrollBy( { left: direction * stepSize(), behavior: behavior } );
			}
		}

		function start() {
			if ( ! autoplay || timer ) {
				return;
			}
			timer = window.setInterval( function () {
				if ( ! document.hidden ) {
					go( 1 );
				}
			}, speed );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		// Hide the buttons when everything already fits
		function toggleButtons() {
			var fits = track.scrollWidth <= track.clientWidth + 2;
			if ( prev ) { prev.hidden = fits; }
			if ( next ) { next.hidden = fits; }
			if ( fits ) { stop(); } else { start(); }
		}

		if ( prev ) { prev.addEventListener( 'click', function () { go( -1 ); } ); }
		if ( next ) { next.addEventListener( 'click', function () { go( 1 ); } ); }

		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );
		root.addEventListener( 'focusin', stop );
		root.addEventListener( 'focusout', start );
		window.addEventListener( 'resize', toggleButtons );

		toggleButtons();
	}

	function ready() {
		var carousels = document.querySelectorAll( '.conboard-carousel' );
		for ( var i = 0; i < carousels.length; i++ ) {
			init( carousels[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
} )();
