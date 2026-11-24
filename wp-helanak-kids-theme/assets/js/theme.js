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
	window.helanakDialog = { open: open, close: close, isOpen: function ( dialog ) { return active === dialog; }, setReturnFocus: function ( element ) { if ( active && element ) { returnFocus = element; } } };
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
	var navSettings = window.helanakSettings || {};
	var menuLabel = toggle ? toggle.getAttribute( 'aria-label' ) : '';
	var togglePath = toggle ? toggle.querySelector( 'svg path' ) : null;
	var closedPath = togglePath ? togglePath.getAttribute( 'd' ) : '';
	var branches = [];
	var pendingNavSpace = false;
	function setBranch( branch, expanded ) {
		branch.list.hidden = ! expanded;
		branch.button.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
	}
	function updateNavSpace() {
		pendingNavSpace = false;
		if ( ! nav || nav.hidden ) { return; }
		var viewport = window.visualViewport;
		var bottom = viewport ? viewport.height + viewport.offsetTop : window.innerHeight;
		nav.style.setProperty( '--helanak-mobile-nav-height', Math.max( 0, Math.floor( bottom - nav.getBoundingClientRect().top - 8 ) ) + 'px' );
	}
	function scheduleNavSpace() {
		if ( ! pendingNavSpace ) { pendingNavSpace = true; window.requestAnimationFrame( updateNavSpace ); }
	}
	function closeNav( restore ) {
		if ( ! nav || nav.hidden ) { return; }
		nav.hidden = true;
		document.body.classList.remove( 'helanak-nav-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		toggle.setAttribute( 'aria-label', menuLabel );
		if ( togglePath ) { togglePath.setAttribute( 'd', closedPath ); }
		branches.forEach( function ( branch ) { setBranch( branch, false ); } );
		if ( restore ) { toggle.focus(); }
	}
	if ( toggle && nav ) {
		Array.prototype.forEach.call( nav.querySelectorAll( '.sub-menu' ), function ( list, index ) {
			var parent = list.parentElement;
			var link = null;
			Array.prototype.forEach.call( parent.children, function ( child ) { if ( child.tagName === 'A' ) { link = child; } } );
			if ( ! link ) { return; }
			if ( ! list.id ) { list.id = 'helanak-mobile-submenu-' + index; }
			var button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'helanak-submenu-toggle';
			button.setAttribute( 'aria-controls', list.id );
			button.setAttribute( 'aria-label', ( navSettings.submenuToggleLabel || 'Toggle submenu for %s' ).replace( '%s', link.textContent.trim() ) );
			button.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			parent.classList.add( 'helanak-nav-parent' );
			parent.insertBefore( button, list );
			var branch = { list: list, button: button };
			branches.push( branch );
			setBranch( branch, false );
			button.addEventListener( 'click', function () { setBranch( branch, list.hidden ); scheduleNavSpace(); } );
		} );
		toggle.addEventListener( 'click', function () {
			if ( ! nav.hidden ) { closeNav( false ); return; }
			if ( window.innerWidth > 1024 ) { return; }
			nav.hidden = false;
			document.body.classList.add( 'helanak-nav-open' );
			toggle.setAttribute( 'aria-expanded', 'true' );
			toggle.setAttribute( 'aria-label', navSettings.closeLabel || menuLabel );
			if ( togglePath ) { togglePath.setAttribute( 'd', 'M6 6l12 12M6 18 18 6' ); }
			updateNavSpace();
			var first = nav.querySelector( 'a[href]' );
			if ( first ) { first.focus(); }
		} );
		nav.addEventListener( 'click', function ( event ) {
			var link = event.target.closest ? event.target.closest( 'a' ) : null;
			if ( ! link || ! nav.contains( link ) ) { return; }
			var href = link.getAttribute( 'href' );
			if ( ! href || href === '#' ) {
				var button = link.parentElement.querySelector( '.helanak-submenu-toggle' );
				if ( button ) { event.preventDefault(); button.click(); return; }
			}
			closeNav( false );
		} );
		document.addEventListener( 'keydown', function ( event ) { if ( event.key === 'Escape' ) { closeNav( true ); } } );
		var closeOutsideNav = function ( event ) {
			if ( nav.hidden ) { return; }
			if ( event.touches && event.touches.length ) {
				// During momentum scrolling a browser can retain the old touch target.
				var point = event.touches[ 0 ];
				var menuBox = nav.getBoundingClientRect();
				var toggleBox = toggle.getBoundingClientRect();
				var inMenu = point.clientX >= menuBox.left && point.clientX <= menuBox.right && point.clientY >= menuBox.top && point.clientY <= menuBox.bottom;
				var inToggle = point.clientX >= toggleBox.left && point.clientX <= toggleBox.right && point.clientY >= toggleBox.top && point.clientY <= toggleBox.bottom;
				if ( ! inMenu && ! inToggle ) { closeNav( false ); }
			} else if ( ! nav.contains( event.target ) && ! toggle.contains( event.target ) ) { closeNav( false ); }
		};
		document.addEventListener( 'click', closeOutsideNav );
		document.addEventListener( 'touchstart', closeOutsideNav, { passive: true } );
		document.addEventListener( 'focusin', function ( event ) { if ( ! nav.contains( event.target ) && ! toggle.contains( event.target ) ) { closeNav( false ); } } );
		window.addEventListener( 'resize', function () { if ( window.innerWidth > 1024 ) { closeNav( false ); } else { scheduleNavSpace(); } } );
		window.addEventListener( 'scroll', scheduleNavSpace, { passive: true } );
		if ( window.visualViewport ) {
			window.visualViewport.addEventListener( 'resize', scheduleNavSpace );
			window.visualViewport.addEventListener( 'scroll', scheduleNavSpace );
		}
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

	document.querySelectorAll( '[data-helanak-category-carousel]' ).forEach( function ( carousel ) {
		var track = carousel.querySelector( '[data-helanak-worlds-track]' );
		if ( ! track ) { return; }
		var prevBtn = carousel.querySelector( '[data-helanak-worlds-prev]' );
		var nextBtn = carousel.querySelector( '[data-helanak-worlds-next]' );
		function scrollByAmount( direction ) {
			var card = track.querySelector( '.helanak-world-card' );
			var step = ( card ? card.getBoundingClientRect().width : 180 ) + 18;
			var rtl = window.getComputedStyle( track ).direction === 'rtl';
			var animate = ! window.helanakMotionAllowed || window.helanakMotionAllowed();
			track.scrollBy( { left: direction * step * 2 * ( rtl ? -1 : 1 ), behavior: animate ? 'smooth' : 'auto' } );
		}
		function updateControls() {
			var maximum = Math.max( 0, track.scrollWidth - track.clientWidth );
			var position = Math.min( maximum, Math.abs( track.scrollLeft ) );
			if ( prevBtn ) { prevBtn.disabled = position <= 1; }
			if ( nextBtn ) { nextBtn.disabled = maximum <= 1 || position >= maximum - 1; }
		}
		if ( prevBtn ) {
			prevBtn.textContent = window.getComputedStyle( track ).direction === 'rtl' ? '›' : '‹';
			prevBtn.addEventListener( 'click', function () { scrollByAmount( -1 ); } );
		}
		if ( nextBtn ) {
			nextBtn.textContent = window.getComputedStyle( track ).direction === 'rtl' ? '‹' : '›';
			nextBtn.addEventListener( 'click', function () { scrollByAmount( 1 ); } );
		}
		track.addEventListener( 'scroll', updateControls, { passive: true } );
		window.addEventListener( 'resize', updateControls );
		window.addEventListener( 'load', updateControls );
		if ( window.ResizeObserver ) { new window.ResizeObserver( updateControls ).observe( track ); }
		updateControls();
	} );
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
	var flybyStage = document.querySelector( '[data-helanak-flyby-stage]' );
	var bird = flybyStage ? flybyStage.querySelector( '[data-character="bird"][data-behavior="fly"]' ) : null;
	var flybyTimer = null;
	var flybyEndTimer = null;
	var flybyHeight = 0.5;
	function placeFlyby() {
		var viewport = window.visualViewport;
		var viewTop = viewport ? viewport.offsetTop : 0;
		var viewHeight = viewport ? viewport.height : window.innerHeight;
		var header = document.querySelector( '.helanak-header' );
		var minimum = Math.max( viewTop + 40, header ? header.getBoundingClientRect().bottom + 40 : 0 );
		var maximum = viewTop + viewHeight - bird.offsetHeight - 40;
		if ( maximum < minimum ) { return false; }
		flybyStage.style.setProperty( '--helanak-flyby-top', Math.round( minimum + ( maximum - minimum ) * flybyHeight ) + 'px' );
		return true;
	}
	function scheduleFlyby( delay ) {
		window.clearTimeout( flybyTimer );
		flybyTimer = null;
		if ( bird && allowed() && settings.idleBehaviors ) { flybyTimer = window.setTimeout( flyby, delay ); }
	}
	function finishFlyby() {
		window.clearTimeout( flybyEndTimer );
		flybyEndTimer = null;
		bird.classList.remove( 'is-flying' );
		scheduleFlyby( 24000 + Math.random() * 14000 );
	}
	function flyby() {
		flybyTimer = null;
		if ( ! bird || ! allowed() || ! settings.idleBehaviors ) { return; }
		flybyHeight = 0.2 + Math.random() * 0.6;
		if ( ! placeFlyby() ) { scheduleFlyby( 24000 ); return; }
		bird.classList.add( 'is-flying' );
		flybyEndTimer = window.setTimeout( finishFlyby, 9200 );
	}
	function resetFlyby() {
		window.clearTimeout( flybyEndTimer );
		flybyEndTimer = null;
		bird.classList.remove( 'is-flying' );
		scheduleFlyby( 1800 );
	}
	function resizeFlyby() {
		if ( bird.classList.contains( 'is-flying' ) && ! placeFlyby() ) { resetFlyby(); }
	}
	if ( bird ) {
		bird.addEventListener( 'animationend', function ( event ) {
			if ( event.target === bird && ( event.animationName === 'scfly' || event.animationName === 'scfly-rtl' ) ) { finishFlyby(); }
		} );
		document.addEventListener( 'helanak:motionchange', resetFlyby );
		window.addEventListener( 'resize', resizeFlyby );
		if ( window.visualViewport ) { window.visualViewport.addEventListener( 'resize', resizeFlyby ); }
		resetFlyby();
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
	form.addEventListener( 'invalid', function ( event ) {
		form.hidden = false;
		toggle.setAttribute( 'aria-expanded', 'true' );
		var group = event.target.closest( '.helanak-filter-group' );
		if ( group ) { group.open = true; }
	}, true );
	if ( phone.addEventListener ) { phone.addEventListener( 'change', update ); } else { phone.addListener( update ); }
	update();
	var panel = form.closest( '.helanak-filters' );
	var header = document.querySelector( '.helanak-header' );
	var pendingOffset = false;
	var lastOffset;
	var lastTop;
	function updateOffset() {
		pendingOffset = false;
		if ( panel && header ) {
			var offset = ( parseFloat( window.getComputedStyle( header ).top ) || 0 ) + header.offsetHeight + 12;
			var top = Math.ceil( Math.max( offset, panel.getBoundingClientRect().top ) );
			if ( offset !== lastOffset ) { panel.style.setProperty( '--helanak-filter-offset', offset + 'px' ); lastOffset = offset; }
			if ( top !== lastTop ) { panel.style.setProperty( '--helanak-filter-space-top', top + 'px' ); lastTop = top; }
		}
	}
	function scheduleOffset() {
		if ( ! pendingOffset ) { pendingOffset = true; window.requestAnimationFrame( updateOffset ); }
	}
	updateOffset();
	window.addEventListener( 'resize', scheduleOffset );
	window.addEventListener( 'scroll', scheduleOffset, { passive: true } );
	if ( header && window.ResizeObserver ) { new window.ResizeObserver( scheduleOffset ).observe( header ); }
} )();

/** Keep native WooCommerce zoom/lightbox discoverable without adding a viewer. */
( function () {
	'use strict';
	var gallery = document.querySelector( '.woocommerce-product-gallery--with-images' );
	if ( ! gallery ) { return; }
	var hint = document.createElement( 'p' );
	hint.className = 'helanak-gallery-hint';
	hint.textContent = ( window.helanakSettings || {} ).galleryHint || 'Hover to zoom · Click to enlarge';
	gallery.appendChild( hint );
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
