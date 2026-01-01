/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Generate and manage exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WPExclusiveCoupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpec-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('wpec-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'wp-exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('wpec_settings', 'wpec_coupons');
        add_settings_section('wpec_main', 'Coupon Settings', null, 'wpec');
        add_settings_field('wpec_coupons_list', 'Coupons', array($this, 'coupons_field'), 'wpec', 'wpec_main');
    }

    public function coupons_field() {
        $coupons = get_option('wpec_coupons', array());
        echo '<textarea name="wpec_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"name":"Coupon Name","code":"SAVE20","afflink":"https://aff.link","expiry":"2026-12-31","uses":0}</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wpec_settings');
                do_settings_sections('wpec');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics & auto-expiry for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('wpec_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $today = date('Y-m-d');
        if ($coupon['expiry'] && $today > $coupon['expiry']) {
            return '<div class="wpec-expired">Coupon expired!</div>';
        }
        ob_start();
        ?>
        <div class="wpec-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <div class="wpec-code">Code: <strong><?php echo esc_html($coupon['code']); ?></strong></div>
            <a href="<?php echo esc_url($coupon['afflink']); ?}" target="_blank" class="wpec-button">Get Deal &rsaquo;</a>
            <small>Used: <?php echo intval($coupon['uses']); ?> times</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('wpec_coupons')) {
            update_option('wpec_coupons', array(
                0 => array(
                    'name' => 'Sample 20% Off',
                    'code' => 'SAVE20',
                    'afflink' => 'https://example.com/aff',
                    'expiry' => '2026-12-31',
                    'uses' => 0
                )
            ));
        }
    }
}

new WPExclusiveCoupons();

// AJAX for tracking uses
add_action('wp_ajax_wpec_track_use', 'wpec_track_use');
add_action('wp_ajax_nopriv_wpec_track_use', 'wpec_track_use');
function wpec_track_use() {
    if (!wp_verify_nonce($_POST['nonce'], 'wpec_nonce')) {
        wp_die('Security check failed');
    }
    $id = intval($_POST['id']);
    $coupons = get_option('wpec_coupons', array());
    if (isset($coupons[$id])) {
        $coupons[$id]['uses']++;
        update_option('wpec_coupons', $coupons);
        wp_send_json_success('Tracked');
    }
    wp_send_json_error();
}

// Sample CSS (inline for single file)
function wpec_inline_styles() {
    ?>
    <style>
    .wpec-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
    .wpec-code { font-size: 24px; margin: 10px 0; }
    .wpec-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .wpec-button:hover { background: #005a87; }
    .wpec-expired { background: #ffebee; color: #c62828; padding: 20px; text-align: center; }
    </style>
    <?php
}
add_action('wp_head', 'wpec_inline_styles');

// Sample JS (inline)
function wpec_inline_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.wpec-button').click(function() {
            var $coupon = $(this).closest('.wpec-coupon');
            var id = $coupon.data('id');
            $.post(wpec_ajax.ajaxurl, {
                action: 'wpec_track_use',
                id: id,
                nonce: '<?php echo wp_create_nonce('wpec_nonce'); ?>'
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'wpec_inline_scripts');