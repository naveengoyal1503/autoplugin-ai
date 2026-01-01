/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and manages exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes from brands.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Coupons Pro',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_scripts($hook) {
        if ('settings_page_exclusive-coupons-pro' !== $hook) {
            return;
        }
        wp_enqueue_script('jquery');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Brand A: SAVE20\nBrand B: DISCOUNT15");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (one per line: Brand: CODE)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode <code>[exclusive_coupons]</code> to display coupons on any page/post.</p>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics, auto-expiry, brand API integrations. <a href="https://example.com/upgrade">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('num' => 5), $atts);
        $coupons_str = get_option('ecp_coupons', '');
        $coupons = explode("\n", trim($coupons_str));
        $coupons = array_filter(array_map('trim', $coupons));
        $display = array_slice($coupons, 0, intval($atts['num']));

        if (empty($display)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=exclusive-coupons-pro') . '">Set up now</a>.</p>';
        }

        $output = '<div class="ecp-coupons" style="background:#f9f9f9; padding:20px; border-radius:8px;">
            <h3>Exclusive Deals for You!</h3>
            <ul style="list-style:none; padding:0;">
        ';
        foreach ($display as $coupon) {
            if (strpos($coupon, ':') !== false) {
                list($brand, $code) = explode(':', $coupon, 2);
                $output .= '<li style="margin:10px 0; padding:10px; background:#fff; border-left:4px solid #0073aa;">
                    <strong>' . esc_html(trim($brand)) . '</strong>: <code>' . esc_html(trim($code)) . '</code>
                </li>';
            }
        }
        $output .= '</ul></div>';
        return $output;
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "YourSite: WELCOME10\nPartner: SAVE20");
        }
    }
}

ExclusiveCouponsPro::get_instance();