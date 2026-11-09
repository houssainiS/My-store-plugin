<?php
/**
 * Plugin Name: Houssaini Core
 * Description: Header, hero slider and custom features for the Houssaini store.
 * Version: 0.1.0
 * Text Domain: houssaini-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HC_VERSION', '0.2.0' );

require_once plugin_dir_path( __FILE__ ) . 'categories.php';
require_once plugin_dir_path( __FILE__ ) . 'featured.php';
require_once plugin_dir_path( __FILE__ ) . 'sections.php';

/* Hide Hello Elementor's automatic page title on the front page and on any page built with Elementor */
add_filter( 'hello_elementor_page_title', function ( $show ) {
	if ( is_front_page() ) {
		return false;
	}
	if ( is_singular() && class_exists( '\Elementor\Plugin' ) ) {
		$doc = \Elementor\Plugin::$instance->documents->get( get_the_ID() );
		if ( $doc && $doc->is_built_with_elementor() ) {
			return false;
		}
	}
	return $show;
} );

/* ------------------------------------------------------------------
 * 1. Helpers
 * ---------------------------------------------------------------- */

/**
 * Finds an image in the Media Library by keyword in its file name.
 * Keys and keywords are listed below; edit them if your file names differ.
 * You can also override any image with: add_filter( 'hc_img', fn( $url, $key ) => ..., 10, 2 );
 */
function hc_img( $key ) {
	$keywords = array(
		'hero_bg'    => array( 'eclipse', 'hero-bg', 'hero_bg', 'banner' ),
		'ps5'        => array( 'ps5', 'playstation' ),
		'laptop'     => array( 'laptop' ),
		'iphone'     => array( 'iphone', 'smartphone', 'phone' ),
		'watch'      => array( 'watch' ),
		'earbuds'    => array( 'earbuds', 'airpods' ),
		'mouse'      => array( 'mouse' ),
		'headphones' => array( 'headphone', 'soundcore' ),
		'keyboard'   => array( 'keyboard' ),
		'promo_bg'   => array( 'limite-time', 'limited-time', 'time-card' ),
		'gpu'        => array( 'graphic-card', 'graphic_card', 'graphiccard', 'gpu' ),
		'wide_bg'    => array( 'under-category-card', 'under-category', 'under_category' ),
	);

	$url = get_transient( 'hc_img_' . $key );

	if ( false === $url || '' === $url ) {
		$url = '';
		global $wpdb;
		foreach ( isset( $keywords[ $key ] ) ? $keywords[ $key ] : array() as $kw ) {
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%%' AND guid LIKE %s ORDER BY ID DESC LIMIT 1",
					'%' . $wpdb->esc_like( $kw ) . '%'
				)
			);
			if ( $id ) {
				$url = wp_get_attachment_url( $id );
				break;
			}
		}
		if ( $url ) {
			set_transient( 'hc_img_' . $key, $url, HOUR_IN_SECONDS );
		}
	}

	return apply_filters( 'hc_img', $url, $key );
}

