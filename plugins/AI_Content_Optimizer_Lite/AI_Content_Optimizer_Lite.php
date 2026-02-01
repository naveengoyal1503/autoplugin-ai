/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Boost SEO with AI-powered content analysis and optimization suggestions for WordPress posts and pages.
 * Version: 1.0.0
 * Author: Your Name
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
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
            array($this, 'settings_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('settings_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), self::VERSION, true);
        wp_localize_script('aco-script', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'premium_url' => self::PREMIUM_URL
        ));
    }

    public function settings_page() {
        if (isset($_POST['aco_analyze'])) {
            $this->handle_analyze();
        }
        include plugin_dir_path(__FILE__) . 'settings-page.php';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        if (strlen($content) > 2000) { // Free limit
            wp_send_json_error('Content too long. Upgrade to premium for unlimited analysis.');
        }

        $analysis = $this->perform_basic_analysis($content);
        wp_send_json_success($analysis);
    }

    private function perform_basic_analysis($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability_score = $this->calculate_flesch_reading_ease($content);

        $suggestions = array();
        if ($word_count < 300) {
            $suggestions[] = 'Add more content for better SEO. Aim for 300+ words.';
        }
        if ($readability_score > 60) {
            $suggestions[] = 'Simplify language for better readability.';
        }
        if (substr_count(strtolower($content), 'keyword') === 0) { // Placeholder for keyword
            $suggestions[] = 'Include primary keyword more frequently.';
        }

        return array(
            'word_count' => $word_count,
            'sentence_count' => $sentence_count,
            'avg_sentence_length' => $word_count > 0 ? round($word_count / $sentence_count, 1) : 0,
            'readability_score' => round($readability_score, 1),
            'suggestions' => $suggestions,
            'is_premium' => false,
            'limit_notice' => 'Free version limited to 2000 characters. ' . '<a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade for AI insights & unlimited use</a>.'
        );
    }

    private function calculate_flesch_reading_ease($text) {
        $text = strip_tags($text);
        $word_count = str_word_count($text);
        $sentence_count = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentence_count);
        $syllable_count = $this->count_syllables($text);

        if ($sentence_count === 0 || $word_count === 0) {
            return 0;
        }

        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $rules = array(
            '/(?!ing|ed)\b[^aeiou][a-z]+y\b/',
            '/\b[^aeiou]{2,}tion\b/',
            '/(?!e)\b[^aeiou][a-z]+e\b/',
            '/\b[^aeiou]+ing\b/',
            '/\b[^aeiou]+ed\b/',
            '/ia/',
            '/ou/',
            '/eau/',
            '/ii/',
            '/[aeiouy]{2,}/',
            '/[dt]ch/',
            '/[cg]le|ls?$/'
        );
        foreach ($rules as $rule) {
            $text = preg_replace($rule, '', $text);
        }
        $words = explode(' ', trim($text));
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += max(1, substr_count($word, 'a') + substr_count($word, 'e') + substr_count($word, 'i') + substr_count($word, 'o') + substr_count($word, 'u') + substr_count($word, 'y'));
        }
        return $syllables;
    }

    public function activate() {
        add_option('aco_version', self::VERSION);
    }
}

new AIContentOptimizerLite();

// Inline settings-page.php content
$settings_page_content = '<div class="wrap">
    <h1>AI Content Optimizer Lite</h1>
    <p>Paste your content below for instant SEO analysis (free version limited to 2000 characters).</p>
    <form method="post">
        <textarea id="aco-content" name="aco_content" rows="10" cols="80" placeholder="Paste your post or page content here..."></textarea><br>
        <input type="submit" name="aco_analyze" class="button button-primary" value="Analyze Content">
    </form>
    <div id="aco-results"></div>
    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7;">
        <strong>Go Premium:</strong> Unlock AI-generated suggestions, keyword research, bulk analysis & more! <a href="' . AIContentOptimizerLite::PREMIUM_URL . '" target="_blank">Upgrade Now</a>
    </div>
</div>';

// Note: In a real single file, embed the JS here as well
function aco_add_inline_script() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('form').on('submit', function(e) {
                e.preventDefault();
                var content = $('#aco-content').val();
                if (content.length === 0) return;

                $('#aco-results').html('<p>Analyzing...</p>');

                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    content: content
                }, function(response) {
                    if (response.success) {
                        var res = response.data;
                        var html = '<h3>Analysis Results:</h3>' +
                            '<ul>' +
                            '<li>Words: <strong>' + res.word_count + '</strong></li>' +
                            '<li>Sentences: <strong>' + res.sentence_count + '</strong></li>' +
                            '<li>Avg. Sentence Length: <strong>' + res.avg_sentence_length + '</strong></li>' +
                            '<li>Readability Score: <strong>' + res.readability_score + '</strong> (Higher is easier)</li>' +
                            '</ul>';
                        if (res.suggestions.length > 0) {
                            html += '<h4>Suggestions:</h4><ul>';
                            $.each(res.suggestions, function(i, sug) {
                                html += '<li>' + sug + '</li>';
                            });
                            html += '</ul>';
                        }
                        html += '<p>' + res.limit_notice + '</p>';
                        $('#aco-results').html(html);
                    } else {
                        $('#aco-results').html('<p style="color:red;">Error: ' + response.data + '</p>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'aco_add_inline_script');