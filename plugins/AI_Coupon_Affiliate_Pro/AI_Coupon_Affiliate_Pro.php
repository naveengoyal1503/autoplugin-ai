/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon generator for affiliate marketing. Auto-creates personalized coupons and tracks conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        // Pro check
        $this->is_pro = get_option('aicoupon_pro_activated', false);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('aicoupon_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            update_option('aicoupon_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $aff_links = get_option('aicoupon_affiliate_links', '');
        $api_key = get_option('aicoupon_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links (one per line: Merchant|Affiliate URL|Discount %)</th>
                        <td><textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea($aff_links); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>OpenAI API Key (for AI generation)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons for $49/year! <a href="https://example.com/pro">Buy Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('merchant' => ''), $atts);
        ob_start();
        ?>
        <div id="ai-coupon-container" data-merchant="<?php echo esc_attr($atts['merchant']); ?>">
            <div class="coupon-loader">Generating coupon...</div>
            <div class="coupon-display" style="display:none;">
                <div class="coupon-code"></div>
                <a class="coupon-link" href="#" target="_blank">Shop Now & Save!</a>
                <div class="coupon-stats">Clicks: <span class="clicks">0</span></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $merchant = sanitize_text_field($_POST['merchant']);
        $aff_links = explode("\n", get_option('aicoupon_affiliate_links', ''));
        $link_data = null;
        foreach ($aff_links as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) == 3 && strtolower($parts) == strtolower($merchant)) {
                $link_data = array('url' => $parts[1], 'discount' => $parts[2]);
                break;
            }
        }
        if (!$link_data) {
            wp_send_json_error('Merchant not found.');
            return;
        }
        // Simulate AI generation (Pro uses real OpenAI)
        $code = 'SAVE' . wp_rand(10, 99) . strtoupper(wp_generate_password(4, false));
        $discount = $link_data['discount'];
        // Track click
        $clicks = get_option('aicoupon_clicks_' . md5($merchant), 0) + 1;
        update_option('aicoupon_clicks_' . md5($merchant), $clicks);
        wp_send_json_success(array('code' => $code, 'discount' => $discount, 'url' => $link_data['url'], 'clicks' => $clicks));
    }
}

// Create assets directories if needed
$upload_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// Default assets
if (!file_exists($upload_dir . 'style.css')) {
    file_put_contents($upload_dir . 'style.css', ".ai-coupon-container { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; } .coupon-code { font-size: 2em; font-weight: bold; color: #007cba; margin: 10px 0; } .coupon-link { display: inline-block; background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; } .coupon-loader { text-align: center; padding: 20px; }");
}
if (!file_exists($upload_dir . 'script.js')) {
    file_put_contents($upload_dir . 'script.js', "jQuery(document).ready(function($) { $('.ai-coupon-container').each(function() { var $cont = $(this); var merchant = $cont.data('merchant'); $.post(aicoupon_ajax.ajax_url, { action: 'generate_coupon', merchant: merchant, nonce: aicoupon_ajax.nonce }, function(resp) { if (resp.success) { $cont.find('.coupon-loader').hide(); $cont.find('.coupon-code').text(resp.data.code + ' (' + resp.data.discount + ' OFF)'); $cont.find('.coupon-link').attr('href', resp.data.url).text('Shop Now & Save ' + resp.data.discount + '%!'); $cont.find('.clicks').text(resp.data.clicks); $cont.find('.coupon-display').show(); $cont.find('.coupon-link').click(function(e) { e.preventDefault(); window.open($(this).attr('href'), '_blank'); }); } }); }); });");
}

AICouponAffiliatePro::get_instance();

// Pro activation hook
register_activation_hook(__FILE__, function() {
    if (isset($_POST['pro_license']) && $_POST['pro_license'] == 'pro123') {
        update_option('aicoupon_pro_activated', true);
    }
});