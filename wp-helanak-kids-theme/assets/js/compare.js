/**
 * Product compare: maintains a localStorage selection (hln-compare, max 3 —
 * same hln-* convention as the wishlist) toggled by the product cards'
 * [data-helanak-compare-toggle] buttons, shows a floating "Compare (n)" pill
 * once 2+ products are picked, and fetches the comparison table from
 * Helanak_Compare's AJAX endpoint into the shared quick-view modal shell.
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'hln-compare';
	var MAX = 3;
	var settings = window.helanakSettings || {};
	var pill = null;

	function read() {
		try {
			var raw = window.localStorage.getItem( STORAGE_KEY );
			var list = raw ? JSON.parse( raw ) : [];
			return Array.isArray( list ) ? list.map( String ) : [];
		} catch ( e ) {
			return [];
		}
	}

	function write( list ) {
		try {
			window.localStorage.setItem( STORAGE_KEY, JSON.stringify( list ) );
		} catch ( e ) {
			// localStorage unavailable — compare selection just won't persist.
		}
	}

	function buildPill() {
		var el = document.createElement( 'button' );
		el.type = 'button';
		el.className = 'helanak-compare-pill';
		el.hidden = true;
		el.setAttribute( 'data-helanak-compare-open', '' );
		document.body.appendChild( el );
		return el;
	}

	function syncUi() {
		var list = read();

		document.querySelectorAll( '[data-helanak-compare-toggle]' ).forEach( function ( btn ) {
			var active = -1 !== list.indexOf( String( btn.getAttribute( 'data-product-id' ) ) );
			btn.classList.toggle( 'is-active', active );
			btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );

		if ( ! pill ) {
			pill = buildPill();
		}
		if ( list.length >= 2 ) {
			pill.textContent = ( settings.compareLabel || 'Compare' ) + ' (' + list.length + ')';
			pill.hidden = false;
		} else {
			pill.hidden = true;
		}
	}

	function toggle( id ) {
		var list = read();
		var index = list.indexOf( id );
		if ( -1 !== index ) {
			list.splice( index, 1 );
		} else {
			list.push( id );
			if ( list.length > MAX ) {
				list.shift(); // oldest selection drops out, newest stays
			}
		}
		write( list );
		syncUi();
	}

	function openCompare() {
		var list = read();
		if ( list.length < 2 ) {
			return;
		}
		var body = new window.URLSearchParams();
		body.set( 'action', 'helanak_compare' );
		body.set( 'nonce', settings.compareNonce || '' );
		body.set( 'product_ids', list.join( ',' ) );

		window
			.fetch( settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( json && json.success && json.data && json.data.html && window.helanakOpenModal ) {
					window.helanakOpenModal( json.data.html );
				}
			} )
			.catch( function () {
				// Network hiccup — the pill stays, the shopper can retry.
			} );
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) {
			return;
		}
		var openTrigger = event.target.closest( '[data-helanak-compare-open]' );
		if ( openTrigger ) {
			openCompare();
			return;
		}
		var toggleTrigger = event.target.closest( '[data-helanak-compare-toggle]' );
		if ( ! toggleTrigger ) {
			return;
		}
		event.preventDefault();
		var id = toggleTrigger.getAttribute( 'data-product-id' );
		if ( id ) {
			toggle( String( id ) );
		}
	} );

	syncUi();
} )();
