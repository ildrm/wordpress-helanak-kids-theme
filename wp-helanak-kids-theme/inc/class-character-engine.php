<?php
/**
 * Storybook SVG character renderer.
 *
 * Character files are trusted theme assets with standardized part classes.
 * Context-aware behavior remains event-driven in assets/js/theme.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Character_Engine {

	/** @var array<string,string> */
	private static array $svg_cache = array();

	public static function init(): void {
		// Templates call render_character() directly.
	}

	public static function master_enabled(): bool {
		return (bool) get_theme_mod( 'helanak_character_enabled', true );
	}

	public static function is_enabled( string $key ): bool {
		return self::master_enabled()
			&& array_key_exists( $key, Helanak_Theme_Customizer::character_labels() )
			&& (bool) get_theme_mod( "helanak_character_{$key}", true );
	}

	/** @return string[] */
	public static function enabled_characters(): array {
		if ( ! self::master_enabled() ) {
			return array();
		}
		return array_values(
			array_filter(
				array_keys( Helanak_Theme_Customizer::character_labels() ),
				array( __CLASS__, 'is_enabled' )
			)
		);
	}

	/**
	 * Render a trusted local SVG using the existing character API.
	 *
	 * @param string $key  Character key.
	 * @param array  $args size, scale, behavior, class, hop, wave, awake.
	 */
	public static function render_character( string $key, array $args = array() ): string {
		if ( ! self::is_enabled( $key ) ) {
			return '';
		}

		$dimensions = self::dimensions();
		if ( ! isset( $dimensions[ $key ] ) ) {
			return '';
		}

		$svg = self::load_svg( $key );
		if ( '' === $svg ) {
			return '';
		}

		$natural_width = $dimensions[ $key ][0];
		$scale         = isset( $args['scale'] ) ? max( 0.1, (float) $args['scale'] ) : ( max( 24, absint( $args['size'] ?? 96 ) ) / $natural_width );
		$size          = (int) round( $natural_width * $scale );
		$behavior      = sanitize_key( $args['behavior'] ?? 'idle' );
		$classes       = array( 'helanak-character', 'helanak-character--' . $key, 'helanak-character--svg' );

		foreach ( preg_split( '/\s+/', (string) ( $args['class'] ?? '' ) ) as $extra_class ) {
			$extra_class = sanitize_html_class( $extra_class );
			if ( $extra_class ) {
				$classes[] = $extra_class;
			}
		}
		if ( ! empty( $args['hop'] ) ) {
			$classes[] = 'has-hop';
		}
		if ( ! empty( $args['wave'] ) ) {
			$classes[] = 'has-wave';
		}
		if ( array_key_exists( 'awake', $args ) && ! $args['awake'] ) {
			$classes[] = 'is-sleeping-art';
		}

		return sprintf(
			'<span class="%1$s" data-character="%2$s" data-behavior="%3$s" style="--helanak-char-size:%4$dpx" aria-hidden="true">%5$s</span>',
			esc_attr( implode( ' ', array_unique( $classes ) ) ),
			esc_attr( $key ),
			esc_attr( $behavior ),
			$size,
			$svg // phpcs:ignore WordPress.Security.EscapeOutput -- fixed, trusted theme SVG selected from a closed whitelist.
		);
	}

	private static function dimensions(): array {
		return array(
			'rabbit'  => array( 150, 196 ),
			'dog'     => array( 136, 152 ),
			'cat'     => array( 150, 106 ),
			'bear'    => array( 150, 186 ),
			'fox'     => array( 150, 176 ),
			'penguin' => array( 130, 168 ),
			'monkey'  => array( 82, 74 ),
			'bird'    => array( 44, 38 ),
			'turtle'  => array( 160, 130 ),
			'elephant' => array( 180, 184 ),
		);
	}

	private static function load_svg( string $key ): string {
		if ( isset( self::$svg_cache[ $key ] ) ) {
			return self::$svg_cache[ $key ];
		}
		if ( ! isset( self::dimensions()[ $key ] ) ) {
			return '';
		}

		$path = HELANAK_THEME_DIR . '/assets/images/characters/' . $key . '.svg';
		if ( ! is_readable( $path ) ) {
			self::$svg_cache[ $key ] = '';
			return '';
		}

		$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local, versioned asset.
		self::$svg_cache[ $key ] = is_string( $svg ) ? trim( $svg ) : '';
		return self::$svg_cache[ $key ];
	}

	/** Decorative hero butterfly, kept as a lightweight inline vector. */
	public static function butterfly( float $scale = 1.0 ): string {
		$width = max( 24, (int) round( 68 * $scale ) );
		return sprintf(
			'<span class="helanak-butterfly-art" style="width:%dpx" aria-hidden="true"><svg viewBox="0 0 68 50" role="presentation"><path class="char-wing char-wing--left" d="M31 24C18 1 1 6 8 27c4 12 16 10 23 3Z" fill="#FF8FC8"/><path class="char-wing char-wing--right" d="M37 24C50 1 67 6 60 27c-4 12-16 10-23 3Z" fill="#8FD7FF"/><path d="M34 18c5 0 5 22 0 22s-5-22 0-22Z" fill="#59466F"/><path d="M32 18c-5-9-10-8-12-7M36 18c5-9 10-8 12-7" fill="none" stroke="#59466F" stroke-width="2" stroke-linecap="round"/></svg></span>',
			$width
		);
	}
}
