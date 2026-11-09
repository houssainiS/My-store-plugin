<?php
/**
 * Category strip  ->  shortcode [houssaini_categories]
 * Loaded by houssaini-core.php
 *
 * Fully dynamic: it reads your WooCommerce product categories
 * (name, link, thumbnail and drag-and-drop order).
 *
 * Options:
 *   [houssaini_categories limit="6"]             how many cards to show (default 6)
 *   [houssaini_categories hide_empty="yes"]      hide categories that have no products
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the cards to display, from WooCommerce top-level categories.
 * Order = the order you set in Products > Categories (drag and drop).
 */
function hc_get_categories( $limit = 6, $hide_empty = false ) {
	$cards = array();

	if ( taxonomy_exists( 'product_cat' ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => $hide_empty,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ), // skip "Uncategorized"
			)
		);

		if ( ! is_wp_error( $terms ) && $terms ) {
			usort( $terms, function ( $a, $b ) {
				$oa = (int) get_term_meta( $a->term_id, 'order', true );
				$ob = (int) get_term_meta( $b->term_id, 'order', true );
				return $oa === $ob ? $a->term_id - $b->term_id : $oa - $ob;
			} );

			foreach ( array_slice( $terms, 0, max( 1, (int) $limit ) ) as $term ) {
				$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				$img      = $thumb_id ? wp_get_attachment_image( $thumb_id, 'medium', false, array( 'alt' => '', 'loading' => 'lazy' ) ) : '';
				if ( ! $img && function_exists( 'wc_placeholder_img_src' ) ) {
					$img = '<img src="' . esc_url( wc_placeholder_img_src() ) . '" alt="" loading="lazy">';
				}
				$cards[] = array(
					'label' => $term->name,
					'link'  => get_term_link( $term ),
					'img'   => $img,
				);
			}
		}
	}

	return apply_filters( 'hc_categories', $cards );
}

add_shortcode( 'houssaini_categories', function ( $atts ) {
	$atts  = shortcode_atts( array( 'limit' => 6, 'hide_empty' => 'no' ), $atts, 'houssaini_categories' );
	$cards = hc_get_categories( (int) $atts['limit'], 'yes' === $atts['hide_empty'] );

	if ( ! $cards ) {
		return current_user_can( 'manage_options' )
			? '<p style="padding:20px;text-align:center">' . esc_html__( 'No product categories yet. Add some in Products > Categories.', 'houssaini-core' ) . '</p>'
			: '';
	}

	ob_start();
	?>
	<style id="hc-cats-css">
	.hc-cats{ padding:22px clamp(16px,2.4vw,42px) 6px; font-family:var(--hc-body,'Inter',sans-serif); }
	.hc-cats-grid{ max-width:1680px; margin:0 auto; display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:15px; }
	.hc-cat{ position:relative; display:flex; align-items:center; gap:14px; min-height:98px; padding:12px 18px; background:#f3f3f5; border:1px solid transparent; border-radius:14px; text-decoration:none !important; color:#111114 !important; overflow:hidden; transition:transform .3s cubic-bezier(.2,.7,.2,1), box-shadow .3s, background .3s, border-color .3s; }
	.hc-cat::after{ content:""; position:absolute; inset-inline:0; bottom:0; height:3px; background:#e4161c; transform:scaleX(0); transform-origin:left; transition:transform .35s; }
	html[dir="rtl"] .hc-cat::after{ transform-origin:right; }
	.hc-cat:hover{ transform:translateY(-5px); background:#fff; border-color:#e6e6ea; box-shadow:0 16px 32px rgba(0,0,0,.1); }
	.hc-cat:hover::after{ transform:scaleX(1); }
	.hc-cat-img{ flex:0 0 84px; height:84px; display:grid; place-items:center; }
	.hc-cat-img img{ max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; filter:drop-shadow(0 8px 10px rgba(0,0,0,.25)); transition:transform .4s cubic-bezier(.2,.7,.2,1); }
	.hc-cat:hover .hc-cat-img img{ transform:scale(1.12) rotate(-4deg); }
	.hc-cat-txt{ display:flex; flex-direction:column; gap:6px; min-width:0; }
	.hc-cat-txt strong{ font:700 15px/1.2 var(--hc-body,'Inter',sans-serif); }
	.hc-cat-txt small{ display:flex; align-items:center; gap:6px; font:500 12.5px var(--hc-body,'Inter',sans-serif); color:#e4161c; }
	.hc-cat-txt svg{ width:13px; height:13px; transition:transform .25s; }
	.hc-cat:hover .hc-cat-txt svg{ transform:translateX(4px); }
	html[dir="rtl"] .hc-cat:hover .hc-cat-txt svg{ transform:scaleX(-1) translateX(4px); }
	@media (max-width:1250px){ .hc-cats-grid{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
	@media (max-width:640px){
		.hc-cats-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
		.hc-cat{ flex-direction:column; text-align:center; padding:14px 10px; min-height:0; gap:8px; }
		.hc-cat-img{ flex-basis:70px; height:70px; }
		.hc-cat-txt{ align-items:center; }
	}
	</style>
	<section class="hc-cats">
		<div class="hc-cats-grid">
			<?php foreach ( $cards as $card ) : ?>
				<a class="hc-cat" href="<?php echo esc_url( $card['link'] ); ?>">
					<span class="hc-cat-img"><?php echo $card['img']; // phpcs:ignore WordPress.Security.EscapeOutput -- built with wp_get_attachment_image() ?></span>
					<span class="hc-cat-txt">
						<strong><?php echo esc_html( $card['label'] ); ?></strong>
						<small><?php esc_html_e( 'Shop Now', 'houssaini-core' ); ?> <?php echo hc_icon( 'arrow' ); ?></small>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );