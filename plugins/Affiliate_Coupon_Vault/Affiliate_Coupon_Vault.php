/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons and deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupons', array($this, 'save_coupons'));
        add_action('wp_ajax_nopriv_save_coupons', array($this, 'save_coupons'));
    }

    public function init() {
        register_setting('affiliate_coupon_settings', 'affiliate_coupons_data');
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('affiliate_coupon_settings'); ?>
                <?php do_settings_sections('affiliate_coupon_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons JSON</th>
                        <td><textarea name="affiliate_coupons_data" rows="10" cols="50"><?php echo esc_textarea(get_option('affiliate_coupons_data', '[]')); ?></textarea><br>
                        Format: [{"name":"Deal Name","code":"COUPON10","desc":"10% off","afflink":"https://aff.link","img":"image.jpg","expiry":"2026-12-31"}]</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-css', plugin_dir_url(__FILE__) . 'coupon.css', array(), '1.0.0');
        wp_localize_script('affiliate-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = json_decode(get_option('affiliate_coupons_data', '[]'), true);
        if (empty($coupons)) {
            return '<p>No coupons configured yet.</p>';
        }
        $output = '<div class="coupon-vault">';
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            if (isset($coupon['expiry']) && strtotime($coupon['expiry']) < time()) continue;
            $output .= '<div class="coupon-item">';
            if (!empty($coupon['img'])) {
                $output .= '<img src="' . esc_url($coupon['img']) . '" alt="' . esc_attr($coupon['name']) . '" style="max-width:200px;">';
            }
            $output .= '<h3>' . esc_html($coupon['name']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $output .= '<p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>';
            $output .= '<a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="coupon-btn" rel="nofollow">Get Deal</a>';
            $output .= '</div>';
            $count++;
        }
        $output .= '</div>';
        return $output;
    }

    public function save_coupons() {
        if (!wp_verify_nonce($_POST['nonce'], 'coupon_nonce')) {
            wp_die('Security check failed');
        }
        update_option('affiliate_coupons_data', sanitize_textarea_field($_POST['coupons_data']));
        wp_send_json_success('Coupons saved');
    }
}

new AffiliateCouponVault();

// Premium teaser
function acv_premium_teaser() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for API auto-updates, analytics, and more! <a href="https://example.com/pro" target="_blank">Learn more</a></p></div>';
}
add_action('admin_notices', 'acv_premium_teaser');

// Minimal CSS - inline for single file

function acv_add_inline_css() {
    ?>
    <style>
    .coupon-vault { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    .coupon-item { border: 1px solid #ddd; padding: 20px; border-radius: 8px; text-align: center; background: #f9f9f9; }
    .coupon-item h3 { color: #333; margin: 0 0 10px; }
    .coupon-btn { display: inline-block; background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .coupon-btn:hover { background: #e55a2b; }
    </style>
    <?php
}
add_action('wp_head', 'acv_add_inline_css');

// Minimal JS - inline

function acv_add_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Simple copy code functionality
        $('.coupon-item').on('click', '.copy-code', function() {
            navigator.clipboard.writeText($(this).data('code')).then(function() {
                alert('Code copied!');
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'acv_add_inline_js');