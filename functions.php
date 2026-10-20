<?php
/**
 * Tales to Tiles theme setup.
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

define( 'TALES2TILES_VERSION', wp_get_theme()->get( 'Version' ) );

require get_template_directory() . '/inc/config.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/orders.php';
require get_template_directory() . '/inc/drive.php';
require get_template_directory() . '/inc/emails.php';
require get_template_directory() . '/inc/fields.php';
require get_template_directory() . '/inc/home-fields.php';
require get_template_directory() . '/inc/order-fields.php';
require get_template_directory() . '/inc/products.php';

/**
 * Theme supports.
 */
function tales2tiles_setup() {
	load_theme_textdomain( 'tales2tiles', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'tales2tiles_setup' );

/**
 * Create the app pages the theme needs (templates page-{slug}.php render them).
 */
function tales2tiles_create_pages() {
	$pages = array(
		'home'        => array( __( 'Home', 'tales2tiles' ), 'publish' ), // Static front page; holds the editable home content.
		'order'       => array( __( 'Order', 'tales2tiles' ), 'publish' ),
		'cart'        => array( __( 'Your cart', 'tales2tiles' ), 'publish' ),
		'track'       => array( __( 'Track your order', 'tales2tiles' ), 'publish' ),
		'shop-orders' => array( __( 'Shop orders', 'tales2tiles' ), 'private' ), // Private: only logged-in owners can see it.
	);
	foreach ( $pages as $slug => $page ) {
		if ( ! get_page_by_path( $slug, OBJECT, 'page' ) ) {
			wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_name'   => $slug,
					'post_title'  => $page[0],
					'post_status' => $page[1],
				)
			);
		}
	}
	if ( ! tales2tiles_front_page_id() ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', get_page_by_path( 'home', OBJECT, 'page' )->ID );
	}
	tales2tiles_seed_products();
	tales2tiles_lock_product_keys(); // Before slugs can be edited: orders keep using these keys.
	if ( '5' !== get_option( 'tales2tiles_pages' ) ) {
		tales2tiles_pretty_product_slugs(); // Once, when product pages arrive.
	}
	flush_rewrite_rules(); // /products/<slug>/ pages.
}
add_action( 'after_switch_theme', 'tales2tiles_create_pages' );
add_action(
	'admin_init',
	static function () {
		// Covers sites where the theme was already active before these pages existed.
		if ( '5' !== get_option( 'tales2tiles_pages' ) && current_user_can( 'manage_options' ) ) {
			tales2tiles_create_pages();
			update_option( 'tales2tiles_pages', '5' );
		}
	}
);

/**
 * Which app view the current page shows: product, order, cart, track, admin, or '' (home/other).
 *
 * @return string
 */
function tales2tiles_view() {
	if ( is_singular( 'tt_product' ) ) {
		return 'product';
	}
	if ( is_page( 'order' ) ) {
		return 'order';
	}
	if ( is_page( 'cart' ) ) {
		return 'cart';
	}
	if ( is_page( 'track' ) ) {
		return 'track';
	}
	if ( is_page( 'shop-orders' ) && current_user_can( 'manage_options' ) ) {
		return 'admin';
	}
	return '';
}

/**
 * Enqueue fonts, styles and the app script with its data.
 */
function tales2tiles_enqueue_assets() {
	wp_enqueue_style(
		'tales2tiles-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Jost:wght@400;500;600&family=Caveat:wght@600&display=swap',
		array(),
		null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts handles versioning.
	);
	wp_enqueue_style( 'tales2tiles-style', get_stylesheet_uri(), array( 'tales2tiles-fonts' ), TALES2TILES_VERSION );
	if ( in_array( tales2tiles_view(), array( 'order', 'cart', 'product' ), true ) ) {
		// Extra fonts for text on photos (app.js FONTS); Jost, Caveat and Cormorant come with the main fonts.
		wp_enqueue_style(
			'tales2tiles-editor-fonts',
			'https://fonts.googleapis.com/css2?family=Pacifico&family=Lobster&family=Oswald:wght@400;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;700&family=Jost:ital,wght@0,400;0,700;1,400;1,700&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts handles versioning.
		);
	}

	wp_enqueue_script(
		'tales2tiles-app',
		get_template_directory_uri() . '/assets/js/app.js',
		array(),
		TALES2TILES_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	$config = tales2tiles_config();
	$images = array();
	foreach ( array( 'logo', 'hero', 'tBeach', 'tLion', 'tBday', 'tXmas' ) as $key ) {
		$images[ $key ] = tales2tiles_img( $key );
	}
	$data = array(
		'view'        => tales2tiles_view(),
		'product'     => is_singular( 'tt_product' ) ? tales2tiles_product_key( get_queried_object() ) : '',
		'config'      => $config['shop'],
		'products'    => $config['products'],
		'groups'      => $config['groups'],
		'swatch'      => $config['swatch'],
		'img'         => $images,
		'api'         => esc_url_raw( rest_url( 'tales2tiles/v1/' ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'maxOriginal' => tales2tiles_original_max(), // Bytes; bigger originals are skipped (the cropped print version still goes through).
		'limits'      => array(
			'items'  => TALES2TILES_MAX_ITEMS,
			'photos' => TALES2TILES_MAX_PHOTOS,
		),
		'urls'        => array(
			'home'   => home_url( '/' ),
			'order'  => tales2tiles_page_url( 'order' ),
			'cart'   => tales2tiles_page_url( 'cart' ),
			'track'  => tales2tiles_page_url( 'track' ),
			'logout' => html_entity_decode( wp_logout_url( home_url( '/' ) ) ), // wp_logout_url() returns &amp;, which breaks location.href.
		),
	);
	wp_add_inline_script( 'tales2tiles-app', 'window.TT = ' . wp_json_encode( $data ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'tales2tiles_enqueue_assets' );

/**
 * Preconnect to Google Fonts.
 *
 * @param array  $urls          URLs for resource hints.
 * @param string $relation_type Relation type.
 * @return array
 */
function tales2tiles_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array(
			'href' => 'https://fonts.gstatic.com',
			'crossorigin',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'tales2tiles_resource_hints', 10, 2 );
