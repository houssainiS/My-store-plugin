<?php
/**
 * Single product page
 * Loaded by houssaini-core.php
 *
 * - WooCommerce's own gallery (white background, hover zoom, thumbnails), lightly styled
 * - discount badge, title, rating, price, stock, short description
 * - quantity, add to cart, wishlist heart
 * - delivery / payment / returns row
 * - tabs: Description (text + your product banner), Specifications, Reviews (styled)
 * - related products
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	add_action( 'woocommerce_after_single_product_summary', 'hc_related_products', 20 );
	add_filter( 'woocommerce_product_tabs', 'hc_product_tabs', 98 );
} );

/* discount / new badge above the title */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$b = hc_product_badge( $product );
	if ( $b ) {
		echo '<span class="hc-tag hc-tag-' . esc_attr( $b[0] ) . ' hc-s-badge">' . esc_html( $b[1] ) . '</span>';
	}
}, 3 );

/* stock status under the price */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( $product->is_on_backorder() ) {
		$cls = 'low';
		$txt = __( 'On backorder', 'houssaini-core' );
	} elseif ( $product->is_in_stock() ) {
		$cls = 'in';
		$txt = __( 'In Stock', 'houssaini-core' );
		$qty = $product->managing_stock() ? $product->get_stock_quantity() : null;
		if ( null !== $qty && $qty > 0 && $qty <= 5 ) {
			$cls = 'low';
			/* translators: %d: items left */
			$txt = sprintf( __( 'Only %d left in stock', 'houssaini-core' ), $qty );
		}
	} else {
		$cls = 'out';
		$txt = __( 'Out of Stock', 'houssaini-core' );
	}
	echo '<span class="hc-s-stock ' . esc_attr( $cls ) . '">' . esc_html( $txt ) . '</span>';
}, 11 );

/* wishlist heart next to the add-to-cart button */
add_action( 'woocommerce_after_add_to_cart_button', function () {
	global $product;
	echo '<button type="button" class="hc-heart hc-heart-lg" data-id="' . (int) $product->get_id() . '" aria-label="' . esc_attr__( 'Add to wishlist', 'houssaini-core' ) . '" aria-pressed="false">' . hc_icon( 'heart' ) . '</button>'; // phpcs:ignore
} );

/* delivery / payment / returns */
add_action( 'woocommerce_single_product_summary', function () {
	$items = apply_filters( 'hc_product_trust', array(
		array( 'truck',  __( 'Free Delivery', 'houssaini-core' ),  __( 'On orders over 150 DT', 'houssaini-core' ) ),
		array( 'shield', __( 'Secure Payment', 'houssaini-core' ), __( '100% secure checkout', 'houssaini-core' ) ),
		array( 'return', __( 'Easy Returns', 'houssaini-core' ),   __( 'Up to 14 days', 'houssaini-core' ) ),
	) );
	echo '<ul class="hc-s-trust">';
	foreach ( $items as $i ) {
		echo '<li><span class="hc-s-ico">' . hc_trust_icon( $i[0] ) . '</span><span><strong>' . esc_html( $i[1] ) . '</strong><small>' . esc_html( $i[2] ) . '</small></span></li>'; // phpcs:ignore
	}
	echo '</ul>';
}, 45 );

/* ------------------------------------------------------------------
 * Tabs
 * ---------------------------------------------------------------- */

function hc_product_tabs( $tabs ) {
	// always show a Description tab when we have a banner to show, even if the description is empty
	if ( ! isset( $tabs['description'] ) && hc_img( 'product_banner' ) ) {
		$tabs['description'] = array(
			'title'    => __( 'Description', 'woocommerce' ),
			'priority' => 10,
			'callback' => 'hc_description_tab',
		);
	}
	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['callback'] = 'hc_description_tab';
	}
	if ( isset( $tabs['additional_information'] ) ) {
		$tabs['additional_information']['title'] = __( 'Specifications', 'houssaini-core' );
	}
	if ( isset( $tabs['reviews'] ) ) {
		$tabs['reviews']['callback'] = 'hc_reviews_tab';
	}
	return $tabs;
}

