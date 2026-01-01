/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-affiliate-coupon
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateCouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-affiliate-coupon', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $aff_links = get_option('ai_coupon_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>AI Affiliate Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" size="50" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (one per line: Product|Link|Brand)</th>
                        <td><textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea($aff_links); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[ai_coupon]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited generations, analytics, custom designs ($49/year).</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('product' => ''), $atts);
        ob_start();
        ?>
        <div id="ai-coupon-container" data-product="<?php echo esc_attr($atts['product']); ?>">
            <button id="generate-coupon">Generate Coupon</button>
            <div id="coupon-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $api_key = get_option('ai_coupon_api_key');
        if (!$api_key) {
            wp_die('API key required.');
        }
        $product = sanitize_text_field($_POST['product']);
        $aff_links = get_option('ai_coupon_affiliate_links');
        $links = explode("\n", trim($aff_links));
        $selected_link = $links[array_rand($links)] ?? '';
        if (!empty($selected_link)) {
            list($p, $link, $brand) = explode('|', $selected_link, 3);
        }

        $prompt = "Generate a compelling coupon code for {$product} from {$brand}. Include discount like 20% OFF, expiry date, and call to action. Make it personalized and urgent.";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 100,
            )),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('API error');
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $coupon_text = $body['choices']['message']['content'] ?? 'Error generating coupon.';

        $html = "<div class='ai-coupon'><strong>Coupon:</strong> " . esc_html($coupon_text) . "<br><a href='" . esc_url($link) . "' target='_blank' rel='nofollow'>Shop Now & Save!</a></div>";
        wp_send_json_success($html);
    }

    public function activate() {
        add_option('ai_coupon_pro', 'free');
    }
}

new AIAffiliateCouponGenerator();

// Pro upsell notice
function ai_coupon_admin_notice() {
    if (get_option('ai_coupon_pro') === 'free') {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Affiliate Coupon Pro</strong> for unlimited coupons & analytics! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49)</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_admin_notice');

// JS file would be embedded or separate, but for single file:
/*
<script>
jQuery(document).ready(function($) {
    $('#generate-coupon').click(function() {
        $.post(ajax_object.ajax_url, {
            action: 'generate_coupon',
            product: $('#ai-coupon-container').data('product'),
            nonce: ajax_object.nonce
        }, function(response) {
            if (response.success) {
                $('#coupon-result').html(response.data);
            }
        });
    });
});
</script>
*/
?>