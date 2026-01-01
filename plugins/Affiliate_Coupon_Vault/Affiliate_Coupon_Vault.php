/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost conversions and commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_url' => '',
            'discount' => '20%',
            'expires' => '+30 days',
            'button_text' => 'Get Coupon',
        ), $atts);

        $coupon_code = $this->generate_coupon_code($atts['affiliate_url']);
        $expires = strtotime($atts['expires']);
        $tracking_id = uniqid('acv_');

        ob_start();
        ?>
        <div class="acv-coupon-vault" data-tracking="<?php echo esc_attr($tracking_id); ?>">
            <div class="acv-coupon-code" style="display:none;"><?php echo esc_html($coupon_code); ?></div>
            <p>Save <strong><?php echo esc_html($atts['discount']); ?></strong> with our exclusive coupon!</p>
            <button class="acv-reveal-btn" data-url="<?php echo esc_url($atts['affiliate_url']); ?>"><?php echo esc_html($atts['button_text']); ?></button>
            <div class="acv-coupon-revealed" style="display:none;">
                <span class="acv-code"><?php echo esc_html($coupon_code); ?></span>
                <a href="<?php echo esc_url($atts['affiliate_url'] . '?coupon=' . $coupon_code); ?>" class="acv-shop-btn" target="_blank" rel="nofollow">Shop Now & Save</a>
                <small>Expires: <?php echo date('M j, Y', $expires); ?></small>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($url) {
        $hash = md5($url . time() . wp_salt());
        return substr(strtoupper($hash), 0, 8);
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_die();
        }
        $url = sanitize_url($_POST['url']);
        $coupon = $this->generate_coupon_code($url);
        wp_send_json_success(array('coupon' => $coupon));
    }

    public function activate() {
        add_option('acv_activated', time());
        flush_rewrite_rules();
    }
}

// Enqueue dummy assets (create these files in plugin dir)
function acv_enqueue_assets() {
    // JS and CSS would be here in real files
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!get_option('acv_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics, and custom designs! <a href="https://example.com/pro" target="_blank">Get Pro ($49/yr)</a> | <a href="?acv_dismiss=1">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

if (isset($_GET['acv_dismiss'])) {
    update_option('acv_pro_dismissed', 1);
}