<?php
/**
 * The "order" page: a grid of products, each linking to its product page.
 * With ?product=<key> (custom amount) or ?edit=<cart item>, assets/js/app.js shows the photo builder instead.
 *
 * @package Tales2Tiles
 */

get_header();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only switch to the JS builder.
if ( isset( $_GET['product'] ) || isset( $_GET['edit'] ) ) :
	?>
	<main id="app"><noscript><div class="wrap" style="padding:40px 20px"><?php esc_html_e( 'Please turn on JavaScript to use this page.', 'tales2tiles' ); ?></div></noscript></main>
	<?php
else :
	?>
	<main id="app" class="order-grid">
		<div class="wrap">
			<h1><?php echo esc_html( tales2tiles_field( 'order', 'order_title' ) ); ?></h1>
			<div class="og-grid">
				<?php foreach ( tales2tiles_config()['products'] as $tales2tiles_key => $tales2tiles_p ) : ?>
					<?php list( $tales2tiles_price, $tales2tiles_note ) = tales2tiles_set_price( $tales2tiles_p ); ?>
					<a class="og-card" href="<?php echo esc_url( $tales2tiles_p['url'] ); ?>">
						<span class="og-img<?php echo $tales2tiles_p['photo'] ? '' : ' art'; ?>">
							<?php if ( $tales2tiles_p['photo'] ) : ?>
								<img src="<?php echo esc_url( $tales2tiles_p['photo'] ); ?>" alt="" loading="lazy">
							<?php else : ?>
								<span data-art="<?php echo esc_attr( $tales2tiles_p['art'] ); ?>"></span>
							<?php endif; ?>
						</span>
						<span class="og-name"><?php echo esc_html( $tales2tiles_p['name'] ); ?></span>
						<span class="og-price"><b><?php echo esc_html( $tales2tiles_price ); ?></b> <?php echo esc_html( $tales2tiles_note ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</main>
	<?php
endif;
get_footer();
