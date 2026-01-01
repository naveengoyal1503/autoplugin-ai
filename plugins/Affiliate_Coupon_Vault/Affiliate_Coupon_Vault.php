/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons and personalized discount codes to boost conversions and commissions.
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_acv_shortcode', array($this, 'ajax_shortcode'));
        add_shortcode('affiliate_coupon_vault', array($this, 'shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-styles', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="80" placeholder='[{"name":"10% Off","code":"SAVE10","afflink":"https://affiliate.com/?ref=123","expiry":"2026-12-31"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p>JSON format: [{&quot;name&quot;:&quot;Name&quot;,&quot;code&quot;:&quot;CODE&quot;,&quot;afflink&quot;:&quot;Affiliate URL&quot;,&quot;expiry&quot;:&quot;YYYY-MM-DD&quot;}]</p>
                <p><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[affiliate_coupon_vault]</code> to display coupons.</p>
            <?php if (!function_exists('is_pro_version')) { ?>
            <div class="notice notice-info"><p><strong>Pro Version:</strong> Unlimited coupons, analytics, auto-expiry. <a href="https://example.com/pro">Upgrade for $49/year</a></p></div>
            <?php } ?>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        update_option('acv_coupons', sanitize_text_field($_POST['data']));
        wp_die('success');
    }

    public function ajax_shortcode() {
        echo $this->shortcode([]);
        wp_die();
    }

    public function shortcode($atts) {
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (empty($coupons) || !is_array($coupons)) {
            return '<p>No coupons available. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Add some in settings</a>.</p>';
        }
        $output = '<div class="acv-vault">';
        foreach ($coupons as $coupon) {
            if (isset($coupon['expiry']) && strtotime($coupon['expiry']) < time()) continue;
            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . esc_html($coupon['name']) . '</h3>';
            $output .= '<p><strong>Code:</strong> <code>' . esc_html($coupon['code']) . '</code></p>';
            $output .= '<p><a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="button">Get Deal</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';
        if (empty($coupons)) {
            $output .= '<p class="acv-pro-upsell">Upgrade to Pro for unlimited exclusive coupons and analytics!</p>';
        }
        return $output;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', '[]');
        }
    }
}

AffiliateCouponVault::get_instance();

/* Pro Upsell Check */
function is_pro_version() {
    return false; // Set to true if pro file included
}

/* Styles */
function acv_add_styles() {
    echo '<style>.acv-vault { display: grid; gap: 20px; max-width: 600px; }.acv-coupon { border: 2px solid #0073aa; padding: 20px; border-radius: 8px; background: #f9f9f9; }.acv-coupon h3 { margin: 0 0 10px; color: #0073aa; }.acv-coupon code { background: #fff; padding: 5px 10px; border-radius: 4px; }.acv-pro-upsell { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; text-align: center; margin-top: 20px; }</style>';
}
add_action('wp_head', 'acv_add_styles');

/* JS */
function acv_add_js() {
    echo '<script> jQuery(document).ready(function($) { $(".acv-coupon .button").on("click", function(e) { $(".acv-coupon").addClass("copied"); setTimeout(() => $(".acv-coupon").removeClass("copied"), 1000); }); }); </script>';
}
add_action('wp_footer', 'acv_add_js');