<?php
/**
 * Single product page (/products/<slug>/).
 * Product content: Products → (this product) → "Product page". Shared sections below it: Pages → Order.
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
$tales2tiles_gallery = array_filter( array_map( 'absint', explode( ',', (string) $tales2tiles_pf( 'gallery' ) ) ) );
$tales2tiles_section_img = static function ( $n ) use ( $tales2tiles_of ) {
	$id = (int) $tales2tiles_of( "pp_s{$n}_img" );
	return $id ? (string) wp_get_attachment_image_url( $id, 'large' ) : tales2tiles_img( 'hero' );
};
?>
<main id="app" class="pdp-page" data-product="<?php echo esc_attr( $tales2tiles_key ); ?>">
	<div class="wrap pdp">
		<div class="pdp-gallery">
			<?php if ( $tales2tiles_p['photo'] ) : ?>
				<button type="button" class="pdp-main" data-zoom="<?php echo esc_url( $tales2tiles_p['photo'] ); ?>" aria-label="<?php esc_attr_e( 'View full size', 'tales2tiles' ); ?>"><img src="<?php echo esc_url( $tales2tiles_p['photo'] ); ?>" alt="<?php echo esc_attr( $tales2tiles_p['name'] ); ?>" fetchpriority="high"></button>
			<?php else : ?>
				<div class="pdp-main art" data-art="<?php echo esc_attr( $tales2tiles_p['art'] ); ?>"></div>
			<?php endif; ?>
			<?php if ( $tales2tiles_gallery ) : ?>
				<div class="pdp-thumbs">
					<?php foreach ( $tales2tiles_gallery as $tales2tiles_image ) : ?>
						<button type="button" data-zoom="<?php echo esc_url( (string) wp_get_attachment_image_url( $tales2tiles_image, 'full' ) ); ?>" aria-label="<?php esc_attr_e( 'View full size', 'tales2tiles' ); ?>"><?php echo wp_get_attachment_image( $tales2tiles_image, 'medium_large', false, array( 'loading' => 'lazy' ) ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="pdp-info">
			<h1><?php echo esc_html( $tales2tiles_p['name'] ); ?></h1>
			<div class="pdp-price"><strong id="pdpPrice"><?php echo esc_html( $tales2tiles_price ); ?></strong><span id="pdpNote"><?php echo esc_html( $tales2tiles_note ); ?></span></div>
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

	<?php $tales2tiles_how = (array) $tales2tiles_of( 'pp_how' ); ?>
	<?php if ( $tales2tiles_how ) : ?>
		<section class="pdp-section pdp-how">
			<div class="wrap">
				<h2><?php echo esc_html( $tales2tiles_of( 'pp_how_title' ) ); ?></h2>
				<div class="pdp-cols">
					<?php foreach ( $tales2tiles_how as $tales2tiles_s ) : ?>
						<div><span class="pdp-ico"><?php echo $tales2tiles_icon( $tales2tiles_s[0] ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span><h3><?php echo esc_html( $tales2tiles_s[1] ?? '' ); ?></h3><p><?php echo esc_html( $tales2tiles_s[2] ?? '' ); ?></p></div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php foreach ( array( 1, 2 ) as $tales2tiles_n ) : ?>
		<?php $tales2tiles_points = (array) $tales2tiles_of( "pp_s{$tales2tiles_n}_points" ); ?>
		<?php if ( $tales2tiles_of( "pp_s{$tales2tiles_n}_title" ) ) : ?>
			<section class="pdp-section pdp-split<?php echo 2 === $tales2tiles_n ? ' flip' : ''; ?>">
				<div class="wrap">
					<div class="pdp-split-text">
						<h2><span class="pdp-ico sm"><?php echo $tales2tiles_icon( $tales2tiles_of( "pp_s{$tales2tiles_n}_icon" ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span><?php echo esc_html( $tales2tiles_of( "pp_s{$tales2tiles_n}_title" ) ); ?></h2>
						<ul>
							<?php foreach ( $tales2tiles_points as $tales2tiles_pt ) : ?>
								<li><?php echo $tales2tiles_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><?php echo esc_html( $tales2tiles_pt[0] ?? '' ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<img src="<?php echo esc_url( $tales2tiles_section_img( $tales2tiles_n ) ); ?>" alt="" loading="lazy">
				</div>
			</section>
		<?php endif; ?>
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
