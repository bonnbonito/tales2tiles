<?php
/**
 * Google Drive storage for customer photos and receipts (Tile orders → Google Drive).
 *
 * - The owner connects their own Google account with OAuth, using their own Google Cloud
 *   "app" (step-by-step instructions on the settings page). Scope drive.file: the site can
 *   only see files it created, nothing else in the owner's Drive.
 * - Orders are saved on the website first (the customer never waits for Google). A WP-Cron job
 *   then copies the files to My Drive → "Tales to Tiles orders" → "<order no.> — <name>",
 *   20 files per run, resuming after failures. Progress lives in post meta `_tt_drive`, apart
 *   from `_tt_order`, so it never overwrites status changes made meanwhile.
 * - Optionally the website copies are deleted after upload. The owner dashboard then loads
 *   them from Drive through an owner-only REST route (same origin, so print layouts still work).
 * - Full-quality originals arrive after the order (inc/orders.php); each one re-queues the job,
 *   which only copies files not yet in Drive.
 *
 * Settings: option `tales2tiles_drive`. Access token: transient `tales2tiles_drive_access`.
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

const TALES2TILES_DRIVE_PAGE = 'tales2tiles-drive';
const TALES2TILES_DRIVE_API  = 'https://www.googleapis.com/drive/v3/files';

/**
 * Saved settings with defaults.
 *
 * @return array
 */
function tales2tiles_drive_settings() {
	return wp_parse_args(
		(array) get_option( 'tales2tiles_drive', array() ),
		array(
			'client_id'     => '',
			'client_secret' => '',
			'refresh_token' => '',
			'email'         => '',
			'root'          => '', // Drive folder ID of "Tales to Tiles orders".
			'delete_local'  => '1',
			'error'         => '', // Last problem, shown on the settings page.
		)
	);
}

/**
 * Change some settings.
 *
 * @param array $changes Keys to update.
 */
function tales2tiles_drive_update( $changes ) {
	update_option( 'tales2tiles_drive', array_merge( tales2tiles_drive_settings(), $changes ), false );
}

/**
 * Whether a Google account is connected.
 *
 * @return bool
 */
function tales2tiles_drive_connected() {
	return '' !== tales2tiles_drive_settings()['refresh_token'];
}

/**
 * The address Google sends the owner back to (pasted into Google Cloud).
 *
 * @return string
 */
function tales2tiles_drive_redirect_uri() {
	return admin_url( 'admin-post.php?action=tales2tiles_drive_callback' );
}

/**
 * Settings page URL.
 *
 * @param string $message Optional message code to show.
 * @return string
 */
function tales2tiles_drive_page_url( $message = '' ) {
	$url = admin_url( 'edit.php?post_type=tt_order&page=' . TALES2TILES_DRIVE_PAGE );
	return $message ? add_query_arg( 'tt-drive', $message, $url ) : $url;
}

/* ---------- Google API helpers ---------- */

/**
 * Decode a Google JSON response; errors become WP_Error (data: status, google error code).
 *
 * @param array|WP_Error $response wp_remote_* response.
 * @return array|WP_Error
 */
function tales2tiles_drive_json( $response ) {
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $code >= 300 || ! is_array( $data ) ) {
		$error   = $data['error'] ?? '';
		$message = $data['error']['message'] ?? $data['error_description'] ?? ( is_string( $error ) && $error ? $error : 'HTTP ' . $code );
		return new WP_Error(
			'tales2tiles_drive',
			$message,
			array(
				'status' => $code,
				'google' => is_string( $error ) ? $error : '',
			)
		);
	}
	return $data;
}

/**
 * Call Google's token endpoint.
 *
 * @param array $params Grant parameters.
 * @return array|WP_Error
 */
function tales2tiles_drive_token_request( $params ) {
	$settings = tales2tiles_drive_settings();
	return tales2tiles_drive_json(
		wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => $params + array(
					'client_id'     => $settings['client_id'],
					'client_secret' => $settings['client_secret'],
				),
			)
		)
	);
}

/**
 * A valid access token, refreshed when needed.
 *
 * @return string|WP_Error
 */
