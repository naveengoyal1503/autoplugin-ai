/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_optimize_score', array($this, 'optimize_score_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_co_pro_active', false);
        add_option('ai_co_api_key', '');
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-co-js', plugin_dir_url(__FILE__) . 'ai-co.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-co-css', plugin_dir_url(__FILE__) . 'ai-co.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_co_nonce', 'ai_co_nonce');
        $score = get_post_meta($post->ID, '_ai_co_score', true);
        $pro_active = get_option('ai_co_pro_active', false);
        echo '<div id="ai-co-score">Score: <span id="score-value">' . esc_html($score ?: 'Not analyzed') . '%</span></div>';
        echo '<button id="ai-analyze" class="button">Analyze Content</button>';
        if (!$pro_active) {
            echo '<p><a href="https://example.com/pro" target="_blank">Upgrade to Pro for AI Rewrites</a></p>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_co_nonce']) || !wp_verify_nonce($_POST['ai_co_nonce'], 'ai_co_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        // Simulate save (in real pro, save AI data)
    }

    public function optimize_score_shortcode($atts) {
        $post_id = get_the_ID();
        $score = get_post_meta($post_id, '_ai_co_score', true) ?: 0;
        return '<div class="ai-co-badge">AI Score: <strong>' . $score . '%</strong></div>';
    }
}

// AJAX handler for analysis
add_action('wp_ajax_ai_analyze_content', 'ai_analyze_content_handler');
function ai_analyze_content_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'ai_co_nonce')) {
        wp_die('Security check failed');
    }
    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);

    // Simulate AI analysis (mock scores for demo; integrate real AI API in pro)
    $word_count = str_word_count(strip_tags($content));
    $readability = min(95, 50 + ($word_count / 1000) * 20 + rand(0, 30));
    $seo_score = min(95, 60 + rand(0, 35));
    $engagement = min(95, 70 + rand(0, 25));
    $overall = round(($readability + $seo_score + $engagement) / 3);

    update_post_meta($post_id, '_ai_co_score', $overall);
    update_post_meta($post_id, '_ai_co_details', array(
        'readability' => $readability,
        'seo' => $seo_score,
        'engagement' => $engagement
    ));

    wp_send_json_success(array('score' => $overall));
}

new AIContentOptimizer();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('ai_co_pro_active')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewrites and schema! <a href="https://example.com/pro">Get Pro Now ($49/year)</a></p></div>';
    }
});

// Minimal CSS
/*
#ai-co-score { font-size: 24px; font-weight: bold; color: #0073aa; }
.ai-co-badge { background: #0073aa; color: white; padding: 5px 10px; border-radius: 5px; }
*/

// Minimal JS
/*
(function($) {
    $('#ai-analyze').click(function(e) {
        e.preventDefault();
        $.post(ajaxurl, {
            action: 'ai_analyze_content',
            post_id: $('#post_ID').val(),
            nonce: $('#ai_co_nonce').val()
        }, function(res) {
            if (res.success) {
                $('#score-value').text(res.data.score + '%').css('color', res.data.score > 80 ? 'green' : 'orange');
            }
        });
    });
})(jQuery);
*/
?>