/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost affiliate commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) return;
        wp_localize_script('jquery', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $(".acv-generate").click(function() {
                    var btn = $(this);
                    btn.prop("disabled", true).text("Generating...");
                    $.post(acv_ajax.ajax_url, {
                        action: "acv_generate_coupon",
                        nonce: acv_ajax.nonce,
                        affiliate_id: btn.data("affiliate"),
                        product: btn.data("product")
                    }, function(response) {
                        if (response.success) {
                            btn.closest(".acv-coupon").find(".acv-code").text(response.data.code);
                            btn.text("Copy Code");
                        } else {
                            alert("Error: " + response.data);
                        }
                        btn.prop("disabled", false);
                    });
                });
            });
        ');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'product' => 'Sample Product',
            'discount' => '20',
            'link' => 'https://affiliate-link.com',
            'image' => ''
        ), $atts);

        $code = 'SAVE' . $atts['discount'] . wp_generate_password(4, false);
        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9;">
            <?php if ($atts['image']): ?>
            <img src="<?php echo esc_url($atts['image']); ?>" alt="<?php echo esc_attr($atts['product']); ?>" style="max-width: 200px;">
            <?php endif; ?>
            <h3><?php echo esc_html($atts['product']); ?> - <?php echo esc_html($atts['discount']); ?>% OFF!</h3>
            <div class="acv-code" style="font-size: 24px; font-weight: bold; color: #007cba; margin: 10px 0;"><?php echo esc_html($code); ?></div>
            <p><a href="<?php echo esc_url($atts['link'] . '?coupon=' . $code); ?>" target="_blank" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Get Deal Now (Affiliate Link)</a></p>
            <button class="acv-generate button" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>" data-product="<?php echo esc_attr($atts['product']); ?>">Generate Unique Code</button>
            <p style="font-size: 12px; color: #666;">Exclusive reader coupon - Limited time!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Settings', null, 'acv-settings');
        add_settings_field('acv_api_key', 'Affiliate API Key', array($this, 'api_key_field'), 'acv-settings', 'acv_main');
    }

    public function api_key_field() {
        $options = get_option('acv_options');
        echo '<input type="text" name="acv_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your affiliate network API key for auto-generation (Pro feature).</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv-settings');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon affiliate="amazon" product="Laptop" discount="15" link="https://amazon.com/deal"]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, custom domains. <a href="#">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }

    // AJAX handler
    public function handle_ajax() {
        check_ajax_referer('acv_nonce', 'nonce');
        $affiliate = sanitize_text_field($_POST['affiliate_id']);
        $product = sanitize_text_field($_POST['product']);
        $code = 'ACV-' . strtoupper(wp_generate_password(6, false));
        wp_send_json_success(array('code' => $code));
    }
}

new AffiliateCouponVault();

add_action('wp_ajax_acv_generate_coupon', function() {
    $instance = new AffiliateCouponVault();
    $instance->handle_ajax();
});

add_action('wp_ajax_nopriv_acv_generate_coupon', function() {
    $instance = new AffiliateCouponVault();
    $instance->handle_ajax();
});