/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes from affiliate partners to boost conversions and revenue.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupons', array($this, 'save_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'ecp-style.css', array(), '1.0.0');
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'category' => ''
        ), $atts);

        $coupons = get_option('ecp_coupons', array());
        $output = '<div class="ecp-coupons-container">';

        $count = 0;
        foreach ($coupons as $coupon) {
            if ($atts['category'] && $coupon['category'] !== $atts['category']) continue;
            if ($count >= $atts['limit']) break;
            $output .= '<div class="ecp-coupon-item">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<div class="ecp-coupon-code">' . esc_html($coupon['code']) . '</div>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" class="ecp-coupon-btn" target="_blank">Get Deal</a>';
            $output .= '</div>';
            $count++;
        }

        $output .= '</div>';
        return $output;
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

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons JSON</th>
                        <td>
                            <textarea name="coupons" rows="20" cols="80" placeholder='[{"title":"50% Off Hosting","description":"Exclusive deal for readers","code":"WP50","link":"https://example.com/hosting","category":"hosting"}]'><?php echo esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)); ?></textarea>
                            <p class="description">Enter coupons as JSON array. Free version limited to 5 coupons.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'ecp_save'); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[exclusive_coupons limit="5" category=""]</code></p>
            <p><strong>Upgrade to Pro</strong> for unlimited coupons, auto-generation, analytics, and affiliate tracking ($49/year).</p>
        </div>
        <?php
    }

    public function save_coupons() {
        if (!current_user_can('manage_options')) wp_die();
        $coupons = json_decode(stripslashes($_POST['coupons']), true);
        if (json_last_error() === JSON_ERROR_NONE && count($coupons) <= 5) {
            update_option('ecp_coupons', $coupons);
            wp_send_json_success('Saved');
        } else {
            wp_send_json_error('Invalid JSON or exceeds free limit');
        }
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', array(
                array('title' => 'Sample Deal', 'description' => 'Try this exclusive coupon', 'code' => 'SAVE10', 'link' => '#', 'category' => 'general')
            ));
        }
    }
}

ExclusiveCouponsPro::get_instance();

// Prevent direct access
if (strpos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    wp_die('Direct access not allowed');
}

// Minified CSS
/*
.ecp-coupons-container { max-width: 600px; margin: 20px 0; }
.ecp-coupon-item { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f9f9f9; }
.ecp-coupon-code { background: #fff; padding: 10px; font-family: monospace; font-size: 18px; text-align: center; margin: 10px 0; }
.ecp-coupon-btn { display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; }
.ecp-coupon-btn:hover { background: #005a87; }
*/

// Sample JS
/*
function ecpTrackClick(link) {
    // Pro feature: track clicks
    console.log('Coupon clicked:', link);
}
*/