function hc_description_tab() {
	global $product;
	$img = hc_img( 'product_banner' );
	ob_start();
	the_content();
	$content = trim( ob_get_clean() );
	if ( '' === $content && $product ) {
		$content = wpautop( $product->get_short_description() );
	}
	echo '<div class="hc-desc' . ( $img ? ' has-banner' : '' ) . '"><div class="hc-desc-text">' . $content . '</div>'; // phpcs:ignore
	if ( $img ) {
		echo '<div class="hc-desc-banner"><img src="' . esc_url( $img ) . '" alt="" loading="lazy"></div>';
	}
	echo '</div>';
}

/** Reviews tab: rating summary on top, then WooCommerce's list + form (styled below). */
function hc_reviews_tab() {
	global $product;
	if ( $product && wc_review_ratings_enabled() && $product->get_review_count() > 0 ) {
		$avg    = (float) $product->get_average_rating();
		$count  = (int) $product->get_review_count();
		$counts = $product->get_rating_counts();
		echo '<div class="hc-rsum"><div class="hc-rscore"><b>' . esc_html( number_format_i18n( $avg, 1 ) ) . '</b>' . wc_get_rating_html( $avg ) . '<span>'; // phpcs:ignore
		/* translators: %d: number of reviews */
		echo esc_html( sprintf( _n( 'Based on %d review', 'Based on %d reviews', $count, 'houssaini-core' ), $count ) );
		echo '</span></div><div class="hc-rbars">';
		for ( $s = 5; $s >= 1; $s-- ) {
			$n   = isset( $counts[ $s ] ) ? (int) $counts[ $s ] : 0;
			$pct = $count ? round( $n / $count * 100 ) : 0;
			echo '<div class="hc-rbar"><span>' . (int) $s . ' &#9733;</span><i><u style="width:' . (int) $pct . '%"></u></i><em>' . (int) $n . '</em></div>';
		}
		echo '</div></div>';
	}
	comments_template();
}

/* ------------------------------------------------------------------
 * Related products with our cards
 * ---------------------------------------------------------------- */

function hc_related_products() {
	global $product;
	$ids = wc_get_related_products( $product->get_id(), 4 );
	if ( ! $ids ) {
		return;
	}
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	$all   = ( $terms && ! is_wp_error( $terms ) ) ? get_term_link( $terms[0] ) : wc_get_page_permalink( 'shop' );
	?>
	<section class="hc-related">
		<div class="hc-rel-head">
			<h2><?php esc_html_e( 'Related Products', 'houssaini-core' ); ?></h2>
			<a href="<?php echo esc_url( $all ); ?>"><?php esc_html_e( 'View All', 'houssaini-core' ); ?> <?php echo hc_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<ul class="products">
			<?php
			foreach ( $ids as $id ) {
				$p = wc_get_product( $id );
				if ( $p && $p->is_visible() ) {
					echo '<li class="product">' . hc_render_product_card( $p ) . '</li>'; // phpcs:ignore
				}
			}
			?>
		</ul>
	</section>
	<?php
}

/* ------------------------------------------------------------------
 * CSS
 * ---------------------------------------------------------------- */

add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	?>
<style id="hc-product-css">
.single-product .hc-shop-main{ padding-top:clamp(18px,2vw,28px); }
.single-product .hc-crumb{ margin-bottom:22px; }

/* ---- layout ---- */
.single-product div.product{ display:grid; grid-template-columns:minmax(0,1.1fr) minmax(0,1fr); gap:clamp(24px,3.6vw,64px); align-items:start; }
.single-product div.product > .woocommerce-product-gallery{ grid-column:1; }
.single-product div.product div.summary{ width:auto !important; float:none !important; margin:0 !important; padding:0; grid-column:2; }
.single-product div.product .woocommerce-tabs, .single-product div.product .hc-related, .single-product div.product .upsells{ grid-column:1 / -1; }
.single-product .woocommerce-notices-wrapper{ margin-bottom:6px; }

