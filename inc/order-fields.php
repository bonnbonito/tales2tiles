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

	$sections[ __( 'Order page', 'tales2tiles' ) ] = array(
		'order_title' => array( 'text', __( 'Page title', 'tales2tiles' ), __( 'Order Magnets', 'tales2tiles' ), array( 'help' => __( 'Heading above the product grid on the Order page.', 'tales2tiles' ) ) ),
	);

	// Shared by every product page (single-tt_product.php), below the product details.
	$sections[ __( 'Product pages: How it works', 'tales2tiles' ) ] = array(
		'pp_how_title' => array( 'text', __( 'Heading', 'tales2tiles' ), __( 'How It Works', 'tales2tiles' ) ),
		'pp_how'       => array(
			'list',
			__( 'Steps', 'tales2tiles' ),
			array(
				array( 'upload', __( 'Upload your pictures', 'tales2tiles' ), __( 'Pick your photos, then crop, edit and preview them before you add them to your cart.', 'tales2tiles' ) ),
				array( 'cart', __( 'Place your order', 'tales2tiles' ), __( 'Check out with your delivery details and pay by GCash or BPI.', 'tales2tiles' ) ),
				array( 'pin', __( 'Track your order', 'tales2tiles' ), __( 'We print, pack and ship your magnets. Follow your order online anytime.', 'tales2tiles' ) ),
			),
			array(
				'columns' => array( array( 'select', __( 'Icon', 'tales2tiles' ), 'check', array( 'choices' => tales2tiles_icon_choices() ) ), array( 'text', __( 'Title', 'tales2tiles' ) ), array( 'text', __( 'Text', 'tales2tiles' ) ) ),
				'add'     => __( 'Add step', 'tales2tiles' ),
			),
		),
	);
	foreach ( array(
		1 => array( 'sparkle', __( 'Easy to upload', 'tales2tiles' ), array( __( 'Upload straight from your phone\'s camera roll or computer.', 'tales2tiles' ), __( 'Crop, add filters and text, and preview before you order.', 'tales2tiles' ), __( 'Works on phones, tablets and computers.', 'tales2tiles' ) ) ),
		2 => array( 'heart', __( 'Made with love', 'tales2tiles' ), array( __( 'A small, family-run shop.', 'tales2tiles' ), __( 'Every order is printed and packed by hand.', 'tales2tiles' ), __( 'We check every magnet before it ships.', 'tales2tiles' ) ) ),
	) as $n => list( $icon, $title, $points ) ) {
		/* translators: %d: section number. */
		$sections[ sprintf( __( 'Product pages: Photo section %d', 'tales2tiles' ), $n ) ] = array(
			"pp_s{$n}_icon"   => array( 'select', __( 'Icon', 'tales2tiles' ), $icon, array( 'choices' => tales2tiles_icon_choices() ) ),
			"pp_s{$n}_title"  => array( 'text', __( 'Heading', 'tales2tiles' ), $title ),
			"pp_s{$n}_points" => array(
				'list',
				__( 'Points', 'tales2tiles' ),
				array_map( static fn( $point ) => array( $point ), $points ),
				array(
					'columns' => array( array( 'text', __( 'Point', 'tales2tiles' ) ) ),
					'add'     => __( 'Add point', 'tales2tiles' ),
				),
			),
			"pp_s{$n}_img"    => array( 'image', __( 'Photo', 'tales2tiles' ), '', array( 'help' => __( 'Leave empty to use the home page hero photo.', 'tales2tiles' ) ) ),
		);
	}
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
				'columns' => array( array( 'select', __( 'Icon', 'tales2tiles' ), 'check', array( 'choices' => tales2tiles_icon_choices() ) ), array( 'text', __( 'Title', 'tales2tiles' ) ), array( 'text', __( 'Text', 'tales2tiles' ) ) ),
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
