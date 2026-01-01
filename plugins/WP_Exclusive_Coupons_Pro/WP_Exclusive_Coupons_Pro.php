/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates and manages exclusive affiliate coupons to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Exclusive_Coupons_Pro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function activate() {
        add_option('wpecp_coupons', array(
            array('code' => 'SAVE10', 'afflink' => '', 'desc' => '10% off first purchase', 'expiry' => '2026-12-31'),
        ));
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['save'])) {
            update_option('wpecp_coupons', sanitize_textarea_field($_POST['coupons_data']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('wpecp_coupons', array());
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post">
                <textarea name="coupons_data" rows="10" cols="80" style="width:100%;"><?php echo esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)); ?></textarea>
                <p>Format: JSON array of objects with keys: code, afflink, desc, expiry (YYYY-MM-DD)</p>
                <p><?php submit_button('Save Coupons'); ?></p>
            </form>
            <p>Upgrade to Pro for analytics, auto-generation, and unlimited coupons! <strong>Limited time: $49/year</strong></p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_option('wpecp_coupons', array());
        $today = date('Y-m-d');
        $valid_coupons = array_filter($coupons, function($c) use ($today) {
            return isset($c['expiry']) && $c['expiry'] >= $today;
        });
        $display = array_slice($valid_coupons, 0, intval($atts['limit']));

        if (empty($display)) {
            return '<p>No active coupons available.</p>';
        }

        $output = '<div class="wpecp-coupons">';
        foreach ($display as $coupon) {
            $output .= '<div class="coupon-item">';
            $output .= '<h3>' . esc_html($coupon['code']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['desc']) . '</p>';
            if (!empty($coupon['afflink'])) {
                $output .= '<a href="' . esc_url($coupon['afflink']) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            }
            $output .= '</div>';
        }
        $output .= '</div><p class="pro-upsell">Upgrade to Pro for more features & analytics!</p>';
        return $output;
    }
}

new WP_Exclusive_Coupons_Pro();

// Pro upsell notice
function wpecp_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>WP Exclusive Coupons Pro</strong>: Auto-generate coupons, track clicks, API integrations. <a href="https://example.com/pro" target="_blank">Get it for $49/year</a> →</p></div>';
}
add_action('admin_notices', 'wpecp_admin_notice');

// Prevent direct access to style.css if needed
if (basename($_SERVER['PHP_SELF']) === 'style.css') {
    header('Content-Type: text/css');
    ?>
    .wpecp-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
    .coupon-item { border: 2px dashed #0073aa; padding: 20px; border-radius: 10px; background: #f9f9f9; flex: 1 1 300px; }
    .coupon-item h3 { color: #0073aa; margin: 0 0 10px; }
    .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .coupon-btn:hover { background: #005a87; }
    .pro-upsell { text-align: center; margin-top: 20px; font-weight: bold; color: #0073aa; }
    <?php
    exit;
}
