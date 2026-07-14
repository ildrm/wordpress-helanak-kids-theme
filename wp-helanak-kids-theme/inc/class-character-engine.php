<?php
/**
 * Character Engine (spec section 8): renders the 8-mascot roster as
 * absolutely-positioned CSS shapes, ported 1:1 from the frontend/ prototype's
 * rabbit()/dog()/cat()/bear()/fox()/penguin()/monkey()/bird() methods (see
 * any frontend/*.dc.html, e.g. archive-product.dc.html around the _wrap()/
 * _eye() helpers) — same nested-div layout, same fixed per-species fur/
 * feather colors (not season-driven; only the unrelated ambient snow/petal
 * particles and confetti use season CSS custom properties), same idle
 * keyframe animations. This is a direct port, not a from-scratch
 * reinterpretation.
 *
 * Context-aware behavior triggers (add-to-cart hop, mini-cart-open wave,
 * scroll-triggered bird fly-past, idle sleep) are event-driven JS in
 * assets/js/theme.js, which toggles behavior classes (is-hopping etc.) on
 * the wrapper <span> this class renders — independent of the mascot's own
 * internal markup, so it works unchanged against the ported artwork.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Character_Engine {

	public static function init(): void {
		// No hooks needed yet — templates call render_character() directly.
		// Reserved for future context-trigger server-side hooks (e.g. an
		// order-confirmation celebratory render) if/when needed.
	}

	public static function master_enabled(): bool {
		return (bool) get_theme_mod( 'helanak_character_enabled', true );
	}

	public static function is_enabled( string $key ): bool {
		if ( ! self::master_enabled() ) {
			return false;
		}
		if ( ! array_key_exists( $key, Helanak_Theme_Customizer::character_labels() ) ) {
			return false;
		}
		return (bool) get_theme_mod( "helanak_character_{$key}", true );
	}

	/** @return string[] keys of every character currently enabled, for JS. */
	public static function enabled_characters(): array {
		if ( ! self::master_enabled() ) {
			return array();
		}
		return array_values( array_filter(
			array_keys( Helanak_Theme_Customizer::character_labels() ),
			array( __CLASS__, 'is_enabled' )
		) );
	}

	/**
	 * Renders a character if enabled, wrapped in a decorative, screen-reader-
	 * hidden container with data attributes theme.js uses to attach context
	 * behaviors. Returns '' if the character (or the master switch) is off,
	 * so callers can simply echo the return value.
	 *
	 * @param string $key      One of Helanak_Theme_Customizer::character_labels() keys.
	 * @param array  $args     {
	 *     @type int    $size     Pixel width of the mascot (its natural aspect ratio is preserved). Default 96. Ignored if $scale is given.
	 *     @type float  $scale    Explicit scale factor, bypassing the size/natural-width computation — used where the
	 *                            prototype hardcodes a literal per-species scale (e.g. the "Meet our friends" parade's
	 *                            rabbit(.46)/dog(.56)/cat(.6)/monkey(.95)/bear(.46)/fox(.48)/penguin(.5)/bird(1.6)),
	 *                            so the rendered pixel size matches exactly instead of being normalized to a shared width.
	 *     @type string $behavior Optional data-behavior hint for theme.js (e.g. 'hop', 'wave', 'sleep', 'fly', 'idle').
	 *     @type string $class    Extra CSS class(es) on the wrapper.
	 *     @type mixed  $hop      Rabbit: CSS duration (e.g. '5.5s') for a continuous hop idle instead of breathing — matches the prototype's hero mascot.
	 *                            Dog: truthy for the prototype's continuous 1.1s celebration hop (page-sale hero / celebrateDog).
	 *     @type bool   $wave     Bear or rabbit: continuous arm-wave idle instead of resting — matches the prototype's hero + celebration mascots.
	 * }
	 */
	public static function render_character( string $key, array $args = array() ): string {
		if ( ! self::is_enabled( $key ) ) {
			return '';
		}

		$behavior = $args['behavior'] ?? 'idle';
		$class    = $args['class'] ?? '';
		$natural  = self::natural_width( $key );
		$scale    = isset( $args['scale'] ) ? (float) $args['scale'] : ( ( $args['size'] ?? 96 ) / $natural );
		$size     = (int) round( $scale * $natural );

		$art = self::art_for( $key, $scale, $args );
		if ( '' === $art ) {
			return '';
		}

		return sprintf(
			'<span class="helanak-character helanak-character--%1$s %2$s" data-character="%1$s" data-behavior="%3$s" style="--helanak-char-size:%4$dpx" aria-hidden="true">%5$s</span>',
			esc_attr( $key ),
			esc_attr( $class ),
			esc_attr( $behavior ),
			absint( $size ),
			$art // phpcs:ignore WordPress.Security.EscapeOutput -- built from the fixed, trusted markup in the species renderers below; every attribute value passed through them is esc_attr()'d in c()/p().
		);
	}

	/** Natural (unscaled) pixel width per species, from the prototype's _wrap(w, ...) calls — used to turn the requested $size into a scale factor while preserving each mascot's real aspect ratio. */
	private static function natural_width( string $key ): int {
		$widths = array(
			'rabbit'  => 150,
			'dog'     => 136,
			'cat'     => 150,
			'bear'    => 150,
			'fox'     => 150,
			'penguin' => 130,
			'monkey'  => 82,
			'bird'    => 44,
		);
		return $widths[ $key ] ?? 150;
	}

	/** $scale is the already-computed multiplier (render_character() derives it from either $args['scale'] or size/natural-width) — applied directly, not divided again. */
	private static function art_for( string $key, float $scale, array $args = array() ): string {
		return match ( $key ) {
			'rabbit'  => self::rabbit( $scale, (string) ( $args['hop'] ?? '' ), (bool) ( $args['wave'] ?? false ) ),
			'dog'     => self::dog( $scale, (bool) ( $args['hop'] ?? false ) ),
			'cat'     => self::cat( $scale, array_key_exists( 'awake', $args ) ? (bool) $args['awake'] : true ),
			'bear'    => self::bear( $scale, (bool) ( $args['wave'] ?? false ) ),
			'fox'     => self::fox( $scale ),
			'penguin' => self::penguin( $scale ),
			'monkey'  => self::monkey( $scale ),
			'bird'    => self::bird( $scale ),
			default   => '',
		};
	}

	/* ---------------------------------------------------------------
	 * Shared style/markup helpers — ports of the prototype's
	 * _c()/_p()/_wrap()/_eye() (see frontend/*.dc.html).
	 * --------------------------------------------------------------- */

	private static function style( array $decls ): string {
		$out = array();
		foreach ( $decls as $prop => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			$out[] = $prop . ':' . $value;
		}
		return implode( ';', $out );
	}

	/** Plain (non-positioned unless the caller's $style says so) decorative div. */
	private static function c( array $style, string $inner = '' ): string {
		return '<div aria-hidden="true" style="' . esc_attr( self::style( $style ) ) . '">' . $inner . '</div>';
	}

	/** Absolutely-positioned decorative div. */
	private static function p( array $style, string $inner = '' ): string {
		return self::c( array_merge( array( 'position' => 'absolute' ), $style ), $inner );
	}

	/** Three-layer sizing/scale/animation wrapper, matching the prototype's _wrap(w, h, s, anim, parts). */
	private static function wrap_mascot( int $w, int $h, float $s, string $anim, string $parts ): string {
		return self::c(
			array(
				'width'          => round( $w * $s, 2 ) . 'px',
				'height'         => round( $h * $s, 2 ) . 'px',
				'position'       => 'relative',
				'pointer-events' => 'none',
			),
			self::c(
				array(
					'transform'        => 'scale(' . $s . ')',
					'transform-origin' => '0 0',
					'width'            => $w . 'px',
					'height'           => $h . 'px',
				),
				self::c(
					array(
						'position'  => 'relative',
						'width'     => '100%',
						'height'    => '100%',
						'animation' => $anim,
					),
					$parts
				)
			)
		);
	}

	/** Three-layer blinking eye (sclera/pupil/highlight), matching the prototype's _eye(x, y). */
	private static function eye( string $x, string $y ): string {
		return self::p(
			array(
				'left'          => $x,
				'top'           => $y,
				'width'         => '14px',
				'height'        => '16px',
				'border-radius' => '50%',
				'background'    => '#fff',
				'box-shadow'    => '0 1px 2px rgba(43,43,51,.15)',
				'animation'     => 'scblink 4.8s ease-in-out infinite',
			),
			self::p(
				array( 'left' => '3px', 'top' => '4px', 'width' => '8px', 'height' => '11px', 'border-radius' => '50%', 'background' => '#2B2B33' ),
				self::p( array( 'left' => '1px', 'top' => '1px', 'width' => '4px', 'height' => '5px', 'border-radius' => '50%', 'background' => '#fff', 'opacity' => '.95' ) )
			)
		);
	}

	/* ---------------------------------------------------------------
	 * Species. Colors, dimensions and layout are ported verbatim from
	 * the prototype's rabbit()/dog()/cat()/bear()/fox()/penguin()/
	 * monkey()/bird() (frontend/*.dc.html).
	 * --------------------------------------------------------------- */

	/**
	 * $hop, given as a CSS duration (e.g. '5.5s'), makes the rabbit hop
	 * continuously instead of idle-breathing (hero mascot); $wave raises +
	 * continuously waves the right arm, matching the prototype's
	 * cartRabbit/celebrateRabbit (rabbit(s, {wave:true})).
	 */
	private static function rabbit( float $s, string $hop = '', bool $wave = false ): string {
		$ear_pink = self::c( array( 'position' => 'absolute', 'inset' => '12px 6px 20px 6px', 'border-radius' => 'inherit', 'background' => '#FFD3E4' ) );

		$right_arm = array( 'left' => '102px', 'top' => '140px', 'width' => '20px', 'height' => '34px', 'border-radius' => '50%', 'background' => '#fff', 'transform' => 'rotate(-16deg)' );
		if ( $wave ) {
			$right_arm['transform-origin'] = 'top center';
			$right_arm['animation']        = 'scwave 1.5s ease-in-out infinite';
		}

		$parts =
			self::p( array( 'left' => '36px', 'top' => '0', 'width' => '24px', 'height' => '76px', 'border-radius' => '50% 50% 44% 44%', 'background' => '#fff', 'transform' => 'rotate(-14deg)', 'box-shadow' => '0 2px 6px rgba(43,43,51,.06)' ), $ear_pink )
			. self::p( array( 'left' => '86px', 'top' => '0', 'width' => '24px', 'height' => '76px', 'border-radius' => '50% 50% 44% 44%', 'background' => '#fff', 'transform' => 'rotate(14deg)', 'box-shadow' => '0 2px 6px rgba(43,43,51,.06)', 'transform-origin' => 'bottom center', 'animation' => 'scear 6s ease-in-out infinite' ), $ear_pink )
			. self::p( array( 'left' => '28px', 'top' => '58px', 'width' => '92px', 'height' => '86px', 'border-radius' => '50%', 'background' => '#fff', 'box-shadow' => '0 8px 18px rgba(43,43,51,.08)' ) )
			. self::eye( '50px', '86px' )
			. self::eye( '82px', '86px' )
			. self::p( array( 'left' => '42px', 'top' => '106px', 'width' => '13px', 'height' => '13px', 'border-radius' => '50%', 'background' => '#FFB7D5', 'opacity' => '.6' ) )
			. self::p( array( 'left' => '93px', 'top' => '106px', 'width' => '13px', 'height' => '13px', 'border-radius' => '50%', 'background' => '#FFB7D5', 'opacity' => '.6' ) )
			. self::p( array( 'left' => '68px', 'top' => '101px', 'width' => '11px', 'height' => '9px', 'border-radius' => '50%', 'background' => '#FF9FC6' ) )
			. self::p( array( 'left' => '66px', 'top' => '106px', 'width' => '15px', 'height' => '10px', 'border' => '2px solid transparent', 'border-bottom-color' => '#2B2B33', 'border-radius' => '50%' ) )
			. self::p( array( 'left' => '36px', 'top' => '132px', 'width' => '76px', 'height' => '58px', 'border-radius' => '46% 46% 42% 42%', 'background' => '#fff', 'box-shadow' => 'inset 0 -8px 0 rgba(43,43,51,.04), 0 6px 14px rgba(43,43,51,.06)' ) )
			. self::p( array( 'left' => '26px', 'top' => '142px', 'width' => '20px', 'height' => '34px', 'border-radius' => '50%', 'background' => '#fff', 'transform' => 'rotate(20deg)' ) )
			. self::p( $right_arm )
			. self::p( array( 'left' => '44px', 'top' => '183px', 'width' => '26px', 'height' => '13px', 'border-radius' => '50%', 'background' => '#F1F1F6' ) )
			. self::p( array( 'left' => '80px', 'top' => '183px', 'width' => '26px', 'height' => '13px', 'border-radius' => '50%', 'background' => '#F1F1F6' ) );

		$anim = $hop ? 'schop ' . $hop . ' ease-in-out infinite' : 'scbreathe 4.5s ease-in-out infinite';
		return self::wrap_mascot( 150, 196, $s, $anim, $parts );
	}

	/** $hop makes the dog hop continuously (the prototype's dogLayer/celebrateDog, dog(s, {hop:true}) → schop 1.1s). */
	private static function dog( float $s, bool $hop = false ): string {
		$parts =
			self::p( array( 'left' => '16px', 'top' => '32px', 'width' => '20px', 'height' => '46px', 'border-radius' => '50%', 'background' => '#CE9257', 'transform' => 'rotate(16deg)' ) )
			. self::p( array( 'left' => '100px', 'top' => '32px', 'width' => '20px', 'height' => '46px', 'border-radius' => '50%', 'background' => '#CE9257', 'transform' => 'rotate(-16deg)' ) )
			. self::p( array( 'left' => '28px', 'top' => '20px', 'width' => '80px', 'height' => '72px', 'border-radius' => '50%', 'background' => '#EFC08A', 'box-shadow' => '0 6px 14px rgba(43,43,51,.08)' ) )
			. self::eye( '46px', '40px' )
			. self::eye( '76px', '40px' )
			. self::p( array( 'left' => '38px', 'top' => '62px', 'width' => '12px', 'height' => '11px', 'border-radius' => '50%', 'background' => '#FFB7D5', 'opacity' => '.5' ) )
			. self::p( array( 'left' => '86px', 'top' => '62px', 'width' => '12px', 'height' => '11px', 'border-radius' => '50%', 'background' => '#FFB7D5', 'opacity' => '.5' ) )
			. self::p( array( 'left' => '51px', 'top' => '60px', 'width' => '34px', 'height' => '24px', 'border-radius' => '50%', 'background' => '#FBEAD1' ) )
			. self::p( array( 'left' => '62px', 'top' => '58px', 'width' => '12px', 'height' => '9px', 'border-radius' => '50%', 'background' => '#6B4E32' ) )
			. self::p( array( 'left' => '36px', 'top' => '88px', 'width' => '66px', 'height' => '52px', 'border-radius' => '46% 46% 42% 42%', 'background' => '#EFC08A', 'box-shadow' => 'inset 0 -6px 0 rgba(43,43,51,.05)' ) )
			. self::p( array( 'left' => '98px', 'top' => '94px', 'width' => '28px', 'height' => '12px', 'border-radius' => '99px', 'background' => '#CE9257', 'transform-origin' => 'left center', 'animation' => 'scwag 1s ease-in-out infinite' ) )
			. self::p( array( 'left' => '44px', 'top' => '136px', 'width' => '22px', 'height' => '12px', 'border-radius' => '50%', 'background' => '#E3B276' ) )
			. self::p( array( 'left' => '72px', 'top' => '136px', 'width' => '22px', 'height' => '12px', 'border-radius' => '50%', 'background' => '#E3B276' ) );

		$anim = $hop ? 'schop 1.1s ease-in-out infinite' : 'scbreathe 4s ease-in-out infinite';
		return self::wrap_mascot( 136, 152, $s, $anim, $parts );
	}

	/**
	 * $awake false renders the closed-eye, "z z z" sleeping variant. Defaults
	 * to true everywhere (matching the prototype's default state) — the
	 * footer peek's idle-sleep look isn't baked into this markup at all, it's
	 * theme.js toggling `.is-sleeping` (see assets/css/theme.css) after 30s of
	 * inactivity, same as the prototype's own idle timer.
	 */
	private static function cat( float $s, bool $awake = true ): string {
		if ( $awake ) {
			$eyes = self::eye( '42px', '40px' ) . self::eye( '66px', '40px' );
			$zs   = '';
		} else {
			$eyes =
				self::p( array( 'left' => '42px', 'top' => '48px', 'width' => '14px', 'height' => '8px', 'border' => '2px solid transparent', 'border-bottom-color' => '#2B2B33', 'border-radius' => '50%' ) )
				. self::p( array( 'left' => '66px', 'top' => '48px', 'width' => '14px', 'height' => '8px', 'border' => '2px solid transparent', 'border-bottom-color' => '#2B2B33', 'border-radius' => '50%' ) );
			$zs   = self::z( '96px', '2px', '13px', '0s' ) . self::z( '108px', '-6px', '16px', '.9s' ) . self::z( '120px', '-14px', '19px', '1.8s' );
		}

		$parts =
			self::p( array( 'left' => '98px', 'top' => '70px', 'width' => '42px', 'height' => '13px', 'border-radius' => '99px', 'background' => '#B9BFD0', 'transform-origin' => 'left center', 'animation' => 'scwag 2.6s ease-in-out infinite' ) )
			. self::p( array( 'left' => '32px', 'top' => '56px', 'width' => '88px', 'height' => '44px', 'border-radius' => '50% 50% 40% 40%', 'background' => '#CBD0DE' ) )
			. self::p( array( 'left' => '34px', 'top' => '12px', 'width' => '18px', 'height' => '18px', 'border-radius' => '40% 40% 20% 40%', 'background' => '#CBD0DE', 'transform' => 'rotate(-10deg)' ) )
			. self::p( array( 'left' => '66px', 'top' => '12px', 'width' => '18px', 'height' => '18px', 'border-radius' => '40% 40% 40% 20%', 'background' => '#CBD0DE', 'transform' => 'rotate(10deg)' ) )
			. self::p( array( 'left' => '28px', 'top' => '22px', 'width' => '64px', 'height' => '56px', 'border-radius' => '50%', 'background' => '#CBD0DE', 'box-shadow' => '0 6px 14px rgba(43,43,51,.08)' ) )
			. self::p( array( 'left' => '56px', 'top' => '58px', 'width' => '9px', 'height' => '7px', 'border-radius' => '50%', 'background' => '#FF9FC6' ) )
			. $eyes . $zs;

		return self::wrap_mascot( 150, 106, $s, 'scbreathe 5s ease-in-out infinite', $parts );
	}

	/** Floating "z" sleep glyph, matching the prototype's cat()-local z(x, y, fontSize, delay) helper. */
	private static function z( string $x, string $y, string $font_size, string $delay ): string {
		return sprintf(
			'<span aria-hidden="true" style="%s">z</span>',
			esc_attr( self::style( array(
				'position'    => 'absolute',
				'left'        => $x,
				'top'         => $y,
				'font-weight' => '800',
				'font-size'   => $font_size,
				'color'       => '#8b8b96',
				'font-family' => "'Poppins',sans-serif",
				'animation'   => 'sczz 2.8s ease-in-out ' . $delay . ' infinite',
			) ) )
		);
	}

	/** $wave raises + continuously waves the right arm instead of resting it — used by the hero mascot. */
	private static function bear( float $s, bool $wave = false ): string {
		$ear_inner = self::c( array( 'position' => 'absolute', 'inset' => '6px', 'border-radius' => '50%', 'background' => '#E7C9A4' ) );

		$right_arm = array( 'left' => '102px', 'top' => '118px', 'width' => '20px', 'height' => '36px', 'border-radius' => '50%', 'background' => '#C89B6E', 'transform' => 'rotate(-18deg)' );
		if ( $wave ) {
			$right_arm['transform-origin'] = 'top center';
			$right_arm['animation']        = 'scwave 1.6s ease-in-out infinite';
		}

		$parts =
			self::p( array( 'left' => '30px', 'top' => '26px', 'width' => '26px', 'height' => '26px', 'border-radius' => '50%', 'background' => '#C89B6E' ), $ear_inner )
			. self::p( array( 'left' => '94px', 'top' => '26px', 'width' => '26px', 'height' => '26px', 'border-radius' => '50%', 'background' => '#C89B6E' ), $ear_inner )
			. self::p( array( 'left' => '32px', 'top' => '36px', 'width' => '86px', 'height' => '80px', 'border-radius' => '50%', 'background' => '#C89B6E', 'box-shadow' => '0 8px 18px rgba(43,43,51,.08)' ) )
			. self::eye( '52px', '60px' )
			. self::eye( '84px', '60px' )
			. self::p( array( 'left' => '42px', 'top' => '82px', 'width' => '12px', 'height' => '11px', 'border-radius' => '50%', 'background' => '#FFB7D5', 'opacity' => '.45' ) )
			. self::p( array( 'left' => '94px', 'top' => '82px', 'width' => '12px', 'height' => '11px', 'border-radius' => '50%', 'background' => '#FFB7D5', 'opacity' => '.45' ) )
			. self::p( array( 'left' => '58px', 'top' => '80px', 'width' => '34px', 'height' => '24px', 'border-radius' => '50%', 'background' => '#E7C9A4' ) )
			. self::p( array( 'left' => '69px', 'top' => '78px', 'width' => '12px', 'height' => '9px', 'border-radius' => '50%', 'background' => '#5B4632' ) )
			. self::p(
				array( 'left' => '38px', 'top' => '112px', 'width' => '74px', 'height' => '60px', 'border-radius' => '46% 46% 42% 42%', 'background' => '#C89B6E', 'box-shadow' => 'inset 0 -8px 0 rgba(43,43,51,.05)' ),
				self::c( array( 'position' => 'absolute', 'left' => '20px', 'top' => '12px', 'width' => '34px', 'height' => '34px', 'border-radius' => '50%', 'background' => '#E7C9A4', 'opacity' => '.75' ) )
			)
			. self::p( array( 'left' => '28px', 'top' => '120px', 'width' => '20px', 'height' => '36px', 'border-radius' => '50%', 'background' => '#C89B6E', 'transform' => 'rotate(22deg)' ) )
			. self::p( $right_arm )
			. self::p( array( 'left' => '46px', 'top' => '166px', 'width' => '26px', 'height' => '14px', 'border-radius' => '50%', 'background' => '#A97F52' ) )
			. self::p( array( 'left' => '80px', 'top' => '166px', 'width' => '26px', 'height' => '14px', 'border-radius' => '50%', 'background' => '#A97F52' ) );

		return self::wrap_mascot( 150, 186, $s, 'scbreathe 4.2s ease-in-out infinite', $parts );
	}

	private static function fox( float $s ): string {
		$parts =
			self::p(
				array( 'left' => '96px', 'top' => '118px', 'width' => '48px', 'height' => '20px', 'border-radius' => '99px', 'background' => '#F2A15C', 'transform-origin' => 'left center', 'animation' => 'scwag 1.8s ease-in-out infinite' ),
				self::c( array( 'position' => 'absolute', 'right' => '-2px', 'top' => '3px', 'width' => '16px', 'height' => '14px', 'border-radius' => '50%', 'background' => '#fff' ) )
			)
			. self::p(
				array( 'left' => '36px', 'top' => '8px', 'width' => '22px', 'height' => '30px', 'border-radius' => '60% 60% 20% 20%', 'background' => '#F2A15C', 'transform' => 'rotate(-12deg)' ),
				self::c( array( 'position' => 'absolute', 'inset' => '10px 5px 4px 5px', 'border-radius' => 'inherit', 'background' => '#D97E3A' ) )
			)
			. self::p(
				array( 'left' => '90px', 'top' => '8px', 'width' => '22px', 'height' => '30px', 'border-radius' => '60% 60% 20% 20%', 'background' => '#F2A15C', 'transform' => 'rotate(12deg)' ),
				self::c( array( 'position' => 'absolute', 'inset' => '10px 5px 4px 5px', 'border-radius' => 'inherit', 'background' => '#D97E3A' ) )
			)
			. self::p( array( 'left' => '34px', 'top' => '26px', 'width' => '80px', 'height' => '72px', 'border-radius' => '50%', 'background' => '#F2A15C', 'box-shadow' => '0 8px 18px rgba(43,43,51,.08)' ) )
			. self::p( array( 'left' => '50px', 'top' => '64px', 'width' => '48px', 'height' => '32px', 'border-radius' => '50%', 'background' => '#FFF6EC' ) )
			. self::eye( '52px', '48px' )
			. self::eye( '82px', '48px' )
			. self::p( array( 'left' => '68px', 'top' => '70px', 'width' => '12px', 'height' => '9px', 'border-radius' => '50%', 'background' => '#5B4632' ) )
			. self::p(
				array( 'left' => '42px', 'top' => '96px', 'width' => '64px', 'height' => '56px', 'border-radius' => '46% 46% 42% 42%', 'background' => '#F2A15C', 'box-shadow' => 'inset 0 -7px 0 rgba(43,43,51,.05)' ),
				self::c( array( 'position' => 'absolute', 'left' => '16px', 'top' => '8px', 'width' => '32px', 'height' => '36px', 'border-radius' => '50%', 'background' => '#FFF6EC', 'opacity' => '.85' ) )
			)
			. self::p( array( 'left' => '48px', 'top' => '148px', 'width' => '24px', 'height' => '13px', 'border-radius' => '50%', 'background' => '#D97E3A' ) )
			. self::p( array( 'left' => '78px', 'top' => '148px', 'width' => '24px', 'height' => '13px', 'border-radius' => '50%', 'background' => '#D97E3A' ) );

		return self::wrap_mascot( 150, 176, $s, 'scbreathe 4.4s ease-in-out infinite', $parts );
	}

	private static function penguin( float $s ): string {
		$blink_dot = static function ( string $left ) {
			return self::p(
				array( 'left' => $left, 'top' => '34px', 'width' => '9px', 'height' => '11px', 'border-radius' => '50%', 'background' => '#fff' ),
				self::c( array( 'position' => 'absolute', 'left' => '2px', 'top' => '2px', 'width' => '5px', 'height' => '7px', 'border-radius' => '50%', 'background' => '#2B2B33', 'animation' => 'scblink 5.4s ease-in-out infinite' ) )
			);
		};

		$parts =
			self::p( array( 'left' => '26px', 'top' => '10px', 'width' => '78px', 'height' => '118px', 'border-radius' => '50% 50% 46% 46%', 'background' => '#454B61', 'box-shadow' => '0 8px 18px rgba(43,43,51,.12)' ) )
			. self::p( array( 'left' => '40px', 'top' => '44px', 'width' => '50px', 'height' => '76px', 'border-radius' => '50% 50% 46% 46%', 'background' => '#fff' ) )
			. $blink_dot( '44px' )
			. $blink_dot( '74px' )
			. self::p( array( 'left' => '56px', 'top' => '46px', 'width' => '0', 'height' => '0', 'border-left' => '8px solid transparent', 'border-right' => '8px solid transparent', 'border-top' => '11px solid #FFA54D' ) )
			. self::p( array( 'left' => '12px', 'top' => '54px', 'width' => '18px', 'height' => '46px', 'border-radius' => '50%', 'background' => '#454B61', 'transform' => 'rotate(18deg)', 'transform-origin' => 'top center', 'animation' => 'scwave 2.2s ease-in-out infinite' ) )
			. self::p( array( 'left' => '100px', 'top' => '54px', 'width' => '18px', 'height' => '46px', 'border-radius' => '50%', 'background' => '#454B61', 'transform' => 'rotate(-18deg)' ) )
			. self::p( array( 'left' => '34px', 'top' => '124px', 'width' => '28px', 'height' => '14px', 'border-radius' => '50%', 'background' => '#FFA54D' ) )
			. self::p( array( 'left' => '68px', 'top' => '124px', 'width' => '28px', 'height' => '14px', 'border-radius' => '50%', 'background' => '#FFA54D' ) );

		return self::wrap_mascot( 130, 168, $s, 'scwiggle 3s ease-in-out infinite', $parts );
	}

	private static function monkey( float $s ): string {
		$ear_inner = self::c( array( 'position' => 'absolute', 'inset' => '5px', 'border-radius' => '50%', 'background' => '#F2DFC8' ) );

		$parts =
			self::p( array( 'left' => '0', 'top' => '18px', 'width' => '22px', 'height' => '22px', 'border-radius' => '50%', 'background' => '#B98A62' ), $ear_inner )
			. self::p( array( 'left' => '60px', 'top' => '18px', 'width' => '22px', 'height' => '22px', 'border-radius' => '50%', 'background' => '#B98A62' ), $ear_inner )
			. self::p( array( 'left' => '10px', 'top' => '8px', 'width' => '62px', 'height' => '56px', 'border-radius' => '50%', 'background' => '#B98A62', 'box-shadow' => '0 6px 14px rgba(43,43,51,.1)' ) )
			. self::p( array( 'left' => '20px', 'top' => '26px', 'width' => '42px', 'height' => '34px', 'border-radius' => '50%', 'background' => '#F2DFC8' ) )
			. self::eye( '26px', '29px' )
			. self::eye( '44px', '29px' )
			. self::p( array( 'left' => '34px', 'top' => '44px', 'width' => '14px', 'height' => '9px', 'border' => '2px solid transparent', 'border-bottom-color' => '#2B2B33', 'border-radius' => '50%' ) )
			. self::p( array( 'left' => '16px', 'top' => '60px', 'width' => '14px', 'height' => '12px', 'border-radius' => '50% 50% 30% 30%', 'background' => '#B98A62' ) )
			. self::p( array( 'left' => '52px', 'top' => '60px', 'width' => '14px', 'height' => '12px', 'border-radius' => '50% 50% 30% 30%', 'background' => '#B98A62' ) );

		return self::wrap_mascot( 82, 74, $s, 'scfloat 3.5s ease-in-out infinite', $parts );
	}

	private static function bird( float $s ): string {
		$parts =
			self::p( array( 'left' => '-6px', 'top' => '16px', 'width' => '14px', 'height' => '8px', 'border-radius' => '50%', 'background' => '#5FB6F2', 'transform' => 'rotate(24deg)' ) )
			. self::p( array( 'left' => '0', 'top' => '8px', 'width' => '30px', 'height' => '26px', 'border-radius' => '50% 50% 46% 46%', 'background' => '#73C8FF' ) )
			. self::p( array( 'left' => '20px', 'top' => '14px', 'width' => '4px', 'height' => '4px', 'border-radius' => '50%', 'background' => '#2B2B33' ) )
			. self::p( array( 'left' => '28px', 'top' => '16px', 'width' => '0', 'height' => '0', 'border-top' => '4px solid transparent', 'border-bottom' => '4px solid transparent', 'border-left' => '8px solid #FFA54D' ) )
			. self::p( array( 'left' => '4px', 'top' => '4px', 'width' => '20px', 'height' => '14px', 'border-radius' => '60% 60% 50% 50%', 'background' => '#5FB6F2', 'transform-origin' => 'bottom right', 'animation' => 'scflap .5s ease-in-out infinite' ) );

		return self::wrap_mascot( 44, 38, $s, 'none', $parts );
	}

	/**
	 * Hero-scene decorative butterfly, ported verbatim from the prototype's
	 * butterfly(s) (frontend/front-page.dc.html). Not part of the 8-mascot
	 * Customizer roster — like the prototype, it's an unconditional hero
	 * decoration (wings recolor via the season's --secondary-color/
	 * --accent-color, unlike every other species' fixed fur/feather tones),
	 * gated only by season/viewport/motion via CSS (see
	 * .helanak-hero__butterfly in theme.css), matching the prototype's
	 * spring/summer-only, desktop-only, motion-respecting condition.
	 */
	public static function butterfly( float $s = 1 ): string {
		$parts =
			self::p( array( 'left' => '2px', 'top' => '2px', 'width' => '18px', 'height' => '26px', 'border-radius' => '60%', 'background' => 'var(--secondary-color)', 'opacity' => '.9', 'transform-origin' => 'right center', 'animation' => 'scflapw .5s ease-in-out infinite' ) )
			. self::p( array( 'left' => '24px', 'top' => '2px', 'width' => '18px', 'height' => '26px', 'border-radius' => '60%', 'background' => 'var(--accent-color)', 'opacity' => '.9', 'transform-origin' => 'left center', 'animation' => 'scflapw .5s ease-in-out .05s infinite' ) )
			. self::p( array( 'left' => '19px', 'top' => '4px', 'width' => '6px', 'height' => '24px', 'border-radius' => '99px', 'background' => '#5b5b66' ) );

		return self::wrap_mascot( 44, 38, $s, 'none', $parts );
	}
}
