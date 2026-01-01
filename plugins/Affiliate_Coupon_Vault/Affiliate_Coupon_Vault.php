/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons, personalized discounts, and deal trackers to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Main Settings', null, 'acv');
        add_settings_field('acv_api_key', 'Affiliate API Key (Pro)', array($this, 'api_key_field'), 'acv', 'acv_main');
        add_settings_field('acv_default_discount', 'Default Discount %', array($this, 'discount_field'), 'acv', 'acv_main');
    }

    public function api_key_field() {
        $options = get_option('acv_options');
        echo '<input type="text" name="acv_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" placeholder="Enter Pro API Key" />';
        echo '<p class="description">Upgrade to Pro for real affiliate integrations. Free version uses mock data.</p>';
    }

    public function discount_field() {
        $options = get_option('acv_options');
        echo '<input type="number" name="acv_options[default_discount]" value="' . esc_attr($options['default_discount'] ?? 20) . '" min="1" max="90" /> %';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode <code>[acv_coupons category="tech" limit="5"]</code> to display coupons.</p>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics, auto-expiry, email capture.</p>
            <a href="https://example.com/pro" class="button button-primary">Upgrade to Pro ($49/year)</a>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'general',
            'limit' => 3,
        ), $atts);

        $options = get_option('acv_options');
        $discount = $options['default_discount'] ?? 20;
        $coupons = $this->generate_mock_coupons($atts['limit'], $atts['category'], $discount);

        ob_start();
        ?>
        <div class="acv-coupons-vault" data-category="<?php echo esc_attr($atts['category']); ?>">
            <?php foreach ($coupons as $coupon): ?>
            <div class="acv-coupon-card">
                <h3><?php echo esc_html($coupon['title']); ?></h3>
                <p class="acv-discount"><?php echo esc_html($coupon['discount']); ?> OFF</p>
                <p class="acv-description"><?php echo esc_html($coupon['description']); ?></p>
                <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                <a href="<?php echo esc_url($coupon['affiliate_link']); ?}" class="acv-button" target="_blank" rel="nofollow">Shop Now & Save</a>
                <span class="acv-expires">Expires: <?php echo esc_html($coupon['expires']); ?></span>
            </div>
            <?php endforeach; ?>
            <p class="acv-pro-upsell">Upgrade to Pro for unlimited coupons & analytics!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_mock_coupons($limit, $category, $discount) {
        $mock_data = array(
            'general' => array(
                array('title' => '10% Off Hosting', 'discount' => $discount . '%', 'description' => 'Premium hosting for your site', 'code' => 'SAVE' . rand(1000,9999), 'affiliate_link' => 'https://example.com/hosting?ref=123', 'expires' => date('M d', strtotime('+7 days'))),
                array('title' => 'Free Theme Trial', 'discount' => 'FREE', 'description' => 'Try premium WordPress themes', 'code' => 'THEMEFREE', 'affiliate_link' => 'https://example.com/themes?ref=123', 'expires' => date('M d', strtotime('+14 days'))),
            ),
            'tech' => array(
                array('title' => 'VPN 50% Off', 'discount' => '50%', 'description' => 'Secure browsing discount', 'code' => 'VPNDEAL', 'affiliate_link' => 'https://example.com/vpn?ref=123', 'expires' => date('M d', strtotime('+30 days'))),
            )
        );
        $data = $mock_data[$category] ?? $mock_data['general'];
        return array_slice($data, 0, $limit);
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $category = sanitize_text_field($_POST['category']);
        $options = get_option('acv_options');
        $discount = $options['default_discount'] ?? 20;
        $coupon = $this->generate_mock_coupons(1, $category, $discount);
        wp_send_json_success($coupon);
    }

    public function activate() {
        add_option('acv_options', array('default_discount' => 20));
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Embed assets as base64 or simple styles (for single file)
function acv_inline_styles() {
    echo '<style>
    .acv-coupons-vault { max-width: 600px; margin: 20px 0; }
    .acv-coupon-card { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 8px; }
    .acv-discount { font-size: 24px; color: #e74c3c; font-weight: bold; }
    .acv-button { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .acv-button:hover { background: #2980b9; }
    .acv-pro-upsell { text-align: center; background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>';
}
add_action('wp_head', 'acv_inline_styles');

// Simple JS
function acv_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-coupon-card .acv-button').on('click', function() {
            $(this).text('Copied! Shop Now');
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'acv_inline_js');

AffiliateCouponVault::get_instance();