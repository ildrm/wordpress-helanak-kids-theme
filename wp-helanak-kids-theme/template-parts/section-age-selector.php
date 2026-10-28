<?php
/**
 * Dynamic "Shop by age" selector backed by age_range term metadata.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helanak_ages = get_terms(
	array(
		'taxonomy'   => 'age_range',
		'hide_empty' => false,
	)
);
if ( is_wp_error( $helanak_ages ) ) {
	$helanak_ages = array();
}
if ( class_exists( 'Helanak_Core_Age_Taxonomy' ) ) {
	usort(
		$helanak_ages,
		static function ( $left, $right ) {
			$left_data  = Helanak_Core_Age_Taxonomy::get_display_data( $left, helanak_current_lang() );
			$right_data = Helanak_Core_Age_Taxonomy::get_display_data( $right, helanak_current_lang() );
			return ( $left_data['order'] <=> $right_data['order'] ) ?: strcasecmp( $left_data['label'], $right_data['label'] );
		}
	);
}
?>
<section class="helanak-section" data-helanak-section="age_selector"<?php helanak_section_hidden_attr( 'age_selector' ); ?>>
	<div class="helanak-container">
		<div class="helanak-section__head">
			<h2><?php esc_html_e( 'Shop by age', 'wp-helanak-kids-theme' ); ?></h2>
			<span class="helanak-section__sub"><?php esc_html_e( 'Sizes that grow with them', 'wp-helanak-kids-theme' ); ?></span>
		</div>
		<?php if ( $helanak_ages ) : ?>
			<div class="helanak-ages">
				<?php
				foreach ( $helanak_ages as $helanak_age_term ) :
					$helanak_age_data = class_exists( 'Helanak_Core_Age_Taxonomy' )
						? Helanak_Core_Age_Taxonomy::get_display_data( $helanak_age_term, helanak_current_lang() )
						: array(
							'label' => $helanak_age_term->name, 'subtitle' => '', 'icon' => '',
							'color_start' => '#F3E8CF', 'color_end' => '#FAF1DE',
						);
					$helanak_age_style = sprintf(
						'--age-bg:linear-gradient(160deg,%1$s,%2$s)',
						$helanak_age_data['color_start'] ?: '#F3E8CF',
						$helanak_age_data['color_end'] ?: '#FAF1DE'
					);
					?>
					<a class="helanak-age" href="<?php echo esc_url( get_term_link( $helanak_age_term ) ); ?>" style="<?php echo esc_attr( $helanak_age_style ); ?>">
						<span class="helanak-age__bubble">
							<?php if ( $helanak_age_data['icon'] ) : ?>
								<span class="helanak-age__icon" aria-hidden="true"><?php echo esc_html( $helanak_age_data['icon'] ); ?></span>
							<?php endif; ?>
							<?php echo esc_html( $helanak_age_data['label'] ); ?>
						</span>
						<?php if ( $helanak_age_data['subtitle'] ) : ?>
							<span class="helanak-age__label"><?php echo esc_html( $helanak_age_data['subtitle'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Add age ranges in Products → Age Ranges to enable age browsing.', 'wp-helanak-kids-theme' ); ?></p>
		<?php endif; ?>
	</div>
</section>
