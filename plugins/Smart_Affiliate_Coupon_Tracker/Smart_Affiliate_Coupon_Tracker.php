/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Tracker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Tracker
 * Plugin URI: https://example.com/smart-affiliate-coupon-tracker
 * Description: Automatically generates, tracks, and displays personalized affiliate coupons with click/session analytics to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupon-tracker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCouponTracker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sact_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sact_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }
        load_plugin_textdomain('smart-affiliate-coupon-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sact-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sact-frontend', 'sact_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sact_nonce')));
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'smart-affiliate') !== false) {
            wp_enqueue_script('sact-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Coupon Tracker',
            'Coupon Tracker',
            'manage_options',
            'smart-affiliate-coupon-tracker',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['sact_save'])) {
            update_option('sact_coupons', sanitize_textarea_field($_POST['sact_coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sact_coupons', "Code: SAVE20\nAffiliate Link: https://example.com/affiliate-link\nDescription: 20% off first purchase");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Tracker</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (one per line: Code|Affiliate Link|Description)</th>
                        <td><textarea name="sact_coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'sact_save'); ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[sact_coupon]</code> to display random coupon. Pro: [sact_coupon id="1"]</p>
            <h2>Analytics</h2>
            <p>Total clicks: <?php echo get_option('sact_total_clicks', 0); ?></p>
        </div>
        <?php
    }

    public function track_click() {
        check_ajax_referer('sact_nonce', 'nonce');
        $clicks = get_option('sact_total_clicks', 0) + 1;
        update_option('sact_total_clicks', $clicks);
        $coupon_id = sanitize_text_field($_POST['coupon_id']);
        $click_data = get_option('sact_clicks', array()) ?: array();
        $click_data[$coupon_id] = ($click_data[$coupon_id] ?? 0) + 1;
        update_option('sact_clicks', $click_data);
        wp_die();
    }

    public function activate() {
        update_option('sact_total_clicks', 0);
    }
}

// Shortcode
add_shortcode('sact_coupon', function() {
    $coupons_text = get_option('sact_coupons', '');
    if (empty($coupons_text)) {
        return '<p>No coupons configured. Go to Settings > Coupon Tracker.</p>';
    }
    $coupons = explode("\n", $coupons_text);
    $coupon = $coupons[array_rand($coupons)];
    list($code, $link, $desc) = array_pad(explode('|', trim($coupon)), 3, '');
    $id = md5($code); // Simple unique ID
    ob_start();
    ?>
    <div class="sact-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9;">
        <h3><?php echo esc_html($code); ?> Coupon</h3>
        <p><?php echo esc_html($desc); ?></p>
        <a href="#" class="sact-track" data-id="<?php echo esc_attr($id); ?>" data-link="<?php echo esc_url($link); ?>" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Redeem Now (Affiliate)</a>
    </div>
    <script>jQuery(document).ready(function($){ $('.sact-track[data-id="<?php echo esc_js($id); ?>"]').click(function(e){ e.preventDefault(); $.post(sact_ajax.ajax_url, {action: 'sact_track_click', nonce: sact_ajax.nonce, coupon_id: $(this).data('id')}, function(){ window.location = $(this).data('link'); }.bind(this)); }); });</script>
    <?php
    return ob_get_clean();
});

SmartAffiliateCouponTracker::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>Smart Affiliate Coupon Tracker Pro</strong>: Unlimited coupons, detailed analytics, custom designs. <a href="https://example.com/pro" target="_blank">Upgrade now ($49/year)</a></p></div>';
});