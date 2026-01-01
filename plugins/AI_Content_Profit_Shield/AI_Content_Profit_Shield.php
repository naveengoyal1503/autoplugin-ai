/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Profit_Shield.php
*/
<?php
/**
 * Plugin Name: AI Content Profit Shield
 * Plugin URI: https://example.com/aicps
 * Description: Detects AI-generated content and auto-inserts profitable affiliate links, ads, or upsells.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentProfitShield {
    public function __construct() {
        add_action('init', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('the_content', [$this, 'monetize_content']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicps-script', plugin_dir_url(__FILE__) . 'aicps.js', ['jquery'], '1.0.0', true);
    }

    public function monetize_content($content) {
        if (is_admin() || !is_single()) return $content;

        $api_key = get_option('aicps_openai_key', '');
        if (!$api_key) return $content;

        $is_ai = $this->detect_ai_content($content);
        if ($is_ai) {
            $aff_links = get_option('aicps_affiliate_links', ['https://example.com/aff1', 'https://example.com/aff2']);
            $ad_code = get_option('aicps_ad_code', '<div class="aicps-ad">Your Ad Here</div>');

            $insertions = '';
            foreach ($aff_links as $link) {
                $insertions .= '<p><a href="' . esc_url($link) . '" target="_blank" rel="nofollow">Check this out for more! &#x1F60A;</a></p>';
            }
            $insertions .= $ad_code;

            $content .= '<div class="aicps-monetization" style="background:#f0f8ff;padding:20px;margin:20px 0;border-left:5px solid #007cba;">
                <h4>Related Deals & Tools:</h4>' . $insertions . '</div>';
        }
        return $content;
    }

    private function detect_ai_content($text) {
        $api_key = get_option('aicps_openai_key');
        if (!$api_key) return false;

        $prompt = "Analyze this text and respond with only 'AI' if it's likely AI-generated (score >70%), or 'HUMAN' if human-written: " . substr($text, 0, 2000);

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 10,
            ]),
        ]);

        if (is_wp_error($response)) return false;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $result = strtolower(trim($body['choices']['message']['content'] ?? ''));
        return $result === 'ai';
    }

    public function admin_menu() {
        add_options_page('AI Content Profit Shield', 'AI Profit Shield', 'manage_options', 'aicps', [$this, 'settings_page']);
    }

    public function admin_init() {
        register_setting('aicps_options', 'aicps_openai_key');
        register_setting('aicps_options', 'aicps_affiliate_links');
        register_setting('aicps_options', 'aicps_ad_code');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Profit Shield Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aicps_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="aicps_openai_key" value="<?php echo esc_attr(get_option('aicps_openai_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (comma-separated)</th>
                        <td><textarea name="aicps_affiliate_links" class="large-text"><?php echo esc_textarea(get_option('aicps_affiliate_links', '')); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Ad Code</th>
                        <td><textarea name="aicps_ad_code" class="large-text" rows="5"><?php echo esc_textarea(get_option('aicps_ad_code', '')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock bulk detection, custom prompts, A/B testing, and priority support for $49/year.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('aicps_affiliate_links', 'https://your-affiliate-link1.com,https://your-affiliate-link2.com');
    }

    public function deactivate() {}
}

new AIContentProfitShield();

// Pro upsell notice
function aicps_pro_notice() {
    if (!get_option('aicps_openai_key') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock full monetization with <strong>AI Content Profit Shield Pro</strong>! Get your <a href="https://example.com/pro" target="_blank">Pro key</a> now.</p></div>';
    }
}
add_action('admin_notices', 'aicps_pro_notice');

// Inline JS
add_action('wp_footer', function() {
    if (is_single()) {
        ?><script>console.log('AI Content Profit Shield active - scanning for monetization opportunities...');</script><?php
    }
});
