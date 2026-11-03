// Export every board as JPGs in one ZIP. Boards are exported one at a time in a hidden frame.
( function () {
	var cfg = window.CONBOARD_EXPORT;
	var button = document.getElementById( 'conboard-export-boards' );
	var status = document.getElementById( 'conboard-export-status' );
	if ( ! cfg || ! button ) return;

	function slug( text ) {
		return ( text || 'board' ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ) || 'board';
	}

	function exportBoard( board ) {
		return new Promise( function ( resolve ) {
			var frame = document.createElement( 'iframe' );
			frame.style.cssText = 'position:fixed;left:-10000px;top:0;width:1920px;height:1080px;border:0;';
			var timer = window.setTimeout( finish, 120000 );

			function finish( files ) {
				window.clearTimeout( timer );
				window.removeEventListener( 'message', onMessage );
				frame.remove();
				resolve( Array.isArray( files ) ? files : [] );
			}
			function onMessage( event ) {
				if ( event.origin !== window.location.origin || event.source !== frame.contentWindow ) return;
				if ( ! event.data || event.data.type !== 'conboard-export' ) return;
				finish( event.data.files );
			}

			window.addEventListener( 'message', onMessage );
			frame.src = board.url;
			document.body.appendChild( frame );
		} );
	}

	button.addEventListener( 'click', function () {
		var flat = ( document.querySelector( 'input[name="conboard-export-layout"]:checked' ) || {} ).value === 'flat';
		var boards = cfg.boards || [];

		if ( ! boards.length ) { status.textContent = 'There are no published boards.'; return; }

		button.disabled = true;
		var all = [];
		var used = {};
		var chain = Promise.resolve();

		boards.forEach( function ( board, i ) {
			chain = chain.then( function () {
				status.textContent = 'Saving ' + board.title + ' (' + ( i + 1 ) + ' of ' + boards.length + ')...';
				return exportBoard( board );
			} ).then( function ( files ) {
				var name = slug( board.title );
				used[ name ] = ( used[ name ] || 0 ) + 1;
				if ( used[ name ] > 1 ) name += '-' + used[ name ];

				files.forEach( function ( file, n ) {
					// Rename to the board title so folders and flat files always match the list
					var number = file.name.replace( /^.*-(\d+)\.jpg$/, '$1' );
					var fileName = name + '-' + number + '.jpg';
					all.push( { name: flat ? fileName : name + '/' + fileName, dataUrl: file.dataUrl } );
				} );
			} );
		} );

		chain.then( function () {
			if ( ! all.length ) { status.textContent = 'Nothing could be saved.'; return; }

			var url = window.URL.createObjectURL( window.ConBoardZip.build( all ) );
			var link = document.createElement( 'a' );
			link.href = url;
			link.download = 'conboard-boards.zip';
			document.body.appendChild( link );
			link.click();
			link.remove();
			window.setTimeout( function () { window.URL.revokeObjectURL( url ); }, 10000 );
			status.textContent = 'Saved ' + all.length + ' pictures.';
		} ).catch( function () {
			status.textContent = 'Something went wrong while saving.';
		} ).then( function () {
			button.disabled = false;
		} );
	} );
} )();
