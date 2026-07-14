/**
 * Controls-panel UX: hides irrelevant settings instead of showing a flat
 * wall of controls, so "very dynamic, change anything" doesn't also mean
 * "every option always visible regardless of context." Runs in the
 * Customizer admin frame (enqueued by class-customizer.php's
 * enqueue_controls_script()), not the preview iframe.
 */
( function ( api ) {
	'use strict';

	if ( ! api ) {
		return;
	}

	var data = window.helanakCustomizerData || {};

	// Manual season picker only matters outside "auto"; campaign name only
	// matters in "campaign" mode.
	api.control( 'helanak_season_mode', function ( modeControl ) {
		function apply( mode ) {
			var manual = api.control( 'helanak_season_manual' );
			var campaign = api.control( 'helanak_campaign_name' );
			if ( manual ) {
				manual.container.toggle( 'auto' !== mode );
			}
			if ( campaign ) {
				campaign.container.toggle( 'campaign' === mode );
			}
		}

		modeControl.setting.bind( apply );
		apply( modeControl.setting.get() );
	} );

	// Per-character and ambient/idle toggles are moot once the master
	// "show mascot characters at all" switch is off.
	api.control( 'helanak_character_enabled', function ( masterControl ) {
		var dependentIds = [ 'helanak_character_ambient', 'helanak_character_idle' ].concat(
			( data.characters || [] ).map( function ( key ) {
				return 'helanak_character_' + key;
			} )
		);

		function apply( enabled ) {
			dependentIds.forEach( function ( id ) {
				var control = api.control( id );
				if ( control ) {
					control.container.toggle( !! enabled );
				}
			} );
		}

		masterControl.setting.bind( apply );
		apply( masterControl.setting.get() );
	} );
} )( window.wp && window.wp.customize );
