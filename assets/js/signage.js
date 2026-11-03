/**
 * ConBoard Screen Mode.
 *
 * - Scales a fixed 1920x1080 (or 1080x1920) stage to fit any display, applying the
 *   orientation / rotate / flip settings.
 * - Keeps a clock that follows the website's time zone, not the display computer's.
 * - Refreshes the content from the server so schedule changes appear on their own.
 * - Loops through pages when a screen has more than one.
 * - Draws the QR code locally (no outside service).
 * - Offers a small settings menu. Changes only live in the page URL, never on the server.
 */
( function () {
	'use strict';

	var cfg = window.ConBoardScreen || {};

	var state = {
		orientation: cfg.orientation === 'portrait' ? 'portrait' : 'landscape',
		rotate: parseInt( cfg.rotate, 10 ) || 0,
		flip: cfg.flip || 'none',
		interval: Math.max( 3, parseInt( cfg.interval, 10 ) || 10 ),
		refresh: Math.max( 15, parseInt( cfg.refresh, 10 ) || 60 ),
		clock: cfg.clock !== false,
		qr: cfg.qr !== false
	};

	var DAYS = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
	var MONTHS = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];

	var stage = document.getElementById( 'cb-stage' );
	var content = document.getElementById( 'cb-content' );
	var pageTimer = null;
	var pageIndex = 0;
	var skew = 0;

	if ( ! stage || ! content ) {
		return;
	}

	function byId( id ) {
		return document.getElementById( id );
	}

	// ---------------------------------------------------------------------
	// Layout: orientation, rotate, flip, scale to fit
	// ---------------------------------------------------------------------

	function layout() {
		var portrait = state.orientation === 'portrait';
		var w = portrait ? 1080 : 1920;
		var h = portrait ? 1920 : 1080;
		var sideways = state.rotate === 90 || state.rotate === 270;
		var boxW = sideways ? h : w;
		var boxH = sideways ? w : h;
		var scale = Math.min( window.innerWidth / boxW, window.innerHeight / boxH );
		var fx = state.flip === 'horizontal' ? -1 : 1;
		var fy = state.flip === 'vertical' ? -1 : 1;

		stage.style.width = w + 'px';
		stage.style.height = h + 'px';
		stage.classList.toggle( 'is-portrait', portrait );
		stage.classList.toggle( 'is-landscape', ! portrait );
		stage.classList.toggle( 'no-clock', ! state.clock );
		stage.classList.toggle( 'no-qr', ! state.qr );
		stage.style.transform = 'translate(-50%, -50%) rotate(' + state.rotate + 'deg) scale(' + ( fx * scale ) + ', ' + ( fy * scale ) + ')';
	}

	// Remember a setting in the address bar only (so a reload keeps it) without touching the server
	function setParam( key, value ) {
		try {
			var url = new URL( window.location.href );
			if ( value === null ) {
				url.searchParams.delete( key );
			} else {
				url.searchParams.set( key, value );
			}
			window.history.replaceState( null, '', url.toString() );
		} catch ( e ) { /* very old browsers: the setting still applies until reload */ }
	}

	// ---------------------------------------------------------------------
	// Clock (website time zone)
	// ---------------------------------------------------------------------

	function readServerTime() {
		var el = byId( 'cb-clock' );
		var serverTime = el ? parseInt( el.getAttribute( 'data-server-time' ), 10 ) : 0;

		if ( serverTime ) {
			skew = serverTime - Date.now();
		}
	}

	function pad( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	function tick() {
		var el = byId( 'cb-clock' );

		if ( ! el || ! state.clock ) {
			return;
		}

		var offset = parseInt( el.getAttribute( 'data-offset' ), 10 ) || 0;
		var d = new Date( Date.now() + skew + offset * 60000 );
		var hours = d.getUTCHours();
		var suffix = hours >= 12 ? 'PM' : 'AM';
		var hour12 = hours % 12 || 12;

		var time = el.querySelector( '.cb-clock-time' );
		var date = el.querySelector( '.cb-clock-date' );

		if ( time ) {
			time.textContent = hour12 + ':' + pad( d.getUTCMinutes() ) + ' ' + suffix;
		}
		if ( date ) {
			date.textContent = DAYS[ d.getUTCDay() ] + ', ' + MONTHS[ d.getUTCMonth() ] + ' ' + d.getUTCDate();
		}
	}

	// ---------------------------------------------------------------------
	// Live schedule: session state, progress and countdowns
	//
	// Every session card carries its start and end as timestamps, so its state
	// (upcoming, starting soon, in progress, finished) is worked out here, once a second,
	// using the server's time. It does not wait for the next refresh. When a state changes,
	// the content is refreshed straight away (at most every 10 seconds) so lists that depend
	// on it, such as "Next", pick up the change.
	// ---------------------------------------------------------------------

	var SOON_SECONDS = 900;
	var lastRefreshAt = 0;

	function describeDuration( seconds ) {
		var minutes = Math.ceil( seconds / 60 );

		if ( seconds < 60 ) {
			return 'less than a minute';
		}

		if ( minutes < 60 ) {
			return minutes + ' min';
		}

		var hours = Math.floor( minutes / 60 );
		var rest = minutes % 60;

		return hours + ' h' + ( rest ? ' ' + rest + ' min' : '' );
	}

	function tickSchedule() {
		var now = ( Date.now() + skew ) / 1000;
		var changed = false;

		content.querySelectorAll( '.cb-session[data-start]' ).forEach( function ( el ) {
			var current = el.getAttribute( 'data-state' );

			if ( current === 'cancelled' ) {
				return;
			}

			var start = parseInt( el.getAttribute( 'data-start' ), 10 );
			var end = parseInt( el.getAttribute( 'data-end' ), 10 );
			var next = now >= end ? 'past' : ( now >= start ? 'current' : ( start - now <= SOON_SECONDS ? 'soon' : 'upcoming' ) );

			if ( next !== current ) {
				el.classList.remove( 'cb-session-' + current );
				el.classList.add( 'cb-session-' + next );
				el.setAttribute( 'data-state', next );
				changed = true;
			}

			var label = el.querySelector( '.cb-countdown' );
			var bar = el.querySelector( '.cb-progress span' );
			var text = '';
			var percent = 0;

			if ( next === 'current' ) {
				text = 'Ends in ' + describeDuration( end - now );
				percent = end > start ? Math.min( 100, Math.max( 0, ( now - start ) / ( end - start ) * 100 ) ) : 100;
			} else if ( next === 'past' ) {
				text = 'Finished';
				percent = 100;
			} else {
				text = 'Starts in ' + describeDuration( start - now );
			}

			if ( label && label.textContent !== text ) {
				label.textContent = text;
			}

			if ( bar ) {
				bar.style.width = percent.toFixed( 1 ) + '%';
			}
		} );

		if ( changed && Date.now() - lastRefreshAt > 10000 ) {
			refresh();
		}
	}

	// ---------------------------------------------------------------------
	// Pages: loop through them when there is more than one
	// ---------------------------------------------------------------------

	function showPage( index ) {
		var pages = content.querySelectorAll( '.cb-page' );

		for ( var i = 0; i < pages.length; i++ ) {
			pages[ i ].classList.toggle( 'is-active', i === index );
		}

		pageIndex = index;
		pageQr();
	}

	// Each page can set its own length with data-duration (seconds); otherwise the
	// screen's default interval is used.
	function pageSeconds( page ) {
		var own = page ? parseInt( page.getAttribute( 'data-duration' ), 10 ) : 0;

		return Math.max( 3, own > 0 ? own : state.interval );
	}

	function startPages() {
		var pages = content.querySelectorAll( '.cb-page' );

		if ( pageTimer ) {
			window.clearTimeout( pageTimer );
			pageTimer = null;
		}

		if ( pages.length < 2 ) {
			pageQr();
			return;
		}

		showPage( Math.min( pageIndex, pages.length - 1 ) );

		function next() {
			var all = content.querySelectorAll( '.cb-page' );

			if ( all.length < 2 ) {
				return;
			}

			showPage( ( pageIndex + 1 ) % all.length );
			pageTimer = window.setTimeout( next, pageSeconds( all[ pageIndex ] ) * 1000 );
		}

		pageTimer = window.setTimeout( next, pageSeconds( pages[ pageIndex ] ) * 1000 );
	}

	// ---------------------------------------------------------------------
	// Live refresh
	// ---------------------------------------------------------------------

	function refresh() {
		if ( ! window.fetch || ! window.DOMParser ) {
			return;
		}

		lastRefreshAt = Date.now();

		window.fetch( window.location.href, { cache: 'no-store', credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.ok ? response.text() : Promise.reject( new Error( 'bad response' ) );
			} )
			.then( function ( html ) {
				var doc = new DOMParser().parseFromString( html, 'text/html' );
				var fresh = doc.getElementById( 'cb-content' );

				// Not a screen page (for example a login redirect): keep what is showing
				if ( ! fresh ) {
					return;
				}

				content.innerHTML = fresh.innerHTML;

				var title = doc.getElementById( 'cb-title' );
				var current = byId( 'cb-title' );
				if ( title && current ) {
					current.textContent = title.textContent;
				}

				var clock = doc.getElementById( 'cb-clock' );
				var liveClock = byId( 'cb-clock' );
				if ( clock && liveClock ) {
					liveClock.setAttribute( 'data-server-time', clock.getAttribute( 'data-server-time' ) );
					liveClock.setAttribute( 'data-offset', clock.getAttribute( 'data-offset' ) );
					readServerTime();
				}

				startPages();
				tickSchedule();
			} )
			.catch( function () { /* offline: keep showing the last good content */ } );
	}

	// ---------------------------------------------------------------------
	// QR code
	// ---------------------------------------------------------------------

	var qrHolder = byId( 'cb-qr' );
	var defaultQrUrl = qrHolder ? qrHolder.getAttribute( 'data-url' ) || '' : '';
	var shownQrUrl = null;

	// Draw the QR code for an address (the screen's own address when none is given)
	function drawQr( url ) {
		var target = byId( 'cb-qr-code' );

		url = url || defaultQrUrl;

		if ( ! target || ! url || typeof window.qrcode !== 'function' || url === shownQrUrl ) {
			return;
		}

		try {
			var qr = window.qrcode( 0, 'M' );
			qr.addData( url );
			qr.make();
			target.innerHTML = qr.createSvgTag( { cellSize: 4, margin: 16, scalable: true } );
			shownQrUrl = url;

			if ( qrHolder ) {
				qrHolder.hidden = false;
			}
		} catch ( e ) {
			// Too long for a QR code or a library problem: hide it rather than show a broken one
			shownQrUrl = null;

			if ( qrHolder ) {
				qrHolder.hidden = true;
			}
		}
	}

	// A page (a slide) can have its own QR address in data-url, shown while it is on screen
	function pageQr() {
		var active = content.querySelector( '.cb-page.is-active' );

		drawQr( active ? active.getAttribute( 'data-url' ) || '' : '' );
	}

	// ---------------------------------------------------------------------
	// Settings menu
	// ---------------------------------------------------------------------

	var menu = byId( 'cb-menu' );
	var panel = byId( 'cb-menu-panel' );
	var toggle = byId( 'cb-menu-toggle' );
	var awakeTimer = null;

	function wake() {
		if ( ! menu ) {
			return;
		}

		document.body.classList.add( 'is-awake' );
		window.clearTimeout( awakeTimer );

		awakeTimer = window.setTimeout( function () {
			if ( panel && panel.hidden ) {
				document.body.classList.remove( 'is-awake' );
			}
		}, 4000 );
	}

	function actions( name ) {
		switch ( name ) {
			case 'fullscreen':
				if ( document.fullscreenElement ) {
					document.exitFullscreen();
				} else if ( document.documentElement.requestFullscreen ) {
					document.documentElement.requestFullscreen();
				}
				return;

			case 'rotate-left':
				state.rotate = ( state.rotate + 270 ) % 360;
				setParam( 'rotate', String( state.rotate ) );
				break;

			case 'rotate-right':
				state.rotate = ( state.rotate + 90 ) % 360;
				setParam( 'rotate', String( state.rotate ) );
				break;

			case 'flip-horizontal':
				state.flip = state.flip === 'horizontal' ? 'none' : 'horizontal';
				setParam( 'flip', state.flip );
				break;

			case 'flip-vertical':
				state.flip = state.flip === 'vertical' ? 'none' : 'vertical';
				setParam( 'flip', state.flip );
				break;

			case 'orientation':
				state.orientation = state.orientation === 'portrait' ? 'landscape' : 'portrait';
				setParam( 'orientation', state.orientation );
				break;

			case 'clock':
				state.clock = ! state.clock;
				setParam( 'clock', state.clock ? 'yes' : 'no' );
				tick();
				break;

			case 'qr':
				state.qr = ! state.qr;
				setParam( 'qr', state.qr ? 'yes' : 'no' );
				break;

			case 'reset':
				try {
					var url = new URL( window.location.href );
					[ 'orientation', 'rotate', 'flip', 'interval', 'refresh', 'clock', 'qr' ].forEach( function ( key ) {
						url.searchParams.delete( key );
					} );
					window.location.href = url.toString();
				} catch ( e ) {
					window.location.reload();
				}
				return;
		}

		layout();
	}

	if ( menu && panel && toggle ) {

		toggle.addEventListener( 'click', function () {
			panel.hidden = ! panel.hidden;
			toggle.setAttribute( 'aria-expanded', panel.hidden ? 'false' : 'true' );
			wake();
		} );

		panel.addEventListener( 'click', function ( event ) {
			var button = event.target.closest ? event.target.closest( 'button[data-action]' ) : null;

			if ( button ) {
				actions( button.getAttribute( 'data-action' ) );
				wake();
			}
		} );

		[ 'mousemove', 'touchstart', 'keydown' ].forEach( function ( type ) {
			document.addEventListener( type, wake, { passive: true } );
		} );
	}

	// ---------------------------------------------------------------------
	// Go
	// ---------------------------------------------------------------------

	window.addEventListener( 'resize', layout );

	readServerTime();
	layout();
	tick();
	tickSchedule();
	startPages();

	window.setInterval( tick, 1000 );
	window.setInterval( tickSchedule, 1000 );
	window.setInterval( refresh, state.refresh * 1000 );
} )();
