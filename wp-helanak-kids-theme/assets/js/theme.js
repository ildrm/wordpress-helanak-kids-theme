/** One dialog controller for the cart, quick view and comparison. */
( function () {
	'use strict';
	var active = null;
	var returnFocus = null;
	var background = [];
	function focusables() {
		if ( ! active ) { return []; }
		return Array.prototype.slice.call( active.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])' ) ).filter( function ( el ) {
			return ! el.closest( '[hidden], [inert]' ) && el.getClientRects().length;
		} );
	}
	function close( restoreFocus ) {
		if ( ! active ) { return; }
		var previous = active;
		active = null;
		previous.hidden = true;
		background.forEach( function ( entry ) { entry.el.inert = entry.inert; } );
		background = [];
		document.body.classList.remove( 'helanak-modal-open' );
		document.dispatchEvent( new window.CustomEvent( 'helanak:dialogclosed', { detail: { dialog: previous } } ) );
		if ( restoreFocus !== false && returnFocus && returnFocus.isConnected && returnFocus.getClientRects().length ) { returnFocus.focus(); }
		returnFocus = null;
	}
	function open( dialog, trigger ) {
		if ( active && active !== dialog ) { close( false ); }
		if ( ! active ) {
			returnFocus = trigger || document.activeElement;
			active = dialog;
			dialog.hidden = false;
			Array.prototype.forEach.call( document.body.children, function ( el ) {
				if ( el === dialog || el.contains( dialog ) || /^(SCRIPT|STYLE|LINK)$/.test( el.tagName ) ) { return; }
				background.push( { el: el, inert: el.inert } );
				el.inert = true;
			} );
		}
		document.body.classList.add( 'helanak-modal-open' );
		var closeButton = dialog.querySelector( 'button[data-helanak-cart-close], button[data-helanak-modal-close]' );
		( closeButton || focusables()[ 0 ] || dialog ).focus();
	}
	document.addEventListener( 'keydown', function ( event ) {
		if ( ! active ) { return; }
		if ( event.key === 'Escape' ) { event.preventDefault(); close(); return; }
		if ( event.key !== 'Tab' ) { return; }
		var items = focusables();
		var first = items[ 0 ];
		var last = items[ items.length - 1 ];
		if ( ! first ) { event.preventDefault(); active.focus(); return; }
		if ( event.shiftKey && ( document.activeElement === first || ! active.contains( document.activeElement ) ) ) {
			event.preventDefault(); last.focus();
		} else if ( ! event.shiftKey && ( document.activeElement === last || ! active.contains( document.activeElement ) ) ) {
			event.preventDefault(); first.focus();
		}
	} );
	document.addEventListener( 'focusin', function ( event ) {
		if ( active && ! active.contains( event.target ) ) { ( focusables()[ 0 ] || active ).focus(); }
	} );
	window.helanakDialog = { open: open, close: close, isOpen: function ( dialog ) { return active === dialog; } };
} )();

