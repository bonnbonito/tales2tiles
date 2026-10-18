<?php
/**
 * Order emails, sent when an order is placed (hook tales2tiles_order_created):
 * - the customer's confirmation (only if they gave an email address),
 * - the shop's new-order email, to Pages → Order → "Send new-order emails to" (default: the site admin email).
 *
 * Sent with wp_mail(). On a live site, use an SMTP plugin (e.g. WP Mail SMTP) so mail isn't marked as spam.
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Peso amount with centavos, e.g. ₱1,250.00.
 *
 * @param float $n Amount.
 * @return string
 */
function tales2tiles_peso2( $n ) {
	return '₱' . number_format( (float) $n, 2 );
}

/**
 * The order as an HTML email body.
 *
 * @param array  $order Order data.
 * @param string $intro Opening paragraph (HTML, already escaped).
 * @param bool   $owner Include the shop-only details (phone, payment reference, dashboard link).
 * @return string
 */
function tales2tiles_order_email_html( $order, $intro, $owner ) {
	$c    = $order['customer'];
	$rows = '';
	foreach ( (array) $order['items'] as $item ) {
		$copies = ( $item['copies'] ?? 1 ) > 1 ? ' × ' . (int) $item['copies'] . ' ' . __( 'copies', 'tales2tiles' ) : '';
		$rows  .= '<tr><td style="padding:6px 0;border-bottom:1px solid #f0dddE">' . esc_html( $item['name'] ) . '<br><small style="color:#7b6562">' . (int) $item['qty'] . esc_html( $copies ) . ' × ' . esc_html( tales2tiles_peso2( $item['unit'] ) ) . '</small></td><td style="padding:6px 0;border-bottom:1px solid #f0ddde;text-align:right">' . esc_html( tales2tiles_peso2( $item['subtotal'] ) ) . '</td></tr>';
	}
	$total = static fn( $label, $value, $bold = false ) => '<tr><td style="padding:4px 0' . ( $bold ? ';font-weight:bold' : '' ) . '">' . esc_html( $label ) . '</td><td style="padding:4px 0;text-align:right' . ( $bold ? ';font-weight:bold' : '' ) . '">' . esc_html( $value ) . '</td></tr>';
	$rows .= $total( __( 'Subtotal', 'tales2tiles' ), tales2tiles_peso2( $order['subtotal'] ) );
	if ( ! empty( $order['discount'] ) ) {
		/* translators: %s: discount code. */
		$rows .= $total( sprintf( __( 'Discount (%s)', 'tales2tiles' ), $order['coupon'] ), '−' . tales2tiles_peso2( $order['discount'] ) );
	}
	$rows .= $total( __( 'Shipping fee', 'tales2tiles' ), null === $order['shipping'] ? __( 'To follow, based on location', 'tales2tiles' ) : tales2tiles_peso2( $order['shipping'] ) );
	$rows .= $total( __( 'Paid', 'tales2tiles' ), tales2tiles_peso2( $order['total'] ), true );

	$address = esc_html( $c['name'] ) . '<br>' . esc_html( $c['address'] ) . '<br>' . esc_html( $c['city'] . ', ' . $c['province'] . ' ' . $c['postal'] );
	if ( $owner ) {
		$address .= '<br>' . esc_html( $c['phone'] ) . ( $c['email'] ? '<br>' . esc_html( $c['email'] ) : '' );
	}
	$payment = esc_html( $order['payMethod'] . ( $order['payRef'] ? ' · ' . __( 'Ref.', 'tales2tiles' ) . ' ' . $order['payRef'] : '' ) );
	$link    = $owner
		? '<a href="' . esc_url( tales2tiles_page_url( 'shop-orders' ) ) . '">' . esc_html__( 'Open the order in your dashboard', 'tales2tiles' ) . '</a>'
		: '<a href="' . esc_url( add_query_arg( 'order', $order['id'], tales2tiles_page_url( 'track' ) ) ) . '">' . esc_html__( 'Track your order', 'tales2tiles' ) . '</a>';

	return '<div style="font-family:Arial,sans-serif;font-size:15px;color:#3d2b28;max-width:560px;margin:0 auto">'
		. '<p>' . $intro . '</p>'
		/* translators: %s: order number. */
		. '<h2 style="font-size:18px;margin:20px 0 8px">' . esc_html( sprintf( __( 'Order %s', 'tales2tiles' ), $order['id'] ) ) . '</h2>'
		. '<table style="width:100%;border-collapse:collapse">' . $rows . '</table>'
		. '<h3 style="font-size:15px;margin:20px 0 6px">' . esc_html__( 'Deliver to', 'tales2tiles' ) . '</h3><p style="margin:0">' . $address . '</p>'
		. '<h3 style="font-size:15px;margin:20px 0 6px">' . esc_html__( 'Payment', 'tales2tiles' ) . '</h3><p style="margin:0">' . $payment . '</p>'
		. ( $c['notes'] ? '<h3 style="font-size:15px;margin:20px 0 6px">' . esc_html__( 'Note', 'tales2tiles' ) . '</h3><p style="margin:0">' . nl2br( esc_html( $c['notes'] ) ) . '</p>' : '' )
		. '<p style="margin-top:24px">' . $link . '</p>'
		. '</div>';
}

/**
 * Send both emails for a new order.
 *
 * @param int   $post_id Order post ID.
 * @param array $order   Order data.
 */
function tales2tiles_send_order_emails( $post_id, $order ) {
	$shop    = get_bloginfo( 'name' );
	$html    = array( 'Content-Type: text/html; charset=UTF-8' );
	$contact = tales2tiles_config()['shop']['email'];
	$notify  = tales2tiles_field( 'order', 'notify_email' );

	if ( $order['customer']['email'] ) {
		$intro = sprintf(
			/* translators: 1: customer name, 2: shop name. */
			esc_html__( 'Hi %1$s, thank you for your order from %2$s! We\'ll check your payment and message you once it\'s approved, and again when it ships.', 'tales2tiles' ),
			esc_html( $order['customer']['name'] ),
			esc_html( $shop )
		);
		wp_mail(
			$order['customer']['email'],
			/* translators: 1: shop name, 2: order number. */
			sprintf( __( 'Your %1$s order %2$s', 'tales2tiles' ), $shop, $order['id'] ),
			tales2tiles_order_email_html( $order, $intro, false ),
			$contact ? array_merge( $html, array( 'Reply-To: ' . $contact ) ) : $html
		);
	}

	if ( is_email( $notify ) ) {
		/* translators: 1: customer name, 2: order total. */
		$intro = sprintf( esc_html__( 'New order from %1$s, %2$s. Check the payment receipt before approving.', 'tales2tiles' ), esc_html( $order['customer']['name'] ), esc_html( tales2tiles_peso2( $order['total'] ) ) );
		wp_mail(
			$notify,
			/* translators: 1: order number, 2: total, 3: customer name. */
			sprintf( __( 'New order %1$s: %2$s from %3$s', 'tales2tiles' ), $order['id'], tales2tiles_peso2( $order['total'] ), $order['customer']['name'] ),
			tales2tiles_order_email_html( $order, $intro, true ),
			$order['customer']['email'] ? array_merge( $html, array( 'Reply-To: ' . $order['customer']['email'] ) ) : $html
		);
	}
}
add_action( 'tales2tiles_order_created', 'tales2tiles_send_order_emails', 20, 2 );
