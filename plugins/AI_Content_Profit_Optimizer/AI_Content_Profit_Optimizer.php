/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Profit_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Profit Optimizer
 * Plugin URI: https://example.com/aicpo
 * Description: Automatically detects AI-generated content, optimizes it for human-like quality, and inserts profitable affiliate links based on content analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentProfitOptimizer {
    const VERSION = '1.0.0';
    const PRO_REQUIRED = 'Upgrade to Pro for unlimited features';

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_filter('the_content', [$this, 'optimize_content'], 99);
        add_action('admin_notices', [$this, 'pro_notice']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicpo-js', plugin_dir_url(__FILE__) . 'aicpo.js', ['jquery'], self::VERSION, true);
        wp_enqueue_style('aicpo-css', plugin_dir_url(__FILE__) . 'aicpo.css', [], self::VERSION);
    }

    public function admin_menu() {
        add_options_page('AI Content Profit Optimizer', 'AI CPO', 'manage_options', 'ai-cpo', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['aicpo_api_key'])) {
            update_option('aicpo_api_key', sanitize_text_field($_POST['aicpo_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('aicpo_api_key', '');
        echo '<div class="wrap"><h1>AI Content Profit Optimizer Settings</h1><form method="post"><table class="form-table"><tr><th>AI Detection API Key</th><td><input type="text" name="aicpo_api_key" value="' . esc_attr($api_key) . '" class="regular-text" placeholder="Enter your OpenAI or similar API key for advanced detection (Pro feature)"></td></tr></table><p><input type="submit" class="button-primary" value="Save Settings"></p></form><p><strong>Pro Features:</strong> Unlimited scans, auto affiliate insertion, revenue analytics.</p></div>';
    }

    public function optimize_content($content) {
        if (is_admin() || !is_single()) return $content;

        // Simulate AI detection (basic heuristic: repetitive patterns, predictability)
        $ai_score = $this->detect_ai_content($content);
        if ($ai_score > 0.7) {
            $content = $this->humanize_content($content);
            $content = $this->insert_affiliate_links($content);
        }

        return $content;
    }

    private function detect_ai_content($text) {
        // Simple heuristic: low sentence variety, repetitive words
        $sentences = preg_split('/[.!?]+/', $text);
        $word_count = str_word_count($text);
        if ($word_count < 50) return 0;
        $unique_words = count(array_unique(str_word_count($text, 1)));
        $perplexity = $unique_words / $word_count;
        return 1 - min(1, $perplexity); // High repetition = likely AI
    }

    private function humanize_content($content) {
        // Free version: Basic randomization of some words/phrases
        $replacements = [
            'utilize' => 'use',
            'however' => ['but', 'yet', 'though'][rand(0,2)],
            'therefore' => ['so', 'thus'][rand(0,1)],
        ];
        foreach ($replacements as $from => $to) {
            if (is_array($to)) $to = $to[array_rand($to)];
            $content = preg_replace('/\b' . preg_quote($from, '/') . '\b/i', $to, $content, 2); // Limit replacements
        }
        return $content;
    }

    private function insert_affiliate_links($content) {
        // Free: Insert 1 demo link
        // Pro: Smart keyword-based Amazon/affiliate links
        if (stripos($content, 'amazon') !== false || stripos($content, 'product') !== false) {
            $link = '<p><strong>Recommended:</strong> Check out this <a href="https://amazon.com/dp/B08N5WRWNW?tag=YOURAFFILIATETAG-20" rel="nofollow sponsored">top-rated product</a> (affiliate link).</p>';
            $content .= $link;
        }
        return $content;
    }

    public function pro_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>' . self::PRO_REQUIRED . ' <a href="options-general.php?page=ai-cpo">Upgrade now</a> for full monetization features!</p></div>';
    }

    public function activate() {
        add_option('aicpo_activated', time());
    }
}

new AIContentProfitOptimizer();

// Inline JS/CSS for self-contained
add_action('wp_head', function() {
    echo '<style>.aicpo-score {background: #ffeb3b; padding: 2px 5px; font-size: 12px; border-radius: 3px;}.aicpo-pro {color: #0073aa; font-weight: bold;}</style>';
    echo '<script>document.addEventListener("DOMContentLoaded", function(){console.log("AI CPO Active - Pro Upgrade for Max Profits!");});</script>';
});