function tales2tiles_drive_access_token() {
	$token = get_transient( 'tales2tiles_drive_access' );
	if ( $token ) {
		return $token;
	}
	$settings = tales2tiles_drive_settings();
	if ( ! $settings['refresh_token'] ) {
		return new WP_Error( 'tales2tiles_drive', __( 'Google Drive is not connected.', 'tales2tiles' ) );
	}
	$data = tales2tiles_drive_token_request(
		array(
			'grant_type'    => 'refresh_token',
			'refresh_token' => $settings['refresh_token'],
		)
	);
	if ( is_wp_error( $data ) ) {
		if ( 'invalid_grant' === ( $data->get_error_data()['google'] ?? '' ) ) { // Access removed in Google, or app left in "Testing".
			tales2tiles_drive_update(
				array(
					'refresh_token' => '',
					'error'         => __( 'Google ended the connection (access was removed, or the app is still in "Testing"). Connect again.', 'tales2tiles' ),
				)
			);
		}
		return $data;
	}
	set_transient( 'tales2tiles_drive_access', $data['access_token'], max( 60, (int) $data['expires_in'] - 120 ) );
	return $data['access_token'];
}

/**
 * Authorized request to a Google API.
 *
 * @param string $method HTTP method.
 * @param string $url    URL.
 * @param array  $args   wp_remote_request args.
 * @return array|WP_Error Raw response.
 */
function tales2tiles_drive_request( $method, $url, $args = array() ) {
	$token = tales2tiles_drive_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	$args['method']  = $method;
	$args['timeout'] = $args['timeout'] ?? 30;
	$args['headers'] = ( $args['headers'] ?? array() ) + array( 'Authorization' => 'Bearer ' . $token );
	return wp_remote_request( $url, $args );
}

/**
 * Create a Drive folder.
 *
 * @param string $name   Folder name.
 * @param string $parent Parent folder ID ('' = My Drive).
 * @return string|WP_Error Folder ID.
 */
function tales2tiles_drive_create_folder( $name, $parent = '' ) {
	$meta = array(
		'name'     => $name,
		'mimeType' => 'application/vnd.google-apps.folder',
	);
	if ( $parent ) {
		$meta['parents'] = array( $parent );
	}
	$data = tales2tiles_drive_json(
		tales2tiles_drive_request(
			'POST',
			TALES2TILES_DRIVE_API . '?fields=id',
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $meta ),
			)
		)
	);
	return is_wp_error( $data ) ? $data : $data['id'];
}

/**
 * The "Tales to Tiles orders" folder; recreated if the owner deleted it.
 *
 * @return string|WP_Error Folder ID.
 */
function tales2tiles_drive_root() {
	$root = tales2tiles_drive_settings()['root'];
	if ( $root ) {
		$data = tales2tiles_drive_json( tales2tiles_drive_request( 'GET', TALES2TILES_DRIVE_API . '/' . rawurlencode( $root ) . '?fields=trashed' ) );
		if ( ! is_wp_error( $data ) && empty( $data['trashed'] ) ) {
			return $root;
		}
		if ( is_wp_error( $data ) && 404 !== ( $data->get_error_data()['status'] ?? 0 ) ) {
			return $data; // Network/Google trouble: retry later rather than create duplicates.
		}
	}
	$root = tales2tiles_drive_create_folder( __( 'Tales to Tiles orders', 'tales2tiles' ) );
	if ( ! is_wp_error( $root ) ) {
		tales2tiles_drive_update( array( 'root' => $root ) );
	}
	return $root;
}

/**
 * Upload one file.
 *
 * @param string $path   Local path.
 * @param string $name   File name in Drive.
 * @param string $parent Folder ID.
 * @return string|WP_Error File ID.
 */
function tales2tiles_drive_upload_file( $path, $name, $parent ) {
	$bytes = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local upload.
	if ( false === $bytes ) {
		return new WP_Error( 'tales2tiles_drive', __( 'Could not read the file on the website.', 'tales2tiles' ) );
	}
	// Resumable upload (start a session, then PUT the bytes): multipart uploads are limited to 5 MB, and phone originals are often bigger.
	$type  = wp_check_filetype( $name )['type'] ? wp_check_filetype( $name )['type'] : 'application/octet-stream';
	$start = tales2tiles_drive_request(
		'POST',
		'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id',
		array(
			'headers' => array(
				'Content-Type'            => 'application/json; charset=UTF-8',
				'X-Upload-Content-Type'   => $type,
				'X-Upload-Content-Length' => (string) strlen( $bytes ),
			),
			'body'    => wp_json_encode(
				array(
					'name'    => $name,
					'parents' => array( $parent ),
				)
			),
		)
	);
	$session = is_wp_error( $start ) ? '' : wp_remote_retrieve_header( $start, 'location' );
	if ( ! $session ) {
		$error = tales2tiles_drive_json( $start );
		return is_wp_error( $error ) ? $error : new WP_Error( 'tales2tiles_drive', __( 'Google Drive did not start the upload.', 'tales2tiles' ) );
	}
	$data = tales2tiles_drive_json(
		tales2tiles_drive_request(
			'PUT',
			$session,
			array(
				'timeout' => 300,
				'headers' => array( 'Content-Type' => $type ),
				'body'    => $bytes,
			)
		)
	);
	return is_wp_error( $data ) ? $data : $data['id'];
}