( function () {
	'use strict';

	var settings = window.helanakSettings || {};

	function productNameNear( el ) {
		if ( ! el ) {
			return '';
		}
		var card = el.closest( '.helanak-card, .helanak-buy-box, .helanak-quick-view' );
		if ( ! card ) {
			return '';
		}
		var titleEl = card.querySelector( '.helanak-card__title, .product_title, .helanak-quick-view__title' );
		return titleEl ? titleEl.textContent.trim() : '';
	}

	function showToast( name ) {
		var toast = document.createElement( 'div' );
		toast.className = 'helanak-toast';
		toast.setAttribute( 'role', 'status' );

		var check = document.createElement( 'span' );
		check.className = 'helanak-toast__check';
		check.setAttribute( 'aria-hidden', 'true' );
		check.textContent = '✓';
		toast.appendChild( check );

		// Product name stays textContent so nothing from the DOM is ever
		// re-parsed as HTML; only the server-rendered, pre-escaped dog markup
		// (helanakSettings.toastDogHtml) goes through insertAdjacentHTML.
		var text = document.createElement( 'span' );
		text.textContent = ( settings.addedToCartLabel || 'Added to cart' ) + ( name ? ': ' + name : '' );
		toast.appendChild( text );

		if ( settings.toastDogHtml ) {
			var dog = document.createElement( 'span' );
			dog.className = 'helanak-toast__dog';
			dog.setAttribute( 'aria-hidden', 'true' );
			dog.insertAdjacentHTML( 'afterbegin', settings.toastDogHtml );
			toast.appendChild( dog );
		}

		document.body.appendChild( toast );
		window.setTimeout( function () {
			toast.remove();
		}, 2600 );
	}

	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'added_to_cart', function ( event, fragments, cartHash, $button ) {
			showToast( productNameNear( $button && $button.get ? $button.get( 0 ) : null ) );
		} );
	}
	document.body.addEventListener( 'wc-blocks_added_to_cart', function () {
		showToast( '' );
	} );
} )();
/** Navigation and cart remain functional with decorative motion disabled. */
( function () {
	'use strict';
	var toggle = document.querySelector( '[data-helanak-nav-toggle]' );
	var nav = document.getElementById( 'helanak-mobile-nav' );
	function closeNav( restore ) {
		if ( ! nav || nav.hidden ) { return; }
		nav.hidden = true;
		toggle.setAttribute( 'aria-expanded', 'false' );
		if ( restore ) { toggle.focus(); }
	}
	if ( toggle && nav ) {
		toggle.addEventListener( 'click', function () { var open = nav.hidden; nav.hidden = ! open; toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' ); } );
		document.addEventListener( 'keydown', function ( event ) { if ( event.key === 'Escape' ) { closeNav( true ); } } );
		document.addEventListener( 'click', function ( event ) { if ( ! nav.contains( event.target ) && ! toggle.contains( event.target ) ) { closeNav( false ); } } );
		window.addEventListener( 'resize', function () { if ( window.innerWidth > 1024 ) { closeNav( false ); } } );
	}
	var drawer = document.querySelector( '[data-helanak-cart-drawer]' );
	if ( ! drawer || ! window.helanakDialog ) { return; }
	function sync( expanded ) {
		document.querySelectorAll( '[data-helanak-cart-toggle]' ).forEach( function ( el ) { el.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' ); } );
	}
	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) { return; }
		if ( event.target.closest( '[data-helanak-cart-close]' ) ) { window.helanakDialog.close(); return; }
		var trigger = event.target.closest( '[data-helanak-cart-toggle]' );
		if ( ! trigger ) { return; }
		event.preventDefault();
		if ( window.helanakDialog.isOpen( drawer ) ) { window.helanakDialog.close(); } else { closeNav( false ); window.helanakDialog.open( drawer, trigger ); sync( true ); }
	} );
	document.addEventListener( 'helanak:dialogclosed', function ( event ) { if ( event.detail.dialog === drawer ) { sync( false ); } } );
} )();

( function () {
	'use strict';

	var track = document.querySelector( '[data-helanak-worlds-track]' );
	if ( ! track ) {
		return;
	}

	function scrollByAmount( direction ) {
		var card = track.querySelector( '.helanak-world-card' );
		var step = ( card ? card.getBoundingClientRect().width : 180 ) + 18;
		var rtl = window.getComputedStyle( track ).direction === 'rtl';
		var animate = ! window.helanakMotionAllowed || window.helanakMotionAllowed();
		track.scrollBy( { left: direction * step * 2 * ( rtl ? -1 : 1 ), behavior: animate ? 'smooth' : 'auto' } );
	}

	var prevBtn = document.querySelector( '[data-helanak-worlds-prev]' );
	var nextBtn = document.querySelector( '[data-helanak-worlds-next]' );
	if ( prevBtn ) {
		prevBtn.textContent = window.getComputedStyle( track ).direction === 'rtl' ? '›' : '‹';
		prevBtn.addEventListener( 'click', function () {
			scrollByAmount( -1 );
		} );
	}
	if ( nextBtn ) {
		nextBtn.textContent = window.getComputedStyle( track ).direction === 'rtl' ? '‹' : '›';
		nextBtn.addEventListener( 'click', function () {
			scrollByAmount( 1 );
		} );
	}
} )();
/** Bounded, event-driven character motion with a persistent pause control. */
( function () {
	'use strict';
	var settings = window.helanakSettings || {};
	var media = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : null;
	var paused = false;
	try { paused = window.localStorage.getItem( 'helanak-motion' ) === 'paused'; } catch ( error ) { /* Private mode: retain this visit's preference. */ }
	function allowed() { return ! paused && ! ( media && media.matches ) && ! document.hidden; }
	window.helanakMotionAllowed = allowed;
	function applyPreference() {
		document.documentElement.setAttribute( 'data-helanak-motion', allowed() ? 'running' : 'paused' );
		document.querySelectorAll( '[data-helanak-motion-toggle]' ).forEach( function ( button ) {
			button.setAttribute( 'aria-pressed', paused || ( media && media.matches ) ? 'true' : 'false' );
			button.disabled = !! ( media && media.matches );
			var label = button.querySelector( '[data-helanak-motion-label]' );
			if ( label ) { label.textContent = media && media.matches ? settings.motionReducedLabel : paused ? settings.resumeMotionLabel : settings.pauseMotionLabel; }
			button.setAttribute( 'title', media && media.matches ? settings.reduceMotionNote || '' : '' );
		} );
		document.dispatchEvent( new window.CustomEvent( 'helanak:motionchange' ) );
	}
	document.querySelectorAll( '[data-helanak-motion-toggle]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			paused = ! paused;
			try { window.localStorage.setItem( 'helanak-motion', paused ? 'paused' : 'running' ); } catch ( error ) { /* The control still works for this visit. */ }
			applyPreference();
		} );
	} );
	if ( media ) { if ( media.addEventListener ) { media.addEventListener( 'change', applyPreference ); } else { media.addListener( applyPreference ); } }
	document.addEventListener( 'visibilitychange', applyPreference );
	applyPreference();

	// Keep every friend visible; observation controls motion, never artwork.
	var observer = 'IntersectionObserver' in window ? new window.IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) { entry.target.classList.toggle( 'is-offscreen', ! entry.isIntersecting ); if ( entry.isIntersecting ) { entry.target.classList.add( 'is-in-view' ); } } );
	}, { rootMargin: '40px 0px', threshold: 0.01 } ) : null;
	var serial = 0;
	function prepareCharacters() {
		document.querySelectorAll( '.helanak-character--svg, .helanak-hero' ).forEach( function ( el ) {
			if ( el.hasAttribute( 'data-helanak-motion-ready' ) ) { return; }
			el.setAttribute( 'data-helanak-motion-ready', '' );
			el.classList.remove( 'is-awaiting-view' );
			el.style.setProperty( '--char-delay', ( -0.73 * serial++ ).toFixed( 2 ) + 's' );
			el.style.setProperty( '--char-breath-duration', ( 4.4 + Math.random() * 1.8 ).toFixed( 2 ) + 's' );
			if ( observer ) { observer.observe( el ); } else { el.classList.add( 'is-in-view' ); }
		} );
	}
	document.querySelectorAll( '[data-helanak-surprise]' ).forEach( function ( container ) {
		var choices = container.querySelectorAll( '[data-helanak-surprise-choice]' );
		if ( choices.length ) { choices[ Math.floor( Math.random() * choices.length ) ].hidden = false; }
	} );
	prepareCharacters();
	document.addEventListener( 'helanak:contentupdated', prepareCharacters );
	function pulse( el, className, duration ) {
		if ( ! allowed() || ! settings.idleBehaviors || el.classList.contains( 'is-offscreen' ) || el.classList.contains( className ) ) { return; }
		el.classList.add( className );
		window.setTimeout( function () { el.classList.remove( className ); }, duration );
	}
	function celebrate() {
		document.querySelectorAll( '.helanak-character--rabbit, .helanak-character--dog, .helanak-character--fox, .helanak-character--elephant' ).forEach( function ( el ) { pulse( el, 'is-hopping', 950 ); } );
	}
	if ( window.jQuery ) { window.jQuery( document.body ).on( 'added_to_cart', celebrate ); }
	document.body.addEventListener( 'wc-blocks_added_to_cart', celebrate );
	document.addEventListener( 'click', function ( event ) {
		if ( event.target.closest && event.target.closest( '[data-helanak-cart-toggle]' ) ) {
			document.querySelectorAll( '[data-helanak-cart-drawer] .helanak-character' ).forEach( function ( el ) { pulse( el, 'is-waving', 1500 ); } );
		}
	} );
	var greeting = document.querySelector( '[data-helanak-friend-greeting]' );
	function greet( item ) {
		var el = item.querySelector( '.helanak-character' );
		if ( el ) { pulse( el, 'is-playful', 1500 ); }
		if ( greeting ) { greeting.textContent = ( settings.friendHelloLabel || '%s says hello!' ).replace( '%s', item.getAttribute( 'data-friend-name' ) || '' ); }
	}
	document.querySelectorAll( '[data-helanak-greet]' ).forEach( function ( item ) {
		item.addEventListener( 'click', function () { greet( item ); } );
		item.addEventListener( 'pointerenter', function () { var el = item.querySelector( '.helanak-character' ); if ( el ) { pulse( el, 'is-playful', 1500 ); } } );
		item.addEventListener( 'focusin', function () { var el = item.querySelector( '.helanak-character' ); if ( el ) { pulse( el, 'is-playful', 1500 ); } } );
	} );
	var surprise = document.querySelector( '[data-helanak-surprise-friend]' );
	if ( surprise ) { surprise.addEventListener( 'click', function () {
		var choices = Array.prototype.slice.call( document.querySelectorAll( '[data-helanak-greet]' ) ).filter( function ( item ) { var el = item.querySelector( '.helanak-character' ); return el && ! el.classList.contains( 'is-offscreen' ); } );
		if ( choices.length ) { greet( choices[ Math.floor( Math.random() * choices.length ) ] ); }
	} ); }
	var idleTimer = null;
	var lastActivity = 0;
	var sleepers = document.querySelectorAll( '.helanak-character[data-behavior="sleep"]' );
	function wake() {
		window.clearTimeout( idleTimer );
		sleepers.forEach( function ( el ) { el.classList.remove( 'is-sleeping' ); } );
		if ( allowed() && settings.idleBehaviors ) { idleTimer = window.setTimeout( function () { if ( allowed() ) { sleepers.forEach( function ( el ) { el.classList.add( 'is-sleeping' ); } ); } }, 30000 ); }
	}
	function activity() { var now = Date.now(); if ( now - lastActivity < 1000 ) { return; } lastActivity = now; wake(); }
	if ( sleepers.length ) {
		[ 'pointermove', 'keydown', 'touchstart', 'scroll', 'click' ].forEach( function ( name ) { window.addEventListener( name, activity, { passive: true } ); } );
		document.addEventListener( 'helanak:motionchange', wake ); wake();
	}
	var birdHasFlown = false;
	var flybyStage = document.querySelector( '[data-helanak-flyby-stage]' );
	function flyby() {
		if ( birdHasFlown || ! flybyStage || ! allowed() || ! settings.idleBehaviors ) { return; }
		var rect = flybyStage.getBoundingClientRect();
		if ( rect.bottom <= 0 || rect.top >= window.innerHeight ) { return; }
		birdHasFlown = true;
		flybyStage.querySelectorAll( '.helanak-character[data-character="bird"][data-behavior="fly"]' ).forEach( function ( el ) {
			el.classList.add( 'is-flying' );
			window.setTimeout( function () { el.classList.remove( 'is-flying' ); }, 9200 );
		} );
	}
	if ( flybyStage ) {
		if ( 'IntersectionObserver' in window ) {
			var flybyObserver = new window.IntersectionObserver( function ( entries ) {
				if ( entries[ 0 ].isIntersecting ) { flyby(); }
				if ( birdHasFlown ) { flybyObserver.disconnect(); }
			} );
			flybyObserver.observe( flybyStage );
		} else {
			var ticking = false;
			window.addEventListener( 'scroll', function () {
				if ( ticking || birdHasFlown ) { return; }
				ticking = true;
				window.requestAnimationFrame( function () { ticking = false; flyby(); } );
			}, { passive: true } );
		}
		document.addEventListener( 'helanak:motionchange', flyby );
		flyby();
	}
} )();

