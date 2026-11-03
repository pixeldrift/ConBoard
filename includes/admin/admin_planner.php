<?php
// includes/admin/admin_planner.php
//
// Program Planner: drag sessions onto a day grid (rooms as columns) to build the program.
// Step 1 = unscheduled cards + day grid. The map pane comes next.

if ( ! defined( 'ABSPATH' ) ) exit;

const CONBOARD_PLANNER_SNAP = 15; // minutes

add_action( 'admin_menu', function () {
	add_submenu_page(
		'conboard',
		'Program Planner',
		'Program Planner',
		'edit_others_posts',
		'conboard_planner',
		'conboard_planner_page'
	);
} );

/**
 * Minutes a session lasts: from its times, else the stored duration of an unscheduled session, else the default.
 */
function conboard_planner_duration( int $id, int $start, int $end ): int {

	if ( $start && $end > $start ) return (int) round( ( $end - $start ) / 60 );

	$stored = (int) get_post_meta( $id, '_conboard_duration', true );
	return $stored > 0 ? $stored : conboard_default_session_minutes();
}

function conboard_planner_session( $post ): array {

	$id    = (int) $post->ID;
	$start = conboard_local_to_timestamp( get_post_meta( $id, 'start_time', true ) );
	$end   = conboard_local_to_timestamp( get_post_meta( $id, 'end_time', true ) );

	$date = '';
	$mins = null;
	if ( $start ) {
		// "Y-m-d H:i:s" text is site-local, so read it directly rather than re-converting the timestamp
		$text = trim( (string) get_post_meta( $id, 'start_time', true ) );
		$date = substr( $text, 0, 10 );
		$mins = ( (int) substr( $text, 11, 2 ) ) * 60 + (int) substr( $text, 14, 2 );
	}

	$location_id = (int) get_post_meta( $id, 'location', true );

	return [
		'id'        => $id,
		'title'     => get_the_title( $post ) ?: '(no title)',
		'status'    => $post->post_status,
		'date'      => $date,
		'start'     => $mins,
		'duration'  => conboard_planner_duration( $id, $start, $end ),
		'location'  => $location_id,
		'cancelled' => get_post_meta( $id, 'cancelled', true ) === '1',
		'edit'      => get_edit_post_link( $id, 'raw' ),
	];
}

