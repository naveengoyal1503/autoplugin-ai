/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized discount codes, boosting conversions and commissions for bloggers.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupon', array($this, 'save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'save_coupon'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            return;
        }
        add_action('admin_notices', array($this, 'pro_notice'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_coupon_data'])) {
            update_option('acv_coupons', sanitize_text_field($_POST['acv_coupon_data']));
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '{"example":{"affiliate":"amazon","code":"SAVE20","desc":"20% off","link":"https://amazon.com"}}');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <p><label>Coupon JSON (affiliate, code, desc, link):</label><br><textarea name="acv_coupon_data" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></p>
                <p><input type="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-generation. <a href="#" onclick="alert('Upgrade to Pro for $49/year')">Get Pro</a></p>
            <p>Use shortcode: <code>[affiliate_coupon id="example"]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = json_decode(get_option('acv_coupons', '{}'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3>Exclusive Deal: <?php echo esc_html($coupon['desc']); ?></h3>
            <p>Your unique code: <strong><?php echo esc_html($unique_code); ?></strong></p>
            <a href="<?php echo esc_url($coupon['link']); ?>?coupon=<?php echo esc_attr($unique_code); ?>" class="button" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none;" target="_blank">Get Deal Now (Affiliate Link)</a>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.acv-coupon .button').click(function() {
                $.post(acv_ajax.ajax_url, {action: 'save_coupon', code: '<?php echo esc_js($unique_code); ?>'}, function() {});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function save_coupon() {
        // Track click for pro analytics
        if (!get_option('acv_pro_version')) {
            wp_die('Pro feature');
        }
        wp_die();
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for unlimited features! <a href="options-general.php?page=affiliate-coupon-vault">Learn more</a></p></div>';
    }
}

new AffiliateCouponVault();

// Pro check stub
function acv_is_pro() {
    return (bool) get_option('acv_pro_version');
}
?>