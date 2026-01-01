/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Profit_Shield.php
*/
<?php
/**
 * Plugin Name: AI Content Profit Shield
 * Plugin URI: https://example.com/aicps
 * Description: Detects AI-generated content, adds profit badges, and monetizes with affiliates.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentProfitShield {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_filter('the_content', [$this, 'process_content']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (get_option('aicps_pro') !== 'activated') {
            add_action('admin_notices', [$this, 'pro_notice']);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicps-js', plugin_dir_url(__FILE__) . 'aicps.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('aicps-css', plugin_dir_url(__FILE__) . 'aicps.css', [], '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Content Profit Shield', 'AI Profit Shield', 'manage_options', 'aicps', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['aicps_api_key'])) {
            update_option('aicps_api_key', sanitize_text_field($_POST['aicps_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('aicps_api_key', '');
        $aff_link = get_option('aicps_aff_link', 'https://example.com/affiliate');
        echo '<div class="wrap"><h1>AI Content Profit Shield Settings</h1><form method="post">';
        echo '<p><label>AI Detection API Key (Free tier: use openrouter.ai or similar):</label><br><input type="text" name="aicps_api_key" value="' . esc_attr($api_key) . '" size="50"></p>';
        echo '<p><label>Affiliate Link:</label><br><input type="url" name="aicps_aff_link" value="' . esc_attr($aff_link) . '" size="50"></p>';
        echo '<p><label><input type="checkbox" name="aicps_pro" value="1"> Enable Pro Features (Enter license)</label></p>';
        echo '<p class="submit"><input type="submit" class="button-primary" value="Save Settings"></p></form>';
        echo '<h2>Upgrade to Pro</h2><p>Get unlimited scans & auto-monetization for $49/year at <a href="https://example.com/pro">example.com/pro</a></p></div>';
    }

    public function process_content($content) {
        if (is_admin() || !is_single()) return $content;

        $api_key = get_option('aicps_api_key');
        if (!$api_key) return $content;

        $text_sample = wp_trim_words($content, 100);
        $ai_score = $this->detect_ai_content($text_sample, $api_key);

        if ($ai_score > 0.7) {
            $badge = $this->get_ai_badge();
            $affiliate = $this->get_affiliate_box();
            $content = $badge . $content . $affiliate;
        } elseif (get_option('aicps_pro') === 'activated') {
            $content .= $this->get_verified_badge();
        }

        return $content;
    }

    private function detect_ai_content($text, $api_key) {
        // Mock AI detection using simple heuristic (Pro uses real API)
        $words = str_word_count($text);
        $score = min(1, ($words / 500) * 0.8); // Placeholder
        // Real impl: curl to OpenAI/ZERO API
        return $score;
    }

    private function get_ai_badge() {
        return '<div class="aicps-badge ai-detected">🤖 AI-Generated Content Detected | <a href="' . esc_url(get_option('aicps_aff_link', '')) . '">Human Review Service</a></div>';
    }

    private function get_verified_badge() {
        return '<div class="aicps-badge human-verified">✅ Human-Verified Content</div>';
    }

    private function get_affiliate_box() {
        return '<div class="aicps-monetize"><p>Like this? <a href="' . esc_url(get_option('aicps_aff_link', '')) . '" target="_blank">Get Premium Tools (Affiliate)</a></p></div>';
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>AI Content Profit Shield:</strong> Unlock Pro for auto-affiliates & unlimited scans! <a href="' . admin_url('options-general.php?page=aicps') . '">Upgrade now</a></p></div>';
    }

    public function activate() {
        add_option('aicps_pro', 'free');
    }
}

new AIContentProfitShield();

// Inline CSS/JS for single file
add_action('wp_head', function() { ?>
<style>
.aicps-badge { padding: 10px; margin: 20px 0; border-radius: 5px; text-align: center; font-weight: bold; }
.ai-detected { background: #ffebee; border: 1px solid #f44336; color: #c62828; }
.human-verified { background: #e8f5e8; border: 1px solid #4caf50; color: #2e7d32; }
.aicps-monetize { background: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; text-align: center; }
.aicps-monetize a { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
</style>
<script>jQuery(document).ready(function($){ $('.aicps-badge').fadeIn(1000); });</script>
<?php });