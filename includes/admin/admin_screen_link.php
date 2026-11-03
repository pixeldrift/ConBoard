<?php
// includes/admin/admin_screen_link.php
//
// "Get Display Screen": a box on every ConBoard post that gives the Screen Mode link,
// with options for orientation, rotation and flip.

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', function () {

	foreach ( conboard_screen_post_types() as $post_type ) {
		add_meta_box(
			'conboard_screen_link',
			'Display Screen',
			'conboard_render_screen_link_metabox',
			$post_type,
			'side',
			'default'
		);
	}
});

function conboard_render_screen_link_metabox( WP_Post $post ): void {

	$base = get_permalink( $post );

	if ( ! $base ) {
		echo '<p>Save this first to get its display link.</p>';
		return;
	}

	$published = $post->post_status === 'publish';

	?>
	<div class="conboard-screen-link" data-base="<?php echo esc_attr( $base ); ?>">

		<p class="description">
			Show this page full screen on a TV or projector. Anyone with the link can use it, no login needed.
		</p>

		<p>
			<label>Orientation<br>
				<select data-param="orientation" style="width:100%">
					<option value="landscape">Landscape</option>
					<option value="portrait">Portrait</option>
				</select>
			</label>
		</p>

		<p>
			<label>Rotate<br>
				<select data-param="rotate" style="width:100%">
					<option value="0">None</option>
					<option value="90">90° right</option>
					<option value="180">180°</option>
					<option value="270">90° left</option>
				</select>
			</label>
		</p>

		<p>
			<label>Flip<br>
				<select data-param="flip" style="width:100%">
					<option value="none">None</option>
					<option value="horizontal">Horizontal</option>
					<option value="vertical">Vertical</option>
				</select>
			</label>
		</p>

		<p>
			<input type="text" class="conboard-screen-url widefat" readonly onfocus="this.select()">
		</p>

		<p>
			<button type="button" class="button button-primary conboard-screen-copy">Copy link</button>
			<a class="button conboard-screen-open" href="#" target="_blank" rel="noopener">Open</a>
		</p>

		<?php if ( ! $published ) : ?>
			<p class="description">This is not published yet. Publish it to get its final link.</p>
		<?php endif; ?>
	</div>

	<script>
	( function () {
		var box = document.querySelector( '.conboard-screen-link' );
		if ( ! box ) { return; }

		var defaults = { orientation: 'landscape', rotate: '0', flip: 'none' };
		var input = box.querySelector( '.conboard-screen-url' );
		var open = box.querySelector( '.conboard-screen-open' );
		var copy = box.querySelector( '.conboard-screen-copy' );

		function build() {
			var url = box.getAttribute( 'data-base' );
			var parts = [ 'display=screen' ];

			box.querySelectorAll( 'select[data-param]' ).forEach( function ( select ) {
				var key = select.getAttribute( 'data-param' );
				if ( select.value !== defaults[ key ] ) {
					parts.push( key + '=' + encodeURIComponent( select.value ) );
				}
			} );

			url += ( url.indexOf( '?' ) === -1 ? '?' : '&' ) + parts.join( '&' );
			input.value = url;
			open.href = url;
		}

		box.querySelectorAll( 'select' ).forEach( function ( select ) {
			select.addEventListener( 'change', build );
		} );

		copy.addEventListener( 'click', function () {
			input.select();

			var done = function () {
				copy.textContent = 'Copied!';
				window.setTimeout( function () { copy.textContent = 'Copy link'; }, 1500 );
			};

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( input.value ).then( done );
			} else {
				document.execCommand( 'copy' );
				done();
			}
		} );

		build();
	} )();
	</script>
	<?php
}
