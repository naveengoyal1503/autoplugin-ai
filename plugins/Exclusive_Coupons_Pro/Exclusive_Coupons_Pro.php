/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site, boosting affiliate conversions and reader engagement with custom discounts from brands.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        if (get_option('ecp_pro_version') !== '1.0') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'ecp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['ecp_coupons']));
            update_option('ecp_pro_version', '1.0');
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Brand1|DISCOUNT10|50% off|https://affiliate.link1\nBrand2|SAVE20|20% off|https://affiliate.link2");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <p><label>Coupons (format: Brand|Code|Description|Affiliate Link, one per line):</label></p>
                <textarea name="ecp_coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="ecp_save" class="button-primary" value="Save Settings"></p>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and custom designs for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'random'), $atts);
        $coupons = explode('\n', get_option('ecp_coupons', ''));
        if (empty($coupons)) return '';
        $coupon = $coupons[array_rand($coupons)];
        $parts = explode('|', $coupon);
        if (count($parts) < 4) return '';
        $visitor_id = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        $personal_code = strtoupper(substr($visitor_id, 0, 8));
        ob_start();
        ?>
        <div class="ecp-coupon" data-afflink="<?php echo esc_url($parts[3]); ?>">
            <h3><?php echo esc_html($parts); ?> Exclusive Deal</h3>
            <p><strong>Your Code: <?php echo $personal_code; ?></strong><br><?php echo esc_html($parts[2]); ?></p>
            <a href="#" class="ecp-copy" data-code="<?php echo $personal_code; ?>">Copy Code</a>
            <a href="<?php echo esc_url($parts[3]); ?>&coupon=<?php echo $personal_code; ?>" class="ecp-button" target="_blank">Shop Now</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Upgrade to pro for advanced features! <a href="options-general.php?page=ecp-settings">Settings</a></p></div>';
    }

    public function activate() {
        update_option('ecp_pro_version', 'free');
    }
}

new ExclusiveCouponsPro();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.ecp-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; border-radius: 10px; }
.ecp-copy { background: #ffcc00; color: #000; padding: 10px 20px; text-decoration: none; margin-right: 10px; border-radius: 5px; }
.ecp-button { background: #007cba; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.ecp-copy:hover, .ecp-button:hover { opacity: 0.8; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) {
    $('.ecp-copy').click(function(e) {
        e.preventDefault();
        navigator.clipboard.writeText($(this).data('code')).then(function() {
            $(this).text('Copied!');
        }.bind(this));
    });
});</script>
<?php });