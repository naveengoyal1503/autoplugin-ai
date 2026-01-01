/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking, boosting conversions for bloggers and eCommerce sites.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (get_option('acv_pro_version')) {
            // Pro features hook here
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_affiliates', sanitize_textarea_field($_POST['affiliates']));
            update_option('acv_default_discount', sanitize_text_field($_POST['discount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliates = get_option('acv_affiliates', "Brand1|https://affiliate.link1.com?coupon={code}\nBrand2|https://affiliate.link2.com?coupon={code}");
        $discount = get_option('acv_default_discount', '10%');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="affiliates" rows="10" cols="50"><?php echo esc_textarea($affiliates); ?></textarea><br>
                        Format: Brand|Affiliate URL with {code} placeholder</td>
                    </tr>
                    <tr>
                        <th>Default Discount</th>
                        <td><input type="text" name="discount" value="<?php echo esc_attr($discount); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'acv_save'); ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[affiliate_coupon]</code> to display coupons anywhere.</p>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics dashboard, and API integrations for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $coupon_code = $this->generate_unique_code();
        $discount = get_option('acv_default_discount', '10%');
        $affiliates = get_option('acv_affiliates', "");
        $lines = explode('\n', $affiliates);
        $link = '';
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 2 && ($atts['brand'] === '' || strpos($parts, $atts['brand']) !== false)) {
                $link = str_replace('{code}', $coupon_code, trim($parts[1]));
                $brand = trim($parts);
                break;
            }
        }
        if (!$link) {
            $link = '#';
            $brand = 'Default';
        }
        ob_start();
        ?>
        <div id="acv-coupon" class="acv-coupon-box">
            <h3><?php echo esc_html($brand); ?> Exclusive Coupon</h3>
            <div class="acv-discount"><?php echo esc_html($discount); ?> OFF</div>
            <div class="acv-code" id="acv-code"><?php echo esc_html($coupon_code); ?></div>
            <a href="<?php echo esc_url($link); ?>" target="_blank" class="acv-button">Get Deal Now</a>
            <small>Generated for you: <?php echo date('Y-m-d H:i'); ?></small>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-coupon').on('click', '.acv-regenerate', function() {
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_generate_coupon',
                    nonce: acv_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#acv-code').text(response.data.code);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $code = $this->generate_unique_code();
        wp_send_json_success(array('code' => $code));
    }

    private function generate_unique_code() {
        return substr(strtoupper(md5(uniqid(rand(), true))), 0, 8);
    }
}

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock advanced features for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});

AffiliateCouponVault::get_instance();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    file_put_contents($assets_dir . 'style.css', ".acv-coupon-box { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; border-radius: 10px; } .acv-discount { font-size: 24px; color: #e74c3c; font-weight: bold; margin: 10px 0; } .acv-code { background: #fff; padding: 10px; font-family: monospace; font-size: 20px; margin: 10px 0; border: 1px solid #ddd; } .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; } .acv-button:hover { background: #005a87; }");
    file_put_contents($assets_dir . 'script.js', "// JS loaded via enqueue");
});