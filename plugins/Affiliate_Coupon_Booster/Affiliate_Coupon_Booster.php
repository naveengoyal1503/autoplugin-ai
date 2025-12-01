<?php
/*
Plugin Name: Affiliate Coupon Booster
Plugin URI: https://example.com/affiliate-coupon-booster
Description: Auto-aggregates and displays affiliate coupons customized to your niche to boost affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
License: GPLv2 or later
Text Domain: affiliate-coupon-booster
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponBooster {
    private $coupons;

    public function __construct() {
        add_shortcode('affiliate_coupons', array($this, 'render_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        // Load coupons on plugin load
        $this->load_coupons();
    }

    public function enqueue_styles() {
        wp_enqueue_style('affiliate-coupon-booster-style', plugin_dir_url(__FILE__).'style.css');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Booster', 'Affiliate Coupon Booster', 'manage_options', 'affiliate-coupon-booster', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('acb_settings_group', 'acb_affiliate_links');
        register_setting('acb_settings_group', 'acb_custom_coupons');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
        <h1>Affiliate Coupon Booster Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('acb_settings_group'); ?>
            <?php do_settings_sections('acb_settings_group'); ?>

            <h2>Affiliate Links (format: brand|url)</h2>
            <textarea name="acb_affiliate_links" rows="8" cols="50" placeholder="Amazon|https://amzn.to/...\nBestBuy|https://bit.ly/..." style="width:100%;"><?php echo esc_textarea(get_option('acb_affiliate_links')); ?></textarea>

            <h2>Custom Coupons (format: brand|coupon code|description)</h2>
            <textarea name="acb_custom_coupons" rows="8" cols="50" placeholder="Amazon|SAVE10|10% off sitewide\nBestBuy|FREESHIP|Free shipping" style="width:100%;"><?php echo esc_textarea(get_option('acb_custom_coupons')); ?></textarea>

            <?php submit_button(); ?>
        </form>
        </div>
        <?php
    }

    private function load_coupons() {
        // Get affiliate links and custom coupons from options
        $affiliate_links_raw = get_option('acb_affiliate_links', '');
        $custom_coupons_raw = get_option('acb_custom_coupons', '');

        $affiliate_links = $this->parse_affiliate_links($affiliate_links_raw);
        $custom_coupons = $this->parse_custom_coupons($custom_coupons_raw);

        // Combine and randomize coupons
        $coupons = array();
        foreach ($affiliate_links as $brand => $url) {
            $coupon = isset($custom_coupons[$brand]) ? $custom_coupons[$brand] : array('code' => '', 'description' => '');
            $coupons[] = array(
                'brand' => $brand,
                'url' => $url,
                'code' => $coupon['code'],
                'description' => $coupon['description']
            );
        }
        shuffle($coupons);
        $this->coupons = $coupons;
    }

    private function parse_affiliate_links($raw) {
        $lines = explode("\n", trim($raw));
        $links = array();
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 2) {
                $brand = sanitize_text_field($parts);
                $url = esc_url_raw(trim($parts[1]));
                if ($brand && $url) {
                    $links[$brand] = $url;
                }
            }
        }
        return $links;
    }

    private function parse_custom_coupons($raw) {
        $lines = explode("\n", trim($raw));
        $coupons = array();
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 3) {
                $brand = sanitize_text_field($parts);
                $code = sanitize_text_field($parts[1]);
                $desc = sanitize_text_field($parts[2]);
                if ($brand) {
                    $coupons[$brand] = array('code' => $code, 'description' => $desc);
                }
            }
        }
        return $coupons;
    }

    public function render_coupons($atts) {
        if (empty($this->coupons)) return '<p>No coupons available yet. Configure in plugin settings.</p>';

        ob_start();
        echo '<div class="acb-coupons-container">';
        foreach ($this->coupons as $coupon) {
            echo '<div class="acb-coupon">';
            echo '<h3>' . esc_html($coupon['brand']) . '</h3>';
            if (!empty($coupon['code'])) {
                echo '<p><strong>Code:</strong> <code>' . esc_html($coupon['code']) . '</code></p>';
            }
            if (!empty($coupon['description'])) {
                echo '<p>' . esc_html($coupon['description']) . '</p>';
            }
            echo '<p><a class="acb-button" href="' . esc_url($coupon['url']) . '" target="_blank" rel="nofollow noopener">Shop Now</a></p>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

new AffiliateCouponBooster();

// Basic styling
add_action('wp_head', function() {
    echo '<style>.acb-coupons-container{display:flex;flex-wrap:wrap;gap:1em;}.acb-coupon{border:1px solid #ddd;padding:1em;border-radius:5px;flex:1 1 250px;max-width:300px;background:#f9f9f9;}.acb-coupon h3{margin-top:0;color:#2c3e50;}.acb-button{display:inline-block;margin-top:0.5em;padding:0.5em 1em;background:#0073aa;color:#fff;text-decoration:none;border-radius:3px;transition:background-color 0.3s ease;} .acb-button:hover{background:#005177;}</style>';
});
