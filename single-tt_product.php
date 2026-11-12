<?php
/**
 * Single product page (/products/<slug>/).
 * Product content: Products → (this product) → "Product page", including its photo sections.
 * Why choose us and the consent switch: Pages → Order.
 * assets/js/app.js (TT.view = 'product') wires the pieces dropdown, price and "Upload pictures".
 *
 * @package Tales2Tiles
 */

get_header();

$tales2tiles_post = get_queried_object();
$tales2tiles_key  = tales2tiles_product_key( $tales2tiles_post );
$tales2tiles_p    = tales2tiles_config()['products'][ $tales2tiles_key ] ?? null;
$tales2tiles_pf   = static fn( $k ) => tales2tiles_field( 'product', $k, $tales2tiles_post->ID );
$tales2tiles_of   = static fn( $k ) => tales2tiles_field( 'order', $k );
$tales2tiles_icon = static fn( $name ) => tales2tiles_icon( $name ? $name : 'check' );

if ( ! $tales2tiles_p ) : // Draft or hidden product.
	?>
	<main id="app"><div class="wrap empty-cart"><h1><?php esc_html_e( 'This product isn\'t available right now', 'tales2tiles' ); ?></h1><a class="btn" href="<?php echo esc_url( tales2tiles_page_url( 'order' ) ); ?>"><?php esc_html_e( 'See all products', 'tales2tiles' ); ?></a></div></main>
	<?php
	get_footer();
	return;
endif;

