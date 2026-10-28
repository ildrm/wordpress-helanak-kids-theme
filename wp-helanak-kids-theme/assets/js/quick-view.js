/** Shared, cancellable product preview and comparison requests. */
( function () {
	'use strict';
	var settings = window.helanakSettings || {};
	var dialog = null;
	var pending = null;
	var requestId = 0;
	function cancel() { requestId++; if ( pending ) { pending.abort(); pending = null; } }
	function buildDialog() {
		var el = document.createElement( 'div' );
		el.className = 'helanak-modal'; el.hidden = true; el.tabIndex = -1;
		el.setAttribute( 'role', 'dialog' ); el.setAttribute( 'aria-modal', 'true' );
		el.innerHTML = '<div class="helanak-modal__backdrop" data-helanak-modal-close></div><div class="helanak-modal__panel"><button type="button" class="helanak-modal__close" data-helanak-modal-close><span aria-hidden="true">&times;</span></button><div class="helanak-modal__body"></div></div>';
		el.querySelector( 'button' ).setAttribute( 'aria-label', settings.closeLabel || 'Close' );
		document.body.appendChild( el ); return el;
	}
	function open( html, trigger, label ) {
		if ( ! dialog ) { dialog = buildDialog(); }
		var body = dialog.querySelector( '.helanak-modal__body' );
		body.innerHTML = html;
		var title = body.querySelector( 'h1, h2, h3' );
		if ( title ) { title.id = 'helanak-modal-title'; dialog.setAttribute( 'aria-labelledby', title.id ); dialog.removeAttribute( 'aria-label' ); }
		else { dialog.removeAttribute( 'aria-labelledby' ); dialog.setAttribute( 'aria-label', label || settings.quickViewLabel || 'Quick view' ); }
		window.helanakDialog.open( dialog, trigger );
		if ( window.jQuery && window.jQuery.fn.wc_variation_form ) {
			window.jQuery( body ).find( 'form.variations_form' ).each( function () { window.jQuery( this ).wc_variation_form(); } );
		}
		document.dispatchEvent( new window.CustomEvent( 'helanak:contentupdated' ) );
	}
	function message( text, status ) { var p = document.createElement( 'p' ); p.className = 'helanak-modal__message'; p.setAttribute( 'role', status ? 'status' : 'alert' ); p.textContent = text; return p.outerHTML; }
	function load( action, values, trigger, label ) {
		cancel();
		open( message( settings.loadingLabel || 'Loading…', true ), trigger, label );
		var current = requestId;
		var body = new window.URLSearchParams();
		body.set( 'action', action );
		Object.keys( values ).forEach( function ( key ) { body.set( key, values[ key ] ); } );
		pending = window.AbortController ? new window.AbortController() : null;
		dialog.setAttribute( 'aria-busy', 'true' );
		window.fetch( settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body, signal: pending ? pending.signal : undefined } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( json ) {
				if ( current !== requestId || ! dialog || dialog.hidden ) { return; }
				dialog.removeAttribute( 'aria-busy' ); pending = null;
				if ( json && json.success && json.data && json.data.html ) { open( json.data.html, trigger, label ); }
				else { open( message( json && json.data && json.data.message ? json.data.message : settings.loadErrorLabel || 'Unable to load this product. Please try again.', false ), trigger, label ); }
			} ).catch( function ( error ) {
				if ( current !== requestId || ( error && error.name === 'AbortError' ) || ! dialog || dialog.hidden ) { return; }
				dialog.removeAttribute( 'aria-busy' ); pending = null;
				open( message( settings.loadErrorLabel || 'Unable to load this product. Please try again.', false ), trigger, label );
			} );
	}
	window.helanakOpenModal = open;
	window.helanakLoadModal = load;
	document.addEventListener( 'helanak:dialogclosed', function ( event ) {
		if ( event.detail.dialog === dialog ) { cancel(); dialog.removeAttribute( 'aria-busy' ); dialog.querySelector( '.helanak-modal__body' ).innerHTML = ''; }
	} );
	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) { return; }
		if ( event.target.closest( '[data-helanak-modal-close]' ) ) { window.helanakDialog.close(); return; }
		var trigger = event.target.closest( '[data-helanak-quick-view]' );
		if ( ! trigger || ! trigger.getAttribute( 'data-product-id' ) ) { return; }
		event.preventDefault();
		load( 'helanak_quick_view', { nonce: settings.quickViewNonce || '', product_id: trigger.getAttribute( 'data-product-id' ) }, trigger, settings.quickViewLabel || 'Quick view' );
	} );
} )();
