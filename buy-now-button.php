<?php
    /**
     * Plugin Name: Woo Buy Now Button
     * Description: Adds a “Buy Now” button to product cards and single product pages for direct checkout with AJAX. Includes a settings page to customize colors (normal/hover/active), animation (hover or always), border radius, button text, and text alignment.
     * Version: 1.5.7
     * Author: absoftlab
     * Author URI: https://absoftlab.com
     * License: MIT
     * Update URI: https://absoftlab.com/woo-buy-now-button
     * Text Domain: ab-buy-now-button-customizable
     */

    if (! defined('ABSPATH')) {
        exit;
    }

    define('ABB_BN_VERSION', '1.5.7');
    define('ABB_BN_FILE', __FILE__);
    define('ABB_BN_URL', plugin_dir_url(__FILE__));
    define('ABB_BN_PATH', plugin_dir_path(__FILE__));

    /** WooCommerce check notice */
    add_action('admin_notices', function () {
        if (current_user_can('activate_plugins') && ! class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p><strong>Buy Now Button for WooCommerce:</strong> WooCommerce is not active. Please install/activate WooCommerce.</p></div>';
        }
    });

    /** Defaults */
    function abb_bn_default_settings()
    {
        return [
            'color_normal'    => '#1e40af',
            'color_hover'     => '#1d4ed8',
            'color_active'    => '#1e3a8a',
            'text_color'      => '#ffffff', // NEW
            'radius'          => 8,
            'animation'       => 'none',  // none|pulse|bounce|wiggle|shake|glow|ripple
            'animation_state' => 'hover', // hover|normal
            'button_text'     => 'Buy Now',
            'text_align'      => 'center', // left|center|right
        ];
    }

    /** Get settings */
    function abb_bn_get_settings()
    {
        $defaults = abb_bn_default_settings();
        $saved    = get_option('abb_bn_settings', []);
        if (! is_array($saved)) {
            $saved = [];
        }
        return wp_parse_args($saved, $defaults);
    }

    /** Sanitize */
    function abb_bn_sanitize_settings($input)
    {
        $defaults = abb_bn_default_settings();
        $out      = [];

                                                                                          // Colors (accept #RGB, #RRGGBB, #RRGGBBAA, rgb/rgba)
        foreach (['color_normal', 'color_hover', 'color_active', 'text_color'] as $key) { // include text_color
            $val = isset($input[$key]) ? trim($input[$key]) : '';
            if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $val)
                || preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(0|0?\.\d+|1))?\s*\)$/', $val)) {
                $out[$key] = $val;
            } else {
                $out[$key] = $defaults[$key];
            }
        }

        $radius        = isset($input['radius']) ? intval($input['radius']) : $defaults['radius'];
        $out['radius'] = min(64, max(0, $radius));

        $allowed_anims    = ['none', 'pulse', 'bounce', 'wiggle', 'shake', 'glow', 'ripple'];
        $anim             = isset($input['animation']) ? sanitize_text_field($input['animation']) : $defaults['animation'];
        $out['animation'] = in_array($anim, $allowed_anims, true) ? $anim : 'none';

        $state                  = isset($input['animation_state']) ? sanitize_text_field($input['animation_state']) : $defaults['animation_state'];
        $out['animation_state'] = in_array($state, ['hover', 'normal'], true) ? $state : 'hover';

        $txt                = isset($input['button_text']) ? sanitize_text_field($input['button_text']) : $defaults['button_text'];
        $out['button_text'] = ($txt === '') ? $defaults['button_text'] : $txt;

        $align             = isset($input['text_align']) ? sanitize_text_field($input['text_align']) : $defaults['text_align'];
        $out['text_align'] = in_array($align, ['left', 'center', 'right'], true) ? $align : 'center';

        return $out;
    }

    /** Settings & menu */
    add_action('admin_init', function () {
        register_setting('abb_bn_settings_group', 'abb_bn_settings', [
            'type'              => 'array',
            'sanitize_callback' => 'abb_bn_sanitize_settings',
            'default'           => abb_bn_default_settings(),
        ]);
    });

    add_action('admin_menu', function () {
        $parent_slug = class_exists('WooCommerce') ? 'woocommerce' : 'options-general.php';
        add_submenu_page(
            $parent_slug,
            __('Buy Now Button', 'buy-now-button-customizable'),
            __('Buy Now Button', 'buy-now-button-customizable'),
            'manage_options',
            'buy-now-button',
            'abb_bn_render_settings_page'
        );
    });

    /** Admin assets */
    add_action('admin_enqueue_scripts', function ($hook) {
        if ($hook === 'woocommerce_page_buy-now-button' || $hook === 'settings_page_buy-now-button') {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('abb-bn-admin', ABB_BN_URL . 'assets/js/admin.js', ['jquery', 'wp-color-picker'], ABB_BN_VERSION, true);
            wp_enqueue_style('abb-bn-admin-css', ABB_BN_URL . 'assets/css/admin.css', [], ABB_BN_VERSION);
        }
    });

    /** Settings UI */
    function abb_bn_render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $opts = abb_bn_get_settings();

        $logo_url = '';
        if (file_exists(ABB_BN_PATH . 'assets/img/logo.svg')) {
            $logo_url = ABB_BN_URL . 'assets/img/logo.svg';
        } elseif (file_exists(ABB_BN_PATH . 'assets/img/logo.png')) {
            $logo_url = ABB_BN_URL . 'assets/img/logo.png';
        }
    ?>
	<div class="wrap abb-bn-wrap">
		<div class="abb-bn-head">
			<?php if ($logo_url): ?>
				<img class="abb-bn-logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Brand logo', 'buy-now-button-customizable'); ?>" />
			<?php endif; ?>
			
			<span class="abb-version-badge">-v<?php echo esc_html(ABB_BN_VERSION); ?></span>
		</div>

		<form method="post" action="options.php" class="abb-bn-form">
			<?php settings_fields('abb_bn_settings_group'); ?>
			<div class="abb-bn-layout">
				<div class="abb-bn-panel">
					<div class="abb-bn-card">
						<h2 class="abb-bn-card-title"><?php esc_html_e('Button Text', 'buy-now-button-customizable'); ?></h2>
						<label class="abb-bn-field">
							<span class="abb-bn-label"><?php esc_html_e('Text', 'buy-now-button-customizable'); ?></span>
							<input type="text" class="regular-text abb-bn-input" name="abb_bn_settings[button_text]" id="abb-bn-button-text" value="<?php echo esc_attr($opts['button_text']); ?>" />
						</label>
						<label class="abb-bn-field">
							<span class="abb-bn-label"><?php esc_html_e('Text Align', 'buy-now-button-customizable'); ?></span>
							<select name="abb_bn_settings[text_align]" id="abb-bn-text-align" class="abb-bn-select">
								<option value="left"								                       <?php selected($opts['text_align'], 'left'); ?>><?php esc_html_e('Left', 'buy-now-button-customizable'); ?></option>
								<option value="center"								                       <?php selected($opts['text_align'], 'center'); ?>><?php esc_html_e('Center', 'buy-now-button-customizable'); ?></option>
								<option value="right"								                       <?php selected($opts['text_align'], 'right'); ?>><?php esc_html_e('Right', 'buy-now-button-customizable'); ?></option>
							</select>
						</label>
					</div>

					<div class="abb-bn-card">
						<h2 class="abb-bn-card-title"><?php esc_html_e('Button Color', 'buy-now-button-customizable'); ?></h2>
						<div class="abb-bn-grid2">
							<label class="abb-bn-field">
								<span class="abb-bn-label"><?php esc_html_e('Normal', 'buy-now-button-customizable'); ?></span>
								<input type="text" class="abb-color-field abb-bn-input" name="abb_bn_settings[color_normal]" id="abb-bn-color-normal" value="<?php echo esc_attr($opts['color_normal']); ?>" data-default-color="#1e40af" />
								<span class="abb-hex-hint">#RRGGBB / rgba()</span>
							</label>
							<label class="abb-bn-field">
								<span class="abb-bn-label"><?php esc_html_e('Hover', 'buy-now-button-customizable'); ?></span>
								<input type="text" class="abb-color-field abb-bn-input" name="abb_bn_settings[color_hover]" id="abb-bn-color-hover" value="<?php echo esc_attr($opts['color_hover']); ?>" data-default-color="#1d4ed8" />
								<span class="abb-hex-hint">#RRGGBB / rgba()</span>
							</label>
							<label class="abb-bn-field">
								<span class="abb-bn-label"><?php esc_html_e('Active', 'buy-now-button-customizable'); ?></span>
								<input type="text" class="abb-color-field abb-bn-input" name="abb_bn_settings[color_active]" id="abb-bn-color-active" value="<?php echo esc_attr($opts['color_active']); ?>" data-default-color="#1e3a8a" />
								<span class="abb-hex-hint">#RRGGBB / rgba()</span>
							</label>


              <!-- NEW: Text color -->
              <label class="abb-bn-field" style="grid-column: 1 / -1;">
                <span class="abb-bn-label"><?php esc_html_e('Text Color', 'buy-now-button-customizable'); ?></span>
                <input type="text" class="abb-color-field abb-bn-input" name="abb_bn_settings[text_color]" id="abb-bn-text-color" value="<?php echo esc_attr($opts['text_color']); ?>" data-default-color="#ffffff" />
                <span class="abb-hex-hint">#RRGGBB / rgba()</span>
              </label>

			  <label class="abb-bn-field">
								<span class="abb-bn-label"><?php esc_html_e('Border Radius (px)', 'buy-now-button-customizable'); ?></span>
								<input type="number" min="0" max="64" class="abb-bn-input" name="abb_bn_settings[radius]" id="abb-bn-radius" value="<?php echo esc_attr($opts['radius']); ?>" />
							</label>
						</div>
					</div>

					<div class="abb-bn-card">
						<h2 class="abb-bn-card-title"><?php esc_html_e('Button Animation', 'buy-now-button-customizable'); ?></h2>
						<div class="abb-bn-grid2">
							<label class="abb-bn-field">
								<span class="abb-bn-label"><?php esc_html_e('Type', 'buy-now-button-customizable'); ?></span>
								<select name="abb_bn_settings[animation]" id="abb-bn-animation" class="abb-bn-select">
									<?php
                                        $animations = [
                                                'none'   => __('None', 'buy-now-button-customizable'),
                                                'pulse'  => __('Pulse', 'buy-now-button-customizable'),
                                                'bounce' => __('Bounce', 'buy-now-button-customizable'),
                                                'wiggle' => __('Wiggle', 'buy-now-button-customizable'),
                                                'shake'  => __('Shake', 'buy-now-button-customizable'),
                                                'glow'   => __('Glow', 'buy-now-button-customizable'),
                                                'ripple' => __('Ripple (on click)', 'buy-now-button-customizable'),
                                            ];
                                            foreach ($animations as $slug => $label) {
                                                printf('<option value="%1$s" %3$s>%2$s</option>',
                                                    esc_attr($slug), esc_html($label), selected($opts['animation'], $slug, false));
                                            }
                                        ?>
								</select>
							</label>
							<label class="abb-bn-field">
								<span class="abb-bn-label"><?php esc_html_e('Trigger', 'buy-now-button-customizable'); ?></span>
								<select name="abb_bn_settings[animation_state]" id="abb-bn-animation-state" class="abb-bn-select">
									<option value="hover"									                       <?php selected($opts['animation_state'], 'hover'); ?>><?php esc_html_e('Apply on Hover', 'buy-now-button-customizable'); ?></option>
									<option value="normal"									                       <?php selected($opts['animation_state'], 'normal'); ?>><?php esc_html_e('Apply Always (Normal)', 'buy-now-button-customizable'); ?></option>
								</select>
							</label>
						</div>
					</div>

					<div class="abb-bn-actions">
						<?php submit_button(__('Save Changes', 'buy-now-button-customizable'), 'primary', 'submit', false); ?>
					</div>
				</div>

				<!-- Right: Preview -->
				<div class="abb-bn-preview">
					<div class="abb-bn-card">
						<h2 class="abb-bn-card-title"><?php esc_html_e('Preview', 'buy-now-button-customizable'); ?></h2>
						<div class="abb-bn-preview-toolbar">
							<label><input type="radio" name="abb-bn-state" value="normal" checked>							                                                                       <?php esc_html_e('Normal', 'buy-now-button-customizable'); ?></label>
							<label><input type="radio" name="abb-bn-state" value="hover">							                                                              <?php esc_html_e('Hover', 'buy-now-button-customizable'); ?></label>
							<label><input type="radio" name="abb-bn-state" value="active">							                                                               <?php esc_html_e('Active', 'buy-now-button-customizable'); ?></label>
						</div>
						<div class="abb-bn-preview-stage">
							<a href="#" id="abb-bn-demo" class="button buy-now-button"><?php echo esc_html($opts['button_text']); ?></a>
						</div>
						<p class="description"><?php esc_html_e('Simulates color states, text color, and animations; frontend reflects your saved settings.', 'buy-now-button-customizable'); ?></p>
					</div>

          <!-- Your extra card left as-is -->
					<div class="abb-extra">
						 <article class="card" aria-labelledby="title">
    <div class="card-inner">
      <div class="logo" role="img" aria-label="Brand logo">
        <img height="100%" width="100%" src="<?php echo esc_url(plugins_url('assets/img/profile.png', __FILE__)); ?>" alt="Profile">
      </div>
      <div>
        <h1 id="title">absoftlab - Is a Web Development Agency</h1>
        <p class="desc">
          If you need a super fast ecommerce website or something like that, we are here to help you. We also offer a wide range of other web development services. like custom plugin development, theme customization, and performance optimization.
        </p>
        <div class="meta" aria-label="Contact methods">
          <a class="chip" href="mailto:example@gmail.com" aria-label="Email example at gmail dot com">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5v-9Z" stroke="currentColor" stroke-width="1.6" />
              <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" />
            </svg>
            absoftlab@gmail.com
          </a>
          <a class="chip" href="https://wa.me/+8801762675353" target="_blank" rel="noopener" aria-label="WhatsApp number 01762675353">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M20.5 11.5A8.5 8.5 0 1 1 6.8 19.8L3.5 21l1.2-3.3A8.5 8.5 0 0 1 20.5 11.5Z" stroke="currentColor" stroke-width="1.6"/>
              <path d="M8.5 8.8c0 4.1 3.4 7.5 7.5 7.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            01762675353
          </a>
        </div>
      </div>
    </div>
    <footer class="footer">
      <a class="btn" href="mailto:absoftlab@gmail.com">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M4 12h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
          <path d="m14 6 6 6-6 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Email Me
      </a>
      <a class="btn" href="tel:+8801762675353">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M6.5 4A2.5 2.5 0 0 1 9 6.5v1A2.5 2.5 0 0 1 6.5 10 10.5 10.5 0 0 0 17 20.5 2.5 2.5 0 0 1 19.5 18h1A2.5 2.5 0 0 1 23 20.5v.3A3.2 3.2 0 0 1 20 24C10.6 24 2.9 16.3 2.9 6.9A3.2 3.2 0 0 1 6.2 3h.3z" stroke="currentColor" stroke-width="1.4"/>
        </svg>
        Call / WhatsApp
      </a>
    </footer>
  </article>
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
        }

        /** Build dynamic CSS with very specific selectors (archive + single) incl. text color */
        function abb_bn_build_dynamic_css()
        {
            $opts = abb_bn_get_settings();

            // Validate colors (allow hex3/6/8 and rgba())
            $def = abb_bn_default_settings();
            foreach (['color_normal', 'color_hover', 'color_active', 'text_color'] as $k) {
                $v = trim((string) $opts[$k]);
                if (! preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $v)
                    && ! preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(0|0?\.\d+|1))?\s*\)$/', $v)) {
                    $opts[$k] = $def[$k];
                }
            }

            $normal     = $opts['color_normal'];
            $hover      = $opts['color_hover'];
            $active     = $opts['color_active'];
            $text_color = $opts['text_color'];
            $radius     = intval($opts['radius']);

            $align   = in_array($opts['text_align'], ['left', 'center', 'right'], true) ? $opts['text_align'] : 'center';
            $justify = $align === 'left' ? 'flex-start' : ($align === 'right' ? 'flex-end' : 'center');

            $archive_selectors = [
                '.woocommerce ul.products li.product a.button.buy-now-button',
                '.woocommerce ul.products li.product .button.buy-now-button',
                '.woocommerce .products .product a.button.buy-now-button',
                'ul.products li.product a.button.buy-now-button',
                '.shop .products .product a.button.buy-now-button',
                '.woocommerce a.button.add_to_cart_button.buy-now-button',
                '.products .product a.add_to_cart_button.buy-now-button',
            ];

            $single_selectors = [
                'button.button.buy-now-button-single',
                '.single-product .summary .buy-now-button-single',
                '.buy-now-button-single',
            ];

            $fallback_selectors = [
                '.buy-now-button',
            ];

            $selectors = implode(',', array_merge($archive_selectors, $single_selectors, $fallback_selectors));

            return "
{$selectors}{
	background: {$normal} !important;
	background-color: {$normal} !important;
	border-color: {$normal} !important;
	color: {$text_color} !important;
	border-radius: {$radius}px !important;
	border: 1px solid {$normal} !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: {$justify} !important;
	text-align: {$align} !important;
	text-decoration: none !important;
	transition: all .2s ease-in-out !important;
	padding: 10px 16px !important;
	cursor: pointer !important;
}
{$selectors}:hover{
	background: {$hover} !important;
	background-color: {$hover} !important;
	border-color: {$hover} !important;
  color: {$text_color} !important;
}
{$selectors}:active,
{$selectors}.is-active{
	background: {$active} !important;
	background-color: {$active} !important;
	border-color: {$active} !important;
  color: {$text_color} !important;
}
";
        }

        /** Front-end assets + dynamic CSS */
        add_action('wp_enqueue_scripts', function () {
            // Ensure a style handle exists
            if (! wp_style_is('abb-bn-css', 'registered')) {
                $css_path = ABB_BN_PATH . 'assets/css/buy-now.css';
                if (file_exists($css_path)) {
                    wp_enqueue_style('abb-bn-css', ABB_BN_URL . 'assets/css/buy-now.css', [], ABB_BN_VERSION);
                } else {
                    wp_register_style('abb-bn-css', false, [], ABB_BN_VERSION);
                    wp_enqueue_style('abb-bn-css');
                }
            } else {
                wp_enqueue_style('abb-bn-css');
            }

            // JS
            wp_enqueue_script('abb-bn-js', ABB_BN_URL . 'assets/js/buy-now.js', ['jquery'], ABB_BN_VERSION, true);

            $opts = abb_bn_get_settings();
            wp_localize_script('abb-bn-js', 'abbBnVars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('abb_bn_nonce'),
                'anim'     => $opts['animation'],
            ]);

            // Inline CSS early (attached to handle)
            wp_add_inline_style('abb-bn-css', abb_bn_build_dynamic_css());
        });

        /** Also print the same CSS very late (head & footer) to beat aggressive themes */
        add_action('wp_head', function () {
            echo '<style id="abb-bn-inline-head-late">' . abb_bn_build_dynamic_css() . '</style>';
        }, 9999);
        add_action('wp_footer', function () {
            echo '<style id="abb-bn-inline-footer-late">' . abb_bn_build_dynamic_css() . '</style>';
        }, 99);

        /** Render buttons — inline style + data colors (JS fallback for hover/active) */
        function abb_bn_inline_button_style($opts)
        {
            $align   = in_array($opts['text_align'], ['left', 'center', 'right'], true) ? $opts['text_align'] : 'center';
            $justify = $align === 'left' ? 'flex-start' : ($align === 'right' ? 'flex-end' : 'center');

            $normal     = esc_attr($opts['color_normal']);
            $text_color = esc_attr($opts['text_color']);
            $radius     = intval($opts['radius']);

            return "background: {$normal} !important; background-color: {$normal} !important; border-color: {$normal} !important; color: {$text_color} !important; border-radius: {$radius}px !important; border:1px solid {$normal} !important; display:inline-flex !important; align-items:center !important; justify-content: {$justify} !important; text-align: {$align} !important; padding:10px 16px !important;";
        }

        /** Single product button */
        add_action('woocommerce_after_add_to_cart_button', function () {
            global $product;if (! $product) {return;}

            $opts = abb_bn_get_settings();

            $classes = '';
            if ($opts['animation'] !== 'none') {
                $classes .= ' bn-anim-' . esc_attr($opts['animation']);
                if ($opts['animation_state'] === 'normal') {
                    $classes .= ' bn-anim-normal';
                }
            }

            $style = abb_bn_inline_button_style($opts);
            printf(
                '<button class="button alt buy-now-button-single%1$s" data-product_id="%2$d" type="button" style="%3$s margin-left:8px;"
			data-color-normal="%4$s" data-color-hover="%5$s" data-color-active="%6$s" data-text-color="%7$s">%8$s</button>',
                $classes,
                absint($product->get_id()),
                esc_attr($style),
                esc_attr($opts['color_normal']),
                esc_attr($opts['color_hover']),
                esc_attr($opts['color_active']),
                esc_attr($opts['text_color']),
                esc_html($opts['button_text'])
            );
        }, 20);

        /** Archive button (simple products only) */
        add_action('woocommerce_after_shop_loop_item', function () {
            global $product;if (! $product || ! $product->is_type('simple')) {return;}

            $opts = abb_bn_get_settings();

            $classes = '';
            if ($opts['animation'] !== 'none') {
                $classes .= ' bn-anim-' . esc_attr($opts['animation']);
                if ($opts['animation_state'] === 'normal') {
                    $classes .= ' bn-anim-normal';
                }
            }

            $style = abb_bn_inline_button_style($opts);
            printf(
                '<a href="#" class="button buy-now-button%1$s" data-product_id="%2$d" style="%3$s margin-top:8px;"
			data-color-normal="%4$s" data-color-hover="%5$s" data-color-active="%6$s" data-text-color="%7$s">%8$s</a>',
                $classes,
                absint($product->get_id()),
                esc_attr($style),
                esc_attr($opts['color_normal']),
                esc_attr($opts['color_hover']),
                esc_attr($opts['color_active']),
                esc_attr($opts['text_color']),
                esc_html($opts['button_text'])
            );
        }, 15);

        /** AJAX handler */
        function abb_bn_ajax_buy_now()
        {
            check_ajax_referer('abb_bn_nonce', 'nonce');
            if (! class_exists('WooCommerce')) {
                wp_send_json_error(['message' => 'WooCommerce not active'], 400);
            }

            $product_id   = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
            $qty          = isset($_POST['quantity']) ? max(1, absint($_POST['quantity'])) : 1;
            $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;

            $attributes = [];
            if (isset($_POST['attributes']) && is_array($_POST['attributes'])) {
                foreach ($_POST['attributes'] as $k => $v) {
                    $attributes[wc_clean($k)] = wc_clean(wp_unslash($v));
                }
            }

            if ($product_id <= 0) {
                wp_send_json_error(['message' => 'Invalid product'], 400);
            }

            if (function_exists('WC') && WC()->cart) {
                // To replace cart contents instead of appending, uncomment:
                // WC()->cart->empty_cart();

                $item_key = WC()->cart->add_to_cart($product_id, $qty, $variation_id, $attributes);
                if (! $item_key) {
                    wp_send_json_error(['message' => 'Could not add to cart'], 400);
                }

                wp_send_json_success(['redirect' => wc_get_checkout_url()]);
            }
            wp_send_json_error(['message' => 'Cart unavailable'], 500);
        }
        add_action('wp_ajax_abb_bn_buy_now', 'abb_bn_ajax_buy_now');
        add_action('wp_ajax_nopriv_abb_bn_buy_now', 'abb_bn_ajax_buy_now');

        /** Non-JS fallback */
        add_action('template_redirect', function () {
            if (! isset($_GET['buy_now_add'])) {return;}
            if (! class_exists('WooCommerce')) {return;}

            $product_id = absint($_GET['buy_now_add']);
            if ($product_id <= 0) {return;}

            $qty          = isset($_GET['quantity']) ? max(1, absint($_GET['quantity'])) : 1;
            $variation_id = isset($_GET['variation_id']) ? absint($_GET['variation_id']) : 0;

            $variations = [];
            foreach ($_GET as $key => $val) {
                if (strpos($key, 'attribute_') === 0) {
                    $variations[wc_clean($key)] = wc_clean(wp_unslash($val));
                }
            }

            if (function_exists('WC') && WC()->cart) {
                WC()->cart->add_to_cart($product_id, $qty, $variation_id, $variations);
                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }
        });

        // Override the "View details" modal for this plugin only
        add_filter('plugins_api', function ($result, $action, $args) {
            if ($action !== 'plugin_information') {
                return $result;
            }

                                                          // WordPress passes ?plugin=<slug>; usually this is the plugin folder name.
            $my_slug  = basename(dirname(ABB_BN_FILE));   // e.g., 'woo-buy-now-button'
            $alt_slug = 'ab-buy-now-button-customizable'; // your Text Domain as a fallback

            if (empty($args->slug) || ($args->slug !== $my_slug && $args->slug !== $alt_slug)) {
                return $result; // not our plugin
            }

            $info = (object) [
                'name'         => 'Woo Buy Now Button',
                'slug'         => $my_slug,
                'version'      => ABB_BN_VERSION,
                'author'       => '<a href="https://absoftlab.com">absoftlab</a>',
                'homepage'     => 'https://absoftlab.com',
                'requires'     => '5.8',
                'requires_php' => '7.2',
                'tested'       => '6.6',
                'last_updated' => gmdate('Y-m-d'),
                'sections'     => [
                    'description'  => wpautop('A lightweight WooCommerce plugin that adds a **Buy Now** button to single products and archives with instant checkout redirect. Includes color, radius, animation, text, and alignment customization.'),
                    'installation' => wpautop("1. Upload the plugin folder to `wp-content/plugins/`.\n2. Activate in **Plugins**.\n3. Go to **WooCommerce → Buy Now Button** to configure."),
                    'changelog'    => wp_kses_post('
                <h4>1.5.2</h4>
                <ul>
                    <li>Fix: force custom colors on archives even when themes inject late CSS.</li>
                    <li>Improve: prints dynamic CSS in head & footer; JS reasserts hover/active.</li>
                </ul>
                <h4>1.5.1</h4>
                <ul>
                    <li>Add: Text Align (Left/Center/Right), defaults to Center.</li>
                </ul>
                <h4>1.5.0</h4>
                <ul>
                    <li>Add: Button text customization and hex color inputs with WP color picker.</li>
                    <li>Add: Animation trigger (Hover/Always).</li>
                </ul>
            '),
                    // Optional extra tab:
                    // 'faq' => wpautop('Q: ...'),
                ],
                // Optional assets (if you add these files):
                'banners'      => [
                    'low'  => ABB_BN_URL . 'assets/img/banner-772x250.png',
                    'high' => ABB_BN_URL . 'assets/img/banner-1544x500.png',
                ],
                'icons'        => [
                    '1x' => ABB_BN_URL . 'assets/img/icon-128x128.png',
                    '2x' => ABB_BN_URL . 'assets/img/icon-256x256.png',
                ],
                                        // Optional: supply a downloadable zip if you host updates yourself
                                        // 'download_link' => 'https://absoftlab.com/downloads/woo-buy-now-button.zip',
                'external'     => true, // mark as external (non .org)
            ];

            return $info;
    }, 10, 3);