function conboard_planner_data(): array {

	$sessions = [];
	foreach ( get_posts( [ 'post_type' => 'session', 'post_status' => [ 'publish', 'future', 'draft', 'pending', 'private' ], 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] ) as $post ) {
		$sessions[] = conboard_planner_session( $post );
	}

	// Rooms are the grid columns; fall back to every location if none are marked as rooms
	$locations = get_posts( [ 'post_type' => 'location', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
	$rooms     = array_filter( $locations, fn( $l ) => get_post_meta( $l->ID, 'location_type', true ) === 'room' );
	$rooms     = $rooms ?: $locations;

	$room_list = [];
	foreach ( $rooms as $r ) {
		$room_list[] = [
			'id'     => (int) $r->ID,
			'title'  => get_the_title( $r ),
			'parent' => $r->post_parent ? get_the_title( $r->post_parent ) : '',
		];
	}

	// Days: the event's days plus any other date that already has sessions
	$days = [];
	foreach ( conboard_get_event_days() as $d ) {
		$days[ $d['date'] ] = conboard_format_event_day( $d['date'] );
	}
	foreach ( $sessions as $s ) {
		if ( $s['date'] && ! isset( $days[ $s['date'] ] ) ) {
			$days[ $s['date'] ] = conboard_format_event_day( $s['date'] );
		}
	}
	if ( ! $days ) {
		$today = wp_date( 'Y-m-d' );
		$days[ $today ] = conboard_format_event_day( $today );
	}
	ksort( $days );

	$day_list = [];
	foreach ( $days as $date => $label ) $day_list[] = [ 'date' => $date, 'label' => $label ];

	return [
		'sessions' => $sessions,
		'rooms'    => $room_list,
		'days'     => $day_list,
		'snap'     => CONBOARD_PLANNER_SNAP,
		'defaultMinutes' => conboard_default_session_minutes(),
	];
}

/**
 * Apply a planner change to one session.
 *
 * @param array $args  unschedule=true, or date (Y-m-d), start (minutes from midnight), duration (minutes), location (id, optional)
 * @return array|WP_Error  the updated session
 */
function conboard_planner_apply( int $id, array $args ) {

	$post = get_post( $id );
	if ( ! $post || $post->post_type !== 'session' ) return new WP_Error( 'conboard_bad_session', 'Not a session.', [ 'status' => 404 ] );

	$set = function ( string $name, $value ) use ( $id ) {
		if ( function_exists( 'update_field' ) ) update_field( $name, $value, $id );
		else update_post_meta( $id, $name, $value );
	};

	if ( ! empty( $args['unschedule'] ) ) {

		// Remember how long it was so it keeps its length when scheduled again
		$start = conboard_local_to_timestamp( get_post_meta( $id, 'start_time', true ) );
		$end   = conboard_local_to_timestamp( get_post_meta( $id, 'end_time', true ) );
		update_post_meta( $id, '_conboard_duration', conboard_planner_duration( $id, $start, $end ) );

		$set( 'start_time', '' );
		$set( 'end_time', '' );

	} else {

		$date = (string) ( $args['date'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return new WP_Error( 'conboard_bad_date', 'Invalid date.', [ 'status' => 400 ] );

		$snap     = CONBOARD_PLANNER_SNAP;
		$duration = max( $snap, (int) round( (int) ( $args['duration'] ?? 60 ) / $snap ) * $snap );
		$start    = (int) round( (int) ( $args['start'] ?? 0 ) / $snap ) * $snap;
		$start    = max( 0, min( 1440 - $snap, $start ) );
		$end      = min( 1440, $start + $duration );

		$fmt = fn( int $m ) => sprintf( '%02d:%02d:00', intdiv( $m, 60 ) % 24, $m % 60 );

		$set( 'start_time', $date . ' ' . $fmt( $start ) );
		$set( 'end_time', $end >= 1440 ? $date . ' 23:59:00' : $date . ' ' . $fmt( $end ) );

		if ( ! empty( $args['location'] ) && get_post_type( (int) $args['location'] ) === 'location' ) {
			$set( 'location', (int) $args['location'] );
		}
		delete_post_meta( $id, '_conboard_duration' );
	}

	// Let conflict checks and cached counts run as if the session was saved in the editor
	do_action( 'acf/save_post', $id );

	return conboard_planner_session( get_post( $id ) );
}

add_action( 'rest_api_init', function () {

	register_rest_route( 'conboard/v1', '/planner/session/(?P<id>\d+)', [
		'methods'             => 'POST',
		'permission_callback' => fn( WP_REST_Request $r ) => current_user_can( 'edit_post', (int) $r['id'] ),
		'callback'            => function ( WP_REST_Request $r ) {
			$result = conboard_planner_apply( (int) $r['id'], (array) $r->get_json_params() );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		},
	] );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {

	if ( strpos( (string) $hook, 'conboard_planner' ) === false ) return;

	wp_enqueue_script( 'conboard-planner', CONBOARD_PLUGIN_URL . 'assets/js/admin_planner.js', [], '0.1.0', true );
	wp_localize_script( 'conboard-planner', 'CONBOARD_PLANNER', [
		'root'  => esc_url_raw( rest_url( 'conboard/v1/planner/session/' ) ),
		'nonce' => wp_create_nonce( 'wp_rest' ),
		'data'  => conboard_planner_data(),
	] );
} );

function conboard_planner_page(): void {
	?>
	<div class="wrap conboard-planner-wrap">
		<h1>Program Planner</h1>
		<p class="description">Drag a session card onto a room and time. Drag a block to move it, drag its bottom edge to change the length, or drop it on the Unscheduled area to clear its date and time (its length is kept). Drop on the side strips to move it to the previous or next day.</p>
		<div id="conboard-planner"></div>
	</div>
	<style>
		.conboard-planner-wrap #conboard-planner { display:flex; gap:16px; height:calc(100vh - 190px); min-height:520px; }
		.cbp-left { flex:1 1 0; min-width:280px; display:flex; flex-direction:column; gap:12px; }
		.cbp-map { flex:1 1 55%; border:2px dashed #c3c4c7; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#787c82; background:#fff; text-align:center; padding:12px; }
		.cbp-unscheduled { flex:1 1 45%; display:flex; flex-direction:column; background:#fff; border:1px solid #c3c4c7; border-radius:6px; min-height:0; }
		.cbp-unscheduled.cbp-over, .cbp-sliver.cbp-over { outline:3px solid #2271b1; }
		.cbp-unscheduled header { padding:8px 10px; display:flex; gap:8px; align-items:center; border-bottom:1px solid #dcdcde; }
		.cbp-unscheduled header input { flex:1; }
		.cbp-cards { overflow:auto; padding:8px; display:flex; flex-wrap:wrap; gap:8px; align-content:flex-start; }
		.cbp-card { background:#f0f6fc; border:1px solid #72aee6; border-radius:5px; padding:6px 8px; width:170px; cursor:grab; font-size:12px; }
		.cbp-card b { display:block; font-size:13px; }
		.cbp-card small { color:#50575e; }
		.cbp-right { flex:0 0 min(760px, 55%); display:flex; flex-direction:column; background:#fff; border:1px solid #c3c4c7; border-radius:6px; min-width:0; }
		.cbp-daybar { display:flex; align-items:center; justify-content:center; gap:12px; padding:8px; border-bottom:1px solid #dcdcde; font-weight:600; }
		.cbp-daywrap { flex:1; display:flex; min-height:0; }
		.cbp-sliver { flex:0 0 46px; background:#f6f7f7; border:0 solid #dcdcde; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#50575e; font-size:12px; }
		.cbp-sliver span { writing-mode:vertical-rl; transform:rotate(180deg); white-space:nowrap; }
		.cbp-sliver.prev { border-right-width:1px; } .cbp-sliver.next { border-left-width:1px; }
		.cbp-sliver.next span { transform:none; }
		.cbp-grid { flex:1; overflow:auto; position:relative; min-width:0; }
		.cbp-gridin { display:flex; min-width:max-content; }
		.cbp-axis { flex:0 0 52px; position:sticky; left:0; background:#fff; z-index:3; border-right:1px solid #dcdcde; }
		.cbp-axis div { position:absolute; right:4px; font-size:11px; color:#787c82; transform:translateY(-50%); }
		.cbp-col { flex:1 0 140px; border-right:1px solid #dcdcde; }
		.cbp-colhead { position:sticky; top:0; z-index:2; background:#f6f7f7; border-bottom:1px solid #dcdcde; padding:6px; font-size:12px; font-weight:600; height:36px; box-sizing:border-box; overflow:hidden; }
		.cbp-colbody { position:relative; background-image:repeating-linear-gradient(to bottom, transparent 0, transparent calc(var(--hour) - 1px), #eee calc(var(--hour) - 1px), #eee var(--hour)); }
		.cbp-colbody.cbp-over { background-color:#f0f6fc; }
		.cbp-block { position:absolute; left:3px; right:3px; background:#d7eafc; border:1px solid #2271b1; border-radius:4px; padding:3px 5px; font-size:11px; overflow:hidden; cursor:grab; box-sizing:border-box; }
		.cbp-block b { display:block; line-height:1.2; }
		.cbp-block.cbp-conflict { background:#fcdcdc; border-color:#b32d2e; }
		.cbp-block.cbp-cancelled { opacity:.55; text-decoration:line-through; }
		.cbp-block.cbp-saving { opacity:.6; }
		.cbp-resize { position:absolute; left:0; right:0; bottom:0; height:7px; cursor:ns-resize; background:rgba(34,113,177,.35); }
		.cbp-empty { color:#787c82; padding:10px; font-size:12px; }
	</style>
	<?php
}
