/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates and manages exclusive coupon codes for your WordPress site, boosting affiliate conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPExclusiveCoupons {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('wpec_enable') !== 'yes') return;
        wp_localize_script('wpec-frontend', 'wpec_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpec-frontend', plugin_dir_url(__FILE__) . 'frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('wpec-styles', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Coupons', 'manage_options', 'wpec-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('wpec_enable', sanitize_text_field($_POST['wpec_enable']));
            update_option('wpec_prefix', sanitize_text_field($_POST['wpec_prefix']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $enable = get_option('wpec_enable', 'yes');
        $prefix = get_option('wpec_prefix', 'SAVE');
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Enable Plugin</th>
                        <td><input type="checkbox" name="wpec_enable" value="yes" <?php checked($enable, 'yes'); ?> /></td>
                    </tr>
                    <tr>
                        <th>Coupon Prefix</th>
                        <td><input type="text" name="wpec_prefix" value="<?php echo esc_attr($prefix); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'wpec_nonce')) {
            wp_die('Security check failed');
        }
        $prefix = get_option('wpec_prefix', 'SAVE');
        $code = $prefix . '-' . wp_generate_uuid4() . substr(md5(uniqid()), 0, 8);
        $coupon = array(
            'code' => $code,
            'discount' => '20% OFF',
            'expires' => date('Y-m-d', strtotime('+30 days')),
            'aff_link' => home_url(),
            'used' => 0
        );
        $coupons = get_option('wpec_coupons', array());
        $coupons[] = $coupon;
        update_option('wpec_coupons', $coupons);
        wp_send_json_success($coupon);
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_option('wpec_coupons', array());
        $output = '<div class="wpec-coupons">';
        $limit = min((int)$atts['limit'], count($coupons));
        for ($i = 0; $i < $limit; $i++) {
            if (isset($coupons[$i])) {
                $c = $coupons[$i];
                $output .= '<div class="wpec-coupon">';
                $output .= '<h3>' . esc_html($c['code']) . '</h3>';
                $output .= '<p>' . esc_html($c['discount']) . ' - Expires: ' . esc_html($c['expires']) . '</p>';
                $output .= '<a href="' . esc_url($c['aff_link']) . '" class="button">Get Deal</a>';
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        $output .= '<button id="wpec-generate" data-nonce="' . wp_create_nonce('wpec_nonce') . '">Generate New Coupon</button>';
        return $output;
    }

    public function activate() {
        update_option('wpec_enable', 'yes');
        update_option('wpec_prefix', 'SAVE');
    }
}

WPExclusiveCoupons::get_instance();

// Frontend JS (embedded)
function wpec_inline_js() {
    if (get_option('wpec_enable') === 'yes') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#wpec-generate').click(function() {
                $.post(wpec_ajax.ajaxurl, {
                    action: 'generate_coupon',
                    nonce: $(this).data('nonce')
                }, function(resp) {
                    if (resp.success) {
                        alert('New coupon generated: ' + resp.data.code);
                        location.reload();
                    }
                });
            });
        });
        </script>
        <style>
        .wpec-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
        .wpec-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 5px; width: 300px; }
        .wpec-coupon h3 { color: #0073aa; }
        #wpec-generate { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        </style>
        <?php
    }
}
add_action('wp_footer', 'wpec_inline_js');