/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive, personalized coupon codes for your WordPress site visitors, boosting affiliate conversions and reader loyalty with custom discounts from brands.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ecp-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_register_script('ecp-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0.0', true);
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        add_options_page(
            __('Exclusive Coupons Pro', 'exclusive-coupons-pro'),
            __('Coupons Pro', 'exclusive-coupons-pro'),
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['ecp_coupons']));
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'exclusive-coupons-pro') . '</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Brand1: SAVE20\nBrand2: DISCOUNT15");
        ?>
        <div class="wrap">
            <h1><?php _e('Exclusive Coupons Pro', 'exclusive-coupons-pro'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Coupons List', 'exclusive-coupons-pro'); ?></th>
                        <td>
                            <textarea name="ecp_coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                            <p class="description"><?php _e('Enter coupons one per line: Brand: CODE (e.g., Amazon: SAVE20)', 'exclusive-coupons-pro'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Changes', 'exclusive-coupons-pro'), 'primary', 'ecp_save'); ?>
            </form>
            <h2><?php _e('Usage', 'exclusive-coupons-pro'); ?></h2>
            <p><?php _e('Use shortcode: [exclusive_coupon brand="Brand1"] or [exclusive_coupon] for random.', 'exclusive-coupons-pro'); ?></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $coupons_str = get_option('ecp_coupons', "");
        $coupons = array_filter(array_map('trim', explode("\n", $coupons_str)));
        if (empty($coupons)) {
            return '<p>' . __('No coupons configured. Go to settings.', 'exclusive-coupons-pro') . '</p>';
        }
        if (!empty($atts['brand'])) {
            foreach ($coupons as $coupon) {
                list($c_brand, $code) = explode(':', $coupon, 2);
                if (strtolower(trim($c_brand)) === strtolower(trim($atts['brand']))) {
                    return $this->render_coupon(trim($c_brand), trim($code));
                }
            }
            return '<p>' . sprintf(__('No coupon for %s.', 'exclusive-coupons-pro'), esc_html($atts['brand'])) . '</p>';
        } else {
            $random_coupon = $coupons[array_rand($coupons)];
            list($brand, $code) = explode(':', $random_coupon, 2);
            return $this->render_coupon(trim($brand), trim($code));
        }
    }

    private function render_coupon($brand, $code) {
        $affiliate_url = 'https://your-affiliate-link.com/?coupon=' . urlencode($code); // Replace with dynamic affiliate links in premium
        ob_start();
        ?>
        <div style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center; max-width: 400px; margin: 20px auto;">
            <h3 style="color: #007cba;">🎉 Exclusive Deal for You! 🎉</h3>
            <p><strong><?php echo esc_html($brand); ?></strong></p>
            <h2 style="color: #d63638; font-size: 2em;"> <?php echo esc_html(strtoupper($code)); ?> </h2>
            <p>Save now with this reader-exclusive coupon!</p>
            <a href="<?php echo esc_url($affiliate_url); ?>" target="_blank" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">🛒 Shop & Save</a>
            <p style="font-size: 0.9em; margin-top: 15px; color: #666;">Limited time offer • Exclusive to our readers</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "Amazon: EXCLUSIVE20\nShopify: BLOG15");
        }
    }
}

new ExclusiveCouponsPro();

// Premium upsell notice
function ecp_premium_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>' . sprintf(__('Upgrade to %s Premium for unlimited coupons, analytics, and API integrations!', 'exclusive-coupons-pro'), 'Exclusive Coupons Pro') . '</p></div>';
}
add_action('admin_notices', 'ecp_premium_notice');

// Enqueue dummy CSS/JS placeholders (minified in real)
function ecp_enqueue_admin_assets($hook) {
    if ($hook !== 'settings_page_exclusive-coupons-pro') return;
    wp_enqueue_style('ecp-admin-style');
    wp_enqueue_script('ecp-admin-script');
}
add_action('admin_enqueue_scripts', 'ecp_enqueue_admin_assets');
?>