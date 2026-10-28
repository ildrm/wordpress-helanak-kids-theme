<?php
/**
 * Homepage hero: seasonal illustration (sun/twinkles/clouds/balloons/hills),
 * ambient falling particles, and a decorative butterfly — ported verbatim
 * from the frontend/ prototype's hero <section> (frontend/front-page.dc.html,
 * around "HERO — layered seasonal scene") as inline styles, the same
 * approach Helanak_Character_Engine uses for the mascots, rather than
 * reinterpreted CSS classes, so pixel geometry and colors match exactly.
 * --sky/--sun/--hill1/2/3 are the prototype's literal per-season tokens,
 * printed by Helanak_Season_Engine alongside --primary-color etc.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helanak_season           = Helanak_Season_Engine::get_active_season();
$helanak_hero_mascot_args = array(
	'spring' => array(
		'key'   => 'rabbit',
		'scale' => 1,
		'extra' => array( 'hop' => '5.5s' ),
	),
	'summer' => array(
		'key'   => 'bear',
		'scale' => 1,
		'extra' => array( 'wave' => true ),
	),
	'autumn' => array(
		'key'   => 'fox',
		'scale' => 1.05,
		'extra' => array(),
	),
	'winter' => array(
		'key'   => 'penguin',
		'scale' => 1.05,
		'extra' => array(),
	),
);
$helanak_hero_mascot    = $helanak_hero_mascot_args[ $helanak_season ] ?? $helanak_hero_mascot_args['spring'];
$helanak_shop_permalink = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
$helanak_show_butterfly = in_array( $helanak_season, array( 'spring', 'summer' ), true );
?>
<section class="helanak-hero" data-helanak-section="hero"<?php helanak_section_hidden_attr( 'hero' ); ?>>
	<div class="helanak-hero__scene" aria-hidden="true">
		<span class="helanak-hero__sun"></span>

		<span class="helanak-hero__twinkle" style="top:52px;inset-inline-start:14%;width:7px;height:7px;animation-duration:3.4s;animation-delay:0s"></span>
		<span class="helanak-hero__twinkle" style="top:120px;inset-inline-start:44%;width:5px;height:5px;animation-duration:4.2s;animation-delay:1s"></span>
		<span class="helanak-hero__twinkle" style="top:34px;inset-inline-start:62%;width:6px;height:6px;animation-duration:5s;animation-delay:.4s"></span>

		<span class="helanak-hero__cloud" style="top:64px;inset-inline-start:6%;width:120px;height:38px;animation:scdriftx 26s ease-in-out infinite alternate">
			<span class="helanak-hero__cloud-puff" style="width:70px;height:26px;inset-inline-start:0;bottom:0"></span>
			<span class="helanak-hero__cloud-puff" style="width:52px;height:34px;inset-inline-start:26px;bottom:6px;border-radius:50%"></span>
			<span class="helanak-hero__cloud-puff" style="width:64px;height:24px;inset-inline-start:52px;bottom:0"></span>
		</span>
		<span class="helanak-hero__cloud" style="top:150px;inset-inline-end:24%;width:90px;height:30px;opacity:.8;animation:scdriftx 34s ease-in-out 2s infinite alternate-reverse">
			<span class="helanak-hero__cloud-puff" style="width:56px;height:20px;inset-inline-start:0;bottom:0;background:rgba(255,255,255,.9)"></span>
			<span class="helanak-hero__cloud-puff" style="width:40px;height:26px;inset-inline-start:20px;bottom:5px;border-radius:50%;background:rgba(255,255,255,.9)"></span>
			<span class="helanak-hero__cloud-puff" style="width:46px;height:18px;inset-inline-start:42px;bottom:0;background:rgba(255,255,255,.9)"></span>
		</span>

		<span class="helanak-hero__balloon" style="top:110px;inset-inline-end:34%;animation:scsway 11s ease-in-out infinite">
			<span class="helanak-hero__balloon-body" style="background:var(--secondary-color);width:44px;height:54px"></span>
			<span class="helanak-hero__balloon-string" style="height:56px"></span>
		</span>
		<span class="helanak-hero__balloon" style="top:160px;inset-inline-end:8%;animation:scsway 13s ease-in-out 1.6s infinite">
			<span class="helanak-hero__balloon-body" style="background:var(--accent-color);width:34px;height:42px"></span>
			<span class="helanak-hero__balloon-string" style="height:44px"></span>
		</span>

		<span class="helanak-hero__hill" style="bottom:-150px;inset-inline-start:-12%;width:64%;height:280px;background:var(--hill3)"></span>
		<span class="helanak-hero__hill" style="bottom:-160px;inset-inline-end:-16%;width:78%;height:300px;background:var(--hill2)"></span>
		<span class="helanak-hero__hill" style="bottom:-210px;inset-inline-start:-20%;width:145%;height:320px;background:var(--hill1)"></span>

		<?php echo Helanak_Season_Engine::render_ambient_particles(); // phpcs:ignore WordPress.Security.EscapeOutput -- pre-escaped inside render_ambient_particles(). ?>
		<?php if ( $helanak_show_butterfly ) : ?>
			<div class="helanak-hero__butterfly">
				<?php echo Helanak_Character_Engine::butterfly( 1 ); // phpcs:ignore WordPress.Security.EscapeOutput -- pre-escaped inside butterfly(). ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="helanak-container helanak-hero__grid">
		<div class="helanak-hero__copy">
			<span class="helanak-hero__kicker"><?php esc_html_e( 'A little joy for every day', 'wp-helanak-kids-theme' ); ?></span>
			<h1><?php esc_html_e( 'Clothes and toys your kids will actually love', 'wp-helanak-kids-theme' ); ?></h1>
			<p><?php esc_html_e( 'Explore clothes and toys by age, category, and the details that matter to your family.', 'wp-helanak-kids-theme' ); ?></p>
			<div class="helanak-hero__actions">
				<a class="helanak-btn helanak-btn--dark" href="<?php echo esc_url( $helanak_shop_permalink ); ?>">
					<?php esc_html_e( 'Shop now', 'wp-helanak-kids-theme' ); ?>
				</a>
				<a class="helanak-btn helanak-btn--outline" href="#helanak-category-slider">
					<?php esc_html_e( 'Explore worlds', 'wp-helanak-kids-theme' ); ?>
				</a>
			</div>
		</div>
		<div class="helanak-hero__stage">
			<?php
			helanak_character(
				$helanak_hero_mascot['key'],
				array_merge(
					array(
						'scale'    => $helanak_hero_mascot['scale'],
						'behavior' => 'idle',
					),
					$helanak_hero_mascot['extra']
				)
			);
			?>

			<?php if ( get_theme_mod( 'helanak_character_surprises', true ) ) : ?>
				<div class="helanak-hero__visitor" data-helanak-surprise aria-hidden="true">
					<?php foreach ( array( 'turtle', 'elephant' ) as $helanak_visitor ) : ?><?php if ( Helanak_Character_Engine::is_enabled( $helanak_visitor ) ) : ?><span data-helanak-surprise-choice hidden><?php helanak_character( $helanak_visitor, array( 'scale' => .45 ) ); ?></span><?php endif; ?><?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
