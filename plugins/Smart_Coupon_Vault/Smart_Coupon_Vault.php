/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: AI-powered coupon management for affiliate marketing. Generate, display, and track exclusive coupons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartCouponVault {
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
        add_shortcode('scv_coupon_display', array($this, 'coupon_display_shortcode'));
        add_action('wp_ajax_scv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_scv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_register_style('scv-admin-css', plugin_dir_url(__FILE__) . 'admin.css');
            wp_enqueue_style('scv-admin-css');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-js', plugin_dir_url(__FILE__) . 'scv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scv-js', 'scv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('scv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Coupon Vault', 'Coupon Vault', 'manage_options', 'scv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['scv_save'])) {
            update_option('scv_api_key', sanitize_text_field($_POST['api_key']));
            update_option('scv_coupons', json_encode($_POST['coupons'] ?? array()));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('scv_api_key', '');
        $coupons = json_decode(get_option('scv_coupons', '[]'), true);
        ?>
        <div class="wrap">
            <h1>Smart Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AI API Key (OpenAI or similar)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" size="50" /></td>
                    </tr>
                </table>
                <h2>Coupons</h2>
                <div id="coupons-list">
                    <?php foreach ($coupons as $coupon): ?>
                    <div class="coupon-item">
                        <input type="text" name="coupons[<?php echo esc_attr($coupon['id']); ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" placeholder="Coupon Code" />
                        <input type="text" name="coupons[<?php echo esc_attr($coupon['id']); ?>][desc]" value="<?php echo esc_attr($coupon['desc']); ?>" placeholder="Description" />
                        <input type="text" name="coupons[<?php echo esc_attr($coupon['id']); ?>][afflink]" value="<?php echo esc_attr($coupon['afflink']); ?>" placeholder="Affiliate Link" />
                        <button type="button" class="button button-secondary remove-coupon">Remove</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-coupon" class="button">Add Coupon</button>
                <p><input type="submit" name="scv_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <h2>Generate New Coupon</h2>
            <input type="text" id="coupon-prompt" placeholder="e.g., Generate a 20% off coupon for hosting services" />
            <button id="generate-coupon" class="button-primary">Generate with AI</button>
            <div id="generated-coupon"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let couponId = <?php echo count($coupons); ?>;
            $('#add-coupon').click(function() {
                $('#coupons-list').append('<div class="coupon-item"><input type="text" name="coupons[' + (couponId++) + '][code]" placeholder="Coupon Code" /><input type="text" name="coupons[' + (couponId-1) + '][desc]" placeholder="Description" /><input type="text" name="coupons[' + (couponId-1) + '][afflink]" placeholder="Affiliate Link" /><button type="button" class="button remove-coupon">Remove</button></div>');
            });
            $(document).on('click', '.remove-coupon', function() {
                $(this).parent().remove();
            });
            $('#generate-coupon').click(function() {
                $.post(scv_ajax.ajax_url, {
                    action: 'scv_generate_coupon',
                    nonce: scv_ajax.nonce,
                    prompt: $('#coupon-prompt').val()
                }, function(response) {
                    $('#generated-coupon').html('<p><strong>Code:</strong> ' + response.code + '</p><p><strong>Desc:</strong> ' + response.desc + '</p>');
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('scv_nonce', 'nonce');
        $prompt = sanitize_text_field($_POST['prompt']);
        $api_key = get_option('scv_api_key');
        if (!$api_key) {
            wp_die('API key required');
        }
        // Simulate AI generation (replace with real OpenAI API call for premium)
        $code = 'SAVE' . rand(10, 99);
        $desc = 'AI Generated: ' . substr($prompt, 0, 50) . '... ' . $code . ' - Up to 50% off!';
        wp_send_json_success(array('code' => $code, 'desc' => $desc));
    }

    public function coupon_display_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = json_decode(get_option('scv_coupons', '[]'), true);
        if (empty($coupons)) return 'No coupons available.';
        $html = '<div class="scv-coupons">';
        foreach ($coupons as $coupon) {
            $html .= '<div class="coupon-card">';
            $html .= '<h3>' . esc_html($coupon['code']) . '</h3>';
            $html .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $html .= '<a href="' . esc_url($coupon['afflink']) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '<style>.scv-coupons .coupon-card {border:1px solid #ddd; padding:15px; margin:10px 0; background:#f9f9f9;}.coupon-btn {background:#0073aa; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;}</style>';
        return $html;
    }

    public function activate() {
        add_option('scv_coupons', json_encode(array()));
    }
}

SmartCouponVault::get_instance();

// Premium upsell notice
function scv_premium_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock AI-powered coupon generation, analytics, and more with <a href="https://example.com/premium" target="_blank">Smart Coupon Vault Premium</a> for $49/year!</p></div>';
}
add_action('admin_notices', 'scv_premium_notice');