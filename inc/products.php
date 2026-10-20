<?php
/**
 * Products: the "Products" menu in wp-admin (post type tt_product, classic editor).
 *
 * Add, remove, reorder (the "Order" number in the Attributes box) or draft products there.
 * Only published products are sold. Each product has its own page at /products/<slug>/
 * (single-tt_product.php); the slug can be edited like any permalink.
 * The product KEY (saved on orders, used in ?product=<key>) is locked in meta `_tt_key`
 * the first time a product is published, so changing the slug later never breaks orders.
 * Each order also saves its own print settings, so editing or deleting a product
 * never breaks past orders.
 *
 * Fields: tales2tiles_product_fields() (format in inc/fields.php).
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the product post type.
 */
function tales2tiles_register_product_type() {
	register_post_type(
		'tt_product',
		array(
			'labels'       => array(
				'name'          => __( 'Products', 'tales2tiles' ),
				'singular_name' => __( 'Product', 'tales2tiles' ),
				'add_new_item'  => __( 'Add new product', 'tales2tiles' ),
				'edit_item'     => __( 'Edit product', 'tales2tiles' ),
				'all_items'     => __( 'All products', 'tales2tiles' ),
			),
			'public'       => true, // Product pages: /products/<slug>/.
			'rewrite'      => array(
				'slug'       => 'products',
				'with_front' => false,
			),
			'has_archive'  => false, // The Order page lists the products.
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-products',
			'supports'     => array( 'title', 'page-attributes' ),
			'capabilities' => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'delete_posts', 'publish_posts', 'read_private_posts', 'create_posts' ), 'manage_options' ), // Shop owners (admins) only.
			'map_meta_cap' => false,
		)
	);
}
add_action( 'init', 'tales2tiles_register_product_type' );

/**
 * A product's key: locked in `_tt_key` at first publish, the slug before that.
 *
 * @param WP_Post $post Product.
 * @return string
 */
function tales2tiles_product_key( $post ) {
	$key = (string) get_post_meta( $post->ID, '_tt_key', true );
	return '' !== $key ? $key : $post->post_name;
}

/**
 * Lock the key of every published product that doesn't have one yet (current slug).
 */