/* ---------- Background upload of orders ---------- */

/**
 * An order's files on the website (receipt, print photos, originals): file name => path.
 * Paths are where the files are or were; they may already be deleted after a Drive upload.
 *
 * @param int $post_id Order post ID.
 * @return array
 */
function tales2tiles_drive_local_files( $post_id ) {
	$order   = (array) get_post_meta( $post_id, '_tt_order', true );
	$uploads = wp_upload_dir();
	$files   = array();
	foreach ( array_merge( array( $order['receipt'] ?? '' ), (array) ( $order['photos'] ?? array() ), tales2tiles_order_originals( $post_id ) ) as $url ) {
		if ( $url && 0 === strpos( $url, $uploads['baseurl'] ) ) {
			$files[ basename( $url ) ] = $uploads['basedir'] . substr( $url, strlen( $uploads['baseurl'] ) );
		}
	}
	return $files;
}

/**
 * Queue an order for upload.
 *
 * @param int $post_id Order post ID.
 * @param int $delay   Seconds from now.
 */
function tales2tiles_drive_queue( $post_id, $delay = 0 ) {
	if ( tales2tiles_drive_connected() ) {
		wp_schedule_single_event( time() + $delay, 'tales2tiles_drive_upload', array( (int) $post_id ) );
	}
}
add_action( 'tales2tiles_order_created', 'tales2tiles_drive_queue' );
add_action(
	'tales2tiles_original_uploaded',
	static function ( $post_id ) {
		delete_post_meta( $post_id, '_tt_drive_done' ); // A new file to copy.
		tales2tiles_drive_queue( $post_id, 2 * MINUTE_IN_SECONDS ); // Let the rest of the originals arrive first.
	}
);

/**
 * WP-Cron job: copy one order's files to Drive (a batch per run).
 *
 * @param int $post_id Order post ID.
 */
function tales2tiles_drive_upload_order( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'tt_order' !== $post->post_type || ! tales2tiles_drive_connected() ) {
		return;
	}
	$state = wp_parse_args(
		(array) get_post_meta( $post_id, '_tt_drive', true ),
		array(
			'folder'   => '',
			'files'    => array(), // name => Drive file ID ('' = was missing on the website).
			'attempts' => 0,
		)
	);
	$order = (array) get_post_meta( $post_id, '_tt_order', true );
	$files = array_diff_key( tales2tiles_drive_local_files( $post_id ), $state['files'] ); // Only what isn't in Drive yet.
	$error = null;
	if ( ! $files ) {
		update_post_meta( $post_id, '_tt_drive_done', 1 );
		return;
	}

	if ( ! $state['folder'] ) {
		$root = tales2tiles_drive_root();
		$id   = is_wp_error( $root ) ? $root : tales2tiles_drive_create_folder( $order['id'] . ' — ' . ( $order['customer']['name'] ?? '' ), $root );
		if ( is_wp_error( $id ) ) {
			$error = $id;
		} else {
			$state['folder'] = $id;
			update_post_meta( $post_id, '_tt_drive', $state );
		}
	}

	$budget = 20; // ponytail: files per cron run, keeps each run well under PHP time limits.
	foreach ( $files as $name => $path ) {
		if ( $error ) {
			break;
		}
		if ( 0 === $budget-- ) {
			tales2tiles_drive_queue( $post_id ); // More to do: continue on the next run.
			return;
		}
		if ( ! file_exists( $path ) ) {
			$state['files'][ $name ] = ''; // Gone from the website; nothing to copy.
			continue;
		}
		$id = tales2tiles_drive_upload_file( $path, $name, $state['folder'] );
		if ( is_wp_error( $id ) ) {
			$error = $id;
			break;
		}
		$state['files'][ $name ] = $id;
		update_post_meta( $post_id, '_tt_drive', $state ); // Save progress after every file.
	}

	if ( $error ) {
		$state['attempts']++;
		update_post_meta( $post_id, '_tt_drive', $state );
		/* translators: 1: order number, 2: Google's error message. */
		tales2tiles_drive_update( array( 'error' => sprintf( __( 'Order %1$s: %2$s', 'tales2tiles' ), $order['id'] ?? $post_id, $error->get_error_message() ) ) );
		if ( $state['attempts'] < 24 ) { // ponytail: ~1 day of retries, then it waits for "Retry uploads" on the settings page.
			tales2tiles_drive_queue( $post_id, min( HOUR_IN_SECONDS, 5 * MINUTE_IN_SECONDS * $state['attempts'] ) );
		}
		return;
	}

	$state['done']     = 1;
	$state['attempts'] = 0;
	if ( '1' === tales2tiles_drive_settings()['delete_local'] ) {
		array_map( 'wp_delete_file', $files );
		$dir = dirname( reset( $files ) );
		// Remove the folder only when nothing else is in it: an original may have arrived during this run.
		if ( is_dir( $dir ) && ! array_diff( scandir( $dir ), array( '.', '..', 'index.html' ) ) ) {
			wp_delete_file( $dir . '/index.html' );
			@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- empty order folder.
		}
	}
	update_post_meta( $post_id, '_tt_drive', $state );
	update_post_meta( $post_id, '_tt_drive_done', 1 );
	tales2tiles_drive_update( array( 'error' => '' ) );
}
add_action( 'tales2tiles_drive_upload', 'tales2tiles_drive_upload_order' );

