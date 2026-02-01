/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Freemium version with basic features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'limit_reached' => get_option('ai_optimizer_scan_count', 0) >= 5
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-results"></div>';
        echo '<button id="ai-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free version: 5 scans/month. <a href="#" id="go-premium">Go Premium</a> for unlimited.</small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        $scan_count = get_option('ai_optimizer_scan_count', 0);
        if ($scan_count >= 5) {
            wp_send_json_error('Free limit reached. Upgrade to premium!');
        }

        update_option('ai_optimizer_scan_count', $scan_count + 1);

        // Simulated AI analysis (basic keyword density, readability score)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 ? round(206.835 - 1.015 * ($word_count / ($sentence_count > 0 ? $sentence_count : 1)) - 84.6 * (100 / $word_count), 2) : 0;

        $keywords = $this->extract_keywords($content);
        $density = $word_count > 0 ? round(($keywords['count'] * 100 / $word_count), 2) : 0;

        $results = array(
            'word_count' => $word_count,
            'readability_score' => $readability,
            'main_keyword' => $keywords['keyword'],
            'density' => $density,
            'suggestions' => $density < 1 ? 'Add more instances of main keyword.' : 'Good density.',
            'limit_remaining' => 5 - $scan_count - 1
        );

        wp_send_json_success($results);
    }

    private function extract_keywords($content) {
        $content = strtolower(strip_tags($content));
        preg_match_all('/\b\w+\b/', $content, $words);
        $word_freq = array_count_values($words);
        arsort($word_freq);
        $top_keyword = key($word_freq);
        return array('keyword' => $top_keyword, 'count' => $word_freq[$top_keyword]);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock unlimited AI scans and advanced suggestions with <strong>AI Content Optimizer Pro</strong>! <a href="https://example.com/premium" target="_blank">Upgrade now</a></p></div>';
    }

    public function activate() {
        add_option('ai_optimizer_scan_count', 0);
    }
}

new AIContentOptimizerLite();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#ai-analyze-btn').click(function() {
            var post_id = $('#post_ID').val();
            $.post(ai_optimizer.ajax_url, {
                action: 'analyze_content',
                post_id: post_id,
                nonce: ai_optimizer.nonce
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<p><strong>Word Count:</strong> ' + res.word_count + '</p>' +
                               '<p><strong>Readability:</strong> ' + res.readability_score + '</p>' +
                               '<p><strong>Main Keyword:</strong> ' + res.main_keyword + ' (Density: ' + res.density + '%)</p>' +
                               '<p><strong>Suggestion:</strong> ' + res.suggestions + '</p>' +
                               '<p>Scans left: ' + res.limit_remaining + '</p>';
                    $('#ai-optimizer-results').html(html);
                } else {
                    alert(response.data);
                }
            });
        });

        $('#go-premium').click(function(e) {
            e.preventDefault();
            alert('Upgrade to Pro for unlimited features! Visit https://example.com/premium');
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
?>