<?php
/**
 * Site footer (replaces the Hello Elementor footer on every page)
 * Loaded by houssaini-core.php
 *
 * EDIT YOUR DETAILS in hc_footer_settings() below: phone, email, address,
 * hours, WhatsApp number, social links and payment methods.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hc_footer_settings() {
	return apply_filters( 'hc_footer_settings', array(
		'about'    => __( 'Your trusted electronics store in Tunisia. Quality products, great prices and fast delivery.', 'houssaini-core' ),
		'address'  => 'Your address, Tunisia',
		'phone'    => '+216 00 000 000',
		'email'    => 'contact@yourdomain.tn',
		'hours'    => 'Mon - Sat: 9:00 - 19:00',
		'whatsapp' => '',    // digits only with country code, e.g. 21620123456. Leave empty to hide the WhatsApp button.
		'socials'  => array( // full URLs. Leave empty to hide an icon.
			'facebook'  => '',
			'instagram' => '',
			'tiktok'    => '',
		),
		'payments' => array( 'Cash on Delivery', 'Bank Card' ), // only list what you really accept
	) );
}

function hc_footer_icon( $name ) {
	$o = '<svg viewBox="0 0 24 24" aria-hidden="true">';
	$i = array(
		'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v8h4v-8h3l1-4h-4V8.500c0-.3.2-.5.5-.5z" fill="currentColor"/>',
		'instagram' => '<rect x="3.500" y="3.500" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.200" cy="6.800" r="1.200" fill="currentColor"/>',
		'tiktok'    => '<path d="M14 3v10.500a3.500 3.500 0 1 1-3.500-3.500" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round"/><path d="M14 3c.3 2.600 2 4.300 5 4.500" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round"/>',
		'whatsapp'  => '<path d="M12 3a9 9 0 0 0-7.800 13.500L3 21l4.600-1.200A9 9 0 1 0 12 3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 8.500c0 3.500 3 6.500 6.500 6.500l1-1.500-2-1-1 .8c-1-.4-2-1.400-2.400-2.400l.8-1-1-2z" fill="currentColor"/>',
		'pin'       => '<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.500" fill="currentColor"/>',
		'phone'     => '<path d="M5 4h4l2 5-2.500 1.500a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 7l9 6 9-6" fill="none" stroke="currentColor" stroke-width="2"/>',
		'clock'     => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
		'up'        => '<path d="M6 15l6-6 6 6" fill="none" stroke="currentColor" stroke-width="2.600" stroke-linecap="round" stroke-linejoin="round"/>',
	);
	return isset( $i[ $name ] ) ? $o . $i[ $name ] . '</svg>' : '';
}

/** Returns the permalink of the first existing page among the given slugs, or ''. */
function hc_find_page( $slugs ) {
	foreach ( (array) $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			return get_permalink( $page );
		}
	}
	return '';
}

/** Hide Hello Elementor's own footer */
add_action( 'wp_head', function () {
	echo '<style id="hc-hide-theme-footer">#site-footer,.site-footer{display:none !important}</style>';
}, 5 );

