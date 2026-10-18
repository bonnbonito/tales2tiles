<?php
/**
 * Site header.
 *
 * @package Tales2Tiles
 */

$tales2tiles_home = home_url( '/' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="sr" href="#app"><?php esc_html_e( 'Skip to content', 'tales2tiles' ); ?></a>

<header class="site-header">
	<div class="wrap">
		<a class="logo" href="<?php echo esc_url( $tales2tiles_home ); ?>" aria-label="<?php esc_attr_e( 'Tales to Tiles home', 'tales2tiles' ); ?>">
			<img class="brand-logo" src="<?php echo esc_url( tales2tiles_img( 'logo' ) ); ?>" alt="<?php esc_attr_e( 'Tales to Tiles photo magnets', 'tales2tiles' ); ?>">
		</a>
		<button class="menu-btn" id="menuBtn" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'tales2tiles' ); ?>" aria-expanded="false" aria-controls="nav"><?php echo tales2tiles_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
		<nav class="nav" id="nav" aria-label="<?php esc_attr_e( 'Primary', 'tales2tiles' ); ?>">
			<a href="<?php echo esc_url( $tales2tiles_home ); ?>"<?php echo is_front_page() ? ' class="active" aria-current="page"' : ''; ?>><?php esc_html_e( 'Home', 'tales2tiles' ); ?></a>
			<a href="<?php echo esc_url( $tales2tiles_home . '#products' ); ?>"><?php esc_html_e( 'Products', 'tales2tiles' ); ?></a>
			<a href="<?php echo esc_url( $tales2tiles_home . '#how' ); ?>"><?php esc_html_e( 'How It Works', 'tales2tiles' ); ?></a>
			<a href="<?php echo esc_url( $tales2tiles_home . '#faq' ); ?>"><?php esc_html_e( 'FAQs', 'tales2tiles' ); ?></a>
			<a href="<?php echo esc_url( $tales2tiles_home . '#contact' ); ?>"><?php esc_html_e( 'Contact', 'tales2tiles' ); ?></a>
			<a class="btn order-btn" href="<?php echo esc_url( tales2tiles_page_url( 'order' ) ); ?>"><?php echo tales2tiles_icon( 'gift18' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><?php esc_html_e( 'Order Now', 'tales2tiles' ); ?></a>
		</nav>
		<a class="cart-link" href="<?php echo esc_url( tales2tiles_page_url( 'cart' ) ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'tales2tiles' ); ?>"><?php echo tales2tiles_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><span class="cart-count" id="cartCount" hidden>0</span></a>
	</div>
</header>
