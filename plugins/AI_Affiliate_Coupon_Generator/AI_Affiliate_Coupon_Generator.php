/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates personalized affiliate coupon codes and banners for blog posts, boosting conversions with AI-optimized deals.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-affiliate-coupon
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateCouponGenerator {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-affiliate-coupon', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'ai_coupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Affiliate Coupons', 'AI Coupons', 'manage_options', 'ai-affiliate-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_affiliates', sanitize_textarea_field($_POST['affiliates']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliates = get_option('ai_coupon_affiliates', "Amazon:10% off\nHostinger:20% discount");
        ?>
        <div class="wrap">
            <h1>AI Affiliate Coupon Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Networks</th>
                        <td><textarea name="affiliates" rows="10" cols="50"><?php echo esc_textarea($affiliates); ?></textarea><br><small>One per line: Network:Discount</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, AI personalization, and premium integrations for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('network' => ''), $atts);
        ob_start();
        ?>
        <div id="ai-coupon-<?php echo uniqid(); ?>" class="ai-coupon-generator" data-network="<?php echo esc_attr($atts['network']); ?>">
            <div class="coupon-loading">Generating your exclusive deal...</div>
            <div class="coupon-display" style="display:none;">
                <h3 id="coupon-title"></h3>
                <div id="coupon-code"></div>
                <a id="coupon-link" href="#" target="_blank" class="coupon-btn">Get Deal Now</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $network = sanitize_text_field($_POST['network']);
        $affiliates = get_option('ai_coupon_affiliates', "Amazon:10% off\nHostinger:20% discount");
        $lines = explode("\n", $affiliates);
        $coupons = array();
        foreach ($lines as $line) {
            list($n, $discount) = explode(':', trim($line), 2);
            $coupons[trim($n)] = trim($discount);
        }
        if (isset($coupons[$network])) {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
            wp_send_json_success(array(
                'title' => $network . ' Exclusive Deal',
                'code' => $code,
                'discount' => $coupons[$network],
                'link' => 'https://example.com/aff/' . strtolower($network) . '?coupon=' . $code // Replace with real affiliate link
            ));
        } else {
            wp_send_json_error('Network not found');
        }
    }

    public function activate() {
        add_option('ai_coupon_affiliates', "Amazon:10% off\nHostinger:20% discount");
    }
}

AIAffiliateCouponGenerator::get_instance();

// Freemium upsell notice
function ai_coupon_admin_notice() {
    if (!get_option('ai_coupon_pro_dismissed')) {
        echo '<div class="notice notice-info is-dismissible"><p>Upgrade to <strong>AI Affiliate Coupon Pro</strong> for unlimited features! <a href="https://example.com/pro" target="_blank">Get it now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_admin_notice');

// Create assets directories if needed
$upload_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// Minimal CSS
file_put_contents($upload_dir . '/style.css', '.ai-coupon-generator { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; } .coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; } .coupon-loading { font-style: italic; color: #666; }');

// Minimal JS
file_put_contents($upload_dir . '/script.js', 'jQuery(document).ready(function($) { $(".ai-coupon-generator").each(function() { var $container = $(this); var network = $container.data("network") || "Amazon"; $.post(ai_coupon_ajax.ajax_url, { action: "generate_coupon", network: network, nonce: ai_coupon_ajax.nonce }, function(response) { if (response.success) { $("#coupon-title", $container).text(response.data.title + " - " + response.data.discount); $("#coupon-code", $container).text("Code: " + response.data.code); $("#coupon-link", $container).attr("href", response.data.link).text("Grab " + response.data.discount); $container.find(".coupon-loading").hide(); $container.find(".coupon-display").show(); } }); }); });');