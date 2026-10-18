<?php
/**
 * Shop owner dashboard. Data is only served by the REST API to users who can manage_options.
 *
 * @package Tales2Tiles
 */

get_header();
?>
<main id="app">
	<?php if ( ! current_user_can( 'manage_options' ) ) : ?>
		<div class="pin">
			<h1><?php esc_html_e( 'Shop owner', 'tales2tiles' ); ?></h1>
			<p><?php esc_html_e( 'Log in with your WordPress admin account to see orders.', 'tales2tiles' ); ?></p>
			<p><a class="btn" href="<?php echo esc_url( wp_login_url( tales2tiles_page_url( 'shop-orders' ) ) ); ?>"><?php esc_html_e( 'Log in', 'tales2tiles' ); ?></a></p>
		</div>
	<?php endif; ?>
</main>
<?php
get_footer();
