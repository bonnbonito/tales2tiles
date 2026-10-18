<?php
/**
 * Site footer.
 *
 * @package Tales2Tiles
 */

$tales2tiles_shop = tales2tiles_config()['shop'];
$tales2tiles_home = home_url( '/' );
?>
<footer class="site" id="contact">
	<div class="wrap">
		<div class="foot-top">
			<a class="logo" href="<?php echo esc_url( $tales2tiles_home ); ?>"><img class="brand-logo" src="<?php echo esc_url( tales2tiles_img( 'logo' ) ); ?>" alt="<?php esc_attr_e( 'Tales to Tiles photo magnets', 'tales2tiles' ); ?>"></a>
			<nav class="foot-links" aria-label="<?php esc_attr_e( 'Footer', 'tales2tiles' ); ?>">
				<a href="<?php echo esc_url( $tales2tiles_home ); ?>"><?php esc_html_e( 'Home', 'tales2tiles' ); ?></a>
				<a href="<?php echo esc_url( $tales2tiles_home . '#products' ); ?>"><?php esc_html_e( 'Products', 'tales2tiles' ); ?></a>
				<a href="<?php echo esc_url( $tales2tiles_home . '#how' ); ?>"><?php esc_html_e( 'How It Works', 'tales2tiles' ); ?></a>
				<a href="<?php echo esc_url( $tales2tiles_home . '#faq' ); ?>"><?php esc_html_e( 'FAQs', 'tales2tiles' ); ?></a>
				<a href="<?php echo esc_url( tales2tiles_page_url( 'track' ) ); ?>"><?php esc_html_e( 'Track order', 'tales2tiles' ); ?></a>
				<?php if ( $tales2tiles_shop['email'] ) : ?>
					<a href="mailto:<?php echo esc_attr( antispambot( $tales2tiles_shop['email'] ) ); ?>"><?php echo esc_html( antispambot( $tales2tiles_shop['email'] ) ); ?></a>
				<?php endif; ?>
			</nav>
			<div class="socials">
				<?php foreach ( array_filter( $tales2tiles_shop['social'] ) as $tales2tiles_network => $tales2tiles_url ) : ?>
					<a href="<?php echo esc_url( $tales2tiles_url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $tales2tiles_network ) ); ?>"><?php echo tales2tiles_icon( $tales2tiles_network ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="foot-bot">
			<span>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'tales2tiles' ); ?> · <a href="<?php echo esc_url( current_user_can( 'manage_options' ) ? tales2tiles_page_url( 'shop-orders' ) : wp_login_url( tales2tiles_page_url( 'shop-orders' ) ) ); ?>" style="color:inherit"><?php esc_html_e( 'Shop owner', 'tales2tiles' ); ?></a></span>
			<span class="script"><?php esc_html_e( 'Made with love', 'tales2tiles' ); ?> <?php echo tales2tiles_icon( 'smallHeart' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
		</div>
	</div>
</footer>

<div id="printArea"></div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<?php wp_footer(); ?>
</body>
</html>