/**
 * Orders not yet fully in Drive.
 *
 * @return int[] Post IDs.
 */
function tales2tiles_drive_pending() {
	return get_posts(
		array(
			'post_type'   => 'tt_order',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery -- small table, settings page only.
				array(
					'key'     => '_tt_drive_done',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
}

/**
 * Queue every order that isn't in Drive yet, spaced a minute apart.
 */
function tales2tiles_drive_queue_pending() {
	foreach ( tales2tiles_drive_pending() as $i => $post_id ) {
		$state = (array) get_post_meta( $post_id, '_tt_drive', true );
		$state['attempts'] = 0;
		update_post_meta( $post_id, '_tt_drive', $state );
		tales2tiles_drive_queue( $post_id, $i * MINUTE_IN_SECONDS );
	}
}

/* ---------- Owner dashboard: Drive link and files served from Drive ---------- */

add_filter(
	'tales2tiles_order_data',
	static function ( $order, $post ) {
		$state = (array) get_post_meta( $post->ID, '_tt_drive', true );
		if ( empty( $state['folder'] ) ) {
			return $order;
		}
		$order['driveUrl'] = 'https://drive.google.com/drive/folders/' . rawurlencode( $state['folder'] );
		// Files no longer on the website are served from Drive. _wpnonce lets <img> requests use the owner's login (REST cookie auth).
		$local = tales2tiles_drive_local_files( $post->ID );
		$nonce = wp_create_nonce( 'wp_rest' );
		$url   = static function ( $url ) use ( $order, $state, $local, $nonce ) {
			$name = basename( (string) strtok( $url, '?' ) ); // strtok: older status changes saved these proxy links (with an expired _wpnonce) into the order.
			if ( empty( $state['files'][ $name ] ) || file_exists( $local[ $name ] ?? '' ) ) {
				return $url;
			}
			return add_query_arg( '_wpnonce', $nonce, rest_url( 'tales2tiles/v1/orders/' . $order['id'] . '/files/' . $name ) );
		};
		$order['receipt']   = $url( $order['receipt'] );
		$order['photos']    = array_map( $url, (array) $order['photos'] );
		$order['originals'] = array_map( $url, (array) $order['originals'] );
		return $order;
	},
	10,
	2
);

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'tales2tiles/v1',
			'/orders/(?P<id>' . TALES2TILES_ID_REGEX . ')/files/(?P<file>(?:receipt|photo-\d{3}|original-\d{3})\.(?:jpg|png|webp|heic))',
			array(
				'methods'             => 'GET',
				'callback'            => 'tales2tiles_drive_serve_file',
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			)
		);
	}
);

/**
 * GET orders/{id}/files/{file} — stream an order file from Drive (owner only).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_Error|void
 */
function tales2tiles_drive_serve_file( WP_REST_Request $request ) {
	$post    = tales2tiles_find_order( $request['id'] );
	$state   = $post ? (array) get_post_meta( $post->ID, '_tt_drive', true ) : array();
	$file_id = $state['files'][ $request['file'] ] ?? '';
	if ( ! $file_id ) {
		return new WP_Error( 'tales2tiles_not_found', __( 'File not found.', 'tales2tiles' ), array( 'status' => 404 ) );
	}
	$response = tales2tiles_drive_request( 'GET', TALES2TILES_DRIVE_API . '/' . rawurlencode( $file_id ) . '?alt=media', array( 'timeout' => 60 ) );
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'tales2tiles_drive', __( 'Could not load this file from Google Drive.', 'tales2tiles' ), array( 'status' => 502 ) );
	}
	$types = array(
		'jpg'  => 'image/jpeg',
		'png'  => 'image/png',
		'webp' => 'image/webp',
		'heic' => 'image/heic',
	);
	header( 'Content-Type: ' . $types[ pathinfo( $request['file'], PATHINFO_EXTENSION ) ] );
	if ( str_starts_with( $request['file'], 'original-' ) ) {
		header( 'Content-Disposition: attachment; filename="' . $request['id'] . '-' . $request['file'] . '"' ); // Download, keeping the full file.
	}
	header( 'Cache-Control: private, max-age=86400' );
	header( 'X-Content-Type-Options: nosniff' );
	echo wp_remote_retrieve_body( $response ); // phpcs:ignore WordPress.Security.EscapeOutput -- image bytes.
	exit;
}

