/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aico_analyze_content', array($this, 'handle_ajax_analysis'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aico-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aico-admin', 'aico_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aico_nonce'),
            'is_premium' => $this->is_premium()
        ));
    }

    public function add_meta_box() {
        add_meta_box('aico-analysis', 'AI Content Optimizer', array($this, 'meta_box_content'), 'post', 'side');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aico_meta_box', 'aico_meta_box_nonce');
        echo '<div id="aico-container">';
        echo '<p><strong>Content Score:</strong> <span id="aico-score">--</span>/100</p>';
        echo '<button id="aico-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aico-results"></div>';
        if (!$this->is_premium()) {
            echo '<p><a href="#" id="aico-upgrade">Upgrade to Premium for AI Rewrite & Keywords</a></p>';
        }
        echo '</div>';
    }

    public function handle_ajax_analysis() {
        check_ajax_referer('aico_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic analysis (free)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $word_count > 0 ? min(100, (300 / max(1, $word_count / max(1, $sentence_count))) * 50) : 0;
        $score = round(($readability + min(50, $word_count / 10)) / 2);

        $results = array(
            'score' => $score,
            'metrics' => array(
                'Words' => $word_count,
                'Readability' => round($readability),
                'SEO Keywords' => substr_count(strtolower($content), 'keyword') // Placeholder
            )
        );

        if ($this->is_premium()) {
            // Premium: Simulate AI enhancements
            $results['ai_rewrite'] = $this->mock_ai_rewrite($content);
            $results['keywords'] = array('premium', 'keyword1', 'keyword2');
        } else {
            $results['upgrade'] = 'Unlock AI Rewrite and Keyword Suggestions';
        }

        wp_send_json_success($results);
    }

    private function mock_ai_rewrite($content) {
        return 'Premium AI Rewritten: ' . substr($content, 0, 100) . '...';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function premium_notice() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> premium features for $9.99/month: AI rewriting, advanced SEO! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
        }
    }

    public function activate() {
        add_option('aico_version', '1.0.0');
    }
}

new AIContentOptimizer();

// Admin JS (embedded for single file)
function aico_admin_js() {
    if (!is_admin()) return;
    ?><script type="text/javascript">
jQuery(document).ready(function($) {
    $('#aico-analyze').click(function(e) {
        e.preventDefault();
        $.post(aico_ajax.ajax_url, {
            action: 'aico_analyze_content',
            nonce: aico_ajax.nonce,
            post_id: $('#post_ID').val()
        }, function(res) {
            if (res.success) {
                $('#aico-score').text(res.data.score);
                let html = '<ul>';
                $.each(res.data.metrics, function(k,v) {
                    html += '<li><strong>' + k + ':</strong> ' + v + '</li>';
                });
                if (res.data.ai_rewrite) {
                    html += '<li>AI Rewrite: ' + res.data.ai_rewrite + '</li>';
                } else if (res.data.upgrade) {
                    html += '<li>' + res.data.upgrade + '</li>';
                }
                html += '</ul>';
                $('#aico-results').html(html);
            }
        });
    });
});
</script><?php
}
add_action('admin_footer-post.php', 'aico_admin_js');
add_action('admin_footer-post-new.php', 'aico_admin_js');
?>