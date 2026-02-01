/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability with AI-powered suggestions. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    private static $instance = null;
    private $daily_limit = 5;
    private $usage_count = 0;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            $this->load_usage();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        $usage = get_option('aco_usage_count', 0);
        $limit_reached = $usage >= $this->daily_limit;
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Lite</h1>
            <p>Free daily limit: <?php echo $this->daily_limit; ?> analyses. Used today: <?php echo $usage; ?>.</p>
            <?php if ($limit_reached): ?>
                <p><strong>Upgrade to Pro for unlimited access!</strong> <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
            <?php endif; ?>
            <textarea id="aco-content" rows="10" cols="80" placeholder="Paste your content here..."></textarea>
            <button id="aco-analyze" class="button button-primary" <?php echo $limit_reached ? 'disabled' : ''; ?>>Analyze Content</button>
            <div id="aco-results"></div>
        </div>
        <?php
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        $this->load_usage();
        if ($this->usage_count >= $this->daily_limit) {
            wp_send_json_error('Daily limit reached. Upgrade to premium!');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('Content is empty.');
        }

        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $seo_score = min(100, 50 + ($word_count / 10) + ($readability / 2));
        $suggestions = $this->generate_suggestions($content, $seo_score);

        $this->usage_count++;
        update_option('aco_usage_count', $this->usage_count);

        wp_send_json_success(array(
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'seo_score' => round($seo_score),
            'suggestions' => $suggestions
        ));
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/s', $text) ?: 1;
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
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy](?![aeiouy])/', $word);
        }
        return max(1, $syllables);
    }

    private function generate_suggestions($content, $seo_score) {
        $suggestions = array();
        if ($seo_score < 70) {
            $suggestions[] = 'Add more keywords related to your topic.';
        }
        if (str_word_count($content) < 300) {
            $suggestions[] = 'Aim for 300+ words for better SEO.';
        }
        $suggestions[] = 'Use short paragraphs and subheadings for readability.';
        return $suggestions;
    }

    private function load_usage() {
        $today = date('Y-m-d');
        $stored_date = get_option('aco_usage_date');
        if ($stored_date !== $today) {
            $this->usage_count = 0;
            update_option('aco_usage_date', $today);
            update_option('aco_usage_count', 0);
        } else {
            $this->usage_count = get_option('aco_usage_count', 0);
        }
    }

    public function activate() {
        update_option('aco_usage_date', date('Y-m-d'));
        update_option('aco_usage_count', 0);
    }

    public function deactivate() {}
}

AIContentOptimizerLite::get_instance();

// Inline JS for simplicity (self-contained)
function aco_add_inline_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function(e) {
            e.preventDefault();
            var content = $('#aco-content').val();
            if (!content) return;

            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<h3>Analysis Results:</h3>' +
                               '<p><strong>Word Count:</strong> ' + res.word_count + '</p>' +
                               '<p><strong>Readability Score:</strong> ' + res.readability + '</p>' +
                               '<p><strong>SEO Score:</strong> ' + res.seo_score + '/100</p>' +
                               '<h4>Suggestions:</h4><ul>';
                    $.each(res.suggestions, function(i, sug) {
                        html += '<li>' + sug + '</li>';
                    });
                    html += '</ul>';
                    $('#aco-results').html(html);
                } else {
                    alert(response.data);
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'aco_add_inline_script');