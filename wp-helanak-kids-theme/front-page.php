<?php
/**
 * Homepage. Assembles the gated section template-parts in display order.
 * `announcement` and `mini_cart` are handled in header.php / footer.php
 * respectively since they're chrome, not homepage-specific sections.
 */

get_header();
?>

<main id="helanak-main" class="helanak-main">
	<?php
	get_template_part( 'template-parts/section', 'hero' );
	get_template_part( 'template-parts/section', 'category-slider' );
	get_template_part( 'template-parts/section', 'product-rail' );
	get_template_part( 'template-parts/section', 'age-selector' );
	get_template_part( 'template-parts/section', 'flash-sale' );
	get_template_part( 'template-parts/section', 'trust-badges' );
	get_template_part( 'template-parts/section', 'friends-parade' );
	get_template_part( 'template-parts/section', 'newsletter' );
	?>
</main>

<?php
get_footer();
