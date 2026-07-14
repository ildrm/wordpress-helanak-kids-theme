/**
 * Live search dropdown for the header search pill (#helanak-search-input):
 * debounced fetch to Helanak_Search_Suggest's endpoint, results rendered in
 * a listbox under the pill. Escape/click-outside closes; ArrowDown/ArrowUp
 * move through results. No-ops when the input isn't on the page.
 */
( function () {
	'use strict';

	var settings = window.helanakSettings || {};
	var input = document.getElementById( 'helanak-search-input' );
	if ( ! input || ! settings.ajaxUrl ) {
		return;
	}

	var wrap = input.closest( '.helanak-header__search' );
	if ( ! wrap ) {
		return;
	}

	var list = document.createElement( 'ul' );
	list.className = 'helanak-search-suggest';
	list.setAttribute( 'role', 'listbox' );
	list.hidden = true;
	wrap.appendChild( list );
	wrap.classList.add( 'helanak-header__search--suggest' );
	input.setAttribute( 'autocomplete', 'off' );
	input.setAttribute( 'aria-expanded', 'false' );

	var debounceTimer = null;
	var lastTerm = '';

	function close() {
		list.hidden = true;
		list.innerHTML = '';
		input.setAttribute( 'aria-expanded', 'false' );
	}

	function open( html ) {
		if ( ! html ) {
			close();
			return;
		}
		list.innerHTML = html;
		list.hidden = false;
		input.setAttribute( 'aria-expanded', 'true' );
	}

	function fetchSuggestions( term ) {
		var body = new window.URLSearchParams();
		body.set( 'action', 'helanak_search_suggest' );
		body.set( 'nonce', settings.searchSuggestNonce || '' );
		body.set( 'term', term );

		window
			.fetch( settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				// A stale response for an older term must not clobber the
				// dropdown for what's currently typed.
				if ( term !== lastTerm ) {
					return;
				}
				open( json && json.success && json.data ? json.data.html : '' );
			} )
			.catch( close );
	}

	input.addEventListener( 'input', function () {
		var term = input.value.trim();
		lastTerm = term;
		window.clearTimeout( debounceTimer );
		if ( term.length < 2 ) {
			close();
			return;
		}
		debounceTimer = window.setTimeout( function () {
			fetchSuggestions( term );
		}, 250 );
	} );

	input.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			close();
			return;
		}
		if ( 'ArrowDown' === event.key && ! list.hidden ) {
			event.preventDefault();
			var first = list.querySelector( 'a' );
			if ( first ) {
				first.focus();
			}
		}
	} );

	list.addEventListener( 'keydown', function ( event ) {
		var links = Array.prototype.slice.call( list.querySelectorAll( 'a' ) );
		var index = links.indexOf( document.activeElement );
		if ( 'ArrowDown' === event.key && index > -1 && index < links.length - 1 ) {
			event.preventDefault();
			links[ index + 1 ].focus();
		} else if ( 'ArrowUp' === event.key ) {
			event.preventDefault();
			if ( index > 0 ) {
				links[ index - 1 ].focus();
			} else {
				input.focus();
			}
		} else if ( 'Escape' === event.key ) {
			close();
			input.focus();
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! wrap.contains( event.target ) ) {
			close();
		}
	} );
} )();
