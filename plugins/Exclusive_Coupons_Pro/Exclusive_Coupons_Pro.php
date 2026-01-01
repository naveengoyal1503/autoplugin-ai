/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive, personalized coupon codes for your WordPress site to boost affiliate conversions and reader engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
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
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Brand1: SAVE20|Brand2: DISCOUNT15");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Coupons (Format: Brand:CODE|Brand2:CODE2)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode <code>[exclusive_coupon]</code> to display coupons on any page/post.</p>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics, custom designs, affiliate link tracking (Upgrade for $49/year).</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $coupons = get_option('ecp_coupons', "Brand1: SAVE20|Brand2: DISCOUNT15");
        $coupons_array = explode('|', $coupons);
        $output = '<div id="ecp-coupons" class="ecp-container">';
        foreach ($coupons_array as $coupon) {
            list($brand, $code) = explode(':', $coupon);
            if ($atts['brand'] && strpos($brand, $atts['brand']) === false) continue;
            $output .= '<div class="ecp-coupon"><strong>' . esc_html($brand) . '</strong>: <span class="ecp-code">' . esc_html($code) . '</span> <button class="ecp-copy" data-code="' . esc_attr($code) . '">Copy</button></div>';
        }
        $output .= '</div><p class="ecp-pro-upsell">Upgrade to Pro for analytics & more! <a href="https://example.com/pro">Get Pro</a></p>';
        return $output;
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "Amazon: PRIME10|Shopify: WPDEAL20");
        }
    }
}

ExclusiveCouponsPro::get_instance();

// Create assets directories if not exist
function ecp_create_assets() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
}
register_activation_hook(__FILE__, 'ecp_create_assets');

// Default JS
$js_content = "jQuery(document).ready(function($) { $('.ecp-copy').click(function() { var code = $(this).data('code'); navigator.clipboard.writeText(code).then(function() { $(this).text('Copied!'); }); }); });");
file_put_contents(plugin_dir_path(__FILE__) . 'assets/script.js', $js_content);

// Default CSS
$css_content = ".ecp-container { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; } .ecp-coupon { margin: 10px 0; font-size: 18px; } .ecp-code { font-family: monospace; background: #fff; padding: 5px 10px; border: 1px solid #ddd; } .ecp-copy { background: #0073aa; color: white; border: none; padding: 5px 10px; cursor: pointer; margin-left: 10px; } .ecp-pro-upsell { text-align: center; font-style: italic; color: #666; }";
file_put_contents(plugin_dir_path(__FILE__) . 'assets/style.css', $css_content);
?>