/**
 * Live-preview bindings for the Helanak Kids Customizer panel (runs inside
 * the preview iframe, enqueued by class-customizer.php's
 * enqueue_preview_script()). Every setting is registered with
 * `transport: 'postMessage'` in PHP, which means nothing updates live unless
 * a binding exists here — settings with no cheap DOM/CSS-var patch (season
 * mode/manual/campaign, character master/ambient/idle switches) fall back to
 * api.preview.send('refresh'), a lightweight reload of just the preview
 * iframe, equivalent to what `transport: 'refresh'` would have done anyway.
 *
 * Section/card-detail visibility toggles expect the templates to mark their
 * wrapper elements with data-helanak-section="{key}" /
 * data-helanak-card-part="{key}" (see the homepage/WooCommerce templates).
 */
( function ( api ) {
	'use strict';

	if ( ! api ) {
		return;
	}

	var data = window.helanakCustomizerData || {};
	var TOKEN_NAMES = [ 'primary', 'secondary', 'accent' ];
	var SHARED_KEYS = [ 'background', 'surface', 'border', 'text' ];
	var RADIUS_KEYS = [ 'sm', 'md', 'lg' ];

	function activeSeason() {
		return document.documentElement.getAttribute( 'data-season' ) || 'spring';
	}

	function setRootVar( name, value ) {
		document.documentElement.style.setProperty( name, value );
	}

	function refreshPreview() {
		api.preview.send( 'refresh' );
	}

	function toggleByAttribute( attribute, key, isVisible ) {
		document
			.querySelectorAll( '[' + attribute + '="' + key + '"]' )
			.forEach( function ( el ) {
				el.hidden = ! isVisible;
			} );
	}

	// --- Season colors: only the currently active season affects the page. ---
	( data.seasons || [] ).forEach( function ( season ) {
		TOKEN_NAMES.forEach( function ( token, index ) {
			api( 'helanak_color_' + season + '_' + index, function ( value ) {
				value.bind( function ( newValue ) {
					if ( season === activeSeason() ) {
						setRootVar( '--' + token + '-color', newValue );
					}
				} );
			} );
		} );
	} );

	// --- Shared, season-independent colors. ---
	SHARED_KEYS.forEach( function ( key ) {
		api( 'helanak_color_shared_' + key, function ( value ) {
			value.bind( function ( newValue ) {
				setRootVar( '--' + key + '-color', newValue );
			} );
		} );
	} );

	// --- Layout. ---
	api( 'helanak_container_width', function ( value ) {
		value.bind( function ( newValue ) {
			setRootVar( '--container-width', newValue + 'px' );
		} );
	} );
	RADIUS_KEYS.forEach( function ( key ) {
		api( 'helanak_radius_' + key, function ( value ) {
			value.bind( function ( newValue ) {
				setRootVar( '--radius-' + key, newValue + 'px' );
			} );
		} );
	} );

	// --- Season mode / manual season / campaign: structural, needs a refresh. ---
	[ 'helanak_season_mode', 'helanak_season_manual', 'helanak_campaign_name' ].forEach( function ( id ) {
		api( id, function ( value ) {
			value.bind( refreshPreview );
		} );
	} );

	// --- Homepage/site section visibility. ---
	( data.sections || [] ).forEach( function ( key ) {
		api( 'helanak_show_' + key, function ( value ) {
			value.bind( function ( newValue ) {
				toggleByAttribute( 'data-helanak-section', key, !! newValue );
			} );
		} );
	} );

	// --- Product-card detail visibility. ---
	( data.cardParts || [] ).forEach( function ( key ) {
		api( 'helanak_card_show_' + key, function ( value ) {
			value.bind( function ( newValue ) {
				toggleByAttribute( 'data-helanak-card-part', key, !! newValue );
			} );
		} );
	} );

	// --- Character master / ambient / idle switches: structural, needs a refresh. ---
	[ 'helanak_character_enabled', 'helanak_character_ambient', 'helanak_character_idle' ].forEach( function ( id ) {
		api( id, function ( value ) {
			value.bind( refreshPreview );
		} );
	} );

	// --- Individual character toggles: just hide/show the existing markup. ---
	( data.characters || [] ).forEach( function ( key ) {
		api( 'helanak_character_' + key, function ( value ) {
			value.bind( function ( newValue ) {
				document.querySelectorAll( '.helanak-character--' + key ).forEach( function ( el ) {
					el.hidden = ! newValue;
				} );
			} );
		} );
	} );
} )( window.wp && window.wp.customize );
