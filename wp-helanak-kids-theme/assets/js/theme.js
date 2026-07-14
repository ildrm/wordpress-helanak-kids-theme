/**
 * Site-wide frontend behavior, split into independent IIFEs:
 *  1. Add-to-cart toast (functional, always active).
 *  2. Mobile nav toggle (functional, always active).
 *  3. Mini-cart drawer toggle (functional, always active; no-ops if the
 *     "Floating mini-cart button" section is disabled and the drawer markup
 *     isn't on the page).
 *  4. Context-aware character behaviors (spec 8.3): add-to-cart hop,
 *     mini-cart-open wave, scroll-triggered bird fly-past, idle-sleep.
 *     Event-driven only (WooCommerce events, click/scroll listeners gated
 *     with rAF, a re-armed idle setTimeout) — no canvas, no setInterval
 *     polling loop, per spec's hard performance rule. Gated by the
 *     "Context-aware idle behaviors" Customizer toggle (helanak_character_idle,
 *     exposed here as settings.idleBehaviors) — not settings.ambientEffects,
 *     which instead controls the separate falling petals/leaves/snow
 *     particle layer (pure CSS, see theme.css). Fully skipped when the
 *     visitor has prefers-reduced-motion; the mascots still render (see
 *     class-character-engine.php) as static, motionless art.
 */
/**
 * Add-to-cart toast: independent of the reduced-motion/idle-behaviors gate
 * below, since it's a functional notification (its entrance animation is
 * separately covered by the global prefers-reduced-motion kill switch in
 * theme.css), not a decorative character behavior.
 */
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

/**
 * Mobile nav toggle: shows/hides the #helanak-mobile-nav dropdown built in
 * header.php (a second <nav>, not a JS-cloned copy of the desktop menu, so
 * no-JS visitors below the breakpoint still get the [hidden] state removed
 * by nothing — acceptable degradation, matching the header icon's own
 * progressive-enhancement pattern for the wishlist link).
 */
( function () {
	'use strict';

	var toggle = document.querySelector( '[data-helanak-nav-toggle]' );
	var nav = document.getElementById( 'helanak-mobile-nav' );
	if ( ! toggle || ! nav ) {
		return;
	}

	toggle.addEventListener( 'click', function () {
		var isOpen = ! nav.hidden;
		nav.hidden = isOpen;
		toggle.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
	} );
} )();

/**
 * Floating/header mini-cart drawer toggle. Independent of the idle-behavior
 * gate below since it's core navigation UI. The drawer markup (rendered by
 * footer.php) is optional — gated by the "Floating mini-cart button" section
 * toggle — so this IIFE no-ops when it's absent, letting
 * [data-helanak-cart-toggle] links (e.g. the header cart icon) fall back to
 * their href (the cart page) instead.
 */
( function () {
	'use strict';

	var drawer = document.querySelector( '[data-helanak-cart-drawer]' );
	if ( ! drawer ) {
		return;
	}

	var lastFocused = null;

	function setToggleState( expanded ) {
		document.querySelectorAll( '[data-helanak-cart-toggle]' ).forEach( function ( el ) {
			el.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
		} );
	}

	function openDrawer( trigger ) {
		lastFocused = trigger || document.activeElement;
		drawer.hidden = false;
		document.body.classList.add( 'helanak-modal-open' );
		setToggleState( true );
		var closeBtn = drawer.querySelector( '[data-helanak-cart-close]' );
		if ( closeBtn ) {
			closeBtn.focus();
		}
	}

	function closeDrawer() {
		drawer.hidden = true;
		document.body.classList.remove( 'helanak-modal-open' );
		setToggleState( false );
		if ( lastFocused && lastFocused.focus ) {
			lastFocused.focus();
		}
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) {
			return;
		}
		// The dedicated close button sits INSIDE the drawer, so the
		// click-outside branch below never reaches it — it needs its own
		// branch or it silently does nothing.
		if ( event.target.closest( '[data-helanak-cart-close]' ) ) {
			closeDrawer();
			return;
		}
		var toggle = event.target.closest( '[data-helanak-cart-toggle]' );
		if ( toggle ) {
			event.preventDefault();
			if ( drawer.hidden ) {
				openDrawer( toggle );
			} else {
				closeDrawer();
			}
			return;
		}
		if ( ! drawer.hidden && ! event.target.closest( '.helanak-mini-cart-drawer' ) ) {
			closeDrawer();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && ! drawer.hidden ) {
			closeDrawer();
		}
	} );
} )();

/**
 * "Shop by world" category slider prev/next buttons: scrolls the track by
 * roughly one card-width per click. Native scroll-snap (see .helanak-worlds__track
 * in theme.css) handles settling on a card boundary, so this only needs to
 * nudge scrollLeft, not compute exact snap positions.
 */
