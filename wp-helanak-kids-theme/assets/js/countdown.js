/**
 * Flash-sale countdown: ticks every [data-helanak-countdown] timer (built in
 * template-parts/section-flash-sale.php and page-sale.php) toward its
 * data-helanak-countdown Unix timestamp — the earliest scheduled sale
 * end-date among on-sale products (real WooCommerce data), not a fabricated
 * demo deadline. No-ops when the markup isn't on the page.
 *
 * The prototype's timers show hours:minutes:seconds only, so when a timer has
 * no [data-unit="days"] element the remaining days are rolled into the hours
 * figure instead.
 */
( function () {
	'use strict';

	var els = document.querySelectorAll( '[data-helanak-countdown]' );
	if ( ! els.length ) {
		return;
	}

	// Persian storefront shows Persian numerals (matching the PHP-side
	// Helanak_Locale_Digits filters, which can't reach client-ticked text).
	var FA_DIGITS = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];
	var isFa = 0 === ( document.documentElement.lang || '' ).indexOf( 'fa' );

	function pad( n ) {
		var s = String( n ).padStart( 2, '0' );
		if ( ! isFa ) {
			return s;
		}
		return s.replace( /[0-9]/g, function ( d ) {
			return FA_DIGITS[ Number( d ) ];
		} );
	}

	function initCountdown( el ) {
		var target    = parseInt( el.getAttribute( 'data-helanak-countdown' ), 10 ) * 1000;
		var daysEl    = el.querySelector( '[data-unit="days"]' );
		var hoursEl   = el.querySelector( '[data-unit="hours"]' );
		var minutesEl = el.querySelector( '[data-unit="minutes"]' );
		var secondsEl = el.querySelector( '[data-unit="seconds"]' );
		var timer     = null;

		if ( ! hoursEl || ! minutesEl || ! secondsEl ) {
			return;
		}

		function tick() {
			var diff = target - Date.now();
			if ( diff <= 0 ) {
				el.hidden = true;
				window.clearInterval( timer );
				return;
			}
			var seconds = Math.floor( diff / 1000 );
			if ( daysEl ) {
				daysEl.textContent = pad( Math.floor( seconds / 86400 ) );
				hoursEl.textContent = pad( Math.floor( ( seconds % 86400 ) / 3600 ) );
			} else {
				hoursEl.textContent = pad( Math.floor( seconds / 3600 ) );
			}
			minutesEl.textContent = pad( Math.floor( ( seconds % 3600 ) / 60 ) );
			secondsEl.textContent = pad( seconds % 60 );
		}

		tick();
		timer = window.setInterval( tick, 1000 );
	}

	Array.prototype.forEach.call( els, initCountdown );
} )();
