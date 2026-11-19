<?php
/**
 * Slide-in cart (side cart)
 * Loaded by houssaini-core.php
 *
 * - clicking the cart icon opens a drawer instead of going to the cart page
 * - it opens by itself when you add a product
 * - change quantities or remove items without leaving the page
 * - free shipping progress bar, subtotal, View Cart + Checkout buttons
 *
 * Free shipping amount: see hc_free_shipping_threshold() below (set 0 to hide the bar).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Amount (in your shop currency) for the "free shipping" progress bar. */
function hc_free_shipping_threshold() {
	return (float) apply_filters( 'hc_free_shipping_threshold', 150 );
}

/** The inside of the drawer. Also sent to the browser as a WooCommerce "fragment" so it stays up to date. */
function hc_mini_cart_html() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '<div class="hc-mc-inner"></div>';
	}
	$cart  = WC()->cart;
	$count = (int) $cart->get_cart_contents_count();
	$shop  = wc_get_page_permalink( 'shop' );
	$thr   = hc_free_shipping_threshold();

	ob_start();
	?>
	<div class="hc-mc-inner">
		<div class="hc-mc-head">
			<h3><?php esc_html_e( 'Your Cart', 'houssaini-core' ); ?> <span class="hc-mc-n">(<?php echo (int) $count; ?>)</span></h3>
			<button type="button" class="hc-mc-close" aria-label="<?php esc_attr_e( 'Close', 'houssaini-core' ); ?>">&times;</button>
		</div>

		<?php if ( $cart->is_empty() ) : ?>
			<div class="hc-mc-empty">
				<span class="hc-mc-eico"><?php echo hc_icon( 'cart' ); // phpcs:ignore ?></span>
				<p><?php esc_html_e( 'Your cart is empty', 'houssaini-core' ); ?></p>
				<a class="hc-btn" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Continue Shopping', 'houssaini-core' ); ?><?php echo hc_icon( 'arrow' ); // phpcs:ignore ?></a>
			</div>
		<?php else : ?>

			<?php
			if ( $thr > 0 ) {
				$sub  = (float) $cart->get_displayed_subtotal();
				$left = max( 0, $thr - $sub );
				$pct  = (int) min( 100, round( $sub / $thr * 100 ) );
				echo '<div class="hc-mc-ship"><p>';
				if ( $left > 0 ) {
					/* translators: %s: amount still needed for free shipping */
					echo wp_kses_post( sprintf( __( 'Add %s more for free shipping', 'houssaini-core' ), wc_price( $left ) ) );
				} else {
					esc_html_e( "You've got free shipping!", 'houssaini-core' );
				}
				echo '</p><i><u style="width:' . (int) $pct . '%"></u></i></div>';
			}
			?>

			<ul class="hc-mc-items">
				<?php
				foreach ( $cart->get_cart() as $key => $item ) :
					$product = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );
					if ( ! $product || ! $product->exists() || $item['quantity'] <= 0 ) {
						continue;
					}
					$link = $product->is_visible() ? $product->get_permalink( $item ) : '';
					$qty  = (int) $item['quantity'];
					$max  = $product->get_max_purchase_quantity();
					$img  = $product->get_image( 'woocommerce_thumbnail' );
					?>
					<li class="hc-mc-item" data-key="<?php echo esc_attr( $key ); ?>" data-qty="<?php echo (int) $qty; ?>">
						<?php echo $link ? '<a class="hc-mc-img" href="' . esc_url( $link ) . '">' . $img . '</a>' : '<span class="hc-mc-img">' . $img . '</span>'; // phpcs:ignore ?>
						<div class="hc-mc-info">
							<?php echo $link ? '<a class="hc-mc-name" href="' . esc_url( $link ) . '">' . esc_html( $product->get_name() ) . '</a>' : '<span class="hc-mc-name">' . esc_html( $product->get_name() ) . '</span>'; // phpcs:ignore ?>
							<?php echo wc_get_formatted_cart_item_data( $item ); // phpcs:ignore ?>
							<div class="hc-mc-row">
								<div class="hc-mc-q">
									<button type="button" data-act="dec" aria-label="-">&minus;</button>
									<span><?php echo (int) $qty; ?></span>
									<button type="button" data-act="inc" aria-label="+" <?php disabled( $max > 0 && $qty >= $max ); ?>>+</button>
								</div>
								<span class="hc-mc-price"><?php echo wp_kses_post( $cart->get_product_subtotal( $product, $qty ) ); ?></span>
							</div>
						</div>
						<button type="button" class="hc-mc-rm" data-act="rm" aria-label="<?php esc_attr_e( 'Remove', 'houssaini-core' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6.500 7l1 13h9l1-13M10 11v6M14 11v6" fill="none" stroke="currentColor" stroke-width="1.900" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="hc-mc-foot">
				<div class="hc-mc-sub"><span><?php esc_html_e( 'Subtotal', 'houssaini-core' ); ?></span><strong><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></strong></div>
				<p class="hc-mc-note"><?php esc_html_e( 'Shipping and taxes are calculated at checkout.', 'houssaini-core' ); ?></p>
				<a class="hc-btn hc-mc-checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'houssaini-core' ); ?><?php echo hc_icon( 'arrow' ); // phpcs:ignore ?></a>
				<a class="hc-mc-viewcart" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'View Cart', 'houssaini-core' ); ?></a>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/* keep the drawer up to date when something is added to the cart */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['div.hc-mc-inner'] = hc_mini_cart_html();
	return $fragments;
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! hc_woo_active() ) {
		return;
	}
	foreach ( array( 'wc-add-to-cart', 'wc-cart-fragments' ) as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_enqueue_script( $handle );
		}
	}
}, 20 );

