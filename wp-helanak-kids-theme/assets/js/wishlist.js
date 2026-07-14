/**
 * Client-side wishlist: localStorage-only (no account/DB backing), matching
 * the original prototype's hln-prefs/hln-cart persistence pattern (see
 * CLAUDE.md). Toggled from any [data-helanak-wishlist-toggle][data-product-id]
 * button (woocommerce/content-product.php). The header wishlist link
 * ([data-helanak-wishlist-nav], header.php) is a real fallback href to the
 * shop page; progressively enhanced here to append the current wishlist as
 * ?helanak_wishlist=1,2,3, which archive-product.php filters by server-side
 * (see inc/class-archive-filters.php).
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'hln-wishlist';

	function readList() {
		try {
			var raw = window.localStorage.getItem( STORAGE_KEY );
			var list = raw ? JSON.parse( raw ) : [];
			return Array.isArray( list ) ? list.map( String ) : [];
		} catch ( e ) {
			return [];
		}
	}

	function writeList( list ) {
		try {
			window.localStorage.setItem( STORAGE_KEY, JSON.stringify( list ) );
		} catch ( e ) {
			// localStorage unavailable (private mode, quota) — wishlist just won't persist.
		}
	}

	function syncButtons( list ) {
		document.querySelectorAll( '[data-helanak-wishlist-toggle]' ).forEach( function ( btn ) {
			var active = list.indexOf( btn.getAttribute( 'data-product-id' ) ) !== -1;
			btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			btn.classList.toggle( 'is-active', active );
			btn.textContent = active ? '♥' : '♡';
		} );
	}

	function syncCount( list ) {
		document.querySelectorAll( '[data-helanak-wishlist-count]' ).forEach( function ( badge ) {
			badge.textContent = String( list.length );
			badge.hidden = 0 === list.length;
		} );
	}

	function sync( list ) {
		syncButtons( list );
		syncCount( list );
	}

	sync( readList() );

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) {
			return;
		}

		var toggle = event.target.closest( '[data-helanak-wishlist-toggle]' );
		if ( toggle ) {
			var id = toggle.getAttribute( 'data-product-id' );
			if ( ! id ) {
				return;
			}
			var list = readList();
			var index = list.indexOf( id );
			if ( -1 === index ) {
				list.push( id );
			} else {
				list.splice( index, 1 );
			}
			writeList( list );
			sync( list );
			return;
		}

		var navLink = event.target.closest( '[data-helanak-wishlist-nav]' );
		if ( navLink ) {
			var ids = readList();
			if ( ! ids.length ) {
				return;
			}
			event.preventDefault();
			var url = new URL( navLink.href, window.location.origin );
			url.searchParams.set( 'helanak_wishlist', ids.join( ',' ) );
			window.location.href = url.toString();
		}
	} );
} )();
