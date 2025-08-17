<?php
/**
 * Plugin Name: Buy Now Button
 * Description: Adds a "Buy Now" button after Add to Cart. Hidden on variable product archives, shown on all single product pages.
 * Version: 1.0.1
 * Author: absoftlab
 * Author URI: https://absoftlab.com
 * Plugin URI: https://absoftlab.com/portfolio/
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue custom script for Buy Now button
 */
add_action( 'wp_enqueue_scripts', function() {
    wp_register_script( 'buy-now-script', plugins_url( 'buy-now.js', __FILE__ ), array('jquery'), '1.0', true );
    wp_localize_script( 'buy-now-script', 'buyNowData', array(
        'checkout_url' => wc_get_checkout_url(),
    ));
    wp_enqueue_script( 'buy-now-script' );
});

/**
 * Add Buy Now button on product archive
 */
add_action( 'woocommerce_after_shop_loop_item', function() {
    global $product;

    // Skip variable products on archive
    if ( $product->is_type('variable') ) {
        return;
    }

    echo '<button 
        class="button buy-now-button" 
        data-product_id="' . esc_attr( $product->get_id() ) . '">
        Buy Now
    </button>';
}, 20 );

/**
 * Add Buy Now button on single product page (for all product types)
 */
add_action( 'woocommerce_after_add_to_cart_button', function() {
    global $product;

    echo '<button 
        class="button buy-now-button-single" 
        data-product_id="' . esc_attr( $product->get_id() ) . '">
        Buy Now
    </button>';
});

/**
 * Handle Buy Now add-to-cart + redirect
 */
add_action( 'template_redirect', function() {
    if ( isset($_GET['buy_now_add']) ) {
        $product_id   = intval($_GET['buy_now_add']);
        $quantity     = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

        if ( $product_id > 0 ) {
            WC()->cart->add_to_cart( $product_id, $quantity );
            wp_safe_redirect( wc_get_checkout_url() );
            exit;
        }
    }
});
