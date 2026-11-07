/** Accessible comparison selection, with removal on cards and in the dialog. */
( function () {
	'use strict';
	var STORAGE_KEY = 'hln-compare';
	var MAX = 3;
	var settings = window.helanakSettings || {};
	var pill = null;
	var notice = null;
	var noticeTimer = null;
	var memory = [];
	var storageAvailable = true;
	var comparing = false;
	var requested = [];
	var openTrigger = null;
	var lastCard = null;
	var focusAfterLoad = false;

	function normalize( value ) {
		return Array.isArray( value ) ? value.map( String ).filter( function ( id, index, list ) {
			return /^[1-9][0-9]*$/.test( id ) && id.length <= 15 && list.indexOf( id ) === index;
		} ).slice( 0, MAX ) : [];
	}
	function read() {
		if ( storageAvailable ) {
			try { memory = normalize( JSON.parse( window.localStorage.getItem( STORAGE_KEY ) || '[]' ) ); }
			catch ( error ) { storageAvailable = false; }
		}
		return memory.slice();
	}
	function write( list ) {
		memory = normalize( list );
		if ( storageAvailable ) {
			try { window.localStorage.setItem( STORAGE_KEY, JSON.stringify( memory ) ); }
			catch ( error ) { storageAvailable = false; }
		}
	}
	function announce( text ) {
		if ( ! notice ) {
			notice = document.createElement( 'p' );
			notice.className = 'helanak-compare-feedback';
			notice.setAttribute( 'role', 'status' );
			document.body.appendChild( notice );
		}
		window.clearTimeout( noticeTimer );
		notice.textContent = text;
		notice.hidden = false;
		noticeTimer = window.setTimeout( function () { notice.hidden = true; }, 5000 );
	}
	function syncUi() {
		var list = read();
		document.querySelectorAll( '[data-helanak-compare-toggle]' ).forEach( function ( button ) {
			var selected = list.indexOf( String( button.getAttribute( 'data-product-id' ) ) ) !== -1;
			var label = selected ? settings.compareRemoveLabel || 'Remove from comparison' : settings.compareAddLabel || 'Add to compare';
			var name = button.getAttribute( 'data-product-name' );
			button.classList.toggle( 'is-active', selected );
			button.setAttribute( 'aria-pressed', selected ? 'true' : 'false' );
			button.setAttribute( 'aria-label', label + ( name ? ': ' + name : '' ) );
			button.setAttribute( 'title', label );
		} );
		if ( ! pill ) {
			pill = document.createElement( 'button' );
			pill.type = 'button';
			pill.className = 'helanak-compare-pill';
			pill.setAttribute( 'data-helanak-compare-open', '' );
			pill.setAttribute( 'aria-haspopup', 'dialog' );
			document.body.appendChild( pill );
		}
		var template = list.length === 1 ? settings.compareCountOne || 'Compare (%d product)' : settings.compareCountMany || 'Compare (%d products)';
		pill.textContent = template.replace( '%d', String( list.length ) );
		pill.hidden = ! list.length;
	}
	function restoreTarget() {
		var target = lastCard && lastCard.isConnected ? lastCard : document.querySelector( '[data-helanak-compare-toggle]' );
		if ( target && window.helanakDialog && window.helanakDialog.setReturnFocus ) { window.helanakDialog.setReturnFocus( target ); }
	}
	function openCompare( trigger ) {
		if ( ! window.helanakLoadModal ) { return; }
		comparing = true;
		requested = read();
		if ( trigger ) { openTrigger = trigger; }
		window.helanakLoadModal( 'helanak_compare', { nonce: settings.compareNonce || '', product_ids: requested.join( ',' ) }, openTrigger, settings.compareLabel || 'Compare' );
		if ( ! requested.length ) { restoreTarget(); }
	}
	function remove( id, refresh ) {
		var list = read().filter( function ( selected ) { return selected !== id; } );
		write( list );
		syncUi();
		if ( refresh ) { focusAfterLoad = true; openCompare(); }
		else { announce( settings.compareRemovedLabel || 'Product removed from comparison.' ); }
	}
	function toggle( id, trigger ) {
		var list = read();
		lastCard = trigger;
		if ( list.indexOf( id ) !== -1 ) { remove( id, false ); return; }
		if ( list.length === MAX ) {
			announce( settings.compareLimitLabel || 'You can compare up to 3 products. Remove a product first.' );
			return;
		}
		list.push( id );
		write( list );
		syncUi();
		announce( settings.compareAddedLabel || 'Product added to comparison.' );
	}
	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) { return; }
		var close = event.target.closest( '[data-helanak-compare-close]' );
		if ( close ) { event.preventDefault(); window.helanakDialog.close(); return; }
		var removeButton = event.target.closest( '[data-helanak-compare-remove]' );
		if ( removeButton ) { event.preventDefault(); remove( String( removeButton.getAttribute( 'data-product-id' ) ), true ); return; }
		var openButton = event.target.closest( '[data-helanak-compare-open]' );
		if ( openButton ) { event.preventDefault(); focusAfterLoad = false; openCompare( openButton ); return; }
		var toggleButton = event.target.closest( '[data-helanak-compare-toggle]' );
		if ( ! toggleButton ) { return; }
		event.preventDefault();
		var id = toggleButton.getAttribute( 'data-product-id' );
		if ( id && /^[1-9][0-9]*$/.test( id ) ) { toggle( String( id ), toggleButton ); }
	} );
	document.addEventListener( 'helanak:contentupdated', function () {
		var content = comparing ? document.querySelector( '.helanak-modal:not([hidden]) [data-helanak-compare-ids]' ) : null;
		if ( content ) {
			var valid = normalize( ( content.getAttribute( 'data-helanak-compare-ids' ) || '' ).split( ',' ) );
			var list = read().filter( function ( id ) { return requested.indexOf( id ) === -1 || valid.indexOf( id ) !== -1; } );
			write( list );
			if ( ! list.length ) { restoreTarget(); }
			if ( focusAfterLoad ) {
				var next = content.querySelector( '[data-helanak-compare-remove], [data-helanak-compare-close]' );
				if ( next ) { next.focus(); }
				focusAfterLoad = false;
			}
		}
		syncUi();
	} );
	document.addEventListener( 'helanak:dialogclosed', function ( event ) {
		if ( event.detail.dialog.classList.contains( 'helanak-modal' ) ) { comparing = false; focusAfterLoad = false; }
	} );
	window.addEventListener( 'storage', function ( event ) {
		if ( event.key === STORAGE_KEY || event.key === null ) {
			syncUi();
			if ( comparing ) { focusAfterLoad = true; openCompare(); }
		}
	} );
	write( read() );
	syncUi();
} )();
