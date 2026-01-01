/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('acv_coupon_display', array($this, 'coupon_display_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_affiliate_ids', sanitize_text_field($_POST['affiliate_ids']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliate_ids = get_option('acv_affiliate_ids', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Program IDs (comma-separated)</th>
                        <td><input type="text" name="affiliate_ids" value="<?php echo esc_attr($affiliate_ids); ?>" class="regular-text" placeholder="amazon123,clickbank456" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="acv_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[acv_coupon_display]</code> to display coupons on any page/post.</p>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $affiliate_ids = get_option('acv_affiliate_ids', '');
        if (empty($affiliate_ids)) {
            wp_send_json_error('No affiliate IDs configured.');
            return;
        }
        $ids = explode(',', $affiliate_ids);
        $random_id = $ids[array_rand($ids)];
        $coupon_code = 'SAVE' . wp_rand(10, 99) . strtoupper(wp_generate_password(4, false));
        $discount = wp_rand(10, 50);
        $tracking_link = 'https://youraffiliate.com/track/?id=' . $random_id . '&coupon=' . $coupon_code;
        wp_send_json_success(array(
            'code' => $coupon_code,
            'discount' => $discount . '% OFF',
            'link' => $tracking_link,
            'expires' => date('Y-m-d', strtotime('+7 days'))
        ));
    }

    public function coupon_display_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 1), $atts);
        ob_start();
        ?>
        <div id="acv-coupons" class="acv-container" data-count="<?php echo esc_attr($atts['count']); ?>">
            <div class="acv-loading">Loading exclusive coupons...</div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var container = $('#acv-coupons');
            var count = container.data('count');
            for(var i=0; i<count; i++) {
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_generate_coupon',
                    nonce: acv_ajax.nonce
                }, function(response) {
                    if(response.success) {
                        var coupon = `
                            <div class="acv-coupon-card">
                                <h3>Exclusive Deal!</h3>
                                <p><strong>${response.data.code}</strong> - ${response.data.discount}</p>
                                <p>Expires: ${response.data.expires}</p>
                                <a href="${response.data.link}" target="_blank" class="acv-button">Get Deal Now</a>
                            </div>
                        `;
                        container.append(coupon);
                    }
                });
            }
            container.find('.acv-loading').hide();
        });
        </script>
        <style>
        .acv-container { max-width: 400px; }
        .acv-coupon-card { background: #fff; border: 2px solid #0073aa; border-radius: 8px; padding: 20px; margin: 10px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .acv-coupon-card h3 { color: #0073aa; margin: 0 0 10px; }
        .acv-button { background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .acv-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        update_option('acv_affiliate_ids', '');
    }
}

AffiliateCouponVault::get_instance();

// Pro upgrade notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons, analytics, and integrations! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');