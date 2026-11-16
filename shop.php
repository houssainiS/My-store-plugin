<?php
/**
 * Shop, category and single product pages
 * Loaded by houssaini-core.php
 *
 * - page banner (uses your shop-bar image) + breadcrumb
 * - sidebar: categories, price range slider, availability
 * - toolbar: grid/list switch, result count, sorting
 * - product cards identical to the home page cards
 * - redesigned product page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Product cards in every list use our card
 * ---------------------------------------------------------------- */

add_filter( 'wc_get_template_part', function ( $template, $slug, $name ) {
	if ( 'content' === $slug && 'product' === $name ) {
		return plugin_dir_path( __FILE__ ) . 'content-product.php';
	}
	return $template;
}, 10, 3 );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

// make the add-to-cart button on product lists work without reloading
add_filter( 'pre_option_woocommerce_enable_ajax_add_to_cart', function () {
	return 'yes';
} );

add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
} );
add_filter( 'woocommerce_product_related_products_heading', function () {
	return __( 'You May Also Like', 'houssaini-core' );
} );
add_filter( 'woocommerce_product_upsells_products_heading', function () {
	return __( 'You May Also Like', 'houssaini-core' );
} );

// arrows in the pagination
add_filter( 'woocommerce_pagination_args', function ( $args ) {
	$args['prev_text'] = hc_icon( 'back' );
	$args['next_text'] = hc_icon( 'arrow' );
	return $args;
} );

/* filters: in stock / on sale */
add_action( 'woocommerce_product_query', function ( $q ) {
	if ( ! empty( $_GET['hc_stock'] ) ) { // phpcs:ignore
		$meta   = (array) $q->get( 'meta_query' );
		$meta[] = array( 'key' => '_stock_status', 'value' => 'instock' );
		$q->set( 'meta_query', $meta );
	}
	if ( ! empty( $_GET['hc_sale'] ) ) { // phpcs:ignore
		$ids = wc_get_product_ids_on_sale();
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
	}
} );

/* ------------------------------------------------------------------
 * Page structure (replaces the theme/WooCommerce wrappers)
 * ---------------------------------------------------------------- */

