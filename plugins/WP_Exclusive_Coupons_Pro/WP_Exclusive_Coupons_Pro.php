/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates and manages exclusive coupon codes to boost affiliate conversions and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Exclusive_Coupons {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wpec_coupon_display', array($this, 'coupon_display_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('wpec_api_key', '');
        add_option('wpec_enabled', 'yes');
        add_option('wpec_coupon_count', 5);
    }

    public function admin_menu() {
        add_options_page(
            'WP Exclusive Coupons',
            'Exclusive Coupons',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['wpec_save'])) {
            update_option('wpec_api_key', sanitize_text_field($_POST['wpec_api_key']));
            update_option('wpec_enabled', sanitize_text_field($_POST['wpec_enabled']));
            update_option('wpec_coupon_count', intval($_POST['wpec_coupon_count']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('wpec_api_key');
        $enabled = get_option('wpec_enabled', 'yes');
        $count = get_option('wpec_coupon_count', 5);
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="wpec_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Enable Coupons</th>
                        <td><input type="checkbox" name="wpec_enabled" value="yes" <?php checked($enabled, 'yes'); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Coupons per User</th>
                        <td><input type="number" name="wpec_coupon_count" value="<?php echo esc_attr($count); ?>" min="1" max="50" /></td>
                    </tr>
                </table>
                <?php submit_button('wpec_save', 'Save Settings'); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[wpec_coupon_display]</code> to display coupons on any page or post.</p>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-expiration, and affiliate integrations for $49/year.</p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        if (get_option('wpec_enabled') !== 'yes') return;
        wp_enqueue_script('wpec-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('wpec-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function coupon_display_shortcode($atts) {
        if (get_option('wpec_enabled') !== 'yes') {
            return '<p>Coupons disabled.</p>';
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p><a href="' . wp_login_url() . '">Log in</a> to get your exclusive coupons!</p>';
        }
        $used_coupons = get_user_meta($user_id, 'wpec_used_coupons', true);
        if (!$used_coupons) $used_coupons = array();
        $count = get_option('wpec_coupon_count', 5);
        $available = $count - count($used_coupons);
        if ($available <= 0) {
            return '<p>You have used all available coupons. <a href="' . admin_url('options-general.php?page=wp-exclusive-coupons') . '">Contact admin for more.</a></p>';
        }
        ob_start();
        echo '<div id="wpec-coupons" class="wpec-container">';
        echo '<h3>Your Exclusive Coupons (' . $available . ' left)</h3>';
        for ($i = 0; $i < min(3, $available); $i++) {
            $code = $this->generate_coupon_code($user_id);
            echo '<div class="wpec-coupon">';
            echo '<strong>' . $code . '</strong><br/>';
            echo '<small>Exclusive 20% off! Valid for 7 days.</small>';
            echo '<button class="wpec-copy" data-code="' . $code . '">Copy</button>';
            echo '</div>';
        }
        echo '<p><em>Pro version: Unlimited + analytics.</em></p>';
        echo '</div>';
        return ob_get_clean();
    }

    private function generate_coupon_code($user_id) {
        $prefix = 'EXC' . strtoupper(substr(md5($user_id . time()), 0, 6));
        $code = $prefix . rand(100, 999);
        $used = get_user_meta($user_id, 'wpec_used_coupons', true);
        if (!is_array($used)) $used = array();
        $used[] = $code;
        update_user_meta($user_id, 'wpec_used_coupons', $used);
        return $code;
    }
}

WP_Exclusive_Coupons::get_instance();

// Pro upsell notice
function wpec_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>WP Exclusive Coupons Pro</strong> for unlimited coupons, detailed analytics, and premium affiliate integrations! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
}
add_action('admin_notices', 'wpec_pro_notice');

// Frontend assets placeholder (create empty folders: assets/frontend.js and assets/frontend.css)
// JS: jQuery('.wpec-copy').click(function(){ navigator.clipboard.writeText($(this).data('code')); });
// CSS: .wpec-container { border: 1px solid #ddd; padding: 20px; } .wpec-coupon { background: #f9f9f9; margin: 10px 0; padding: 15px; }