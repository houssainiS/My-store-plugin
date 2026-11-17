<?php
/**
 * Fixes the magenta (#cc3366) colour that Hello Elementor puts on every button
 * when you hover, click or focus it. Loaded by houssaini-core.php
 *
 * It is printed last, so it wins over the theme. Each of our buttons gets its own
 * hover / click colours here. Any other button on the site (cart, checkout, login...)
 * turns red instead of magenta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', function () {
	?>
<style id="hc-button-fix">
/* any other button: brand red instead of Hello's magenta */
button:hover, button:focus, button:active,
[type="button"]:hover, [type="button"]:focus, [type="button"]:active,
[type="submit"]:hover, [type="submit"]:focus, [type="submit"]:active{ background-color:#e4161c; border-color:#e4161c; color:#fff; }

/* header */
.hc-burger:hover, .hc-burger:focus, .hc-burger:active{ background:transparent; color:#0b0b0d; }
.hc-search button:hover, .hc-search button:focus, .hc-search button:active{ background:transparent; color:#e4161c; }

/* hero slider */
.hc-idx button:focus, .hc-idx button:active{ background:none; color:#8b8b93; }
.hc-idx button:hover, .hc-idx button.on, .hc-idx button.on:focus, .hc-idx button.on:active{ background:none; color:#e4161c; }
.hc-ctrl button:focus, .hc-ctrl button:active{ background:#fff; border-color:#c9c9d0; color:#111114; }
.hc-ctrl button:hover{ background:#e4161c; border-color:#e4161c; color:#fff; }

/* heart buttons */
.hc-heart:hover, .hc-heart:focus, .hc-heart:active{ background:#fff; color:#e4161c; }
.hc-heart.on, .hc-heart.on:hover, .hc-heart.on:focus, .hc-heart.on:active{ background:#e4161c; color:#fff; }

/* shop page */
.hc-view:focus, .hc-view:active{ background:transparent; color:#33333a; }
.hc-view:hover{ background:#f6f6f8; color:#33333a; }
.hc-view.on, .hc-view.on:hover, .hc-view.on:focus, .hc-view.on:active{ background:#fdeced; color:#e4161c; }
.hc-filter-btn:focus, .hc-filter-btn:active{ background:#111114; color:#fff; }
.hc-filter-btn:hover{ background:#e4161c; color:#fff; }
.hc-fclose:hover, .hc-fclose:focus, .hc-fclose:active{ background:none; color:#e4161c; }

/* product page */
.hc-qb:focus, .hc-qb:active{ background:transparent; color:#111114; }
.hc-qb:hover{ background:transparent; color:#e4161c; }
.hc-thumbs button:hover, .hc-thumbs button:focus, .hc-thumbs button:active{ background:#f6f6f8; }
.hc-thumb-more:focus, .hc-thumb-more:active{ background:#fff; border-color:#e0e0e6; color:#111114; }
.hc-thumb-more:hover{ background:#111114; border-color:#111114; color:#fff; }
.single-product #review_form .form-submit input[type=submit]:focus, .single-product #review_form .form-submit input[type=submit]:active{ background:#e4161c; border-color:#e4161c; color:#fff; }
.single-product #review_form .form-submit input[type=submit]:hover{ background:#111114; border-color:#111114; color:#fff; }

/* back to top */
.hc-top:focus, .hc-top:active{ background:#e4161c; color:#fff; }
.hc-top:hover{ background:#111114; color:#fff; }
</style>
	<?php
}, 99 );