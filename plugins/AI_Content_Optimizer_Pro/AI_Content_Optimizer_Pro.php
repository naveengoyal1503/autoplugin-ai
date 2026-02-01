/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability.
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium()
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce_field');
        echo '<div id="ai-optimizer-panel">';
        echo '<button id="analyze-content" class="button">Analyze Content</button>';
        echo '<div id="analysis-results"></div>';
        echo '<p><small><strong>Premium:</strong> AI Rewrites, Bulk Optimize, SEO Scores. <a href="#" id="go-premium">Upgrade Now</a></small></p>';
        echo '</div>';
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_premium() && !isset($_POST['premium_feature'])) {
            wp_send_json_error('Premium feature required.');
            return;
        }
        $content = sanitize_textarea_field($_POST['content']);
        $analysis = $this->analyze_content($content);
        wp_send_json_success($analysis);
    }

    private function analyze_content($content) {
        // Simulated AI analysis (in real: integrate OpenAI API for premium)
        $word_count = str_word_count($content);
        $readability = $word_count > 300 ? 'Good' : 'Improve length';
        $seo_score = min(100, 50 + ($word_count / 10));
        $suggestions = array(
            'Word count: ' . $word_count,
            'Readability: ' . $readability,
            'SEO Score: ' . $seo_score . '%',
            'Free tip: Add more headings.'
        );
        if ($this->is_premium()) {
            $suggestions[] = 'Premium: Optimized rewrite ready.';
        }
        return $suggestions;
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function premium_notice() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for advanced features! <a href="https://example.com/premium">Get Premium</a></p></div>';
        }
    }

    public function activate() {
        add_option('ai_content_optimizer_version', '1.0.0');
    }
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'post') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#analyze-content').click(function() {
                var content = $('#content').val();
                $.post(ai_optimizer.ajax_url, {
                    action: 'optimize_content',
                    nonce: ai_optimizer.nonce,
                    content: content
                }, function(response) {
                    if (response.success) {
                        $('#analysis-results').html('<ul>' + response.data.map(function(s) { return '<li>' + s + '</li>'; }).join('') + '</ul>');
                    } else {
                        alert(response.data);
                    }
                });
            });
            $('#go-premium').click(function(e) {
                e.preventDefault();
                alert('Redirect to premium purchase (integrate with Freemius or Stripe).');
            });
        });
        <?php
    }
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');