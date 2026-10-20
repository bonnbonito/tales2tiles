<?php
/**
 * Orders: storage (tt_order post type) and REST API used by assets/js/app.js.
 *
 * Routes (namespace tales2tiles/v1):
 *   POST orders            public  multipart: items[n][product|color|qty|copies|photos][] (photos = data URLs), customer[...], payMethod, payRef, coupon, receipt (file)
 *                                  qty = designs (photos per item = per × qty); copies = how many times the set is printed.
 *   GET  orders            owner   list all orders
 *   GET  orders/{id}       owner   one order
 *   POST orders/{id}       owner   JSON {status}
 *   POST orders/{id}/originals  public with the order's upload key: one full-quality original per request (key, index, file)
 *   GET  track/{id}        public  status only (no customer details)
 *   GET  coupon/{code}     public  a discount code's rule (throttled), for the cart preview
 *
 * Order data lives in post meta `_tt_order`; images in uploads/tt-orders/<id>-<random>/
 * (and, when connected, copied to Google Drive by inc/drive.php).
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

const TALES2TILES_STATUSES = array( 'pending', 'approved', 'processing', 'completed', 'rejected' );
const TALES2TILES_ID_REGEX = 'TT-\d{4}-\d{4}-\d{4,}';
const TALES2TILES_MAX_ITEMS  = 30;  // Cart lines per order.
const TALES2TILES_MAX_PHOTOS = 200; // Print photos per order; all arrive in one request (see AGENTS.md: post_max_size).

/**
 * Register the order post type (visible in wp-admin as a backup view; created only via the API).
 */
function tales2tiles_register_order_type() {
	register_post_type(
		'tt_order',
		array(
			'labels'       => array(
				'name'          => __( 'Tile orders', 'tales2tiles' ),
				'singular_name' => __( 'Tile order', 'tales2tiles' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-format-gallery',
			'supports'     => array( 'title' ),
			'capabilities' => array_merge(
				array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'delete_posts', 'publish_posts', 'read_private_posts' ), 'manage_options' ), // Customer data: shop owners (admins) only.
				array( 'create_posts' => 'do_not_allow' ) // Orders are created only through the API.
			),
			'map_meta_cap' => false,
		)
	);
}
add_action( 'init', 'tales2tiles_register_order_type' );

/**
 * Register REST routes.
 */