add_action( 'wp', function () {
	if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return;
	}

	add_filter( 'body_class', function ( $c ) {
		$c[] = 'hc-woo';
		return $c;
	} );

	add_filter( 'woocommerce_show_page_title', '__return_false' );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
	if ( ! is_product() ) {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	}

	add_action( 'woocommerce_before_main_content', 'hc_shop_banner', 5 );
	add_action( 'woocommerce_before_main_content', 'hc_shop_open', 10 );
	add_action( 'woocommerce_after_main_content', 'hc_shop_close', 10 );

	/* toolbar: [filters btn][grid|list][result count]   ...   [Sort by: select] */
	add_action( 'woocommerce_before_shop_loop', function () {
		echo '<div class="hc-toolbar"><div class="hc-tl">';
		echo '<button type="button" class="hc-filter-btn">' . esc_html__( 'Filters', 'houssaini-core' ) . '</button>';
		echo '<div class="hc-views">';
		echo '<button type="button" class="hc-view on" data-view="grid" aria-label="' . esc_attr__( 'Grid view', 'houssaini-core' ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.500" y="3.500" width="7" height="7" rx="1.500" fill="currentColor"/><rect x="13.500" y="3.500" width="7" height="7" rx="1.500" fill="currentColor"/><rect x="3.500" y="13.500" width="7" height="7" rx="1.500" fill="currentColor"/><rect x="13.500" y="13.500" width="7" height="7" rx="1.500" fill="currentColor"/></svg></button>';
		echo '<button type="button" class="hc-view" data-view="list" aria-label="' . esc_attr__( 'List view', 'houssaini-core' ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6.500h11M9 12h11M9 17.500h11" stroke="currentColor" stroke-width="2.200" stroke-linecap="round"/><circle cx="4.500" cy="6.500" r="1.400" fill="currentColor"/><circle cx="4.500" cy="12" r="1.400" fill="currentColor"/><circle cx="4.500" cy="17.500" r="1.400" fill="currentColor"/></svg></button>';
		echo '</div>';
	}, 10 );
	add_action( 'woocommerce_before_shop_loop', function () {
		echo '</div><div class="hc-tr"><span class="hc-sortlabel">' . esc_html__( 'Sort by:', 'houssaini-core' ) . '</span>';
	}, 25 );
	add_action( 'woocommerce_before_shop_loop', function () {
		echo '</div></div>';
	}, 40 );
} );

function hc_shop_banner() {
	if ( is_product() ) {
		return;
	}
	$title = woocommerce_page_title( false );
	$desc  = '';
	if ( is_product_taxonomy() ) {
		$desc = wp_strip_all_tags( term_description() );
	} elseif ( is_shop() && ! is_search() ) {
		$desc = __( 'Discover the latest electronics at the best prices.', 'houssaini-core' );
	}
	$bg = hc_img( 'shop_bar' );
	if ( ! $bg ) {
		$bg = hc_img( 'hero_bg' );
	}
	?>
	<section class="hc-sbanner"<?php echo $bg ? ' style="--hc-sbg:url(' . esc_url( $bg ) . ')"' : ''; ?>>
		<div class="hc-sbanner-in">
			<?php
			woocommerce_breadcrumb( array(
				'wrap_before' => '<nav class="hc-crumb" aria-label="breadcrumb">',
				'wrap_after'  => '</nav>',
				'delimiter'   => '<i></i>',
			) );
			?>
			<h1><?php echo esc_html( $title ); ?></h1>
			<?php if ( $desc ) : ?><p><?php echo esc_html( $desc ); ?></p><?php endif; ?>
		</div>
	</section>
	<?php
}

function hc_shop_open() {
	echo '<main id="main" class="hc-shop-main"><div class="hc-shop-in">';
	if ( is_product() ) {
		return;
	}
	echo '<div class="hc-shop-layout">';
	hc_shop_filters();
	echo '<div class="hc-shop-content">';
}

function hc_shop_close() {
	if ( ! is_product() ) {
		echo '</div></div>';
	}
	echo '</div></main>';
}

/* ------------------------------------------------------------------
 * Sidebar
 * ---------------------------------------------------------------- */

/** Highest product price in the store (rounded up), used as the slider maximum. */
function hc_price_max() {
	global $wpdb;
	$hi = (float) $wpdb->get_var( "SELECT MAX(max_price) FROM {$wpdb->prefix}wc_product_meta_lookup" ); // phpcs:ignore
	if ( $hi <= 0 ) {
		return 0;
	}
	$step = $hi > 500 ? 10 : 1;
	return (int) ( ceil( $hi / $step ) * $step );
}

/**
 * Picks a line icon for a category from its name/slug (works in English, French and Arabic names).
 * To force an icon for a category, use the 'hc_cat_icon_key' filter.
 */
function hc_cat_icon( $term ) {
	$icons = array(
		'phone'  => '<rect x="7" y="2.500" width="10" height="19" rx="2.500"/><path d="M11 18.500h2"/>',
		'audio'  => '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="3" y="14" width="4.500" height="6.500" rx="1.800"/><rect x="16.500" y="14" width="4.500" height="6.500" rx="1.800"/>',
		'gaming' => '<path d="M7 7h10a4.500 4.500 0 0 1 4.400 5.400l-.9 4a2.500 2.500 0 0 1-4.500 1L14.500 15h-5L8 17.400a2.500 2.500 0 0 1-4.500-1l-.9-4A4.500 4.500 0 0 1 7 7z"/><path d="M8 10v3M6.500 11.500h3M15.500 10.500h.01M17.500 12.500h.01"/>',
		'laptop' => '<rect x="4.500" y="5" width="15" height="10.500" rx="1.500"/><path d="M2.500 19h19"/>',
		'plug'   => '<path d="M9 3v5M15 3v5M6.500 8h11v3a5.500 5.500 0 0 1-11 0z"/><path d="M12 16.500V21"/>',
		'watch'  => '<rect x="7" y="6.500" width="10" height="11" rx="3"/><path d="M9 6.500L9.700 3h4.600l.7 3.500M9 17.500l.7 3.500h4.600l.7-3.500M12 10v2.500l1.500 1"/>',
		'tv'     => '<rect x="3" y="4.500" width="18" height="12" rx="2"/><path d="M8 20h8M12 16.500V20"/>',
		'camera' => '<path d="M4 8h3l1.500-2.500h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.500"/>',
		'tag'    => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.500" cy="8.500" r="1.200"/>',
	);
	$words = array(
		'phone'  => array( 'phone', 'mobile', 'téléphone', 'telephone', 'هاتف', 'جوال' ),
		'audio'  => array( 'audio', 'headphone', 'sound', 'speaker', 'casque', 'écouteur', 'سماع', 'صوت' ),
		'gaming' => array( 'gam', 'console', 'jeu', 'ألعاب', 'العاب' ),
		'laptop' => array( 'laptop', 'computer', 'pc', 'ordinateur', 'portable', 'حاسوب', 'كمبيوتر' ),
		'watch'  => array( 'watch', 'smart', 'wearable', 'montre', 'ساعة', 'ذكي' ),
		'plug'   => array( 'accessor', 'إكسسوار', 'اكسسوار', 'accessoire' ),
		'tv'     => array( 'tv', 'monitor', 'screen', 'écran', 'ecran', 'television', 'شاشة' ),
		'camera' => array( 'camera', 'caméra', 'photo', 'كاميرا' ),
	);
	$hay = mb_strtolower( $term->slug . ' ' . $term->name );
	$key = 'tag';
	foreach ( $words as $k => $list ) {
		foreach ( $list as $w ) {
			if ( false !== mb_strpos( $hay, $w ) ) {
				$key = $k;
				break 2;
			}
		}
	}
	$key = apply_filters( 'hc_cat_icon_key', $key, $term );
	$svg = isset( $icons[ $key ] ) ? $icons[ $key ] : $icons['tag'];
	return '<svg class="hc-ci" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $svg . '</svg>';
}

function hc_shop_filters() {
	$current    = get_queried_object();
	$current_id = ( $current && isset( $current->term_id ) ) ? (int) $current->term_id : 0;
	$shop_url   = wc_get_page_permalink( 'shop' );

	if ( is_search() ) {
		$action = home_url( '/' );
	} elseif ( $current_id && ! is_wp_error( get_term_link( $current ) ) ) {
		$action = get_term_link( $current );
	} else {
		$action = $shop_url;
	}

	$cur  = html_entity_decode( get_woocommerce_currency_symbol() );
	$hi   = hc_price_max();
	$step = $hi > 500 ? 10 : 1;
	$vmin = isset( $_GET['min_price'] ) ? max( 0, (int) $_GET['min_price'] ) : 0; // phpcs:ignore
	$vmax = isset( $_GET['max_price'] ) ? min( $hi, (int) $_GET['max_price'] ) : $hi; // phpcs:ignore
	$filtered = isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ) || ! empty( $_GET['hc_stock'] ) || ! empty( $_GET['hc_sale'] ); // phpcs:ignore

	// top-level categories in your drag-and-drop order
	$cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
	if ( is_wp_error( $cats ) ) {
		$cats = array();
	}
	usort( $cats, function ( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, 'order', true );
		$ob = (int) get_term_meta( $b->term_id, 'order', true );
		return $oa === $ob ? $a->term_id - $b->term_id : $oa - $ob;
	} );
	$ancestors = $current_id ? get_ancestors( $current_id, 'product_cat' ) : array();
	?>
	<div class="hc-fover"></div>
	<aside class="hc-filters" id="hc-filters">
		<button type="button" class="hc-fclose" aria-label="<?php esc_attr_e( 'Close', 'houssaini-core' ); ?>">&times;</button>

		<div class="hc-fbox">
			<h4 class="hc-fbig"><?php esc_html_e( 'Categories', 'houssaini-core' ); ?></h4>
			<ul class="hc-fcats">
				<li class="<?php echo is_shop() && ! $filtered ? 'active' : ''; ?>"><a href="<?php echo esc_url( $shop_url ); ?>"><span class="hc-fname"><?php esc_html_e( 'All Products', 'houssaini-core' ); ?></span></a></li>
				<?php foreach ( $cats as $c ) :
					$is_active = ( $current_id === $c->term_id ) || in_array( $c->term_id, $ancestors, true );
															?>
					<li class="<?php echo $is_active ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( get_term_link( $c ) ); ?>">
							<span class="hc-fname"><?php echo hc_cat_icon( $c ); // phpcs:ignore ?><?php echo esc_html( $c->name ); ?></span>
							<em><?php echo (int) $c->count; ?></em>
						</a>
						<?php
						if ( $is_active ) {
							$kids = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $c->term_id, 'hide_empty' => false ) );
							if ( ! is_wp_error( $kids ) && $kids ) {
								echo '<ul>';
								foreach ( $kids as $k ) {
									echo '<li class="' . ( $current_id === $k->term_id ? 'active' : '' ) . '"><a href="' . esc_url( get_term_link( $k ) ) . '"><span class="hc-fname">' . esc_html( $k->name ) . '</span><em>' . (int) $k->count . '</em></a></li>';
								}
								echo '</ul>';
							}
						}
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<form class="hc-fform" method="get" action="<?php echo esc_url( $action ); ?>">
			<?php
			// keep sorting and search when filtering
			foreach ( $_GET as $k => $v ) { // phpcs:ignore
				if ( is_scalar( $v ) && in_array( $k, array( 'orderby', 's', 'post_type' ), true ) ) {
					echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( wp_unslash( $v ) ) . '">';
				}
			}
			?>
			<?php if ( $hi > 0 ) : ?>
				<div class="hc-fbox">
					<h4><?php esc_html_e( 'Price Range', 'houssaini-core' ); ?></h4>
					<div class="hc-range" data-min="0" data-max="<?php echo (int) $hi; ?>" data-cur="<?php echo esc_attr( $cur ); ?>">
						<div class="hc-range-track"><span class="hc-range-fill"></span></div>
						<input type="range" class="hc-r-min" min="0" max="<?php echo (int) $hi; ?>" step="<?php echo (int) $step; ?>" value="<?php echo (int) $vmin; ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'houssaini-core' ); ?>">
						<input type="range" class="hc-r-max" min="0" max="<?php echo (int) $hi; ?>" step="<?php echo (int) $step; ?>" value="<?php echo (int) $vmax; ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'houssaini-core' ); ?>">
						<input type="hidden" name="min_price" value="<?php echo (int) $vmin; ?>" disabled>
						<input type="hidden" name="max_price" value="<?php echo (int) $vmax; ?>" disabled>
					</div>
					<div class="hc-range-vals"><span class="hc-vmin"><?php echo (int) $vmin . ' ' . esc_html( $cur ); ?></span><span class="hc-vmax"><?php echo (int) $vmax . ' ' . esc_html( $cur ); ?></span></div>
				</div>
			<?php endif; ?>

			<div class="hc-fbox">
				<h4><?php esc_html_e( 'Availability', 'houssaini-core' ); ?></h4>
				<label class="hc-fcheck"><input type="checkbox" name="hc_stock" value="1" <?php checked( ! empty( $_GET['hc_stock'] ) ); // phpcs:ignore ?>> <?php esc_html_e( 'In stock', 'houssaini-core' ); ?></label>
				<label class="hc-fcheck"><input type="checkbox" name="hc_sale" value="1" <?php checked( ! empty( $_GET['hc_sale'] ) ); // phpcs:ignore ?>> <?php esc_html_e( 'On sale', 'houssaini-core' ); ?></label>
				<?php if ( $filtered ) : ?>
					<a class="hc-freset" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Reset filters', 'houssaini-core' ); ?></a>
				<?php endif; ?>
			</div>
		</form>
	</aside>
	<?php
}

