/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Affiliate Pro
 * Plugin URI: https://example.com/smart-coupon-pro
 * Description: Automatically generates and displays exclusive affiliate coupon codes with tracking to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartCouponAffiliatePro {
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
        add_shortcode('smart_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_track_coupon_click', array($this, 'track_coupon_click'));
        add_action('wp_ajax_nopriv_track_coupon_click', array($this, 'track_coupon_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('scp_pro_version') !== '1.0') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scp-frontend', 'scp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('scp_nonce')));
    }

    public function admin_menu() {
        add_options_page(
            'Smart Coupon Pro Settings',
            'Smart Coupons',
            'manage_options',
            'smart-coupon-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['scp_submit'])) {
            update_option('scp_coupons', sanitize_textarea_field($_POST['scp_coupons']));
            update_option('scp_affiliate_link', esc_url_raw($_POST['scp_affiliate_link']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('scp_coupons', "COUPON10: 10% off at Store\nWELCOME20: $20 off first purchase");
        $link = get_option('scp_affiliate_link', 'https://affiliate.example.com/?coupon=%code%');
        ?>
        <div class="wrap">
            <h1>Smart Coupon Affiliate Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (code:description)</th>
                        <td><textarea name="scp_coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link Template</th>
                        <td><input type="url" name="scp_affiliate_link" value="<?php echo esc_attr($link); ?>" size="50" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Auto-generate unique codes, click analytics, unlimited coupons. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode("\n", get_option('scp_coupons', ''));
        if (empty($coupons)) return 'No coupons configured.';

        $output = '<div class="scp-coupons">';
        foreach ($coupons as $coupon) {
            $parts = explode(':', trim($coupon), 2);
            if (count($parts) === 2) {
                $code = trim($parts);
                $desc = trim($parts[1]);
                $link = str_replace('%code%', $code, get_option('scp_affiliate_link', '#'));
                $output .= '<div class="scp-coupon"><strong>' . esc_html($code) . '</strong>: ' . esc_html($desc) . ' <a href="' . esc_url($link) . '" class="scp-btn" data-code="' . esc_attr($code) . '">Get Deal</a></div>';
            }
        }
        $output .= '</div>';
        $output .= '<style>.scp-coupons { max-width: 400px; margin: 20px 0; }.scp-coupon { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; }.scp-btn { background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; }</style>';
        return $output;
    }

    public function track_coupon_click() {
        check_ajax_referer('scp_nonce', 'nonce');
        $code = sanitize_text_field($_POST['code']);
        error_log('SCP Coupon Click: ' . $code . ' from ' . $_SERVER['REMOTE_ADDR']);
        wp_die('success');
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart Coupon Pro</strong> features like analytics and auto-codes. <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }

    public function activate() {
        update_option('scp_pro_version', '1.0');
    }

    public function deactivate() {}
}

SmartCouponAffiliatePro::get_instance();

// Pro teaser - in real pro version, this would be unlocked
function scp_is_pro() {
    return false; // Set to true with license check
}

if (!scp_is_pro()) {
    add_action('admin_notices', array(SmartCouponAffiliatePro::get_instance(), 'pro_notice'));
}
?>