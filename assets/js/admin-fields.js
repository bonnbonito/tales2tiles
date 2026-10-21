/**
 * Meta box controls for inc/fields.php: image pickers, focus points and repeatable rows.
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

// Focus point ('focus' field): click or drag on the photo; the previews crop it like the site does.
document.querySelectorAll( '.tt-focus' ).forEach( ( box ) => {
	const input = box.querySelector( 'input' );
	const pick = box.querySelector( '.tt-focus-pick' );
	const dot = box.querySelector( '.tt-focus-dot' );
	const source = document.getElementById( box.dataset.image )?.closest( '.tt-image' )?.querySelector( 'img' );
	const show = () => {
		const [ x, y ] = input.value.split( ' ' );
		dot.style.left = x;
		dot.style.top = y;
		box.querySelectorAll( '.tt-focus-crop img' ).forEach( ( img ) => ( img.style.objectPosition = input.value ) );
	};
	const sync = () => box.querySelectorAll( 'img' ).forEach( ( img ) => ( img.src = source?.src || '' ) );
	const set = ( e ) => {
		const r = pick.getBoundingClientRect();
		const pct = ( v ) => Math.round( Math.min( 100, Math.max( 0, v ) ) ) + '%';
		input.value = pct( ( ( e.clientX - r.left ) / r.width ) * 100 ) + ' ' + pct( ( ( e.clientY - r.top ) / r.height ) * 100 );
		show();
	};
	pick.addEventListener( 'pointerdown', ( e ) => {
		pick.setPointerCapture( e.pointerId );
		set( e );
		pick.onpointermove = set;
	} );
	pick.addEventListener( 'pointerup', () => ( pick.onpointermove = null ) );
	if ( source ) {
		new MutationObserver( sync ).observe( source, { attributes: true, attributeFilter: [ 'src' ] } ); // A new hero photo was chosen.
	}
	sync();
	show();
} );