/* ------------------------------------------------------------------
 * Product page extras
 * ---------------------------------------------------------------- */

// category label above the title
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		echo '<a class="hc-s-cat" href="' . esc_url( get_term_link( $terms[0] ) ) . '">' . esc_html( $terms[0]->name ) . '</a>';
	}
}, 4 );

// reassurance row under the add-to-cart button
add_action( 'woocommerce_single_product_summary', function () {
	$tick = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#e4161c"/><path d="M7 12.500l3.500 3.500L17 9" fill="none" stroke="#fff" stroke-width="2.400" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	echo '<ul class="hc-s-trust">';
	foreach ( array(
		__( 'Free Shipping on orders over 150 DT', 'houssaini-core' ),
		__( 'Secure Payment', 'houssaini-core' ),
		__( 'Easy Returns', 'houssaini-core' ),
	) as $t ) {
		echo '<li>' . $tick . '<span>' . esc_html( $t ) . '</span></li>'; // phpcs:ignore
	}
	echo '</ul>';
}, 45 );

/* ------------------------------------------------------------------
 * CSS (only on shop / product pages)
 * ---------------------------------------------------------------- */

add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return;
	}
	?>
<style id="hc-shop-css">
/* ---- layout ---- */
.hc-woo .hc-shop-main{ font-family:var(--hc-body,'Inter',sans-serif); color:#111114; padding:clamp(24px,3vw,44px) 0 clamp(50px,6vw,90px); }
.hc-woo .hc-shop-in{ max-width:1680px; margin:0 auto; padding-inline:clamp(16px,2.4vw,42px); }
.hc-shop-layout{ display:grid; grid-template-columns:280px minmax(0,1fr); gap:clamp(20px,2.4vw,32px); align-items:start; }
.hc-woo a{ transition:color .2s; }

/* ---- banner ---- */
.hc-sbanner{ position:relative; width:100vw; margin-inline:calc(50% - 50vw); background:#0b0b0d var(--hc-sbg) center/cover no-repeat; color:#fff; overflow:hidden; isolation:isolate; }
.hc-sbanner::before{ content:""; position:absolute; inset:0; z-index:-1; background:linear-gradient(90deg,rgba(11,11,13,.6) 0%,rgba(11,11,13,0) 62%); }
html[dir="rtl"] .hc-sbanner::before{ background:linear-gradient(270deg,rgba(11,11,13,.6) 0%,rgba(11,11,13,0) 62%); }
.hc-sbanner-in{ max-width:1680px; margin:0 auto; padding:clamp(34px,4vw,64px) clamp(16px,2.4vw,42px); }
.hc-sbanner h1{ margin:10px 0 0; color:#fff; font:900 clamp(32px,4.2vw,64px)/1 var(--hc-head,'Saira',sans-serif); text-transform:uppercase; letter-spacing:-.005em; }
.hc-sbanner p{ margin:12px 0 0; max-width:56ch; color:rgba(255,255,255,.85); font-size:clamp(14px,1.2vw,18px); line-height:1.5; }
.hc-crumb{ display:flex; flex-wrap:wrap; align-items:center; gap:8px; font-size:13.5px; color:rgba(255,255,255,.78); }
.hc-crumb a{ color:rgba(255,255,255,.78) !important; text-decoration:none; }
.hc-crumb a:hover{ color:#fff !important; }
.hc-crumb i{ width:6px; height:6px; border-top:1.5px solid #e4161c; border-inline-end:1.5px solid #e4161c; transform:rotate(45deg); display:inline-block; margin:0 4px; }
html[dir="rtl"] .hc-crumb i{ transform:rotate(-135deg); }
.single-product .hc-crumb{ color:#6f6f78; margin-bottom:22px; }
.single-product .hc-crumb a{ color:#6f6f78 !important; }
.single-product .hc-crumb a:hover{ color:#e4161c !important; }

/* ---- sidebar ---- */
.hc-filters{ position:sticky; top:calc(var(--hc-hh,66px) + 22px); display:flex; flex-direction:column; gap:16px; }
.hc-fform{ display:flex; flex-direction:column; gap:16px; }
.hc-fbox{ background:#fff; border:1px solid #ececf0; border-radius:18px; padding:18px 18px 16px; box-shadow:0 2px 10px rgba(0,0,0,.03); }
.hc-fbox h4{ display:flex; align-items:center; gap:10px; margin:0 0 14px; font:800 17px/1.2 var(--hc-body); color:#111114; }
.hc-fbox h4::before{ content:""; width:4px; height:18px; border-radius:2px; background:#e4161c; }
.hc-fbox h4.hc-fbig{ font-size:22px; padding-bottom:12px; border-bottom:1px solid #ececf0; margin-bottom:10px; }
.hc-fbox h4.hc-fbig::before{ height:24px; }
.hc-fcats, .hc-fcats ul{ list-style:none; margin:0; padding:0; }
.hc-fcats li{ margin:0; }
.hc-fcats > li > a, .hc-fcats ul a{ display:flex; justify-content:space-between; align-items:center; gap:8px; padding:9px 10px; border-radius:10px; color:#33333a !important; font-size:15px; font-weight:500; text-decoration:none !important; }
.hc-fname{ display:inline-flex; align-items:center; gap:12px; min-width:0; }
.hc-ci{ width:24px; height:24px; flex:0 0 24px; display:block; color:#33333a; transition:color .2s; }
.hc-fcats a:hover .hc-ci{ color:#e4161c; }
.hc-fcats li.active > a .hc-ci{ color:#e4161c; }
.hc-fcats em{ font-style:normal; font-size:12.5px; color:#8b8b93; background:#f1f1f4; min-width:34px; text-align:center; padding:3px 9px; border-radius:99px; }
.hc-fcats a:hover{ background:#f6f6f8; color:#111114 !important; }
.hc-fcats li.active > a{ background:#fdeced; color:#e4161c !important; font-weight:700; }
.hc-fcats li.active > a em{ background:#e4161c; color:#fff; }
.hc-fcats ul{ margin:4px 0 6px; padding-inline-start:16px; }

/* price slider */
.hc-range{ position:relative; height:24px; margin:6px 9px 8px; }
.hc-range-track{ position:absolute; inset-inline:0; top:50%; height:4px; margin-top:-2px; border-radius:4px; background:#e6e6ea; }
.hc-range-fill{ position:absolute; top:0; bottom:0; border-radius:4px; background:#e4161c; }
.hc-range input[type=range]{ position:absolute; inset:0; width:100%; height:24px; margin:0; background:none; pointer-events:none; -webkit-appearance:none; appearance:none; }
.hc-range input[type=range]::-webkit-slider-runnable-track{ background:transparent; height:24px; }
.hc-range input[type=range]::-moz-range-track{ background:transparent; height:24px; }
.hc-range input[type=range]::-webkit-slider-thumb{ -webkit-appearance:none; appearance:none; pointer-events:auto; width:20px; height:20px; border-radius:50%; background:#e4161c; border:3px solid #fff; box-shadow:0 1px 6px rgba(0,0,0,.35); cursor:grab; margin-top:2px; }
.hc-range input[type=range]::-moz-range-thumb{ pointer-events:auto; width:14px; height:14px; border-radius:50%; background:#e4161c; border:3px solid #fff; box-shadow:0 1px 6px rgba(0,0,0,.35); cursor:grab; }
.hc-range-vals{ display:flex; justify-content:space-between; font:600 13px var(--hc-body); color:#33333a; }
.hc-fcheck{ display:flex; align-items:center; gap:10px; padding:7px 0; font-size:15px; color:#33333a; cursor:pointer; }
.hc-fcheck input{ width:18px; height:18px; accent-color:#e4161c; }
.hc-freset{ display:inline-block; margin-top:8px; color:#e4161c !important; font-size:14px; font-weight:600; text-decoration:underline !important; }
.hc-fclose, .hc-filter-btn, .hc-fover{ display:none; }

/* ---- toolbar ---- */
.hc-toolbar{ display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:20px; padding:8px 10px; background:#fff; border:1px solid #ececf0; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.03); }
.hc-tl{ display:flex; align-items:center; gap:14px; min-width:0; }
.hc-views{ display:flex; gap:6px; padding-inline-end:14px; border-inline-end:1px solid #ececf0; }
.hc-view{ width:42px; height:42px; display:grid; place-items:center; border:0; border-radius:10px; background:transparent; color:#33333a; cursor:pointer; padding:0; transition:.2s; }
.hc-view svg{ width:20px; height:20px; display:block; }
.hc-view:hover{ background:#f6f6f8; }
.hc-view.on{ background:#fdeced; color:#e4161c; }
.hc-woo .woocommerce-result-count{ margin:0 !important; float:none !important; font-size:14px; color:#55555e; }
.hc-tr{ display:flex; align-items:center; gap:6px; height:44px; padding-inline-start:16px; border:1.5px solid #e6e6ea; border-radius:12px; }
.hc-sortlabel{ font:700 14px var(--hc-body); color:#111114; white-space:nowrap; }
.hc-woo .woocommerce-ordering{ margin:0 !important; float:none !important; }
.hc-woo .woocommerce-ordering select{ height:40px; border:0; background-color:transparent; padding:0 34px 0 4px; font:600 14px var(--hc-body); color:#111114; cursor:pointer; }
.hc-woo .woocommerce-ordering select:focus{ outline:0; }
.hc-filter-btn{ border:0; background:#111114; color:#fff; height:42px; padding:0 18px; border-radius:999px; font:600 14px var(--hc-body); cursor:pointer; }

/* ---- product grid ---- */
.hc-woo ul.products{ list-style:none; margin:0 !important; padding:0; display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:18px; }
.hc-woo ul.products::before, .hc-woo ul.products::after{ display:none !important; }
.hc-woo ul.products li.product{ width:auto !important; float:none !important; margin:0 !important; padding:0; list-style:none; }
.hc-woo ul.products li.product .hc-pcard{ height:100%; }
/* list view */
.hc-woo ul.products.hc-list{ grid-template-columns:1fr; }
.hc-list .hc-pcard{ flex-direction:row; gap:18px; align-items:stretch; }
.hc-list .hc-pmedia{ flex:0 0 min(250px,34%); height:auto; }
.hc-list .hc-pinfo{ justify-content:center; padding:10px 8px; }
.hc-list .hc-ptitle{ font-size:19px; min-height:0; }
.hc-list .hc-pbuy{ margin-top:14px; max-width:380px; }

/* product card (same as the home page) */
.hc-pcard{ display:flex; flex-direction:column; background:#fff; border:1px solid #ececf0; border-radius:18px; padding:12px; transition:transform .3s cubic-bezier(.2,.7,.2,1), box-shadow .3s, border-color .3s; }
.hc-pcard:hover{ transform:translateY(-6px); box-shadow:0 22px 44px rgba(0,0,0,.1); border-color:#e0e0e6; }
.hc-pmedia{ position:relative; border-radius:13px; background:#f6f6f8; aspect-ratio:1/1; overflow:hidden; }
.hc-pimg{ position:absolute; inset:0; display:grid; place-items:center; padding:9%; }
.hc-pimg img{ max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; transition:transform .5s cubic-bezier(.2,.7,.2,1), opacity .4s; mix-blend-mode:multiply; }
.hc-pimg .hc-img-hover{ position:absolute; inset:9%; width:82%; height:82%; opacity:0; }
.hc-pimg.has-hover:hover .hc-img-main{ opacity:0; }
.hc-pimg.has-hover:hover .hc-img-hover{ opacity:1; }
.hc-pcard:hover .hc-pimg img{ transform:scale(1.07); }
.hc-tag{ position:absolute; top:12px; inset-inline-start:12px; z-index:2; padding:6px 12px; border-radius:8px; font:700 13px/1 var(--hc-body); color:#fff; background:#e4161c; }
.hc-tag-new{ background:#111114; }
.hc-tag-out{ background:#8b8b93; }
.hc-heart{ position:absolute; top:10px; inset-inline-end:10px; z-index:2; width:36px; height:36px; border-radius:50%; border:0; background:#fff; color:#111114; display:grid; place-items:center; cursor:pointer; padding:0; font-size:18px; box-shadow:0 2px 8px rgba(0,0,0,.08); transition:.2s; }
.hc-heart svg{ width:1em; height:1em; display:block; }
.hc-heart:hover{ color:#e4161c; transform:scale(1.1); }
.hc-heart.on{ background:#e4161c; color:#fff; }
.hc-heart.on svg path{ fill:currentColor; }
.hc-pinfo{ display:flex; flex-direction:column; gap:6px; padding:14px 6px 6px; flex:1; }
.hc-pcat{ font:500 12px var(--hc-body); color:#8b8b93; text-transform:uppercase; letter-spacing:.05em; }
.hc-ptitle{ margin:0; font:700 16px/1.35 var(--hc-body); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.7em; }
.hc-ptitle a{ color:inherit !important; text-decoration:none !important; }
.hc-ptitle a:hover{ color:#e4161c !important; }
.hc-pbuy{ margin-top:auto; padding-top:8px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.hc-pprice{ font:700 18px var(--hc-body); display:flex; flex-wrap:wrap; align-items:baseline; gap:2px 8px; }
.hc-pprice .amount{ font-weight:800; }
.hc-pprice del{ order:2; color:#9a9aa3; font-weight:500; font-size:14px; }
.hc-pprice ins{ order:1; text-decoration:none; color:#e4161c; }
.hc-pprice > .amount, .hc-pprice > .woocommerce-Price-amount{ color:#e4161c; }
.hc-pprice del .amount{ color:#9a9aa3; }
.hc-pprice .screen-reader-text{ position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
.hc-add{ flex:0 0 42px; width:42px; height:42px; border-radius:50%; background:#e4161c; color:#fff !important; display:grid; place-items:center; font-size:20px; text-decoration:none !important; position:relative; transition:.25s; box-shadow:0 8px 18px rgba(228,22,28,.28); }
.hc-add svg{ width:1em; height:1em; grid-area:1/1; }
.hc-add:hover{ background:#111114; transform:scale(1.08); }
.hc-add .hc-check{ opacity:0; transform:scale(.4); transition:.25s; }
.hc-add.added .hc-check{ opacity:1; transform:none; }
.hc-add.added svg:not(.hc-check){ opacity:0; }
.hc-add.loading{ opacity:.6; pointer-events:none; }
.hc-pcard .added_to_cart{ display:none !important; }

/* ---- pagination, notices ---- */
.hc-woo .woocommerce-pagination{ margin-top:38px; }
.hc-woo .woocommerce-pagination ul{ display:flex; justify-content:center; flex-wrap:wrap; gap:10px; border:0 !important; margin:0; padding:0; }
.hc-woo .woocommerce-pagination ul li{ border:0 !important; float:none; }
.hc-woo .woocommerce-pagination .page-numbers{ display:grid; place-items:center; min-width:42px; height:42px; padding:0 10px; border-radius:99px; background:#fff; border:1px solid #e6e6ea; color:#111114; font-weight:600; text-decoration:none; }
.hc-woo .woocommerce-pagination .page-numbers svg{ width:18px; height:18px; display:block; }
.hc-woo .woocommerce-pagination a.page-numbers:hover{ background:#111114; border-color:#111114; color:#fff; }
.hc-woo .woocommerce-pagination .page-numbers.current{ background:#e4161c; border-color:#e4161c; color:#fff; }
.hc-woo .woocommerce-pagination .page-numbers.dots{ border:0; background:none; }
.hc-woo .woocommerce-info, .hc-woo .woocommerce-message, .hc-woo .woocommerce-error{ border:0; border-top:3px solid #e4161c; background:#f6f6f8; border-radius:12px; padding:16px 20px; margin:0 0 22px; color:#111114; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; }
.hc-woo .woocommerce-info::before, .hc-woo .woocommerce-message::before, .hc-woo .woocommerce-error::before{ display:none; }
.hc-woo .woocommerce-message .button{ order:2; background:#e4161c; color:#fff; border-radius:99px; padding:11px 22px; font-weight:700; float:none; }
.hc-woo .woocommerce-message .button:hover{ background:#111114; }

/* ================= SINGLE PRODUCT ================= */
.single-product div.product{ display:grid; grid-template-columns:minmax(0,1.05fr) minmax(0,1fr); gap:clamp(24px,3.6vw,64px); align-items:start; }
.single-product div.product > .woocommerce-product-gallery, .single-product div.product div.images{ width:auto !important; float:none !important; margin:0 !important; position:sticky; top:calc(var(--hc-hh,66px) + 22px); align-self:start; }
.single-product div.product div.summary{ width:auto !important; float:none !important; margin:0 !important; padding:0; }
.single-product div.product .woocommerce-tabs, .single-product div.product .related, .single-product div.product .upsells{ grid-column:1 / -1; }
.single-product span.onsale{ position:absolute; inset:16px auto auto 16px; z-index:5; min-width:0; min-height:0; line-height:1; padding:8px 14px; border-radius:8px; background:#e4161c; font:700 13px var(--hc-body); }
html[dir="rtl"] .single-product span.onsale{ inset:16px 16px auto auto; }

/* gallery */
.single-product .woocommerce-product-gallery .flex-viewport, .single-product .woocommerce-product-gallery__wrapper{ background:#f6f6f8; border-radius:22px; overflow:hidden; }
.single-product .woocommerce-product-gallery__image img{ padding:28px; mix-blend-mode:multiply; width:100%; height:auto; }
.single-product .woocommerce-product-gallery__trigger{ top:16px !important; z-index:6; background:#fff; border-radius:50%; width:40px; height:40px; box-shadow:0 2px 10px rgba(0,0,0,.1); }
html:not([dir="rtl"]) .single-product .woocommerce-product-gallery__trigger{ right:16px !important; }
.single-product .flex-control-thumbs{ display:grid !important; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin:12px 0 0 !important; padding:0; list-style:none; }
.single-product .flex-control-thumbs li{ width:auto !important; float:none !important; margin:0 !important; }
.single-product .flex-control-thumbs img{ border-radius:14px; border:2px solid transparent; background:#f6f6f8; opacity:.7; cursor:pointer; transition:.2s; padding:6px; mix-blend-mode:multiply; }
.single-product .flex-control-thumbs img:hover{ opacity:1; }
.single-product .flex-control-thumbs img.flex-active{ opacity:1; border-color:#e4161c; }

/* summary */
.hc-s-cat{ display:inline-block; color:#e4161c !important; font:600 13px var(--hc-body); letter-spacing:.06em; text-transform:uppercase; text-decoration:none !important; margin-bottom:10px; }
.single-product .product_title{ margin:0 0 14px; font:800 clamp(26px,2.6vw,40px)/1.15 var(--hc-body); letter-spacing:-.01em; color:#111114; }
.single-product .woocommerce-product-rating{ display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.single-product .summary p.price, .single-product .summary span.price{ display:flex; flex-wrap:wrap; align-items:baseline; gap:4px 12px; margin:0 0 18px; color:#e4161c; font:800 clamp(28px,2.6vw,38px)/1.1 var(--hc-body); }
.single-product .summary p.price ins{ order:1; text-decoration:none; }
.single-product .summary p.price del{ order:2; color:#9a9aa3; font-size:.55em; font-weight:500; opacity:1; }
.single-product .woocommerce-product-details__short-description{ color:#55555e; font-size:15.5px; line-height:1.75; margin-bottom:20px; }
.single-product .stock{ display:inline-flex; align-items:center; gap:8px; margin:0 0 18px; font:600 14px var(--hc-body); color:#0a8f4d; }
.single-product .stock::before{ content:""; width:9px; height:9px; border-radius:50%; background:currentColor; }
.single-product .stock.out-of-stock{ color:#e4161c; }
.single-product form.cart{ display:flex; flex-wrap:wrap; align-items:center; gap:14px; margin:0 0 22px; }
.single-product form.cart .quantity{ display:inline-flex; align-items:center; border:1.5px solid #e0e0e6; border-radius:999px; background:#fff; overflow:hidden; margin:0; }
.single-product form.cart .quantity input.qty{ width:50px; height:52px; border:0; text-align:center; font:700 16px var(--hc-body); background:transparent; -moz-appearance:textfield; padding:0; }
.single-product form.cart .quantity input.qty::-webkit-outer-spin-button, .single-product form.cart .quantity input.qty::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
.hc-qb{ width:44px; height:52px; border:0; background:transparent; font:600 20px var(--hc-body); color:#111114; cursor:pointer; }
.hc-qb:hover{ color:#e4161c; }
.single-product .single_add_to_cart_button{ flex:1 1 220px; height:54px; border:0; border-radius:999px; background:#e4161c !important; color:#fff !important; font:700 16px var(--hc-body); padding:0 34px; cursor:pointer; box-shadow:0 10px 24px rgba(228,22,28,.3); transition:.25s; }
.single-product .single_add_to_cart_button:hover{ background:#111114 !important; transform:translateY(-2px); }
.single-product .single_add_to_cart_button.disabled, .single-product .single_add_to_cart_button:disabled{ opacity:.45; cursor:not-allowed; transform:none; }
.single-product table.variations{ border:0; margin:0 0 16px; width:100%; }
.single-product table.variations td, .single-product table.variations th{ display:block; padding:0 0 10px; border:0; text-align:start; }
.single-product table.variations label{ font-weight:700; font-size:14.5px; }
.single-product table.variations select{ width:100%; max-width:360px; height:48px; border:1.5px solid #e0e0e6; border-radius:12px; padding:0 14px; font:500 15px var(--hc-body); background-color:#fff; }
.single-product .reset_variations{ font-size:13px; color:#e4161c; }
.single-product .woocommerce-variation-price .price{ color:#e4161c; font-weight:800; font-size:24px; }
.single-product .woocommerce-variation-add-to-cart{ display:flex; flex-wrap:wrap; gap:14px; align-items:center; width:100%; }
.hc-s-trust{ list-style:none; margin:0 0 22px; padding:18px 0; border-block:1px solid #ececf0; display:grid; gap:12px; }
.hc-s-trust li{ display:flex; align-items:center; gap:12px; font:500 14.5px var(--hc-body); color:#33333a; }
.hc-s-trust svg{ width:22px; height:22px; flex:0 0 22px; }
.single-product .product_meta{ display:grid; gap:6px; font-size:14px; color:#6f6f78; }
.single-product .product_meta a{ color:#111114 !important; font-weight:600; text-decoration:none; }
.single-product .product_meta a:hover{ color:#e4161c !important; }

/* tabs */
.single-product .woocommerce-tabs{ margin-top:clamp(10px,2vw,30px); }
.single-product .woocommerce-tabs ul.tabs{ display:flex; gap:6px; padding:0 !important; margin:0 0 28px !important; list-style:none; border-bottom:1px solid #ececf0; overflow-x:auto; }
.single-product .woocommerce-tabs ul.tabs::before, .single-product .woocommerce-tabs ul.tabs li::before, .single-product .woocommerce-tabs ul.tabs li::after{ display:none !important; }
.single-product .woocommerce-tabs ul.tabs li{ margin:0 !important; padding:0 !important; border:0 !important; background:none !important; border-radius:0 !important; }
.single-product .woocommerce-tabs ul.tabs li a{ display:block; padding:14px 22px !important; font:700 15px var(--hc-body); color:#6f6f78 !important; text-decoration:none; white-space:nowrap; }
.single-product .woocommerce-tabs ul.tabs li.active a{ color:#111114 !important; box-shadow:inset 0 -3px 0 #e4161c; }
.single-product .woocommerce-Tabs-panel{ max-width:920px; color:#44444c; line-height:1.85; font-size:15.5px; }
.single-product .woocommerce-Tabs-panel > h2:first-child{ display:none; }
.single-product .woocommerce-Tabs-panel h2{ font:800 22px var(--hc-body); color:#111114; margin:0 0 14px; }
.single-product table.shop_attributes{ border:0; width:100%; }
.single-product table.shop_attributes th, .single-product table.shop_attributes td{ border:0; border-bottom:1px solid #ececf0; padding:14px 12px; background:none; font-style:normal; }
.single-product table.shop_attributes th{ width:34%; color:#111114; font-weight:700; }
.single-product #reviews #comments ol.commentlist{ padding:0; }
.single-product #reviews #comments ol.commentlist li{ list-style:none; }
.single-product #reviews #comments ol.commentlist li .comment_container{ display:flex; gap:16px; }
.single-product #reviews #comments ol.commentlist li img.avatar{ position:static; width:52px; height:52px; border-radius:50%; border:0; padding:0; }
.single-product #reviews #comments ol.commentlist li .comment-text{ flex:1; margin:0; border:1px solid #ececf0; border-radius:14px; padding:16px 18px; }
.single-product #review_form #respond input[type=text], .single-product #review_form #respond input[type=email], .single-product #review_form #respond textarea{ width:100%; border:1.5px solid #e0e0e6; border-radius:12px; padding:12px 14px; font:500 15px var(--hc-body); }
.single-product #review_form #respond input:focus, .single-product #review_form #respond textarea:focus{ outline:0; border-color:#e4161c; }
.single-product #review_form #respond .form-submit input{ background:#e4161c; color:#fff; border:0; border-radius:99px; padding:14px 30px; font-weight:700; cursor:pointer; }
.single-product #review_form #respond .form-submit input:hover{ background:#111114; }

/* related */
.single-product .related.products > h2, .single-product .upsells.products > h2{ margin:clamp(30px,4vw,60px) 0 22px; font:800 clamp(24px,2.2vw,34px)/1.1 var(--hc-body); letter-spacing:-.01em; }
.single-product .related ul.products, .single-product .upsells ul.products{ grid-template-columns:repeat(4,minmax(0,1fr)); }

/* ================= RESPONSIVE ================= */
@media (max-width:1100px){
	.single-product div.product{ grid-template-columns:1fr; }
	.single-product div.product > .woocommerce-product-gallery, .single-product div.product div.images{ position:relative; top:0; }
}
@media (max-width:900px){
	.hc-shop-layout{ grid-template-columns:1fr; }
	.hc-filter-btn{ display:inline-block; }
	.hc-filters{ position:fixed; z-index:1200; inset-block:0; inset-inline-start:0; width:min(88vw,340px); overflow:auto; background:#f6f6f8; padding:54px 14px 20px; transform:translateX(-105%); transition:transform .3s; }
	html[dir="rtl"] .hc-filters{ transform:translateX(105%); }
	.hc-filters.open{ transform:none !important; }
	.hc-fclose{ display:block; position:absolute; top:10px; inset-inline-end:14px; border:0; background:none; font-size:32px; line-height:1; cursor:pointer; color:#111114; }
	.hc-fover{ display:block; position:fixed; inset:0; z-index:1150; background:rgba(0,0,0,.5); opacity:0; visibility:hidden; transition:.3s; }
	.hc-fover.open{ opacity:1; visibility:visible; }
	.hc-woo ul.products{ grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
	.single-product .related ul.products, .single-product .upsells ul.products{ grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width:640px){
	.hc-pcard{ padding:8px; border-radius:14px; }
	.hc-pinfo{ padding:10px 4px 4px; }
	.hc-ptitle{ font-size:14px; }
	.hc-pprice{ font-size:15px; }
	.hc-add{ flex-basis:36px; width:36px; height:36px; font-size:17px; }
	.hc-toolbar .woocommerce-result-count{ display:none; }
	.hc-views{ padding-inline-end:8px; }
	.hc-sortlabel{ display:none; }
	.hc-tr{ padding-inline-start:6px; }
	.hc-list .hc-pmedia{ flex-basis:40%; }
	.hc-list .hc-pbuy{ flex-wrap:wrap; }
	.single-product .flex-control-thumbs{ grid-template-columns:repeat(4,minmax(0,1fr)); }
}
</style>
	<?php
}, 30 );

/* ------------------------------------------------------------------
 * JS: price slider, auto-apply filters, grid/list switch, mobile drawer, quantity buttons
 * ---------------------------------------------------------------- */

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return;
	}
	?>
<script id="hc-shop-js">
(function(){
	/* filter drawer (mobile) */
	var btn=document.querySelector('.hc-filter-btn'), box=document.getElementById('hc-filters'), ov=document.querySelector('.hc-fover'), cl=document.querySelector('.hc-fclose');
	function toggle(o){ if(!box) return; box.classList.toggle('open',o); if(ov) ov.classList.toggle('open',o); }
	if(btn) btn.addEventListener('click',function(){ toggle(true); });
	if(ov) ov.addEventListener('click',function(){ toggle(false); });
	if(cl) cl.addEventListener('click',function(){ toggle(false); });

	/* price slider + auto-apply */
	var form=document.querySelector('.hc-fform');
	var rng=document.querySelector('.hc-range');
	var prep=function(){};
	if(form&&rng){
		var a=rng.querySelector('.hc-r-min'), b=rng.querySelector('.hc-r-max'), fill=rng.querySelector('.hc-range-fill');
		var lo=parseFloat(rng.getAttribute('data-min')), hi=parseFloat(rng.getAttribute('data-max')), cur=rng.getAttribute('data-cur')||'';
		var hMin=rng.querySelector('input[name=min_price]'), hMax=rng.querySelector('input[name=max_price]');
		var vMin=form.querySelector('.hc-vmin'), vMax=form.querySelector('.hc-vmax');
		var sync=function(src){
			var x=parseFloat(a.value), y=parseFloat(b.value);
			if(x>y){ if(src===a){ x=y; a.value=x; } else { y=x; b.value=y; } }
			var span=(hi-lo)||1;
			fill.style.insetInlineStart=((x-lo)/span*100)+'%';
			fill.style.width=((y-x)/span*100)+'%';
			vMin.textContent=Math.round(x)+' '+cur; vMax.textContent=Math.round(y)+' '+cur;
		};
		prep=function(){
			hMin.value=a.value; hMax.value=b.value;
			hMin.disabled=(parseFloat(a.value)<=lo);
			hMax.disabled=(parseFloat(b.value)>=hi);
		};
		a.addEventListener('input',function(){ sync(a); });
		b.addEventListener('input',function(){ sync(b); });
		a.addEventListener('change',function(){ prep(); form.submit(); });
		b.addEventListener('change',function(){ prep(); form.submit(); });
		sync(a);
	}
	if(form){
		form.querySelectorAll('.hc-fcheck input').forEach(function(i){
			i.addEventListener('change',function(){ prep(); form.submit(); });
		});
		form.addEventListener('submit',prep);
	}

	/* grid / list switch (remembered) */
	var list=document.querySelector('.hc-shop-content ul.products'), views=document.querySelectorAll('.hc-view');
	function setView(v){
		if(list) list.classList.toggle('hc-list',v==='list');
		views.forEach(function(x){ x.classList.toggle('on',x.getAttribute('data-view')===v); });
		try{ localStorage.setItem('hcView',v); }catch(e){}
	}
	views.forEach(function(x){ x.addEventListener('click',function(){ setView(x.getAttribute('data-view')); }); });
	var saved='grid'; try{ saved=localStorage.getItem('hcView')||'grid'; }catch(e){}
	setView(saved);

	/* + / - buttons around the quantity field */
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
}, 30 );