/* ---- gallery: WooCommerce's own gallery (white background, hover zoom, thumbnails below) ---- */
.single-product div.product div.images.woocommerce-product-gallery{ width:100% !important; float:none !important; margin:0 !important; }
.single-product .woocommerce-product-gallery > .flex-viewport, .single-product .woocommerce-product-gallery > .woocommerce-product-gallery__wrapper{ background:#fff; border:1px solid #ececf0; border-radius:20px; overflow:hidden; }
.single-product .woocommerce-product-gallery__image img{ display:block; width:100%; height:auto; }
.single-product .woocommerce-product-gallery__trigger{ top:16px !important; z-index:6; background:#fff; border-radius:50%; box-shadow:0 2px 10px rgba(0,0,0,.12); }
.single-product .flex-control-thumbs{ display:grid !important; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin:12px 0 0 !important; padding:0; list-style:none; }
.single-product .flex-control-thumbs li{ width:auto !important; float:none !important; margin:0 !important; }
.single-product .flex-control-thumbs img{ display:block; width:100%; height:auto; border-radius:12px; border:2px solid #ececf0; opacity:.75; cursor:pointer; transition:.2s; }
.single-product .flex-control-thumbs img:hover{ opacity:1; }
.single-product .flex-control-thumbs img.flex-active{ opacity:1; border-color:#e4161c; }

/* ---- summary ---- */
.single-product .hc-s-badge{ position:static; display:inline-block; margin-bottom:14px; }
.single-product .product_title{ margin:0 0 12px; font:800 clamp(28px,3vw,44px)/1.1 var(--hc-body); letter-spacing:-.015em; color:#111114; }
.single-product .woocommerce-product-rating{ display:flex; align-items:center; gap:10px; margin:0 0 14px; font-size:14px; color:#6f6f78; }
.single-product .woocommerce-product-rating .star-rating{ margin:0; }
.single-product .star-rating span::before{ color:#e4161c; }
.single-product .woocommerce-review-link{ color:#6f6f78 !important; text-decoration:none; }
.single-product .summary p.price, .single-product .summary span.price{ display:flex; flex-wrap:wrap; align-items:baseline; gap:4px 14px; margin:0 0 10px; color:#e4161c; font:800 clamp(28px,2.8vw,40px)/1.1 var(--hc-body); }
.single-product .summary p.price ins{ order:1; text-decoration:none; }
/* WooCommerce paints the price olive/gold with a stronger rule, so these need !important */
.single-product div.product p.price, .single-product div.product span.price, .single-product div.product p.price ins, .single-product div.product p.price .amount, .single-product div.product .woocommerce-variation-price .price, .single-product div.product .woocommerce-variation-price .amount{ color:#e4161c !important; }
.single-product div.product p.price del, .single-product div.product p.price del .amount{ color:#9a9aa3 !important; }
.single-product .summary p.price del{ order:2; color:#9a9aa3; font-size:.58em; font-weight:600; opacity:1; }
.hc-s-stock{ display:inline-flex; align-items:center; gap:8px; margin:0 0 18px; font:700 14px var(--hc-body); color:#0a8f4d; }
.hc-s-stock::before{ content:""; width:10px; height:10px; border-radius:50%; background:currentColor; }
.hc-s-stock.low{ color:#d98200; }
.hc-s-stock.out{ color:#e4161c; }
.single-product div.summary > p.stock{ display:none; }
.single-product .woocommerce-product-details__short-description{ color:#44444c; font-size:16px; line-height:1.7; margin:0 0 24px; }
.single-product form.cart{ display:flex; flex-wrap:wrap; align-items:center; gap:14px; margin:0 0 24px; }
.single-product form.cart .quantity{ display:inline-flex; align-items:center; border:1.5px solid #e0e0e6; border-radius:999px; background:#fff; overflow:hidden; margin:0; }
.single-product form.cart .quantity input.qty{ width:48px; height:54px; border:0; text-align:center; font:700 16px var(--hc-body); background:transparent; -moz-appearance:textfield; padding:0; }
.single-product form.cart .quantity input.qty::-webkit-outer-spin-button, .single-product form.cart .quantity input.qty::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
.hc-qb{ width:46px; height:54px; border:0; background:transparent; font:500 22px var(--hc-body); color:#6f6f78; cursor:pointer; padding:0; }
.single-product .single_add_to_cart_button{ flex:1 1 200px; height:56px; border:0; border-radius:999px; background:#e4161c !important; color:#fff !important; font:700 17px var(--hc-body); padding:0 30px; cursor:pointer; box-shadow:0 12px 26px rgba(228,22,28,.32); transition:.25s; display:inline-flex; align-items:center; justify-content:center; gap:10px; }
.single_add_to_cart_button::before{ content:""; width:22px; height:22px; background:currentColor; -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M2 3h3l2.600 12.200h10.700L21 7H6.200' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3Ccircle cx='9.500' cy='20' r='1.600'/%3E%3Ccircle cx='17' cy='20' r='1.600'/%3E%3C/svg%3E") center/contain no-repeat; mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M2 3h3l2.600 12.200h10.700L21 7H6.200' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3Ccircle cx='9.500' cy='20' r='1.600'/%3E%3Ccircle cx='17' cy='20' r='1.600'/%3E%3C/svg%3E") center/contain no-repeat; }
.single-product .single_add_to_cart_button:hover{ background:#111114 !important; transform:translateY(-2px); }
.single-product .single_add_to_cart_button.disabled, .single-product .single_add_to_cart_button:disabled{ opacity:.45; cursor:not-allowed; transform:none; }
.hc-heart.hc-heart-lg{ position:static; width:76px; height:56px; border-radius:999px; border:1.5px solid #e0e0e6; background:#fff; font-size:24px; box-shadow:none; flex:0 0 auto; }
.hc-heart.hc-heart-lg:hover{ transform:none; border-color:#e4161c; }
.single-product table.variations{ border:0; margin:0 0 16px; width:100%; }
.single-product table.variations td, .single-product table.variations th{ display:block; padding:0 0 10px; border:0; text-align:start; }
.single-product table.variations label{ font-weight:700; font-size:14.5px; }
.single-product table.variations select{ width:100%; max-width:360px; height:48px; border:1.5px solid #e0e0e6; border-radius:12px; padding:0 14px; font:500 15px var(--hc-body); background-color:#fff; }
.single-product .reset_variations{ font-size:13px; color:#e4161c; }
.single-product .woocommerce-variation-price .price{ color:#e4161c; font-weight:800; font-size:24px; }
.single-product .woocommerce-variation-add-to-cart{ display:flex; flex-wrap:wrap; gap:14px; align-items:center; width:100%; }
.hc-s-trust{ list-style:none; margin:0; padding:0; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px 20px; }
.hc-s-trust li{ display:flex; align-items:center; gap:14px; }
.hc-s-ico{ flex:0 0 36px; width:36px; height:36px; display:block; }
.hc-s-ico svg{ width:100%; height:100%; display:block; }
.hc-s-trust strong{ display:block; font:700 14.5px/1.3 var(--hc-body); color:#111114; }
.hc-s-trust small{ display:block; margin-top:2px; font:400 13px/1.3 var(--hc-body); color:#6f6f78; }

/* ---- tabs ---- */
.single-product .woocommerce-tabs{ margin-top:clamp(20px,3vw,44px); }
.single-product .woocommerce-tabs ul.tabs{ display:flex; gap:10px; padding:0 !important; margin:0 0 30px !important; list-style:none; border-bottom:1px solid #ececf0; overflow-x:auto; }
.single-product .woocommerce-tabs ul.tabs::before, .single-product .woocommerce-tabs ul.tabs li::before, .single-product .woocommerce-tabs ul.tabs li::after{ display:none !important; }
.single-product .woocommerce-tabs ul.tabs li{ margin:0 !important; padding:0 !important; border:0 !important; background:none !important; border-radius:0 !important; }
.single-product .woocommerce-tabs ul.tabs li a{ display:block; padding:16px 18px !important; font:600 16px var(--hc-body); color:#55555e !important; text-decoration:none; white-space:nowrap; }
.single-product .woocommerce-tabs ul.tabs li.active a{ color:#e4161c !important; font-weight:700; box-shadow:inset 0 -3px 0 #e4161c; }
.single-product .woocommerce-Tabs-panel{ color:#33333a; line-height:1.8; font-size:16px; }
.single-product .woocommerce-Tabs-panel > h2:first-child{ display:none; }
.single-product .woocommerce-Tabs-panel--additional_information{ max-width:1000px; }

/* description + banner */
.hc-desc{ display:grid; grid-template-columns:minmax(0,1fr); gap:30px; align-items:center; }
.hc-desc.has-banner{ grid-template-columns:minmax(0,1fr) minmax(0,1.25fr); gap:clamp(24px,3.4vw,56px); }
.hc-desc-text > :first-child{ margin-top:0; }
.hc-desc-text h2, .hc-desc-text h3{ margin:0 0 14px; font:800 clamp(22px,2vw,28px)/1.2 var(--hc-body); color:#111114; }
.hc-desc-text p{ margin:0 0 16px; }
.hc-desc-text ul{ list-style:none; margin:0 0 16px; padding:0; display:grid; gap:12px; }
.hc-desc-text ul li{ position:relative; padding-inline-start:36px; line-height:1.5; }
.hc-desc-text ul li::before{ content:""; position:absolute; inset-inline-start:0; top:-1px; width:24px; height:24px; border-radius:50%; background:#fdeced url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M6.500 12.500l3.500 3.500 7.500-8' fill='none' stroke='%23e4161c' stroke-width='2.600' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/16px no-repeat; }
.hc-desc-banner img{ display:block; width:100%; height:100%; max-height:360px; min-height:200px; object-fit:cover; border-radius:20px; }
.single-product table.shop_attributes{ border:0; width:100%; }
.single-product table.shop_attributes th, .single-product table.shop_attributes td{ border:0; border-bottom:1px solid #ececf0; padding:14px 12px; background:none; font-style:normal; }
.single-product table.shop_attributes th{ width:34%; color:#111114; font-weight:700; }

/* ---- reviews ---- */
.hc-rsum{ display:grid; grid-template-columns:minmax(170px,240px) minmax(0,440px); gap:clamp(20px,4vw,64px); align-items:center; padding:clamp(18px,2.4vw,30px); background:#f6f6f8; border-radius:20px; margin-bottom:30px; }
.hc-rscore{ display:flex; flex-direction:column; gap:8px; align-items:flex-start; }
.hc-rscore b{ font:900 clamp(44px,5vw,60px)/1 var(--hc-head); color:#111114; }
.hc-rscore .star-rating{ float:none; margin:0; font-size:19px; }
.hc-rscore span{ color:#6f6f78; font-size:14px; }
.hc-rbar{ display:grid; grid-template-columns:44px minmax(0,1fr) 30px; align-items:center; gap:10px; margin:7px 0; font-size:13.5px; color:#44444c; }
.hc-rbar i{ display:block; height:8px; border-radius:6px; background:#e2e2e7; overflow:hidden; }
.hc-rbar u{ display:block; height:100%; background:#e4161c; border-radius:6px; text-decoration:none; }
.hc-rbar em{ font-style:normal; text-align:end; color:#8b8b93; }

.single-product #reviews{ display:grid; grid-template-columns:minmax(0,1.4fr) minmax(0,1fr); gap:clamp(24px,3.4vw,50px); align-items:start; }
.single-product #reviews #comments .woocommerce-Reviews-title{ display:none; }
.single-product #reviews #comments ol.commentlist{ list-style:none; margin:0; padding:0; display:grid; gap:16px; }
.single-product #reviews #comments ol.commentlist li{ list-style:none; margin:0; padding:0; border:0; }
.single-product #reviews #comments ol.commentlist li .comment_container{ display:flex; gap:16px; align-items:flex-start; padding:20px; background:#fff; border:1px solid #ececf0; border-radius:18px; box-shadow:0 2px 10px rgba(0,0,0,.03); }
.single-product #reviews #comments ol.commentlist li img.avatar{ position:static; flex:0 0 50px; width:50px; height:50px; border-radius:50%; border:0; padding:0; background:#f1f1f4; }
.single-product #reviews #comments ol.commentlist li .comment-text{ flex:1; min-width:0; margin:0; padding:0; border:0; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:6px 14px; }
.single-product #reviews .comment-text .star-rating{ grid-column:2; grid-row:1; float:none; margin:4px 0 0; font-size:15px; }
.single-product #reviews .comment-text .meta{ grid-column:1; grid-row:1; margin:0; font-size:14px; color:#8b8b93; }
.single-product #reviews .comment-text .meta strong{ display:block; color:#111114; font:700 16px var(--hc-body); }
.single-product #reviews .woocommerce-review__dash{ display:none; }
.single-product #reviews .woocommerce-review__published-date{ font-size:13px; color:#8b8b93; }
.single-product #reviews .woocommerce-review__verified{ display:inline-block; margin-inline-start:8px; padding:2px 9px; border-radius:99px; background:#e7f6ee; color:#0a8f4d; font:600 12px var(--hc-body); font-style:normal; }
.single-product #reviews .comment-text .description{ grid-column:1 / -1; color:#33333a; font-size:15px; line-height:1.7; }
.single-product #reviews .comment-text .description p{ margin:0; }
.single-product #reviews .woocommerce-noreviews, .single-product #reviews .woocommerce-verification-required{ margin:0; padding:30px; text-align:center; background:#f6f6f8; border-radius:16px; color:#6f6f78; font-size:15px; }
.single-product #reviews .woocommerce-pagination{ margin-top:20px; }

.single-product #review_form_wrapper{ background:#fff; border:1px solid #ececf0; border-radius:20px; padding:clamp(20px,2.4vw,30px); box-shadow:0 10px 34px rgba(0,0,0,.06); }
.single-product #review_form #reply-title{ display:block; margin:0 0 16px; font:800 22px/1.2 var(--hc-body); color:#111114; }
.single-product #review_form .comment-notes{ margin:0 0 16px; font-size:13.5px; color:#8b8b93; }
.single-product #review_form label{ display:block; margin-bottom:6px; font:600 14px var(--hc-body); color:#111114; }
.single-product #review_form p{ margin:0 0 16px; }
.single-product #review_form p.stars{ margin:4px 0 16px; }
.single-product #review_form p.stars a{ font-size:24px; }
.single-product #review_form p.stars a::before{ color:#e4161c !important; }
.single-product #review_form input[type=text], .single-product #review_form input[type=email], .single-product #review_form input[type=url], .single-product #review_form textarea, .single-product #review_form select{ width:100%; border:1.5px solid #e0e0e6; border-radius:12px; padding:12px 14px; font:500 15px var(--hc-body); background:#fff; color:#111114; box-sizing:border-box; }
.single-product #review_form textarea{ min-height:130px; resize:vertical; }
.single-product #review_form input:focus, .single-product #review_form textarea:focus, .single-product #review_form select:focus{ outline:0; border-color:#e4161c; box-shadow:0 0 0 3px rgba(228,22,28,.12); }
.single-product #review_form .comment-form-cookies-consent{ display:flex; align-items:flex-start; gap:10px; }
.single-product #review_form .comment-form-cookies-consent input{ width:auto; margin-top:4px; accent-color:#e4161c; }
.single-product #review_form .comment-form-cookies-consent label{ margin:0; font-weight:400; font-size:13px; color:#6f6f78; }
.single-product #review_form .form-submit{ margin:0; }
.single-product #review_form .form-submit input[type=submit]{ width:100%; height:52px; border:0; border-radius:999px; background:#e4161c; color:#fff; font:700 16px var(--hc-body); cursor:pointer; box-shadow:0 10px 24px rgba(228,22,28,.28); transition:.25s; padding:0 28px; }

/* ---- related ---- */
.hc-related{ margin-top:clamp(24px,3vw,44px); padding-top:clamp(24px,3vw,40px); border-top:1px solid #ececf0; }
.hc-rel-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:22px; }
.hc-rel-head h2{ position:relative; margin:0; padding-top:14px; font:800 clamp(24px,2.2vw,32px)/1.1 var(--hc-body); letter-spacing:-.01em; color:#111114; }
.hc-rel-head h2::before{ content:""; position:absolute; top:0; inset-inline-start:0; width:38px; height:3px; border-radius:2px; background:#e4161c; }
.hc-rel-head a{ display:inline-flex; align-items:center; gap:8px; color:#e4161c !important; font:600 15px var(--hc-body); text-decoration:none !important; white-space:nowrap; }
.hc-rel-head a svg{ width:16px; height:16px; }
.hc-related ul.products{ grid-template-columns:repeat(4,minmax(0,1fr)); }
.single-product .upsells > h2{ font:800 clamp(24px,2.2vw,32px)/1.1 var(--hc-body); margin:30px 0 20px; }

/* ---- responsive ---- */
@media (max-width:1100px){
	.single-product div.product{ grid-template-columns:1fr; }
	.single-product div.product > .woocommerce-product-gallery, .single-product div.product div.summary{ grid-column:1; }
}
@media (max-width:900px){
	.hc-desc.has-banner{ grid-template-columns:1fr; }
	.single-product #reviews{ grid-template-columns:1fr; }
	.hc-related ul.products{ grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width:700px){
	.single-product .flex-control-thumbs{ grid-template-columns:repeat(4,minmax(0,1fr)); }
	.hc-rsum{ grid-template-columns:1fr; }
	.hc-s-trust{ grid-template-columns:1fr; }
	.hc-heart.hc-heart-lg{ width:56px; }
	.single-product .woocommerce-tabs ul.tabs li a{ padding:14px 12px !important; font-size:15px; }
	.single-product #reviews #comments ol.commentlist li .comment_container{ padding:14px; gap:12px; }
}
</style>
	<?php
}, 31 );

/* ------------------------------------------------------------------
 * JS: + / - buttons around the quantity field
 * ---------------------------------------------------------------- */

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	?>
<script id="hc-product-js">
(function(){
	document.querySelectorAll('.single-product form.cart .quantity').forEach(function(q){
		var i=q.querySelector('input.qty');
		if(!i||q.getAttribute('data-hc')) return;
		q.setAttribute('data-hc','1');
		function mk(txt,dir){
			var bt=document.createElement('button'); bt.type='button'; bt.className='hc-qb'; bt.textContent=txt; bt.setAttribute('aria-label',dir>0?'+':'-');
			bt.addEventListener('click',function(){
				var step=parseFloat(i.step)||1, min=i.min===''?1:parseFloat(i.min), max=i.max===''?Infinity:parseFloat(i.max), v=(parseFloat(i.value)||min)+dir*step;
				i.value=Math.max(min,Math.min(max,v)); i.dispatchEvent(new Event('change',{bubbles:true}));
			});
			return bt;
		}
		q.insertBefore(mk('\u2212',-1),i); q.appendChild(mk('+',1));
	});
})();
</script>
	<?php
}, 31 );