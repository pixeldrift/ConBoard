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
	// Pages: loop through them when there is more than one
	// ---------------------------------------------------------------------

	function showPage( index ) {
		var pages = content.querySelectorAll( '.cb-page' );

		for ( var i = 0; i < pages.length; i++ ) {
			pages[ i ].classList.toggle( 'is-active', i === index );
		}

		pageIndex = index;
	}

	function startPages() {
		var pages = content.querySelectorAll( '.cb-page' );

		if ( pageTimer ) {
			window.clearInterval( pageTimer );
			pageTimer = null;
		}

		if ( pages.length < 2 ) {
			return;
		}

		showPage( Math.min( pageIndex, pages.length - 1 ) );

		pageTimer = window.setInterval( function () {
			var count = content.querySelectorAll( '.cb-page' ).length;
			showPage( count ? ( pageIndex + 1 ) % count : 0 );
		}, state.interval * 1000 );
	}

	// ---------------------------------------------------------------------
	// Live refresh
	// ---------------------------------------------------------------------

	function refresh() {
		if ( ! window.fetch || ! window.DOMParser ) {
			return;
		}

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
			} )
			.catch( function () { /* offline: keep showing the last good content */ } );
	}

	// ---------------------------------------------------------------------
	// QR code
	// ---------------------------------------------------------------------

	function drawQr() {
		var holder = byId( 'cb-qr' );
		var target = byId( 'cb-qr-code' );
		var url = holder ? holder.getAttribute( 'data-url' ) : '';

		if ( ! target || ! url || typeof window.qrcode !== 'function' ) {
			return;
		}

		try {
			var qr = window.qrcode( 0, 'M' );
			qr.addData( url );
			qr.make();
			target.innerHTML = qr.createSvgTag( { cellSize: 4, margin: 16, scalable: true } );
		} catch ( e ) {
			// Too long for a QR code or a library problem: hide it rather than show a broken one
			if ( holder ) {
				holder.hidden = true;
			}
		}
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
	drawQr();
	startPages();

	window.setInterval( tick, 1000 );
	window.setInterval( refresh, state.refresh * 1000 );
} )();