function tales2tiles_lock_product_keys() {
	foreach ( get_posts( array( 'post_type' => 'tt_product', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $post ) {
		if ( '' === (string) get_post_meta( $post->ID, '_tt_key', true ) && '' !== $post->post_name ) {
			add_post_meta( $post->ID, '_tt_key', $post->post_name, true );
		}
	}
}
/**
 * One time: give the starting products readable addresses (e.g. /products/2-5-x-2-5-photo-magnet/)
 * instead of their short keys. Keys are locked first, so orders and old links keep working.
 */
function tales2tiles_pretty_product_slugs() {
	foreach ( array_keys( tales2tiles_default_products() ) as $key ) {
		$post = get_page_by_path( $key, OBJECT, 'tt_product' );
		if ( $post && tales2tiles_product_key( $post ) === $key ) {
			$slug = sanitize_title( str_replace( array( '″', '×' ), array( '', 'x' ), $post->post_title ) );
			wp_update_post(
				array(
					'ID'        => $post->ID,
					'post_name' => wp_unique_post_slug( $slug, $post->ID, 'publish', 'tt_product', 0 ),
				)
			);
		}
	}
}

add_action(
	'save_post_tt_product',
	static function ( $post_id, $post ) {
		if ( 'publish' === $post->post_status && '' !== $post->post_name && '' === (string) get_post_meta( $post_id, '_tt_key', true ) ) {
			add_post_meta( $post_id, '_tt_key', $post->post_name, true ); // Locked from now on.
		}
	},
	10,
	2
);

/**
 * Icon choices for badges and shared product-page sections (tales2tiles_icon names).
 *
 * @return array
 */
function tales2tiles_icon_choices() {
	return array(
		'check'   => __( 'Check mark', 'tales2tiles' ),
		'heart'   => __( 'Heart', 'tales2tiles' ),
		'star'    => __( 'Star', 'tales2tiles' ),
		'shield'  => __( 'Shield', 'tales2tiles' ),
		'truck'   => __( 'Truck', 'tales2tiles' ),
		'box'     => __( 'Box', 'tales2tiles' ),
		'gift'    => __( 'Gift', 'tales2tiles' ),
		'upload'  => __( 'Upload', 'tales2tiles' ),
		'cart'    => __( 'Cart', 'tales2tiles' ),
		'pin'     => __( 'Location pin', 'tales2tiles' ),
		'sparkle' => __( 'Sparkle', 'tales2tiles' ),
		'clock'   => __( 'Clock', 'tales2tiles' ),
		'wallet'  => __( 'Wallet', 'tales2tiles' ),
	);
}

/**
 * Product fields grouped by section.
 *
 * @return array
 */
function tales2tiles_product_fields() {
	$groups = array();
	foreach ( tales2tiles_apply_order_fields( tales2tiles_config_defaults() )['groups'] as list( $group, $title ) ) { // Not tales2tiles_config(): that builds products from these fields.
		$groups[ $group ] = $title;
	}
	return array(
		__( 'Details', 'tales2tiles' )             => array(
			'blurb' => array( 'text', __( 'Short description', 'tales2tiles' ), '' ),
			'group' => array( 'select', __( 'Product group', 'tales2tiles' ), 'magnets', array( 'choices' => $groups ) ),
			'image' => array( 'image', __( 'Photo', 'tales2tiles' ), '', array( 'help' => __( 'The main photo: product cards, the Order page and the top of the product page.', 'tales2tiles' ) ) ),
		),
		__( 'Product page', 'tales2tiles' )        => array(
			'gallery'   => array( 'gallery', __( 'More photos', 'tales2tiles' ), '', array( 'help' => __( 'Shown under the main photo on the product page.', 'tales2tiles' ) ) ),
			'options'   => array(
				'list',
				__( 'Pieces dropdown', 'tales2tiles' ),
				array(),
				array(
					'columns' => array( array( 'int', __( 'Pieces', 'tales2tiles' ) ), array( 'text', __( 'Label (optional)', 'tales2tiles' ) ) ),
					'add'     => __( 'Add choice', 'tales2tiles' ),
					'help'    => __( 'The choices customers pick from, e.g. 6 → "Set of 6". The first one is selected at the start and used for the "₱… for 6 magnets" price on the Order page. Price comes from the price or price breaks below. Leave empty for automatic sets.', 'tales2tiles' ),
				),
			),
			'other'     => array( 'checkbox', __( 'Add an "Other amount…" choice', 'tales2tiles' ), '1', array( 'help' => __( 'Lets customers type any quantity between the minimum and maximum.', 'tales2tiles' ) ) ),
			'unit_word' => array( 'text', __( 'Item word', 'tales2tiles' ), '', array( 'help' => __( 'Used in "₱1,700.00 for 6 magnets". Leave empty for "magnets" (photo magnet group) or "pieces".', 'tales2tiles' ) ) ),
			'features'  => array(
				'list',
				__( 'Feature points', 'tales2tiles' ),
				array(
					array( __( 'Premium photo magnets', 'tales2tiles' ), __( 'Sharp 300 dpi prints on sturdy, glossy magnets.', 'tales2tiles' ) ),
					array( __( 'Moments that last', 'tales2tiles' ), __( 'Turn your favorite photos into keepsakes you see every day.', 'tales2tiles' ) ),
					array( __( 'Easy to order', 'tales2tiles' ), __( 'Upload, crop and preview your photos right from your phone.', 'tales2tiles' ) ),
					array( __( 'Made with love', 'tales2tiles' ), __( 'Every order is printed and packed by hand.', 'tales2tiles' ) ),
				),
				array(
					'columns' => array( array( 'text', __( 'Title', 'tales2tiles' ) ), array( 'text', __( 'Text', 'tales2tiles' ) ) ),
					'add'     => __( 'Add feature', 'tales2tiles' ),
				),
			),
			'badges'    => array(
				'list',
				__( 'Badges', 'tales2tiles' ),
				array(
					array( 'heart', __( 'Printed with care', 'tales2tiles' ), __( 'Every magnet checked', 'tales2tiles' ) ),
					array( 'wallet', __( 'Easy payment', 'tales2tiles' ), __( 'GCash or BPI', 'tales2tiles' ) ),
					array( 'truck', __( 'Order tracking', 'tales2tiles' ), __( 'Follow your order online', 'tales2tiles' ) ),
				),
				array(
					'columns' => array(
						array( 'select', __( 'Icon', 'tales2tiles' ), 'check', array( 'choices' => tales2tiles_icon_choices() ) ),
						array( 'text', __( 'Title', 'tales2tiles' ) ),
						array( 'text', __( 'Small text', 'tales2tiles' ) ),
					),
					'add'     => __( 'Add badge', 'tales2tiles' ),
					'help'    => __( 'The row of badges under the Upload button.', 'tales2tiles' ),
				),
			),
		),
		__( 'Price and quantity', 'tales2tiles' )  => array(
			'price' => array( 'number', __( 'Price (₱)', 'tales2tiles' ), '', array( 'help' => __( 'Used when there are no price breaks below.', 'tales2tiles' ) ) ),
			'unit'  => array( 'text', __( 'Price label', 'tales2tiles' ), 'each', array( 'help' => __( 'Shown after the price, e.g. "each" or "per set".', 'tales2tiles' ) ) ),
			'tiers' => array(
				'list',
				__( 'Price breaks', 'tales2tiles' ),
				array(),
				array(
					'columns' => array( array( 'int', __( 'From (pieces)', 'tales2tiles' ) ), array( 'number', __( 'Price each (₱)', 'tales2tiles' ) ) ),
					'add'     => __( 'Add price break', 'tales2tiles' ),
					'help'    => __( 'Optional. When set, these replace the price above, and the lowest "from" quantity becomes the minimum order.', 'tales2tiles' ),
				),
			),
			'min'   => array( 'int', __( 'Minimum quantity', 'tales2tiles' ), '1' ),
			'max'   => array( 'int', __( 'Maximum quantity', 'tales2tiles' ), '50' ),
		),
		__( 'Colors', 'tales2tiles' )              => array(
			'colorLabel' => array( 'text', __( 'Color option label', 'tales2tiles' ), __( 'Color', 'tales2tiles' ) ),
			'colors'     => array(
				'list',
				__( 'Colors', 'tales2tiles' ),
				array(),
				array(
					'columns' => array( array( 'text', __( 'Color name', 'tales2tiles' ) ), array( 'color', __( 'Swatch', 'tales2tiles' ) ) ),
					'add'     => __( 'Add color', 'tales2tiles' ),
					'help'    => __( 'Leave empty if there is no color choice.', 'tales2tiles' ),
				),
			),
		),
		__( 'Photos and printing', 'tales2tiles' ) => array(
			'per'    => array( 'int', __( 'Photos per item', 'tales2tiles' ), '1', array( 'help' => __( '0 = no photos (e.g. an empty frame), 2 = front and back, 9 = a set of nine.', 'tales2tiles' ) ) ),
			'shape'  => array(
				'select',
				__( 'Print shape', 'tales2tiles' ),
				'rect',
				array(
					'choices' => array(
						'rect'   => __( 'Rectangle', 'tales2tiles' ),
						'circle' => __( 'Circle', 'tales2tiles' ),
						'pin'    => __( 'Button pin (circle that wraps around the edge)', 'tales2tiles' ),
					),
				),
			),
			'width'  => array( 'number', __( 'Print width (mm)', 'tales2tiles' ), '63.5' ),
			'height' => array( 'number', __( 'Print height (mm)', 'tales2tiles' ), '63.5', array( 'help' => __( 'For circles, use the same width and height.', 'tales2tiles' ) ) ),
			'orient' => array( 'checkbox', __( 'Customers can turn photos landscape', 'tales2tiles' ), '' ),
		),
	);
}

/**
 * Published products in menu order, in the shape the templates and app.js use.
 *
 * @return array array( key => product, color name => swatch hex ).
 */
function tales2tiles_products() {
	$posts = get_posts(
		array(
			'post_type'   => 'tt_product',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
		)
	);

	$products = array();
	$swatch   = array();
	foreach ( $posts as $post ) {
		$f     = static fn( $key ) => tales2tiles_field( 'product', $key, $post->ID );
		$image = (int) $f( 'image' );
		$art   = (string) get_post_meta( $post->ID, '_tt_product_art', true ); // Drawn SVG art of the original keepsakes (app.js keepsakeArt).
		$shape = $f( 'shape' );
		$p     = array(
			'name'  => $post->post_title,
			'group' => $f( 'group' ),
			'blurb' => $f( 'blurb' ),
			'photo' => $image ? (string) wp_get_attachment_image_url( $image, 'large' ) : ( $art ? '' : tales2tiles_img( 'logo' ) ),
			'photoFull' => $image ? (string) wp_get_attachment_image_url( $image, 'full' ) : '', // For the zoom view.
			'art'   => $art,
			'min'   => max( 1, (int) $f( 'min' ) ),
			'per'   => (int) $f( 'per' ),
			'mm'    => array( (float) $f( 'width' ) > 0 ? (float) $f( 'width' ) : 63.5, (float) $f( 'height' ) > 0 ? (float) $f( 'height' ) : 63.5 ),
		);
		if ( 'rect' !== $shape ) {
			$p['circle'] = true;
		}
		if ( 'pin' === $shape ) {
			$p['pin'] = true;
		}
		if ( '1' === $f( 'orient' ) ) {
			$p['orient'] = true;
		}
		if ( 2 === $p['per'] ) {
			$p['sides'] = true;
		}

		$tiers = array_map( static fn( $t ) => array( max( 1, (int) $t[0] ), (float) $t[1] ), (array) $f( 'tiers' ) );
		if ( $tiers ) {
			usort( $tiers, static fn( $a, $b ) => $a[0] <=> $b[0] );
			$p['tiers'] = $tiers;
			$p['min']   = max( $p['min'], $tiers[0][0] ); // No price exists below the first break.
		} else {
			$p['price'] = (float) $f( 'price' );
			$p['unit']  = $f( 'unit' );
		}
		$p['max'] = max( $p['min'], (int) $f( 'max' ) );

		$colors = (array) $f( 'colors' );
		if ( $colors ) {
			$p['colors']     = array_column( $colors, 0 );
			$p['colorLabel'] = $f( 'colorLabel' );
			foreach ( $colors as list( $name, $hex ) ) {
				$swatch[ $name ] = $hex ? $hex : '#cccccc';
			}
		}

		$options = array();
		foreach ( (array) $f( 'options' ) as $row ) {
			$q = (int) ( $row[0] ?? 0 );
			if ( $q >= 1 && $q <= $p['max'] ) {
				$options[] = array( $q, (string) ( $row[1] ?? '' ) );
			}
		}
		$p['options']  = $options; // Pieces dropdown; empty = automatic sets (app.js variants()).
		$p['other']    = '1' === $f( 'other' );
		$p['unitWord'] = '' !== trim( $f( 'unit_word' ) ) ? $f( 'unit_word' ) : ( 'magnets' === $p['group'] ? __( 'magnets', 'tales2tiles' ) : __( 'pieces', 'tales2tiles' ) );
		$p['url']      = get_permalink( $post );
		$p['postId']   = $post->ID;

		$products[ tales2tiles_product_key( $post ) ] = $p;
	}
	return array( $products, $swatch );
}

/**
 * The original catalog, used once to create the starting products.
 *
 * @return array
 */
function tales2tiles_default_products() {
	return array(
		'sq'       => array( 'name' => '2.5″ × 2.5″ Photo Magnet', 'group' => 'magnets', 'img' => 'sq', 'blurb' => '63.5 × 63.5 mm, the classic fridge tile.', 'tiers' => array( array( 3, 80 ), array( 6, 75 ), array( 51, 70 ) ), 'min' => 3, 'max' => 100, 'per' => 1, 'mm' => array( 63.5, 63.5 ) ),
		'rect'     => array( 'name' => '2.5″ × 3.5″ Photo Magnet', 'group' => 'magnets', 'img' => 'rect', 'blurb' => '65 × 90 mm, portrait or landscape.', 'tiers' => array( array( 3, 85 ), array( 6, 80 ), array( 51, 75 ) ), 'min' => 3, 'max' => 100, 'per' => 1, 'mm' => array( 65, 90 ), 'orient' => true ),
		'frameset' => array( 'name' => '9 Magnets with Frame Set', 'group' => 'frames', 'img' => 'frameset', 'blurb' => 'Wooden magnetic frame with nine 2.5″ photo magnets.', 'price' => 1000, 'unit' => 'per set', 'min' => 1, 'max' => 1, 'per' => 9, 'mm' => array( 63.5, 63.5 ), 'colors' => array( 'Black', 'White' ), 'colorLabel' => 'Frame color' ),
		'frame'    => array( 'name' => '8″ × 8″ Magnetic Frame', 'group' => 'frames', 'img' => 'frame', 'blurb' => 'Wooden frame that holds nine 2.5″ magnets.', 'price' => 325, 'unit' => 'each', 'min' => 1, 'max' => 20, 'per' => 0, 'colors' => array( 'Black', 'White' ), 'colorLabel' => 'Frame color' ), // Placeholder price; set yours.
		'pearl'    => array( 'name' => 'Pearl Wristlet Photo Keychain', 'group' => 'keepsakes', 'art' => 'pearl', 'blurb' => 'Double-sided photo charm on a pearl wristlet.', 'price' => 80, 'unit' => 'each', 'min' => 1, 'max' => 50, 'per' => 2, 'sides' => true, 'circle' => true, 'mm' => array( 30, 30 ) ),
		'leather'  => array( 'name' => 'Leather Photo Keychain', 'group' => 'keepsakes', 'art' => 'leather', 'blurb' => 'Double-sided photo charm with a soft-touch leather strap.', 'price' => 60, 'unit' => 'each', 'min' => 1, 'max' => 50, 'per' => 2, 'sides' => true, 'circle' => true, 'mm' => array( 30, 30 ), 'colors' => array( 'Black', 'Grey', 'Blue', 'Red', 'Pink', 'Brown', 'Green' ), 'colorLabel' => 'Strap color' ),
		'pin'      => array( 'name' => '58mm Custom Button Pin', 'group' => 'keepsakes', 'art' => 'pin', 'blurb' => 'Round pin-back button with your photo or design.', 'price' => 35, 'unit' => 'each', 'min' => 1, 'max' => 100, 'per' => 1, 'circle' => true, 'pin' => true, 'mm' => array( 70, 70 ) ), // Prints on a 70 mm circle to wrap a 58 mm face.
	);
}

/**
 * Create the starting products (with their bundled photos) if there are none yet.
 */
function tales2tiles_seed_products() {
	if ( get_posts( array( 'post_type' => 'tt_product', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$swatch = tales2tiles_config_defaults()['swatch'];
	$order  = 0;
	foreach ( tales2tiles_default_products() as $key => $p ) {
		$id = wp_insert_post(
			array(
				'post_type'   => 'tt_product',
				'post_status' => 'publish',
				'post_title'  => $p['name'],
				'post_name'   => $key,
				'meta_input'  => array( '_tt_key' => $key ),
				'menu_order'  => $order++,
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$image = '';
		if ( ! empty( $p['img'] ) ) {
			$upload = wp_upload_bits( $key . '.jpg', null, file_get_contents( get_template_directory() . '/assets/img/' . $p['img'] . '.jpg' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
			if ( empty( $upload['error'] ) ) {
				$image = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg', 'post_title' => $p['name'] ), $upload['file'], $id );
				wp_update_attachment_metadata( $image, wp_generate_attachment_metadata( $image, $upload['file'] ) );
			}
		}
		if ( ! empty( $p['art'] ) ) {
			update_post_meta( $id, '_tt_product_art', $p['art'] );
		}
		update_post_meta(
			$id,
			'_tt_product',
			array(
				'blurb'      => $p['blurb'],
				'group'      => $p['group'],
				'image'      => (string) $image,
				'price'      => isset( $p['price'] ) ? (string) $p['price'] : '',
				'unit'       => $p['unit'] ?? 'each',
				'tiers'      => array_map( static fn( $t ) => array_map( 'strval', $t ), $p['tiers'] ?? array() ),
				'min'        => (string) $p['min'],
				'max'        => (string) $p['max'],
				'colorLabel' => $p['colorLabel'] ?? __( 'Color', 'tales2tiles' ),
				'colors'     => array_map( static fn( $c ) => array( $c, $swatch[ $c ] ), $p['colors'] ?? array() ),
				'per'        => (string) $p['per'],
				'shape'      => empty( $p['circle'] ) ? 'rect' : ( empty( $p['pin'] ) ? 'circle' : 'pin' ),
				'width'      => (string) ( $p['mm'][0] ?? 63.5 ),
				'height'     => (string) ( $p['mm'][1] ?? 63.5 ),
				'orient'     => empty( $p['orient'] ) ? '' : '1',
			)
		);
	}
}

/**
 * Admin list: sort by the "Order" number, like the shop does.
 *
 * @param WP_Query $query Query.
 */
function tales2tiles_admin_product_order( $query ) {
	if ( is_admin() && $query->is_main_query() && 'tt_product' === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) );
	}
}
add_action( 'pre_get_posts', 'tales2tiles_admin_product_order' );

add_filter(
	'enter_title_here',
	static fn( $text, $post ) => 'tt_product' === $post->post_type ? __( 'Product name', 'tales2tiles' ) : $text,
	10,
	2
);
