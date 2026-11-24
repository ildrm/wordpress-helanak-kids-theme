/** Keep WooCommerce's real selects and change events; style their option UI. */
( function ( $ ) {
	'use strict';
	var updateImage = $.fn.wc_variations_image_update;

	function selectedImage( form, variation ) {
		if ( ! variation ) { return variation; }
		var map = form.data( 'helanakVariationImages' );
		if ( map === undefined ) {
			try { map = JSON.parse( form.find( 'script.helanak-variation-images' ).text() ); } catch ( error ) { map = null; }
			form.data( 'helanakVariationImages', map );
		}
		if ( ! map || ! map.variations || ! map.images ) { return variation; }
		var fields = form.find( '.variations select' );
		var values = {};
		var complete = fields.length > 0;
		fields.each( function () {
			values[ this.name ] = $( this ).val();
			if ( ! values[ this.name ] ) { complete = false; }
		} );
		if ( ! complete ) { return variation; }

		function specificity( attributes ) {
			var score = 0;
			var matches = true;
			$.each( values, function ( name, value ) {
				var expected = attributes[ name ] || '';
				if ( expected && expected !== value ) { matches = false; }
				if ( expected ) { score++; }
			} );
			return matches ? score : -1;
		}
		var score = specificity( variation.attributes || {} );
		var best = null;
		$.each( map.variations, function ( index, choice ) {
			var candidateScore = specificity( choice.attributes );
			if ( candidateScore > score ) { score = candidateScore; best = choice; }
		} );
		if ( ! best || ! best.image_id || ! map.images[ best.image_id ] ) { return variation; }
		// Preserve WooCommerce's matched ID, prices, stock, and submitted attributes.
		return $.extend( {}, variation, { image_id: best.image_id, image: map.images[ best.image_id ], gallery_images_html: '' } );
	}

	if ( typeof updateImage === 'function' ) {
		$.fn.wc_variations_image_update = function ( variation ) {
			return updateImage.call( this, selectedImage( this.first(), variation ) );
		};
	}
	$( function () {
		if ( ! $.fn.selectWoo ) { return; }
		$( 'body.single-product .summary form.variations_form' ).each( function () {
			var form = $( this );
			form.find( '.variations select' ).each( function () {
				var select = $( this );
				if ( select.hasClass( 'select2-hidden-accessible' ) ) { return; }
				select.selectWoo( {
					width: '100%',
					dir: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr',
					minimumResultsForSearch: Infinity,
					dropdownCssClass: 'helanak-variation-dropdown'
				} );
				var control = select.next( '.select2-container' ).addClass( 'helanak-variation-control' );
				select.on( 'select2:open.helanakPicker', function () {
					$( '.helanak-variation-dropdown' ).parent( '.select2-container' ).addClass( 'helanak-variation-menu' );
				} );
				var id = this.id;
				var label = form.find( 'label' ).filter( function () { return this.htmlFor === id; } ).first();
				if ( label.length ) {
					if ( ! label.attr( 'id' ) ) { label.attr( 'id', 'helanak-variation-label-' + id.replace( /[^a-zA-Z0-9_-]/g, '-' ) ); }
					var selection = control.find( '.select2-selection' );
					selection.attr( 'aria-labelledby', label.attr( 'id' ) + ' ' + selection.attr( 'aria-labelledby' ) );
				}
			} );
			form.on( 'woocommerce_update_variation_values.helanakPicker', function () {
				form.find( '.variations select.select2-hidden-accessible' ).trigger( 'change.select2' );
			} );
		} );
	} );
} )( jQuery );
