/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Vault_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Vault Pro
 * Plugin URI: https://example.com/coupon-vault
 * Description: Generate, manage, and display exclusive custom coupons to boost affiliate sales and site engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_save_coupon', array($this, 'save_coupon'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('coupon-vault-js', plugin_dir_url(__FILE__) . 'coupon-vault.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('coupon-vault-css', plugin_dir_url(__FILE__) . 'coupon-vault.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('coupon_vault_coupons', $_POST['coupons']);
        }
        $coupons = get_option('coupon_vault_coupons', array());
        ?>
        <div class="wrap">
            <h1>Custom Coupon Vault</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format: [{'code':'SAVE20','desc':'20% off','afflink':'https://aff.link'}])</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode($coupons)); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use <code>[coupon_vault]</code> shortcode on any page/post.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = get_option('coupon_vault_coupons', array());
        if (empty($coupons)) {
            return '<p>No coupons configured. Go to Settings > Coupon Vault.</p>';
        }
        $output = '<div class="coupon-vault">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="coupon-item">';
            $output .= '<h3>' . esc_html($coupon['desc']) . '</h3>';
            $output .= '<code>' . esc_html($coupon['code']) . '</code>';
            $output .= '<a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="coupon-btn">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        if (!get_option('coupon_vault_coupons')) {
            update_option('coupon_vault_coupons', array(
                array('code' => 'WELCOME10', 'desc' => '10% off first purchase', 'afflink' => 'https://example.com/aff')
            ));
        }
    }
}

new CustomCouponVault();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.coupon-vault { display: flex; flex-wrap: wrap; gap: 20px; }
.coupon-item { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; flex: 1 1 300px; text-align: center; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.coupon-btn:hover { background: #005a87; }
</style>
<?php });

// Sample JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.coupon-item').hover(function() {
        $(this).addClass('highlight');
    }, function() {
        $(this).removeClass('highlight');
    });
});
</script>
<?php });

// Premium notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Coupon Vault Pro</strong> for unlimited coupons, analytics, and auto-expiry!</p></div>';
});