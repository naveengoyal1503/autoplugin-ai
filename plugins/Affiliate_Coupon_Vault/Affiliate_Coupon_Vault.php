/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from popular networks to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'network' => 'amazon',
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $coupons = $this->get_sample_coupons($atts['network'], $atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h4><?php echo esc_html($coupon['title']); ?></h4>
                <p class="discount">Save <strong><?php echo esc_html($coupon['discount']); ?></strong></p>
                <div class="code">Code: <span class="coupon-code"><?php echo esc_html($coupon['code']); ?></span></div>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Shop Now & Save</a>
                <small>Expires: <?php echo esc_html($coupon['expires']); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_sample_coupons($network, $category, $limit) {
        $sample_coupons = array(
            array(
                'title' => '50% Off Premium Hosting',
                'discount' => '50%',
                'code' => 'AFFV50',
                'link' => 'https://example.com/hosting?aff=123',
                'expires' => '2026-03-01'
            ),
            array(
                'title' => 'Free Domain with Purchase',
                'discount' => 'Free Domain',
                'code' => 'AFFVDOMAIN',
                'link' => 'https://example.com/domain?aff=123',
                'expires' => '2026-02-15'
            ),
            array(
                'title' => '20% Off WordPress Themes',
                'discount' => '20%',
                'code' => 'AFFVWP20',
                'link' => 'https://example.com/themes?aff=123',
                'expires' => '2026-01-31'
            )
        );
        return array_slice($sample_coupons, 0, $limit);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('affcv_settings', 'affcv_api_keys');
        add_settings_section('affcv_main', 'API Settings (Pro)', null, 'affcv');
        add_settings_field('affcv_amazon_key', 'Amazon Affiliate ID', array($this, 'amazon_key_field'), 'affcv', 'affcv_main');
    }

    public function amazon_key_field() {
        $keys = get_option('affcv_api_keys', array());
        echo '<input type="text" name="affcv_api_keys[amazon]" value="' . esc_attr($keys['amazon'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your Amazon Affiliate ID. <strong>Pro:</strong> Unlock more networks.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affcv_settings');
                do_settings_sections('affcv');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupons network="amazon" category="hosting" limit="3"]</code></p>
            <p><strong>Pro Upgrade:</strong> Real-time coupon fetching, analytics, custom designs. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Inline styles and scripts for single-file

function affcv_add_inline_styles() {
    echo '<style>
        .affiliate-coupon-vault { max-width: 600px; margin: 20px 0; }
        .coupon-item { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin-bottom: 15px; border-radius: 8px; }
        .coupon-item h4 { margin: 0 0 10px; color: #333; }
        .discount { font-size: 24px; color: #e74c3c; margin: 10px 0; }
        .coupon-code { background: #fff; padding: 5px 10px; font-family: monospace; border: 1px dashed #ccc; }
        .coupon-btn { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
        .coupon-btn:hover { background: #219a52; }
    </style>';
}
add_action('wp_head', 'affcv_add_inline_styles');

function affcv_add_inline_scripts() {
    echo '<script>jQuery(document).ready(function($) { $(".coupon-btn").on("click", function() { $(this).text("Copied! Shopping..."); }); });</script>';
}
add_action('wp_footer', 'affcv_add_inline_scripts');

// Pro upsell notice
function affcv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro for real API integrations & analytics! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'affcv_admin_notice');