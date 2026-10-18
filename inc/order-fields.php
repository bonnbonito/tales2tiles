<?php
/**
 * Order page fields: "Shop and payment" box on Pages → Order.
 * Field format and types: inc/fields.php. Defaults come from inc/config.php;
 * tales2tiles_config() applies the saved values. Products are edited under Products (inc/products.php).
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions grouped by section: key => array( type, label, default, options ).
 *
 * @return array
 */
function tales2tiles_order_fields() {
	$d    = tales2tiles_config_defaults();
	$shop = $d['shop'];

	$sections = array(
		__( 'Payment and shipping', 'tales2tiles' ) => array(
			'shippingFee'  => array( 'number', __( 'Shipping fee (₱)', 'tales2tiles' ), (string) $shop['shippingFee'], array( 'help' => __( 'Leave blank to say "based on location" and message customers the fee.', 'tales2tiles' ) ) ),
			'gcash_name'   => array( 'text', __( 'GCash account name', 'tales2tiles' ), $shop['gcash']['name'] ),
			'gcash_number' => array( 'text', __( 'GCash number', 'tales2tiles' ), $shop['gcash']['number'] ),
			'gcash_qr'     => array( 'image', __( 'GCash QR code', 'tales2tiles' ), '' ),
			'bpi_name'     => array( 'text', __( 'BPI account name', 'tales2tiles' ), $shop['bpi']['name'] ),
			'bpi_account'  => array( 'text', __( 'BPI account number', 'tales2tiles' ), $shop['bpi']['account'] ),
		),
		__( 'Contact and social links (footer)', 'tales2tiles' ) => array(
			'email'        => array( 'email', __( 'Contact email', 'tales2tiles' ), $shop['email'], array( 'help' => __( 'Shown in the footer; customers\' replies to their confirmation email go here.', 'tales2tiles' ) ) ),
			'notify_email' => array( 'email', __( 'Send new-order emails to', 'tales2tiles' ), (string) get_option( 'admin_email' ), array( 'help' => __( 'You get an email for every new order. Leave empty to turn it off.', 'tales2tiles' ) ) ),
			'instagram' => array( 'url', __( 'Instagram', 'tales2tiles' ), $shop['social']['instagram'] ),
			'facebook'  => array( 'url', __( 'Facebook', 'tales2tiles' ), $shop['social']['facebook'] ),
			'tiktok'    => array( 'url', __( 'TikTok', 'tales2tiles' ), $shop['social']['tiktok'] ),
		),
	);

	$groups = array();
	foreach ( $d['groups'] as list( $group, $title, $sub ) ) {
		/* translators: %s: product group, e.g. Photo magnets. */
		$groups[ "group_{$group}_title" ] = array( 'text', sprintf( __( '%s: title', 'tales2tiles' ), $title ), $title );
		/* translators: %s: product group, e.g. Photo magnets. */
		$groups[ "group_{$group}_sub" ] = array( 'text', sprintf( __( '%s: tagline', 'tales2tiles' ), $title ), $sub );
	}
	$sections[ __( 'Product groups', 'tales2tiles' ) ] = $groups;

	// Server-only: never passed to the browser (see tales2tiles_coupon()).
	$sections[ __( 'Discount codes', 'tales2tiles' ) ] = array(
		'coupons' => array(
			'list',
			__( 'Codes', 'tales2tiles' ),
			array(),
			array(
				'columns' => array(
					array( 'text', __( 'Code', 'tales2tiles' ) ),
					array( 'number', __( '% off', 'tales2tiles' ) ),
					array( 'number', __( '₱ off', 'tales2tiles' ) ),
					array( 'number', __( 'Minimum order (₱)', 'tales2tiles' ) ),
				),
				'add'     => __( 'Add code', 'tales2tiles' ),
				'help'    => __( 'Customers type the code in their cart (capital letters don\'t matter). Fill in % off or ₱ off; the discount never goes below ₱0. Remove a row to end the code.', 'tales2tiles' ),
			),
		),
	);

	return $sections;
}

/**
 * Apply the saved Order page values on top of the config defaults.
 *
 * @param array $config Defaults from tales2tiles_config_defaults().
 * @return array
 */
function tales2tiles_apply_order_fields( $config ) {
	$f = static fn( $key ) => tales2tiles_field( 'order', $key );

	$fee = $f( 'shippingFee' );
	$qr  = (int) $f( 'gcash_qr' );

	$config['shop']['shippingFee']       = '' === $fee ? null : (float) $fee;
	$config['shop']['gcash']['name']     = $f( 'gcash_name' );
	$config['shop']['gcash']['number']   = $f( 'gcash_number' );
	$config['shop']['gcash']['qr']       = $qr ? (string) wp_get_attachment_image_url( $qr, 'medium' ) : $config['shop']['gcash']['qr'];
	$config['shop']['bpi']['name']       = $f( 'bpi_name' );
	$config['shop']['bpi']['account']    = $f( 'bpi_account' );
	$config['shop']['email']             = $f( 'email' );
	$config['shop']['social']            = array(
		'instagram' => $f( 'instagram' ),
		'facebook'  => $f( 'facebook' ),
		'tiktok'    => $f( 'tiktok' ),
	);

	foreach ( $config['groups'] as &$group ) {
		$group[1] = $f( "group_{$group[0]}_title" );
		$group[2] = $f( "group_{$group[0]}_sub" );
	}
	unset( $group );

	return $config;
}
