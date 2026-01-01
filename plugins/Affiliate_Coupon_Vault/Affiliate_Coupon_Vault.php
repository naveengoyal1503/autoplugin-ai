/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically fetch, display, and track affiliate coupons to boost your commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Sample coupon data - in premium, fetch from APIs like CJ Affiliate, ShareASale
        $this->coupons = array(
            array('code' => 'SAVE20', 'description' => '20% off on all products', 'afflink' => 'https://example.com/aff?ref=123', 'expires' => '2026-12-31'),
            array('code' => 'FREESHIP', 'description' => 'Free shipping today', 'afflink' => 'https://example.com/aff?ref=456', 'expires' => '2026-01-15'),
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        ob_start();
        echo '<div class="acv-coupons">';
        $count = 0;
        foreach ($this->coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            if (strtotime($coupon['expires']) < time()) continue;
            echo '<div class="acv-coupon">';
            echo '<h4>' . esc_html($coupon['code']) . '</h4>';
            echo '<p>' . esc_html($coupon['description']) . '</p>';
            echo '<a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="acv-button" onclick="return acvTrackClick(this.href);">Get Deal</a>';
            echo '</div>';
            $count++;
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Tracking script
add_action('wp_head', function() {
    echo '<script>
    function acvTrackClick(url) {
        gtag("event", "click", {"event_category":"coupon","event_label":url});
        window.open(url, "_blank");
        return false;
    }
    </script>';
});

AffiliateCouponVault::get_instance();

// Premium upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock premium features like API integrations and analytics for $49/year! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
});

// Minimal CSS (inline for single file)
add_action('wp_head', function() {
    echo '<style>
    .acv-coupons { display: grid; gap: 15px; max-width: 600px; }
    .acv-coupon { background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 5px solid #007cba; }
    .acv-coupon h4 { margin: 0 0 10px; color: #007cba; font-size: 24px; }
    .acv-coupon p { margin: 0 0 15px; }
    .acv-button { background: #ff6600; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .acv-button:hover { background: #e65c00; }
    @media (max-width: 768px) { .acv-coupons { grid-template-columns: 1fr; } }
    </style>';
});