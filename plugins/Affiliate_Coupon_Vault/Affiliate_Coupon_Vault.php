/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays personalized affiliate coupons and exclusive deals to boost conversions and commissions.
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
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'affiliate-coupon-vault') {
            wp_enqueue_script('jquery');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Amazon:10%OFF\nShopify:DEAL25");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Format: Merchant:Code:Description)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'acv_save'); ?>
            </form>
            <h2>Pro Features (Upgrade for $49/year)</h2>
            <ul>
                <li>Unlimited coupons</li>
                <li>Analytics dashboard</li>
                <li>Custom designs</li>
                <li>API integrations</li>
            </ul>
            <a href="https://example.com/pro" class="button button-primary">Upgrade to Pro</a>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('num' => 5), $atts);
        $coupons_text = get_option('acv_coupons', "");
        $coupons = explode("\n", trim($coupons_text));
        $display_coupons = array_slice(array_filter($coupons), 0, intval($atts['num']));
        $output = '<div class="acv-vault">';
        foreach ($display_coupons as $coupon) {
            if (strpos($coupon, ':') !== false) {
                list($merchant, $code, $desc) = array_pad(explode(':', $coupon, 3), 3, '');
                $output .= '<div class="acv-coupon">'
                         . '<h4>' . esc_html($merchant) . '</h4>'
                         . '<p>' . esc_html($desc) . '</p>'
                         . '<strong>Code: ' . esc_html($code) . '</strong>'
                         . '<button class="acv-copy" data-code="' . esc_attr($code) . '">Copy</button>'
                         . '</div>';
            }
        }
        $output .= '</div>';
        return $output;
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $coupons_text = get_option('acv_coupons', "");
        $coupons = explode("\n", trim($coupons_text));
        if (!empty($coupons)) {
            $random_coupon = $coupons[array_rand($coupons)];
            wp_send_json_success(array('coupon' => $random_coupon));
        } else {
            wp_send_json_error('No coupons available.');
        }
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Amazon:10%OFF\nShopify:DEAL25");
        }
    }
}

new AffiliateCouponVault();

// Dummy CSS and JS files would be created separately, but for single-file, inline them

function acv_inline_assets() {
    ?>
    <style>
    .acv-vault { max-width: 600px; margin: 20px 0; }
    .acv-coupon { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }
    .acv-copy { background: #0073aa; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
    .acv-copy:hover { background: #005a87; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-copy').click(function() {
            navigator.clipboard.writeText($(this).data('code')).then(function() {
                $(this).text('Copied!');
            }.bind(this));
        });
    });
    </script>
    <?php
}
add_action('wp_head', 'acv_inline_assets');

// Pro upsell notice
add_action('admin_notices', function() {
    if (!function_exists('is_plugin_active') || !is_plugin_active(plugin_basename(__FILE__))) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock <strong>Pro</strong> for unlimited coupons & analytics! <a href="https://example.com/pro">Upgrade Now ($49/year)</a></p></div>';
});