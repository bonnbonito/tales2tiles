<?php
/**
 * Shop settings DEFAULTS and the assembled shop config.
 *
 * Payment accounts, shipping, links and group titles are edited under Pages → Order
 * (inc/order-fields.php). Products are edited under Products (inc/products.php).
 *
 * Used by the PHP templates (home page) and passed to assets/js/app.js as window.TT,
 * and by inc/orders.php to recompute prices server-side (the browser's totals are never trusted).
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shop configuration with the Order page edits applied. Use this everywhere.
 *
 * @return array
 */
function tales2tiles_config() {
	static $config = null;
	if ( null === $config ) {
		$config = tales2tiles_apply_order_fields( tales2tiles_config_defaults() );

		list( $config['products'], $swatch ) = tales2tiles_products();
		$config['swatch']                    = array_merge( $config['swatch'], $swatch );
	}
	return $config;
}

/**
 * Default shop settings, groups and color swatches (before Order page edits).
 *
 * @return array
 */
function tales2tiles_config_defaults() {
	return array(
		'shop'   => array(
			'shippingFee' => null, // null = "calculated separately based on location". Set a number for a flat fee.
			'gcash'       => array(
				'name'   => 'Tales to Tiles',
				'number' => '0917 000 0000',
				'qr'     => '', // Optional: URL of your GCash QR image (e.g. from the Media Library).
			),
			'bpi'         => array(
				'name'    => 'Tales to Tiles',
				'account' => 'XXXX XXXX 1234',
			),
			'social'      => array(
				'instagram' => 'https://instagram.com/talestotiles',
				'facebook'  => 'https://facebook.com/',
				'tiktok'    => 'https://tiktok.com/',
			),
			'email'       => '', // Optional contact email shown in the footer.
		),
		'groups' => array(
			array( 'magnets', 'Photo magnets', 'Little photos. Big memories.' ),
			array( 'frames', 'Frames', 'Nine moments, one beautiful frame.' ),
			array( 'keepsakes', 'Keepsakes', 'Made personal. Made to keep.' ),
		),
		'swatch' => array( 'Black' => '#1F1D1C', 'White' => '#F5F2EE', 'Grey' => '#E4E1DC', 'Blue' => '#243463', 'Red' => '#E0261C', 'Pink' => '#F4B7A4', 'Brown' => '#7B3F22', 'Green' => '#CBBE92' ),
	);
}

/**
 * Price per piece for a product at a quantity.
 *
 * @param array $product Product config.
 * @param int   $qty     Quantity.
 * @return float
 */
function tales2tiles_unit_price( $product, $qty ) {
	if ( empty( $product['tiers'] ) ) {
		return (float) $product['price'];
	}
	$price = $product['tiers'][0][1];
	foreach ( $product['tiers'] as $tier ) {
		if ( $qty >= $tier[0] ) {
			$price = $tier[1];
		}
	}
	return (float) $price;
}
