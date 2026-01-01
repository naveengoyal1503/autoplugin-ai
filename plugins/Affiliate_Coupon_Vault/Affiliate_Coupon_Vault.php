<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and boosts conversions with personalized discount displays.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
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

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', 'Coupon Code: SAVE10
Affiliate Link: https://example.com/aff
Description: 10% off on all products');
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (one per line: Code|Link|Description)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-generation. <a href="#pro">Get Pro for $49/year</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        if (!isset($coupons[$atts['id']])) return '';
        list($code, $link, $desc) = explode('|', $coupons[$atts['id']], 3);
        $clicks = get_option('acv_clicks_' . $atts['id'], 0);
        $track_url = add_query_arg('acv_track', $atts['id'], $link);
        ob_start();
        ?>
        <div class="acv-coupon">
            <h3><?php echo esc_html($desc); ?></h3>
            <p><strong>Code:</strong> <span class="coupon-code"><?php echo esc_html($code); ?></span></p>
            <p>Clicks: <?php echo intval($clicks); ?> | <a href="<?php echo esc_url($track_url); ?>" class="coupon-link" target="_blank">Get Deal</a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!wp_next_scheduled('acv_cleanup')) {
            wp_schedule_event(time(), 'daily', 'acv_cleanup');
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('acv_cleanup');
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['acv_track'])) {
        $id = intval($_GET['acv_track']);
        $clicks = get_option('acv_clicks_' . $id, 0) + 1;
        update_option('acv_clicks_' . $id, $clicks);
        wp_redirect(remove_query_arg('acv_track'));
        exit;
    }
});

// Cleanup old data
add_action('acv_cleanup', function() {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'acv_clicks_%' AND option_name NOT LIKE '%_transient_%'");
});

AffiliateCouponVault::get_instance();

// Pro upsell notice (simulated)
add_action('admin_notices', function() {
    if (!get_option('acv_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons & analytics for $49/year. <a href="#pro">Upgrade Now</a></p></div>';
    }
});

// Assets (base64 or inline for single file - here assuming dir, but for pure single file, inline CSS/JS)
/*
For production single file, inline CSS/JS below:
*/

?>