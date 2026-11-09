<?php
/**
 * Featured products + promo card  ->  shortcode [houssaini_featured]
 * Loaded by houssaini-core.php
 *
 * Options:
 *   [houssaini_featured limit="4"]                 number of product cards (default 4)
 *   [houssaini_featured source="featured"]         featured (default), latest, or sale
 *
 * "featured" uses the star icon in Products > All Products. If you have fewer
 * featured products than the limit, it is filled up with your latest products.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Data
 * ---------------------------------------------------------------- */

function hc_get_featured_products( $limit = 4, $source = 'featured' ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$limit = max( 1, min( 12, (int) $limit ) );
	$base  = array( 'status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects' );

	if ( 'sale' === $source ) {
		$ids = wc_get_product_ids_on_sale();
		return $ids ? wc_get_products( $base + array( 'limit' => $limit, 'include' => $ids ) ) : array();
	}

	$products = array();
	if ( 'latest' !== $source ) {
		$products = wc_get_products( $base + array( 'limit' => $limit, 'featured' => true ) );
	}
	if ( count( $products ) < $limit ) {
		$have = array_map( function ( $p ) {
			return $p->get_id();
		}, $products );
		$more = wc_get_products( $base + array( 'limit' => $limit - count( $products ), 'exclude' => $have ) );
		$products = array_merge( $products, $more );
	}
	return $products;
}

/** Badge shown on the card: sold out / -20% / New. Returns array( type, text ) or null. */
function hc_product_badge( $product ) {
	if ( ! $product->is_in_stock() ) {
		return array( 'out', __( 'Sold out', 'houssaini-core' ) );
	}
	if ( $product->is_on_sale() ) {
		if ( $product->is_type( 'variable' ) ) {
			$reg  = (float) $product->get_variation_regular_price( 'min' );
			$sale = (float) $product->get_variation_sale_price( 'min' );
		} else {
			$reg  = (float) $product->get_regular_price();
			$sale = (float) $product->get_sale_price();
		}
		if ( $reg > 0 && $sale > 0 && $sale < $reg ) {
			return array( 'sale', '-' . round( ( 1 - $sale / $reg ) * 100 ) . '%' );
		}
		return array( 'sale', __( 'Sale', 'houssaini-core' ) );
	}
	$created = $product->get_date_created();
	if ( $created && ( time() - $created->getTimestamp() ) < 14 * DAY_IN_SECONDS ) {
		return array( 'new', __( 'New', 'houssaini-core' ) );
	}
	return null;
}

/* ------------------------------------------------------------------
 * Product card
 * ---------------------------------------------------------------- */

function hc_render_product_card( $product ) {
	$id    = $product->get_id();
	$link  = $product->get_permalink();
	$badge = hc_product_badge( $product );

	// first category name
	$cat   = '';
	$terms = get_the_terms( $id, 'product_cat' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$cat = $terms[0]->name;
	}

	// hover image = first gallery image
	$gallery = $product->get_gallery_image_ids();
	$hover   = $gallery ? wp_get_attachment_image( $gallery[0], 'woocommerce_single', false, array( 'class' => 'hc-img-hover', 'alt' => '', 'loading' => 'lazy' ) ) : '';

	$ajax = $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' );
	ob_start();
	?>
	<article class="hc-pcard">
		<div class="hc-pmedia">
			<?php if ( $badge ) : ?><span class="hc-tag hc-tag-<?php echo esc_attr( $badge[0] ); ?>"><?php echo esc_html( $badge[1] ); ?></span><?php endif; ?>
			<button type="button" class="hc-heart" data-id="<?php echo (int) $id; ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'houssaini-core' ); ?>" aria-pressed="false"><?php echo hc_icon( 'heart' ); ?></button>
			<a class="hc-pimg<?php echo $hover ? ' has-hover' : ''; ?>" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo $product->get_image( 'woocommerce_single', array( 'class' => 'hc-img-main', 'loading' => 'lazy' ) ); // phpcs:ignore ?>
				<?php echo $hover; // phpcs:ignore ?>
			</a>
		</div>
		<div class="hc-pinfo">
			<?php if ( $cat ) : ?><span class="hc-pcat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
			<h3 class="hc-ptitle"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
			<div class="hc-pbuy">
				<div class="hc-pprice"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<?php if ( $ajax ) : ?>
					<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" class="hc-add add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo (int) $id; ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Add "%s" to your cart', 'houssaini-core' ), $product->get_name() ) ); ?>" rel="nofollow">
						<?php echo hc_icon( 'cart' ); ?>
						<svg class="hc-check" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $link ); ?>" class="hc-add" aria-label="<?php esc_attr_e( 'View product', 'houssaini-core' ); ?>"><?php echo hc_icon( 'arrow' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------
 * Promo card (right side)
 * ---------------------------------------------------------------- */

