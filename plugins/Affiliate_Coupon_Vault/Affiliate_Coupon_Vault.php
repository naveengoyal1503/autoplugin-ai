/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with click tracking for higher conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
            return; // Pro version active
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
        register_setting('acv_settings', 'acv_api_key');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="acv_api_key" value="<?php echo esc_attr(get_option('acv_api_key')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Coupons (JSON)</th>
                        <td>
                            <textarea name="acv_coupons" rows="10" cols="50"><?php echo esc_textarea(get_option('acv_coupons', '[]')); ?></textarea>
                            <p class="description">Enter coupons as JSON: [{ "code": "SAVE20", "afflink": "https://aff.link", "desc": "20% off", "expiry": "2026-12-31" }]</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and auto-generation for $49/year. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts);

        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=acv-settings') . '">Set up coupons</a>.</p>';
        }

        if ($atts['id'] && isset($coupons[$atts['id']])) {
            $coupon = $coupons[$atts['id']];
        } else {
            $coupon = $coupons[array_rand($coupons)];
        }

        if (strtotime($coupon['expiry']) < time()) {
            return '<p>This coupon has expired.</p>';
        }

        ob_start();
        ?>
        <div class="acv-coupon" data-link="<?php echo esc_url($coupon['afflink']); ?>" data-nonce="<?php echo wp_create_nonce('acv_nonce'); ?>">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p><strong>Code:</strong> <span class="acv-code"><?php echo esc_html($coupon['code']); ?></span></p>
            <p><strong>Expires:</strong> <?php echo esc_html($coupon['expiry']); ?></p>
            <button class="acv-button">Get Deal (Track Click)</button>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .acv-button { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .acv-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $link = sanitize_url($_POST['link']);
        // In pro version, log to database
        if (!get_option('acv_pro_version')) {
            update_option('acv_clicks', (int)get_option('acv_clicks', 0) + 1);
        }
        wp_redirect($link);
        exit;
    }

    public function activate() {
        add_option('acv_clicks', 0);
        add_option('acv_coupons', '[]');
    }
}

// Sample JS file content (save as acv-script.js in plugin dir)
/*
jQuery(document).ready(function($) {
    $('.acv-button').click(function(e) {
        e.preventDefault();
        var $coupon = $(this).closest('.acv-coupon');
        var link = $coupon.data('link');
        var nonce = $coupon.data('nonce');
        $.post(acv_ajax.ajax_url, {
            action: 'acv_track_click',
            link: link,
            nonce: nonce
        }, function() {
            window.open(link, '_blank');
        });
    });
});
*/

AffiliateCouponVault::get_instance();

// Note: Create acv-script.js file with the JS code above for full functionality.
?>