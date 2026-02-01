/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Premium version available for advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_lite_activated', true);
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'post.php' && $hook != 'post-new.php') return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content (3/5 free uses left)</button>';
        echo '<p><small><a href="https://example.com/premium" target="_blank">Go Premium</a> for unlimited analyzes & AI suggestions!</small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        $uses = get_option('aco_lite_uses', 0);
        if ($uses >= 5) {
            wp_send_json_error('Free limit reached. <a href="https://example.com/premium" target="_blank">Upgrade to Premium</a>');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulated AI analysis (basic metrics)
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->flesch_readability($content);
        $keywords = $this->extract_keywords($content);
        $seo_score = min(100, (50 + ($word_count > 500 ? 20 : 0) + ($readability > 60 ? 20 : 0) + (count($keywords) > 3 ? 10 : 0)));

        update_option('aco_lite_uses', $uses + 1);

        ob_start();
        ?>
        <div class="aco-score">SEO Score: <strong><?php echo $seo_score; ?>/100</strong></div>
        <ul>
            <li>Word Count: <?php echo $word_count; ?></li>
            <li>Readability: <?php echo round($readability, 1); ?>/100</li>
            <li>Keywords: <?php echo implode(', ', array_slice($keywords, 0, 5)); ?></li>
        </ul>
        <p><em>Premium: Get AI rewrite suggestions & bulk optimization!</em></p>
        <?php
        $results = ob_get_clean();
        wp_send_json_success($results);
    }

    private function flesch_readability($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = explode(' ', $text);
        $word_count = count(array_filter($words));
        $syllables = $this->count_syllables($text);
        if ($word_count == 0 || $sentence_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllables / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy](?![aeiouy])/i', $word);
        }
        return $syllables;
    }

    private function extract_keywords($content) {
        $words = explode(' ', strip_tags(strtolower($content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_keys(array_slice($counts, 0, 5, true));
    }

    public function premium_notice() {
        if (!current_user_can('manage_options') || get_option('aco_lite_activated') !== true) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong>: Unlimited scans, AI rewrites & more! <a href="https://example.com/premium" target="_blank">Get it now</a></p></div>';
    }
}

new AIContentOptimizerLite();

// JS file content would be enqueued separately, but for single-file, inline it
add_action('admin_footer-post.php', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        var post_id = $('#post_ID').val();
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            post_id: post_id,
            nonce: aco_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#aco-results').html(response.data);
            } else {
                $('#aco-results').html('<p style="color:red;">' + response.data + '</p>');
            }
        });
    });
});
</script>
<?php });

// Prevent direct access to JS file simulation
if (isset($_GET['aco-js'])) {
    header('Content-Type: application/javascript');
    echo '// Inline JS loaded';
    exit;
}
