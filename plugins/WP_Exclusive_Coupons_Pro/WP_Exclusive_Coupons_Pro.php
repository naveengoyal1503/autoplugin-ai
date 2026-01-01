/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Exclusive_Coupons_Pro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wpec_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_wpec_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_wpec_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('wpec_pro_active', false);
        add_option('wpec_coupons', array());
    }

    public function admin_menu() {
        add_options_page(
            'WP Exclusive Coupons Pro',
            'Coupons Pro',
            'manage_options',
            'wpec-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['wpec_save'])) {
            update_option('wpec_pro_active', !empty($_POST['wpec_pro_key']));
            update_option('wpec_coupons', sanitize_text_field($_POST['wpec_coupons'] ?? ''));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $pro_active = get_option('wpec_pro_active', false);
        $coupons = get_option('wpec_coupons', '');
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post">
                <?php if (!$pro_active): ?>
                <p><label>Pro Key: <input type="text" name="wpec_pro_key" placeholder="Enter pro key for unlimited features"></label></p>
                <?php endif; ?>
                <p><label>Coupons JSON ("code":"discount":"link"): <textarea name="wpec_coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></label></p>
                <p><?php _e('Use shortcode [wpec_coupon id="1"] to display.', 'wp-exclusive-coupons-pro'); ?></p>
                <p class="description"><?php _e('Pro: Unlimited coupons, analytics. Free: 3 max.', 'wp-exclusive-coupons-pro'); ?></p>
                <input type="submit" name="wpec_save" class="button-primary" value="Save">
                <?php if (!$pro_active): ?>
                <p><a href="https://example.com/pro-upgrade" target="_blank" class="button">Upgrade to Pro ($49/yr)</a></p>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpec-script', plugin_dir_url(__FILE__) . 'wpec.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wpec-script', 'wpec_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => '1'), $atts);
        $coupons_json = get_option('wpec_coupons', '');
        $coupons = json_decode($coupons_json, true) ?: array();
        $is_pro = get_option('wpec_pro_active', false);
        $max_free = $is_pro ? 999 : 3;

        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }

        $coupon = $coupons[$atts['id']];
        $personal_code = $this->generate_personal_code();

        ob_start();
        ?>
        <div class="wpec-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3>Exclusive Deal: <?php echo esc_html($coupon['code']); ?></h3>
            <p>Save <?php echo esc_html($coupon['discount']); ?>! Your personal code: <strong id="wpec-code"><?php echo esc_html($personal_code); ?></strong></p>
            <a href="<?php echo esc_url($coupon['link'] . '?coupon=' . $personal_code); ?>" class="button" target="_blank">Shop Now & Save</a>
            <button class="button-secondary" onclick="wpecRegenerate(this)">New Code</button>
        </div>
        <style>
        .wpec-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; }
        .wpec-coupon .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style>
        <script>
        function wpecRegenerate(btn) {
            jQuery.post(wpec_ajax.ajax_url, {action: 'wpec_generate_coupon', id: jQuery(btn).closest('.wpec-coupon').data('id')}, function(code) {
                jQuery('#wpec-code').text(code);
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    public function generate_personal_code() {
        return substr(md5(uniqid()), 0, 8);
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wpec_nonce')) {
            wp_die();
        }
        echo $this->generate_personal_code();
        wp_die();
    }
}

new WP_Exclusive_Coupons_Pro();

// Freemium upsell notice
function wpec_admin_notice() {
    if (!get_option('wpec_pro_active')) {
        echo '<div class="notice notice-info"><p><strong>WP Exclusive Coupons Pro:</strong> Unlock unlimited coupons & analytics. <a href="options-general.php?page=wpec-pro">Upgrade now ($49/yr)</a></p></div>';
    }
}
add_action('admin_notices', 'wpec_admin_notice');
