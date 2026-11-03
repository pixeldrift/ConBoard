// Keeps [conboard_schedule] cards live: state (upcoming, starting soon, in progress, finished),
// countdown text and progress bar update without reloading the page.
( function () {
	var SOON = 900;

	function describe( seconds ) {
		var minutes = Math.ceil( seconds / 60 );
		if ( seconds < 60 ) return 'less than a minute';
		if ( minutes < 60 ) return minutes + ' min';
		var hours = Math.floor( minutes / 60 ), rest = minutes % 60;
		return hours + ' h' + ( rest ? ' ' + rest + ' min' : '' );
	}

	function init() {
		var roots = document.querySelectorAll( '.conboard-schedule[data-now]' );
		if ( ! roots.length ) return;

		var loadedAt = Date.now();

		function tick() {
			Array.prototype.forEach.call( roots, function ( root ) {
				var now = parseInt( root.getAttribute( 'data-now' ), 10 ) + ( Date.now() - loadedAt ) / 1000;

				Array.prototype.forEach.call( root.querySelectorAll( '.cb-session[data-start]' ), function ( el ) {
					var current = el.getAttribute( 'data-state' );
					if ( current === 'cancelled' ) return;

					var start = parseInt( el.getAttribute( 'data-start' ), 10 );
					var end = parseInt( el.getAttribute( 'data-end' ), 10 );
					var next = now >= end ? 'past' : ( now >= start ? 'current' : ( start - now <= SOON ? 'soon' : 'upcoming' ) );

					if ( next !== current ) {
						el.classList.remove( 'cb-session-' + current );
						el.classList.add( 'cb-session-' + next );
						el.setAttribute( 'data-state', next );
					}

					var label = el.querySelector( '.cb-countdown' );
					var bar = el.querySelector( '.cb-progress span' );
					var text, percent = 0;

					if ( next === 'current' ) {
						text = 'Ends in ' + describe( end - now );
						percent = end > start ? Math.min( 100, Math.max( 0, ( now - start ) / ( end - start ) * 100 ) ) : 100;
					} else if ( next === 'past' ) {
						text = 'Finished'; percent = 100;
					} else {
						text = 'Starts in ' + describe( start - now );
					}

					if ( label && label.textContent !== text ) label.textContent = text;
					if ( bar ) bar.style.width = percent.toFixed( 1 ) + '%';
				} );
			} );
		}

		tick();
		window.setInterval( tick, 1000 );
	}

	if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
	else init();
} )();
