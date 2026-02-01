/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered plugin that analyzes and optimizes post readability, SEO, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        }
    }

    public function activate() {
        add_option('aco_premium_active', false);
    }

    public function add_action_links($links) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        $links[] = '<a href="https://example.com/docs">Docs</a>';
        return $links;
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'aco-style.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box_nonce', 'aco_nonce');
        echo '<div id="aco-results">';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-score"></div>';
        echo '<div id="aco-suggestions"></div>';
        echo '</div>';
        if (!get_option('aco_premium_active', false)) {
            echo '<p><strong>Premium:</strong> Unlock AI suggestions, bulk optimize, and more! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>';
        }
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(($word_count / $sentence_count), 1) : 0;
        $score = $this->calculate_score($word_count, $readability);

        $results = array(
            'score' => $score,
            'word_count' => $word_count,
            'avg_sentence_length' => $readability,
            'premium_teaser' => !get_option('aco_premium_active', false)
        );

        // Premium AI-like suggestions (simulated)
        if (get_option('aco_premium_active', false)) {
            $suggestions = $this->generate_ai_suggestions($content);
            $results['suggestions'] = $suggestions;
        } else {
            $results['suggestions'] = array('Upgrade to Premium for AI-powered suggestions!');
        }

        wp_send_json_success($results);
    }

    private function calculate_score($words, $readability) {
        $score = 50;
        if ($words > 300 && $words < 1500) $score += 20;
        if ($readability > 10 && $readability < 25) $score += 20;
        if (preg_match_all('/h[1-6]/i', $content ?? '')) $score += 10;
        return min(100, $score);
    }

    private function generate_ai_suggestions($content) {
        $suggestions = array();
        if (str_word_count($content) < 300) {
            $suggestions[] = 'Add more content: Aim for 500-1000 words for better engagement.';
        }
        if (!preg_match('/<h[2-3]/', $content)) {
            $suggestions[] = 'Use H2/H3 headings to improve structure and SEO.';
        }
        $suggestions[] = 'Include bullet points or numbered lists for scannability.';
        $suggestions[] = 'Add a call-to-action at the end.';
        return $suggestions;
    }
}

AIContentOptimizer::get_instance();

// Freemium check - simulate license
add_action('admin_init', function() {
    if (isset($_GET['aco_activate_premium']) && wp_verify_nonce($_GET['nonce'], 'aco_premium')) {
        update_option('aco_premium_active', true);
        wp_redirect(admin_url('plugins.php?p=1'));
        exit;
    }
});

// Enqueue dummy assets (inline for single file)
function aco_inline_scripts() {
    if (!is_admin()) return;
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, array('post', 'page'))) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function() {
            var content = $('#content').val() || tinyMCE.activeEditor.getContent();
            $('#aco-results').append('<p>Analyzing...</p>');
            $.post(aco_ajax.ajaxurl, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                content: content
            }, function(response) {
                $('#aco-results').html('<p><strong>Score: ' + response.data.score + '%</strong></p><p>Words: ' + response.data.word_count + '</p><p>Avg Sentence: ' + response.data.avg_sentence_length + '</p>');
                if (response.data.suggestions) {
                    var sugg = '<ul>';
                    $.each(response.data.suggestions, function(i, s) { sugg += '<li>' + s + '</li>'; });
                    sugg += '</ul>';
                    $('#aco-results').append(sugg);
                }
            });
        });
    });
    </script>
    <style>
    #aco-results { margin: 10px 0; }
    #aco-score { font-size: 24px; color: #0073aa; }
    </style>
    <?php
}
add_action('admin_footer', 'aco_inline_scripts');
