/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupon codes with tracking, boosting conversions for bloggers and site owners.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('affiliate-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Code: SAVE10\nAffiliate Link: https://example.com/aff\nDescription: 10% off");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Format: Code: CODE&lt;br&gt;Affiliate Link: URL&lt;br&gt;Description: Text (one per line)</p>
                <p><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[affiliate_coupon id="1"]</code> to display a coupon. IDs start from 1.</p>
            <h2>Pro Features</h2>
            <p>Upgrade for click tracking, analytics, unlimited coupons, and custom designs. <a href="#pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        // Pro feature placeholder
        wp_die();
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 1), $atts);
        $coupons = explode("\n\n", get_option('acv_coupons', ''));
        if (!isset($coupons[$atts['id'] - 1])) {
            return '';
        }
        $coupon = $coupons[$atts['id'] - 1];
        preg_match('/Code: (.*?)(?:<br>|$)/', $coupon, $code_match);
        preg_match('/Affiliate Link: (.*?)(?:<br>|$)/', $coupon, $link_match);
        preg_match('/Description: (.*)/', $coupon, $desc_match);
        $code = isset($code_match[1]) ? trim($code_match[1]) : '';
        $link = isset($link_match[1]) ? trim($link_match[1]) : '';
        $desc = isset($desc_match[1]) ? trim($desc_match[1]) : '';
        if (empty($code) || empty($link)) {
            return '';
        }
        return '<div class="acv-coupon"><h3>' . esc_html($code) . '</h3><p>' . esc_html($desc) . '</p><a href="' . esc_url($link) . '" class="button acv-button" target="_blank">Get Deal</a></div>';
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Code: SAVE20\nAffiliate Link: https://yourafflink.com/?ref=20\nDescription: 20% off first purchase\n\nCode: WELCOME10\nAffiliate Link: https://yourafflink.com/?ref=10\nDescription: Welcome discount");
        }
    }
}

AffiliateCouponVault::get_instance();

// Free version limits
add_action('admin_notices', function() {
    if (get_option('acv_pro') !== 'yes') {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock tracking & unlimited coupons!</p></div>';
    }
});

// CSS
$css = '#acv-coupon { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; } .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }';
wp_add_inline_style('affiliate-coupon-css', $css);

// JS
$js = "jQuery(document).ready(function($) { $('.acv-button').on('click', function() { $(this).text('Copied!'); }); });");
wp_add_inline_script('affiliate-coupon-js', $js);

?>