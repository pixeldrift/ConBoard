// Minimal ZIP writer (no compression - JPEG files are already compressed).
// ConBoardZip.build([{ name: 'folder/a.jpg', dataUrl: 'data:image/jpeg;base64,...' }]) -> Blob
( function ( window ) {
	'use strict';

	var table = ( function () {
		var t = [], c, n, k;
		for ( n = 0; n < 256; n++ ) {
			c = n;
			for ( k = 0; k < 8; k++ ) { c = c & 1 ? 0xEDB88320 ^ ( c >>> 1 ) : c >>> 1; }
			t[ n ] = c >>> 0;
		}
		return t;
	} )();

	function crc32( bytes ) {
		var c = 0xFFFFFFFF;
		for ( var i = 0; i < bytes.length; i++ ) { c = table[ ( c ^ bytes[ i ] ) & 255 ] ^ ( c >>> 8 ); }
		return ( c ^ 0xFFFFFFFF ) >>> 0;
	}

	function dataUrlToBytes( dataUrl ) {
		var bin = window.atob( dataUrl.split( ',' )[ 1 ] || '' );
		var out = new Uint8Array( bin.length );
		for ( var i = 0; i < bin.length; i++ ) { out[ i ] = bin.charCodeAt( i ); }
		return out;
	}

	function build( files ) {
		var enc = new TextEncoder();
		var now = new Date();
		var dosTime = ( now.getHours() << 11 ) | ( now.getMinutes() << 5 ) | ( now.getSeconds() >> 1 );
		var dosDate = ( ( now.getFullYear() - 1980 ) << 9 ) | ( ( now.getMonth() + 1 ) << 5 ) | now.getDate();
		var parts = [], central = [], offset = 0;

		files.forEach( function ( file ) {
			var name = enc.encode( file.name );
			var data = file.bytes || dataUrlToBytes( file.dataUrl );
			var crc = crc32( data );

			var local = new DataView( new ArrayBuffer( 30 ) );
			local.setUint32( 0, 0x04034b50, true );
			local.setUint16( 4, 20, true );
			local.setUint16( 6, 0x0800, true );          // UTF-8 names
			local.setUint16( 8, 0, true );               // stored
			local.setUint16( 10, dosTime, true );
			local.setUint16( 12, dosDate, true );
			local.setUint32( 14, crc, true );
			local.setUint32( 18, data.length, true );
			local.setUint32( 22, data.length, true );
			local.setUint16( 26, name.length, true );
			local.setUint16( 28, 0, true );

			var head = new DataView( new ArrayBuffer( 46 ) );
			head.setUint32( 0, 0x02014b50, true );
			head.setUint16( 4, 20, true );
			head.setUint16( 6, 20, true );
			head.setUint16( 8, 0x0800, true );
			head.setUint16( 10, 0, true );
			head.setUint16( 12, dosTime, true );
			head.setUint16( 14, dosDate, true );
			head.setUint32( 16, crc, true );
			head.setUint32( 20, data.length, true );
			head.setUint32( 24, data.length, true );
			head.setUint16( 28, name.length, true );
			head.setUint32( 42, offset, true );

			parts.push( new Uint8Array( local.buffer ), name, data );
			central.push( new Uint8Array( head.buffer ), name );
			offset += 30 + name.length + data.length;
		} );

		var cdSize = central.reduce( function ( sum, p ) { return sum + p.length; }, 0 );
		var end = new DataView( new ArrayBuffer( 22 ) );
		end.setUint32( 0, 0x06054b50, true );
		end.setUint16( 8, files.length, true );
		end.setUint16( 10, files.length, true );
		end.setUint32( 12, cdSize, true );
		end.setUint32( 16, offset, true );

		return new Blob( parts.concat( central, [ new Uint8Array( end.buffer ) ] ), { type: 'application/zip' } );
	}

	window.ConBoardZip = { build: build };
} )( window );