/* ---------- Settings page and connect flow ---------- */

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'edit.php?post_type=tt_order', __( 'Google Drive', 'tales2tiles' ), __( 'Google Drive', 'tales2tiles' ), 'manage_options', TALES2TILES_DRIVE_PAGE, 'tales2tiles_drive_page' );
	}
);

/**
 * Stop unless the current user is the shop owner and the form nonce is valid.
 *
 * @param string $action Nonce action.
 */
function tales2tiles_drive_check( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Only the shop owner can change Google Drive settings.', 'tales2tiles' ), 403 );
	}
	check_admin_referer( $action );
}

add_action(
	'admin_post_tales2tiles_drive_save',
	static function () {
		tales2tiles_drive_check( 'tales2tiles_drive_save' );
		$settings  = tales2tiles_drive_settings();
		$client_id = sanitize_text_field( wp_unslash( $_POST['client_id'] ?? '' ) );
		$secret    = sanitize_text_field( wp_unslash( $_POST['client_secret'] ?? '' ) );
		$changes   = array(
			'client_id'    => $client_id,
			'delete_local' => isset( $_POST['delete_local'] ) ? '1' : '',
		);
		if ( '' !== $secret ) { // Blank = keep the saved secret.
			$changes['client_secret'] = $secret;
		}
		if ( $client_id !== $settings['client_id'] ) { // A connection belongs to one Client ID.
			$changes['refresh_token'] = '';
			delete_transient( 'tales2tiles_drive_access' );
		}
		tales2tiles_drive_update( $changes );
		wp_safe_redirect( tales2tiles_drive_page_url( 'saved' ) );
		exit;
	}
);

add_action(
	'admin_post_tales2tiles_drive_connect',
	static function () {
		tales2tiles_drive_check( 'tales2tiles_drive_connect' );
		$settings = tales2tiles_drive_settings();
		if ( ! $settings['client_id'] || ! $settings['client_secret'] ) {
			wp_safe_redirect( tales2tiles_drive_page_url( 'missing' ) );
			exit;
		}
		$state = wp_generate_password( 32, false );
		set_transient( 'tales2tiles_drive_state_' . get_current_user_id(), $state, 15 * MINUTE_IN_SECONDS );
		$query = array(
			'client_id'     => $settings['client_id'],
			'redirect_uri'  => tales2tiles_drive_redirect_uri(),
			'response_type' => 'code',
			'scope'         => 'https://www.googleapis.com/auth/drive.file',
			'access_type'   => 'offline', // Get a refresh token so uploads keep working.
			'prompt'        => 'consent',
			'state'         => $state,
		);
		wp_redirect( 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ) ); // phpcs:ignore WordPress.Security.SafeRedirect -- Google sign-in.
		exit;
	}
);

add_action(
	'admin_post_tales2tiles_drive_callback',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Only the shop owner can connect Google Drive.', 'tales2tiles' ), 403 );
		}
		// The state token proves this came from our own "Connect" click (CSRF protection).
		$key   = 'tales2tiles_drive_state_' . get_current_user_id();
		$state = (string) get_transient( $key );
		delete_transient( $key );
		$given = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification -- verified by state.
		if ( ! $state || ! hash_equals( $state, $given ) ) {
			wp_safe_redirect( tales2tiles_drive_page_url( 'expired' ) );
			exit;
		}
		if ( isset( $_GET['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( tales2tiles_drive_page_url( 'denied' ) );
			exit;
		}
		$data = tales2tiles_drive_token_request(
			array(
				'grant_type'   => 'authorization_code',
				'code'         => sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
				'redirect_uri' => tales2tiles_drive_redirect_uri(),
			)
		);
		if ( ! is_wp_error( $data ) && empty( $data['refresh_token'] ) ) {
			$data = new WP_Error( 'tales2tiles_drive', __( 'Google did not give long-term access. Remove "Tales to Tiles website" at myaccount.google.com/permissions and connect again.', 'tales2tiles' ) );
		}
		if ( is_wp_error( $data ) ) {
			tales2tiles_drive_update( array( 'error' => $data->get_error_message() ) );
			wp_safe_redirect( tales2tiles_drive_page_url( 'failed' ) );
			exit;
		}
		set_transient( 'tales2tiles_drive_access', $data['access_token'], max( 60, (int) $data['expires_in'] - 120 ) );
		tales2tiles_drive_update(
			array(
				'refresh_token' => $data['refresh_token'],
				'root'          => '', // New account: make its own folder.
				'error'         => '',
			)
		);
		$about = tales2tiles_drive_json( tales2tiles_drive_request( 'GET', 'https://www.googleapis.com/drive/v3/about?fields=user(emailAddress)' ) );
		tales2tiles_drive_update( array( 'email' => is_wp_error( $about ) ? '' : (string) ( $about['user']['emailAddress'] ?? '' ) ) );
		tales2tiles_drive_queue_pending(); // Earlier orders go to Drive too.
		wp_safe_redirect( tales2tiles_drive_page_url( 'connected' ) );
		exit;
	}
);