function hc_icon( $name ) {
	$icons = array(
		'user'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7.5" r="4.5" fill="currentColor"/><path d="M3 21c0-4.4 4-7 9-7s9 2.6 9 7z" fill="currentColor"/></svg>',
		'heart'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-8-4.9-8-11A4.6 4.6 0 0 1 12 6.8 4.6 4.6 0 0 1 20 9.5c0 6.1-8 11-8 11z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
		'cart'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 3h3l2.6 12.2h10.7L21 7H6.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9.5" cy="20" r="1.6" fill="currentColor"/><circle cx="17" cy="20" r="1.6" fill="currentColor"/></svg>',
		'search'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.5 15.5L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		'chevron' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'arrow'   => '<svg class="hc-flip" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16M14 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'back'    => '<svg class="hc-flip" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 12H4M10 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'truck'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 6h11v9H2zM13 9h4l4 3v3h-8z" fill="currentColor"/><circle cx="7" cy="17.5" r="2" fill="currentColor" stroke="#0b0b0d" stroke-width="1.2"/><circle cx="17" cy="17.5" r="2" fill="currentColor" stroke="#0b0b0d" stroke-width="1.2"/></svg>',
		'play'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5l12 7-12 7z" fill="currentColor"/></svg>',
		'menu'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>',
	);
	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

function hc_woo_active() {
	return class_exists( 'WooCommerce' );
}

/* ------------------------------------------------------------------
 * 2. Menu location + assets
 * ---------------------------------------------------------------- */

add_action( 'after_setup_theme', function () {
	register_nav_menu( 'houssaini_primary', __( 'Houssaini Primary Menu', 'houssaini-core' ) );
	load_plugin_textdomain( 'houssaini-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'hc-fonts',
		'https://fonts.googleapis.com/css2?family=Saira:wght@600;700;800;900&family=Inter:wght@400;500;600&family=Cairo:wght@400;600;700;800&display=swap',
		array(),
		null
	);
} );

/* ------------------------------------------------------------------
 * 3. Header (announcement bar + main bar)
 * ---------------------------------------------------------------- */

function hc_fallback_menu() {
	$shop = hc_woo_active() ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	echo '<ul class="hc-menu">';
	echo '<li class="current"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'houssaini-core' ) . '</a></li>';
	echo '<li class="has-sub"><a href="' . esc_url( $shop ) . '">' . esc_html__( 'Shop', 'houssaini-core' ) . hc_icon( 'chevron' ) . '</a>';
	echo '<ul class="sub"><li><a href="' . esc_url( $shop ) . '">' . esc_html__( 'All Products', 'houssaini-core' ) . '</a></li></ul></li>';

	$terms = taxonomy_exists( 'product_cat' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) ) : array();
	echo '<li class="has-sub"><a href="' . esc_url( $shop ) . '">' . esc_html__( 'Categories', 'houssaini-core' ) . hc_icon( 'chevron' ) . '</a><ul class="sub">';
	if ( ! is_wp_error( $terms ) && $terms ) {
		foreach ( $terms as $t ) {
			echo '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
		}
	} else {
		echo '<li><a href="' . esc_url( $shop ) . '">' . esc_html__( 'Add product categories', 'houssaini-core' ) . '</a></li>';
	}
	echo '</ul></li>';
	echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'About', 'houssaini-core' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact', 'houssaini-core' ) . '</a></li>';
	echo '</ul>';
}

function hc_cart_count_html() {
	$count = hc_woo_active() && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	return '<span class="hc-badge hc-cart-count">' . (int) $count . '</span>';
}

add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['span.hc-cart-count'] = hc_cart_count_html();
	return $fragments;
} );

add_action( 'wp_body_open', function () {
	$account = hc_woo_active() ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
	$cart    = hc_woo_active() ? wc_get_cart_url() : '#';
	$wish    = home_url( '/wishlist/' );
	?>
	<header class="hc-header" id="hc-header">
		<div class="hc-topbar">
			<div class="hc-wrap">
				<span class="hc-top-start"><?php echo hc_icon( 'truck' ); ?><?php esc_html_e( 'Free Shipping on orders over 150 DT', 'houssaini-core' ); ?></span>
				<span class="hc-top-mid"><i class="hc-diamond"></i><?php esc_html_e( 'Best Prices in Tunisia', 'houssaini-core' ); ?></span>
				<span class="hc-top-end">
					<a href="<?php echo esc_url( home_url( '/track-order/' ) ); ?>"><?php esc_html_e( 'Track Order', 'houssaini-core' ); ?></a><b></b>
					<a href="<?php echo esc_url( home_url( '/help/' ) ); ?>"><?php esc_html_e( 'Help', 'houssaini-core' ); ?></a><b></b>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'houssaini-core' ); ?></a>
				</span>
			</div>
		</div>
		<div class="hc-main">
			<div class="hc-wrap">
				<button class="hc-burger" type="button" aria-label="<?php esc_attr_e( 'Menu', 'houssaini-core' ); ?>" aria-expanded="false"><?php echo hc_icon( 'menu' ); ?></button>
				<a class="hc-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="HOUSSAINI">HOUSSAINI<span>.</span></a>

				<nav class="hc-nav" aria-label="<?php esc_attr_e( 'Primary', 'houssaini-core' ); ?>">
					<?php
					if ( has_nav_menu( 'houssaini_primary' ) ) {
						wp_nav_menu( array( 'theme_location' => 'houssaini_primary', 'container' => false, 'menu_class' => 'hc-menu', 'depth' => 2 ) );
					} else {
						hc_fallback_menu();
					}
					?>
				</nav>

				<div class="hc-right">
				<form class="hc-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search for products...', 'houssaini-core' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'houssaini-core' ); ?>">
					<input type="hidden" name="post_type" value="product">
					<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'houssaini-core' ); ?>"><?php echo hc_icon( 'search' ); ?></button>
				</form>

				<div class="hc-icons">
					<a href="<?php echo esc_url( $account ); ?>" aria-label="<?php esc_attr_e( 'My account', 'houssaini-core' ); ?>"><?php echo hc_icon( 'user' ); ?></a>
					<a href="<?php echo esc_url( $wish ); ?>" aria-label="<?php esc_attr_e( 'Wishlist', 'houssaini-core' ); ?>"><?php echo hc_icon( 'heart' ); ?><span class="hc-badge hc-wish-count">0</span></a>
					<a href="<?php echo esc_url( $cart ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'houssaini-core' ); ?>"><?php echo hc_icon( 'cart' ); ?><?php echo hc_cart_count_html(); ?></a>
				</div>
				</div>
			</div>
		</div>
	</header>
	<?php
} );

