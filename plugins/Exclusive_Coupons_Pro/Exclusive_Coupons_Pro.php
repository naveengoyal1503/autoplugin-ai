/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and track performance.
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
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_enqueue_script('ecp-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0');
            wp_enqueue_style('ecp-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
        }
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format)</th>
                        <td>
                            <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                            <p class="description">Enter coupons as JSON array: [{'code':'SAVE20','afflink':'https://example.com','desc':'20% off','expires':'2026-12-31'}]</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[exclusive_coupon id="0"]</code> to display coupon by index.</p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-expiration for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('ecp_coupons', '[]'), true);
        $id = intval($atts['id']);
        if (!isset($coupons[$id])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$id];
        $today = date('Y-m-d');
        if (isset($coupon['expires']) && $today > $coupon['expires']) {
            return '<p class="expired-coupon">Coupon expired!</p>';
        }
        $click_id = uniqid();
        return sprintf(
            '<div class="exclusive-coupon"><h3>%s</h3><p>%s</p><a href="%s" class="coupon-btn" data-clickid="%s" target="_blank">Get Deal (Code: %s)</a></div>',
            esc_html($coupon['desc'] ?? 'Exclusive Deal'),
            esc_html($coupon['desc'] ?? ''),
            esc_url($coupon['afflink']),
            $click_id,
            esc_html($coupon['code'])
        );
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', json_encode(array(
                array(
                    'code' => 'WELCOME10',
                    'afflink' => 'https://example.com/affiliate',
                    'desc' => '10% off first purchase',
                    'expires' => '2026-06-30'
                )
            )));
        }
    }
}

ExclusiveCouponsPro::get_instance();

// Enqueue frontend styles
function ecp_enqueue_frontend() {
    wp_enqueue_style('ecp-frontend-css', plugin_dir_url(__FILE__) . 'frontend.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'ecp_enqueue_frontend');

// Track clicks (basic)
function ecp_track_click() {
    if (isset($_GET['ecp_click'])) {
        error_log('Coupon click: ' . sanitize_text_field($_GET['ecp_click']));
    }
}
add_action('init', 'ecp_track_click');

// Pro notice
if (!function_exists('ecp_is_pro')) {
    function ecp_is_pro() { return false; }
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><p>Unlock <strong>Exclusive Coupons Pro</strong>: Unlimited coupons & analytics for $49/year. <a href="https://example.com/pro">Upgrade now</a></p></div>';
    });
}

// Frontend CSS (inline for single file)
function ecp_inline_styles() {
    ?>
    <style>
    .exclusive-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; }
    .coupon-btn { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; }
    .coupon-btn:hover { background: #005a87; }
    .expired-coupon { color: red; font-style: italic; }
    </style>
    <?php
}
add_action('wp_head', 'ecp_inline_styles');

// Admin JS (inline)
function ecp_inline_admin_js() {
    if (isset($_GET['page']) && $_GET['page'] === 'exclusive-coupons') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('textarea[name="coupons"]').on('input', function() {
                try {
                    JSON.parse($(this).val());
                    $(this).css('border-color', '#0073aa');
                } catch(e) {
                    $(this).css('border-color', 'red');
                }
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'ecp_inline_admin_js');

// Admin CSS (inline)
function ecp_inline_admin_css() {
    if (isset($_GET['page']) && $_GET['page'] === 'exclusive-coupons') {
        ?>
        <style>
        .form-table textarea { font-family: monospace; }
        </style>
        <?php
    }
}
add_action('admin_head', 'ecp_inline_admin_css');