add_action( 'wp_footer', function () {
	$s    = hc_footer_settings();
	$woo  = function_exists( 'wc_get_page_permalink' );
	$shop = $woo ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	/* shop column: all products + your real top-level categories */
	$shop_links = array( array( __( 'All Products', 'houssaini-core' ), $shop ) );
	if ( taxonomy_exists( 'product_cat' ) ) {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
		if ( ! is_wp_error( $terms ) && $terms ) {
			usort( $terms, function ( $a, $b ) {
				$oa = (int) get_term_meta( $a->term_id, 'order', true );
				$ob = (int) get_term_meta( $b->term_id, 'order', true );
				return $oa === $ob ? $a->term_id - $b->term_id : $oa - $ob;
			} );
			foreach ( array_slice( $terms, 0, 6 ) as $t ) {
				$shop_links[] = array( $t->name, get_term_link( $t ) );
			}
		}
	}

	/* customer care column: shop pages + your own pages (only if they exist) */
	$care = array();
	if ( $woo ) {
		$care[] = array( __( 'My Account', 'houssaini-core' ), wc_get_page_permalink( 'myaccount' ) );
		$care[] = array( __( 'My Orders', 'houssaini-core' ), wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) );
		$care[] = array( __( 'Cart', 'houssaini-core' ), wc_get_cart_url() );
	}
	$pages = array(
		array( __( 'About Us', 'houssaini-core' ),            array( 'about', 'about-us' ) ),
		array( __( 'Contact', 'houssaini-core' ),             array( 'contact', 'contact-us' ) ),
		array( __( 'FAQ', 'houssaini-core' ),                 array( 'faq', 'faqs', 'help' ) ),
		array( __( 'Shipping & Delivery', 'houssaini-core' ), array( 'shipping', 'shipping-policy', 'delivery' ) ),
		array( __( 'Returns & Refunds', 'houssaini-core' ),   array( 'returns', 'return-policy', 'refund-policy', 'refunds' ) ),
		array( __( 'Track Order', 'houssaini-core' ),         array( 'track-order', 'order-tracking' ) ),
	);
	foreach ( $pages as $p ) {
		$url = hc_find_page( $p[1] );
		if ( $url ) {
			$care[] = array( $p[0], $url );
		}
	}

	/* legal links */
	$legal   = array();
	$terms_u = ( $woo && function_exists( 'wc_terms_and_conditions_page_id' ) && wc_terms_and_conditions_page_id() ) ? get_permalink( wc_terms_and_conditions_page_id() ) : hc_find_page( array( 'terms', 'terms-and-conditions', 'terms-conditions' ) );
	if ( $terms_u ) {
		$legal[] = array( __( 'Terms & Conditions', 'houssaini-core' ), $terms_u );
	}
	$priv = get_privacy_policy_url();
	if ( $priv ) {
		$legal[] = array( __( 'Privacy Policy', 'houssaini-core' ), $priv );
	}

	$phone_digits = preg_replace( '/[^0-9+]/', '', $s['phone'] );
	$socials      = array_filter( $s['socials'] );
	?>
	<style id="hc-footer-css">
	.hc-footer{ position:relative; background:#0b0b0d; color:#a9a9b2; font-family:var(--hc-body,'Inter',sans-serif); overflow:hidden; isolation:isolate; margin-top:clamp(30px,4vw,60px); }
	.hc-footer::before{ content:""; position:absolute; inset-inline:0; top:0; height:3px; background:linear-gradient(90deg,#e4161c,rgba(228,22,28,0) 70%); }
	html[dir="rtl"] .hc-footer::before{ background:linear-gradient(270deg,#e4161c,rgba(228,22,28,0) 70%); }
	.hc-footer::after{ content:""; position:absolute; inset-inline-end:-10%; top:-30%; width:600px; height:600px; background:radial-gradient(circle,rgba(228,22,28,.16),rgba(228,22,28,0) 65%); z-index:-1; pointer-events:none; }
	.hc-footer a{ text-decoration:none; }
	.hc-f-in{ max-width:1680px; margin:0 auto; padding:0 clamp(16px,2.4vw,42px); }
	.hc-f-main{ display:grid; grid-template-columns:1.5fr 1fr 1fr 1.3fr; gap:clamp(28px,4vw,72px); padding:clamp(40px,5vw,72px) 0 clamp(30px,4vw,52px); }
	.hc-f-logo{ display:inline-block; font:900 30px/1 'Saira',sans-serif; letter-spacing:.5px; color:#fff !important; margin-bottom:16px; }
	.hc-f-logo span{ color:#e4161c; }
	.hc-f-about{ margin:0 0 22px; font-size:14.5px; line-height:1.7; max-width:34ch; }
	.hc-f-social{ display:flex; gap:10px; flex-wrap:wrap; }
	.hc-f-social a{ width:40px; height:40px; border-radius:50%; background:#1a1a1e; color:#fff !important; display:grid; place-items:center; font-size:19px; transition:.25s; }
	.hc-f-social a:hover{ background:#e4161c; transform:translateY(-3px); }
	.hc-footer svg{ width:1em; height:1em; display:block; }
	.hc-f-col h4{ margin:0 0 20px; color:#fff; font:700 16px var(--hc-body,'Inter',sans-serif); position:relative; padding-bottom:12px; }
	.hc-f-col h4::after{ content:""; position:absolute; inset-inline-start:0; bottom:0; width:28px; height:3px; border-radius:2px; background:#e4161c; }
	.hc-f-col ul{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:12px; }
	.hc-f-col li a{ color:#a9a9b2 !important; font-size:14.5px; display:inline-block; transition:.2s; }
	.hc-f-col li a:hover{ color:#fff !important; transform:translateX(5px); }
	html[dir="rtl"] .hc-f-col li a:hover{ transform:translateX(-5px); }
	.hc-f-contact li{ display:flex; gap:12px; align-items:flex-start; font-size:14.5px; line-height:1.5; }
	.hc-f-contact li > svg{ flex:0 0 20px; width:20px; height:20px; color:#e4161c; margin-top:2px; }
	.hc-f-contact a{ color:#a9a9b2 !important; }
	.hc-f-contact a:hover{ color:#fff !important; }
	.hc-f-bar{ border-top:1px solid #1f1f24; }
	.hc-f-bar .hc-f-in{ display:flex; align-items:center; justify-content:space-between; gap:18px 30px; flex-wrap:wrap; padding-top:20px; padding-bottom:20px; font-size:13.5px; }
	.hc-f-pay{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
	.hc-f-pay span{ padding:6px 12px; border:1px solid #2a2a30; border-radius:8px; color:#d0d0d6; font-size:12.5px; font-weight:500; }
	.hc-f-legal{ display:flex; gap:20px; flex-wrap:wrap; }
	.hc-f-legal a{ color:#a9a9b2 !important; }
	.hc-f-legal a:hover{ color:#fff !important; }

	.hc-float{ position:fixed; bottom:22px; z-index:900; width:48px; height:48px; border-radius:50%; display:grid; place-items:center; font-size:24px; color:#fff !important; border:0; cursor:pointer; box-shadow:0 10px 26px rgba(0,0,0,.3); transition:.3s; }
	.hc-top{ inset-inline-end:22px; background:#e4161c; opacity:0; visibility:hidden; transform:translateY(12px); }
	.hc-top.show{ opacity:1; visibility:visible; transform:none; }
	.hc-top:hover{ background:#111114; }
	.hc-wa{ inset-inline-start:22px; background:#25d366; }
	.hc-wa:hover{ transform:scale(1.08); }

	@media (max-width:1000px){ .hc-f-main{ grid-template-columns:1fr 1fr; } }
	@media (max-width:600px){
		.hc-f-main{ grid-template-columns:1fr 1fr; gap:30px 18px; }
		.hc-f-brand, .hc-f-contact-col{ grid-column:1 / -1; }
		.hc-f-bar .hc-f-in{ flex-direction:column; align-items:flex-start; }
		.hc-float{ width:44px; height:44px; bottom:16px; }
	}
	</style>

	<footer class="hc-footer">
		<div class="hc-f-in">
			<div class="hc-f-main">
				<div class="hc-f-brand">
					<a class="hc-f-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">HOUSSAINI<span>.</span></a>
					<p class="hc-f-about"><?php echo esc_html( $s['about'] ); ?></p>
					<?php if ( $socials ) : ?>
						<div class="hc-f-social">
							<?php foreach ( $socials as $name => $url ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $name ) ); ?>"><?php echo hc_footer_icon( $name ); // phpcs:ignore ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="hc-f-col">
					<h4><?php esc_html_e( 'Shop', 'houssaini-core' ); ?></h4>
					<ul>
						<?php foreach ( $shop_links as $l ) : ?>
							<li><a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="hc-f-col">
					<h4><?php esc_html_e( 'Customer Care', 'houssaini-core' ); ?></h4>
					<ul>
						<?php foreach ( $care as $l ) : ?>
							<li><a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="hc-f-col hc-f-contact-col">
					<h4><?php esc_html_e( 'Contact Us', 'houssaini-core' ); ?></h4>
					<ul class="hc-f-contact">
						<?php if ( $s['address'] ) : ?><li><?php echo hc_footer_icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( $s['address'] ); ?></span></li><?php endif; ?>
						<?php if ( $s['phone'] ) : ?><li><?php echo hc_footer_icon( 'phone' ); // phpcs:ignore ?><a href="tel:<?php echo esc_attr( $phone_digits ); ?>" dir="ltr"><?php echo esc_html( $s['phone'] ); ?></a></li><?php endif; ?>
						<?php if ( $s['email'] ) : ?><li><?php echo hc_footer_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( $s['email'] ); ?>"><?php echo esc_html( $s['email'] ); ?></a></li><?php endif; ?>
						<?php if ( $s['hours'] ) : ?><li><?php echo hc_footer_icon( 'clock' ); // phpcs:ignore ?><span><?php echo esc_html( $s['hours'] ); ?></span></li><?php endif; ?>
					</ul>
				</div>
			</div>
		</div>

		<div class="hc-f-bar">
			<div class="hc-f-in">
				<span>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> HOUSSAINI. <?php esc_html_e( 'All rights reserved.', 'houssaini-core' ); ?></span>
				<?php if ( $s['payments'] ) : ?>
					<div class="hc-f-pay">
						<?php foreach ( $s['payments'] as $pay ) : ?><span><?php echo esc_html( $pay ); ?></span><?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( $legal ) : ?>
					<div class="hc-f-legal">
						<?php foreach ( $legal as $l ) : ?><a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a><?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</footer>

	<?php if ( $s['whatsapp'] ) : ?>
		<a class="hc-float hc-wa" href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $s['whatsapp'] ) ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?php echo hc_footer_icon( 'whatsapp' ); // phpcs:ignore ?></a>
	<?php endif; ?>
	<button type="button" class="hc-float hc-top" aria-label="<?php esc_attr_e( 'Back to top', 'houssaini-core' ); ?>"><?php echo hc_footer_icon( 'up' ); // phpcs:ignore ?></button>
	<script>
	(function(){
		var b=document.querySelector('.hc-top'); if(!b) return;
		function t(){ b.classList.toggle('show',window.scrollY>700); }
		window.addEventListener('scroll',t,{passive:true}); t();
		b.addEventListener('click',function(){ window.scrollTo({top:0,behavior:'smooth'}); });
	})();
	</script>
	<?php
}, 5 );