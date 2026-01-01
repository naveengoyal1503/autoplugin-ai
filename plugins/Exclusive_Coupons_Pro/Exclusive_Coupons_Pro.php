/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays personalized, trackable coupon codes for your WordPress site to boost affiliate conversions and reader engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (get_option('ecp_pro_version')) {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ecp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Brand1: DISCOUNT10\nBrand2: SAVE20");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea><br>
                <p>Format: Brand: COUPONCODE (one per line)</p>
                <input type="submit" name="ecp_save" value="Save Coupons" class="button-primary">
            </form>
            <?php if (!get_option('ecp_pro_version')) { ?>
            <p><strong>Upgrade to Pro</strong> for unlimited coupons, analytics & custom branding. <a href="#">Get Pro ($49/year)</a></p>
            <?php } ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $coupons = explode("\n", get_option('ecp_coupons', ''));
        $personalized = '';
        foreach ($coupons as $coupon) {
            $parts = explode(':', trim($coupon), 2);
            if (count($parts) == 2 && stripos($parts, $atts['brand']) !== false) {
                $code = $parts[1];
                $user_id = get_current_user_id() ?: wp_generate_uuid4();
                $track_code = $code . '-' . substr(md5($user_id . time()), 0, 8);
                $personalized = "<div id='ecp-coupon'><strong>Your Exclusive Code:</strong> <span id='ecp-code'>$track_code</span><br><small>Generated for you on " . date('Y-m-d H:i') . '</small></div>';
                break;
            }
        }
        return $personalized ?: '<p>No coupon available for this brand.</p>';
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ecp_nonce', 'nonce');
        $brand = sanitize_text_field($_POST['brand']);
        // Simulate generation (Pro would integrate with APIs)
        $code = 'EXC' . wp_rand(10000, 99999);
        wp_send_json_success(array('code' => $code));
    }

    public function pro_notice() {
        echo '<div class="notice notice-success"><p>Exclusive Coupons Pro is activated! Enjoy unlimited features.</p></div>';
    }
}

new ExclusiveCouponsPro();

// Freemium check
function ecp_is_pro() {
    return get_option('ecp_pro_version');
}

// Sample JS file content (save as ecp-script.js in plugin dir)
/*
jQuery(document).ready(function($) {
    $('#generate-coupon').click(function() {
        $.post(ecp_ajax.ajax_url, {
            action: 'generate_coupon',
            brand: $('#coupon-brand').val(),
            nonce: ecp_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#ecp-code').text(response.data.code);
            }
        });
    });
});
*/
?>