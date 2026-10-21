<?php
/**
 * Home page: hero, products, how it works, FAQs, call to action.
 * Text and images: Pages → Home → "Home page content" (inc/home-fields.php).
 * Products and prices come from inc/config.php.
 *
 * @package Tales2Tiles
 */

$tales2tiles_config = tales2tiles_config();
$tales2tiles_order = tales2tiles_page_url( 'order' );
$tales2tiles_fee = $tales2tiles_config['shop']['shippingFee'];

get_header();
?>
<main id="app">
	<section class="hero">
		<div class="hero-copy">
			<h1><?php echo esc_html( tales2tiles_home( 'hero_title' ) ); ?></h1>
			<p class="lede"><?php echo nl2br( esc_html( tales2tiles_home( 'hero_lede' ) ) ); ?></p>
			<a class="btn big"
				href="<?php echo esc_url( $tales2tiles_order ); ?>"><?php echo esc_html( tales2tiles_home( 'hero_button' ) ); ?>
				<?php echo tales2tiles_icon( 'arrowR' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a>
			<ul class="perk-row">
				<li>
					<?php echo tales2tiles_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo nl2br( esc_html( tales2tiles_home( 'perk1' ) ) ); ?></span>
				</li>
				<li>
					<?php echo tales2tiles_icon( 'gift' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo nl2br( esc_html( tales2tiles_home( 'perk2' ) ) ); ?></span>
				</li>
				<li>
					<?php echo tales2tiles_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo nl2br( esc_html( tales2tiles_home( 'perk3' ) ) ); ?></span>
				</li>
			</ul>
		</div>
		<div class="hero-photo">
			<img src="<?php echo esc_url( tales2tiles_img( 'hero' ) ); ?>"
				alt="<?php echo esc_attr( tales2tiles_home( 'hero_alt' ) ); ?>" fetchpriority="high">
		</div>
	</section>

	<section class="block" id="products">
		<div class="wrap">
			<div class="sec-title">
				<h2><?php echo esc_html( tales2tiles_home( 'products_title' ) ); ?></h2>
				<p><?php echo esc_html( tales2tiles_home( 'products_sub' ) ); ?></p>
			</div>
			<div class="pgroups">
				<?php foreach ( $tales2tiles_config['groups'] as list( $tales2tiles_group, $tales2tiles_title, $tales2tiles_sub ) ) : ?>
					<div class="pgroup<?php echo 'keepsakes' === $tales2tiles_group ? ' wide' : ''; ?>">
						<div class="pgroup-head">
							<h3><?php echo esc_html( $tales2tiles_title ); ?></h3>
							<span><?php echo esc_html( $tales2tiles_sub ); ?></span>
						</div>
						<div class="pgrid2">
							<?php
							foreach ( $tales2tiles_config['products'] as $tales2tiles_key => $tales2tiles_product ) {
								if ( $tales2tiles_product['group'] === $tales2tiles_group ) {
									tales2tiles_product_card( $tales2tiles_key, $tales2tiles_product );
								}
							}
							?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="ship-note">
				<?php
				if ( null === $tales2tiles_fee ) {
					esc_html_e( 'Shipping fee is calculated separately based on your location.', 'tales2tiles' );
				} else {
					/* translators: %s: shipping fee. */
					echo esc_html( sprintf( __( 'Shipping is a flat %s anywhere in the Philippines.', 'tales2tiles' ), tales2tiles_peso( $tales2tiles_fee ) ) );
				}
				?>
			</p>
		</div>
	</section>

	<section class="band" id="how">
		<div class="wrap">
			<div class="tagline">
				<div>
					<span><?php echo esc_html( tales2tiles_home( 'how_tag1' ) ); ?></span><span><?php echo esc_html( tales2tiles_home( 'how_tag2' ) ); ?></span>
				</div><?php echo tales2tiles_icon( 'smallHeart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="bstep">
				<?php echo tales2tiles_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b><?php echo esc_html( tales2tiles_home( 'step1_title' ) ); ?></b>
				<p><?php echo esc_html( tales2tiles_home( 'step1_text' ) ); ?></p>
			</div>
			<div class="barrow"><?php echo tales2tiles_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="bstep">
				<?php echo tales2tiles_icon( 'image' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b><?php echo esc_html( tales2tiles_home( 'step2_title' ) ); ?></b>
				<p><?php echo esc_html( tales2tiles_home( 'step2_text' ) ); ?></p>
			</div>
			<div class="barrow"><?php echo tales2tiles_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="bstep">
				<?php echo tales2tiles_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b><?php echo esc_html( tales2tiles_home( 'step3_title' ) ); ?></b>
				<p><?php echo esc_html( tales2tiles_home( 'step3_text' ) ); ?></p>
			</div>
		</div>
	</section>

	<section class="block" id="faq">
		<div class="wrap">
			<div class="sec-title">
				<h2><?php echo esc_html( tales2tiles_home( 'faq_title' ) ); ?></h2>
				<p><?php echo esc_html( tales2tiles_home( 'faq_sub' ) ); ?></p>
			</div>
			<div class="faq">
				<?php foreach ( tales2tiles_home( 'faqs' ) as list( $tales2tiles_q, $tales2tiles_a ) ) : ?>
					<details>
						<summary><?php echo esc_html( $tales2tiles_q ); ?></summary>
						<p><?php echo wp_kses_post( $tales2tiles_a ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="cta">
		<h2><?php echo esc_html( tales2tiles_home( 'cta_title' ) ); ?></h2>
		<a class="btn"
			href="<?php echo esc_url( $tales2tiles_order ); ?>"><?php echo esc_html( tales2tiles_home( 'cta_button' ) ); ?>
			<?php echo tales2tiles_icon( 'arrowR' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<div class="motto"><?php echo esc_html( tales2tiles_home( 'cta_motto' ) ); ?></div>
	</section>
</main>
<?php
get_footer();