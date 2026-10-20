/**
 * Meta box controls for inc/fields.php: image pickers and repeatable rows.
 * Row order on screen is the saved order.
 */
let ttRowIndex = Date.now(); // Unique input names for new rows.

document.addEventListener( 'click', ( e ) => {
	const button = e.target.closest( 'button' );
	if ( ! button ) {
		return;
	}
	const image = button.closest( '.tt-image' );
	const row = button.closest( '.tt-row' );

	if ( image && button.matches( '.tt-pick' ) ) {
		const frame = wp.media( { library: { type: 'image' }, multiple: false } );
		frame.on( 'select', () => {
			const file = frame.state().get( 'selection' ).first().toJSON();
			image.querySelector( 'input' ).value = file.id;
			image.querySelector( 'img' ).src = file.sizes?.medium?.url || file.url;
		} );
		frame.open();
	} else if ( image && button.matches( '.tt-reset' ) ) {
		image.querySelector( 'input' ).value = '';
		image.querySelector( 'img' ).src = image.dataset.fallback;
	} else if ( button.matches( '.tt-pick-gallery' ) ) {
		// Several images; the order picked is the order shown on the product page.
		const box = button.closest( '.tt-gallery' );
		const input = box.querySelector( 'input' );
		const frame = wp.media( { library: { type: 'image' }, multiple: 'add' } );
		frame.on( 'open', () => {
			const selection = frame.state().get( 'selection' );
			input.value.split( ',' ).filter( Boolean ).forEach( ( id ) => selection.add( wp.media.attachment( id ) ) );
		} );
		frame.on( 'select', () => {
			const files = frame.state().get( 'selection' ).toJSON();
			input.value = files.map( ( f ) => f.id ).join( ',' );
			box.querySelector( '.tt-gallery-thumbs' ).replaceChildren( ...files.map( ( f ) => Object.assign( document.createElement( 'img' ), { src: f.sizes?.thumbnail?.url || f.url, alt: '' } ) ) );
		} );
		frame.open();
	} else if ( button.matches( '.tt-clear-gallery' ) ) {
		const box = button.closest( '.tt-gallery' );
		box.querySelector( 'input' ).value = '';
		box.querySelector( '.tt-gallery-thumbs' ).replaceChildren();
	} else if ( button.matches( '.tt-add' ) ) {
		const list = button.closest( '.tt-list' );
		const rows = list.querySelector( '.tt-rows' );
		rows.insertAdjacentHTML( 'beforeend', list.querySelector( 'template' ).innerHTML.replaceAll( '__i__', ttRowIndex++ ) );
		rows.lastElementChild.querySelector( 'input, textarea' ).focus();
	} else if ( row && button.matches( '.tt-remove' ) ) {
		row.remove();
	} else if ( row && button.matches( '.tt-up' ) && row.previousElementSibling ) {
		row.after( row.previousElementSibling );
	}
} );