function tales2tiles_register_routes() {
	$ns    = 'tales2tiles/v1';
	$owner = static fn() => current_user_can( 'manage_options' );

	register_rest_route(
		$ns,
		'/orders',
		array(
			array(
				'methods'             => 'POST',
				'callback'            => 'tales2tiles_rest_create_order',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'GET',
				'callback'            => 'tales2tiles_rest_list_orders',
				'permission_callback' => $owner,
			),
		)
	);
	register_rest_route(
		$ns,
		'/orders/(?P<id>' . TALES2TILES_ID_REGEX . ')',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'tales2tiles_rest_get_order',
				'permission_callback' => $owner,
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'tales2tiles_rest_update_order',
				'permission_callback' => $owner,
			),
		)
	);
	register_rest_route(
		$ns,
		'/orders/(?P<id>' . TALES2TILES_ID_REGEX . ')/originals',
		array(
			'methods'             => 'POST',
			'callback'            => 'tales2tiles_rest_upload_original',
			'permission_callback' => '__return_true', // Checked by the order's secret upload key.
		)
	);
	register_rest_route(
		$ns,
		'/coupon/(?P<code>[A-Za-z0-9_-]{1,30})',
		array(
			'methods'             => 'GET',
			'callback'            => 'tales2tiles_rest_check_coupon',
			'permission_callback' => '__return_true',
		)
	);
	register_rest_route(
		$ns,
		'/track/(?P<id>' . TALES2TILES_ID_REGEX . ')',
		array(
			'methods'             => 'GET',
			'callback'            => 'tales2tiles_rest_track_order',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'tales2tiles_register_routes' );

/**
 * Customer-facing validation error.
 *
 * @param string $message Message shown to the customer.
 * @return WP_Error
 */
function tales2tiles_error( $message ) {
	return new WP_Error( 'tales2tiles_invalid', $message, array( 'status' => 400 ) );
}

/**
 * Find an order post by its order number.
 *
 * @param string $id Order number, e.g. TT-2026-0924-0001.
 * @return WP_Post|null
 */
function tales2tiles_find_order( $id ) {
	$posts = get_posts(
		array(
			'post_type'      => 'tt_order',
			'name'           => sanitize_title( $id ),
			'post_status'    => 'any',
			'posts_per_page' => 1,
		)
	);
	return $posts ? $posts[0] : null;
}

/**
 * Order data for a post, as the API returns it (inc/drive.php adds the Drive link via the filter).
 *
 * @param WP_Post $post Order post.
 * @return array
 */
function tales2tiles_order_data( $post ) {
	$order = (array) get_post_meta( $post->ID, '_tt_order', true );
	if ( empty( $order['items'] ) && isset( $order['product'] ) ) { // Orders from before the cart: one item.
		$order['items'] = array(
			array(
				'product'    => $order['product'],
				'name'       => $order['productName'],
				'color'      => $order['color'] ?? '',
				'qty'        => $order['qty'],
				'unit'       => $order['unit'] ?? 0,
				'subtotal'   => $order['subtotal'],
				'photoFrom'  => 0,
				'photoCount' => count( (array) $order['photos'] ),
				'print'      => $order['print'] ?? array(),
			),
		);
	}
	$order['originals'] = tales2tiles_order_originals( $post->ID );
	return apply_filters( 'tales2tiles_order_data', $order, $post );
}

/**
 * Full-quality originals received so far: photo index => URL.
 * One meta row per file (add_post_meta), so uploads never overwrite each other or `_tt_order`.
 *
 * @param int $post_id Order post ID.
 * @return array
 */
function tales2tiles_order_originals( $post_id ) {
	$originals = array();
	foreach ( get_post_meta( $post_id, '_tt_original' ) as $row ) {
		$originals[ (int) $row[0] ] = $row[1];
	}
	ksort( $originals );
	return $originals;
}

/**
 * Largest original photo the server accepts, in bytes.
 *
 * @return int
 */
function tales2tiles_original_max() {
	return (int) min( 30 * MB_IN_BYTES, wp_max_upload_size() );
}

/**
 * Check an uploaded original is a real JPEG/PNG/WebP/HEIC photo under the size limit.
 *
 * @param array $file One $_FILES entry.
 * @return string|false Extension, or false.
 */
function tales2tiles_original_ext( $file ) {
	if ( empty( $file['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) || $file['size'] > tales2tiles_original_max() ) {
		return false;
	}
	$info = @getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Non-images return false.
	$map  = array(
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG  => 'png',
		IMAGETYPE_WEBP => 'webp',
	);
	if ( $info && isset( $map[ $info[2] ] ) ) {
		return $map[ $info[2] ];
	}
	// iPhone HEIC photos: getimagesize() can't read them, so check the file signature.
	$head = (string) file_get_contents( $file['tmp_name'], false, null, 0, 12 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return ( 'ftyp' === substr( $head, 4, 4 ) && in_array( substr( $head, 8, 4 ), array( 'heic', 'heix', 'mif1', 'msf1', 'hevc' ), true ) ) ? 'heic' : false;
}

/**
 * Check an uploaded file is a real JPEG/PNG under a size limit.
 *
 * @param array $file    One $_FILES entry.
 * @param int   $max_mb  Size limit.
 * @return string|false  'jpg'/'png', or false.
 */
function tales2tiles_image_ext( $file, $max_mb ) {
	if ( empty( $file['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return false;
	}
	if ( $file['size'] > $max_mb * MB_IN_BYTES ) {
		return false;
	}
	$info = @getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Non-images return false.
	$map  = array(
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG  => 'png',
	);
	return ( $info && isset( $map[ $info[2] ] ) ) ? $map[ $info[2] ] : false;
}

/**
 * Decode a JPEG/PNG data URL and check it really is an image.
 *
 * @param string $data_url data:image/...;base64,...
 * @param int    $max_mb   Size limit.
 * @return array|false     ['bytes' => string, 'ext' => 'jpg'|'png'], or false.
 */
function tales2tiles_decode_image( $data_url, $max_mb ) {
	if ( ! preg_match( '#^data:image/(?:jpeg|png);base64,#', $data_url, $m ) ) {
		return false;
	}
	$bytes = base64_decode( substr( $data_url, strlen( $m[0] ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- decoding uploaded image data.
	if ( ! $bytes || strlen( $bytes ) > $max_mb * MB_IN_BYTES ) {
		return false;
	}
	$info = getimagesizefromstring( $bytes );
	$map  = array(
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG  => 'png',
	);
	return ( $info && isset( $map[ $info[2] ] ) ) ? array( 'bytes' => $bytes, 'ext' => $map[ $info[2] ] ) : false;
}

/**
 * A discount code from Pages → Order, matched without regard to case.
 *
 * @param string $code Code typed by the customer.
 * @return array|null array( 'code', 'percent', 'amount', 'min' ), or null if there's no such code.
 */
function tales2tiles_coupon( $code ) {
	$code = strtoupper( trim( (string) $code ) );
	foreach ( (array) tales2tiles_field( 'order', 'coupons' ) as $row ) {
		if ( '' !== $code && strtoupper( trim( (string) $row[0] ) ) === $code ) {
			return array(
				'code'    => $code,
				'percent' => min( 100, (float) ( $row[1] ?? 0 ) ),
				'amount'  => (float) ( $row[2] ?? 0 ),
				'min'     => (float) ( $row[3] ?? 0 ),
			);
		}
	}
	return null;
}

/**
 * Discount for a subtotal (same formula as app.js couponDiscount()).
 *
 * @param array $coupon   From tales2tiles_coupon().
 * @param float $subtotal Items subtotal.
 * @return float
 */
function tales2tiles_coupon_discount( $coupon, $subtotal ) {
	if ( $subtotal < $coupon['min'] ) {
		return 0.0;
	}
	return min( $subtotal, round( $subtotal * $coupon['percent'] / 100 + $coupon['amount'], 2 ) );
}

/**
 * GET /coupon/{code} — check a code from the cart. Throttled so codes can't be guessed by brute force.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function tales2tiles_rest_check_coupon( WP_REST_Request $request ) {
	$throttle = 'tt_coupon_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- hashed, never output.
	$tries    = (int) get_transient( $throttle );
	if ( $tries >= 20 ) {
		return new WP_Error( 'tales2tiles_busy', __( 'Too many tries. Please wait an hour and try again.', 'tales2tiles' ), array( 'status' => 429 ) );
	}
	set_transient( $throttle, $tries + 1, HOUR_IN_SECONDS );
	$coupon = tales2tiles_coupon( $request['code'] );
	return $coupon ? rest_ensure_response( $coupon ) : tales2tiles_error( __( 'That code isn\'t valid.', 'tales2tiles' ) );
}

/**
 * Next order number. Atomic in MySQL, so simultaneous orders never share a number.
 *
 * @return int
 */
function tales2tiles_next_order_number() {
	global $wpdb;
	add_option( 'tales2tiles_order_counter', 0, '', false ); // No-op once it exists.
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = LAST_INSERT_ID( option_value + 1 ) WHERE option_name = %s", 'tales2tiles_order_counter' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	wp_cache_delete( 'tales2tiles_order_counter', 'options' );
	return (int) $wpdb->get_var( 'SELECT LAST_INSERT_ID()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * POST /orders — validate, store images, create the order.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function tales2tiles_rest_create_order( WP_REST_Request $request ) {
	// ponytail: per-IP throttle against junk orders filling the disk; behind a proxy/CDN every visitor may share one IP, so raise the limit or key on the forwarded IP there.
	$throttle = 'tt_orders_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- hashed, never output.
	$recent   = (int) get_transient( $throttle );
	if ( $recent >= 10 ) {
		return new WP_Error( 'tales2tiles_busy', __( 'Too many orders from your connection. Please try again in an hour or message us.', 'tales2tiles' ), array( 'status' => 429 ) );
	}

	$config   = tales2tiles_config();
	$products = $config['products'];

	// Cart items. Everything is checked before any photo is decoded or written.
	$raw_items = array_values( (array) $request->get_param( 'items' ) );
	if ( ! $raw_items || count( $raw_items ) > TALES2TILES_MAX_ITEMS ) {
		/* translators: %d: maximum number of items. */
		return tales2tiles_error( $raw_items ? sprintf( __( 'One order can have up to %d items.', 'tales2tiles' ), TALES2TILES_MAX_ITEMS ) : __( 'Your cart is empty.', 'tales2tiles' ) );
	}
	$items        = array();
	$product_qty  = array(); // Pieces per product across the cart: price breaks and minimums use the total.
	$photo_count  = 0;
	foreach ( $raw_items as $raw_item ) {
		$raw_item = (array) $raw_item;
		$key      = (string) ( $raw_item['product'] ?? '' );
		if ( ! isset( $products[ $key ] ) ) {
			return tales2tiles_error( __( 'A product in your cart is no longer available. Remove it and try again.', 'tales2tiles' ) );
		}
		$product = $products[ $key ];
		$qty     = (int) ( $raw_item['qty'] ?? 0 );
		if ( $qty < 1 || $qty > $product['max'] ) {
			/* translators: 1: product name, 2: maximum quantity. */
			return tales2tiles_error( sprintf( __( '%1$s: choose a quantity from 1 to %2$d.', 'tales2tiles' ), $product['name'], $product['max'] ) );
		}
		$color = '';
		if ( ! empty( $product['colors'] ) ) {
			$color = (string) ( $raw_item['color'] ?? '' );
			if ( ! in_array( $color, $product['colors'], true ) ) {
				/* translators: %s: product name. */
				return tales2tiles_error( sprintf( __( '%s: choose a color.', 'tales2tiles' ), $product['name'] ) );
			}
		}
		$copies = (int) ( $raw_item['copies'] ?? 1 );
		if ( $copies < 1 || $copies > 20 ) {
			/* translators: %s: product name. */
			return tales2tiles_error( sprintf( __( '%s: choose 1 to 20 copies.', 'tales2tiles' ), $product['name'] ) );
		}
		if ( ! $product['per'] ) { // No photos: copies are just more of the same.
			$qty   *= $copies;
			$copies = 1;
		}
		$photos = array_values( (array) ( $raw_item['photos'] ?? array() ) );
		$needed = $product['per'] * $qty;
		if ( count( $photos ) !== $needed ) {
			/* translators: 1: product name, 2: number of photos. */
			return tales2tiles_error( sprintf( __( '%1$s needs %2$d photos.', 'tales2tiles' ), $product['name'], $needed ) );
		}
		$photo_count        += $needed;
		$product_qty[ $key ] = ( $product_qty[ $key ] ?? 0 ) + $qty * $copies; // Pieces: price breaks and minimums count every printed piece.
		$feature             = ! empty( $raw_item['feature'] ); // "I agree to have my order featured on social media".
		$items[]             = compact( 'key', 'product', 'qty', 'copies', 'color', 'photos', 'feature' );
	}
	foreach ( $product_qty as $key => $qty ) {
		if ( $qty < $products[ $key ]['min'] ) {
			/* translators: 1: product name, 2: minimum quantity. */
			return tales2tiles_error( sprintf( __( '%1$s: the minimum order is %2$d pieces.', 'tales2tiles' ), $products[ $key ]['name'], $products[ $key ]['min'] ) );
		}
	}
	if ( $photo_count > TALES2TILES_MAX_PHOTOS ) {
		/* translators: %d: maximum number of photos. */
		return tales2tiles_error( sprintf( __( 'One order can have up to %d photos. Please split it into two orders.', 'tales2tiles' ), TALES2TILES_MAX_PHOTOS ) );
	}

	$raw      = (array) $request->get_param( 'customer' );
	$customer = array();
	foreach ( array( 'name', 'phone', 'email', 'address', 'city', 'province', 'postal' ) as $field ) {
		$customer[ $field ] = sanitize_text_field( $raw[ $field ] ?? '' );
	}
	$customer['notes'] = sanitize_textarea_field( $raw['notes'] ?? '' );

	$valid = mb_strlen( $customer['name'] ) >= 2
		&& preg_match( '/^(09|\+639)\d{9}$/', preg_replace( '/[\s-]/', '', $customer['phone'] ) )
		&& ( '' === $customer['email'] || is_email( $customer['email'] ) )
		&& mb_strlen( $customer['address'] ) >= 6
		&& mb_strlen( $customer['city'] ) >= 2
		&& mb_strlen( $customer['province'] ) >= 2
		&& preg_match( '/^\d{4}$/', $customer['postal'] );
	if ( ! $valid ) {
		return tales2tiles_error( __( 'Check your contact and address details.', 'tales2tiles' ) );
	}

	$pay_method = (string) $request->get_param( 'payMethod' );
	if ( ! in_array( $pay_method, array( 'GCash', 'BPI' ), true ) ) {
		return tales2tiles_error( __( 'Choose how you paid.', 'tales2tiles' ) );
	}

	// Validate every file before writing anything to disk.
	$files       = $request->get_file_params();
	$receipt     = $files['receipt'] ?? array();
	$receipt_ext = tales2tiles_image_ext( $receipt, 10 );
	if ( ! $receipt_ext ) {
		return tales2tiles_error( __( 'Upload your payment receipt as a JPG or PNG under 10 MB.', 'tales2tiles' ) );
	}

	// Photos arrive as JPEG/PNG data URLs (text fields), which sidesteps PHP's max_file_uploads limit.
	// One flat list for the whole order (photo-001… on disk); each item remembers its slice.
	$photos = array();
	foreach ( $items as $i => $item ) {
		$items[ $i ]['photoFrom'] = count( $photos );
		foreach ( $item['photos'] as $data_url ) {
			$photos[] = tales2tiles_decode_image( (string) $data_url, 8 );
		}
	}
	if ( in_array( false, $photos, true ) ) {
		return tales2tiles_error( __( 'One of your photos could not be read. Please try again.', 'tales2tiles' ) );
	}

	$order_id = 'TT-' . wp_date( 'Y-md' ) . '-' . sprintf( '%04d', tales2tiles_next_order_number() );

	// Random suffix keeps customer photos from being guessable by order number.
	$uploads = wp_upload_dir();
	$folder  = 'tt-orders/' . $order_id . '-' . wp_generate_password( 16, false );
	$dir     = trailingslashit( $uploads['basedir'] ) . $folder;
	$url     = trailingslashit( $uploads['baseurl'] ) . $folder;
	if ( ! wp_mkdir_p( $dir ) ) {
		return new WP_Error( 'tales2tiles_storage', __( 'We could not save your order. Please try again later.', 'tales2tiles' ), array( 'status' => 500 ) );
	}
	// Block directory listing of the order folder and of tt-orders/ itself (which would reveal every folder's random name).
	file_put_contents( $dir . '/index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	file_put_contents( dirname( $dir ) . '/index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	$saved = array();
	$move  = static function ( $tmp, $name ) use ( $dir, $url, &$saved ) {
		if ( ! move_uploaded_file( $tmp, $dir . '/' . $name ) ) {
			return false;
		}
		$saved[] = $dir . '/' . $name;
		return $url . '/' . $name;
	};

	$receipt_url = $move( $receipt['tmp_name'], 'receipt.' . $receipt_ext );
	$photo_urls  = array();
	foreach ( $photos as $i => $photo ) {
		$name = sprintf( 'photo-%03d.%s', $i + 1, $photo['ext'] );
		if ( false === file_put_contents( $dir . '/' . $name, $photo['bytes'] ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			$photo_urls[] = false;
			continue;
		}
		$saved[]      = $dir . '/' . $name;
		$photo_urls[] = $url . '/' . $name;
	}
	if ( ! $receipt_url || in_array( false, $photo_urls, true ) ) {
		array_map( 'wp_delete_file', $saved );
		return new WP_Error( 'tales2tiles_storage', __( 'We could not save your photos. Please try again.', 'tales2tiles' ), array( 'status' => 500 ) );
	}

	$lines    = array();
	$subtotal = 0;
	foreach ( $items as $item ) {
		$unit      = tales2tiles_unit_price( $item['product'], $product_qty[ $item['key'] ] );
		$subtotal += $unit * $item['qty'] * $item['copies'];
		$lines[]   = array(
			'product'    => $item['key'],
			'name'       => $item['product']['name'] . ( $item['color'] ? " ({$item['color']})" : '' ),
			'color'      => $item['color'],
			'qty'        => $item['qty'],
			'copies'     => $item['copies'],
			'feature'    => $item['feature'],
			'unit'       => $unit,
			'subtotal'   => $unit * $item['qty'] * $item['copies'],
			'photoFrom'  => $item['photoFrom'], // This item's photos: order photos[photoFrom … photoFrom + photoCount - 1].
			'photoCount' => count( $item['photos'] ),
			'print'      => array_intersect_key( $item['product'], array_flip( array( 'mm', 'circle', 'pin', 'orient', 'sides' ) ) ), // Kept so print layouts survive product edits.
		);
	}
	$coupon   = null;
	$discount = 0.0;
	if ( '' !== trim( (string) $request->get_param( 'coupon' ) ) ) {
		$coupon = tales2tiles_coupon( $request->get_param( 'coupon' ) );
		if ( ! $coupon ) {
			array_map( 'wp_delete_file', $saved );
			return tales2tiles_error( __( 'That discount code isn\'t valid any more. Remove it from your cart and try again.', 'tales2tiles' ) );
		}
		$discount = tales2tiles_coupon_discount( $coupon, $subtotal );
	}
	$shipping = $config['shop']['shippingFee'];
	$now      = time() * 1000;

	$order = array(
		'id'          => $order_id,
		'createdAt'   => $now,
		'status'      => 'pending',
		'items'       => $lines,
		'productName' => implode( ', ', array_map( static fn( $line ) => $line['name'] . ' × ' . $line['qty'] . ( $line['copies'] > 1 ? ' (' . $line['copies'] . ' copies)' : '' ), $lines ) ), // Summary for lists and tracking.
		'qty'         => array_sum( array_map( static fn( $line ) => $line['qty'] * $line['copies'], $lines ) ),
		'subtotal'    => $subtotal,
		'coupon'      => $coupon ? $coupon['code'] : '',
		'discount'    => $discount,
		'shipping'    => $shipping,
		'total'       => $subtotal - $discount + (float) $shipping,
		'customer'    => $customer,
		'payMethod'   => $pay_method,
		'payRef'      => sanitize_text_field( (string) $request->get_param( 'payRef' ) ),
		'receipt'     => $receipt_url,
		'photos'      => $photo_urls,
		'history'     => array(
			array(
				'status' => 'pending',
				'at'     => $now,
			),
		),
	);

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'tt_order',
			'post_status' => 'publish',
			'post_title'  => $order_id . ' — ' . $customer['name'],
			'post_name'   => sanitize_title( $order_id ),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		array_map( 'wp_delete_file', $saved );
		return new WP_Error( 'tales2tiles_storage', __( 'We could not save your order. Please try again later.', 'tales2tiles' ), array( 'status' => 500 ) );
	}
	update_post_meta( $post_id, '_tt_order', $order );
	set_transient( $throttle, $recent + 1, HOUR_IN_SECONDS );
	do_action( 'tales2tiles_order_created', $post_id, $order ); // inc/drive.php queues the Google Drive upload.

	// Secret key letting this browser send the full-quality originals afterwards (stored hashed, valid 1 day).
	$upload_key = wp_generate_password( 32, false );
	update_post_meta( $post_id, '_tt_upload_key', array( hash( 'sha256', $upload_key ), time() + DAY_IN_SECONDS ) );

	return new WP_REST_Response( $order + array( 'uploadKey' => $upload_key ), 201 );
}

/**
 * POST /orders/{id}/originals — one full-quality original photo, sent after the order.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function tales2tiles_rest_upload_original( WP_REST_Request $request ) {
	$post = tales2tiles_find_order( $request['id'] );
	$key  = $post ? (array) get_post_meta( $post->ID, '_tt_upload_key', true ) : array();
	if ( ! $post || 2 !== count( $key ) || time() > $key[1] || ! hash_equals( (string) $key[0], hash( 'sha256', (string) $request->get_param( 'key' ) ) ) ) {
		return new WP_Error( 'tales2tiles_forbidden', __( 'This upload link has expired.', 'tales2tiles' ), array( 'status' => 403 ) );
	}
	$order = (array) get_post_meta( $post->ID, '_tt_order', true );
	$index = (int) $request->get_param( 'index' );
	if ( $index < 0 || $index >= count( $order['photos'] ) || isset( tales2tiles_order_originals( $post->ID )[ $index ] ) ) {
		return tales2tiles_error( __( 'That photo was already sent.', 'tales2tiles' ) );
	}
	$file = $request->get_file_params()['file'] ?? array();
	$ext  = tales2tiles_original_ext( $file );
	if ( ! $ext ) {
		/* translators: %d: size limit in MB. */
		return tales2tiles_error( sprintf( __( 'Send a JPG, PNG, WebP or HEIC photo under %d MB.', 'tales2tiles' ), tales2tiles_original_max() / MB_IN_BYTES ) );
	}

	// Same folder as the order's other files (recreated if Drive storage already cleared it).
	$uploads = wp_upload_dir();
	$folder  = substr( dirname( $order['receipt'] ), strlen( $uploads['baseurl'] ) );
	$dir     = $uploads['basedir'] . $folder;
	$name    = sprintf( 'original-%03d.%s', $index + 1, $ext );
	if ( ! wp_mkdir_p( $dir ) || ! move_uploaded_file( $file['tmp_name'], $dir . '/' . $name ) ) {
		return new WP_Error( 'tales2tiles_storage', __( 'We could not save this photo. Please try again.', 'tales2tiles' ), array( 'status' => 500 ) );
	}
	if ( ! file_exists( $dir . '/index.html' ) ) {
		file_put_contents( $dir . '/index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- Blocks directory listing.
	}
	add_post_meta( $post->ID, '_tt_original', array( $index, $uploads['baseurl'] . $folder . '/' . $name ) );
	do_action( 'tales2tiles_original_uploaded', $post->ID ); // inc/drive.php copies it to Drive.

	return rest_ensure_response( array( 'index' => $index ) );
}

/**
 * GET /orders — newest first.
 *
 * @return WP_REST_Response
 */
function tales2tiles_rest_list_orders() {
	// ponytail: loads every order at once; add pagination if this grows into the thousands.
	$posts = get_posts(
		array(
			'post_type'      => 'tt_order',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);
	return rest_ensure_response( array_map( 'tales2tiles_order_data', $posts ) );
}

/**
 * GET /orders/{id}.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function tales2tiles_rest_get_order( WP_REST_Request $request ) {
	$post = tales2tiles_find_order( $request['id'] );
	return $post ? rest_ensure_response( tales2tiles_order_data( $post ) ) : new WP_Error( 'tales2tiles_not_found', __( 'Order not found.', 'tales2tiles' ), array( 'status' => 404 ) );
}

/**
 * POST /orders/{id} — change status.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function tales2tiles_rest_update_order( WP_REST_Request $request ) {
	$post   = tales2tiles_find_order( $request['id'] );
	$status = (string) $request->get_param( 'status' );
	if ( ! $post ) {
		return new WP_Error( 'tales2tiles_not_found', __( 'Order not found.', 'tales2tiles' ), array( 'status' => 404 ) );
	}
	if ( ! in_array( $status, TALES2TILES_STATUSES, true ) ) {
		return tales2tiles_error( __( 'Unknown status.', 'tales2tiles' ) );
	}
	$order              = tales2tiles_order_data( $post );
	$order['status']    = $status;
	$order['history'][] = array(
		'status' => $status,
		'at'     => time() * 1000,
	);
	update_post_meta( $post->ID, '_tt_order', $order );
	return rest_ensure_response( $order );
}

/**
 * GET /track/{id} — public status lookup. Returns no customer details.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function tales2tiles_rest_track_order( WP_REST_Request $request ) {
	$post = tales2tiles_find_order( $request['id'] );
	if ( ! $post ) {
		return new WP_Error( 'tales2tiles_not_found', __( 'Order not found.', 'tales2tiles' ), array( 'status' => 404 ) );
	}
	$order = tales2tiles_order_data( $post );
	return rest_ensure_response( array_intersect_key( $order, array_flip( array( 'id', 'status', 'productName', 'qty', 'total' ) ) ) );
}
