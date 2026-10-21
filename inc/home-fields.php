<?php
/**
 * Home page fields: "Home page content" box on Pages → Home (the static front page).
 * Field format and types: inc/fields.php. Templates read values with tales2tiles_home( 'key' ).
 * Image fields named img_{key} override tales2tiles_img( key ). Product photos are set on each product.
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions grouped by section: key => array( type, label, default, options ).
 *
 * @return array
 */
function tales2tiles_home_fields() {
	$track = '<a href="' . esc_url( tales2tiles_page_url( 'track' ) ) . '">' . __( 'Track order', 'tales2tiles' ) . '</a>';
	$faqs  = array(
		array( __( 'Is there a minimum order?', 'tales2tiles' ), __( 'Photo magnets start at 3 pieces, and the price per piece drops at 6 and again at 51 pieces. Frames, keychains and pins can be ordered one at a time.', 'tales2tiles' ) ),
		array( __( 'How much is shipping?', 'tales2tiles' ), __( "Shipping is calculated separately based on your location. We'll message you the fee for your area after you place your order.", 'tales2tiles' ) ),
		array( __( 'Are the keychains double-sided?', 'tales2tiles' ), __( 'Yes. You upload one photo for the front and one for the back of each keychain.', 'tales2tiles' ) ),
		array( __( 'What photos work best?', 'tales2tiles' ), __( 'Clear, well-lit photos straight from your phone camera. Screenshots and photos saved from chat apps can look soft once printed, so use the original where you can.', 'tales2tiles' ) ),
		array( __( 'How do I pay?', 'tales2tiles' ), __( "Through GCash or BPI. After paying, upload a screenshot of your receipt and we'll confirm it before printing.", 'tales2tiles' ) ),
		/* translators: %s: track order link. */
		array( __( 'Can I follow my order?', 'tales2tiles' ), sprintf( __( 'Yes. Use your order number on the %s page anytime.', 'tales2tiles' ), $track ) ),
	);

	return array(
		__( 'Hero', 'tales2tiles' )         => array(
			'hero_title'  => array( 'text', __( 'Heading', 'tales2tiles' ), __( 'Turn your favorite photos into photo magnets', 'tales2tiles' ) ),
			'hero_lede'   => array( 'textarea', __( 'Intro text', 'tales2tiles' ), __( 'Bring your memories out of your phone gallery and onto your fridge, desk or anywhere you can enjoy them every day.', 'tales2tiles' ) ),
			'hero_button' => array( 'text', __( 'Button label', 'tales2tiles' ), __( 'Start Your Order', 'tales2tiles' ) ),
			'perk1'       => array( 'textarea', __( 'Perk 1', 'tales2tiles' ), __( "Premium\nquality prints", 'tales2tiles' ) ),
			'perk2'       => array( 'textarea', __( 'Perk 2', 'tales2tiles' ), __( "Perfect for\ngifts & giveaways", 'tales2tiles' ) ),
			'perk3'       => array( 'textarea', __( 'Perk 3', 'tales2tiles' ), __( "Made with\ncare and love", 'tales2tiles' ) ),
			'img_hero'    => array( 'image', __( 'Hero photo', 'tales2tiles' ), '' ),
			'hero_pos'    => array( 'focus', __( 'Hero photo position', 'tales2tiles' ), '50% 64%', array( 'image' => 'img_hero', 'help' => __( 'Click or drag on the photo to pick the part that must always stay in view. The previews show how the photo is cropped on a computer and on a phone.', 'tales2tiles' ) ) ),
			'hero_alt'    => array( 'text', __( 'Hero photo description (alt text)', 'tales2tiles' ), __( 'Two magnetic frames, one white and one black, each holding nine photo magnets', 'tales2tiles' ) ),
			'note1'       => array( 'text', __( 'Sticky note, line 1', 'tales2tiles' ), __( 'Little photos.', 'tales2tiles' ) ),
			'note2'       => array( 'text', __( 'Sticky note, line 2', 'tales2tiles' ), __( 'Big memories.', 'tales2tiles' ) ),
		),
		__( 'Products', 'tales2tiles' )     => array(
			'products_title' => array( 'text', __( 'Heading', 'tales2tiles' ), __( 'Our Products', 'tales2tiles' ) ),
			'products_sub'   => array( 'text', __( 'Subheading', 'tales2tiles' ), __( 'Choose the perfect way to keep your memories close.', 'tales2tiles' ) ),
		),
		__( 'How it works', 'tales2tiles' ) => array(
			'how_tag1'    => array( 'text', __( 'Tagline, line 1', 'tales2tiles' ), __( 'Every photo', 'tales2tiles' ) ),
			'how_tag2'    => array( 'text', __( 'Tagline, line 2', 'tales2tiles' ), __( 'tells a story', 'tales2tiles' ) ),
			'step1_title' => array( 'text', __( 'Step 1 title', 'tales2tiles' ), __( '1. Order', 'tales2tiles' ) ),
			'step1_text'  => array( 'text', __( 'Step 1 text', 'tales2tiles' ), __( 'Choose your product and upload your photos', 'tales2tiles' ) ),
			'step2_title' => array( 'text', __( 'Step 2 title', 'tales2tiles' ), __( '2. We Create', 'tales2tiles' ) ),
			'step2_text'  => array( 'text', __( 'Step 2 text', 'tales2tiles' ), __( 'We print and craft your keepsakes with care', 'tales2tiles' ) ),
			'step3_title' => array( 'text', __( 'Step 3 title', 'tales2tiles' ), __( '3. We Deliver', 'tales2tiles' ) ),
			'step3_text'  => array( 'text', __( 'Step 3 text', 'tales2tiles' ), __( 'Straight to your door', 'tales2tiles' ) ),
		),
		__( 'FAQs', 'tales2tiles' )         => array(
			'faq_title' => array( 'text', __( 'Heading', 'tales2tiles' ), __( 'FAQs', 'tales2tiles' ) ),
			'faq_sub'   => array( 'text', __( 'Subheading', 'tales2tiles' ), __( 'Quick answers before you order.', 'tales2tiles' ) ),
			'faqs'      => array(
				'list',
				__( 'Questions', 'tales2tiles' ),
				$faqs,
				array(
					'columns' => array( array( 'text', __( 'Question', 'tales2tiles' ) ), array( 'html', __( 'Answer (links and basic formatting allowed)', 'tales2tiles' ) ) ),
					'add'     => __( 'Add question', 'tales2tiles' ),
				),
			),
		),
		__( 'Call to action', 'tales2tiles' ) => array(
			'cta_title'  => array( 'text', __( 'Heading', 'tales2tiles' ), __( 'Ready to turn your memories into magnets?', 'tales2tiles' ) ),
			'cta_button' => array( 'text', __( 'Button label', 'tales2tiles' ), __( 'Start Your Order', 'tales2tiles' ) ),
			'cta_motto'  => array( 'text', __( 'Motto', 'tales2tiles' ), __( 'Hold your memories close', 'tales2tiles' ) ),
		),
	);
}

/**
 * A home page value (see inc/fields.php).
 *
 * @param string $key Field key.
 * @return mixed
 */
function tales2tiles_home( $key ) {
	return tales2tiles_field( 'home', $key );
}
