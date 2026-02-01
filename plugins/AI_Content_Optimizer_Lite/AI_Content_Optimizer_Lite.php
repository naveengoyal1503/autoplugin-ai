/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress post content for SEO and readability with AI-powered insights. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium_user()
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-results">';
        echo '<p><strong>Basic Analysis:</strong> Free for up to 5 scans/day.</p>';
        echo '<textarea id="ai-content-input" rows="4" cols="20" placeholder="Paste content or use editor content..."></textarea>';
        echo '<button id="analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="ai-results"></div>';
        echo '<p id="premium-upsell" style="display:none;"><a href="https://example.com/premium" target="_blank">Upgrade to Premium for unlimited AI rewriting & advanced SEO metrics!</a></p>';
        echo '</div>';
    }

    public function analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium_user() && $this->get_daily_scans() >= 5) {
            wp_send_json_error('Daily limit reached. Upgrade to premium!');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $keywords = $this->extract_keywords($content);
        $suggestions = $this->generate_suggestions($content, $word_count, $readability, $keywords);

        $this->increment_daily_scans();

        wp_send_json_success(array(
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'keywords' => $keywords,
            'suggestions' => $suggestions,
            'premium_teaser' => !$this->is_premium_user()
        ));
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/s', $text);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        if ($sentence_count == 0 || $word_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        preg_match_all('/[aeiouy]+/', $text, $matches);
        return count($matches);
    }

    private function extract_keywords($content) {
        $words = explode(' ', strtolower($content));
        $word_freq = array_count_values(array_filter($words, function($w) {
            return strlen($w) > 4 && !in_array($w, ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out', 'day', 'get']);
        }));
        arsort($word_freq);
        return array_slice(array_keys($word_freq), 0, 5);
    }

    private function generate_suggestions($content, $wc, $readability, $keywords) {
        $sugs = [];
        if ($wc < 300) $sugs[] = 'Add more content to reach 300+ words for better SEO.';
        if ($readability < 60) $sugs[] = 'Improve readability: Use shorter sentences and simpler words.';
        $sugs[] = 'Top keywords: ' . implode(', ', $keywords);
        $sugs[] = 'Add H2/H3 headings and internal links for structure.';
        return $sugs;
    }

    private function get_daily_scans() {
        $today = date('Y-m-d');
        $scans = get_option('ai_optimizer_scans_' . $today, 0);
        return $scans;
    }

    private function increment_daily_scans() {
        $today = date('Y-m-d');
        $scans = $this->get_daily_scans() + 1;
        update_option('ai_optimizer_scans_' . $today, $scans);
    }

    private function is_premium_user() {
        // Simulate premium check (integrate with Freemius or Stripe in full version)
        return false; // Free version
    }

    public function activate() {
        // Reset daily scans on activation
        delete_option('ai_optimizer_scans_' . date('Y-m-d'));
    }
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#analyze-btn').click(function() {
            var content = $('#ai-content-input').val() || tinyMCE.activeEditor.getContent();
            if (!content) {
                alert('Please enter content to analyze.');
                return;
            }
            $('#analyze-btn').prop('disabled', true).text('Analyzing...');
            $.post(ai_optimizer.ajax_url, {
                action: 'analyze_content',
                nonce: ai_optimizer.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<strong>Results:</strong><br>Words: ' + res.word_count + '<br>Readability Score: ' + res.readability + ' (Higher is easier)<br>Keywords: ' + res.keywords.join(', ') + '<br><ul>';
                    res.suggestions.forEach(function(s) { html += '<li>' + s + '</li>'; });
                    html += '</ul>';
                    $('#ai-results').html(html);
                    if (res.premium_teaser) $('#premium-upsell').show();
                } else {
                    alert(response.data);
                }
                $('#analyze-btn').prop('disabled', false).text('Analyze Content');
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');