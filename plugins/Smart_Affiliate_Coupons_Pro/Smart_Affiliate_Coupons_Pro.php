/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCoupons {
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
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Pro', 'manage_options', 'sac-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sac_options', 'sac_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sac_options'); ?>
                <?php do_settings_sections('sac_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="sac_settings[affiliates]" rows="10" cols="50"><?php echo esc_textarea(get_option('sac_settings')['affiliates'] ?? ''); ?></textarea><br>
                        Format: Brand|Code|AffiliateLink|Discount%</td>
                    </tr>
                    <tr>
                        <th>Enable Tracking</th>
                        <td><input type="checkbox" name="sac_settings[tracking]" value="1" <?php checked((get_option('sac_settings')['tracking'] ?? 0)); ?>></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlimited coupons, analytics dashboard, auto-rotation. <a href="#">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $settings = get_option('sac_settings', array());
        $affiliates = explode("\n", $settings['affiliates'] ?? '');
        $coupon = '';

        foreach ($affiliates as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) >= 4 && strtolower($parts) === strtolower($atts['brand'])) {
                $unique_code = $parts[1] . '-' . uniqid();
                $coupon = '<div class="sac-coupon" data-link="' . esc_url($parts[2]) . '" data-discount="' . $parts[3] . '%">
                    <h3>' . esc_html($parts) . ' Coupon: ' . esc_html($unique_code) . '</h3>
                    <p>Save ' . $parts[3] . '%! <a href="#" class="sac-use-coupon">Use Now</a></p>
                </div>';
                break;
            }
        }

        return $coupon ?: '<p>No coupon available for ' . esc_html($atts['brand']) . '</p>';
    }

    public function activate() {
        add_option('sac_settings', array('tracking' => 1));
    }
}

SmartAffiliateCoupons::get_instance();

add_action('wp_ajax_sac_track_click', 'sac_track_click');
function sac_track_click() {
    check_ajax_referer('sac_nonce', 'nonce');
    // Track click logic (Pro feature)
    wp_die('Tracked');
}

// Inline JS for basic functionality
add_action('wp_footer', function() {
    if (is_singular()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.sac-use-coupon').click(function(e) {
                e.preventDefault();
                var $coupon = $(this).closest('.sac-coupon');
                window.open($coupon.data('link'), '_blank');
                // AJAX tracking for Pro
            });
        });
        </script>
        <style>
        .sac-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .sac-use-coupon { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }
        </style>
        <?php
    }
});