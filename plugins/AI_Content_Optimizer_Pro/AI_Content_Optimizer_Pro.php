/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Free basic features; premium for advanced AI tools.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_URL = 'https://example.com/premium-upgrade?ref=plugin';
    const VERSION = '1.0.0';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), self::VERSION, true);
        wp_localize_script('aco-admin', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => false
        ));
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), array('post', 'page'), 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-analysis-result">Click "Analyze" to scan your content.</div>';
        echo '<button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-premium-nag" style="margin-top:10px; padding:10px; background:#fff3cd; border:1px solid #ffeaa7; border-radius:4px;">
                <p><strong>Go Premium!</strong> Unlock AI rewriting, keyword research, and unlimited scans for $9/mo. <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade Now</a></p>
              </div>';
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $seo_score = min(100, (50 + ($word_count > 300 ? 20 : 0) + ($readability > 60 ? 30 : 0)));

        $result = array(
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'readability_label' => $this->get_readability_label($readability),
            'seo_score' => $seo_score,
            'recommendations' => $this->get_recommendations($word_count, $readability)
        );

        wp_send_json_success($result);
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
        $rules = array(
            '/(?!ing|ed)\b[^aeiou]*[aeiou]+[y?]//' => 1,
            '/\b[^aeiou]+[aeiou]+[y?][^aeiou]*/' => 1
        );
        $count = 0;
        foreach ($rules as $pattern => $syls) {
            $count += preg_match_all($pattern, $text, $matches);
        }
        return $count;
    }

    private function get_readability_label($score) {
        if ($score > 90) return 'Very Easy';
        if ($score > 80) return 'Easy';
        if ($score > 70) return 'Fairly Easy';
        if ($score > 60) return 'Standard';
        if ($score > 50) return 'Fairly Difficult';
        if ($score > 30) return 'Difficult';
        return 'Very Difficult';
    }

    private function get_recommendations($words, $readability) {
        $recs = array();
        if ($words < 300) $recs[] = 'Add more content (aim for 300+ words).';
        if ($readability < 60) $recs[] = 'Simplify sentences for better readability.';
        $recs[] = '<strong>Premium:</strong> Get AI-powered keyword suggestions and auto-rewrites.';
        return $recs;
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function aco_admin_js() {
    if (!wp_script_is('aco-admin', 'enqueued')) return;
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#aco-analyze-btn').click(function() {
        var post_id = $('#post_ID').val();
        $('#aco-analysis-result').html('Analyzing...');
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            post_id: post_id
        }, function(response) {
            if (response.success) {
                var r = response.data;
                var html = '<p><strong>Word Count:</strong> ' + r.word_count + '</p>' +
                          '<p><strong>Readability:</strong> ' + r.readability + ' (' + r.readability_label + ')</p>' +
                          '<p><strong>SEO Score:</strong> ' + r.seo_score + '/100</p>' +
                          '<ul>';
                $.each(r.recommendations, function(i, rec) {
                    html += '<li>' + rec + '</li>';
                });
                html += '</ul>';
                $('#aco-analysis-result').html(html);
            } else {
                $('#aco-analysis-result').html('Error: ' + response.data);
            }
        });
    });
});
</script>
<?php
}
add_action('admin_footer-post.php', 'aco_admin_js');
add_action('admin_footer-post-new.php', 'aco_admin_js');