function hc_render_promo_card() {
	$shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$promo = apply_filters( 'hc_promo', array(
		'eyebrow' => __( 'Limited Time', 'houssaini-core' ),
		'line1'   => __( 'Upgrade', 'houssaini-core' ),
		'line2'   => __( 'Your', 'houssaini-core' ),
		'line3'   => __( 'World.', 'houssaini-core' ),
		'text'    => __( 'Get the best deals on top electronics.', 'houssaini-core' ),
		'button'  => __( 'Shop the Deals', 'houssaini-core' ),
		'link'    => $shop,
	) );
	$bg  = hc_img( 'promo_bg' );
	$gpu = hc_img( 'gpu' );
	ob_start();
	?>
	<a class="hc-promo" href="<?php echo esc_url( $promo['link'] ); ?>"<?php echo $bg ? ' style="background-image:url(' . esc_url( $bg ) . ')"' : ''; ?>>
		<span class="hc-promo-copy">
			<span class="hc-promo-eyebrow"><?php echo esc_html( $promo['eyebrow'] ); ?></span>
			<span class="hc-promo-title"><?php echo esc_html( $promo['line1'] ); ?><br><?php echo esc_html( $promo['line2'] ); ?> <em><?php echo esc_html( $promo['line3'] ); ?></em></span>
			<span class="hc-promo-text"><?php echo esc_html( $promo['text'] ); ?></span>
			<span class="hc-btn"><?php echo esc_html( $promo['button'] ); ?><?php echo hc_icon( 'arrow' ); ?></span>
		</span>
		<?php if ( $gpu ) : ?><img class="hc-gpu" src="<?php echo esc_url( $gpu ); ?>" alt="" loading="lazy"><?php endif; ?>
	</a>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------
 * Shortcode
 * ---------------------------------------------------------------- */

add_shortcode( 'houssaini_featured', function ( $atts ) {
	$atts     = shortcode_atts( array( 'limit' => 4, 'source' => 'featured' ), $atts, 'houssaini_featured' );
	$products = hc_get_featured_products( (int) $atts['limit'], sanitize_key( $atts['source'] ) );
	$shop     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	// make sure WooCommerce's ajax add-to-cart scripts are on this page
	foreach ( array( 'wc-add-to-cart', 'wc-cart-fragments' ) as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_enqueue_script( $handle );
		}
	}

	ob_start();
	?>
	<style id="hc-feat-css">
	.hc-feat{ padding:clamp(34px,4vw,60px) clamp(16px,2.4vw,42px) clamp(34px,4vw,60px); font-family:var(--hc-body,'Inter',sans-serif); color:#111114; }
	.hc-feat-in{ max-width:1680px; margin:0 auto; }
	.hc-feat-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:22px; }
	.hc-feat-eyebrow{ display:flex; align-items:center; gap:14px; color:#e4161c; font:600 13px var(--hc-body); letter-spacing:.06em; text-transform:uppercase; margin-bottom:8px; }
	.hc-feat-eyebrow::before{ content:""; width:50px; height:3px; background:#e4161c; }
	.hc-feat-title{ margin:0; font:800 clamp(26px,2.3vw,36px)/1.1 var(--hc-body); letter-spacing:-.01em; }
	.hc-feat-all{ display:inline-flex; align-items:center; gap:8px; color:#e4161c !important; font:600 15px var(--hc-body); text-decoration:none !important; white-space:nowrap; }
	.hc-feat-all svg{ width:16px; height:16px; transition:transform .25s; }
	.hc-feat-all:hover svg{ transform:translateX(4px); }
	html[dir="rtl"] .hc-feat-all:hover svg{ transform:scaleX(-1) translateX(4px); }

	.hc-feat-body{ display:grid; grid-template-columns:minmax(0,1fr) minmax(300px,31%); gap:18px; align-items:stretch; }
	.hc-feat-grid{ display:grid; grid-template-columns:repeat(var(--n,4),minmax(0,1fr)); gap:18px; }

	/* product card */
	.hc-pcard{ display:flex; flex-direction:column; background:#fff; border:1px solid #ececf0; border-radius:18px; padding:12px; transition:transform .3s cubic-bezier(.2,.7,.2,1), box-shadow .3s, border-color .3s; }
	.hc-pcard:hover{ transform:translateY(-6px); box-shadow:0 22px 44px rgba(0,0,0,.1); border-color:#e0e0e6; }
	.hc-pmedia{ position:relative; border-radius:13px; background:#f6f6f8; aspect-ratio:1/1; overflow:hidden; }
	.hc-pimg{ position:absolute; inset:0; display:grid; place-items:center; padding:12%; }
	.hc-pimg img{ max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; transition:transform .5s cubic-bezier(.2,.7,.2,1), opacity .4s; mix-blend-mode:multiply; }
	.hc-pimg .hc-img-hover{ position:absolute; inset:12%; width:76%; height:76%; opacity:0; }
	.hc-pimg.has-hover:hover .hc-img-main{ opacity:0; }
	.hc-pimg.has-hover:hover .hc-img-hover{ opacity:1; }
	.hc-pcard:hover .hc-pimg img{ transform:scale(1.07); }
	.hc-tag{ position:absolute; top:12px; inset-inline-start:12px; z-index:2; padding:6px 12px; border-radius:8px; font:700 13px/1 var(--hc-body); color:#fff; background:#e4161c; }
	.hc-tag-new{ background:#111114; }
	.hc-tag-out{ background:#8b8b93; }
	.hc-heart{ position:absolute; top:10px; inset-inline-end:10px; z-index:2; width:36px; height:36px; border-radius:50%; border:0; background:#fff; color:#111114; display:grid; place-items:center; cursor:pointer; padding:0; font-size:18px; box-shadow:0 2px 8px rgba(0,0,0,.08); transition:.2s; }
	.hc-heart svg{ width:1em; height:1em; }
	.hc-heart:hover{ color:#e4161c; transform:scale(1.1); }
	.hc-heart.on{ background:#e4161c; color:#fff; }
	.hc-heart.on svg path{ fill:currentColor; }
	.hc-pinfo{ display:flex; flex-direction:column; gap:6px; padding:14px 6px 6px; flex:1; }
	.hc-pcat{ font:500 12px var(--hc-body); color:#8b8b93; text-transform:uppercase; letter-spacing:.05em; }
	.hc-ptitle{ margin:0; font:600 16px/1.35 var(--hc-body); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.7em; }
	.hc-ptitle a{ color:inherit !important; text-decoration:none !important; }
	.hc-ptitle a:hover{ color:#e4161c !important; }
	.hc-pbuy{ margin-top:auto; padding-top:8px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
	.hc-pprice{ font:700 18px var(--hc-body); display:flex; flex-wrap:wrap; align-items:baseline; gap:2px 8px; }
	.hc-pprice .amount{ font-weight:800; }
	.hc-pprice del{ order:2; color:#9a9aa3; font-weight:500; font-size:14px; }
	.hc-pprice ins{ order:1; text-decoration:none; color:#e4161c; }
	.hc-pprice .screen-reader-text{ position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
	.hc-add{ flex:0 0 42px; width:42px; height:42px; border-radius:50%; background:#e4161c; color:#fff !important; display:grid; place-items:center; font-size:20px; text-decoration:none !important; position:relative; transition:.25s; box-shadow:0 8px 18px rgba(228,22,28,.28); }
	.hc-add svg{ width:1em; height:1em; grid-area:1/1; }
	.hc-add:hover{ background:#111114; transform:scale(1.08); }
	.hc-add .hc-check{ opacity:0; transform:scale(.4); transition:.25s; }
	.hc-add.added .hc-check{ opacity:1; transform:none; }
	.hc-add.added svg:not(.hc-check){ opacity:0; }
	.hc-add.loading{ opacity:.6; pointer-events:none; }
	.hc-pcard .added_to_cart{ display:none !important; }

	/* promo card */
	.hc-promo{ position:relative; display:flex; align-items:center; min-height:380px; border-radius:18px; overflow:hidden; background-color:#0b0b0d; background-size:cover; background-position:center; color:#fff !important; text-decoration:none !important; isolation:isolate; }
	.hc-promo::before{ content:""; position:absolute; inset:0; z-index:-1; background:linear-gradient(90deg, rgba(0,0,0,.55), rgba(0,0,0,0) 65%); }
	html[dir="rtl"] .hc-promo::before{ background:linear-gradient(270deg, rgba(0,0,0,.55), rgba(0,0,0,0) 65%); }
	.hc-promo-copy{ position:relative; z-index:2; display:flex; flex-direction:column; align-items:flex-start; padding:clamp(24px,2.6vw,44px); max-width:62%; }
	.hc-promo-eyebrow{ display:flex; align-items:center; gap:10px; color:#e4161c; font:600 13px var(--hc-body); letter-spacing:.06em; text-transform:uppercase; margin-bottom:14px; }
	.hc-promo-eyebrow::before{ content:""; width:18px; height:2px; background:#e4161c; }
	.hc-promo-title{ font:900 clamp(30px,3vw,52px)/1 var(--hc-head); text-transform:uppercase; margin-bottom:16px; }
	.hc-promo-title em{ color:#e4161c; font-style:normal; }
	.hc-promo-text{ font:500 15px/1.5 var(--hc-body); margin-bottom:24px; color:rgba(255,255,255,.9); }
	.hc-promo .hc-btn{ padding:14px 22px; font-size:14px; }
	.hc-promo:hover .hc-btn{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(228,22,28,.4); }
	.hc-gpu{ position:absolute; inset-inline-end:-6%; bottom:-4%; width:68%; max-width:560px; z-index:1; pointer-events:none; filter:drop-shadow(0 24px 30px rgba(0,0,0,.6)); animation:hcGpu 7s ease-in-out infinite; transition:transform .6s cubic-bezier(.2,.7,.2,1); }
	.hc-promo:hover .hc-gpu{ transform:scale(1.06) rotate(-3deg); }
	@keyframes hcGpu{ 0%,100%{ translate:0 0; } 50%{ translate:0 -12px; } }

	@media (max-width:1250px){
		.hc-feat-body{ grid-template-columns:1fr; }
		.hc-promo{ min-height:300px; }
		.hc-promo-copy{ max-width:55%; }
	}
	@media (max-width:900px){
		.hc-feat-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
	}
	@media (max-width:640px){
		.hc-feat-head{ align-items:flex-start; flex-direction:column; gap:10px; }
		.hc-feat-grid{ gap:10px; }
		.hc-pcard{ padding:8px; border-radius:14px; }
		.hc-pinfo{ padding:10px 4px 4px; }
		.hc-ptitle{ font-size:14px; }
		.hc-pprice{ font-size:15px; }
		.hc-add{ flex-basis:36px; width:36px; height:36px; font-size:17px; }
		.hc-promo-copy{ max-width:100%; }
		.hc-gpu{ opacity:.45; width:80%; }
	}
	@media (prefers-reduced-motion:reduce){ .hc-gpu{ animation:none; } }
	</style>

	<section class="hc-feat">
		<div class="hc-feat-in">
			<div class="hc-feat-head">
				<div>
					<span class="hc-feat-eyebrow"><?php esc_html_e( 'Featured Products', 'houssaini-core' ); ?></span>
					<h2 class="hc-feat-title"><?php esc_html_e( 'Top Picks for You', 'houssaini-core' ); ?></h2>
				</div>
				<a class="hc-feat-all" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'View All Products', 'houssaini-core' ); ?> <?php echo hc_icon( 'arrow' ); ?></a>
			</div>

			<div class="hc-feat-body">
				<div class="hc-feat-grid" style="--n:<?php echo (int) max( 1, min( 4, count( $products ) ) ); ?>">
					<?php
					if ( $products ) {
						foreach ( $products as $product ) {
							echo hc_render_product_card( $product ); // phpcs:ignore
						}
					} elseif ( current_user_can( 'manage_options' ) ) {
						echo '<p>' . esc_html__( 'No products yet. Add some in Products > Add New.', 'houssaini-core' ) . '</p>';
					}
					?>
				</div>
				<?php echo hc_render_promo_card(); // phpcs:ignore ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ------------------------------------------------------------------
 * Wishlist (simple, saved in the visitor's browser) + header counter
 * ---------------------------------------------------------------- */

add_action( 'wp_footer', function () {
	?>
<script id="hc-wish-js">
(function(){
	var KEY='hcWish';
	function get(){ try{ return JSON.parse(localStorage.getItem(KEY))||[]; }catch(e){ return []; } }
	function save(a){ try{ localStorage.setItem(KEY,JSON.stringify(a)); }catch(e){} }
	function paint(){
		var w=get();
		document.querySelectorAll('.hc-wish-count').forEach(function(b){ b.textContent=w.length; });
		document.querySelectorAll('.hc-heart').forEach(function(h){
			var on=w.indexOf(h.getAttribute('data-id'))>-1;
			h.classList.toggle('on',on); h.setAttribute('aria-pressed',on?'true':'false');
		});
	}
	document.addEventListener('click',function(e){
		var h=e.target.closest('.hc-heart'); if(!h) return;
		e.preventDefault();
		var w=get(), id=h.getAttribute('data-id'), i=w.indexOf(id);
		if(i>-1){ w.splice(i,1); } else { w.push(id); }
		save(w); paint();
	});
	paint();
})();
</script>
	<?php
}, 25 );