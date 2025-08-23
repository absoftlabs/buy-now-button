<?php
/**
 * Plugin Name: Buy Now Button
 * Description: Adds a “Buy Now” button to product cards and single product pages for direct checkout with AJAX. Includes a settings page to customize colors (normal/hover/active), animation (hover or always), border radius, and button text.
 * Version: 1.4.0
 * Author: absoftlab
 * License: MIT
 * Text Domain: buy-now-button-customizable
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ABB_BN_VERSION', '1.4.0' );
define( 'ABB_BN_FILE', __FILE__ );
define( 'ABB_BN_URL', plugin_dir_url( __FILE__ ) );
define( 'ABB_BN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Admin notice if WooCommerce not active
 */
add_action('admin_notices', function(){
    if ( current_user_can('activate_plugins') && ! class_exists('WooCommerce') ) {
        echo '<div class="notice notice-error"><p><strong>Buy Now Button for WooCommerce:</strong> WooCommerce is not active. Please install/activate WooCommerce.</p></div>';
    }
});

/**
 * Default settings
 */
function abb_bn_default_settings() {
	return array(
		'color_normal'     => '#1e40af', // indigo-800
		'color_hover'      => '#1d4ed8', // indigo-600
		'color_active'     => '#1e3a8a', // indigo-900
		'radius'           => 8,
		'animation'        => 'none',   // none|pulse|bounce|wiggle|shake|glow|ripple
		'animation_state'  => 'hover',  // hover|normal
		'button_text'      => 'Buy Now',
		'text_align'       => 'center', // left|center|right
	);
}

/**
 * Helper: Get settings merged with defaults
 */
function abb_bn_get_settings() {
	$defaults = abb_bn_default_settings();
	$saved    = get_option( 'abb_bn_settings', array() );
	if ( ! is_array( $saved ) ) $saved = array();
	return wp_parse_args( $saved, $defaults );
}

/**
 * Sanitize settings
 */
function abb_bn_sanitize_settings( $input ) {
	$defaults = abb_bn_default_settings();
	$out = array();

	// Colors: hex
	foreach ( array( 'color_normal', 'color_hover', 'color_active' ) as $key ) {
		if ( isset( $input[ $key ] ) && preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $input[ $key ] ) ) {
			$out[ $key ] = $input[ $key ];
		} else {
			$out[ $key ] = $defaults[ $key ];
		}
	}

	// Radius: 0-64
	$radius = isset( $input['radius'] ) ? intval( $input['radius'] ) : $defaults['radius'];
	if ( $radius < 0 ) $radius = 0;
	if ( $radius > 64 ) $radius = 64;
	$out['radius'] = $radius;

	// Animation: whitelist
	$allowed_anims = array( 'none','pulse','bounce','wiggle','shake','glow','ripple' );
	$anim = isset( $input['animation'] ) ? sanitize_text_field( $input['animation'] ) : $defaults['animation'];
	$out['animation'] = in_array( $anim, $allowed_anims, true ) ? $anim : 'none';

	// Animation state
	$state = isset( $input['animation_state'] ) ? sanitize_text_field( $input['animation_state'] ) : $defaults['animation_state'];
	$out['animation_state'] = in_array( $state, array('hover','normal'), true ) ? $state : 'hover';

	// Button text
	$btn_text = isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : $defaults['button_text'];
	if ( $btn_text === '' ) $btn_text = $defaults['button_text'];
	$out['button_text'] = $btn_text;

	// Text align
	$align = isset( $input['text_align'] ) ? sanitize_text_field( $input['text_align'] ) : $defaults['text_align'];
	$out['text_align'] = in_array( $align, array('left','center','right'), true ) ? $align : 'center';

	return $out;
}

/**
 * Register settings + admin menu
 */
add_action( 'admin_init', function() {
	register_setting(
		'abb_bn_settings_group',
		'abb_bn_settings',
		array(
			'type' => 'array',
			'sanitize_callback' => 'abb_bn_sanitize_settings',
			'default' => abb_bn_default_settings(),
		)
	);
} );

