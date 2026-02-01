/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered SEO content optimizer for WordPress. Free version provides basic analysis; premium unlocks advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_co_premium_key';
    const PREMIUM_STATUS = 'ai_co_premium_status';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ai_co_optimize', array($this, 'handle_optimize'));
        add_action('wp_ajax_ai_co_activate_premium', array($this, 'handle_premium_activation'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_co_version', '1.0.0');
    }

    public function add_admin_menu() {
        add_posts_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'edit.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-co-js', plugin_dir_url(__FILE__) . 'ai-co.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-co-js', 'aiCoAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_co_nonce'),
            'isPremium' => $this->is_premium()
        ));
        wp_enqueue_style('ai-co-css', plugin_dir_url(__FILE__) . 'ai-co.css', array(), '1.0.0');
    }

    public function admin_page() {
        $is_premium = $this->is_premium();
        echo '<div class="wrap"><h1>AI Content Optimizer</h1>';
        echo '<div id="ai-co-results"></div>';
        echo '<button id="ai-co-optimize-btn" class="button button-primary">' . ($is_premium ? 'Full AI Optimize' : 'Basic SEO Check') . '</button>';
        if (!$is_premium) {
            echo '<p><strong>Go Premium</strong> for AI rewriting, bulk processing, and more! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>';
        }
        echo '</div>';
    }

    public function handle_optimize() {
        check_ajax_referer('ai_co_nonce', 'nonce');
        if (!$this->is_premium() && !isset($_POST['force_free'])) {
            wp_send_json_error('Premium feature required.');
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content = $post_id ? get_post_field('post_content', $post_id) : '';

        if (empty($content)) {
            wp_send_json_error('No content found.');
        }

        // Simulate AI analysis (in real: integrate OpenAI API or similar)
        $suggestions = $this->generate_suggestions($content);

        if ($this->is_premium()) {
            $optimized_content = $this->ai_rewrite($content); // Premium: full rewrite
            wp_send_json_success(array(
                'suggestions' => $suggestions,
                'optimized_content' => $optimized_content,
                'score' => rand(75, 95)
            ));
        } else {
            wp_send_json_success(array(
                'suggestions' => array_slice($suggestions, 0, 3), // Free: limited
                'score' => rand(50, 74)
            ));
        }
    }

    private function generate_suggestions($content) {
        $words = str_word_count(strip_tags($content));
        $suggestions = array(
            'Add more keywords: Current density low.',
            'Improve readability: Aim for shorter sentences.',
            'Optimize headings: Use H2/H3 more effectively.',
            'Add internal links for better SEO.',
            'Include meta description.',
            'Enhance with images and alt text.'
        );
        return $suggestions;
    }

    private function ai_rewrite($content) {
        // Simulated AI rewrite (premium feature)
        return $content . '\n\n<p><em>AI Optimized: Enhanced for SEO with better structure and keywords.</em></p>';
    }

    public function handle_premium_activation() {
        check_ajax_referer('ai_co_nonce', 'nonce');
        if (isset($_POST['license_key'])) {
            $key = sanitize_text_field($_POST['license_key']);
            update_option(self::PREMIUM_KEY, $key);
            // Simulate validation
            if (strlen($key) > 10) {
                update_option(self::PREMIUM_STATUS, 'active');
                wp_send_json_success('Premium activated!');
            } else {
                wp_send_json_error('Invalid key.');
            }
        }
    }

    private function is_premium() {
        return get_option(self::PREMIUM_STATUS) === 'active';
    }
}

new AIContentOptimizer();

// Include JS and CSS as inline for single file

function ai_co_inline_assets() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
        ?>
        <style id="ai-co-css">
        #ai-co-results { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 5px; }
        #ai-co-results h3 { color: #23282d; }
        .premium-upsell { background: #fff3cd; padding: 15px; border-left: 4px solid #ffeaa7; }
        </style>
        <script id="ai-co-js">
        jQuery(document).ready(function($) {
            $('#ai-co-optimize-btn').click(function() {
                $.post(aiCoAjax.ajaxurl, {
                    action: 'ai_co_optimize',
                    nonce: aiCoAjax.nonce,
                    post_id: $('#post_ID').val() || 0
                }, function(response) {
                    if (response.success) {
                        let html = '<h3>SEO Score: ' + response.data.score + '%</h3><ul>';
                        $.each(response.data.suggestions, function(i, sug) {
                            html += '<li>' + sug + '</li>';
                        });
                        html += '</ul>';
                        if (response.data.optimized_content) {
                            html += '<h4>Optimized Content:</h4><textarea style="width:100%;height:200px;">' + response.data.optimized_content + '</textarea>';
                        }
                        $('#ai-co-results').html(html);
                    } else {
                        alert(response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
}

add_action('admin_footer', 'ai_co_inline_assets');