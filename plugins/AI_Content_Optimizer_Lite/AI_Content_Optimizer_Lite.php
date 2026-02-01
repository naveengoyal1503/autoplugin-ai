/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Boost your content with AI-powered analysis for readability, SEO, and engagement. Premium features available.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

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
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'default'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">' . __('Analyze Content', 'ai-content-optimizer') . '</button>';
        echo '<p><small>' . __('Premium: AI Rewrite, Bulk Optimize', 'ai-content-optimizer') . ' <a href="https://example.com/premium" target="_blank">' . __('Upgrade Now', 'ai-content-optimizer') . '</a></small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (free version: basic metrics)
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->calculate_flesch_reading_ease($content);
        $seo_score = min(100, (50 + ($word_count > 300 ? 20 : 0) + ($this->has_keywords($content) ? 30 : 0)));
        $engagement = min(100, (60 + (substr_count($content, 'http') > 2 ? 20 : 0) + ($this->has_lists($content) ? 20 : 0)));

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'seo_score' => $seo_score,
            'engagement' => $engagement,
            'tips' => $this->generate_tips($readability, $seo_score, $engagement)
        );

        wp_send_json_success($results);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/s', $text) ?: 1;
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables(strip_tags($text));
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $rules = array(
            '/(?!ing|ed)[aeiouy]+/',
            '/^y([aeiou])/', '/^[^aeiouy]*/',
        );
        $count = 0;
        foreach (explode(' ', $text) as $word) {
            if (strlen($word) < 3) continue;
            $syls = 0;
            foreach ($rules as $rule) {
                if (preg_match($rule, $word, $m)) $syls++;
            }
            $count += max(1, $syls);
        }
        return $count;
    }

    private function has_keywords($content) {
        $keywords = array('the', 'and', 'for'); // Basic check
        foreach ($keywords as $kw) {
            if (stripos($content, $kw) !== false) return true;
        }
        return false;
    }

    private function has_lists($content) {
        return preg_match('/<ul|<ol/', $content);
    }

    private function generate_tips($readability, $seo, $engagement) {
        $tips = array();
        if ($readability < 60) $tips[] = 'Improve readability: Use shorter sentences.';
        if ($seo < 70) $tips[] = 'Boost SEO: Add more keywords and headings.';
        if ($engagement < 70) $tips[] = 'Increase engagement: Add lists or links.';
        return $tips;
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AIContentOptimizer::get_instance();

// Inline admin.js for single file
$admin_js = "
<script>
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function(e) {
        e.preventDefault();
        var post_id = $('#post_ID').val();
        $('#aco-results').html('<p>Analyzing...</p>');
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            post_id: post_id,
            nonce: aco_ajax.nonce
        }, function(response) {
            if (response.success) {
                var r = response.data;
                var html = '<ul>' +
                    '<li><strong>Words:</strong> ' + r.word_count + '</li>' +
                    '<li><strong>Readability:</strong> ' + r.readability + '</li>' +
                    '<li><strong>SEO Score:</strong> ' + r.seo_score + '%</li>' +
                    '<li><strong>Engagement:</strong> ' + r.engagement + '%</li>' +
                    '</ul>';
                if (r.tips.length) {
                    html += '<p><strong>Tips:</strong><ul>';
                    $.each(r.tips, function(i, tip) { html += '<li>' + tip + '</li>'; });
                    html += '</ul></p>';
                }
                $('#aco-results').html(html);
            }
        });
    });
});
</script>
";
add_action('admin_footer-post.php', function() use ($admin_js) { echo $admin_js; });
add_action('admin_footer-post-new.php', function() use ($admin_js) { echo $admin_js; });
