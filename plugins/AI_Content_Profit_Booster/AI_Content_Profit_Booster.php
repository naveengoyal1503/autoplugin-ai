/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Profit_Booster.php
*/
<?php
/**
 * Plugin Name: AI Content Profit Booster
 * Plugin URI: https://example.com/ai-content-profit-booster
 * Description: Automatically generates high-quality, SEO-optimized affiliate content with embedded profit links and personalized coupon codes to boost blog revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentProfitBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_generate_profit_content', array($this, 'handle_generate_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acpb_api_key') === false) {
            add_option('acpb_api_key', '');
        }
        if (get_option('acpb_affiliate_links') === false) {
            add_option('acpb_affiliate_links', json_encode(array(
                array('keyword' => 'best hosting', 'link' => 'https://youraffiliate.link/hosting'),
                array('keyword' => 'wordpress plugin', 'link' => 'https://youraffiliate.link/plugin')
            )));
        }
    }

    public function add_admin_menu() {
        add_options_page('AI Content Profit Booster', 'Profit Booster', 'manage_options', 'ai-content-profit', array($this, 'admin_page'));
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_ai-content-profit') return;
        wp_enqueue_script('acpb-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acpb-admin', 'acpb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acpb_nonce')));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Profit Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acpb_settings'); ?>
                <?php do_settings_sections('acpb_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="acpb_api_key" value="<?php echo esc_attr(get_option('acpb_api_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON)</th>
                        <td><textarea name="acpb_affiliate_links" class="large-text" rows="10"><?php echo esc_textarea(get_option('acpb_affiliate_links')); ?></textarea><br>
                        <small>Example: [{ "keyword": "best hosting", "link": "https://aff.link" }]</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Generate Content</h2>
            <input type="text" id="topic" placeholder="Enter topic (e.g., Best WordPress Hosting 2026)" class="regular-text">
            <button id="generate-btn" class="button button-primary">Generate Profitable Content</button>
            <div id="result"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#generate-btn').click(function() {
                var topic = $('#topic').val();
                $.post(acpb_ajax.ajax_url, {
                    action: 'generate_profit_content',
                    topic: topic,
                    nonce: acpb_ajax.nonce
                }, function(response) {
                    $('#result').html(response);
                });
            });
        });
        </script>
        <?php
    }

    public function handle_generate_content() {
        check_ajax_referer('acpb_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $topic = sanitize_text_field($_POST['topic']);
        $api_key = get_option('acpb_api_key');
        $aff_links = json_decode(get_option('acpb_affiliate_links'), true);

        if (empty($api_key)) {
            wp_send_json_error('API key required');
        }

        $prompt = "Generate a 800-word SEO-optimized blog post on '$topic'. Include affiliate links for: " . json_encode($aff_links) . ". Add unique coupon codes like 'SAVE20'. Make it engaging, with H2 headings, lists, and a strong CTA.";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 1200
            ))
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('API error');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = $body['choices']['message']['content'] ?? 'Error generating content';

        echo '<textarea class="large-text" rows="20">' . esc_textarea($content) . '</textarea><br>
        <button class="button button-secondary" onclick="navigator.clipboard.writeText(jQuery(this).prev().val()); alert(\'Copied!\')">Copy Content</button>';
        wp_die();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentProfitBooster();

add_action('admin_init', 'acpb_register_settings');
function acpb_register_settings() {
    register_setting('acpb_settings', 'acpb_api_key');
    register_setting('acpb_settings', 'acpb_affiliate_links');
}
