/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: Automatically generates personalized, trackable coupon codes and affiliate links using AI to boost affiliate commissions and site monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGeneratorPro {
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
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if ($this->is_pro()) {
            // Pro features
        }
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
            update_option('ai_coupon_pro_key', sanitize_text_field($_POST['pro_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $pro_key = get_option('ai_coupon_pro_key', '');
        echo '<div class="wrap"><h1>AI Coupon Generator Pro Settings</h1><form method="post"><table class="form-table"><tr><th>Pro License Key</th><td><input type="text" name="pro_key" value="' . esc_attr($pro_key) . '" class="regular-text"></td></tr></table><p><input type="submit" name="submit" class="button-primary" value="Save"></p></form><p><strong>Upgrade to Pro:</strong> Unlock unlimited coupons, AI personalization, and analytics for $49/year. <a href="https://example.com/upgrade" target="_blank">Get Pro</a></p></div>';
    }

    private function is_pro() {
        $pro_key = get_option('ai_coupon_pro_key', '');
        return !empty($pro_key) && strlen($pro_key) > 10;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => 'Generic',
            'discount' => '10',
            'affiliate' => '',
        ), $atts);

        if (!$this->is_pro() && $this->get_coupon_count() >= 5) {
            return '<p><strong>Upgrade to Pro</strong> for unlimited coupons! <a href="' . admin_url('options-general.php?page=ai-coupon-pro') . '">Upgrade Now</a></p>';
        }

        ob_start();
        ?>
        <div id="ai-coupon-container" data-brand="<?php echo esc_attr($atts['brand']); ?>" data-discount="<?php echo esc_attr($atts['discount']); ?>" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <button id="generate-coupon" class="button ai-coupon-btn">Generate My Coupon</button>
            <div id="coupon-result" style="display:none;">
                <h3>Your Personal Coupon:</h3>
                <div class="coupon-code"></div>
                <p>Discount: <span class="discount"></span>% off</p>
                <a href="#" class="affiliate-link" target="_blank">Shop Now & Save</a>
            </div>
        </div>
        <?php
        $output = ob_get_clean();
        $this->increment_coupon_count();
        return $output;
    }

    private function get_coupon_count() {
        return get_option('ai_coupon_count', 0);
    }

    private function increment_coupon_count() {
        $count = $this->get_coupon_count();
        update_option('ai_coupon_count', $count + 1);
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        if (!$this->is_pro()) {
            wp_send_json_error('Pro required for unlimited use.');
            return;
        }

        $brand = sanitize_text_field($_POST['brand']);
        $discount = intval($_POST['discount']);
        $affiliate = sanitize_text_field($_POST['affiliate']);

        // Simulate AI generation (in Pro, integrate real AI API like OpenAI)
        $coupon_code = strtoupper(substr(md5(uniqid($brand . time())), 0, 8));
        $link = !empty($affiliate) ? $affiliate . '?coupon=' . $coupon_code : '#';

        wp_send_json_success(array(
            'code' => $coupon_code,
            'discount' => $discount,
            'link' => $link
        ));
    }
}

AICouponGeneratorPro::get_instance();

// Create assets directories if they don't exist
$upload_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// Default assets (embedded for single-file)
if (!file_exists($upload_dir . '/script.js')) {
    file_put_contents($upload_dir . '/script.js', "jQuery(document).ready(function($) {
    $('#generate-coupon').click(function(e) {
        e.preventDefault();
        var container = $('#ai-coupon-container');
        $.post(aicoupon_ajax.ajax_url, {
            action: 'generate_coupon',
            nonce: aicoupon_ajax.nonce,
            brand: container.data('brand'),
            discount: container.data('discount'),
            affiliate: container.data('affiliate')
        }, function(response) {
            if (response.success) {
                $('.coupon-code').text(response.data.code);
                $('.discount').text(response.data.discount);
                $('.affiliate-link').attr('href', response.data.link);
                $('#coupon-result').show();
            } else {
                alert(response.data);
            }
        });
    });
});");
}

if (!file_exists($upload_dir . '/style.css')) {
    file_put_contents($upload_dir . '/style.css', ".ai-coupon-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
.ai-coupon-btn:hover { background: #005a87; }
#coupon-result { margin-top: 20px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; }
.coupon-code { font-size: 24px; font-weight: bold; color: #0073aa; background: white; padding: 10px; display: inline-block; }");
}

?>