/** Form feedback is functional and runs after native redirect/hash focus settles. */
( function () {
	'use strict';
	function focusFeedback() {
		var feedback = document.querySelector( '[data-helanak-form-feedback]' );
		if ( ! feedback ) { return; }
		window.requestAnimationFrame( function () { feedback.focus( { preventScroll: true } ); feedback.scrollIntoView( { block: 'center', behavior: 'auto' } ); } );
	}
	if ( document.readyState === 'complete' ) { focusFeedback(); }
	else { window.addEventListener( 'pageshow', focusFeedback, { once: true } ); }
} )();

/** Phone filters collapse without losing server-rendered form state. */
( function () {
	'use strict';
	var toggle = document.querySelector( '[data-helanak-filter-toggle]' );
	var form = document.getElementById( 'helanak-filter-form' );
	if ( ! toggle || ! form ) { return; }
	var phone = window.matchMedia( '(max-width: 782px)' );
	function update() { form.hidden = phone.matches; toggle.setAttribute( 'aria-expanded', phone.matches ? 'false' : 'true' ); }
	toggle.addEventListener( 'click', function () { form.hidden = ! form.hidden; toggle.setAttribute( 'aria-expanded', form.hidden ? 'false' : 'true' ); } );
	if ( phone.addEventListener ) { phone.addEventListener( 'change', update ); } else { phone.addListener( update ); }
	update();
} )();

/** Merchant-authored size tables stay reachable with a keyboard. */
( function () {
	'use strict';
	var regions = document.querySelectorAll( '.woocommerce-Tabs-panel--description, .woocommerce-product-details__short-description' );
	if ( ! regions.length ) { return; }
	var scheduled = false;
	function update() {
		scheduled = false;
		regions.forEach( function ( region ) {
			if ( region.scrollWidth > region.clientWidth + 1 ) {
				region.setAttribute( 'tabindex', '0' );
				region.setAttribute( 'data-helanak-scroll-focus', '' );
			} else if ( region.hasAttribute( 'data-helanak-scroll-focus' ) ) {
				region.removeAttribute( 'tabindex' );
				region.removeAttribute( 'data-helanak-scroll-focus' );
			}
		} );
	}
	function schedule() {
		if ( scheduled ) { return; }
		scheduled = true;
		window.requestAnimationFrame( update );
	}
	window.addEventListener( 'resize', schedule );
	window.addEventListener( 'load', schedule );
	document.addEventListener( 'click', function ( event ) {
		if ( event.target.closest && event.target.closest( '.wc-tabs a' ) ) { schedule(); }
	} );
	schedule();
} )();
