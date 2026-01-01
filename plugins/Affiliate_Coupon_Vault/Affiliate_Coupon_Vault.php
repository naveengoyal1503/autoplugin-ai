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
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            return; // Pro version active
        }
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_options', 'acv_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_options'); ?>
                <?php do_settings_sections('acv_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="acv_settings[links]" rows="10" cols="50"><?php echo esc_textarea(get_option('acv_settings')['links'] ?? ''); ?></textarea><br>
                        Format: Product Name|Affiliate URL|Discount Code</td>
                    </tr>
                    <tr>
                        <th>Pro Upgrade</th>
                        <td><a href="https://example.com/pro" target="_blank" class="button button-primary">Upgrade to Pro ($49/year)</a></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $settings = get_option('acv_settings', array('links' => ''));
        $links = explode("\n", trim($settings['links']));
        $coupons = array();
        foreach ($links as $line) {
            if (trim($line)) {
                list($name, $url, $code) = explode('|', trim($line), 3);
                $coupons[] = array('name' => trim($name), 'url' => trim($url), 'code' => trim($code));
            }
        }
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Configure now</a>.</p>';
        }
        ob_start();
        ?>
        <div id="acv-vault" class="acv-coupon-vault">
            <h3>Exclusive Coupons</h3>
            <div id="acv-coupons">
                <?php foreach ($coupons as $i => $coupon): ?>
                <div class="acv-coupon-item" data-id="<?php echo $i; ?>">
                    <h4><?php echo esc_html($coupon['name']); ?></h4>
                    <input type="text" class="acv-code" readonly value="<?php echo esc_attr($coupon['code']); ?>" onclick="this.select()">
                    <a href="#" class="acv-generate">Generate Personal Code</a>
                    <a href="<?php echo esc_url($coupon['url']); ?>" class="button acv-shop" target="_blank">Shop Now & Save</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <style>
        .acv-coupon-vault { max-width: 600px; margin: 20px 0; }
        .acv-coupon-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .acv-code { width: 100%; padding: 10px; font-size: 18px; background: #f9f9f9; border: 1px solid #ccc; }
        .acv-generate, .acv-shop { margin: 5px; padding: 8px 15px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('.acv-generate').click(function(e) {
                e.preventDefault();
                var item = $(this).closest('.acv-coupon-item');
                var id = item.data('id');
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_generate_coupon',
                    id: id,
                    nonce: acv_ajax.nonce
                }, function(resp) {
                    if (resp.success) {
                        item.find('.acv-code').val(resp.data.code);
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
        $id = intval($_POST['id']);
        $settings = get_option('acv_settings', array('links' => ''));
        $links = explode("\n", trim($settings['links']));
        if (isset($links[$id])) {
            list($name, $url, $base_code) = explode('|', trim($links[$id]), 3);
            $personal_code = $base_code . '-' . wp_generate_uuid4() . substr(md5($_SERVER['REMOTE_ADDR']), 0, 4);
            wp_send_json_success(array('code' => $personal_code));
        }
        wp_send_json_error();
    }

    public function activate() {
        add_option('acv_settings', array('links' => "Example Product|https://affiliate-link.com/?ref=" . get_bloginfo('url') . "|SAVE20\nAnother Deal|https://another-link.com/?coupon=" . get_bloginfo('url') . "|DEAL30"));
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock unlimited coupons, advanced tracking, and analytics with <strong>Affiliate Coupon Vault Pro</strong>! <a href="https://example.com/pro">Upgrade now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

// Embed script for JS
add_action('wp_footer', function() {
    echo '<script>/* Affiliate Coupon Vault Free */</script>';
});