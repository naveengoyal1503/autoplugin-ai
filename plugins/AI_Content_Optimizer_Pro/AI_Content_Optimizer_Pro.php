/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upsell.
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
    public $premium_key = '';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('wp_ajax_aco_upgrade', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->premium_key = get_option('aco_premium_key', '');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aco-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin', 'aco_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aco_nonce'),
                'is_premium' => !empty($this->premium_key)
            ));
        }
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = wp_strip_all_tags(get_post_field('post_content', $post->ID));
        echo '<div id="aco-analysis-result">';
        if (empty($content)) {
            echo '<p>' . __('Write some content to analyze.', 'ai-content-optimizer') . '</p>';
        } else {
            echo '<button id="aco-analyze-btn" class="button button-primary">' . __('Analyze Content', 'ai-content-optimizer') . '</button>';
            echo '<div id="aco-loading" style="display:none;">' . __('Analyzing...', 'ai-content-optimizer') . '</div>';
            echo '<div id="aco-result"></div>';
        }
        echo '</div>';
        if (empty($this->premium_key)) {
            echo '<div style="margin-top:10px;padding:10px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:4px;">';
            echo '<p><strong>' . __('Go Premium!</strong> Unlock AI rewriting, advanced keywords, and more for $4.99/mo.', 'ai-content-optimizer') . '</p>';
            echo '<input type="text" id="aco-premium-key" placeholder="Enter premium key" style="width:60%;">';
            echo '<button id="aco-upgrade-btn" class="button button-secondary">Activate Premium</button>';
            echo '<p><a href="https://example.com/premium" target="_blank">Buy Now</a></p>';
            echo '</div>';
        }
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        $post_id = intval($_POST['post_id']);
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $keywords = $this->extract_keywords($content, 5);

        $result = array(
            'word_count' => $word_count,
            'readability_score' => round($readability, 2),
            'readability_grade' => $this->get_readability_grade($readability),
            'avg_sentence_length' => $sentence_count > 0 ? round($word_count / $sentence_count, 1) : 0,
            'top_keywords' => $keywords,
            'is_premium' => !empty($this->premium_key),
            'suggestions' => $this->generate_basic_suggestions($word_count, $readability)
        );

        if (!empty($this->premium_key)) {
            // Simulate premium AI features (in real: integrate OpenAI API)
            $result['ai_rewrite'] = $this->mock_ai_rewrite($content);
            $result['premium_keywords'] = $this->mock_premium_keywords($content);
        }

        wp_send_json_success($result);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentence_count);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        if ($sentence_count == 0 || $word_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy]/', $word) - preg_match_all('/ed|ing|es\b/', $word);
        }
        return $syllables;
    }

    private function get_readability_grade($score) {
        if ($score > 90) return __('Very Easy', 'ai-content-optimizer');
        if ($score > 80) return __('Easy', 'ai-content-optimizer');
        if ($score > 70) return __('Fairly Easy', 'ai-content-optimizer');
        if ($score > 60) return __('Standard', 'ai-content-optimizer');
        if ($score > 50) return __('Fairly Difficult', 'ai-content-optimizer');
        if ($score > 30) return __('Difficult', 'ai-content-optimizer');
        return __('Very Difficult', 'ai-content-optimizer');
    }

    private function extract_keywords($content, $limit) {
        $words = explode(' ', strtolower(strip_tags($content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_slice(array_keys($counts), 0, $limit);
    }

    private function generate_basic_suggestions($words, $readability) {
        $sugs = array();
        if ($words < 300) $sugs[] = __('Add more content for better engagement.', 'ai-content-optimizer');
        if ($readability < 60) $sugs[] = __('Simplify sentences for better readability.', 'ai-content-optimizer');
        return $sugs;
    }

    private function mock_ai_rewrite($content) {
        return substr($content, 0, 200) . '... (Premium AI Rewrite: Improved for SEO and engagement)';
    }

    private function mock_premium_keywords($content) {
        return array('premium keyword 1', 'premium keyword 2');
    }

    public function handle_upgrade() {
        check_ajax_referer('aco_nonce', 'nonce');
        $key = sanitize_text_field($_POST['key']);
        // In real: validate with your server
        if (strlen($key) > 10) { // Mock validation
            update_option('aco_premium_key', $key);
            wp_send_json_success('Premium activated!');
        } else {
            wp_send_json_error('Invalid key.');
        }
    }

    public function activate() {
        // Create assets dir if needed
        $upload_dir = plugin_dir_path(__FILE__) . 'assets';
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
    }
}

AIContentOptimizer::get_instance();

// Mock JS files content (in real, create separate files)
function aco_create_js_files() {
    $js_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($js_dir . 'admin.js')) {
        file_put_contents($js_dir . 'admin.js', "jQuery(document).ready(function($) {
    $('#aco-analyze-btn').click(function() {
        var content = $('#' + $('#post_ID').val() + '_content_ifr').contents().find('body').html() || $('textarea#content').val();
        $('#aco-loading').show();
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            post_id: $('#post_ID').val(),
            content: content
        }, function(resp) {
            $('#aco-loading').hide();
            if (resp.success) {
                var r = resp.data;
                var html = '<h4>Analysis:</h4><p><strong>Words:</strong> ' + r.word_count + '</p><p><strong>Readability:</strong> ' + r.readability_score + ' (' + r.readability_grade + ')</p>';
                if (r.avg_sentence_length) html += '<p><strong>Avg Sentence:</strong> ' + r.avg_sentence_length + ' words</p>';
                html += '<p><strong>Keywords:</strong> ' + r.top_keywords.join(', ') + '</p>';
                if (r.suggestions.length) html += '<p><strong>Suggestions:</strong> ' + r.suggestions.join('; ') + '</p>';
                if (r.ai_rewrite) html += '<p><strong>AI Rewrite:</strong> ' + r.ai_rewrite + '</p>';
                $('#aco-result').html(html);
            }
        });
    });
    $('#aco-upgrade-btn').click(function() {
        $.post(aco_ajax.ajax_url, {
            action: 'aco_upgrade',
            nonce: aco_ajax.nonce,
            key: $('#aco-premium-key').val()
        }, function(resp) {
            if (resp.success) {
                location.reload();
            } else {
                alert(resp.data);
            }
        });
    });
});");
    }
    if (!file_exists($js_dir . 'frontend.js')) {
        file_put_contents($js_dir . 'frontend.js', "// Frontend enhancements - e.g., site-wide readability scores");
    }
}
add_action('init', 'aco_create_js_files');
?>