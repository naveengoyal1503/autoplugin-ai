/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate dynamic affiliate coupon pages to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_post_type();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function create_post_type() {
        register_post_type('acv_coupon', array(
            'labels' => array(
                'name' => 'Coupons',
                'singular_name' => 'Coupon'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'coupons'),
            'menu_icon' => 'dashicons-cart'
        ));
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=acv_coupon', 'Coupon Settings', 'Settings', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_pro_version', sanitize_text_field($_POST['pro_version']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $pro = get_option('acv_pro_version', 'free');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Version</th>
                        <td>
                            <select name="pro_version">
                                <option value="free" <?php selected($pro, 'free'); ?>>Free</option>
                                <option value="pro" <?php selected($pro, 'pro'); ?>>Pro (Enter License)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if ($pro === 'free') : ?>
            <div class="notice notice-info">
                <p>Upgrade to Pro for advanced tracking and unlimited coupons! <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts);
        $coupons = get_posts(array(
            'post_type' => 'acv_coupon',
            'posts_per_page' => $atts['count'],
            'post_status' => 'publish'
        ));
        ob_start();
        echo '<div class="acv-coupons-grid">';
        foreach ($coupons as $coupon) {
            $aff_link = get_post_meta($coupon->ID, 'affiliate_link', true);
            $code = get_post_meta($coupon->ID, 'coupon_code', true);
            $discount = get_post_meta($coupon->ID, 'discount', true);
            echo '<div class="acv-coupon-card">';
            echo '<h3>' . get_the_title($coupon->ID) . '</h3>';
            echo '<p>Code: <strong>' . esc_html($code) . '</strong> - Save ' . esc_html($discount) . '%</p>';
            if ($aff_link) {
                echo '<a href="' . esc_url($aff_link) . '" class="acv-button" target="_blank">Get Deal</a>';
            }
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function activate() {
        $this->create_post_type();
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_admin_notice() {
    if (get_option('acv_pro_version') !== 'pro') {
        echo '<div class="notice notice-upgrade notice-info is-dismissible">';
        echo '<p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, click tracking, and analytics for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

// Minimal CSS
add_action('wp_head', function() {
    echo '<style>.acv-coupons-grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;}.acv-coupon-card {border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9;}.acv-button {background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;}</style>';
});

// Minimal JS
add_action('wp_footer', function() {
    echo '<script>jQuery(document).ready(function($) { $(".acv-button").on("click", function() { $(this).text("Copied! Redeem at checkout"); }); });</script>';
});