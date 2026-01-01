/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost your commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_ids', sanitize_textarea_field($_POST['affiliate_ids']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliate_ids = get_option('ai_coupon_affiliate_ids', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Affiliate Program IDs</th>
                        <td><textarea name="affiliate_ids" rows="5" class="large-text"><?php echo esc_textarea($affiliate_ids); ?></textarea><br><small>e.g. Amazon: YOUR-ID, Example: REF123</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Premium:</strong> Unlock unlimited generations, analytics, and custom designs for $49/year. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'amount' => '20',
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>" data-amount="<?php echo esc_attr($atts['amount']); ?>">
            <div class="coupon-loader">Generating your exclusive coupon...</div>
            <div class="coupon-display" style="display:none;">
                <div class="coupon-code"></div>
                <div class="coupon-desc"></div>
                <a class="coupon-link" href="#" target="_blank">Shop Now & Save</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');

        $niche = sanitize_text_field($_POST['niche']);
        $amount = intval($_POST['amount']);
        $affiliate_ids = get_option('ai_coupon_affiliate_ids', '');
        $api_key = get_option('ai_coupon_api_key', '');

        if (empty($api_key)) {
            wp_die(json_encode(array('error' => 'API key required. Set in settings.')));
        }

        // Mock AI generation (replace with real OpenAI API call for premium)
        $prompt = "Generate a unique {$amount}% off coupon code for {$niche} products. Include description and affiliate-friendly link placeholder.";
        // Simulate AI response
        $coupon_code = strtoupper(substr(md5($niche . time()), 0, 8));
        $description = "Exclusive AI-generated {$amount}% discount on top {$niche} deals! Limited time.";
        $link = "https://example-affiliate.com/?ref=" . trim(explode('\n', $affiliate_ids) ?? 'DEMO');

        // Premium check: limit free to 5/day
        $today = date('Y-m-d');
        $usage = get_transient('ai_coupon_usage') ?: array();
        if (!isset($usage[$today]) || $usage[$today] < 5) {
            $usage[$today] = ($usage[$today] ?? 0) + 1;
            set_transient('ai_coupon_usage', $usage, DAY_IN_SECONDS);
            wp_send_json_success(array(
                'code' => $coupon_code,
                'desc' => $description,
                'link' => $link
            ));
        } else {
            wp_send_json_error(array('message' => 'Free limit reached. Upgrade to premium for unlimited coupons.'));
        }
    }
}

new AICouponAffiliatePro();

// Enqueue dummy JS/CSS for demo
function ai_coupon_assets() {
    ?>
    <style>
    #ai-coupon-container { max-width: 400px; margin: 20px 0; }
    .coupon-loader { text-align: center; padding: 20px; background: #f0f0f0; border-radius: 8px; }
    .coupon-display { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
    .coupon-code { font-size: 2em; font-weight: bold; margin-bottom: 10px; }
    .coupon-desc { margin-bottom: 15px; }
    .coupon-link { display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; }
    .coupon-link:hover { background: rgba(255,255,255,0.3); }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('#ai-coupon-container').on('click', '.coupon-loader', function() {
            var $container = $(this).closest('#ai-coupon-container');
            $.post(aicoupon_ajax.ajax_url, {
                action: 'generate_coupon',
                nonce: aicoupon_ajax.nonce,
                niche: $container.data('niche'),
                amount: $container.data('amount')
            }, function(response) {
                if (response.success) {
                    $container.find('.coupon-loader').hide();
                    $container.find('.coupon-display').show().find('.coupon-code').text(response.data.code);
                    $container.find('.coupon-desc').text(response.data.desc);
                    $container.find('.coupon-link').attr('href', response.data.link);
                } else {
                    alert(response.data.message || 'Error generating coupon.');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ai_coupon_assets');