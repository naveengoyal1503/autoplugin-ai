/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate, manage, and display exclusive affiliate coupons with auto-expiration, tracking, and revenue dashboards.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_ecp_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-frontend', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ecp_nonce')));
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_save_coupon'])) {
            update_option('ecp_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <?php wp_nonce_field('ecp_admin'); ?>
                <table class="form-table">
                    <tr>
                        <th>Add Coupon</th>
                        <td>
                            <input type="text" name="coupons[code]" placeholder="Coupon Code" style="width:200px;">
                            <input type="text" name="coupons[url]" placeholder="Affiliate URL" style="width:300px;">
                            <input type="date" name="coupons[expiry]" placeholder="Expiry Date">
                            <input type="text" name="coupons[description]" placeholder="Description">
                        </td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="ecp_save_coupon" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Existing Coupons</h2>
            <ul>
                <?php foreach ($coupons as $index => $coupon): ?>
                    <li><?php echo esc_html($coupon['code']); ?> - <a href="<?php echo esc_url($coupon['url']); ?>"><?php echo esc_html($coupon['url']); ?></a> (Expires: <?php echo esc_html($coupon['expiry']); ?>)</li>
                <?php endforeach; ?>
            </ul>
            <h2>Shortcode: [ecp_coupons]</h2>
            <p>Use this shortcode to display coupons on any page or post.</p>
        </div>
        <?php
    }

    public function track_click() {
        check_ajax_referer('ecp_nonce', 'nonce');
        $coupon_code = sanitize_text_field($_POST['code']);
        $coupons = get_option('ecp_coupons', array());
        foreach ($coupons as &$coupon) {
            if ($coupon['code'] === $coupon_code && strtotime($coupon['expiry']) > time()) {
                $coupon['clicks'] = isset($coupon['clicks']) ? $coupon['clicks'] + 1 : 1;
                update_option('ecp_coupons', $coupons);
                wp_send_json_success();
            }
        }
        wp_send_json_error();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', array());
        }
    }
}

ExclusiveCouponsPro::get_instance();

// Shortcode to display coupons
function ecp_shortcode($atts) {
    $coupons = get_option('ecp_coupons', array());
    $output = '<div class="ecp-coupons">';
    foreach ($coupons as $coupon) {
        if (strtotime($coupon['expiry']) > time()) {
            $output .= '<div class="ecp-coupon">';
            $output .= '<h3>' . esc_html($coupon['code']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<a href="#" class="ecp-btn" data-code="' . esc_attr($coupon['code']) . '">Get Deal</a>';
            $output .= '</div>';
        }
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('ecp_coupons', 'ecp_shortcode');

// Pro upgrade notice
function ecp_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons, revenue tracking, and analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
}
add_action('admin_notices', 'ecp_pro_notice');