add_action(
	'admin_post_tales2tiles_drive_disconnect',
	static function () {
		tales2tiles_drive_check( 'tales2tiles_drive_disconnect' );
		$token = tales2tiles_drive_settings()['refresh_token'];
		if ( $token ) {
			wp_remote_post( 'https://oauth2.googleapis.com/revoke', array( 'body' => array( 'token' => $token ) ) ); // Best effort.
		}
		delete_transient( 'tales2tiles_drive_access' );
		tales2tiles_drive_update(
			array(
				'refresh_token' => '',
				'email'         => '',
			)
		);
		wp_safe_redirect( tales2tiles_drive_page_url( 'disconnected' ) );
		exit;
	}
);

add_action(
	'admin_post_tales2tiles_drive_retry',
	static function () {
		tales2tiles_drive_check( 'tales2tiles_drive_retry' );
		tales2tiles_drive_queue_pending();
		wp_safe_redirect( tales2tiles_drive_page_url( 'retrying' ) );
		exit;
	}
);

/**
 * A small form that posts to admin-post.php.
 *
 * @param string $action  Action name (also the nonce action).
 * @param string $label   Button label.
 * @param string $classes Button classes.
 */
function tales2tiles_drive_button( $action, $label, $classes = 'button' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin:0 8px 8px 0">';
	echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
	wp_nonce_field( $action );
	echo '<button class="' . esc_attr( $classes ) . '">' . esc_html( $label ) . '</button></form>';
}

/**
 * Settings page.
 */
