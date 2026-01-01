/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generate, manage, and display exclusive affiliate coupons with personalized discount codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Exclusive_Coupons_Pro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (get_option('wpecp_license') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function pro_notice() {
        echo '<div class="notice notice-warning"><p>Upgrade to <strong>WP Exclusive Coupons Pro</strong> for unlimited coupons and analytics!</p></div>';
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Coupons', 'manage_options', 'wp-exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupons'])) {
            update_option('wpecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('wpecp_coupons', "Coupon Code: SAVE10\nAffiliate Link: https://example.com/affiliate\nDescription: 10% off first purchase");
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50" style="width:100%;" placeholder="Coupon Code: CODE\nAffiliate Link: URL\nDescription: Details\n\nAdd more lines for multiple coupons."><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="save_coupons" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[exclusive_coupon id="1"]</code> or <code>[exclusive_coupons]</code> for all.</p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpecp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wpecp-script', 'wpecp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode('\n\n', get_option('wpecp_coupons', ''));
        if ($atts['id']) {
            $id = intval($atts['id']) - 1;
            if (isset($coupons[$id])) {
                return $this->render_coupon($coupons[$id]);
            }
        } else {
            $output = '';
            foreach ($coupons as $coupon) {
                if (trim($coupon)) {
                    $output .= $this->render_coupon($coupon);
                }
            }
            return $output;
        }
        return '';
    }

    private function render_coupon($coupon_data) {
        $lines = explode('\n', $coupon_data);
        $code = $link = $desc = '';
        foreach ($lines as $line) {
            if (strpos($line, 'Code:') !== false) $code = trim(str_replace('Code:', '', $line));
            if (strpos($line, 'Link:') !== false) $link = trim(str_replace('Link:', '', $line));
            if (strpos($line, 'Description:') !== false) $desc = trim(str_replace('Description:', '', $line));
        }
        $unique_code = $code . '-' . wp_generate_uuid4();
        ob_start();
        ?>
        <div class="exclusive-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3>🎉 Exclusive Deal!</h3>
            <p><strong>Your Code:</strong> <code><?php echo esc_html($unique_code); ?></code></p>
            <?php if ($desc): ?><p><?php echo esc_html($desc); ?></p><?php endif; ?>
            <a href="<?php echo esc_url($link); ?>?coupon=<?php echo urlencode($unique_code); ?>" target="_blank" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Redeem Now & Save!</a>
            <p style="font-size: 12px; color: #666;">Limited time offer - Generated for you exclusively!</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.exclusive-coupon .button').click(function() {
                $.post(wpecp_ajax.ajax_url, {action: 'generate_coupon', code: '<?php echo esc_js($unique_code); ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        // Track usage for analytics (premium feature)
        wp_die('Coupon generated!');
    }
}

new WP_Exclusive_Coupons_Pro();

// Premium upsell notice
function wpecp_pro_upsell() {
    if (current_user_can('manage_options')) {
        echo '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; margin: 20px 0; border-radius: 10px; text-align: center;">
            <h3>🚀 Unlock WP Exclusive Coupons Pro</h3>
            <p>Unlimited coupons, analytics, auto-expiration & more for just $49/year!</p>
            <a href="https://example.com/pro" style="background: white; color: #667eea; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Upgrade Now</a>
        </div>';
    }
}
add_action('wp_footer', 'wpecp_pro_upsell');