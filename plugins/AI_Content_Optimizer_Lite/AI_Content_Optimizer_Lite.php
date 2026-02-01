/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and readability. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    const VERSION = '1.0.0';
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'handle_analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Lite',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('settings_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), self::VERSION, true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'max_free_scans' => 3,
            'premium_url' => self::PREMIUM_URL
        ));
    }

    public function admin_page() {
        $scans_today = get_option('aco_scans_today', 0);
        $last_reset = get_option('aco_last_reset', 0);
        $today = date('Y-m-d');
        if ($last_reset !== $today) {
            update_option('aco_scans_today', 0);
            update_option('aco_last_reset', $today);
            $scans_today = 0;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Lite', 'ai-content-optimizer'); ?></h1>
            <p><?php _e('Paste your content below for AI-powered analysis on SEO, readability, and optimization suggestions.', 'ai-content-optimizer'); ?></p>
            <?php if ($scans_today >= 3): ?>
                <div class="notice notice-warning">
                    <p><?php printf(__('Daily free scans limit reached (%d/3). <a href="%s" target="_blank">Upgrade to Premium</a> for unlimited access!', 'ai-content-optimizer'), $scans_today, self::PREMIUM_URL); ?></p>
                </div>
            <?php endif; ?>
            <textarea id="aco-content" rows="10" cols="80" placeholder="Paste your post content here..."></textarea>
            <br><button id="aco-analyze" class="button button-primary" <?php echo $scans_today >= 3 ? 'disabled' : ''; ?>>Analyze Content</button>
            <div id="aco-results"></div>
        </div>
        <?php
    }

    public function handle_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $scans_today = get_option('aco_scans_today', 0);
        if ($scans_today >= 3) {
            wp_send_json_error(__('Daily limit reached. Upgrade to premium!', 'ai-content-optimizer'));
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error(__('Content is empty.', 'ai-content-optimizer'));
        }

        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $keywords = $this->extract_keywords($content);
        $suggestions = $this->generate_suggestions($word_count, $readability, $keywords);

        update_option('aco_scans_today', $scans_today + 1);

        wp_send_json_success(array(
            'word_count' => $word_count,
            'readability_score' => number_format($readability, 2),
            'keywords' => $keywords,
            'suggestions' => $suggestions
        ));
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/s', $text);
        $sentence_count = max(1, $sentence_count);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $syllables = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{1,2}/', $word, $matches);
        }
        return max(1, $syllables);
    }

    private function extract_keywords($content) {
        $words = explode(' ', strtolower(strip_tags($content)));
        $word_freq = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($word_freq);
        return array_slice(array_keys($word_freq), 0, 5);
    }

    private function generate_suggestions($wc, $readability, $keywords) {
        $sugs = array();
        if ($wc < 300) $sugs[] = 'Add more content to reach 300+ words for better SEO.';
        if ($readability < 60) $sugs[] = 'Improve readability: Use shorter sentences and common words.';
        $sugs[] = 'Primary keywords: ' . implode(', ', $keywords);
        $sugs[] = 'Premium: Unlock auto-rewrites and meta tag generator.';
        return $sugs;
    }

    public function activate() {
        update_option('aco_scans_today', 0);
        update_option('aco_last_reset', date('Y-m-d'));
    }
}

new AIContentOptimizerLite();

// Inline admin.js for single file
$admin_js = "
<script>
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function(e) {
        e.preventDefault();
        var content = $('#aco-content').val();
        if (!content) return;
        $('#aco-results').html('<p>Analyzing...</p>');
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            content: content
        }, function(resp) {
            if (resp.success) {
                var r = resp.data;
                var html = '<h3>Analysis Results:</h3>' +
                    '<p><strong>Word Count:</strong> ' + r.word_count + '</p>' +
                    '<p><strong>Readability Score:</strong> ' + r.readability_score + ' (Higher is better)</p>' +
                    '<p><strong>Top Keywords:</strong> ' + r.keywords.join(', ') + '</p>' +
                    '<ul>';
                r.suggestions.forEach(function(s) { html += '<li>' + s + '</li>'; });
                html += '</ul>';
                $('#aco-results').html(html);
            } else {
                $('#aco-results').html('<p class="error">' + resp.data + '</p>');
            }
        });
    });
});
</script>
";
add_action('admin_footer-settings_page_ai-content-optimizer', function() use ($admin_js) { echo $admin_js; });
