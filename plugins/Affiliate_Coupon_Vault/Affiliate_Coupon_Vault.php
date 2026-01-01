/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
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
            // Pro features hook here
        }
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '{"example":{"affiliate":"Amazon","code":"SAVE20","desc":"20% off electronics","link":"https://amazon.com"}}');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="20" cols="80"><?php echo esc_textarea($coupons); ?></textarea><br>
                <p class="description">JSON format: {"name":{"affiliate":"Name","code":"CODE","desc":"Description","link":"URL"}}</p>
                <p><input type="submit" name="acv_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p><strong>Shortcode:</strong> <code>[affiliate_coupon name="example"]</code> | Pro: Unlimited, analytics, auto-generation.</p>
            <p><a href="https://example.com/pro" target="_blank" class="button">Upgrade to Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('name' => ''), $atts);
        $coupons = json_decode(get_option('acv_coupons', '{}'), true);
        if (!isset($coupons[$atts['name']])) {
            return '<p>Coupon not found. <a href="/wp-admin/options-general.php?page=affiliate-coupon-vault">Add it in settings</a>.</p>';
        }
        $coupon = $coupons[$atts['name']];
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div class="acv-coupon" data-coupon="<?php echo esc_attr($atts['name']); ?>">
            <h3><?php echo esc_html($coupon['affiliate']); ?> Exclusive Deal</h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <p><strong>Your Code: <span class="acv-code"><?php echo esc_html($unique_code); ?></strong></p>
            <a href="<?php echo esc_url($coupon['link']); ?>?ref=<?php echo get_bloginfo('url'); ?>" class="acv-button" target="_blank">Shop Now & Save</a>
            <small>Copy code: <button class="acv-copy">Copy</button></small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function save_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        // Pro feature simulation
        wp_send_json_success('Coupon personalized!');
    }
}

new AffiliateCouponVault();

// Create assets directories if needed
$upload_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// Sample style.css content
$css = ".acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; } .acv-code { font-size: 1.2em; color: #d63638; } .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; } .acv-copy { background: #46b450; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }";
file_put_contents($upload_dir . '/style.css', $css);

// Sample script.js content
$js = "jQuery(document).ready(function($) { $('.acv-copy').click(function() { var code = $(this).closest('.acv-coupon').find('.acv-code').text(); navigator.clipboard.writeText(code).then(function() { $(this).text('Copied!'); setTimeout(() => $(this).text('Copy'), 2000); }.bind(this)); }); $('.acv-coupon').hover(function() { $(this).css('border-style', 'solid'); }, function() { $(this).css('border-style', 'dashed'); }); });";
file_put_contents($upload_dir . '/script.js', $js);

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/yr)</a></p></div>';
    }
});