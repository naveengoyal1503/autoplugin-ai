/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress post content for SEO and readability with AI-powered suggestions. Freemium version.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (get_option('aco_usage_count', 0) >= 5) {
            add_action('admin_notices', array($this, 'usage_limit_notice'));
        }
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'limit_reached' => get_option('aco_usage_count', 0) >= 5
        ));
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', __('AI Content Optimizer', 'ai-content-optimizer'), array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">' . __('Analyze Content', 'ai-content-optimizer') . '</button>';
        echo '<p id="aco-status"></p>';
        if (get_option('aco_usage_count', 0) >= 5) {
            echo '<p class="notice notice-warning"><strong>' . __('Usage limit reached. ', 'ai-content-optimizer') . '</strong><a href="https://example.com/premium" target="_blank">' . __('Upgrade to Premium', 'ai-content-optimizer') . '</a></p>';
        }
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (get_option('aco_usage_count', 0) >= 5) {
            wp_die(json_encode(array('error' => 'limit_reached')));
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
        $word_count = str_word_count(strip_tags($content));
        $readability_score = $this->calculate_readability($content);
        $seo_score = min(100, 50 + ($word_count / 10)); // Simple mock
        $suggestions = $this->generate_suggestions($content, $word_count, $readability_score);

        update_option('aco_usage_count', get_option('aco_usage_count', 0) + 1);

        wp_die(json_encode(array(
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'word_count' => $word_count,
            'suggestions' => $suggestions
        )));
    }

    private function calculate_readability($content) {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count(strip_tags($content));
        if ($sentence_count == 0) return 0;
        $words_per_sentence = $words / $sentence_count;
        return max(0, 100 - ($words_per_sentence - 15) * 5); // Mock Flesch-like score
    }

    private function generate_suggestions($content, $word_count, $readability) {
        $suggestions = array();
        if ($word_count < 300) {
            $suggestions[] = 'Add more content to reach 300+ words for better SEO.';
        }
        if ($readability < 60) {
            $suggestions[] = 'Improve readability: Use shorter sentences and simpler words.';
        }
        $suggestions[] = 'Include target keywords naturally in the first 100 words.';
        $suggestions[] = 'Add subheadings (H2/H3) for better structure.';
        return $suggestions;
    }

    public function usage_limit_notice() {
        echo '<div class="notice notice-info"><p>' . sprintf(__('AI Content Optimizer: You have reached the free limit of 5 analyses. <a href="%s" target="_blank">Upgrade to Premium</a> for unlimited access!', 'ai-content-optimizer'), 'https://example.com/premium') . '</p></div>';
    }

    public function activate() {
        add_option('aco_usage_count', 0);
    }
}

new AIContentOptimizerLite();

// Enqueue JS (inline for single file)
function aco_enqueue_js() {
    if (isset($_GET['post']) || isset($_GET['post_type'])) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function(e) {
                e.preventDefault();
                var postId = $('#post_ID').val();
                if (aco_ajax.limit_reached) {
                    $('#aco-status').html('<span style="color:red;">Limit reached. Upgrade to premium.</span>');
                    return;
                }
                $('#aco-status').html('Analyzing...');
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    post_id: postId
                }, function(response) {
                    var data = JSON.parse(response);
                    if (data.error) {
                        $('#aco-status').html('<span style="color:red;">Error: ' + data.error + '</span>');
                        return;
                    }
                    var html = '<strong>SEO Score: ' + data.seo_score + '%</strong><br>' +
                               '<strong>Readability: ' + data.readability_score + '%</strong><br>' +
                               'Words: ' + data.word_count + '<br>';
                    html += '<h4>Suggestions:</h4><ul>';
                    $.each(data.suggestions, function(i, sug) {
                        html += '<li>' + sug + '</li>';
                    });
                    html += '</ul>';
                    $('#aco-results').html(html);
                    $('#aco-status').html('');
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer-post.php', 'aco_enqueue_js');
add_action('admin_footer-post-new.php', 'aco_enqueue_js');
?>