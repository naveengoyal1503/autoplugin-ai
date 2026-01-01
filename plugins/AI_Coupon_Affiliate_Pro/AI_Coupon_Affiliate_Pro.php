/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-pro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
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
            <h1>AI Coupon Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro Feature)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON format)</th>
                        <td><textarea name="affiliate_links" rows="10" class="large-text"><?php echo esc_textarea($aff_links); ?></textarea><br><small>Example: {"amazon":"https://amzn.to/abc","shopify":"https://shopify.com/xyz"}</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('niche' => 'general'), $atts);
        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <h3>Grab Your Personalized Coupon!</h3>
            <input type="text" id="coupon-input" placeholder="Enter product or niche..." />
            <button id="generate-coupon">Generate Deal</button>
            <div id="coupon-result"></div>
            <div id="affiliate-track"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $niche = sanitize_text_field($_POST['niche']);
        $aff_links = json_decode(get_option('ai_coupon_affiliate_links', '{}'), true);

        // Simulate AI generation (Pro: integrate OpenAI)
        $coupons = array(
            'amazon' => 'SAVE20% - ' . $aff_links['amazon'],
            'shopify' => 'DEAL50 - ' . $aff_links['shopify'],
            'general' => 'DISCOUNT15% - Visit Partner Site'
        );

        $coupon = isset($coupons[$niche]) ? $coupons[$niche] : $coupons['general'];

        // Track click (Pro: advanced analytics)
        $track_id = uniqid();
        set_transient('coupon_click_' . $track_id, $niche, 3600);

        wp_send_json_success(array(
            'coupon' => $coupon,
            'track_url' => add_query_arg('coupon_track', $track_id, home_url())
        ));
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Dummy JS/CSS files would be created separately, but for single-file, inline them
/*
Add these as separate files in real plugin folder:
- ai-coupon.js
jQuery(document).ready(function($) {
    $('#generate-coupon').click(function() {
        $.post(ajax_object.ajax_url, {
            action: 'generate_coupon',
            nonce: ajax_object.nonce,
            niche: $('#coupon-input').val() || $('#ai-coupon-container').data('niche')
        }, function(response) {
            if (response.success) {
                $('#coupon-result').html('<p><strong>' + response.data.coupon + '</strong></p><a href="' + response.data.track_url + '" target="_blank">Redeem Now (Affiliate Link)</a>');
            }
        });
    });
});

- ai-coupon.css
#ai-coupon-container { border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9; }
#coupon-result { margin-top: 15px; font-size: 18px; color: green; }
*/
?>