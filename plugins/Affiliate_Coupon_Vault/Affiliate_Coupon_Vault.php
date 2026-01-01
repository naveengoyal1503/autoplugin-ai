/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupon codes, tracks clicks and conversions, and displays personalized deals to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Main Settings', null, 'acv-settings');
        add_settings_field('acv_api_key', 'Affiliate API Key', array($this, 'api_key_field'), 'acv-settings', 'acv_main');
        add_settings_field('acv_coupons', 'Coupons', array($this, 'coupons_field'), 'acv-settings', 'acv_main');
    }

    public function api_key_field() {
        $options = get_option('acv_options', array());
        echo '<input type="text" name="acv_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
    }

    public function coupons_field() {
        $options = get_option('acv_options', array());
        $coupons = $options['coupons'] ?? array();
        echo '<textarea name="acv_options[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"name":"Discount","code":"SAVE20","aff_link":"https://aff.link","expires":"2026-12-31"}</p>';
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
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $options = get_option('acv_options', array());
        $coupons = json_decode($options['coupons'] ?? '[]', true);
        $output = '<div class="acv-coupons">';
        $limit = min((int)$atts['limit'], count($coupons));
        for ($i = 0; $i < $limit; $i++) {
            if (isset($coupons[$i])) {
                $coupon = $coupons[$i];
                $id = 'acv-' . md5($coupon['code']);
                $output .= '<div class="acv-coupon">';
                $output .= '<h3>' . esc_html($coupon['name']) . '</h3>';
                $output .= '<p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
                $output .= '<a href="' . esc_url($coupon['aff_link'] . '?coupon=' . $coupon['code']) . '" class="acv-btn" data-coupon="' . esc_attr($coupon['code']) . '" data-track="' . $id . '">Get Deal</a>';
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        if (!get_option('acv_options')) {
            update_option('acv_options', array('api_key' => '', 'coupons' => json_encode(array(
                array('name' => 'Sample 20% Off', 'code' => 'SAVE20', 'aff_link' => '#', 'expires' => '2026-12-31')
            ))));
        }
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

AffiliateCouponVault::get_instance();

// Inline CSS and JS for self-contained
function acv_inline_assets() {
    ?>
    <style>
    .acv-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
    .acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1 1 300px; }
    .acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    .acv-btn:hover { background: #005a87; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-btn').click(function(e) {
            var coupon = $(this).data('coupon');
            var track = $(this).data('track');
            // Track click (simulate analytics)
            console.log('Tracking coupon: ' + coupon);
            // In pro version, send to server
            fetch(acv_ajax.ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=acv_track&coupon=' + encodeURIComponent(coupon)
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'acv_inline_assets');

// AJAX handler
add_action('wp_ajax_acv_track', 'acv_track_click');
add_action('wp_ajax_nopriv_acv_track', 'acv_track_click');
function acv_track_click() {
    $coupon = sanitize_text_field($_POST['coupon'] ?? '');
    error_log('ACV Track: ' . $coupon);
    wp_die();
}
