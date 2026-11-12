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
			'gcash_qr'     => array( 'image', __( 'GCash QR code', 'tales2tiles' ), '' ),
			'bpi_qr'       => array( 'image', __( 'BPI QR code', 'tales2tiles' ), '' ),
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

	$sections[ __( 'Order page', 'tales2tiles' ) ] = array(
		'order_title' => array( 'text', __( 'Page title', 'tales2tiles' ), __( 'Order Magnets', 'tales2tiles' ), array( 'help' => __( 'Heading above the product grid on the Order page.', 'tales2tiles' ) ) ),
	);

	// Shared by every product page (single-tt_product.php), below the product details.
	$sections[ __( 'Product pages: Why choose us', 'tales2tiles' ) ] = array(
		'pp_why_title' => array( 'text', __( 'Heading', 'tales2tiles' ), __( 'Why Choose Our Photo Magnets?', 'tales2tiles' ) ),
		'pp_why'       => array(
			'list',
			__( 'Reasons', 'tales2tiles' ),
			array(
				array( 'star', __( 'Premium quality', 'tales2tiles' ), __( 'Sharp prints on sturdy magnets, made to last on your fridge for years.', 'tales2tiles' ) ),
				array( 'heart', __( 'Personal touch', 'tales2tiles' ), __( 'A small shop that treats every order like a gift.', 'tales2tiles' ) ),
				array( 'check', __( 'Easy to order', 'tales2tiles' ), __( 'Upload from your phone, crop, and check out in a few minutes.', 'tales2tiles' ) ),
			),
			array(
				'columns' => array( array( 'icon', __( 'Icon', 'tales2tiles' ), 'check' ), array( 'text', __( 'Title', 'tales2tiles' ) ), array( 'text', __( 'Text', 'tales2tiles' ) ) ),
				'add'     => __( 'Add reason', 'tales2tiles' ),
			),
		),
	);
	$sections[ __( 'Product pages: Social media consent', 'tales2tiles' ) ] = array(
		'consent_on'    => array( 'checkbox', __( 'Show the consent switch', 'tales2tiles' ), '1' ),
		'consent_label' => array( 'text', __( 'Switch label', 'tales2tiles' ), __( 'I agree to have my order featured in your social media content.', 'tales2tiles' ) ),
		'consent_help'  => array( 'text', __( 'Help text (the ? button)', 'tales2tiles' ), __( 'We may post photos of your finished magnets on our social media. We never share your name or address.', 'tales2tiles' ) ),
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
	$qr  = static fn( $key ) => ( $id = (int) $f( $key ) ) ? (string) wp_get_attachment_url( $id ) : '';

	$config['shop']['shippingFee'] = '' === $fee ? null : (float) $fee;
	$config['shop']['gcash']['qr'] = $qr( 'gcash_qr' );
	$config['shop']['bpi']['qr']   = $qr( 'bpi_qr' );
	unset( $config['shop']['gcash']['name'], $config['shop']['gcash']['number'], $config['shop']['bpi']['name'], $config['shop']['bpi']['account'] );
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
