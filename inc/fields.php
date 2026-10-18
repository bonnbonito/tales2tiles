<?php
/**
 * Tiny custom-fields engine for the theme's editable pages (classic editor only).
 *
 * Each "box" is a meta box defined in tales2tiles_field_boxes(), either on one page
 * or on every post of a post type:
 * - home    → Pages → Home  (inc/home-fields.php)
 * - order   → Pages → Order (inc/order-fields.php)
 * - product → each Products post (inc/products.php)
 *
 * A field is array( type, label, default, options ):
 * - text, textarea (new line = line break), html (limited HTML), url, email
 * - number (blank allowed), int
 * - select (options 'choices' => array( value => label )), checkbox ('1' or '')
 * - image (attachment ID; a key named img_{x} previews the bundled assets/img/{x})
 * - list: repeatable rows, saved as array( array( col0, col1, … ), … ).
 *   options: 'columns' => array( array( type, label ), … ) with column types text|html|int|number|color;
 *   'add' => button label. Rows with an empty first column are dropped.
 *
 * Read values with tales2tiles_field( $box, $key [, $post_id] ). Unsaved fields return their default.
 * Any field may set options 'help' (description under the input).
 *
 * @package Tales2Tiles
 */

defined( 'ABSPATH' ) || exit;

// Classic editor everywhere.
add_filter( 'use_block_editor_for_post_type', '__return_false' );

/**
 * Editable boxes: page ID (or post_type), meta key, title, sections callback, and
 * 'cap': extra capability needed to see/save the box (on top of edit_post).
 *
 * @return array
 */
function tales2tiles_field_boxes() {
	$order = get_page_by_path( 'order' );
	return array(
		'home'    => array(
			'page'     => tales2tiles_front_page_id(),
			'meta'     => '_tt_home',
			'title'    => __( 'Home page content', 'tales2tiles' ),
			'sections' => 'tales2tiles_home_fields',
		),
		'order'   => array(
			'page'     => $order ? $order->ID : 0,
			'meta'     => '_tt_order_page',
			'title'    => __( 'Shop and payment', 'tales2tiles' ),
			'sections' => 'tales2tiles_order_fields',
			'cap'      => 'manage_options', // Payment accounts: editors must not be able to change where money goes.
		),
		'product' => array(
			'post_type' => 'tt_product',
			'meta'      => '_tt_product',
			'title'     => __( 'Product details', 'tales2tiles' ),
			'sections'  => 'tales2tiles_product_fields',
			'cap'       => 'manage_options',
		),
	);
}

/**
 * ID of the static front page, 0 if the front page shows posts.
 *
 * @return int
 */
function tales2tiles_front_page_id() {
	return 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
}

/**
 * Flat key => field map of a box.
 *
 * @param string $box Box name.
 * @return array
 */
function tales2tiles_field_map( $box ) {
	static $maps = array();
	if ( ! isset( $maps[ $box ] ) ) {
		$maps[ $box ] = array_merge( ...array_values( call_user_func( tales2tiles_field_boxes()[ $box ]['sections'] ) ) );
	}
	return $maps[ $box ];
}

/**
 * A field value: saved value, else its default.
 *
 * @param string $box     Box name.
 * @param string $key     Field key.
 * @param int    $post_id Post ID, for post-type boxes (page boxes know their page).
 * @return mixed String, or array for list fields.
 */
function tales2tiles_field( $box, $key, $post_id = 0 ) {
	static $saved = array();
	$id = $post_id ? $post_id : ( tales2tiles_field_boxes()[ $box ]['page'] ?? 0 );
	if ( ! isset( $saved[ $box ][ $id ] ) ) {
		$saved[ $box ][ $id ] = $id ? (array) get_post_meta( $id, tales2tiles_field_boxes()[ $box ]['meta'], true ) : array();
	}
	if ( array_key_exists( $key, $saved[ $box ][ $id ] ) ) {
		return $saved[ $box ][ $id ][ $key ];
	}
	return tales2tiles_field_map( $box )[ $key ][2] ?? '';
}

/**
 * Which box (if any) belongs to a post.
 *
 * @param WP_Post $post Post.
 * @return string Box name or ''.
 */
function tales2tiles_box_for( $post ) {
	foreach ( tales2tiles_field_boxes() as $box => $def ) {
		if ( isset( $def['post_type'] ) ? $def['post_type'] === $post->post_type : ( 'page' === $post->post_type && $def['page'] && (int) $post->ID === (int) $def['page'] ) ) {
			return $box;
		}
	}
	return '';
}

/**
 * Add the meta box and hide the (unused) content editor.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Post being edited.
 */