/* ------------------------------------------------------------------
 * 4. Hero slider  ->  shortcode [houssaini_hero]
 * ---------------------------------------------------------------- */

function hc_hero_slides() {
	$shop = hc_woo_active() ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	// Edit titles, prices and links here. Prices are examples.
	$slides = array(
		array(
			'img'     => 'ps5',
			'eyebrow' => __( 'Next-Gen Technology', 'houssaini-core' ),
			'line1'   => __( 'Technology.', 'houssaini-core' ),
			'line2'   => __( 'Redefined.', 'houssaini-core' ),
			'text'    => __( 'Discover the latest electronics, with unmatched quality and performance.', 'houssaini-core' ),
			'name'    => 'PlayStation 5',
			'price'   => '2,299 DT',
			'link'    => $shop,
		),
		array(
			'img'     => 'laptop',
			'eyebrow' => __( 'Work & Play', 'houssaini-core' ),
			'line1'   => __( 'Power.', 'houssaini-core' ),
			'line2'   => __( 'Unleashed.', 'houssaini-core' ),
			'text'    => __( 'Thin, fast and built for everything you do, from work to creation.', 'houssaini-core' ),
			'name'    => __( 'Ultra Laptop', 'houssaini-core' ),
			'price'   => '1,899 DT',
			'link'    => $shop,
		),
		array(
			'img'     => 'iphone',
			'eyebrow' => __( 'Flagship Smartphones', 'houssaini-core' ),
			'line1'   => __( 'Stay.', 'houssaini-core' ),
			'line2'   => __( 'Ahead.', 'houssaini-core' ),
			'text'    => __( 'The newest phones, with the best prices and fast delivery across Tunisia.', 'houssaini-core' ),
			'name'    => 'iPhone Pro',
			'price'   => '4,299 DT',
			'link'    => $shop,
		),
	);
	return apply_filters( 'hc_hero_slides', $slides );
}

