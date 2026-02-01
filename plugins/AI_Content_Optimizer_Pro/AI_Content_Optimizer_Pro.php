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
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_notices', array($this, 'premium_nag'));
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
            'is_premium' => false
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        echo '<div id="ai-optimizer-panel">';
        echo '<button id="analyze-content" class="button button-primary">Analyze Content</button>';
        echo '<div id="results"></div>';
        echo '<p><em>Free: Basic readability score. <a href="' . self::PREMIUM_URL . '" target="_blank">Go Premium</a> for AI rewrites & SEO keywords!</em></p>';
        echo '</div>';
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $sentence_count > 0 ? round(180 - (word_count * 1.8 + sentence_count * 5.0) / $sentence_count, 2) : 0; // Mock Flesch score

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'score_label' => $readability > 60 ? 'Good' : ($readability > 30 ? 'Fair' : 'Poor'),
            'premium_tease' => 'Upgrade for AI-powered keyword suggestions and auto-rewrites!'
        );

        wp_send_json_success($results);
    }

    public function premium_nag() {
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        if ($screen->id === 'plugin-install') {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong>: AI rewrites, SEO keywords & more! <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade Now</a></p></div>';
        }
    }

    public function activate() {
        set_transient('ai_optimizer_activated', true, 3600);
    }
}

new AIContentOptimizer();

// Inline JS and CSS for single-file
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
function ai_optimizer_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#analyze-content').click(function() {
            var content = $('#content').val() || $('#title').val();
            if (!content) return alert('Add some content first!');

            $('#results').html('<p>Analyzing...</p>');
            $.post(ai_optimizer.ajax_url, {
                action: 'optimize_content',
                content: content,
                nonce: ai_optimizer.nonce
            }, function(res) {
                if (res.success) {
                    var html = '<strong>Words:</strong> ' + res.data.word_count + '<br>' +
                               '<strong>Readability:</strong> ' + res.data.readability + ' (' + res.data.score_label + ')<br>' +
                               '<p>' + res.data.premium_tease + ' <a href="<?php echo AIContentOptimizer::PREMIUM_URL; ?>" target="_blank">Upgrade</a></p>';
                    $('#results').html(html);
                }
            });
        });
    });
    </script>
    <style>
    #ai-optimizer-panel { padding: 10px; }
    #results { margin-top: 10px; font-size: 12px; }
    </style>
    <?php
}

?>