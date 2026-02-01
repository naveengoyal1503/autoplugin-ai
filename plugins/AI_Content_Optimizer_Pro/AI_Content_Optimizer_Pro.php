/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Boost SEO with AI-powered content analysis, keyword suggestions, and readability scores.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (!session_id()) {
            session_start();
        }
    }

    public function activate() {
        add_option('aco_scan_count', 0);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_add_inline_script('aco-admin', 'const FREE_SCANS = 3;');
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), array('post', 'page'), 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p id="aco-status"></p>';
        echo '<div id="aco-premium-upsell" style="display:none;"><p><strong>Upgrade to Pro for unlimited scans & AI suggestions!</strong> <a href="https://example.com/premium" target="_blank" class="button">Get Pro</a></p></div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        $scan_count = get_option('aco_scan_count', 0) + 1;
        update_option('aco_scan_count', $scan_count);

        if ($scan_count > 3 && !$this->is_premium()) {
            wp_send_json_error('Free scans limit reached. Upgrade to Pro!');
        }

        $word_count = str_word_count($content);
        $readability = $this->calculate_readability($content);
        $keywords = $this->extract_keywords($content);
        $seo_score = min(100, ($word_count / 10) + ($readability * 20) + (count($keywords) * 5));

        ob_start();
        ?>
        <div class="aco-score" style="background: linear-gradient(90deg, #ff0000 <?=100-$seo_score?>%, #00ff00 <?=100-$seo_score?>%); height: 20px; border-radius: 10px; margin: 10px 0;"></div>
        <p><strong>SEO Score:</strong> <?=round($seo_score)?>/100</p>
        <ul>
            <li>Words: <?=$word_count?> (Aim: 500+)</li>
            <li>Readability: <?=round($readability,1)?> (Aim: 60+)</li>
            <li>Keywords: <strong><?=implode(', ', array_slice($keywords, 0, 5))?></strong></li>
        </ul>
        <?php
        $results = ob_get_clean();

        wp_send_json_success($results);
    }

    private function calculate_readability($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count($text);
        if ($sentence_count == 0 || $words == 0) return 0;
        $syl = $this->count_syllables($text);
        $flesch = 206.835 - 1.015 * ($words / $sentence_count) - 84.6 * ($syl / $words);
        return min(100, max(0, $flesch));
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $syllables = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            $vowels = preg_match_all('/[aeiouy]/', $word);
            $syllables += $vowels > 0 ? $vowels : 1;
        }
        return $syllables;
    }

    private function extract_keywords($content) {
        $words = explode(' ', strtolower(strip_tags($content)));
        $word_freq = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($word_freq);
        return array_keys(array_slice($word_freq, 0, 10));
    }

    private function is_premium() {
        return get_option('aco_premium_key') !== false;
    }

    public function premium_notice() {
        if (!$this->is_premium() && get_option('aco_scan_count', 0) > 3) {
            echo '<div class="notice notice-warning"><p>AI Content Optimizer: <strong>Free scans used up!</strong> <a href="https://example.com/premium">Upgrade to Pro</a> for unlimited access.</p></div>';
        }
    }
}

AIContentOptimizer::get_instance();

// Dummy JS file content would be enqueued, but for single file, inline it
add_action('admin_footer-post.php', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        var $btn = $(this), $results = $('#aco-results'), $status = $('#aco-status');
        $btn.prop('disabled', true).text('Analyzing...');
        $status.text('Running AI analysis...');

        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            post_id: $('#post_ID').val()
        }, function(res) {
            if (res.success) {
                $results.html(res.data);
            } else {
                $results.html('<p style="color:red;">Error: ' + res.data + '</p>');
                $('#aco-premium-upsell').show();
            }
            $status.empty();
        }).always(function() {
            $btn.prop('disabled', false).text('Analyze Content');
        });
    });
});
</script>
<?php });