add_shortcode( 'houssaini_hero', function () {
	$slides = hc_hero_slides();
	$bg     = hc_img( 'hero_bg' );
	$total  = count( $slides );
	$video  = apply_filters( 'hc_hero_video_url', '' ); // set a YouTube/Vimeo URL to show "Watch Video"
	ob_start();
	?>
	<section class="hc-hero" style="--px:0;--py:0" data-autoplay="6500">
		<div class="hc-bg"<?php echo $bg ? ' style="background-image:url(' . esc_url( $bg ) . ')"' : ''; ?>></div>
		<div class="hc-shade"></div>

		<ol class="hc-idx">
			<?php for ( $i = 0; $i < $total; $i++ ) : ?>
				<li><button type="button" data-go="<?php echo (int) $i; ?>" class="<?php echo 0 === $i ? 'on' : ''; ?>"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></button></li>
			<?php endfor; ?>
		</ol>

		<div class="hc-slides">
			<?php foreach ( $slides as $i => $s ) : $img = hc_img( $s['img'] ); ?>
				<article class="hc-slide<?php echo 0 === $i ? ' is-active' : ''; ?>">
					<div class="hc-copy">
						<span class="hc-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span>
						<h2 class="hc-title"><span><?php echo esc_html( $s['line1'] ); ?></span><em><?php echo esc_html( $s['line2'] ); ?></em></h2>
						<p><?php echo esc_html( $s['text'] ); ?></p>
						<div class="hc-actions">
							<a class="hc-btn" href="<?php echo esc_url( $s['link'] ); ?>"><?php esc_html_e( 'Explore Collection', 'houssaini-core' ); ?><?php echo hc_icon( 'arrow' ); ?></a>
							<?php if ( $video ) : ?>
								<a class="hc-watch" href="<?php echo esc_url( $video ); ?>" target="_blank" rel="noopener"><i><?php echo hc_icon( 'play' ); ?></i><?php esc_html_e( 'Watch Video', 'houssaini-core' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
					<div class="hc-prod">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $s['name'] ); ?>" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
						<?php endif; ?>
					</div>
					<a class="hc-price" href="<?php echo esc_url( $s['link'] ); ?>">
						<span><strong><?php echo esc_html( $s['name'] ); ?></strong><small><?php esc_html_e( 'From', 'houssaini-core' ); ?> <b><?php echo esc_html( $s['price'] ); ?></b></small></span>
						<i><?php echo hc_icon( 'arrow' ); ?></i>
					</a>
				</article>
			<?php endforeach; ?>
		</div>

		<div class="hc-ctrl">
			<button type="button" class="hc-prev" aria-label="<?php esc_attr_e( 'Previous', 'houssaini-core' ); ?>"><?php echo hc_icon( 'back' ); ?></button>
			<span class="hc-count"><b class="hc-cur">1</b> / <?php echo (int) $total; ?></span>
			<button type="button" class="hc-next" aria-label="<?php esc_attr_e( 'Next', 'houssaini-core' ); ?>"><?php echo hc_icon( 'arrow' ); ?></button>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ------------------------------------------------------------------
 * 5. Admin-only debug comment: shows which images were found
 * ---------------------------------------------------------------- */

add_action( 'wp_footer', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$keys = array( 'hero_bg', 'ps5', 'laptop', 'iphone', 'watch', 'earbuds', 'mouse', 'headphones', 'keyboard', 'promo_bg', 'gpu', 'wide_bg' );
	echo "\n<!-- hc-debug (admins only)\n";
	foreach ( $keys as $k ) {
		echo $k . ' => ' . ( hc_img( $k ) ? hc_img( $k ) : 'NOT FOUND' ) . "\n";
	}
	echo "-->\n";
}, 99 );

/* ------------------------------------------------------------------
 * 6. Global CSS
 * ---------------------------------------------------------------- */

add_action( 'wp_head', function () {
	?>
<style id="hc-css">
:root{
	--hc-red:#e4161c;
	--hc-black:#0b0b0d;
	--hc-hh:66px; /* height of the main header bar */
	--hc-ink:#111114;
	--hc-grey:#f3f3f5;
	--hc-line:#e6e6ea;
	--hc-head:'Saira','Cairo',sans-serif;
	--hc-body:'Inter','Cairo',sans-serif;
}
html[dir="rtl"]{ --hc-head:'Cairo','Saira',sans-serif; --hc-body:'Cairo','Inter',sans-serif; }
body{ font-family:var(--hc-body); overflow-x:hidden; }

/* hide the default Hello Elementor header and the page title on the home page */
#site-header, .site-header{ display:none !important; }
.home .page-header, .elementor-page .page-header{ display:none; }

.hc-wrap{ max-width:1680px; margin:0 auto; padding-inline:clamp(16px,2.4vw,52px); display:flex; align-items:center; gap:24px; }
.hc-header a{ text-decoration:none; color:inherit; }
.hc-header svg{ width:1em; height:1em; display:block; }
html[dir="rtl"] .hc-flip{ transform:scaleX(-1); }

/* announcement bar */
.hc-topbar{ background:var(--hc-black); color:#fff; font-size:13px; font-weight:500; }
.hc-topbar .hc-wrap{ justify-content:space-between; min-height:38px; }
.hc-topbar span{ display:flex; align-items:center; gap:10px; }
.hc-topbar svg{ font-size:22px; }
.hc-top-end{ gap:14px !important; }
.hc-top-end b{ width:1px; height:12px; background:var(--hc-red); }
.hc-top-end a:hover{ color:var(--hc-red); }
.hc-diamond{ width:10px; height:10px; background:var(--hc-red); transform:rotate(45deg); display:inline-block; }

/* main bar */
.hc-header{ display:contents; }
.hc-topbar{ position:relative; z-index:1001; }
.hc-main{ position:sticky; top:0; z-index:1000; background:#fff; box-shadow:0 1px 0 var(--hc-line); transition:background .3s, box-shadow .3s; }
.admin-bar .hc-main{ top:32px; }
.hc-main .hc-wrap{ min-height:var(--hc-hh); display:grid; grid-template-columns:minmax(0,1fr) auto minmax(0,1fr); }
.hc-logo{ justify-self:start; }
.hc-right{ display:flex; align-items:center; justify-content:flex-end; gap:22px; }
/* glass header floating over the hero */
html[dir="rtl"]{ --hc-dir:to left; }
/* solid on the logo + menu side, fading to transparent on the search + icons side.
   --hc-solid = where the solid part ends, --hc-fade = where it becomes fully transparent */
body:has(.hc-hero) .hc-main{ background:linear-gradient(var(--hc-dir,to right), rgba(255,255,255,.97) 0%, rgba(255,255,255,.97) var(--hc-solid,58%), rgba(255,255,255,0) var(--hc-fade,84%)); box-shadow:none; }
.hc-main::before{ content:""; position:absolute; inset:0; z-index:-1; background:#fff; opacity:0; transition:opacity .3s; pointer-events:none; }
body:has(.hc-hero) .hc-main.is-scrolled::before{ opacity:.97; }
body:has(.hc-hero) .hc-main.is-scrolled{ box-shadow:0 6px 24px rgba(0,0,0,.08); }
/* white icons while the right side is transparent over the dark hero */
body:has(.hc-hero) .hc-main:not(.is-scrolled) .hc-icons a{ color:#fff !important; }
body:has(.hc-hero) .hc-main:not(.is-scrolled) .hc-icons a:hover{ color:var(--hc-red) !important; }
.hc-logo{ font-family:'Saira',sans-serif; font-weight:900; font-size:30px; letter-spacing:.5px; color:var(--hc-black) !important; line-height:1; }
.hc-logo span{ color:var(--hc-red); }
.hc-nav{ flex:1; display:flex; justify-content:center; }
.hc-menu, .hc-menu ul{ list-style:none; margin:0; padding:0; }
.hc-menu{ display:flex; align-items:center; gap:6px; }
.hc-menu > li{ position:relative; }
.hc-menu > li > a{ display:flex; align-items:center; gap:6px; padding:11px 20px; border-radius:999px; font-weight:600; font-size:15px; transition:.2s; }
.hc-menu > li > a svg{ width:14px; height:14px; transition:.2s; }
.hc-menu > li.current > a, .hc-menu > li.current-menu-item > a{ background:var(--hc-red); color:#fff; }
.hc-menu > li:not(.current):hover > a{ color:var(--hc-red); }
.hc-menu > li:hover > a svg{ transform:rotate(180deg); }
.hc-menu .sub, .hc-menu .sub-menu{ position:absolute; inset-inline-start:0; top:100%; min-width:220px; background:#fff; border:1px solid var(--hc-line); border-radius:14px; padding:8px; box-shadow:0 18px 40px rgba(0,0,0,.12); opacity:0; visibility:hidden; transform:translateY(8px); transition:.2s; }
.hc-menu li:hover > .sub, .hc-menu li:hover > .sub-menu{ opacity:1; visibility:visible; transform:none; }
.hc-menu .sub a, .hc-menu .sub-menu a{ display:block; padding:10px 14px; border-radius:9px; font-size:14px; font-weight:500; }
.hc-menu .sub a:hover, .hc-menu .sub-menu a:hover{ background:var(--hc-grey); color:var(--hc-red); }

.hc-search{ position:relative; width:min(220px,15vw); }
.hc-search input{ width:100%; height:44px; border:0; border-radius:999px; background:#e9e9ec; padding-inline:20px 48px; font:500 14px var(--hc-body); color:var(--hc-ink); outline:0; transition:.2s; }
.hc-search input:focus{ background:#fff; box-shadow:0 0 0 2px var(--hc-red); }
.hc-search button{ position:absolute; inset-inline-end:6px; top:50%; transform:translateY(-50%); width:34px; height:34px; border:0; background:transparent; font-size:19px; color:var(--hc-black); cursor:pointer; padding:0; }
.hc-icons{ display:flex; align-items:center; gap:20px; }
.hc-icons a{ position:relative; font-size:25px; color:var(--hc-black) !important; display:flex; transition:.2s; }
.hc-icons a:hover{ color:var(--hc-red) !important; }
.hc-badge{ position:absolute; top:-8px; inset-inline-end:-9px; min-width:18px; height:18px; padding:0 4px; border-radius:99px; background:var(--hc-red); color:#fff; font:700 11px/18px var(--hc-body); text-align:center; }
.hc-burger{ display:none; border:0; background:transparent; font-size:28px; padding:0; cursor:pointer; color:var(--hc-black); }

/* ============ HERO ============ */
.hc-hero{ position:relative; width:100vw; max-width:100vw; margin-inline:calc(50% - 50vw); margin-top:calc(var(--hc-hh) * -1); height:calc(clamp(500px,32vw,620px) + var(--hc-hh)); overflow:hidden; background:#0b0b0d; isolation:isolate; font-family:var(--hc-body); }
.hc-bg{ position:absolute; inset:-4%; background-size:cover; background-position:center 60%; background-color:#0b0b0d; transform:translate(calc(var(--px) * -22px), calc(var(--py) * -14px)) scale(1.04); transition:transform .5s cubic-bezier(.2,.7,.2,1); z-index:-3; }
.hc-shade{ position:absolute; inset:0; z-index:-2; --dir:to right; background:linear-gradient(var(--dir), #f4f4f6 0%, #f4f4f6 30%, rgba(244,244,246,.85) 40%, rgba(244,244,246,0) 62%); }
html[dir="rtl"] .hc-shade{ --dir:to left; }

.hc-slides{ position:absolute; inset:var(--hc-hh) 0 0 0; }
.hc-slide{ position:absolute; inset:0; opacity:0; visibility:hidden; transition:opacity .7s ease, visibility .7s; }
.hc-slide.is-active{ opacity:1; visibility:visible; }

.hc-copy{ position:absolute; inset-inline-start:clamp(70px,8.2vw,150px); top:50%; transform:translateY(-54%); width:min(46%,720px); color:var(--hc-black); }
.hc-eyebrow{ display:flex; align-items:center; gap:14px; font:600 14px var(--hc-body); letter-spacing:.06em; text-transform:uppercase; margin-bottom:12px; }
.hc-eyebrow::before{ content:""; width:50px; height:3px; background:var(--hc-red); }
.hc-title{ margin:0 0 20px; font-family:var(--hc-head); font-weight:900; font-size:clamp(44px,5.4vw,96px); line-height:.95; letter-spacing:-.01em; text-transform:uppercase; }
.hc-title span, .hc-title em{ display:block; font-style:normal; }
.hc-title em{ color:var(--hc-red); }
.hc-copy p{ margin:0 0 28px; font-size:clamp(15px,1.15vw,20px); line-height:1.5; max-width:30em; font-weight:500; }
.hc-actions{ display:flex; align-items:center; gap:24px; flex-wrap:wrap; }
.hc-btn{ display:inline-flex; align-items:center; gap:12px; background:var(--hc-red); color:#fff !important; padding:17px 28px; border-radius:999px; font:700 16px var(--hc-body); text-decoration:none !important; transition:.25s; box-shadow:0 10px 24px rgba(228,22,28,.3); }
.hc-btn svg{ width:20px; height:20px; transition:.25s; }
.hc-btn:hover{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(228,22,28,.4); }
.hc-btn:hover svg{ transform:translateX(4px); }
html[dir="rtl"] .hc-btn:hover svg{ transform:scaleX(-1) translateX(4px); }
.hc-watch{ display:inline-flex; align-items:center; gap:14px; font:700 16px var(--hc-body); color:var(--hc-black) !important; text-decoration:none !important; }
.hc-watch i{ width:44px; height:44px; border:1.5px solid var(--hc-black); border-radius:50%; display:grid; place-items:center; font-size:18px; }

/* the product: moves with the mouse and floats */
.hc-prod{ position:absolute; inset-block:5% 4%; inset-inline-end:11%; width:min(46%,760px); display:flex; align-items:center; justify-content:center; pointer-events:none;
	transform:translate(calc(var(--px) * 34px), calc(var(--py) * 22px)); transition:transform .45s cubic-bezier(.2,.7,.2,1); }
.hc-prod img{ max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; filter:drop-shadow(0 30px 40px rgba(0,0,0,.55)); opacity:0; transform:translateX(70px) scale(.92); transition:opacity .8s ease .15s, transform .9s cubic-bezier(.2,.7,.2,1) .15s; }
html[dir="rtl"] .hc-prod img{ transform:translateX(-70px) scale(.92); }
.hc-slide.is-active .hc-prod img{ opacity:1; transform:none; animation:hcFloat 6s ease-in-out 1s infinite; }
@keyframes hcFloat{ 0%,100%{ transform:translateY(0) rotate(0deg); } 50%{ transform:translateY(-14px) rotate(-.8deg); } }

/* staggered text entrance */
.hc-slide .hc-eyebrow, .hc-slide .hc-title span, .hc-slide .hc-title em, .hc-slide .hc-copy p, .hc-slide .hc-actions{ opacity:0; transform:translateY(24px); transition:opacity .6s ease, transform .7s cubic-bezier(.2,.7,.2,1); }
.hc-slide.is-active .hc-eyebrow{ opacity:1; transform:none; transition-delay:.15s; }
.hc-slide.is-active .hc-title span{ opacity:1; transform:none; transition-delay:.25s; }
.hc-slide.is-active .hc-title em{ opacity:1; transform:none; transition-delay:.35s; }
.hc-slide.is-active .hc-copy p{ opacity:1; transform:none; transition-delay:.45s; }
.hc-slide.is-active .hc-actions{ opacity:1; transform:none; transition-delay:.55s; }

/* price card */
.hc-price{ position:absolute; inset-inline-end:clamp(16px,2.6vw,50px); top:18%; display:flex; align-items:center; gap:34px; padding:16px 18px 16px 26px; background:rgba(10,10,12,.55); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,.2); border-radius:18px; color:#fff !important; text-decoration:none !important; transition:.25s; }
.hc-price:hover{ border-color:var(--hc-red); transform:translateY(-3px); }
.hc-price strong{ display:block; font:700 17px var(--hc-body); margin-bottom:2px; }
.hc-price small{ font:600 16px var(--hc-body); }
.hc-price b{ color:var(--hc-red); font:800 22px var(--hc-head); }
.hc-price i{ width:48px; height:48px; border-radius:50%; background:var(--hc-red); display:grid; place-items:center; font-size:22px; }
.hc-price svg{ width:22px; height:22px; }

/* index 01 02 03 */
.hc-idx{ position:absolute; inset-inline-start:clamp(14px,2.4vw,42px); top:calc(50% + var(--hc-hh) / 2); transform:translateY(-62%); margin:0; padding:0; list-style:none; z-index:3; display:flex; flex-direction:column; gap:18px; }
.hc-idx button{ background:none; border:0; padding:0; cursor:pointer; font:800 19px var(--hc-head); color:#8b8b93; transition:.25s; position:relative; }
.hc-idx button.on{ color:var(--hc-red); font-size:22px; }
.hc-idx button:hover{ color:var(--hc-red); }

/* arrows */
.hc-ctrl{ position:absolute; inset-inline-start:clamp(14px,2.4vw,42px); bottom:26px; z-index:4; display:flex; align-items:center; gap:14px; color:var(--hc-black); }
.hc-ctrl button{ width:42px; height:42px; border-radius:50%; border:1px solid #c9c9d0; background:#fff; color:var(--hc-black); display:grid; place-items:center; cursor:pointer; padding:0; transition:.2s; }
.hc-ctrl button svg{ width:18px; height:18px; }
.hc-ctrl button:hover{ background:var(--hc-red); border-color:var(--hc-red); color:#fff; }
.hc-count{ font:500 14px var(--hc-body); color:#444; min-width:44px; text-align:center; }
.hc-count b{ font-weight:600; }

/* ============ RESPONSIVE ============ */
@media (max-width:1100px){
	.hc-search{ width:150px; }
	.hc-right{ gap:16px; }
	.hc-menu > li > a{ padding:10px 13px; }
}
@media (max-width:900px){
	.hc-topbar .hc-top-mid, .hc-topbar .hc-top-end{ display:none; }
	.hc-topbar .hc-wrap{ justify-content:center; }
	/* on mobile the bar is a plain light bar with dark icons */
	.hc-main .hc-wrap{ display:flex; }
	body:has(.hc-hero) .hc-main{ background:rgba(255,255,255,.92); }
	body:has(.hc-hero) .hc-main:not(.is-scrolled) .hc-icons a{ color:var(--hc-black) !important; }
	.hc-burger{ display:block; order:0; }
	.hc-logo{ font-size:25px; margin-inline-end:auto; }
	.hc-search{ display:none; }
	.hc-icons{ gap:16px; }
	.hc-nav{ position:fixed; inset-inline:0; top:0; bottom:0; width:min(84vw,340px); background:#fff; z-index:1000; padding:78px 20px 24px; flex-direction:column; justify-content:flex-start; transform:translateX(-105%); transition:transform .3s; box-shadow:10px 0 40px rgba(0,0,0,.2); overflow:auto; inset-inline-end:auto; }
	html[dir="rtl"] .hc-nav{ transform:translateX(105%); inset-inline-end:0; inset-inline-start:auto; }
	.hc-nav.open{ transform:none !important; }
	.hc-menu{ flex-direction:column; align-items:stretch; gap:2px; }
	.hc-menu > li > a{ border-radius:12px; justify-content:space-between; }
	.hc-menu .sub, .hc-menu .sub-menu{ position:static; opacity:1; visibility:visible; transform:none; box-shadow:none; border:0; padding:0 0 6px 12px; display:none; }
	.hc-menu li.sub-open > .sub, .hc-menu li.sub-open > .sub-menu{ display:block; }

	.hc-hero{ height:auto; min-height:calc(640px + var(--hc-hh)); }
	.hc-shade{ background:linear-gradient(to bottom, #f4f4f6 0%, #f4f4f6 48%, rgba(244,244,246,0) 72%); }
	.hc-copy{ inset-inline-start:0; inset-inline-end:0; top:34px; transform:none; width:auto; padding-inline:60px 22px; }
	.hc-title{ font-size:clamp(38px,11vw,60px); }
	.hc-prod{ inset-block:auto calc(70px + var(--hc-over,0px)); inset-inline:0; width:100%; height:42%; }
	.hc-price{ top:auto; bottom:calc(70px + var(--hc-over,0px)); inset-inline-end:14px; gap:16px; padding:10px 12px 10px 16px; }
	.hc-price i{ width:38px; height:38px; }
	.hc-idx{ transform:translateY(-110%); top:38%; gap:12px; }
	.hc-ctrl{ bottom:calc(16px + var(--hc-over,0px)); }
}
@media (prefers-reduced-motion:reduce){
	.hc-slide.is-active .hc-prod img{ animation:none; }
	.hc-bg, .hc-prod{ transition:none; }
}
</style>
	<?php
}, 20 );

/* ------------------------------------------------------------------
 * 7. Global JS
 * ---------------------------------------------------------------- */

add_action( 'wp_footer', function () {
	?>
<script id="hc-js">
(function(){
	/* glass header turns more solid after scrolling */
	var hm=document.querySelector('.hc-main');
	if(hm){
		var onS=function(){ hm.classList.toggle('is-scrolled',window.scrollY>40); };
		onS(); window.addEventListener('scroll',onS,{passive:true});
	}

	/* mobile menu */
	var burger=document.querySelector('.hc-burger'), nav=document.querySelector('.hc-nav');
	if(burger&&nav){
		burger.addEventListener('click',function(){
			var o=nav.classList.toggle('open'); burger.setAttribute('aria-expanded',o?'true':'false');
		});
		document.addEventListener('click',function(e){
			if(nav.classList.contains('open')&&!nav.contains(e.target)&&!burger.contains(e.target)){nav.classList.remove('open');}
		});
		nav.querySelectorAll('li.has-sub > a, li.menu-item-has-children > a').forEach(function(a){
			a.addEventListener('click',function(e){
				if(window.innerWidth<=900){ e.preventDefault(); a.parentNode.classList.toggle('sub-open'); }
			});
		});
	}

	/* keep the hero flush under the announcement bar, whatever padding Elementor adds around the widget */
	var heroEl=document.querySelector('.hc-hero'), topEl=document.querySelector('.hc-topbar');
	function fitHero(){
		if(!heroEl||!topEl) return;
		heroEl.style.marginTop='';
		var gap=heroEl.getBoundingClientRect().top-topEl.getBoundingClientRect().bottom;
		if(Math.abs(gap)>0.5){ heroEl.style.marginTop='calc(var(--hc-hh) * -1 + '+(-gap)+'px)'; }
	}
	fitHero();
	window.addEventListener('load',fitHero);
	window.addEventListener('resize',fitHero);
	if(document.fonts&&document.fonts.ready){ document.fonts.ready.then(fitHero); }

	/* hero slider */
	document.querySelectorAll('.hc-hero').forEach(function(h){
		var slides=h.querySelectorAll('.hc-slide'), n=slides.length, i=0, timer;
		var dots=h.querySelectorAll('.hc-idx button'), cur=h.querySelector('.hc-cur');
		var delay=parseInt(h.getAttribute('data-autoplay'),10)||6500;
		if(!n) return;
		function go(k){
			i=(k+n)%n;
			slides.forEach(function(s,j){ s.classList.toggle('is-active',j===i); });
			dots.forEach(function(d,j){ d.classList.toggle('on',j===i); });
			if(cur) cur.textContent=i+1;
		}
		function play(){ clearInterval(timer); if(n>1) timer=setInterval(function(){ go(i+1); },delay); }
		var rtl=document.documentElement.dir==='rtl';
		var prev=h.querySelector('.hc-prev'), next=h.querySelector('.hc-next');
		if(prev) prev.addEventListener('click',function(){ go(i-1); play(); });
		if(next) next.addEventListener('click',function(){ go(i+1); play(); });
		dots.forEach(function(d){ d.addEventListener('click',function(){ go(parseInt(d.getAttribute('data-go'),10)); play(); }); });

		/* mouse parallax: the product and the eclipse move at different depths */
		if(window.matchMedia('(hover:hover)').matches){
			h.addEventListener('mousemove',function(e){
				var r=h.getBoundingClientRect();
				h.style.setProperty('--px',((e.clientX-r.left)/r.width-.5).toFixed(3));
				h.style.setProperty('--py',((e.clientY-r.top)/r.height-.5).toFixed(3));
			});
			h.addEventListener('mouseleave',function(){ h.style.setProperty('--px',0); h.style.setProperty('--py',0); });
		}

		/* swipe on touch devices */
		var sx=null;
		h.addEventListener('touchstart',function(e){ sx=e.touches[0].clientX; },{passive:true});
		h.addEventListener('touchend',function(e){
			if(sx===null) return; var dx=e.changedTouches[0].clientX-sx; sx=null;
			if(Math.abs(dx)>50){ go(i+((dx<0)!==rtl?1:-1)); play(); }
		});
		document.addEventListener('visibilitychange',function(){ document.hidden?clearInterval(timer):play(); });
		play();
	});
})();
</script>
	<?php
}, 20 );