( function () {
	'use strict';

	var track = document.querySelector( '[data-helanak-worlds-track]' );
	if ( ! track ) {
		return;
	}

	function scrollByAmount( direction ) {
		var card = track.querySelector( '.helanak-world-card' );
		var step = ( card ? card.getBoundingClientRect().width : 180 ) + 18;
		track.scrollBy( { left: direction * step * 2, behavior: 'smooth' } );
	}

	var prevBtn = document.querySelector( '[data-helanak-worlds-prev]' );
	var nextBtn = document.querySelector( '[data-helanak-worlds-next]' );
	if ( prevBtn ) {
		prevBtn.addEventListener( 'click', function () {
			scrollByAmount( -1 );
		} );
	}
	if ( nextBtn ) {
		nextBtn.addEventListener( 'click', function () {
			scrollByAmount( 1 );
		} );
	}
} )();

( function () {
	'use strict';

	var settings = window.helanakSettings || {};
	var prefersReducedMotion =
		window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( prefersReducedMotion || ! settings.idleBehaviors ) {
		return;
	}

	var enabledCharacters = settings.characters || [];

	function isEnabled( key ) {
		return enabledCharacters.indexOf( key ) !== -1;
	}

	function elementsFor( key ) {
		return document.querySelectorAll( '.helanak-character--' + key );
	}

	function pulse( el, behaviorClass, durationMs ) {
		el.classList.add( behaviorClass );
		window.setTimeout( function () {
			el.classList.remove( behaviorClass );
		}, durationMs );
	}

	function triggerBehavior( keys, behaviorClass, durationMs ) {
		keys.forEach( function ( key ) {
			if ( ! isEnabled( key ) ) {
				return;
			}
			elementsFor( key ).forEach( function ( el ) {
				pulse( el, behaviorClass, durationMs );
			} );
		} );
	}

	// --- Add-to-cart hop --------------------------------------------------
	// Classic (shortcode/template) add-to-cart forms trigger this jQuery
	// event on <body>; WooCommerce Blocks dispatch a native CustomEvent.
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'added_to_cart', function () {
			triggerBehavior( [ 'rabbit', 'dog', 'cat' ], 'is-hopping', 900 );
		} );
	}
	document.body.addEventListener( 'wc-blocks_added_to_cart', function () {
		triggerBehavior( [ 'rabbit', 'dog', 'cat' ], 'is-hopping', 900 );
	} );

	// --- Cart-open wave -----------------------------------------------------
	// Any control that opens the mini-cart (header icon, floating button)
	// marks itself with data-helanak-cart-toggle in the template markup.
	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) {
			return;
		}
		var trigger = event.target.closest( '[data-helanak-cart-toggle]' );
		if ( ! trigger ) {
			return;
		}
		triggerBehavior( [ 'bear', 'monkey' ], 'is-waving', 1200 );
	} );

	// --- Idle sleep -----------------------------------------------------------
	// Re-armed setTimeout, not a polling loop: the timer only ever fires once
	// per idle period and is cleared/reset by real interaction events.
	var IDLE_DELAY_MS = 30000;
	var idleTimer = null;
	var sleepers = document.querySelectorAll( '.helanak-character[data-behavior="sleep"]' );

	var armIdleTimer = function () {
		window.clearTimeout( idleTimer );
		sleepers.forEach( function ( el ) {
			el.classList.remove( 'is-sleeping' );
		} );
		idleTimer = window.setTimeout( function () {
			sleepers.forEach( function ( el ) {
				el.classList.add( 'is-sleeping' );
			} );
		}, IDLE_DELAY_MS );
	};

	if ( sleepers.length ) {
		[ 'mousemove', 'keydown', 'touchstart', 'scroll', 'click' ].forEach( function ( eventName ) {
			window.addEventListener( eventName, armIdleTimer, { passive: true } );
		} );
		armIdleTimer();
	}

	// --- Scroll-triggered bird fly-past --------------------------------------
	// Fires once per page view, past a quarter of the page's scroll height.
	if ( isEnabled( 'bird' ) && elementsFor( 'bird' ).length ) {
		var birdHasFlown = false;
		var scrollTicking = false;

		window.addEventListener(
			'scroll',
			function () {
				if ( birdHasFlown || scrollTicking ) {
					return;
				}
				scrollTicking = true;
				window.requestAnimationFrame( function () {
					var scrolled = window.scrollY || document.documentElement.scrollTop;
					var threshold = document.documentElement.scrollHeight * 0.25;
					if ( scrolled > threshold ) {
						birdHasFlown = true;
						// Slightly longer than the 9s scfly/scfly-rtl flight so
						// the forwards-filled animation has carried the bird
						// fully off-screen before the class removal resets
						// opacity — removing it early makes the bird vanish
						// mid-flight.
						triggerBehavior( [ 'bird' ], 'is-flying', 9200 );
					}
					scrollTicking = false;
				} );
			},
			{ passive: true }
		);
	}
} )();
