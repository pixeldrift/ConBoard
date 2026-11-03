/**
 * Screen Assignments: drag a Display Board onto a screen card, or a screen card onto a board.
 * A drop-down on each card does the same thing without dragging (touch screens, keyboards).
 */
( function () {
	'use strict';

	var cfg = window.CONBOARD_SCREENS || {};
	var grid = document.getElementById( 'cbs-grid' );
	var side = document.getElementById( 'cbs-boards' );
	var statusBox = document.getElementById( 'cbs-status' );
	var statusTimer = null;

	if ( ! grid || ! side ) {
		return;
	}

	function say( text, isError ) {
		statusBox.textContent = text;
		statusBox.className = 'cbs-status is-visible' + ( isError ? ' is-error' : '' );
		window.clearTimeout( statusTimer );
		statusTimer = window.setTimeout( function () {
			statusBox.className = 'cbs-status';
		}, 2500 );
	}

	function closestOf( node, selector ) {
		return node && node.closest ? node.closest( selector ) : null;
	}

	function updateCounts() {
		var counts = {};
		Array.prototype.forEach.call( grid.querySelectorAll( '.cbs-card' ), function ( card ) {
			var id = card.getAttribute( 'data-board' );
			if ( id && id !== '0' ) {
				counts[ id ] = ( counts[ id ] || 0 ) + 1;
			}
		} );
		Array.prototype.forEach.call( side.querySelectorAll( '.cbs-board[data-board]' ), function ( chip ) {
			var badge = chip.querySelector( '.cbs-count' );
			var n = counts[ chip.getAttribute( 'data-board' ) ] || 0;
			if ( badge ) {
				badge.textContent = n ? n + ( n === 1 ? ' screen' : ' screens' ) : '';
			}
		} );
	}

	function showAssignment( card, boardId, title ) {
		var box = card.querySelector( '.cbs-assigned' );
		var clear = card.querySelector( '.cbs-clear' );
		var select = card.querySelector( '.cbs-select' );
		var has = boardId && boardId !== 0;

		card.setAttribute( 'data-board', has ? String( boardId ) : '0' );
		box.classList.toggle( 'is-empty', ! has );
		box.querySelector( '.cbs-assigned-name' ).textContent = has ? title : 'Nothing assigned';
		clear.hidden = ! has;
		select.value = has ? String( boardId ) : '0';
		updateCounts();
	}

	function assign( card, boardId ) {
		var previous = card.getAttribute( 'data-board' ) || '0';

		window.fetch( cfg.root + card.getAttribute( 'data-screen' ) + '/board', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify( { board: boardId } )
		} )
			.then( function ( r ) {
				return r.ok ? r.json() : Promise.reject( new Error( 'Save failed' ) );
			} )
			.then( function ( data ) {
				showAssignment( card, data.board, data.board_title );
				say( data.board ? 'Assigned "' + data.board_title + '"' : 'Unassigned' );
			} )
			.catch( function () {
				showAssignment( card, parseInt( previous, 10 ) || 0, card.querySelector( '.cbs-assigned-name' ).textContent );
				say( 'Could not save that change.', true );
			} );
	}

	// Remember each card's starting board (kept in sync by showAssignment)
	Array.prototype.forEach.call( grid.querySelectorAll( '.cbs-card' ), function ( card ) {
		card.setAttribute( 'data-board', card.querySelector( '.cbs-select' ).value );
	} );
	updateCounts();

	// ----- Dragging -----

	var dragged = null; // { kind: 'screen'|'board', id: number, node }

	document.addEventListener( 'dragstart', function ( e ) {
		var card = closestOf( e.target, '.cbs-card' );
		var chip = closestOf( e.target, '.cbs-board[data-board]' );
		var node = card || chip;

		if ( ! node || node.getAttribute( 'data-board' ) === '0' && chip ) {
			return;
		}

		dragged = card
			? { kind: 'screen', id: parseInt( card.getAttribute( 'data-screen' ), 10 ), node: card }
			: { kind: 'board', id: parseInt( chip.getAttribute( 'data-board' ), 10 ), node: chip };

		node.classList.add( 'is-dragging' );
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData( 'text/plain', dragged.kind + ':' + dragged.id );
	} );

	document.addEventListener( 'dragend', function () {
		if ( dragged ) {
			dragged.node.classList.remove( 'is-dragging' );
		}
		dragged = null;
		Array.prototype.forEach.call( document.querySelectorAll( '.is-over' ), function ( n ) {
			n.classList.remove( 'is-over' );
		} );
	} );

	// A board can be dropped on a screen card; a screen card on a board (or Unassigned)
	function targetFor( e ) {
		if ( ! dragged ) {
			return null;
		}
		return dragged.kind === 'board' ? closestOf( e.target, '.cbs-card' ) : closestOf( e.target, '.cbs-board[data-board]' );
	}

	document.addEventListener( 'dragover', function ( e ) {
		var target = targetFor( e );

		Array.prototype.forEach.call( document.querySelectorAll( '.is-over' ), function ( n ) {
			if ( n !== target ) {
				n.classList.remove( 'is-over' );
			}
		} );

		if ( target ) {
			e.preventDefault();
			target.classList.add( 'is-over' );
		}
	} );

	document.addEventListener( 'drop', function ( e ) {
		var target = targetFor( e );

		if ( ! target ) {
			return;
		}

		e.preventDefault();
		target.classList.remove( 'is-over' );

		if ( dragged.kind === 'board' ) {
			assign( target, dragged.id );
		} else {
			assign( dragged.node, parseInt( target.getAttribute( 'data-board' ), 10 ) || 0 );
		}
	} );

	// ----- Buttons and lists on the cards -----

	grid.addEventListener( 'click', function ( e ) {
		var clear = closestOf( e.target, '.cbs-clear' );
		var copy = closestOf( e.target, '.cbs-copy' );

		if ( clear ) {
			assign( closestOf( clear, '.cbs-card' ), 0 );
		}

		if ( copy ) {
			e.preventDefault();
			var address = copy.getAttribute( 'data-address' );
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( address ).then( function () { say( 'Address copied' ); } );
			} else {
				window.prompt( 'Copy this address:', address );
			}
		}
	} );

	grid.addEventListener( 'change', function ( e ) {
		var select = closestOf( e.target, '.cbs-select' );

		if ( select ) {
			assign( closestOf( select, '.cbs-card' ), parseInt( select.value, 10 ) || 0 );
		}
	} );
}() );
