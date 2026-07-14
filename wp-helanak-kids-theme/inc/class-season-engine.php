<?php
/**
 * Season Engine (spec section 7): resolves the active season from the
 * Customizer's mode setting, outputs the single `data-season` control point
 * on <html>, and writes the Customizer's color choices out as CSS custom
 * property overrides — every seasonal component in theme.css reads
 * var(--primary-color) etc., nothing hardcodes a season's hex value.
 *
 * Automatic mode is a pure server-side date calculation (site timezone),
 * deliberately not a per-request geolocation lookup, per spec 7.1's
 * "not per-request geolocation... which adds latency and privacy exposure."
 * It assumes a Northern-hemisphere calendar; there's no separate
 * hemisphere setting in this phase.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Season_Engine {

	/**
	 * Season => falling-particle color/shape, ported verbatim from the
	 * prototype's ambient() (frontend/front-page.dc.html): plain colored
	 * div shapes (a CSS border-radius trick, not emoji glyphs).
	 */
	private const PARTICLE_CONFIG = array(
		'spring' => array(
			'color' => '#FFB7D5',
			'shape' => '50% 0 50% 0',
			'glow'  => false,
		),
		'summer' => array(
			'color' => 'rgba(255,255,255,.9)',
			'shape' => '50%',
			'glow'  => true,
		),
		'autumn' => array(
			'color' => '#F59B42',
			'shape' => '0 50% 0 50%',
			'glow'  => false,
		),
		'winter' => array(
			'color' => '#fff',
			'shape' => '50%',
			'glow'  => false,
		),
	);

	private const PARTICLE_COUNT = 7;

	/**
	 * Hero-scene sky/hill/sun tokens, ported verbatim from the prototype's
	 * [data-season="..."] blocks in frontend/assets/css/theme.css (--sky,
	 * --hill1/2/3, --sun). These are presentation constants in the prototype,
	 * not Customizer-editable color pickers, so they're kept as literal
	 * per-season values here rather than wired to season_color_defaults().
	 */
	private const HERO_SCENE_TOKENS = array(
		'spring' => array(
			'sky'   => 'linear-gradient(180deg,#D6F1FF 0%,#F2FBEE 78%)',
			'hill1' => '#93E888',
			'hill2' => '#B4F0A8',
			'hill3' => '#D2F7C9',
			'sun'   => '#FFE9A8',
		),
		'summer' => array(
			'sky'   => 'linear-gradient(180deg,#A3DAFF 0%,#FFF4D4 82%)',
			'hill1' => '#A9DE6D',
			'hill2' => '#C6EA92',
			'hill3' => '#DFF3B7',
			'sun'   => '#FFD84D',
		),
		'autumn' => array(
			'sky'   => 'linear-gradient(180deg,#FFE0BC 0%,#FFF5E6 80%)',
			'hill1' => '#F0A052',
			'hill2' => '#F7BD7C',
			'hill3' => '#FCD9AC',
			'sun'   => '#FFD46C',
		),
		'winter' => array(
			'sky'   => 'linear-gradient(180deg,#C6E2FF 0%,#F4F7FF 80%)',
			'hill1' => '#E3EEFA',
			'hill2' => '#EEF5FE',
			'hill3' => '#F9FBFF',
			'sun'   => '#FFF3D6',
		),
	);

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'print_inline_style' ), 1 );
		add_filter( 'language_attributes', array( __CLASS__, 'filter_language_attributes' ) );
		add_filter( 'body_class', array( __CLASS__, 'filter_body_class' ) );
	}

	/**
	 * Automatic season from the current month at the site timezone.
	 * Mar–May spring, Jun–Aug summer, Sep–Nov autumn, Dec–Feb winter.
	 */
	public static function auto_season_from_date(): string {
		$month = (int) wp_date( 'n' );
		return match ( true ) {
			$month >= 3 && $month <= 5  => 'spring',
			$month >= 6 && $month <= 8  => 'summer',
			$month >= 9 && $month <= 11 => 'autumn',
			default                     => 'winter',
		};
	}

	public static function get_active_season(): string {
		$mode = get_theme_mod( 'helanak_season_mode', 'auto' );

		if ( 'auto' === $mode ) {
			return self::auto_season_from_date();
		}

		// Manual and campaign modes both pin to the chosen season; campaign
		// mode additionally layers a data-campaign attribute (see
		// get_active_campaign()) rather than swapping the palette out.
		return get_theme_mod( 'helanak_season_manual', 'spring' );
	}

	public static function get_active_campaign(): string {
		if ( 'campaign' !== get_theme_mod( 'helanak_season_mode', 'auto' ) ) {
			return '';
		}
		return (string) get_theme_mod( 'helanak_campaign_name', '' );
	}

	public static function filter_language_attributes( string $output ): string {
		$season   = self::get_active_season();
		$campaign = self::get_active_campaign();

		$output .= sprintf( ' data-season="%s"', esc_attr( $season ) );
		if ( $campaign ) {
			$output .= sprintf( ' data-campaign="%s"', esc_attr( sanitize_title( $campaign ) ) );
		}
		return $output;
	}

	public static function filter_body_class( array $classes ): array {
		$classes[] = 'season-' . self::get_active_season();
		return $classes;
	}

	/**
	 * Returns the ambient falling-particle layer markup, ported verbatim from
	 * the prototype's ambient() (frontend/front-page.dc.html): 7 plain colored
	 * shape-divs (not emoji), scoped to the hero scene only — the prototype
	 * never places this layer anywhere else. Gated by the "Ambient seasonal
	 * particles" Customizer toggle (helanak_character_ambient); hidden
	 * entirely for prefers-reduced-motion/narrow-viewport visitors via CSS
	 * (see theme.css' .helanak-hero__ambient rules), not server-side, since
	 * neither preference is available at render time.
	 */
	public static function render_ambient_particles(): string {
		if ( ! get_theme_mod( 'helanak_character_ambient', true ) ) {
			return '';
		}

		$season = self::get_active_season();
		$cfg    = self::PARTICLE_CONFIG[ $season ] ?? self::PARTICLE_CONFIG['spring'];

		$out = '<div class="helanak-hero__ambient" aria-hidden="true">';
		for ( $i = 0; $i < self::PARTICLE_COUNT; $i++ ) {
			$left     = 6 + $i * 13;
			$size     = 9 + ( $i % 3 ) * 4;
			$duration = 7 + $i * 1.3;
			$delay    = $i * 0.9;
			$style    = sprintf(
				'left:%1$s%%;width:%2$spx;height:%2$spx;background:%3$s;border-radius:%4$s;box-shadow:%5$s;animation:scfall %6$ss linear %7$ss infinite',
				esc_attr( $left ),
				esc_attr( $size ),
				esc_attr( $cfg['color'] ),
				esc_attr( $cfg['shape'] ),
				$cfg['glow'] ? '0 0 8px rgba(255,255,255,.6)' : 'none',
				esc_attr( $duration ),
				esc_attr( $delay )
			);
			$out .= sprintf( '<span class="helanak-hero__ambient-item" style="%s"></span>', $style );
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * Writes the season's colors + shared tokens + radius scale as inline
	 * :root custom properties, sourced entirely from Customizer settings so
	 * an admin changing a color picker is the single source of truth — no
	 * separate hardcoded palette to keep in sync.
	 */
	public static function print_inline_style(): void {
		$season_defaults = Helanak_Theme_Customizer::season_color_defaults();
		$shared_defaults = Helanak_Theme_Customizer::shared_color_defaults();
		$radius_defaults = Helanak_Theme_Customizer::radius_defaults();

		$css = ":root{\n";
		foreach ( array_keys( $shared_defaults ) as $key ) {
			$value = get_theme_mod( "helanak_color_shared_{$key}", $shared_defaults[ $key ] );
			$css  .= sprintf( "--%s-color:%s;\n", $key, esc_attr( $value ) );
		}
		foreach ( $radius_defaults as $key => $default ) {
			$value = (int) get_theme_mod( "helanak_radius_{$key}", $default );
			$css  .= sprintf( "--radius-%s:%dpx;\n", $key, $value );
		}
		$width = (int) get_theme_mod( 'helanak_container_width', 1180 );
		$css  .= sprintf( "--container-width:%dpx;\n", $width );
		$css  .= "}\n";

		$token_names = array( 'primary', 'secondary', 'accent' );
		foreach ( $season_defaults as $season => $defaults ) {
			$css .= sprintf( ":root[data-season=\"%s\"]{\n", esc_attr( $season ) );
			foreach ( $token_names as $i => $token ) {
				$value = get_theme_mod( "helanak_color_{$season}_{$i}", $defaults[ $i ] );
				$css  .= sprintf( "--%s-color:%s;\n", $token, esc_attr( $value ) );
			}
			$css .= "--gradient-primary:linear-gradient(135deg,var(--primary-color),var(--accent-color));\n";
			$css .= "--gradient-secondary:linear-gradient(135deg,var(--secondary-color),var(--accent-color));\n";
			foreach ( self::HERO_SCENE_TOKENS[ $season ] as $token => $value ) {
				$css .= sprintf( "--%s:%s;\n", $token, esc_attr( $value ) );
			}
			$css .= "}\n";
		}

		echo "<style id=\"helanak-season-tokens\">\n" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- built entirely from esc_attr()'d values above.
	}
}
