/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicop_pro_key';
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aicop_analyze', array($this, 'ajax_analyze'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aicop-js', plugin_dir_url(__FILE__) . 'aicop.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aicop-js', 'aicop_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicop_nonce')));
        wp_enqueue_style('aicop-css', plugin_dir_url(__FILE__) . 'aicop.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('aicop-meta', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aicop_meta_nonce', 'aicop_meta_nonce');
        echo '<div id="aicop-container">';
        echo '<button id="aicop-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aicop-results"></div>';
        echo '<p><em>Free: Basic score. <a href="' . self::PREMIUM_URL . '" target="_blank">Go Pro</a> for AI rewrite & keywords.</em></p>';
        echo '</div>';
    }

    public function ajax_analyze() {
        check_ajax_referer('aicop_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $sentence_count > 0 ? round(180 - ($word_count / $sentence_count * 4.71), 1) : 0; // Approx Flesch
        $score = min(100, max(0, ($readability / 1.8) + ($word_count / 500 * 20))); // Simple score

        $results = array(
            'score' => $score,
            'word_count' => $word_count,
            'readability' => $readability,
            'is_premium' => $this->is_premium(),
            'message' => $this->is_premium() ? 'Premium: AI optimized!' : 'Free basic analysis. Upgrade for full AI features.'
        );

        wp_send_json_success($results);
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function premium_notice() {
        if ($this->is_premium()) return;
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock AI rewriting in <strong>AI Content Optimizer Pro</strong>: <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade Now</a></p></div>';
    }

    public function activate() {
        add_option('aicop_version', '1.0.0');
    }
}

new AIContentOptimizer();

// Dummy JS/CSS files would be created separately; inline for single-file
/*
Add to plugin dir: aicop.js with:
$(document).ready(function() {
    $('#aicop-analyze').click(function() {
        $.post(aicop_ajax.ajax_url, {
            action: 'aicop_analyze',
            post_id: $('#post_ID').val(),
            nonce: aicop_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('#aicop-results').html('<p>Score: ' + res.data.score + '% | Words: ' + res.data.word_count + ' | Premium: ' + res.data.message + '</p>');
            }
        });
    });
});

Add aicop.css with:
#aicop-container { padding: 10px; }
*/

// Note: For production, enqueue external JS/CSS files. This is self-contained demo.?>
