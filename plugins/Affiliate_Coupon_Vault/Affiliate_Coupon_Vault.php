/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with click tracking for higher conversions.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            return;
        }
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
        }
        $coupons = get_option('acv_coupons', "Brand1:10OFF|https://example.com/brand1\nBrand2:SAVE20|https://example.com/brand2");
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post"><table class="form-table">';
        echo '<tr><th>Coupons (format: Name:Code|AffiliateLink per line)</th></tr><tr><td><textarea name="coupons" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea></td></tr>';
        echo '<tr><td><input type="submit" name="submit" class="button-primary" value="Save Settings"></td></tr></table></form>';
        echo '<p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard, auto-rotation. <a href="https://example.com/pro">Get Pro ($49/year)</a></p></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = $this->parse_coupons();
        if (empty($coupons)) return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=acv-settings') . '">Set up now</a>.</p>';

        $coupon = $coupons[array_rand($coupons)];
        $id = !empty($atts['id']) ? sanitize_text_field($atts['id']) : uniqid();
        $track_url = add_query_arg(array('acv_coupon' => $coupon['code'], 'acv_ref' => $id), $coupon['link']);

        ob_start();
        echo '<div class="acv-coupon" data-id="' . esc_attr($id) . '">';
        echo '<h3>Exclusive Deal: <strong>' . esc_html($coupon['name']) . '</strong></h3>';
        echo '<p>Use code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
        echo '<a href="#" class="acv-track-btn" data-url="' . esc_url($track_url) . '" data-your-affiliate="your-affiliate-id">🛒 Shop Now & Save!</a>';
        echo '<p class="acv-tracked" style="display:none;">Tracked! Redirecting...</p>';
        echo '</div>';
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $coupon = sanitize_text_field($_POST['coupon'] ?? '');
        $ref = sanitize_text_field($_POST['ref'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'];
        $user = get_current_user_id();

        // Free version: Basic log (Pro: full analytics)
        $log = get_option('acv_logs', array()) + array(time() => array('coupon' => $coupon, 'ref' => $ref, 'ip' => $ip, 'user' => $user));
        if (count($log) > 100) array_shift($log); // Limit free logs
        update_option('acv_logs', $log);

        wp_send_json_success(array('redirect' => $_POST['url']));
    }

    private function parse_coupons() {
        $raw = get_option('acv_coupons', '');
        $lines = explode("\n", trim($raw));
        $coupons = array();
        foreach ($lines as $line) {
            if (strpos($line, '|') !== false) {
                list($name_code, $link) = explode('|', trim($line), 2);
                list($name, $code) = explode(':', $name_code, 2);
                $coupons[] = array('name' => trim($name), 'code' => trim($code), 'link' => esc_url_raw(trim($link)));
            }
        }
        return $coupons;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Brand1:10OFF|https://example.com/brand1\nBrand2:SAVE20|https://example.com/brand2");
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline JS for tracking
function acv_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-track-btn').click(function(e) {
            e.preventDefault();
            var btn = $(this), tracked = btn.siblings('.acv-tracked');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                nonce: acv_ajax.nonce,
                coupon: btn.data('your-affiliate'),
                ref: btn.closest('.acv-coupon').data('id'),
                url: btn.data('url')
            }, function(res) {
                if (res.success) {
                    tracked.show();
                    window.location = res.data.redirect;
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'acv_inline_js');

// CSS
add_action('wp_head', function() {
    echo '<style>.acv-coupon {border:2px dashed #007cba;padding:20px;margin:20px 0;background:#f9f9f9;border-radius:8px;}.acv-track-btn {background:#007cba;color:white;padding:12px 24px;text-decoration:none;border-radius:5px;font-weight:bold;display:inline-block;}.acv-track-btn:hover {background:#005a87;}</style>';
});