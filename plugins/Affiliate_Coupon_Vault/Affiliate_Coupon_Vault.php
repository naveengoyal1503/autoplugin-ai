/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from popular networks to boost your affiliate commissions.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupons', array($this, 'save_coupons'));
        add_action('wp_ajax_nopriv_save_coupons', array($this, 'save_coupons'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['coupons'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Amazon: SAVE20\nShopify: AFF10");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Enter coupons (format: Network: CODE):</label></p>
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Pro version unlocks API auto-fetch and analytics.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('num' => 3), $atts);
        $coupons = explode('\n', get_option('acv_coupons', ''));
        $output = '<div class="acv-vault">';
        shuffle($coupons);
        for ($i = 0; $i < min($atts['num'], count($coupons)); $i++) {
            if (strpos($coupons[$i], ':')) {
                list($network, $code) = explode(':', $coupons[$i], 2);
                $output .= '<div class="acv-coupon"><strong>' . esc_html($network) . '</strong>: ' . esc_html(trim($code)) . ' <a href="#" class="acv-copy" data-code="' . esc_attr(trim($code)) . '">Copy</a></div>';
            }
        }
        $output .= '</div>';
        return $output;
    }

    public function save_coupons() {
        if (current_user_can('manage_options')) {
            update_option('acv_coupons', sanitize_text_field($_POST['data']));
            wp_die('success');
        }
    }
}

new AffiliateCouponVault();

// Pro Upsell Notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock auto-API coupons, tracking & more! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49)</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');

// Frontend Assets (inline for single file)
function acv_inline_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-copy').click(function(e) {
            e.preventDefault();
            navigator.clipboard.writeText($(this).data('code'));
            $(this).text('Copied!');
        });
    });
    </script>
    <style>
    .acv-vault { background: #f9f9f9; padding: 20px; border-radius: 8px; }
    .acv-coupon { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #0073aa; }
    .acv-copy { float: right; color: #0073aa; cursor: pointer; }
    </style>
    <?php
}
add_action('wp_footer', 'acv_inline_scripts');