$tales2tiles_variants = tales2tiles_variants( $tales2tiles_p );
list( $tales2tiles_price, $tales2tiles_note ) = tales2tiles_set_price( $tales2tiles_p, $tales2tiles_variants[0][0] );
$tales2tiles_slides = tales2tiles_product_slides( $tales2tiles_p );
?>
<main id="app" class="pdp-page" data-product="<?php echo esc_attr( $tales2tiles_key ); ?>">
	<div class="wrap pdp">
		<div class="pdp-gallery">
			<?php if ( $tales2tiles_slides ) : ?>
				<?php // Swiper slider + thumbnails; tapping a slide opens PhotoSwipe (assets/js/app.js initGallery). ?>
				<div class="swiper pdp-slider">
					<div class="swiper-wrapper">
						<?php foreach ( $tales2tiles_slides as $tales2tiles_i => $tales2tiles_slide ) : ?>
							<a class="swiper-slide" <?php echo tales2tiles_slide_attrs( $tales2tiles_slide ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in tales2tiles_slide_attrs(). ?> aria-label=""<?php echo esc_attr( sprintf( /* translators: 1: photo number, 2: photo count. */ __( 'Photo %1$d of %2$d, view full size', 'tales2tiles' ), $tales2tiles_i + 1, count( $tales2tiles_slides ) ) ); ?>">
								<img src="<?php echo esc_url( $tales2tiles_slide['src'] ); ?>" alt="<?php echo esc_attr( $tales2tiles_p['name'] ); ?>"<?php echo $tales2tiles_i ? ' loading="lazy"' : ' fetchpriority="high"'; ?>>
							</a>
						<?php endforeach; ?>
					</div>
					<?php if ( count( $tales2tiles_slides ) > 1 ) : ?>
						<button type="button" class="pdp-arrow prev" aria-label="<?php esc_attr_e( 'Previous photo', 'tales2tiles' ); ?>"><?php echo tales2tiles_icon( 'chevL' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
						<button type="button" class="pdp-arrow next" aria-label="<?php esc_attr_e( 'Next photo', 'tales2tiles' ); ?>"><?php echo tales2tiles_icon( 'chevR' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
						<span class="pdp-count" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="pdp-zoom" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5M11 8.5v5M8.5 11h5"/></svg></span>
				</div>
				<?php if ( count( $tales2tiles_slides ) > 1 ) : ?>
					<div class="swiper pdp-thumbs" aria-hidden="true">
						<div class="swiper-wrapper">
							<?php foreach ( $tales2tiles_slides as $tales2tiles_slide ) : ?>
								<div class="swiper-slide"><img src="<?php echo esc_url( $tales2tiles_slide['thumb'] ); ?>" alt="" loading="lazy"></div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="pdp-main art" data-art="<?php echo esc_attr( $tales2tiles_p['art'] ); ?>"></div>
			<?php endif; ?>
		</div>

		<div class="pdp-info">
			<h1><?php echo esc_html( $tales2tiles_p['name'] ); ?></h1>
			<div class="pdp-price"><strong id="pdpPrice"><?php echo esc_html( $tales2tiles_price ); ?></strong><span id="pdpNote"><?php echo wp_kses( $tales2tiles_note, array( 'b' => array() ) ); ?></span></div>
			<?php if ( $tales2tiles_p['blurb'] ) : ?>
				<p class="pdp-blurb"><?php echo esc_html( $tales2tiles_p['blurb'] ); ?></p>
			<?php endif; ?>

			<div class="pdp-opts">
				<?php if ( ! empty( $tales2tiles_p['colors'] ) ) : ?>
					<label><span><?php echo esc_html( $tales2tiles_p['colorLabel'] ); ?></span>
						<select class="input" id="pdpColor"><?php foreach ( $tales2tiles_p['colors'] as $tales2tiles_c ) : ?><option><?php echo esc_html( $tales2tiles_c ); ?></option><?php endforeach; ?></select></label>
				<?php endif; ?>
				<?php if ( count( $tales2tiles_variants ) > 1 || $tales2tiles_p['other'] ) : ?>
					<label><span><?php esc_html_e( 'How many', 'tales2tiles' ); ?></span>
						<select class="input" id="pdpQty">
							<?php foreach ( $tales2tiles_variants as list( $tales2tiles_q, $tales2tiles_label ) ) : ?>
								<option value="<?php echo (int) $tales2tiles_q; ?>"><?php echo esc_html( $tales2tiles_label ); ?></option>
							<?php endforeach; ?>
							<?php if ( $tales2tiles_p['other'] ) : ?>
								<option value="other"><?php esc_html_e( 'Other amount…', 'tales2tiles' ); ?></option>
							<?php endif; ?>
						</select></label>
					<?php if ( $tales2tiles_p['other'] ) : ?>
						<label class="pdp-other" hidden><span><?php echo esc_html( sprintf( /* translators: 1: minimum, 2: maximum. */ __( 'Number of pieces (%1$d–%2$d)', 'tales2tiles' ), $tales2tiles_p['min'], $tales2tiles_p['max'] ) ); ?></span>
							<input class="input" type="number" id="pdpOther" min="<?php echo (int) $tales2tiles_p['min']; ?>" max="<?php echo (int) $tales2tiles_p['max']; ?>" value="<?php echo (int) $tales2tiles_variants[0][0]; ?>" inputmode="numeric"></label>
					<?php endif; ?>
				<?php else : ?>
					<input type="hidden" id="pdpQty" value="<?php echo (int) $tales2tiles_variants[0][0]; ?>">
				<?php endif; ?>
			</div>

			<?php $tales2tiles_features = (array) $tales2tiles_pf( 'features' ); ?>
			<?php if ( $tales2tiles_features ) : ?>
				<ul class="pdp-features">
					<?php foreach ( $tales2tiles_features as $tales2tiles_f ) : ?>
						<li><?php echo $tales2tiles_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><div><b><?php echo esc_html( $tales2tiles_f[0] ); ?></b><?php if ( ! empty( $tales2tiles_f[1] ) ) : ?><span><?php echo esc_html( $tales2tiles_f[1] ); ?></span><?php endif; ?></div></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<button type="button" class="btn block pdp-go" id="pdpGo"><?php echo $tales2tiles_icon( $tales2tiles_p['per'] ? 'upload' : 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?> <?php echo $tales2tiles_p['per'] ? esc_html__( 'Upload pictures', 'tales2tiles' ) : esc_html__( 'Add to cart', 'tales2tiles' ); ?></button>

			<?php if ( '1' === $tales2tiles_of( 'consent_on' ) ) : ?>
				<div class="pdp-consent">
					<label class="switch"><input type="checkbox" id="pdpConsent"><span class="sw-track" aria-hidden="true"></span><span><?php echo esc_html( $tales2tiles_of( 'consent_label' ) ); ?></span></label>
					<?php if ( $tales2tiles_of( 'consent_help' ) ) : ?>
						<button type="button" class="help" aria-label="<?php esc_attr_e( 'More about this', 'tales2tiles' ); ?>" title="<?php echo esc_attr( $tales2tiles_of( 'consent_help' ) ); ?>" data-help="<?php echo esc_attr( $tales2tiles_of( 'consent_help' ) ); ?>">?</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php $tales2tiles_badges = (array) $tales2tiles_pf( 'badges' ); ?>
			<?php if ( $tales2tiles_badges ) : ?>
				<ul class="pdp-badges">
					<?php foreach ( $tales2tiles_badges as $tales2tiles_b ) : ?>
						<li><?php echo $tales2tiles_icon( $tales2tiles_b[0] ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><b><?php echo esc_html( $tales2tiles_b[1] ?? '' ); ?></b><span><?php echo esc_html( $tales2tiles_b[2] ?? '' ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<?php $tales2tiles_how = '1' === $tales2tiles_pf( 'show_how' ) ? (array) tales2tiles_home( 'how_steps' ) : array(); ?>
	<?php if ( $tales2tiles_how ) : ?>
		<section class="pdp-section pdp-how">
			<div class="wrap">
				<h2><?php esc_html_e( 'How It Works', 'tales2tiles' ); ?></h2>
				<div class="pdp-cols">
					<?php foreach ( $tales2tiles_how as $tales2tiles_s ) : ?>
						<div><span class="pdp-ico"><?php echo $tales2tiles_icon( $tales2tiles_s[0] ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span><h3><?php echo esc_html( $tales2tiles_s[1] ?? '' ); ?></h3></div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php foreach ( (array) $tales2tiles_pf( 'sections' ) as $tales2tiles_i => $tales2tiles_s ) : ?>
		<?php if ( empty( $tales2tiles_s[0] ) ) { continue; } ?>
		<?php $tales2tiles_img = ( $tales2tiles_id = (int) ( $tales2tiles_s[3] ?? 0 ) ) ? (string) wp_get_attachment_image_url( $tales2tiles_id, 'large' ) : tales2tiles_img( 'hero' ); ?>
		<section class="pdp-section pdp-split<?php echo $tales2tiles_i % 2 ? ' flip' : ''; ?>">
			<div class="wrap">
				<div class="pdp-split-text">
					<h2><span class="pdp-ico sm"><?php echo $tales2tiles_icon( $tales2tiles_s[1] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span><?php echo esc_html( $tales2tiles_s[0] ); ?></h2>
					<ul>
						<?php foreach ( array_filter( array_map( 'trim', explode( "\n", (string) ( $tales2tiles_s[2] ?? '' ) ) ) ) as $tales2tiles_pt ) : ?>
							<li><?php echo $tales2tiles_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><?php echo esc_html( $tales2tiles_pt ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<img src="<?php echo esc_url( $tales2tiles_img ); ?>" alt="" loading="lazy">
			</div>
		</section>
	<?php endforeach; ?>

	<?php $tales2tiles_why = (array) $tales2tiles_of( 'pp_why' ); ?>
	<?php if ( $tales2tiles_why ) : ?>
		<section class="pdp-section pdp-why">
			<div class="wrap">
				<h2><?php echo esc_html( $tales2tiles_of( 'pp_why_title' ) ); ?></h2>
				<div class="pdp-cols">
					<?php foreach ( $tales2tiles_why as $tales2tiles_w ) : ?>
						<div><span class="pdp-ico"><?php echo $tales2tiles_icon( $tales2tiles_w[0] ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span><h3><?php echo esc_html( $tales2tiles_w[1] ?? '' ); ?></h3><p><?php echo esc_html( $tales2tiles_w[2] ?? '' ); ?></p></div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