function tales2tiles_add_field_box( $post_type, $post ) {
	$box = $post instanceof WP_Post ? tales2tiles_box_for( $post ) : '';
	if ( ! $box || ! current_user_can( tales2tiles_field_boxes()[ $box ]['cap'] ?? 'edit_posts' ) ) {
		return;
	}
	remove_post_type_support( $post_type, 'editor' );
	add_meta_box( 'tales2tiles-' . $box, tales2tiles_field_boxes()[ $box ]['title'], 'tales2tiles_field_box_html', $post_type, 'normal', 'high', array( 'box' => $box ) );
	wp_enqueue_media();
	wp_enqueue_script( 'tales2tiles-fields', get_template_directory_uri() . '/assets/js/admin-fields.js', array(), TALES2TILES_VERSION, true );
}
add_action( 'add_meta_boxes', 'tales2tiles_add_field_box', 10, 2 );

/**
 * Meta box markup.
 *
 * @param WP_Post $post    Post being edited.
 * @param array   $metabox Meta box args.
 */
function tales2tiles_field_box_html( $post, $metabox ) {
	$box = $metabox['args']['box'];
	wp_nonce_field( 'tales2tiles_' . $box, 'tales2tiles_' . $box . '_nonce' );
	echo '<style>.tt-row{display:flex;flex-wrap:wrap;gap:8px 12px;align-items:flex-end;border:1px solid #c3c4c7;padding:8px 12px;margin-bottom:8px;background:#fff}.tt-row label{display:flex;flex-direction:column;gap:2px}.tt-row .tt-wide{flex:1 1 100%}.tt-row-actions{margin-left:auto}.tt-image img{max-width:200px;height:auto;display:block;margin-bottom:8px}.tt-image img[src=""]{display:none}</style>';
	foreach ( call_user_func( tales2tiles_field_boxes()[ $box ]['sections'] ) as $section => $fields ) {
		echo '<h3>' . esc_html( $section ) . '</h3><table class="form-table" role="presentation">';
		foreach ( $fields as $key => $field ) {
			$id = 'tt-' . $box . '-' . $key;
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $field[1] ) . '</label></th><td>';
			tales2tiles_field_input( $id, 'tt_' . $box . '[' . $key . ']', $key, $field, tales2tiles_field( $box, $key, $post->ID ) );
			if ( ! empty( $field[3]['help'] ) ) {
				echo '<p class="description">' . esc_html( $field[3]['help'] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
}

/**
 * One field input.
 *
 * @param string $id    Element ID.
 * @param string $name  Input name.
 * @param string $key   Field key.
 * @param array  $field Field definition.
 * @param mixed  $value Current value.
 */
function tales2tiles_field_input( $id, $name, $key, $field, $value ) {
	switch ( $field[0] ) {
		case 'image':
			$fallback = 0 === strpos( $key, 'img_' ) ? tales2tiles_img( substr( $key, 4 ) ) : ''; // Bundled image when nothing is chosen.
			$src      = $value ? wp_get_attachment_image_url( (int) $value, 'medium' ) : $fallback;
			printf(
				'<div class="tt-image" data-fallback="%1$s"><img src="%2$s" alt=""><input type="hidden" id="%3$s" name="%4$s" value="%5$s"><button type="button" class="button tt-pick">%6$s</button> <button type="button" class="button-link tt-reset">%7$s</button></div>',
				esc_url( $fallback ),
				esc_url( (string) $src ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value ),
				esc_html__( 'Choose image', 'tales2tiles' ),
				$fallback ? esc_html__( 'Use theme default', 'tales2tiles' ) : esc_html__( 'Remove', 'tales2tiles' )
			);
			break;
		case 'list':
			echo '<div class="tt-list" id="' . esc_attr( $id ) . '"><div class="tt-rows">';
			foreach ( (array) $value as $i => $row ) {
				tales2tiles_list_row( $name . '[' . $i . ']', $field[3]['columns'], (array) $row );
			}
			echo '</div><template>';
			tales2tiles_list_row( $name . '[__i__]', $field[3]['columns'], array() );
			echo '</template><button type="button" class="button tt-add">' . esc_html( $field[3]['add'] ?? __( 'Add row', 'tales2tiles' ) ) . '</button></div>';
			break;
		case 'select':
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $field[3]['choices'] as $choice => $label ) {
				echo '<option value="' . esc_attr( $choice ) . '"' . selected( $value, $choice, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
			break;
		case 'checkbox':
			printf( '<input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s>', esc_attr( $id ), esc_attr( $name ), checked( $value, '1', false ) );
			break;
		case 'textarea':
		case 'html':
			printf( '<textarea class="large-text" rows="3" id="%1$s" name="%2$s">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
			break;
		case 'number':
		case 'int':
			printf( '<input type="number" class="small-text" min="0" step="%1$s" id="%2$s" name="%3$s" value="%4$s">', 'int' === $field[0] ? '1' : 'any', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
			break;
		default: // text, url, email.
			printf( '<input type="%1$s" class="large-text" id="%2$s" name="%3$s" value="%4$s">', 'text' === $field[0] ? 'text' : esc_attr( $field[0] ), esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
	}
}

/**
 * One repeatable row.
 *
 * @param string $name    Row input name prefix.
 * @param array  $columns Column definitions.
 * @param array  $row     Row values.
 */
function tales2tiles_list_row( $name, $columns, $row ) {
	echo '<div class="tt-row">';
	foreach ( $columns as $c => list( $type, $label ) ) {
		$col   = $name . '[' . $c . ']';
		$value = $row[ $c ] ?? '';
		if ( 'html' === $type ) {
			printf( '<label class="tt-wide">%1$s<textarea class="large-text" rows="3" name="%2$s">%3$s</textarea></label>', esc_html( $label ), esc_attr( $col ), esc_textarea( $value ) );
		} elseif ( 'text' === $type ) {
			printf( '<label class="tt-wide">%1$s<input type="text" class="large-text" name="%2$s" value="%3$s"></label>', esc_html( $label ), esc_attr( $col ), esc_attr( $value ) );
		} else { // int, number, color.
			$attrs = 'color' === $type ? 'type="color"' : 'type="number" class="small-text" min="0" step="' . ( 'int' === $type ? '1' : 'any' ) . '"';
			printf( '<label>%1$s<input %2$s name="%3$s" value="%4$s"></label>', esc_html( $label ), $attrs, esc_attr( $col ), esc_attr( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- $attrs is static.
		}
	}
	printf(
		'<span class="tt-row-actions"><button type="button" class="button-link tt-up">%1$s</button> | <button type="button" class="button-link button-link-delete tt-remove">%2$s</button></span></div>',
		esc_html__( 'Move up', 'tales2tiles' ),
		esc_html__( 'Remove', 'tales2tiles' )
	);
}

/**
 * Sanitize one value by type.
 *
 * @param array $field Field or column definition.
 * @param mixed $value Raw value.
 * @return string
 */
function tales2tiles_sanitize_field( $field, $value ) {
	$value = is_scalar( $value ) ? (string) $value : '';
	switch ( $field[0] ) {
		case 'select':
			return isset( $field[3]['choices'][ $value ] ) ? $value : (string) $field[2];
		case 'checkbox':
			return '1' === $value ? '1' : '';
		case 'image':
		case 'int':
			return '' === trim( $value ) ? '' : (string) absint( $value );
		case 'number':
			return is_numeric( $value ) ? (string) max( 0, round( (float) $value, 2 ) ) : '';
		case 'color':
			return (string) sanitize_hex_color( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'html':
			return wp_kses_post( $value );
		case 'url':
			return esc_url_raw( $value );
		case 'email':
			return sanitize_email( $value );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Save the box of the post being saved.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function tales2tiles_save_field_box( $post_id, $post ) {
	$box   = tales2tiles_box_for( $post );
	$nonce = 'tales2tiles_' . $box . '_nonce';
	if (
		! $box
		|| ! isset( $_POST[ $nonce ], $_POST[ 'tt_' . $box ] )
		|| ! wp_verify_nonce( sanitize_key( $_POST[ $nonce ] ), 'tales2tiles_' . $box )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! current_user_can( 'edit_post', $post_id )
		|| ! current_user_can( tales2tiles_field_boxes()[ $box ]['cap'] ?? 'edit_posts' )
	) {
		return;
	}
	$input = wp_unslash( (array) $_POST[ 'tt_' . $box ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
	$clean = array();
	foreach ( tales2tiles_field_map( $box ) as $key => $field ) {
		if ( 'list' !== $field[0] ) {
			$clean[ $key ] = tales2tiles_sanitize_field( $field, $input[ $key ] ?? '' );
			continue;
		}
		$clean[ $key ] = array();
		foreach ( (array) ( $input[ $key ] ?? array() ) as $row ) { // POST keeps the on-screen order.
			$values = array();
			foreach ( $field[3]['columns'] as $c => $column ) {
				$values[] = tales2tiles_sanitize_field( $column, $row[ $c ] ?? '' );
			}
			if ( '' !== $values[0] ) {
				$clean[ $key ][] = $values;
			}
		}
	}
	update_post_meta( $post_id, tales2tiles_field_boxes()[ $box ]['meta'], $clean );
}
add_action( 'save_post', 'tales2tiles_save_field_box', 10, 2 );
