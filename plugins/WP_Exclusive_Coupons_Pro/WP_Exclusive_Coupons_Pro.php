/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays personalized exclusive coupon codes from affiliate programs to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Exclusive_Coupons_Pro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('wpec_coupon_display', array($this, 'coupon_display_shortcode'));
        add_action('wp_ajax_wpec_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_wpec_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpec-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wpec-frontend', 'wpec_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('wpec_nonce')));
    }

    public function admin_menu() {
        add_options_page('WP Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'wpec-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('wpec_options', 'wpec_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('WP Exclusive Coupons Pro Settings', 'wp-exclusive-coupons-pro'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('wpec_options'); ?>
                <?php do_settings_sections('wpec_options'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Affiliate Programs', 'wp-exclusive-coupons-pro'); ?></th>
                        <td>
                            <textarea name="wpec_settings[affiliates]" rows="10" cols="50"><?php echo esc_textarea(get_option('wpec_settings')['affiliates'] ?? 'Amazon:10% off|Shopify:20% off'); ?></textarea>
                            <p class="description"><?php _e('Enter affiliate programs as: Name:Discount|Name2:Discount2', 'wp-exclusive-coupons-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Pro Upgrade', 'wp-exclusive-coupons-pro'); ?></th>
                        <td>
                            <p><strong>Unlock unlimited coupons, analytics & integrations for $49/year!</strong></p>
                            <a href="https://example.com/pro" class="button button-primary" target="_blank">Upgrade to Pro</a>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_display_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts);
        $settings = get_option('wpec_settings', array('affiliates' => 'Amazon:10% off|Shopify:20% off'));
        $affiliates = explode('|', $settings['affiliates']);
        $coupons = array();
        for ($i = 0; $i < min($atts['count'], count($affiliates)); $i++) {
            list($name, $discount) = explode(':', $affiliates[$i]);
            $code = substr(md5(uniqid()), 0, 8);
            $coupons[] = array('name' => trim($name), 'discount' => trim($discount), 'code' => $code);
        }
        ob_start();
        ?>
        <div id="wpec-coupons" class="wpec-grid">
            <?php foreach ($coupons as $coupon): ?>
                <div class="wpec-coupon-card">
                    <h3><?php echo esc_html($coupon['name']); ?></h3>
                    <p><?php echo esc_html($coupon['discount']); ?></p>
                    <code id="code-<?php echo esc_attr($coupon['code']); ?>"><?php echo esc_html($coupon['code']); ?></code>
                    <button class="wpec-copy-btn" data-code="<?php echo esc_attr($coupon['code']); ?>">Copy Code</button>
                    <a href="#" class="wpec-generate-btn" data-name="<?php echo esc_attr($coupon['name']); ?>">New Exclusive Code</a>
                </div>
            <?php endforeach; ?>
        </div>
        <style>
            .wpec-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
            .wpec-coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
            .wpec-copy-btn, .wpec-generate-btn { background: #0073aa; color: white; border: none; padding: 10px; margin: 5px; cursor: pointer; border-radius: 4px; }
        </style>
        <script>
            jQuery('.wpec-copy-btn').click(function(e) {
                e.preventDefault();
                var code = jQuery('#code-' + jQuery(this).data('code')).text();
                navigator.clipboard.writeText(code);
                alert('Copied!');
            });
            jQuery('.wpec-generate-btn').click(function(e) {
                e.preventDefault();
                var name = jQuery(this).data('name');
                jQuery.post(wpec_ajax.ajax_url, {action: 'wpec_generate_coupon', name: name, nonce: wpec_ajax.nonce}, function(resp) {
                    jQuery('#code-' + resp.old_code).text(resp.new_code);
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('wpec_nonce', 'nonce');
        $name = sanitize_text_field($_POST['name']);
        $old_code = substr(md5(uniqid()), 0, 8);
        $new_code = substr(md5($name . time()), 0, 8);
        wp_send_json(array('old_code' => $old_code, 'new_code' => $new_code));
    }

    public function activate() {
        add_option('wpec_settings', array('affiliates' => 'Amazon:10% off|Shopify:20% off|Hostinger:50% off'));
    }
}

new WP_Exclusive_Coupons_Pro();

// Pro upsell notice
function wpec_pro_notice() {
    if (!get_option('wpec_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>WP Exclusive Coupons Pro:</strong> Upgrade to unlock unlimited features! <a href="' . admin_url('options-general.php?page=wpec-pro') . '">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'wpec_pro_notice');
?>