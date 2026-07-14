/**
 * Quick View modal: fetches inner HTML from Helanak_Quick_View's AJAX
 * endpoint (inc/class-quick-view.php) and displays it in a focus-trapped
 * dialog. Triggered by any [data-helanak-quick-view][data-product-id]
 * button — see the "Quick View" toggle in woocommerce/content-product.php.
 */
( function () {
	'use strict';

	var settings = window.helanakSettings || {};
	var dialog = null;
	var lastFocused = null;

	function buildDialog() {
		var el = document.createElement( 'div' );
		el.className = 'helanak-modal';
		el.setAttribute( 'role', 'dialog' );
		el.setAttribute( 'aria-modal', 'true' );
		el.hidden = true;
		el.innerHTML =
			'<div class="helanak-modal__backdrop" data-helanak-modal-close></div>' +
			'<div class="helanak-modal__panel">' +
			'<button type="button" class="helanak-modal__close" data-helanak-modal-close aria-label="' +
			( settings.closeLabel || 'Close' ) +
			'">&times;</button>' +
			'<div class="helanak-modal__body"></div>' +
			'</div>';
		document.body.appendChild( el );
		return el;
	}

	function getFocusable( container ) {
		return Array.prototype.slice.call(
			container.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			)
		);
	}

	function trapFocus( event ) {
		if ( ! dialog || dialog.hidden || 'Tab' !== event.key ) {
			return;
		}
		var focusable = getFocusable( dialog );
		if ( ! focusable.length ) {
			return;
		}
		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function closeDialog() {
		if ( ! dialog || dialog.hidden ) {
			return;
		}
		dialog.hidden = true;
		document.body.classList.remove( 'helanak-modal-open' );
		dialog.querySelector( '.helanak-modal__body' ).innerHTML = '';
		if ( lastFocused ) {
			lastFocused.focus();
		}
	}

	function openDialog( html ) {
		if ( ! dialog ) {
			dialog = buildDialog();
		}
		dialog.querySelector( '.helanak-modal__body' ).innerHTML = html;
		dialog.hidden = false;
		document.body.classList.add( 'helanak-modal-open' );
		var closeButton = dialog.querySelector( '.helanak-modal__close' );
		if ( closeButton ) {
			closeButton.focus();
		}
	}

	function showError( message ) {
		openDialog( '<p class="helanak-modal__error">' + message + '</p>' );
	}

	// Shared modal shell: other features (compare.js) reuse the same
	// focus-trapped dialog instead of building their own.
	window.helanakOpenModal = openDialog;

	function fetchProduct( productId ) {
		var body = new window.URLSearchParams();
		body.set( 'action', 'helanak_quick_view' );
		body.set( 'nonce', settings.quickViewNonce || '' );
		body.set( 'product_id', productId );

		window
			.fetch( settings.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body,
			} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( json && json.success && json.data && json.data.html ) {
					openDialog( json.data.html );
				} else {
					showError( ( json && json.data && json.data.message ) || 'Unable to load this product.' );
				}
			} )
			.catch( function () {
				showError( 'Unable to load this product.' );
			} );
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) {
			return;
		}

		var closeTarget = event.target.closest( '[data-helanak-modal-close]' );
		if ( closeTarget ) {
			closeDialog();
			return;
		}

		var trigger = event.target.closest( '[data-helanak-quick-view]' );
		if ( ! trigger ) {
			return;
		}
		event.preventDefault();
		var productId = trigger.getAttribute( 'data-product-id' );
		if ( ! productId ) {
			return;
		}
		lastFocused = trigger;
		fetchProduct( productId );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			closeDialog();
		}
		trapFocus( event );
	} );
} )();