/* change quantity / remove an item from the drawer (uses WooCommerce's own ajax endpoint) */
add_action( 'wc_ajax_hc_cart_qty', function () {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'hc_cart' ) ) { // phpcs:ignore
		wp_send_json_error();
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error();
	}
	$key  = isset( $_POST['key'] ) ? wc_clean( wp_unslash( $_POST['key'] ) ) : ''; // phpcs:ignore
	$qty  = isset( $_POST['qty'] ) ? max( 0, (int) $_POST['qty'] ) : 0; // phpcs:ignore
	$cart = WC()->cart;
	$item = $key ? $cart->get_cart_item( $key ) : null;
	if ( $item ) {
		$max = $item['data']->get_max_purchase_quantity();
		if ( $max > 0 ) {
			$qty = min( $qty, $max );
		}
		$cart->set_quantity( $key, $qty, true );
	}
	$cart->calculate_totals();
	wp_send_json_success( array( 'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ) ) );
} );

/* ------------------------------------------------------------------
 * Markup + CSS + JS (printed on every page)
 * ---------------------------------------------------------------- */

add_action( 'wp_footer', function () {
	if ( ! hc_woo_active() ) {
		return;
	}
	?>
	<div class="hc-mc-overlay"></div>
	<aside class="hc-mc" id="hc-mc" aria-hidden="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'houssaini-core' ); ?>">
		<?php echo hc_mini_cart_html(); // phpcs:ignore ?>
	</aside>

	<style id="hc-mc-css">
	html.hc-noscroll{ overflow:hidden; }
	.hc-mc-overlay{ position:fixed; inset:0; z-index:1290; background:rgba(11,11,13,.55); opacity:0; visibility:hidden; transition:opacity .3s, visibility 0s .3s; }
	.hc-mc-overlay.open{ opacity:1; visibility:visible; transition-delay:0s; }
	.hc-mc{ position:fixed; z-index:1300; inset-block:0; inset-inline-end:0; width:min(430px,92vw); background:#fff; box-shadow:-20px 0 60px rgba(0,0,0,.25); transform:translateX(105%); visibility:hidden; transition:transform .35s cubic-bezier(.2,.7,.2,1), visibility 0s .35s; font-family:var(--hc-body,'Inter',sans-serif); color:#111114; }
	html[dir="rtl"] .hc-mc{ transform:translateX(-105%); box-shadow:20px 0 60px rgba(0,0,0,.25); }
	.hc-mc.open{ transform:none !important; visibility:visible; transition-delay:0s; }
	.hc-mc-inner{ display:flex; flex-direction:column; height:100%; min-height:0; }

	.hc-mc-head{ display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid #ececf0; }
	.hc-mc-head h3{ margin:0; font:800 21px/1.2 var(--hc-body); color:#111114; }
	.hc-mc-n{ color:#8b8b93; font-weight:600; font-size:16px; }
	.hc-mc .hc-mc-close, .hc-mc .hc-mc-close:hover, .hc-mc .hc-mc-close:focus, .hc-mc .hc-mc-close:active{ width:40px; height:40px; border:0; border-radius:50%; background:#f3f3f5; color:#111114; font-size:26px; line-height:1; cursor:pointer; padding:0; display:grid; place-items:center; transition:.2s; }
	.hc-mc .hc-mc-close:hover{ background:#e4161c; color:#fff; }

	.hc-mc-ship{ padding:14px 22px; background:#fff5f5; border-bottom:1px solid #f6dcdd; }
	.hc-mc-ship p{ margin:0 0 9px; font:600 13.5px/1.4 var(--hc-body); color:#33333a; }
	.hc-mc-ship .amount{ color:#e4161c; font-weight:800; }
	.hc-mc-ship i{ display:block; height:7px; border-radius:6px; background:#f0d4d5; overflow:hidden; }
	.hc-mc-ship u{ display:block; height:100%; border-radius:6px; background:#e4161c; text-decoration:none; transition:width .4s; }

	.hc-mc-items{ list-style:none; margin:0; padding:4px 22px; overflow-y:auto; flex:1; min-height:0; }
	.hc-mc-item{ display:grid; grid-template-columns:78px minmax(0,1fr) auto; gap:14px; align-items:start; padding:16px 0; border-bottom:1px solid #ececf0; transition:opacity .2s; }
	.hc-mc-item.busy{ opacity:.45; pointer-events:none; }
	.hc-mc-img{ display:block; width:78px; height:78px; border-radius:14px; background:#f6f6f8; overflow:hidden; }
	.hc-mc-img img{ display:block; width:100%; height:100%; object-fit:contain; padding:6px; box-sizing:border-box; mix-blend-mode:multiply; }
	.hc-mc-info{ min-width:0; }
	.hc-mc-name{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; font:700 15px/1.35 var(--hc-body); color:#111114 !important; text-decoration:none !important; }
	a.hc-mc-name:hover{ color:#e4161c !important; }
	.hc-mc-info dl.variation{ margin:4px 0 0; font-size:12.5px; color:#8b8b93; }
	.hc-mc-info dl.variation dt, .hc-mc-info dl.variation dd{ display:inline; margin:0; }
	.hc-mc-info dl.variation dd p{ display:inline; margin:0; }
	.hc-mc-row{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:10px; }
	.hc-mc-q{ display:inline-flex; align-items:center; border:1.5px solid #e0e0e6; border-radius:999px; overflow:hidden; }
	.hc-mc-q span{ min-width:28px; text-align:center; font:700 14px var(--hc-body); }
	.hc-mc .hc-mc-q button, .hc-mc .hc-mc-q button:focus, .hc-mc .hc-mc-q button:active{ width:32px; height:32px; border:0; background:transparent; color:#55555e; font:500 18px var(--hc-body); cursor:pointer; padding:0; }
	.hc-mc .hc-mc-q button:hover{ background:transparent; color:#e4161c; }
	.hc-mc .hc-mc-q button:disabled{ opacity:.35; cursor:not-allowed; }
	.hc-mc-price{ font:800 15.5px var(--hc-body); color:#e4161c; }
	.hc-mc .hc-mc-rm, .hc-mc .hc-mc-rm:focus, .hc-mc .hc-mc-rm:active{ width:34px; height:34px; border:0; border-radius:50%; background:transparent; color:#9a9aa3; cursor:pointer; padding:0; display:grid; place-items:center; transition:.2s; }
	.hc-mc .hc-mc-rm:hover{ background:#fdeced; color:#e4161c; }
	.hc-mc-rm svg{ width:19px; height:19px; display:block; }

	.hc-mc-foot{ padding:18px 22px 22px; border-top:1px solid #ececf0; box-shadow:0 -10px 30px rgba(0,0,0,.05); }
	.hc-mc-sub{ display:flex; align-items:center; justify-content:space-between; font:600 16px var(--hc-body); }
	.hc-mc-sub strong{ font:800 21px var(--hc-body); }
	.hc-mc-note{ margin:6px 0 16px; font-size:12.5px; color:#8b8b93; }
	.hc-mc .hc-mc-checkout{ width:100%; justify-content:center; padding:16px 24px; font-size:16px; box-sizing:border-box; }
	.hc-mc-viewcart{ display:block; margin-top:12px; padding:14px; text-align:center; border:1.5px solid #e0e0e6; border-radius:999px; font:700 15px var(--hc-body); color:#111114 !important; text-decoration:none !important; transition:.2s; }
	.hc-mc-viewcart:hover{ border-color:#111114; background:#111114; color:#fff !important; }

	.hc-mc-empty{ flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:16px; padding:40px 30px; text-align:center; }
	.hc-mc-eico{ width:84px; height:84px; border-radius:50%; background:#f6f6f8; display:grid; place-items:center; font-size:38px; color:#c3c3cb; }
	.hc-mc-eico svg{ width:1em; height:1em; }
	.hc-mc-empty p{ margin:0; font:700 18px var(--hc-body); }
	</style>

	<script id="hc-mc-js">
	(function(){
		var d=document, box=d.getElementById('hc-mc'), ov=d.querySelector('.hc-mc-overlay');
		if(!box||!ov) return;
		var CFG={ url:<?php echo wp_json_encode( class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'hc_cart_qty' ) : '' ); ?>, nonce:<?php echo wp_json_encode( wp_create_nonce( 'hc_cart' ) ); ?> };

		function open(){ box.classList.add('open'); ov.classList.add('open'); box.setAttribute('aria-hidden','false'); d.documentElement.classList.add('hc-noscroll'); }
		function close(){ box.classList.remove('open'); ov.classList.remove('open'); box.setAttribute('aria-hidden','true'); d.documentElement.classList.remove('hc-noscroll'); }

		function apply(f){
			Object.keys(f).forEach(function(sel){
				var el=d.querySelector(sel); if(!el) return;
				var t=d.createElement('template'); t.innerHTML=String(f[sel]).trim();
				var n=t.content.firstElementChild; if(n) el.replaceWith(n);
			});
		}
		function update(key,qty){
			var li=box.querySelector('.hc-mc-item[data-key="'+key+'"]'); if(li) li.classList.add('busy');
			var fd=new FormData(); fd.append('key',key); fd.append('qty',qty); fd.append('nonce',CFG.nonce);
			fetch(CFG.url,{method:'POST',body:fd,credentials:'same-origin'})
				.then(function(r){ return r.json(); })
				.then(function(r){ if(r&&r.success&&r.data&&r.data.fragments){ apply(r.data.fragments); } else { location.reload(); } })
				.catch(function(){ location.reload(); });
		}

		d.addEventListener('click',function(e){
			/* cart icon in the header */
			var link=e.target.closest('.hc-cart-link');
			if(link&&!e.ctrlKey&&!e.metaKey&&!e.shiftKey&&!d.body.classList.contains('woocommerce-cart')&&!d.body.classList.contains('woocommerce-checkout')){
				e.preventDefault(); open(); return;
			}
			if(e.target.closest('.hc-mc-close')||e.target===ov){ close(); return; }
			var b=e.target.closest('#hc-mc [data-act]'); if(!b) return;
			var li=b.closest('.hc-mc-item'); if(!li) return;
			var key=li.getAttribute('data-key'), qty=parseInt(li.getAttribute('data-qty'),10)||1, act=b.getAttribute('data-act');
			if(act==='inc') update(key,qty+1);
			else if(act==='dec') update(key,qty-1);
			else if(act==='rm') update(key,0);
		});
		d.addEventListener('keydown',function(e){ if(e.key==='Escape') close(); });

		/* open by itself when a product is added (product lists use ajax) */
		if(window.jQuery){ window.jQuery(d.body).on('added_to_cart',function(){ setTimeout(open,90); }); }
		/* product page: the form reloads the page, so remember it and open after the reload */
		d.addEventListener('submit',function(e){
			if(e.target&&e.target.matches&&e.target.matches('form.cart')){ try{ sessionStorage.setItem('hcOpenCart','1'); }catch(x){} }
		});
		try{
			if(sessionStorage.getItem('hcOpenCart')==='1'){ sessionStorage.removeItem('hcOpenCart'); setTimeout(open,350); }
		}catch(x){}
	})();
	</script>
	<?php
}, 6 );