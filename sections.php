<?php
/**
 * Trust bar + wide promo banner
 *   [houssaini_trust]   the 4 benefits bar that overlaps the bottom of the hero
 *   [houssaini_banner]  the wide "Upgrade Your World" banner (goes under the categories)
 * Loaded by houssaini-core.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Trust bar
 * ---------------------------------------------------------------- */

function hc_trust_icon( $name ) {
	$open  = '<svg viewBox="0 0 48 48" fill="none" stroke="#111114" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
	$icons = array(
		'truck'   => '<path d="M4 12h24v21H4z"/><path d="M28 19h9l7 7v7H28"/><circle cx="14" cy="35" r="4" fill="#fff"/><circle cx="36" cy="35" r="4" fill="#fff"/><path d="M9 18h11M9 23h7" stroke="#e4161c"/>',
		'shield'  => '<path d="M24 5l15 6v11c0 10-6.500 17-15 21C17.500 38 9 32 9 22V11z" stroke="#e4161c"/><path d="M17 24l5 5 9-10"/>',
		'support' => '<path d="M9 26v-4a15 15 0 0 1 30 0v4"/><rect x="6" y="26" width="8" height="12" rx="3" fill="#fff"/><rect x="34" y="26" width="8" height="12" rx="3" fill="#fff"/><path d="M38 38c0 4-5 6-12 6" stroke="#e4161c"/>',
		'return'  => '<path d="M40 22a16 16 0 0 0-28-8l-4 4"/><path d="M8 8v10h10"/><path d="M8 26a16 16 0 0 0 28 8l4-4" stroke="#e4161c"/><path d="M40 40V30H30" stroke="#e4161c"/>',
	);
	return isset( $icons[ $name ] ) ? $open . $icons[ $name ] . '</svg>' : '';
}

