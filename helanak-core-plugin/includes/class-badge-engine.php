<?php
/**
 * Dynamic manual and automatic merchandising badges.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Badge_Engine {

	const TAXONOMY       = 'helanak_badge';
	const OPTION_NAME    = 'helanak_core_badge_settings';
	const META_PREFIX    = '_helanak_badge_';
	const META_LABEL_FA  = 'helanak_badge_label_fa';
	const META_BG        = 'helanak_badge_bg';
	const META_FG        = 'helanak_badge_fg';
	const META_PRIORITY  = 'helanak_badge_priority';
	const META_ENABLED   = 'helanak_badge_enabled';
	const NONCE_ACTION   = 'helanak_save_badge_meta';
	const NONCE_NAME     = 'helanak_badge_meta_nonce';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
		add_action( self::TAXONOMY . '_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'save_term_fields' ) );
		add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'save_term_fields' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'option_page_capability_helanak_badges', array( __CLASS__, 'settings_capability' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_badges' ) );
	}

	public static function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			array(
				'labels'            => array(
					'name'          => __( 'Product Badges', 'helanak-core' ),
					'singular_name' => __( 'Product Badge', 'helanak-core' ),
					'menu_name'     => __( 'Badges', 'helanak-core' ),
					'add_new_item'  => __( 'Add New Badge', 'helanak-core' ),
					'edit_item'     => __( 'Edit Badge', 'helanak-core' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'meta_box_cb'       => false,
				'rewrite'           => false,
			)
		);
	}

	/** Starter definitions used once by the installer. */
	public static function starter_badges(): array {
		return array(
			'limited'       => array( 'label' => 'Limited', 'label_fa' => 'محدود', 'bg' => '#2B2B33', 'fg' => '#FFFFFF', 'priority' => 90 ),
			'hot'           => array( 'label' => 'Hot', 'label_fa' => 'داغ', 'bg' => '#FF7E55', 'fg' => '#FFFFFF', 'priority' => 85 ),
			'trending'      => array( 'label' => 'Trending', 'label_fa' => 'پرطرفدار', 'bg' => '#73C8FF', 'fg' => '#173247', 'priority' => 80 ),
			'best_seller'   => array( 'label' => 'Best Seller', 'label_fa' => 'پرفروش', 'bg' => '#FFD84D', 'fg' => '#4A3B00', 'priority' => 75 ),
			'recommended'   => array( 'label' => 'Recommended', 'label_fa' => 'پیشنهاد ما', 'bg' => '#B8DFFF', 'fg' => '#1B3550', 'priority' => 70 ),
			'gift'          => array( 'label' => 'Gift', 'label_fa' => 'هدیه', 'bg' => '#FFB7D5', 'fg' => '#5B1F3A', 'priority' => 65 ),
			'educational'   => array( 'label' => 'Educational', 'label_fa' => 'آموزشی', 'bg' => '#A8E4FF', 'fg' => '#1B4358', 'priority' => 60 ),
			'organic'       => array( 'label' => 'Organic', 'label_fa' => 'ارگانیک', 'bg' => '#A8C86A', 'fg' => '#233A0E', 'priority' => 55 ),
			'safe_material' => array( 'label' => 'Safe Material', 'label_fa' => 'مواد ایمن', 'bg' => '#DDD2FF', 'fg' => '#372766', 'priority' => 50 ),
		);
	}

	public static function seed_default_terms(): void {
		foreach ( self::starter_badges() as $slug => $data ) {
			$existing = term_exists( $slug, self::TAXONOMY );
			if ( $existing ) {
				$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
			} else {
				$created = wp_insert_term( $data['label'], self::TAXONOMY, array( 'slug' => $slug ) );
				if ( is_wp_error( $created ) ) {
					continue;
				}
				$term_id = (int) $created['term_id'];
			}
			self::seed_term_meta( $term_id, $data );
		}
	}

	public static function seed_term_meta( int $term_id, array $data ): void {
		$map = array(
			self::META_LABEL_FA => 'label_fa',
			self::META_BG       => 'bg',
			self::META_FG       => 'fg',
			self::META_PRIORITY => 'priority',
			self::META_ENABLED  => 'enabled',
		);
		$data['enabled'] = 'yes';
		foreach ( $map as $meta_key => $data_key ) {
			if ( '' === (string) get_term_meta( $term_id, $meta_key, true ) ) {
				update_term_meta( $term_id, $meta_key, $data[ $data_key ] );
			}
		}
	}

	/** Dynamic compatibility helper: slug => current English label. */
	public static function manual_badge_keys(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) || ! $terms ) {
			$keys = array();
			foreach ( self::starter_badges() as $slug => $data ) {
				$keys[ $slug ] = $data['label'];
			}
			return $keys;
		}

		$keys = array();
		foreach ( $terms as $term ) {
			$keys[ $term->slug ] = $term->name;
		}
		return $keys;
	}

	public static function automatic_defaults(): array {
		return array(
			'sale' => array(
				'enabled' => 'yes', 'label_en' => 'Sale', 'label_fa' => 'فروش ویژه',
				'bg' => '#E86752', 'fg' => '#FFFFFF', 'priority' => 100,
			),
			'new' => array(
				'enabled' => 'yes', 'label_en' => 'New', 'label_fa' => 'جدید',
				'bg' => '#8AE67E', 'fg' => '#1D3B18', 'priority' => 40, 'days' => 14,
			),
			'age_specific' => array(
				'enabled' => 'yes', 'label_en' => '', 'label_fa' => '',
				'bg' => '#F3E8CF', 'fg' => '#5B4A2A', 'priority' => 30,
			),
		);
	}

	public static function automatic_settings(): array {
		$saved = get_option( self::OPTION_NAME, array() );
		$output = self::automatic_defaults();
		if ( ! is_array( $saved ) ) {
			return $output;
		}
		foreach ( $output as $key => $defaults ) {
			if ( isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ) {
				$output[ $key ] = array_merge( $defaults, $saved[ $key ] );
			}
		}
		return $output;
	}

	public static function settings_capability(): string {
		return 'manage_woocommerce';
	}

	public static function register_settings(): void {
		register_setting(
			'helanak_badges',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::automatic_defaults(),
			)
		);
	}

	public static function sanitize_settings( $input ): array {
		$input  = is_array( $input ) ? $input : array();
		$output = self::automatic_defaults();
		foreach ( $output as $key => $defaults ) {
			$rule = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();
			$output[ $key ]['enabled']  = isset( $rule['enabled'] ) ? 'yes' : 'no';
			$label_en = $rule['label_en'] ?? $defaults['label_en'];
			$label_fa = $rule['label_fa'] ?? $defaults['label_fa'];
			$bg       = $rule['bg'] ?? '';
			$fg       = $rule['fg'] ?? '';
			$priority = $rule['priority'] ?? $defaults['priority'];
			$output[ $key ]['label_en'] = is_scalar( $label_en ) ? ( sanitize_text_field( (string) $label_en ) ?: $defaults['label_en'] ) : $defaults['label_en'];
			$output[ $key ]['label_fa'] = is_scalar( $label_fa ) ? ( sanitize_text_field( (string) $label_fa ) ?: $defaults['label_fa'] ) : $defaults['label_fa'];
			$output[ $key ]['bg']       = is_string( $bg ) ? ( sanitize_hex_color( $bg ) ?: $defaults['bg'] ) : $defaults['bg'];
			$output[ $key ]['fg']       = is_string( $fg ) ? ( sanitize_hex_color( $fg ) ?: $defaults['fg'] ) : $defaults['fg'];
			$output[ $key ]['priority'] = is_scalar( $priority ) ? min( 999, absint( $priority ) ) : $defaults['priority'];
			if ( 'new' === $key ) {
				$days = $rule['days'] ?? $defaults['days'];
				$output[ $key ]['days'] = is_scalar( $days ) ? max( 1, min( 365, absint( $days ) ) ) : $defaults['days'];
			}
		}
		return $output;
	}

	public static function register_settings_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Helanak Badge Settings', 'helanak-core' ),
			__( 'Helanak Badges', 'helanak-core' ),
			'manage_woocommerce',
			'helanak-badges',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$settings = self::automatic_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Helanak Badge Settings', 'helanak-core' ); ?></h1>
			<p>
				<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . self::TAXONOMY . '&post_type=product' ) ); ?>">
					<?php esc_html_e( 'Manage manual badge definitions', 'helanak-core' ); ?>
				</a>
			</p>
			<form action="options.php" method="post">
				<?php settings_fields( 'helanak_badges' ); ?>
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Automatic badge', 'helanak-core' ); ?></th>
						<th><?php esc_html_e( 'Enabled', 'helanak-core' ); ?></th>
						<th><?php esc_html_e( 'English label', 'helanak-core' ); ?></th>
						<th><?php esc_html_e( 'Persian label', 'helanak-core' ); ?></th>
						<th><?php esc_html_e( 'Background', 'helanak-core' ); ?></th>
						<th><?php esc_html_e( 'Text', 'helanak-core' ); ?></th>
						<th><?php esc_html_e( 'Priority', 'helanak-core' ); ?></th>
						<th><?php esc_html_e( 'New days', 'helanak-core' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $settings as $key => $rule ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></th>
								<td><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . "[{$key}][enabled]" ); ?>" value="1" <?php checked( 'yes', $rule['enabled'] ); ?>></td>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_NAME . "[{$key}][label_en]" ); ?>" value="<?php echo esc_attr( $rule['label_en'] ); ?>" <?php disabled( 'age_specific', $key ); ?>></td>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_NAME . "[{$key}][label_fa]" ); ?>" value="<?php echo esc_attr( $rule['label_fa'] ); ?>" <?php disabled( 'age_specific', $key ); ?>></td>
								<td><input type="color" name="<?php echo esc_attr( self::OPTION_NAME . "[{$key}][bg]" ); ?>" value="<?php echo esc_attr( $rule['bg'] ); ?>"></td>
								<td><input type="color" name="<?php echo esc_attr( self::OPTION_NAME . "[{$key}][fg]" ); ?>" value="<?php echo esc_attr( $rule['fg'] ); ?>"></td>
								<td><input type="number" min="0" max="999" name="<?php echo esc_attr( self::OPTION_NAME . "[{$key}][priority]" ); ?>" value="<?php echo esc_attr( $rule['priority'] ); ?>"></td>
								<td>
									<?php if ( 'new' === $key ) : ?>
										<input type="number" min="1" max="365" name="<?php echo esc_attr( self::OPTION_NAME . "[{$key}][days]" ); ?>" value="<?php echo esc_attr( $rule['days'] ); ?>">
									<?php else : ?>
										&mdash;
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function render_add_fields(): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		self::render_term_fields();
	}

	public static function render_edit_fields( \WP_Term $term ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		self::render_term_fields( $term );
	}

	private static function render_term_fields( ?\WP_Term $term = null ): void {
		$values = array(
			self::META_LABEL_FA => $term ? get_term_meta( $term->term_id, self::META_LABEL_FA, true ) : '',
			self::META_BG       => $term ? ( get_term_meta( $term->term_id, self::META_BG, true ) ?: '#E9EEFF' ) : '#E9EEFF',
			self::META_FG       => $term ? ( get_term_meta( $term->term_id, self::META_FG, true ) ?: '#3A4FB0' ) : '#3A4FB0',
			self::META_PRIORITY => $term && '' !== (string) get_term_meta( $term->term_id, self::META_PRIORITY, true ) ? get_term_meta( $term->term_id, self::META_PRIORITY, true ) : 50,
			self::META_ENABLED  => $term ? ( get_term_meta( $term->term_id, self::META_ENABLED, true ) ?: 'yes' ) : 'yes',
		);
		$fields = array(
			self::META_LABEL_FA => array( __( 'Persian label', 'helanak-core' ), 'text' ),
			self::META_BG       => array( __( 'Background color', 'helanak-core' ), 'color' ),
			self::META_FG       => array( __( 'Text color', 'helanak-core' ), 'color' ),
			self::META_PRIORITY => array( __( 'Priority (higher appears first)', 'helanak-core' ), 'number' ),
		);
		foreach ( $fields as $key => $field ) {
			$is_edit = null !== $term;
			if ( $is_edit ) {
				echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label></th><td>';
			} else {
				echo '<div class="form-field"><label for="' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label>';
			}
			printf(
				'<input type="%1$s" name="%2$s" id="%2$s" value="%3$s"%4$s>',
				esc_attr( $field[1] ),
				esc_attr( $key ),
				esc_attr( (string) $values[ $key ] ),
				'number' === $field[1] ? ' min="0" max="999"' : ''
			);
			echo $is_edit ? '</td></tr>' : '</div>';
		}

		$is_edit = null !== $term;
		if ( $is_edit ) {
			echo '<tr class="form-field"><th scope="row">' . esc_html__( 'Enabled', 'helanak-core' ) . '</th><td>';
		} else {
			echo '<div class="form-field"><label>' . esc_html__( 'Enabled', 'helanak-core' ) . '</label>';
		}
		echo '<label><input type="checkbox" name="' . esc_attr( self::META_ENABLED ) . '" value="yes" ' . checked( 'yes', $values[ self::META_ENABLED ], false ) . '> ' . esc_html__( 'Available for products and storefront display', 'helanak-core' ) . '</label>';
		echo $is_edit ? '</td></tr>' : '</div>';
	}

	public static function save_term_fields( int $term_id ): void {
		$taxonomy = get_taxonomy( self::TAXONOMY );
		if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return;
		}
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? wp_unslash( $_POST[ self::NONCE_NAME ] ) : '';
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( $nonce ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( isset( $_POST[ self::META_LABEL_FA ] ) ) {
			$label_fa = wp_unslash( $_POST[ self::META_LABEL_FA ] );
			if ( is_scalar( $label_fa ) ) {
				update_term_meta( $term_id, self::META_LABEL_FA, sanitize_text_field( (string) $label_fa ) );
			}
		}
		foreach ( array( self::META_BG, self::META_FG ) as $color_key ) {
			$raw   = isset( $_POST[ $color_key ] ) ? wp_unslash( $_POST[ $color_key ] ) : '';
			$color = is_string( $raw ) ? sanitize_hex_color( $raw ) : null;
			if ( $color ) {
				update_term_meta( $term_id, $color_key, $color );
			}
		}
		$raw_priority = isset( $_POST[ self::META_PRIORITY ] ) ? wp_unslash( $_POST[ self::META_PRIORITY ] ) : 0;
		$priority     = is_scalar( $raw_priority ) ? min( 999, absint( $raw_priority ) ) : 0;
		update_term_meta( $term_id, self::META_PRIORITY, $priority );
		update_term_meta( $term_id, self::META_ENABLED, isset( $_POST[ self::META_ENABLED ] ) ? 'yes' : 'no' );
	}

	public static function add_tab( array $tabs ): array {
		$tabs['helanak_badges'] = array(
			'label'    => __( 'Badges', 'helanak-core' ),
			'target'   => 'helanak_badges_data',
			'class'    => array(),
			'priority' => 22,
		);
		return $tabs;
	}

	public static function render_panel(): void {
		global $post;
		$terms    = self::all_manual_terms();
		$assigned = $post ? wp_get_object_terms( $post->ID, self::TAXONOMY, array( 'fields' => 'ids' ) ) : array();
		$assigned = is_wp_error( $assigned ) ? array() : array_map( 'intval', $assigned );
		?>
		<div id="helanak_badges_data" class="panel woocommerce_options_panel"><div class="options_group">
			<p style="padding:0 12px;color:#666">
				<?php esc_html_e( 'Sale, New, and age-specific badges are automatic. Assign any enabled manual badges below.', 'helanak-core' ); ?>
			</p>
			<?php foreach ( $terms as $term ) : ?>
				<p class="form-field helanak-badge-definition">
					<label for="helanak_badge_term_<?php echo esc_attr( $term->term_id ); ?>"><?php echo esc_html( self::term_label( $term ) ); ?></label>
					<input
						type="checkbox"
						class="checkbox"
						name="helanak_badge_terms[]"
						id="helanak_badge_term_<?php echo esc_attr( $term->term_id ); ?>"
						value="<?php echo esc_attr( $term->term_id ); ?>"
						<?php checked( in_array( $term->term_id, $assigned, true ) ); ?>
					>
				</p>
			<?php endforeach; ?>
			<p style="padding:0 12px">
				<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . self::TAXONOMY . '&post_type=product' ) ); ?>"><?php esc_html_e( 'Manage badge definitions', 'helanak-core' ); ?></a>
			</p>
		</div></div>
		<?php
	}

	public static function save_product_badges( int $post_id ): void {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$nonce = isset( $_POST['woocommerce_meta_nonce'] ) ? wp_unslash( $_POST['woocommerce_meta_nonce'] ) : '';
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( $nonce ), 'woocommerce_save_data' ) ) {
			return;
		}

		$submitted = isset( $_POST['helanak_badge_terms'] ) ? (array) wp_unslash( $_POST['helanak_badge_terms'] ) : array();
		$term_ids  = array_map( static fn( $value ) => is_scalar( $value ) ? absint( $value ) : 0, $submitted );
		$term_ids = array_values( array_filter( $term_ids ) );

		// Disabled definitions are hidden from the editor but retain their
		// relationships so temporarily disabling a badge is reversible.
		$assigned = wp_get_object_terms( $post_id, self::TAXONOMY );
		if ( ! is_wp_error( $assigned ) ) {
			foreach ( $assigned as $term ) {
				if ( 'no' === get_term_meta( $term->term_id, self::META_ENABLED, true ) ) {
					$term_ids[] = $term->term_id;
				}
			}
		}
		$term_ids = array_values( array_unique( array_map( 'absint', $term_ids ) ) );
		wp_set_object_terms( $post_id, $term_ids, self::TAXONOMY, false );
	}

	private static function all_manual_terms(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'meta_query' => array(
					'relation' => 'OR',
					array( 'key' => self::META_ENABLED, 'value' => 'yes' ),
					array( 'key' => self::META_ENABLED, 'compare' => 'NOT EXISTS' ),
				),
			)
		);
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		usort(
			$terms,
			static fn( $a, $b ) => (int) get_term_meta( $b->term_id, self::META_PRIORITY, true ) <=> (int) get_term_meta( $a->term_id, self::META_PRIORITY, true )
		);
		return $terms;
	}

	private static function term_label( \WP_Term $term ): string {
		if ( 'fa' === substr( get_locale(), 0, 2 ) ) {
			$fa = (string) get_term_meta( $term->term_id, self::META_LABEL_FA, true );
			return $fa ?: $term->name;
		}
		return $term->name;
	}

	public static function new_window_days(): int {
		$settings = self::automatic_settings();
		return (int) $settings['new']['days'];
	}

	/** Current colors keyed by badge slug; retained for filter compatibility. */
	public static function default_colors(): array {
		$settings = self::automatic_settings();
		$colors   = array();
		foreach ( $settings as $key => $rule ) {
			$defaults       = self::automatic_defaults()[ $key ];
			$colors[ $key ] = array(
				sanitize_hex_color( $rule['bg'] ) ?: $defaults['bg'],
				sanitize_hex_color( $rule['fg'] ) ?: $defaults['fg'],
			);
		}
		foreach ( self::all_manual_terms() as $term ) {
			$colors[ $term->slug ] = array(
				sanitize_hex_color( get_term_meta( $term->term_id, self::META_BG, true ) ) ?: '#EEEEEE',
				sanitize_hex_color( get_term_meta( $term->term_id, self::META_FG, true ) ) ?: '#222222',
			);
		}
		return apply_filters( 'helanak_badge_colors', $colors );
	}

	/**
	 * Ordered active badges. The public item shape remains unchanged.
	 *
	 * @return array<int,array{key:string,label:string,bg:string,fg:string}>
	 */
	public static function get_badges( \WC_Product $product ): array {
		$settings = self::automatic_settings();
		$colors   = self::default_colors();
		$is_fa    = 'fa' === substr( get_locale(), 0, 2 );
		$badges   = array();

		$add = static function ( string $key, string $label, int $priority ) use ( &$badges, $colors ) {
			$badges[] = array(
				'key'      => $key,
				'label'    => $label,
				'bg'       => $colors[ $key ][0] ?? '#EEEEEE',
				'fg'       => $colors[ $key ][1] ?? '#222222',
				'priority' => $priority,
			);
		};

		if ( 'yes' === $settings['sale']['enabled'] && $product->is_on_sale() ) {
			$add( 'sale', $is_fa ? $settings['sale']['label_fa'] : $settings['sale']['label_en'], (int) $settings['sale']['priority'] );
		}

		$manual_terms = wp_get_object_terms( $product->get_id(), self::TAXONOMY );
		if ( ! is_wp_error( $manual_terms ) ) {
			foreach ( $manual_terms as $term ) {
				if ( 'no' === get_term_meta( $term->term_id, self::META_ENABLED, true ) ) {
					continue;
				}
				$add( $term->slug, self::term_label( $term ), (int) get_term_meta( $term->term_id, self::META_PRIORITY, true ) );
			}
		}

		$published = get_post_field( 'post_date_gmt', $product->get_id() );
		if ( 'yes' === $settings['new']['enabled'] && $published ) {
			$age_days = ( time() - strtotime( $published . ' GMT' ) ) / DAY_IN_SECONDS;
			if ( $age_days <= self::new_window_days() ) {
				$add( 'new', $is_fa ? $settings['new']['label_fa'] : $settings['new']['label_en'], (int) $settings['new']['priority'] );
			}
		}

		if ( 'yes' === $settings['age_specific']['enabled'] && class_exists( 'Helanak_Core_Age_Taxonomy' ) ) {
			$terms = get_the_terms( $product->get_id(), Helanak_Core_Age_Taxonomy::TAXONOMY );
			if ( is_array( $terms ) && 1 === count( $terms ) ) {
				$label = Helanak_Core_Age_Taxonomy::localized_label( $terms[0], $is_fa );
				$add( 'age_specific', $label, (int) $settings['age_specific']['priority'] );
			}
		}

		usort( $badges, static fn( $a, $b ) => $b['priority'] <=> $a['priority'] );
		foreach ( $badges as &$badge ) {
			unset( $badge['priority'] );
		}
		unset( $badge );
		return $badges;
	}
}
