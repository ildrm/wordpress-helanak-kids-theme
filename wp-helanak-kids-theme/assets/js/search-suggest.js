/** Accessible search combobox with request sequencing and dismissal cancellation. */
( function () {
	'use strict';
	var settings = window.helanakSettings || {};
	var input = document.getElementById( 'helanak-search-input' );
	if ( ! input || ! settings.ajaxUrl ) { return; }
	var wrap = input.closest( '.helanak-header__search' );
	var list = document.createElement( 'ul' );
	list.id = 'helanak-search-results'; list.className = 'helanak-search-suggest'; list.hidden = true;
	list.setAttribute( 'role', 'listbox' ); list.setAttribute( 'aria-label', settings.suggestionsLabel || 'Search suggestions' );
	wrap.appendChild( list ); wrap.classList.add( 'helanak-header__search--suggest' );
	var status = document.createElement( 'span' ); status.className = 'screen-reader-text'; status.setAttribute( 'role', 'status' ); wrap.appendChild( status );
	input.setAttribute( 'role', 'combobox' ); input.setAttribute( 'autocomplete', 'off' ); input.setAttribute( 'aria-autocomplete', 'list' ); input.setAttribute( 'aria-controls', list.id ); input.setAttribute( 'aria-expanded', 'false' );
	var sequence = 0;
	var timer = null;
	var pending = null;
	var options = [];
	var selected = -1;
	function close() {
		sequence++; window.clearTimeout( timer ); if ( pending ) { pending.abort(); pending = null; }
		list.hidden = true; list.innerHTML = ''; status.textContent = ''; options = []; selected = -1;
		input.setAttribute( 'aria-expanded', 'false' ); input.removeAttribute( 'aria-activedescendant' ); wrap.removeAttribute( 'aria-busy' );
	}
	function select( index ) {
		selected = index;
		options.forEach( function ( option, i ) { option.setAttribute( 'aria-selected', i === index ? 'true' : 'false' ); } );
		if ( options[ index ] ) { input.setAttribute( 'aria-activedescendant', options[ index ].id ); options[ index ].scrollIntoView( { block: 'nearest' } ); }
		else { input.removeAttribute( 'aria-activedescendant' ); }
	}
	function search() {
		var term = input.value.trim(); close(); if ( term.length < 2 ) { return; }
		var current = sequence;
		timer = window.setTimeout( function () {
			var body = new window.URLSearchParams(); body.set( 'action', 'helanak_search_suggest' ); body.set( 'nonce', settings.searchSuggestNonce || '' ); body.set( 'term', term );
			pending = window.AbortController ? new window.AbortController() : null; wrap.setAttribute( 'aria-busy', 'true' );
			window.fetch( settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body, signal: pending ? pending.signal : undefined } ).then( function ( response ) { return response.json(); } ).then( function ( json ) {
				if ( current !== sequence || input.value.trim() !== term ) { return; }
				wrap.removeAttribute( 'aria-busy' ); pending = null;
				if ( ! json || ! json.success || ! json.data || ! json.data.html ) { close(); return; }
				list.innerHTML = json.data.html;
				options = Array.prototype.slice.call( list.querySelectorAll( 'a' ) );
				options.forEach( function ( option, index ) {
					option.parentNode.setAttribute( 'role', 'presentation' ); option.id = 'helanak-search-option-' + index; option.setAttribute( 'role', 'option' ); option.setAttribute( 'aria-selected', 'false' ); option.tabIndex = -1;
				} );
				if ( ! options.length ) {
					Array.prototype.forEach.call( list.children, function ( item ) { item.setAttribute( 'role', 'option' ); item.setAttribute( 'aria-disabled', 'true' ); item.setAttribute( 'aria-selected', 'false' ); } );
					status.textContent = list.textContent;
				}
				list.hidden = false; input.setAttribute( 'aria-expanded', 'true' );
			} ).catch( function ( error ) { if ( current === sequence && ( ! error || error.name !== 'AbortError' ) ) { close(); } } );
		}, 250 );
	}
	input.addEventListener( 'input', search );
	input.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' ) { close(); return; }
		if ( list.hidden || ! options.length ) { return; }
		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			event.preventDefault(); select( event.key === 'ArrowDown' ? ( selected + 1 ) % options.length : ( selected <= 0 ? options.length - 1 : selected - 1 ) );
		} else if ( event.key === 'Enter' && selected >= 0 && ! event.ctrlKey && ! event.metaKey ) { event.preventDefault(); options[ selected ].click(); }
	} );
	wrap.addEventListener( 'focusout', function () { window.setTimeout( function () { if ( ! wrap.contains( document.activeElement ) ) { close(); } }, 0 ); } );
	wrap.addEventListener( 'submit', close );
	document.addEventListener( 'click', function ( event ) { if ( ! wrap.contains( event.target ) ) { close(); } } );
} )();
