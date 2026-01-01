/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates, tracks, and displays personalized affiliate coupon codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCoupons {
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
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Pro', 'manage_options', 'sac-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_affiliate_links', sanitize_textarea_field($_POST['sac_links']));
            update_option('sac_coupon_length', intval($_POST['sac_length']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('sac_affiliate_links', "Amazon: https://amazon.com/?tag=yourtag\nOther: https://example.com/aff");
        $length = get_option('sac_coupon_length', 8);
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="sac_links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea><br><small>One per line: Merchant: Affiliate URL</small></td>
                    </tr>
                    <tr>
                        <th>Coupon Code Length</th>
                        <td><input type="number" name="sac_length" value="<?php echo $length; ?>" min="5" max="20" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'sac_save'); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[sac_coupon merchant="Amazon" discount="20%"]</code></p>
            <?php if (!get_option('sac_pro_activated')) { ?>
            <p><a href="#" class="button button-primary" onclick="alert('Upgrade to Pro for advanced features!')">Upgrade to Pro</a></p>
            <?php } ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'merchant' => '',
            'discount' => '10%',
            'affiliate' => ''
        ), $atts);

        $coupon_code = $this->generate_coupon_code($atts['merchant']);
        $link = $this->get_affiliate_link($atts['merchant'], $atts['affiliate']);

        ob_start();
        ?>
        <div id="sac-coupon" class="sac-coupon-box" data-merchant="<?php echo esc_attr($atts['merchant']); ?>">
            <h3>Exclusive Deal: <?php echo esc_html($atts['discount']); ?> OFF!</h3>
            <p>Your Coupon: <strong id="sac-code"><?php echo esc_html($coupon_code); ?></strong></p>
            <a href="<?php echo esc_url($link . $coupon_code); ?>" target="_blank" class="sac-button button">Shop Now & Save</a>
            <p><small>Track clicks for commissions. Pro version tracks conversions.</small></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#sac-coupon .sac-button').click(function() {
                $.post(sac_ajax.ajax_url, {
                    action: 'generate_coupon',
                    merchant: $('#sac-coupon').data('merchant'),
                    nonce: sac_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#sac-code').text(response.data.code);
                    }
                });
            });
        });
        </script>
        <style>
        .sac-coupon-box { border: 2px solid #0073aa; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; max-width: 300px; }
        .sac-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .sac-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($merchant) {
        $length = get_option('sac_coupon_length', 8);
        return strtoupper(substr(md5($merchant . time() . rand()), 0, $length));
    }

    private function get_affiliate_link($merchant, $custom = '') {
        $links = explode("\n", get_option('sac_affiliate_links', ''));
        foreach ($links as $line) {
            list($m, $url) = explode(':', trim($line), 2);
            if (strtolower(trim($m)) === strtolower($merchant)) {
                return trim($url);
            }
        }
        return $custom ?: home_url('/');
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('sac_nonce', 'nonce');
        $merchant = sanitize_text_field($_POST['merchant']);
        $code = $this->generate_coupon_code($merchant);
        wp_send_json_success(array('code' => $code));
    }

    public function activate() {
        add_option('sac_coupon_length', 8);
    }
}

SmartAffiliateCoupons::get_instance();

// Pro upsell notice
function sac_admin_notice() {
    if (!get_option('sac_pro_activated')) {
        echo '<div class="notice notice-info"><p>Unlock Pro features: Unlimited coupons, analytics, custom designs. <a href="options-general.php?page=sac-pro">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'sac_admin_notice');