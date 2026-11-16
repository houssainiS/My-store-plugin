<?php
/**
 * Replaces WooCommerce's product card in every product list
 * (shop, categories, search, related products) with the Houssaini card.
 * Loaded through the wc_get_template_part filter in shop.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'hc-li', $product ); ?>>
	<?php echo hc_render_product_card( $product ); // phpcs:ignore ?>
</li>