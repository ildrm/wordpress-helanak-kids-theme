<?php
/**
 * The theme's dynamic appearance settings: every season color, every
 * homepage/product-card detail's visibility, and every character's on/off
 * switch is a real Customizer control with postMessage live preview — per
 * the brief, "let the user change anything" rather than hardcoding a fixed
 * design. Settings are grouped in one "Helanak Kids" panel so they don't get
 * lost among core WP Customizer sections.
 *
 * Reading these values back out is done by Helanak_Season_Engine (colors +
 * season mode) and Helanak_Character_Engine (character/ambient toggles);
 * template files read section/card-detail visibility directly via
 * `helanak_show( 'section_key' )` / `helanak_card_shows( 'detail_key' )`
 * (see inc/template-tags.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Theme_Customizer {

	/** season => [primary, secondary, accent] defaults, per spec section 5. */
	public static function season_color_defaults(): array {
		return array(
			'spring' => array( '#8AE67E', '#FFB7D5', '#A8E4FF' ),
			'summer' => array( '#FFD84D', '#73C8FF', '#FFA54D' ),
			'autumn' => array( '#F59B42', '#E86752', '#FFD46C' ),
			'winter' => array( '#B8DFFF', '#DDD2FF', '#F7F7FF' ),
		);
	}

	public static function season_labels(): array {
		return array(
			'spring' => __( 'Spring', 'wp-helanak-kids-theme' ),
			'summer' => __( 'Summer', 'wp-helanak-kids-theme' ),
			'autumn' => __( 'Autumn', 'wp-helanak-kids-theme' ),
			'winter' => __( 'Winter', 'wp-helanak-kids-theme' ),
		);
	}

	/** Shared, season-independent tokens + their defaults. */
	public static function shared_color_defaults(): array {
		return array(
			'background' => '#FAF1DE',
			'surface'    => '#F3E8CF',
			'border'     => '#E6D8B8',
			'text'       => '#2B2B33',
		);
	}

	public static function radius_defaults(): array {
		return array(
			'sm' => 8,
			'md' => 16,
			'lg' => 28,
		);
	}

	/**
	 * character key => English label. Roster and order match the existing
	 * frontend/ prototype's 8 mascots exactly as listed in its `parade` array
	 * (frontend/front-page.dc.html) — this order also drives the "Meet our
	 * friends" render order and the Customizer's per-character toggle list.
	 */
	public static function character_labels(): array {
		return array(
			'rabbit'  => __( 'Rabbit', 'wp-helanak-kids-theme' ),
			'dog'     => __( 'Dog', 'wp-helanak-kids-theme' ),
			'cat'     => __( 'Cat', 'wp-helanak-kids-theme' ),
			'monkey'  => __( 'Monkey', 'wp-helanak-kids-theme' ),
			'bear'    => __( 'Teddy', 'wp-helanak-kids-theme' ),
			'fox'     => __( 'Fox', 'wp-helanak-kids-theme' ),
			'penguin' => __( 'Penguin', 'wp-helanak-kids-theme' ),
			'bird'    => __( 'Bird', 'wp-helanak-kids-theme' ),
			'turtle'  => __( 'Turtle', 'wp-helanak-kids-theme' ),
			'elephant' => __( 'Elephant', 'wp-helanak-kids-theme' ),
		);
	}

	/** Homepage/site section visibility toggles: key => [label, default]. */
	public static function section_toggles(): array {
		return array(
			'announcement'   => array( __( 'Announcement ticker', 'wp-helanak-kids-theme' ), true ),
			'hero'           => array( __( 'Hero banner', 'wp-helanak-kids-theme' ), true ),
			'category_slider' => array( __( 'Category ("worlds") slider', 'wp-helanak-kids-theme' ), true ),
			'product_rail'   => array( __( 'New / featured product rail', 'wp-helanak-kids-theme' ), true ),
			'age_selector'   => array( __( 'Shop-by-age selector', 'wp-helanak-kids-theme' ), true ),
			'flash_sale'     => array( __( 'Flash sale banner', 'wp-helanak-kids-theme' ), true ),
			'trust_badges'   => array( __( 'Trust badges strip', 'wp-helanak-kids-theme' ), true ),
			'friends_parade' => array( __( '"Meet our friends" mascot parade', 'wp-helanak-kids-theme' ), true ),
			'newsletter'     => array( __( 'Newsletter signup', 'wp-helanak-kids-theme' ), true ),
			'mini_cart'      => array( __( 'Floating mini-cart button', 'wp-helanak-kids-theme' ), true ),
		);
	}

	/** Product-card detail visibility toggles: key => [label, default]. */
	public static function card_toggles(): array {
		return array(
			'hover_image' => array( __( 'Hover secondary image', 'wp-helanak-kids-theme' ), true ),
			'rating'      => array( __( 'Star rating', 'wp-helanak-kids-theme' ), true ),
			'badge'       => array( __( 'Merchandising badge', 'wp-helanak-kids-theme' ), true ),
			'wishlist'    => array( __( 'Wishlist heart icon', 'wp-helanak-kids-theme' ), true ),
			'quick_view'  => array( __( 'Quick View button', 'wp-helanak-kids-theme' ), true ),
			'compare'     => array( __( 'Compare icon', 'wp-helanak-kids-theme' ), true ),
			'age_label'   => array( __( 'Age label', 'wp-helanak-kids-theme' ), true ),
			'stock'       => array( __( 'Stock indicator', 'wp-helanak-kids-theme' ), true ),
		);
	}

	public static function init(): void {
		add_action( 'customize_register', array( __CLASS__, 'register' ) );
		add_action( 'customize_preview_init', array( __CLASS__, 'enqueue_preview_script' ) );
		add_action( 'customize_controls_enqueue_scripts', array( __CLASS__, 'enqueue_controls_script' ) );
	}

	public static function sanitize_checkbox( $input ): bool {
		return (bool) $input;
	}

	public static function sanitize_season_mode( string $input ): string {
		return in_array( $input, array( 'auto', 'manual', 'campaign' ), true ) ? $input : 'auto';
	}

	public static function sanitize_season( string $input ): string {
		return array_key_exists( $input, self::season_color_defaults() ) ? $input : 'spring';
	}

	public static function sanitize_radius( $input ): int {
		return max( 0, min( 80, absint( $input ) ) );
	}

	public static function sanitize_width( $input ): int {
		return max( 600, min( 2400, absint( $input ) ) );
	}

	public static function register( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_panel( 'helanak_settings', array(
			'title'       => __( 'Helanak Kids Settings', 'wp-helanak-kids-theme' ),
			'description' => __( 'Season colors, character visibility, and per-feature/detail toggles for the Helanak Kids storefront.', 'wp-helanak-kids-theme' ),
			'priority'    => 20,
		) );

		self::register_season_section( $wp_customize );
		self::register_color_section( $wp_customize );
		self::register_character_section( $wp_customize );
		self::register_toggle_section(
			$wp_customize,
			'helanak_sections',
			__( 'Homepage & Site Sections', 'wp-helanak-kids-theme' ),
			__( 'Show or hide entire storefront sections.', 'wp-helanak-kids-theme' ),
			'helanak_show_',
			self::section_toggles()
		);
		self::register_toggle_section(
			$wp_customize,
			'helanak_card',
			__( 'Product Card Details', 'wp-helanak-kids-theme' ),
			__( 'Show or hide individual small details on every product card.', 'wp-helanak-kids-theme' ),
			'helanak_card_show_',
			self::card_toggles()
		);
		self::register_layout_section( $wp_customize );
		self::register_contact_section( $wp_customize );
	}

	private static function register_contact_section( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section( 'helanak_contact_details', array( 'title' => __( 'Public contact details', 'wp-helanak-kids-theme' ), 'description' => __( 'Only details entered here are shown publicly. The site administrator email stays private.', 'wp-helanak-kids-theme' ), 'panel' => 'helanak_settings' ) );
		$fields = array(
			'email' => array( __( 'Email', 'wp-helanak-kids-theme' ), 'email', 'sanitize_email' ),
			'phone' => array( __( 'Phone', 'wp-helanak-kids-theme' ), 'text', 'sanitize_text_field' ),
			'address' => array( __( 'Address', 'wp-helanak-kids-theme' ), 'textarea', 'sanitize_textarea_field' ),
			'hours' => array( __( 'Support hours', 'wp-helanak-kids-theme' ), 'text', 'sanitize_text_field' ),
		);
		foreach ( $fields as $key => $field ) {
			$id = 'helanak_contact_' . $key;
			$wp_customize->add_setting( $id, array( 'default' => '', 'sanitize_callback' => $field[2], 'transport' => 'refresh' ) );
			$wp_customize->add_control( $id, array( 'type' => $field[1], 'section' => 'helanak_contact_details', 'label' => $field[0] ) );
		}
	}

	private static function register_season_section( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section( 'helanak_season', array(
			'title' => __( 'Season Engine', 'wp-helanak-kids-theme' ),
			'panel' => 'helanak_settings',
		) );

		$wp_customize->add_setting( 'helanak_season_mode', array(
			'default'           => 'auto',
			'sanitize_callback' => array( __CLASS__, 'sanitize_season_mode' ),
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'helanak_season_mode', array(
			'type'        => 'select',
			'section'     => 'helanak_season',
			'label'       => __( 'Mode', 'wp-helanak-kids-theme' ),
			'description' => __( 'Automatic derives the season from today\'s date at the site timezone. Manual lets you pin one season year-round.', 'wp-helanak-kids-theme' ),
			'choices'     => array(
				'auto'     => __( 'Automatic (by date)', 'wp-helanak-kids-theme' ),
				'manual'   => __( 'Manual', 'wp-helanak-kids-theme' ),
				'campaign' => __( 'Campaign (season + event layer)', 'wp-helanak-kids-theme' ),
			),
		) );

		$wp_customize->add_setting( 'helanak_season_manual', array(
			'default'           => 'spring',
			'sanitize_callback' => array( __CLASS__, 'sanitize_season' ),
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'helanak_season_manual', array(
			'type'    => 'select',
			'section' => 'helanak_season',
			'label'   => __( 'Manual season', 'wp-helanak-kids-theme' ),
			'choices' => self::season_labels(),
		) );

		$wp_customize->add_setting( 'helanak_campaign_name', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'helanak_campaign_name', array(
			'type'        => 'text',
			'section'     => 'helanak_season',
			'label'       => __( 'Campaign name', 'wp-helanak-kids-theme' ),
			'description' => __( 'e.g. "Back to School", "Eid", "Black Friday". Layers on top of the base season as a data-campaign attribute for campaign-specific CSS/banners; it does not replace the season palette.', 'wp-helanak-kids-theme' ),
		) );
	}

	private static function register_color_section( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section( 'helanak_colors', array(
			'title' => __( 'Colors', 'wp-helanak-kids-theme' ),
			'panel' => 'helanak_settings',
		) );

		$token_labels = array(
			0 => __( 'Primary', 'wp-helanak-kids-theme' ),
			1 => __( 'Secondary', 'wp-helanak-kids-theme' ),
			2 => __( 'Accent', 'wp-helanak-kids-theme' ),
		);

		foreach ( self::season_color_defaults() as $season => $defaults ) {
			foreach ( $defaults as $i => $hex ) {
				$id = "helanak_color_{$season}_{$i}";
				$wp_customize->add_setting( $id, array(
					'default'           => $hex,
					'sanitize_callback' => 'sanitize_hex_color',
					'transport'         => 'postMessage',
				) );
				$wp_customize->add_control( new \WP_Customize_Color_Control(
					$wp_customize,
					$id,
					array(
						'label'   => sprintf(
							/* translators: 1: season label, 2: color token (Primary/Secondary/Accent) */
							__( '%1$s — %2$s', 'wp-helanak-kids-theme' ),
							self::season_labels()[ $season ],
							$token_labels[ $i ]
						),
						'section' => 'helanak_colors',
					)
				) );
			}
		}

		foreach ( self::shared_color_defaults() as $key => $hex ) {
			$id = "helanak_color_shared_{$key}";
			$wp_customize->add_setting( $id, array(
				'default'           => $hex,
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			) );
			$wp_customize->add_control( new \WP_Customize_Color_Control(
				$wp_customize,
				$id,
				array(
					'label'   => sprintf(
						/* translators: %s: token name, e.g. Background */
						__( 'Shared — %s', 'wp-helanak-kids-theme' ),
						ucfirst( $key )
					),
					'section' => 'helanak_colors',
				)
			) );
		}
	}

	private static function register_character_section( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section( 'helanak_characters', array(
			'title' => __( 'Character Engine', 'wp-helanak-kids-theme' ),
			'panel' => 'helanak_settings',
		) );

		$wp_customize->add_setting( 'helanak_character_enabled', array(
			'default'           => true,
			'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'helanak_character_enabled', array(
			'type'        => 'checkbox',
			'section'     => 'helanak_characters',
			'label'       => __( 'Show mascot characters at all', 'wp-helanak-kids-theme' ),
			'description' => __( 'Master switch. Turning this off hides every character regardless of the per-character toggles below.', 'wp-helanak-kids-theme' ),
		) );

		$wp_customize->add_setting( 'helanak_character_ambient', array(
			'default'           => true,
			'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'helanak_character_ambient', array(
			'type'        => 'checkbox',
			'section'     => 'helanak_characters',
			'label'       => __( 'Ambient seasonal particles', 'wp-helanak-kids-theme' ),
			'description' => __( 'Falling petals/leaves/snow etc. Always off when the visitor has prefers-reduced-motion enabled, regardless of this setting.', 'wp-helanak-kids-theme' ),
		) );

		$wp_customize->add_setting( 'helanak_character_idle', array(
			'default'           => true,
			'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'helanak_character_idle', array(
			'type'        => 'checkbox',
			'section'     => 'helanak_characters',
			'label'       => __( 'Context-aware idle behaviors', 'wp-helanak-kids-theme' ),
			'description' => __( 'Dog hops on add-to-cart, cat sleeps when idle, a bird flies past on scroll, etc. (spec section 8.4).', 'wp-helanak-kids-theme' ),
		) );

		$wp_customize->add_setting( 'helanak_character_surprises', array( 'default' => true, 'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ), 'transport' => 'refresh' ) );
		$wp_customize->add_control( 'helanak_character_surprises', array( 'type' => 'checkbox', 'section' => 'helanak_characters', 'label' => __( 'Surprise animal visitors', 'wp-helanak-kids-theme' ), 'description' => __( 'A matching animal friend joins the seasonal hero. It never covers shopping controls.', 'wp-helanak-kids-theme' ) ) );

		foreach ( self::character_labels() as $key => $label ) {
			$id = "helanak_character_{$key}";
			$wp_customize->add_setting( $id, array(
				'default'           => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
				'transport'         => 'postMessage',
			) );
			$wp_customize->add_control( $id, array(
				'type'    => 'checkbox',
				'section' => 'helanak_characters',
				'label'   => $label,
			) );
		}
	}

	private static function register_toggle_section( \WP_Customize_Manager $wp_customize, string $section_id, string $title, string $description, string $prefix, array $toggles ): void {
		$wp_customize->add_section( $section_id, array(
			'title'       => $title,
			'description' => $description,
			'panel'       => 'helanak_settings',
		) );

		foreach ( $toggles as $key => list( $label, $default ) ) {
			$id = $prefix . $key;
			$wp_customize->add_setting( $id, array(
				'default'           => $default,
				'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
				'transport'         => 'postMessage',
			) );
			$wp_customize->add_control( $id, array(
				'type'    => 'checkbox',
				'section' => $section_id,
				'label'   => $label,
			) );
		}
	}

	private static function register_layout_section( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section( 'helanak_layout', array(
			'title' => __( 'Layout', 'wp-helanak-kids-theme' ),
			'panel' => 'helanak_settings',
		) );

		$wp_customize->add_setting( 'helanak_container_width', array(
			'default'           => 1180,
			'sanitize_callback' => array( __CLASS__, 'sanitize_width' ),
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'helanak_container_width', array(
			'type'        => 'number',
			'section'     => 'helanak_layout',
			'label'       => __( 'Content max width (px)', 'wp-helanak-kids-theme' ),
			'input_attrs' => array( 'min' => 600, 'max' => 2400, 'step' => 10 ),
		) );

		foreach ( self::radius_defaults() as $key => $default ) {
			$id = "helanak_radius_{$key}";
			$wp_customize->add_setting( $id, array(
				'default'           => $default,
				'sanitize_callback' => array( __CLASS__, 'sanitize_radius' ),
				'transport'         => 'postMessage',
			) );
			$wp_customize->add_control( $id, array(
				'type'        => 'number',
				'section'     => 'helanak_layout',
				'label'       => sprintf(
					/* translators: %s: sm/md/lg radius key */
					__( 'Corner radius — %s (px)', 'wp-helanak-kids-theme' ),
					strtoupper( $key )
				),
				'input_attrs' => array( 'min' => 0, 'max' => 80, 'step' => 1 ),
			) );
		}
	}

	public static function enqueue_preview_script(): void {
		wp_enqueue_script(
			'helanak-customizer-preview',
			HELANAK_THEME_URI . '/assets/js/customizer-preview.js',
			array( 'customize-preview', 'jquery' ),
			HELANAK_THEME_VERSION,
			true
		);
		wp_localize_script( 'helanak-customizer-preview', 'helanakCustomizerData', array(
			'seasons'    => array_keys( self::season_color_defaults() ),
			'characters' => array_keys( self::character_labels() ),
			'sections'   => array_keys( self::section_toggles() ),
			'cardParts'  => array_keys( self::card_toggles() ),
		) );
	}

	public static function enqueue_controls_script(): void {
		wp_enqueue_script(
			'helanak-customizer-controls',
			HELANAK_THEME_URI . '/assets/js/customizer-controls.js',
			array( 'customize-controls' ),
			HELANAK_THEME_VERSION,
			true
		);
		wp_localize_script( 'helanak-customizer-controls', 'helanakCustomizerData', array(
			'characters' => array_keys( self::character_labels() ),
		) );
	}
}