add_shortcode( 'houssaini_trust', function () {
	$items = apply_filters( 'hc_trust_items', array(
		array( 'icon' => 'truck',   'title' => __( 'Free Shipping', 'houssaini-core' ),   'sub' => __( 'Across Tunisia', 'houssaini-core' ) ),
		array( 'icon' => 'shield',  'title' => __( 'Secure Payment', 'houssaini-core' ),  'sub' => __( '100% Secure', 'houssaini-core' ) ),
		array( 'icon' => 'support', 'title' => __( '24/7 Support', 'houssaini-core' ),    'sub' => __( "We're here to help", 'houssaini-core' ) ),
		array( 'icon' => 'return',  'title' => __( 'Easy Returns', 'houssaini-core' ),    'sub' => __( 'Up to 14 days', 'houssaini-core' ) ),
	) );
	ob_start();
	?>
	<style id="hc-trust-css">
	.hc-trust{ position:relative; z-index:6; padding:0 clamp(16px,2.4vw,42px); font-family:var(--hc-body,'Inter',sans-serif); }
	.hc-trust-in{ max-width:1680px; margin:0 auto; background:#fff; border:1px solid #ececf0; border-radius:18px; box-shadow:0 18px 50px rgba(0,0,0,.12); display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); }
	.hc-trust-item{ position:relative; display:flex; align-items:center; justify-content:center; gap:16px; padding:22px clamp(14px,2vw,34px); }
	.hc-trust-item + .hc-trust-item::before{ content:""; position:absolute; inset-inline-start:0; top:50%; height:50px; transform:translateY(-50%); width:1px; background:#d9d9df; }
	.hc-trust-ico{ flex:0 0 auto; width:46px; height:46px; display:block; }
	.hc-trust-ico svg{ width:100%; height:100%; display:block; }
	.hc-trust-txt strong{ display:block; font:700 15px/1.25 var(--hc-body,'Inter',sans-serif); color:#111114; }
	.hc-trust-txt span{ display:block; margin-top:3px; font:400 13px/1.3 var(--hc-body,'Inter',sans-serif); color:#6f6f78; }
	@media (max-width:1000px){
		.hc-trust-in{ grid-template-columns:repeat(2,minmax(0,1fr)); }
		.hc-trust-item{ justify-content:flex-start; }
		.hc-trust-item::before{ display:none; }
		.hc-trust-item:nth-child(n+3){ border-top:1px solid #ececf0; }
	}
	@media (max-width:560px){
		.hc-trust-item{ gap:10px; padding:14px 12px; }
		.hc-trust-ico{ width:34px; height:34px; }
		.hc-trust-txt strong{ font-size:13px; }
		.hc-trust-txt span{ font-size:11.5px; }
	}
	</style>
	<section class="hc-trust">
		<div class="hc-trust-in">
			<?php foreach ( $items as $item ) : ?>
				<div class="hc-trust-item">
					<span class="hc-trust-ico"><?php echo hc_trust_icon( $item['icon'] ); // phpcs:ignore ?></span>
					<span class="hc-trust-txt">
						<strong><?php echo esc_html( $item['title'] ); ?></strong>
						<span><?php echo esc_html( $item['sub'] ); ?></span>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<script>
	/* overlap the bottom of the hero by a fixed amount, whatever spacing Elementor adds between the widgets */
	(function(){
		var t=document.querySelector('.hc-trust'), h=document.querySelector('.hc-hero');
		if(!t||!h) return;
		var OVER=20, timer; /* how many px the bar overlaps the hero: lower = bar sits lower, 0 = no overlap */
		document.documentElement.style.setProperty('--hc-over',OVER+'px'); /* the hero lifts its arrows by the same amount */
		function fit(){
			t.style.marginTop='';
			var gap=t.getBoundingClientRect().top-h.getBoundingClientRect().bottom;
			t.style.marginTop=(-(gap+OVER))+'px';
		}
		function later(){ clearTimeout(timer); timer=setTimeout(fit,60); }
		fit();
		window.addEventListener('load',later);
		window.addEventListener('resize',later);
		if(document.fonts&&document.fonts.ready){ document.fonts.ready.then(later); }
	})();
	</script>
	<?php
	return ob_get_clean();
} );

/* ------------------------------------------------------------------
 * Wide promo banner
 * ---------------------------------------------------------------- */

add_shortcode( 'houssaini_banner', function () {
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$b    = apply_filters( 'hc_wide_banner', array(
		'eyebrow'  => __( 'Limited Time Offer', 'houssaini-core' ),
		'line1'    => __( 'Upgrade', 'houssaini-core' ),
		'line2'    => __( 'Your World.', 'houssaini-core' ),
		'text'     => __( 'Get the best deals on top electronics.', 'houssaini-core' ),
		'button'   => __( 'Shop the Deals', 'houssaini-core' ),
		'link'     => $shop,
		'off_pre'  => __( 'Up to', 'houssaini-core' ),
		'off_val'  => '-50%',   // leave empty ('') to hide the discount block
		'off_post' => __( 'Off', 'houssaini-core' ),
	) );
	$bg  = hc_img( 'wide_bg' );
	$gpu = hc_img( 'gpu' );
	ob_start();
	?>
	<style id="hc-wb-css">
	.hc-wb-sec{ padding:clamp(26px,3vw,44px) clamp(16px,2.4vw,42px) 0; font-family:var(--hc-body,'Inter',sans-serif); }
	.hc-wb-in{ max-width:1680px; margin:0 auto; }
	.hc-wb{ position:relative; display:flex; align-items:center; justify-content:space-between; min-height:clamp(240px,16.5vw,300px); border-radius:20px; overflow:hidden; background-color:#0b0b0d; background-size:cover; background-position:center; color:#fff !important; text-decoration:none !important; isolation:isolate; }
	.hc-wb::before{ content:""; position:absolute; inset:0; z-index:-1; background:linear-gradient(90deg, rgba(0,0,0,.55), rgba(0,0,0,0) 62%); }
	html[dir="rtl"] .hc-wb::before{ background:linear-gradient(270deg, rgba(0,0,0,.55), rgba(0,0,0,0) 62%); }
	.hc-wb-copy{ position:relative; z-index:3; display:flex; flex-direction:column; align-items:flex-start; padding:clamp(24px,3vw,56px); max-width:60%; }
	.hc-wb-eyebrow{ display:flex; align-items:center; gap:14px; color:#e4161c; font:600 13px var(--hc-body); letter-spacing:.06em; text-transform:uppercase; margin-bottom:12px; }
	.hc-wb-eyebrow::before{ content:""; width:50px; height:3px; background:#e4161c; }
	.hc-wb-title{ font:900 clamp(28px,3.5vw,60px)/1.02 var(--hc-head); text-transform:uppercase; margin-bottom:12px; }
	.hc-wb-title em{ color:#e4161c; font-style:normal; }
	.hc-wb-text{ font:500 clamp(15px,1.4vw,22px)/1.4 var(--hc-body); margin-bottom:24px; color:rgba(255,255,255,.92); }
	.hc-wb .hc-btn{ padding:14px 26px; font-size:15px; }
	.hc-wb:hover .hc-btn{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(228,22,28,.4); }
	.hc-wb-gpu{ position:absolute; inset-inline-end:17%; bottom:-20%; width:min(46%,720px); z-index:2; pointer-events:none; filter:drop-shadow(0 26px 34px rgba(0,0,0,.65)); animation:hcWbFloat 7s ease-in-out infinite; transition:transform .6s cubic-bezier(.2,.7,.2,1); }
	.hc-wb:hover .hc-wb-gpu{ transform:scale(1.05) rotate(-2deg); }
	@keyframes hcWbFloat{ 0%,100%{ translate:0 0; } 50%{ translate:0 -12px; } }
	.hc-wb-off{ position:relative; z-index:3; text-align:center; padding-inline-end:clamp(24px,4.5vw,80px); padding-inline-start:12px; }
	.hc-wb-off small{ display:block; font:500 clamp(14px,1.4vw,22px) var(--hc-body); letter-spacing:.05em; text-transform:uppercase; color:rgba(255,255,255,.9); }
	.hc-wb-off b{ display:block; font:900 clamp(40px,5.2vw,88px)/1 var(--hc-head); color:#e4161c; }
	@media (max-width:900px){
		.hc-wb{ flex-direction:column; align-items:flex-start; justify-content:center; min-height:300px; }
		.hc-wb-copy{ max-width:100%; }
		.hc-wb-gpu{ inset-inline-end:-10%; bottom:-10%; width:72%; opacity:.5; }
		.hc-wb-off{ position:absolute; top:16px; inset-inline-end:16px; padding:0; text-align:end; }
		.hc-wb-off b{ font-size:36px; }
	}
	@media (prefers-reduced-motion:reduce){ .hc-wb-gpu{ animation:none; } }
	</style>
	<section class="hc-wb-sec">
		<div class="hc-wb-in">
			<a class="hc-wb" href="<?php echo esc_url( $b['link'] ); ?>"<?php echo $bg ? ' style="background-image:url(' . esc_url( $bg ) . ')"' : ''; ?>>
				<span class="hc-wb-copy">
					<span class="hc-wb-eyebrow"><?php echo esc_html( $b['eyebrow'] ); ?></span>
					<span class="hc-wb-title"><?php echo esc_html( $b['line1'] ); ?> <em><?php echo esc_html( $b['line2'] ); ?></em></span>
					<span class="hc-wb-text"><?php echo esc_html( $b['text'] ); ?></span>
					<span class="hc-btn"><?php echo esc_html( $b['button'] ); ?><?php echo hc_icon( 'arrow' ); ?></span>
				</span>
				<?php if ( $gpu ) : ?><img class="hc-wb-gpu" src="<?php echo esc_url( $gpu ); ?>" alt="" loading="lazy"><?php endif; ?>
				<?php if ( ! empty( $b['off_val'] ) ) : ?>
					<span class="hc-wb-off">
						<small><?php echo esc_html( $b['off_pre'] ); ?></small>
						<b><?php echo esc_html( $b['off_val'] ); ?></b>
						<small><?php echo esc_html( $b['off_post'] ); ?></small>
					</span>
				<?php endif; ?>
			</a>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );