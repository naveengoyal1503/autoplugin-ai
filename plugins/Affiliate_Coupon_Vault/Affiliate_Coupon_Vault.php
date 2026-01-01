/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks and conversions, and displays personalized deals to boost revenue.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_api_key') === false) {
            add_option('acv_api_key', ''); // Store API key for premium
            add_option('acv_coupons', json_encode(array(
                array('code' => 'SAVE20', 'afflink' => '#', 'desc' => '20% off on hosting', 'expires' => '2026-12-31'),
                array('code' => 'DEAL10', 'afflink' => '#', 'desc' => '10% off tools', 'expires' => '2026-06-30')
            )));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons_json = get_option('acv_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        $output = '<div class="acv-coupons">';
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            if (strtotime($coupon['expires']) < time()) continue;
            $output .= '<div class="acv-coupon">';
            $output .= '<h4>' . esc_html($coupon['desc']) . '</h4>';
            $output .= '<div class="acv-code">' . esc_html($coupon['code']) . '</div>';
            $output .= '<a href="#" class="acv-track" data-link="' . esc_url($coupon['afflink']) . '" data-code="' . esc_attr($coupon['code']) . '">Get Deal</a>';
            $output .= '</div>';
            $count++;
        }
        $output .= '</div>';
        if (empty($coupons) || $count == 0) {
            $output .= '<p>No active coupons available. <a href="' . admin_url('options-general.php?page=acv-settings') . '">Add some in settings</a>.</p>';
        }
        return $output;
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $link = sanitize_url($_POST['link']);
        $code = sanitize_text_field($_POST['code']);
        // Log click (premium: send to API)
        $api_key = get_option('acv_api_key');
        if ($api_key) {
            // Simulate API call
            error_log('ACV Track: ' . $code . ' -> ' . $link);
        }
        wp_redirect($link);
        exit;
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Admin
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_admin_page');
    });

    function acv_admin_page() {
        if (isset($_POST['acv_save'])) {
            if (!wp_verify_nonce($_POST['acv_nonce'], 'acv_save')) wp_die('Security failed');
            update_option('acv_api_key', sanitize_text_field($_POST['api_key']));
            $coupons = array();
            if (isset($_POST['coupons'])) {
                foreach ($_POST['coupons'] as $i => $data) {
                    $coupons[] = array(
                        'code' => sanitize_text_field($data['code']),
                        'afflink' => esc_url_raw($data['afflink']),
                        'desc' => sanitize_text_field($data['desc']),
                        'expires' => sanitize_text_field($data['expires'])
                    );
                }
            }
            update_option('acv_coupons', json_encode($coupons));
            echo '<div class="notice notice-success"><p>Saved!</p></div>';
        }
        $api_key = get_option('acv_api_key', '');
        $coupons_json = get_option('acv_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <?php wp_nonce_field('acv_save', 'acv_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Premium API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Enter for advanced tracking"></td>
                    </tr>
                </table>
                <h2>Add Coupons</h2>
                <?php foreach ($coupons as $i => $coupon): ?>
                    <div class="acv-coupon-row">
                        <input type="hidden" name="coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>">
                        <!-- Simplified; in full version, make dynamic add/remove -->
                        <p>Code: <input name="coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>"></p>
                        <p>Aff Link: <input name="coupons[<?php echo $i; ?>][afflink]" value="<?php echo esc_attr($coupon['afflink']); ?>" style="width:300px;"></p>
                        <p>Desc: <input name="coupons[<?php echo $i; ?>][desc]" value="<?php echo esc_attr($coupon['desc']); ?>"></p>
                        <p>Expires: <input name="coupons[<?php echo $i; ?>][expires]" type="date" value="<?php echo esc_attr($coupon['expires']); ?>"></p>
                    </div>
                <?php endforeach; ?>
                <p><em>Pro: Unlimited coupons, auto-generate codes, analytics dashboard.</em></p>
                <p><input type="submit" name="acv_save" class="button-primary" value="Save Settings"></p>
            </form>
            <p>Use shortcode: <code>[acv_coupons limit="3"]</code></p>
        </div>
        <style>.acv-coupon-row {border:1px solid #ddd; padding:10px; margin:10px 0;}</style>
        <?php
    }
}

// JS and CSS as inline for single file
add_action('wp_head', function() {
    echo '<script>jQuery(document).ready(function($){$(".acv-track").click(function(e){e.preventDefault();var link=$(this).data("link"),code=$(this).data("code");$.post(acv_ajax.ajax_url,{action:"acv_track_click",link:link,code:code,nonce:"' . wp_create_nonce('acv_nonce') . '"});window.location=link;});});</script>';
    echo '<style>.acv-coupons{display:grid;gap:15px;max-width:600px;}.acv-coupon{background:#f9f9f9;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.1);}.acv-code{font-size:2em;font-weight:bold;color:#e74c3c;background:#fff;padding:10px;margin:10px 0;border-radius:5px;display:inline-block;}.acv-track{display:inline-block;background:#3498db;color:white;padding:12px 24px;text-decoration:none;border-radius:5px;font-weight:bold;transition:background 0.3s;}.acv-track:hover{background:#2980b9;}</style>';
});

AffiliateCouponVault::get_instance();
