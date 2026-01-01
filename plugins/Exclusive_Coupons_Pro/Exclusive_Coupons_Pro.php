/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized discount codes to boost conversions and revenue.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons JSON</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea><br>
                        Format: [{ "name": "Brand", "code": "SAVE20", "url": "https://affiliate-link.com", "desc": "20% off" }]</td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons'); ?>
            </form>
            <p>Use shortcode: <code>[exclusive_coupon id="1"]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('ecp_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div class="ecp-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?> Exclusive Deal</h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <div class="ecp-code"><?php echo esc_html($unique_code); ?></div>
            <a href="<?php echo esc_url($coupon['url'] . '?code=' . $unique_code); ?>" class="ecp-button" target="_blank">Get Deal Now</a>
            <small>Copy code: <span class="code"><?php echo esc_html($unique_code); ?></span></small>
        </div>
        <script>
        jQuery('.ecp-coupon').on('click', '.code', function() {
            navigator.clipboard.writeText(jQuery(this).text());
            jQuery(this).text('Copied!');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', json_encode(array(
                array('name' => 'Demo Brand', 'code' => 'DEMO20', 'url' => '#', 'desc' => '20% Off Demo')
            )));
        }
    }
}

new ExclusiveCouponsPro();

/* Premium Upsell */
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !defined('ECP_PRO')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, analytics, and auto-expiration with <a href="https://example.com/premium" target="_blank">Premium version</a> for $49/year!</p></div>';
    }
});

/* Style */
function ecp_add_style() {
    echo '<style>
    .ecp-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
    .ecp-code { font-size: 24px; font-weight: bold; color: #0073aa; margin: 10px 0; }
    .ecp-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .ecp-button:hover { background: #005a87; }
    .code { cursor: pointer; background: #eee; padding: 5px; border-radius: 3px; }
    </style>';
}
add_action('wp_head', 'ecp_add_style');
add_action('admin_head', 'ecp_add_style');