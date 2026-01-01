/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Monetizer.php
*/
<?php
/**
 * Plugin Name: AI Content Monetizer
 * Plugin URI: https://example.com/ai-content-monetizer
 * Description: Automatically generates premium AI-powered content for paid members, with affiliate links and personalized coupons to boost monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentMonetizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_premium_content', array($this, 'premium_content_shortcode'));
        add_action('wp_ajax_generate_ai_content', array($this, 'handle_ai_content'));
        add_action('wp_ajax_nopriv_generate_ai_content', array($this, 'handle_ai_content'));
    }

    public function init() {
        if (get_option('aicm_pro_version')) {
            // Pro features enabled
            add_filter('the_content', array($this, 'protect_premium_content'));
        }
        register_setting('aicm_settings', 'aicm_api_key');
        register_setting('aicm_settings', 'aicm_pro_version');
        register_setting('aicm_settings', 'aicm_affiliate_links');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicm-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aicm-script', 'aicm_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicm_nonce')));
    }

    public function premium_content_shortcode($atts) {
        if (!is_user_logged_in() || !current_user_can('read_premium_content')) {
            return '<div id="aicm-paywall"><p><strong>Premium Content</strong>: <a href="' . wp_login_url() . '">Upgrade to Pro</a> to unlock AI-generated exclusive content with affiliate deals!</p></div>';
        }
        ob_start();
        ?>
        <div id="aicm-content">
            <button id="generate-ai" class="button">Generate Premium AI Content</button>
            <div id="aicm-output"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_ai_content() {
        check_ajax_referer('aicm_nonce', 'nonce');
        if (!is_user_logged_in() || !get_option('aicm_pro_version')) {
            wp_die('Pro required');
        }
        $topic = sanitize_text_field($_POST['topic']);
        $api_key = get_option('aicm_api_key');
        // Simulate AI generation (replace with real OpenAI API call)
        $content = $this->generate_mock_ai_content($topic);
        $affiliates = get_option('aicm_affiliate_links', array());
        foreach ($affiliates as $link) {
            $content .= "\n\n**Special Deal:** Check out this [affiliate product](" . esc_url($link['url']) . ") with exclusive coupon: " . $link['coupon'];
        }
        wp_send_json_success($content);
    }

    private function generate_mock_ai_content($topic) {
        $templates = array(
            "In-depth guide on $topic: Start with basics, advanced tips, and pro strategies.",
            "Top 10 $topic tools with reviews and affiliate recommendations.",
            "Ultimate $topic checklist for success in 2026."
        );
        return $templates[array_rand($templates)];
    }

    public function protect_premium_content($content) {
        if (has_shortcode($content, 'ai_premium_content') && !current_user_can('read_premium_content')) {
            return 'Premium content locked. <a href="/upgrade">Join Pro</a> for full access.';
        }
        return $content;
    }
}

new AIContentMonetizer();

// Admin menu
add_action('admin_menu', function() {
    add_options_page('AI Content Monetizer', 'AI Monetizer', 'manage_options', 'aicm-settings', 'aicm_settings_page');
});

function aicm_settings_page() {
    ?>
    <div class="wrap">
        <h1>AI Content Monetizer Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('aicm_settings'); ?>
            <table class="form-table">
                <tr>
                    <th>Pro Version Key</th>
                    <td><input type="text" name="aicm_pro_version" value="<?php echo esc_attr(get_option('aicm_pro_version')); ?>" /></td>
                </tr>
                <tr>
                    <th>AI API Key (OpenAI)</th>
                    <td><input type="password" name="aicm_api_key" value="<?php echo esc_attr(get_option('aicm_api_key')); ?>" /></td>
                </tr>
                <tr>
                    <th>Affiliate Links (JSON)</th>
                    <td><textarea name="aicm_affiliate_links"><?php echo esc_textarea(json_encode(get_option('aicm_affiliate_links'))); ?></textarea><br><small>Format: [{"name":"Product","url":"link","coupon":"SAVE20"}]</small></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Create assets/script.js placeholder (in real plugin, include file)
// For single-file, inline JS
add_action('wp_footer', function() {
    if (is_user_logged_in()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#generate-ai').click(function() {
                var topic = prompt('Enter content topic:');
                $.post(aicm_ajax.ajax_url, {
                    action: 'generate_ai_content',
                    nonce: aicm_ajax.nonce,
                    topic: topic
                }, function(res) {
                    if (res.success) {
                        $('#aicm-output').html('<div class="aicm-generated">' + res.data + '</div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
});