/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Monetizer.php
*/
<?php
/**
 * Plugin Name: AI Content Monetizer
 * Plugin URI: https://example.com/ai-content-monetizer
 * Description: Automatically locks premium AI-generated content behind paywalls, allowing users to unlock with one-time micropayments.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentMonetizer {
    private $option_key = 'aicm_settings';
    private $prices = [];

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aicm_unlock_content', [$this, 'handle_unlock']);
        add_action('wp_ajax_nopriv_aicm_unlock_content', [$this, 'handle_unlock']);
        add_shortcode('aicm_paywall', [$this, 'paywall_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        $settings = get_option($this->option_key, ['default_price' => 0.99]);
        $this->prices = $settings;

        // Auto-lock new posts with AI tag
        add_filter('the_content', [$this, 'lock_ai_content']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicm-script', plugin_dir_url(__FILE__) . 'aicm.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('aicm-style', plugin_dir_url(__FILE__) . 'aicm.css', [], '1.0.0');
        wp_localize_script('aicm-script', 'aicm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicm_nonce'),
        ]);
    }

    public function lock_ai_content($content) {
        if (is_single() && has_tag('ai-generated') && !is_user_logged_in()) {
            $price = $this->prices['default_price'] ?? 0.99;
            $locked_content = '<div id="aicm-paywall"><p>This premium AI-generated content is locked. Unlock for <strong>$' . $price . '</strong>:</p><button class="aicm-unlock-btn" data-price="' . $price . '" data-post="' . get_the_ID() . '">Pay & Unlock</button><div id="aicm-message"></div></div>';
            $content = $locked_content . '<div id="aicm-content" style="display:none;">' . $content . '</div>';
        }
        return $content;
    }

    public function paywall_shortcode($atts) {
        $atts = shortcode_atts(['price' => 0.99, 'content' => ''], $atts);
        if (!is_user_logged_in()) {
            return '<div id="aicm-paywall-shortcode"><p>Unlock this content for <strong>$' . $atts['price'] . '</strong>:</p><button class="aicm-unlock-btn" data-price="' . $atts['price'] . '" data-post="shortcode">Pay & Unlock</button><div id="aicm-message-shortcode"></div></div><div id="aicm-content-shortcode" style="display:none;">' . $atts['content'] . '</div>';
        }
        return $atts['content'];
    }

    public function handle_unlock() {
        check_ajax_referer('aicm_nonce', 'nonce');
        $price = sanitize_text_field($_POST['price']);
        $post_id = intval($_POST['post_id']);

        // Simulate payment (integrate with Stripe/PayPal in pro version)
        // For demo: always 'succeed'
        if (true) { // Replace with real payment check
            setcookie('aicm_unlock_' . $post_id, '1', time() + 3600 * 24 * 30, '/');
            wp_send_json_success(['message' => 'Unlocked! Refreshing...']);
        } else {
            wp_send_json_error(['message' => 'Payment failed.']);
        }
    }

    public function activate() {
        add_option($this->option_key, ['default_price' => 0.99]);
    }
}

new AIContentMonetizer();

// Inline JS and CSS for self-contained plugin
add_action('wp_head', function() {
    echo '<script>jQuery(document).ready(function($) { $(".aicm-unlock-btn").click(function() { var btn = $(this); var price = btn.data("price"); var post = btn.data("post"); $.post(aicm_ajax.ajax_url, {action: "aicm_unlock_content", price: price, post_id: post, nonce: aicm_ajax.nonce}, function(res) { if (res.success) { $("#aicm-paywall, #aicm-paywall-shortcode").hide(); $("#aicm-content, #aicm-content-shortcode").show(); $("#aicm-message").html(res.data.message); } else { $("#aicm-message").html(res.data.message); } }); }); });</script>';
    echo '<style>#aicm-paywall { background: #f9f9f9; padding: 20px; border: 2px dashed #ccc; text-align: center; margin: 20px 0; } .aicm-unlock-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; } .aicm-unlock-btn:hover { background: #005a87; }</style>';
});

// Settings page
add_action('admin_menu', function() {
    add_options_page('AI Content Monetizer', 'AI Content Monetizer', 'manage_options', 'aicm-settings', function() {
        if (isset($_POST['submit'])) {
            update_option('aicm_settings', $_POST['aicm_settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = get_option('aicm_settings', ['default_price' => 0.99]);
        ?>
        <div class="wrap">
            <h1>AI Content Monetizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Default Unlock Price ($)</th>
                        <td><input type="number" step="0.01" name="aicm_settings[default_price]" value="<?php echo esc_attr($settings['default_price']); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Usage:</strong> Tag posts with <code>ai-generated</code> to auto-lock, or use shortcode <code>[aicm_paywall price="1.99"]Your content[/aicm_paywall]</code></p>
        </div>
        <?php
    });
});