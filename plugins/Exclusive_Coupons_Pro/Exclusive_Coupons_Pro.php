/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site visitors, boosting affiliate conversions and engagement.
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
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Coupons Pro Settings',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('ecp_promo_text', sanitize_text_field($_POST['promo_text']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "BrandA:20OFF\nBrandB:FREEDELIVERY");
        $promo_text = get_option('ecp_promo_text', 'Get your exclusive coupon!');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Format: Brand:Code)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Promo Text</th>
                        <td><input type="text" name="promo_text" value="<?php echo esc_attr($promo_text); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $coupons = explode("\n", get_option('ecp_coupons', ''));
        $promo_text = get_option('ecp_promo_text', 'Get your exclusive coupon!');
        $personalized_code = '';
        foreach ($coupons as $coupon) {
            $parts = explode(':', trim($coupon), 2);
            if (count($parts) === 2 && stripos($parts, $atts['brand']) !== false) {
                $user_id = get_current_user_id();
                $suffix = $user_id ? '-' . substr(md5('ecp-' . $user_id . time()), 0, 4) : '';
                $personalized_code = $parts[1] . $suffix;
                break;
            }
        }
        if (!$personalized_code) {
            $personalized_code = 'EXCLUSIVE-' . substr(md5(time()), 0, 6);
        }
        ob_start();
        ?>
        <div id="ecp-coupon" class="ecp-coupon-box">
            <h3><?php echo esc_html($promo_text); ?></h3>
            <div class="ecp-code"><?php echo esc_html($personalized_code); ?></div>
            <p>Copy and use at checkout for your discount!</p>
            <button class="ecp-copy-btn" data-clipboard-text="<?php echo esc_attr($personalized_code); ?>">Copy Code</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ecp_coupons', "BrandA:20OFF\nBrandB:FREEDELIVERY");
        add_option('ecp_promo_text', 'Get your exclusive coupon!');
    }
}

new ExclusiveCouponsPro();

// Premium upsell notice
function ecp_premium_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock premium features like analytics, custom branding, and unlimited coupons for $49/year! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'ecp_premium_notice');

// Create assets directories if needed
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Inline styles and scripts for self-contained
function ecp_inline_assets() {
    ?>
    <style>
    .ecp-coupon-box { border: 2px solid #0073aa; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; max-width: 400px; margin: 20px auto; }
    .ecp-code { font-size: 24px; font-weight: bold; color: #0073aa; background: white; padding: 10px; border-radius: 5px; margin: 10px 0; letter-spacing: 3px; }
    .ecp-copy-btn { background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
    .ecp-copy-btn:hover { background: #005a87; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.ecp-copy-btn').click(function() {
            var code = $(this).data('clipboard-text');
            navigator.clipboard.writeText(code).then(function() {
                $(this).text('Copied!');
            }.bind(this));
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ecp_inline_assets');