function tales2tiles_drive_page() {
	$settings  = tales2tiles_drive_settings();
	$connected = tales2tiles_drive_connected();
	$uri       = tales2tiles_drive_redirect_uri();
	$messages  = array(
		'saved'        => array( 'success', __( 'Settings saved.', 'tales2tiles' ) ),
		'connected'    => array( 'success', __( 'Google Drive is connected. New order photos will be copied there automatically.', 'tales2tiles' ) ),
		'disconnected' => array( 'success', __( 'Google Drive is disconnected. Nothing was deleted from your Drive.', 'tales2tiles' ) ),
		'retrying'     => array( 'success', __( 'Uploads queued. They run in the background over the next few minutes.', 'tales2tiles' ) ),
		'missing'      => array( 'error', __( 'Paste your Client ID and Client secret (step 5) and save first.', 'tales2tiles' ) ),
		'expired'      => array( 'error', __( 'That sign-in took too long or came from somewhere else. Click "Connect Google Drive" again.', 'tales2tiles' ) ),
		'denied'       => array( 'error', __( 'Google sign-in was cancelled, so nothing was connected.', 'tales2tiles' ) ),
		'failed'       => array( 'error', __( 'Google did not accept the connection. Details are below.', 'tales2tiles' ) ),
	);
	$message   = $messages[ sanitize_key( wp_unslash( $_GET['tt-drive'] ?? '' ) ) ] ?? null; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
	$pending   = $connected ? count( tales2tiles_drive_pending() ) : 0;
	$link      = static fn( $url, $text ) => '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $text ) . '</a>';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Google Drive for order photos', 'tales2tiles' ); ?></h1>
		<?php if ( $message ) : ?>
			<div class="notice notice-<?php echo esc_attr( $message[0] ); ?>"><p><?php echo esc_html( $message[1] ); ?></p></div>
		<?php endif; ?>
		<p style="max-width:760px"><?php esc_html_e( "Save customers' photos and payment receipts in your own Google Drive. Each order gets its own folder inside \"Tales to Tiles orders\" in My Drive. The website can only see the files it puts there, never the rest of your Drive.", 'tales2tiles' ); ?></p>

		<div class="card" style="max-width:760px">
			<h2><?php esc_html_e( 'Status', 'tales2tiles' ); ?></h2>
			<?php if ( $connected ) : ?>
				<p><span class="dashicons dashicons-yes-alt" style="color:#00a32a"></span>
					<?php
					/* translators: %s: Google account email. */
					echo esc_html( $settings['email'] ? sprintf( __( 'Connected to %s.', 'tales2tiles' ), $settings['email'] ) : __( 'Connected.', 'tales2tiles' ) );
					?>
					<?php if ( $settings['root'] ) : ?>
						<?php echo $link( 'https://drive.google.com/drive/folders/' . rawurlencode( $settings['root'] ), __( 'Open the orders folder', 'tales2tiles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in $link. ?>
					<?php endif; ?>
				</p>
				<p>
					<?php
					echo esc_html(
						$pending
							/* translators: %d: number of orders. */
							? sprintf( _n( '%d order is waiting to be copied to Drive.', '%d orders are waiting to be copied to Drive.', $pending, 'tales2tiles' ), $pending )
							: __( 'All orders are in Drive.', 'tales2tiles' )
					);
					?>
				</p>
			<?php else : ?>
				<p><span class="dashicons dashicons-warning" style="color:#dba617"></span> <?php esc_html_e( 'Not connected. Photos are kept on this website only. Follow the steps below to connect.', 'tales2tiles' ); ?></p>
			<?php endif; ?>
			<?php if ( $settings['error'] ) : ?>
				<p style="color:#b32d2e"><strong><?php esc_html_e( 'Last problem:', 'tales2tiles' ); ?></strong> <?php echo esc_html( $settings['error'] ); ?></p>
			<?php endif; ?>
			<p>
				<?php
				if ( $connected ) {
					if ( $pending ) {
						tales2tiles_drive_button( 'tales2tiles_drive_retry', __( 'Retry uploads now', 'tales2tiles' ), 'button button-primary' );
					}
					tales2tiles_drive_button( 'tales2tiles_drive_disconnect', __( 'Disconnect', 'tales2tiles' ) );
				} elseif ( $settings['client_id'] && $settings['client_secret'] ) {
					tales2tiles_drive_button( 'tales2tiles_drive_connect', __( 'Connect Google Drive', 'tales2tiles' ), 'button button-primary button-hero' );
				}
				?>
			</p>
		</div>

		<?php if ( 0 !== strpos( $uri, 'https://' ) ) : ?>
			<div class="notice notice-warning inline" style="max-width:760px"><p><?php esc_html_e( 'Google only connects to secure (https://) websites. Do these steps on your live website, not on a local copy.', 'tales2tiles' ); ?></p></div>
		<?php endif; ?>

		<details class="card" style="max-width:760px" <?php echo $connected ? '' : 'open'; ?>>
			<summary style="cursor:pointer"><h2 style="display:inline"><?php esc_html_e( 'One-time setup (about 10 minutes)', 'tales2tiles' ); ?></h2></summary>
			<p><?php esc_html_e( 'You will create a free, private "app" in Google Cloud that lets this website put files in your Drive. No payment details are needed. Use the Google account whose Drive should hold the photos.', 'tales2tiles' ); ?></p>
			<ol style="line-height:1.7">
				<li>
					<strong><?php esc_html_e( 'Create a project.', 'tales2tiles' ); ?></strong>
					<?php echo $link( 'https://console.cloud.google.com/projectcreate', __( 'Open Google Cloud: New project', 'tales2tiles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>.
					<?php esc_html_e( 'Project name: Tales to Tiles website. Click Create and wait a few seconds until it is ready.', 'tales2tiles' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Turn on Google Drive.', 'tales2tiles' ); ?></strong>
					<?php echo $link( 'https://console.cloud.google.com/apis/library/drive.googleapis.com', __( 'Open the Google Drive API page', 'tales2tiles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>.
					<?php esc_html_e( 'Check that "Tales to Tiles website" is the project shown at the top, then click Enable.', 'tales2tiles' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Describe the app.', 'tales2tiles' ); ?></strong>
					<?php echo $link( 'https://console.cloud.google.com/auth/overview', __( 'Open Google Auth Platform', 'tales2tiles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php esc_html_e( 'and click Get started.', 'tales2tiles' ); ?>
					<ul style="list-style:disc;margin-left:20px">
						<li><?php esc_html_e( 'App name: Tales to Tiles website. User support email: your email. Next.', 'tales2tiles' ); ?></li>
						<li><?php esc_html_e( 'Audience: External. Next.', 'tales2tiles' ); ?></li>
						<li><?php esc_html_e( 'Contact information: your email. Next, tick "I agree", then Create.', 'tales2tiles' ); ?></li>
					</ul>
				</li>
				<li>
					<strong><?php esc_html_e( 'Publish the app (important).', 'tales2tiles' ); ?></strong>
					<?php echo $link( 'https://console.cloud.google.com/auth/audience', __( 'Open Audience', 'tales2tiles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>,
					<?php esc_html_e( 'click "Publish app", then Confirm. If you skip this, Google disconnects the website after 7 days. Google does not need to review it, because the app only asks for the limited "files it creates" permission.', 'tales2tiles' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Create the website key.', 'tales2tiles' ); ?></strong>
					<?php echo $link( 'https://console.cloud.google.com/auth/clients/create', __( 'Open Create OAuth client', 'tales2tiles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>.
					<ul style="list-style:disc;margin-left:20px">
						<li><?php esc_html_e( 'Application type: Web application. Name: Tales to Tiles website.', 'tales2tiles' ); ?></li>
						<li>
							<?php esc_html_e( 'Under "Authorized redirect URIs" click Add URI and paste exactly:', 'tales2tiles' ); ?><br>
							<input type="text" readonly class="large-text code" value="<?php echo esc_attr( $uri ); ?>" onclick="this.select()" style="max-width:560px">
							<button type="button" class="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value);this.textContent='<?php echo esc_js( __( 'Copied', 'tales2tiles' ) ); ?>'"><?php esc_html_e( 'Copy', 'tales2tiles' ); ?></button>
						</li>
						<li><?php esc_html_e( 'Click Create. A window shows your Client ID and Client secret. Keep it open for the next step.', 'tales2tiles' ); ?></li>
					</ul>
				</li>
				<li>
					<strong><?php esc_html_e( 'Paste the key here and save.', 'tales2tiles' ); ?></strong>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="tales2tiles_drive_save">
						<?php wp_nonce_field( 'tales2tiles_drive_save' ); ?>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="tt-client-id"><?php esc_html_e( 'Client ID', 'tales2tiles' ); ?></label></th>
								<td><input type="text" class="large-text code" id="tt-client-id" name="client_id" value="<?php echo esc_attr( $settings['client_id'] ); ?>" placeholder="1234567890-abc123.apps.googleusercontent.com" autocomplete="off"></td>
							</tr>
							<tr>
								<th scope="row"><label for="tt-client-secret"><?php esc_html_e( 'Client secret', 'tales2tiles' ); ?></label></th>
								<td>
									<input type="password" class="large-text code" id="tt-client-secret" name="client_secret" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $settings['client_secret'] ? __( 'Saved. Leave empty to keep it.', 'tales2tiles' ) : 'GOCSPX-…' ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Website copies', 'tales2tiles' ); ?></th>
								<td>
									<label><input type="checkbox" name="delete_local" value="1" <?php checked( $settings['delete_local'], '1' ); ?>> <?php esc_html_e( 'Delete photos and receipts from this website once they are safely in Google Drive', 'tales2tiles' ); ?></label>
									<p class="description"><?php esc_html_e( 'Saves hosting space. The orders page still shows them (loaded from Drive) while Drive is connected. Applies to uploads from now on.', 'tales2tiles' ); ?></p>
								</td>
							</tr>
						</table>
						<?php submit_button( __( 'Save', 'tales2tiles' ), 'secondary', 'submit', false ); ?>
					</form>
				</li>
				<li>
					<strong><?php esc_html_e( 'Connect.', 'tales2tiles' ); ?></strong>
					<?php esc_html_e( 'Click "Connect Google Drive" in the Status box above, choose your Google account and click Continue. If Google says "Google hasn\'t verified this app", click Advanced, then "Go to Tales to Tiles website". It is your own app, so this is safe.', 'tales2tiles' ); ?>
				</li>
			</ol>
			<p><strong><?php esc_html_e( 'Good to know', 'tales2tiles' ); ?></strong></p>
			<ul style="list-style:disc;margin-left:20px">
				<li><?php esc_html_e( 'Uploads run in the background a minute or so after each order. They need visitors (or you) to open the website now and then; on very quiet sites, ask your host to set up a "real cron job" for WordPress.', 'tales2tiles' ); ?></li>
				<li><?php esc_html_e( 'Orders placed before you connected are copied too.', 'tales2tiles' ); ?></li>
				<li><?php esc_html_e( 'Disconnecting never deletes anything from your Drive. Photos that were already removed from the website stay in Drive but won\'t show on the orders page until you connect again.', 'tales2tiles' ); ?></li>
				<li><?php esc_html_e( 'Photos use your Google Drive storage (about 0.3 MB per photo).', 'tales2tiles' ); ?></li>
			</ul>
		</details>
	</div>
	<?php
}
