<?php
/**
 * Small rendering helpers shared by the templates.
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of an image: the one chosen in "Home page content" (img_{key}), else the bundled one in assets/img.
 *
 * @param string $key Image key, e.g. 'logo', 'hero', 'sq'.
 * @return string
 */
function tales2tiles_img( $key ) {
	$id  = (int) tales2tiles_home( 'img_' . $key );
	$url = $id ? wp_get_attachment_image_url( $id, 'hero' === $key ? 'full' : 'large' ) : '';
	return $url ? $url : get_template_directory_uri() . '/assets/img/' . $key . ( 'logo' === $key ? '.png' : '.jpg' );
}

/**
 * URL of one of the theme's app pages (created on theme activation).
 *
 * @param string $slug order|track|shop-orders.
 * @return string
 */
function tales2tiles_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/**
 * Peso amount without decimals, e.g. ₱1,000.
 *
 * @param float $n Amount.
 * @return string
 */
function tales2tiles_peso( $n ) {
	return '₱' . number_format( (float) $n );
}

/**
 * Inline SVG icon. Markup is static and trusted.
 *
 * @param string $name Icon name.
 * @return string
 */
function tales2tiles_icon( $name ) {
	$icons = array(
		'heart'      => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true"><path d="M16 27S4 19.6 4 11.8A6 6 0 0 1 16 9a6 6 0 0 1 12 2.8C28 19.6 16 27 16 27z"/></svg>',
		'gift'       => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><rect x="5" y="11" width="22" height="6" rx="1"/><path d="M7 17v11h18V17M16 11v17M16 11c-2-5-8-5-8-2s6 2 8 2zm0 0c2-5 8-5 8-2s-6 2-8 2z"/></svg>',
		'star'       => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true"><path d="M16 4l3.6 7.6 8.2 1-6 5.7 1.5 8.2L16 22.5l-7.3 4 1.5-8.2-6-5.7 8.2-1z"/></svg>',
		'phone'      => '<svg width="34" height="44" viewBox="0 0 34 44" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="6" y="2" width="22" height="40" rx="4"/><path d="M14 36h6" stroke-linecap="round"/></svg>',
		'image'      => '<svg width="44" height="40" viewBox="0 0 44 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="38" height="34" rx="4"/><circle cx="14" cy="13" r="3.5"/><path d="M4 32l11-11 8 8 6-6 12 12"/></svg>',
		'truck'      => '<svg width="50" height="36" viewBox="0 0 50 36" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h28v22H3zM31 12h9l6 7v8H31z"/><circle cx="12" cy="29" r="4" fill="var(--fridge)"/><circle cx="38" cy="29" r="4" fill="var(--fridge)"/><path d="M8 11h8M8 16h5"/></svg>',
		'arrow'      => '<svg width="30" height="16" viewBox="0 0 30 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8h25M21 2l6 6-6 6"/></svg>',
		'arrowR'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>',
		'smallHeart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" width="22" height="22" aria-hidden="true"><path d="M12 20S3 14.5 3 8.6A4.5 4.5 0 0 1 12 6.5a4.5 4.5 0 0 1 9 2.1C21 14.5 12 20 12 20z"/></svg>',
		'gift18'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13M3 12h18M12 8c-2-4-6-4-6-1.5S10 8 12 8zm0 0c2-4 6-4 6-1.5S14 8 12 8z"/></svg>',
		'cart'       => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 8H6.2"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17.5" cy="20" r="1.3"/></svg>',
		'menu'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
		'instagram'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>',
		'facebook'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-1.6 19.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7A10 10 0 0 0 12 2z"/></svg>',
		'tiktok'     => '<svg width="20" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.5 3c.3 2.2 1.6 3.7 3.8 3.9v3.1c-1.4.1-2.7-.3-3.8-1v6.2c0 3.6-2.9 5.8-5.8 5.8A5.6 5.6 0 0 1 5 15.4c0-3.4 3-5.9 6.4-5.3v3.2c-1.5-.4-3.2.5-3.2 2.1 0 1.4 1.1 2.4 2.4 2.4 1.5 0 2.5-1 2.5-2.7V3z"/></svg>',
	);
	return $icons[ $name ] ?? '';
}

/**
 * Print a product card for the home page.
 *
 * @param string $key     Product key.
 * @param array  $product Product config.
 */
function tales2tiles_product_card( $key, $product ) {
	$swatch = tales2tiles_config()['swatch'];
	?>
	<article class="pcard2">
		<?php if ( $product['photo'] ) : ?>
			<button type="button" class="pimg photo" data-zoom="<?php echo esc_url( $product['photo'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'View larger: %s', 'tales2tiles' ), $product['name'] ) ); ?>"><img src="<?php echo esc_url( $product['photo'] ); ?>" alt="<?php echo esc_attr( $product['name'] ); ?>" loading="lazy"></button>
		<?php else : ?>
			<div class="pimg art" data-art="<?php echo esc_attr( $product['art'] ); ?>"></div>
		<?php endif; ?>
		<div class="pbody">
			<h4><?php echo esc_html( $product['name'] ); ?></h4>
			<p class="blurb"><?php echo esc_html( $product['blurb'] ); ?></p>
			<?php if ( ! empty( $product['colors'] ) ) : ?>
				<div class="dots" aria-label="<?php echo esc_attr( $product['colorLabel'] . ': ' . implode( ', ', $product['colors'] ) ); ?>">
					<?php foreach ( $product['colors'] as $color ) : ?>
						<span style="background:<?php echo esc_attr( $swatch[ $color ] ); ?>" title="<?php echo esc_attr( $color ); ?>"></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $product['tiers'] ) ) : ?>
				<table class="tiers"><tbody>
					<?php
					foreach ( $product['tiers'] as $i => $tier ) :
						$next  = $product['tiers'][ $i + 1 ] ?? null;
						$range = $next ? $tier[0] . '–' . ( $next[0] - 1 ) . ' pcs' : $tier[0] . '+ pcs';
						?>
						<tr><td><?php echo esc_html( $range ); ?></td><td><?php echo esc_html( tales2tiles_peso( $tier[1] ) ); ?> each</td></tr>
					<?php endforeach; ?>
				</tbody></table>
			<?php else : ?>
				<p class="price"><?php echo esc_html( tales2tiles_peso( $product['price'] ) ); ?> <span><?php echo esc_html( $product['unit'] ); ?></span></p>
			<?php endif; ?>
			<a class="btn sm" href="<?php echo esc_url( tales2tiles_page_url( 'order' ) . '#p-' . $key ); ?>"><?php esc_html_e( 'Order this', 'tales2tiles' ); ?></a>
		</div>
	</article>
	<?php
}