add_action( 'admin_menu', function() {
	$parent_slug = 'woocommerce';
	if ( ! class_exists( 'WooCommerce' ) ) {
		// Fallback to Settings if WooCommerce menu is missing
		$parent_slug = 'options-general.php';
	}

	add_submenu_page(
		$parent_slug,
		__( 'Buy Now Button', 'buy-now-button-customizable' ),
		__( 'Buy Now Button', 'buy-now-button-customizable' ),
		'manage_options',
		'buy-now-button',
		'abb_bn_render_settings_page'
	);
} );

/**
 * Admin assets (color picker + tiny CSS)
 */
add_action( 'admin_enqueue_scripts', function( $hook ){
	if ( $hook === 'woocommerce_page_buy-now-button' || $hook === 'settings_page_buy-now-button' ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'abb-bn-admin', ABB_BN_URL . 'assets/js/admin.js', array('jquery','wp-color-picker'), ABB_BN_VERSION, true );
		wp_enqueue_style( 'abb-bn-admin-css', ABB_BN_URL . 'assets/css/admin.css', array(), ABB_BN_VERSION );
	}
} );

function abb_bn_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$opts = abb_bn_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Buy Now Button – Settings', 'buy-now-button-customizable' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'abb_bn_settings_group' ); ?>
			<table class="form-table abb-bn-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button Text', 'buy-now-button-customizable' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="abb_bn_settings[button_text]" value="<?php echo esc_attr( $opts['button_text'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Text shown on the Buy Now button', 'buy-now-button-customizable' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button Text Align', 'buy-now-button-customizable' ); ?></th>
						<td>
							<select name="abb_bn_settings[text_align]">
								<?php
								$aligns = array(
									'left'   => __( 'Left', 'buy-now-button-customizable' ),
									'center' => __( 'Center (default)', 'buy-now-button-customizable' ),
									'right'  => __( 'Right', 'buy-now-button-customizable' ),
								);
								foreach ( $aligns as $slug => $label ) {
									printf(
										'<option value="%1$s" %3$s>%2$s</option>',
										esc_attr( $slug ),
										esc_html( $label ),
										selected( $opts['text_align'], $slug, false )
									);
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button Color (Normal)', 'buy-now-button-customizable' ); ?></th>
						<td class="abb-color-row">
							<input type="text" class="abb-color-field" name="abb_bn_settings[color_normal]" value="<?php echo esc_attr( $opts['color_normal'] ); ?>" data-default-color="#1e40af" />
							<span class="abb-hex-hint">#RRGGBB</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button Color (Hover)', 'buy-now-button-customizable' ); ?></th>
						<td class="abb-color-row">
							<input type="text" class="abb-color-field" name="abb_bn_settings[color_hover]" value="<?php echo esc_attr( $opts['color_hover'] ); ?>" data-default-color="#1d4ed8" />
							<span class="abb-hex-hint">#RRGGBB</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button Color (Active)', 'buy-now-button-customizable' ); ?></th>
						<td class="abb-color-row">
							<input type="text" class="abb-color-field" name="abb_bn_settings[color_active]" value="<?php echo esc_attr( $opts['color_active'] ); ?>" data-default-color="#1e3a8a" />
							<span class="abb-hex-hint">#RRGGBB</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Border Radius (px)', 'buy-now-button-customizable' ); ?></th>
						<td>
							<input type="number" min="0" max="64" name="abb_bn_settings[radius]" value="<?php echo esc_attr( $opts['radius'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button Animation', 'buy-now-button-customizable' ); ?></th>
						<td>
							<select name="abb_bn_settings[animation]">
								<?php
								$animations = array(
									'none'   => __( 'None', 'buy-now-button-customizable' ),
									'pulse'  => __( 'Pulse', 'buy-now-button-customizable' ),
									'bounce' => __( 'Bounce', 'buy-now-button-customizable' ),
									'wiggle' => __( 'Wiggle', 'buy-now-button-customizable' ),
									'shake'  => __( 'Shake', 'buy-now-button-customizable' ),
									'glow'   => __( 'Glow', 'buy-now-button-customizable' ),
									'ripple' => __( 'Ripple (on click)', 'buy-now-button-customizable' ),
								);
								foreach ( $animations as $slug => $label ) {
									printf(
										'<option value="%1$s" %3$s>%2$s</option>',
										esc_attr( $slug ),
										esc_html( $label ),
										selected( $opts['animation'], $slug, false )
									);
								}
								?>
							</select>
							&nbsp;
							<select name="abb_bn_settings[animation_state]">
								<?php
								$states = array(
									'hover'  => __( 'Apply on hover', 'buy-now-button-customizable' ),
									'normal' => __( 'Apply always (normal)', 'buy-now-button-customizable' ),
								);
								foreach ( $states as $slug => $label ) {
									printf(
										'<option value="%1$s" %3$s>%2$s</option>',
										esc_attr( $slug ),
										esc_html( $label ),
										selected( $opts['animation_state'], $slug, false )
									);
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Choose animation and when it should run.', 'buy-now-button-customizable' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save Changes', 'buy-now-button-customizable' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Enqueue frontend assets + dynamic styles
 */
add_action( 'wp_enqueue_scripts', function() {
	// Base CSS for animations + minimal button styling
	wp_enqueue_style(
		'abb-bn-css',
		ABB_BN_URL . 'assets/css/buy-now.css',
		array(),
		ABB_BN_VERSION
	);

	// Frontend behavior
	wp_enqueue_script(
		'abb-bn-js',
		ABB_BN_URL . 'assets/js/buy-now.js',
		array( 'jquery' ),
		ABB_BN_VERSION,
		true
	);

	$opts    = abb_bn_get_settings();

	wp_localize_script( 'abb-bn-js', 'abbBnVars', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'abb_bn_nonce' ),
		'anim'     => $opts['animation'],
	) );

	// Inject dynamic CSS from settings
	$normal  = $opts['color_normal'];
	$hover   = $opts['color_hover'];
	$active  = $opts['color_active'];
	$radius  = intval( $opts['radius'] );

	// Map text_align to flex justify-content
	$align   = in_array( $opts['text_align'], array('left','center','right'), true ) ? $opts['text_align'] : 'center';
	$justify = ($align === 'left') ? 'flex-start' : ( ($align === 'right') ? 'flex-end' : 'center' );

	$selectors = '.buy-now-button, .buy-now-button-single';
	$dynamic = "
	{$selectors}{
		background: {$normal} !important;
		border-radius: {$radius}px !important;
		color: #fff !important;
		border: 0 !important;
		padding: 10px 16px !important;
		cursor: pointer !important;
		transition: all .2s ease-in-out !important;
		display: inline-flex !important;
		align-items: center !important;
		justify-content: {$justify} !important;
		text-align: {$align} !important;
		gap: 8px !important;
		text-decoration: none !important;
	}
	{$selectors}:hover{ background: {$hover} !important; outline: none !important; }
	{$selectors}:active, {$selectors}.is-active{ background: {$active} !important; }
	{$selectors}:focus-visible{ outline: 2px solid rgba(255,255,255,.6) !important; outline-offset: 2px !important; }
	";
	wp_add_inline_style( 'abb-bn-css', $dynamic );
} );

/**
 * Output Buy Now button on single product pages
 */
add_action( 'woocommerce_after_add_to_cart_button', function() {
	global $product;
	if ( ! $product ) return;

	$opts = abb_bn_get_settings();
	$classes = '';
	if ( $opts['animation'] !== 'none' ) {
		$classes .= ' bn-anim-' . esc_attr( $opts['animation'] );
		if ( $opts['animation_state'] === 'normal' ) {
			$classes .= ' bn-anim-normal';
		}
	}

	printf(
		'<button class="button alt buy-now-button-single%1$s" data-product_id="%2$d" type="button" style="margin-left:8px;">%3$s</button>',
		$classes,
		absint( $product->get_id() ),
		esc_html( $opts['button_text'] )
	);
}, 20 );

/**
 * Output Buy Now button on archive (simple products only)
 */
add_action( 'woocommerce_after_shop_loop_item', function() {
	global $product;
	if ( ! $product || ! $product->is_type( 'simple' ) ) return;

	$opts = abb_bn_get_settings();
	$classes = '';
	if ( $opts['animation'] !== 'none' ) {
		$classes .= ' bn-anim-' . esc_attr( $opts['animation'] );
		if ( $opts['animation_state'] === 'normal' ) {
			$classes .= ' bn-anim-normal';
		}
	}

	printf(
		'<a href="#" class="button buy-now-button%1$s" data-product_id="%2$d" style="margin-top:8px;">%3$s</a>',
		$classes,
		absint( $product->get_id() ),
		esc_html( $opts['button_text'] )
	);
}, 15 );

/**
 * AJAX: Add to cart then return checkout URL
 */
function abb_bn_ajax_buy_now() {
	check_ajax_referer( 'abb_bn_nonce', 'nonce' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( array( 'message' => 'WooCommerce not active' ), 400 );
	}

	$product_id  = isset($_POST['product_id']) ? absint( $_POST['product_id'] ) : 0;
	$qty         = isset($_POST['quantity']) ? max( 1, absint( $_POST['quantity'] ) ) : 1;
	$variation_id = isset($_POST['variation_id']) ? absint( $_POST['variation_id'] ) : 0;

	$attributes = array();
	if ( isset($_POST['attributes']) && is_array($_POST['attributes']) ) {
		foreach ( $_POST['attributes'] as $key => $val ) {
			$attributes[ wc_clean( $key ) ] = wc_clean( wp_unslash( $val ) );
		}
	}

	if ( $product_id <= 0 ) {
		wp_send_json_error( array( 'message' => 'Invalid product' ), 400 );
	}

	if ( function_exists('WC') && WC()->cart ) {
		// Optional: WC()->cart->empty_cart(); // uncomment to replace cart contents
		$item_key = WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $attributes );
		if ( ! $item_key ) {
			wp_send_json_error( array( 'message' => 'Could not add to cart' ), 400 );
		}
		wp_send_json_success( array( 'redirect' => wc_get_checkout_url() ) );
	}

	wp_send_json_error( array( 'message' => 'Cart unavailable' ), 500 );
}
add_action( 'wp_ajax_abb_bn_buy_now', 'abb_bn_ajax_buy_now' );
add_action( 'wp_ajax_nopriv_abb_bn_buy_now', 'abb_bn_ajax_buy_now' );

/**
 * Fallback (non-JS): Handle direct add-to-cart via query param, then redirect to checkout
 */
add_action( 'template_redirect', function() {
	if ( ! isset( $_GET['buy_now_add'] ) ) return;
	if ( ! class_exists( 'WooCommerce' ) ) return;

	$product_id  = absint( $_GET['buy_now_add'] );
	if ( $product_id <= 0 ) return;

	$qty = isset( $_GET['quantity'] ) ? max( 1, absint( $_GET['quantity'] ) ) : 1;
	$variation_id = isset( $_GET['variation_id'] ) ? absint( $_GET['variation_id'] ) : 0;

	// Gather variation attributes from query string
	$variations = array();
	foreach ( $_GET as $key => $val ) {
		if ( strpos( $key, 'attribute_' ) === 0 ) {
			$variations[ wc_clean( $key ) ] = wc_clean( wp_unslash( $val ) );
		}
	}

	if ( function_exists('WC') && WC()->cart ) {
		WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variations );
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
} );
