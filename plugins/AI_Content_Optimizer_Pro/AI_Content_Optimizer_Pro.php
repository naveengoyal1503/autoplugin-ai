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
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_settings', array('api_key' => '', 'pro' => false));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'pro' => get_option('ai_content_optimizer_settings')['pro'] ?? false
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce_field');
        $score = get_post_meta($post->ID, '_ai_score', true);
        echo '<div id="ai-score">Score: <strong>' . esc_html($score ?: 'Not analyzed') . '</strong></div>';
        echo '<button id="analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<button id="optimize-btn" class="button" style="display:none;">Optimize with AI</button>';
        echo '<div id="ai-suggestions"></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce_field']) || !wp_verify_nonce($_POST['ai_optimizer_nonce_field'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate AI analysis (in pro version, integrate real API like OpenAI)
        $score = rand(60, 95);
        $suggestions = $this->generate_suggestions($content);
        $is_pro = get_option('ai_content_optimizer_settings')['pro'] ?? false;

        wp_send_json_success(array(
            'score' => $score,
            'suggestions' => $suggestions,
            'pro_required' => !$is_pro && rand(0,1)
        ));
    }

    private function generate_suggestions($content) {
        $word_count = str_word_count($content);
        $suggestions = array();
        if ($word_count < 300) $suggestions[] = 'Add more content: Aim for 300+ words for better SEO.';
        $suggestions[] = 'Improve readability: Use shorter sentences and paragraphs.';
        $suggestions[] = 'Add keywords: Include primary keyword 2-3 times naturally.';
        if (rand(0,1)) $suggestions[] = 'Pro feature: Auto-generate optimized title and meta.';
        return $suggestions;
    }
}

new AIContentOptimizer();

// Freemium upsell page
add_action('admin_menu', function() {
    add_plugins_page('AI Content Optimizer Pro', 'AI Optimizer Pro', 'manage_options', 'ai-optimizer-pro', function() {
        echo '<div class="wrap"><h1>Upgrade to Pro</h1><p>Unlock unlimited optimizations, real AI integration, and more for $49/year.</p><a href="https://example.com/pro" class="button button-primary">Buy Pro</a></div>';
    });
});

// Dummy JS/CSS placeholders (in real plugin, add files)
function ai_optimizer_placeholder_assets() {
    ?>
    <style>
    #ai-score { font-size: 18px; margin-bottom: 10px; }
    #ai-suggestions { margin-top: 10px; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('#analyze-btn').click(function() {
            $.post(ai_optimizer.ajax_url, {
                action: 'optimize_content',
                post_id: $('#post_ID').val(),
                nonce: ai_optimizer.nonce
            }, function(res) {
                if (res.success) {
                    $('#ai-score strong').text(res.data.score);
                    let html = '<ul>';
                    res.data.suggestions.forEach(s => html += '<li>' + s + '</li>');
                    html += '</ul>';
                    if (res.data.pro_required) html += '<p><strong>Upgrade to Pro for full AI optimization!</strong></p>';
                    $('#ai-suggestions').html(html);
                    $('#optimize-btn').show();
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_placeholder_assets');
add_action('admin_footer-post-new.php', 'ai_optimizer_placeholder_assets');
?>