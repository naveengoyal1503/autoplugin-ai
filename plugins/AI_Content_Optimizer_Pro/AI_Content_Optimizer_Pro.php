/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content optimization for SEO. Free version analyzes readability and keywords; Pro unlocks bulk edits and advanced features.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'handle_ajax_analyze'));
        add_action('wp_ajax_upgrade_to_pro', array($this, 'handle_upgrade_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_optimizer_license_key', '');
        add_option('ai_optimizer_pro_active', false);
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
        if ('post.php' != $hook && 'post-new.php' != $hook && 'edit.php' != $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_pro' => get_option('ai_optimizer_pro_active')
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function admin_page() {
        if (isset($_GET['page']) && $_GET['page'] == 'ai-content-optimizer') {
            echo '<div class="wrap">';
            echo '<h1>AI Content Optimizer</h1>';
            echo '<p>Analyze your post content for SEO. <strong>Pro:</strong> Unlimited + Bulk.</p>';
            $this->show_analysis_form();
            $this->show_pro_notice();
            echo '</div>';
        }
    }

    private function show_analysis_form() {
        echo '<div id="ai-optimizer-form">';
        echo '<textarea id="content-input" placeholder="Paste your content here..."></textarea>';
        echo '<button id="analyze-btn" class="button button-primary">Analyze (Free: 500 chars)</button>';
        echo '<div id="results"></div>';
        echo '</div>';
    }

    private function show_pro_notice() {
        if (!get_option('ai_optimizer_pro_active')) {
            echo '<div class="notice notice-info">';
            echo '<p><strong>Go Pro!</strong> Unlock bulk optimization, advanced AI SEO suggestions, and more for $9/month. <a href="#" id="upgrade-pro">Upgrade Now</a></p>';
            echo '</div>';
        }
    }

    public function handle_ajax_analyze() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);
        $length = strlen($content);

        if ($length > 500 && !get_option('ai_optimizer_pro_active')) {
            wp_send_json_error('Free version limited to 500 characters. Upgrade to Pro!');
            return;
        }

        // Simulate AI analysis (in real: integrate OpenAI API)
        $score = min(95, 50 + (rand(0, 45))); // Mock score
        $suggestions = $this->generate_suggestions($content);

        $results = array(
            'readability_score' => $score,
            'keyword_density' => rand(1, 5) . '%',
            'suggestions' => $suggestions,
            'is_pro_needed' => $length > 500
        );

        wp_send_json_success($results);
    }

    private function generate_suggestions($content) {
        $words = str_word_count($content);
        $suggestions = array();
        if ($words < 300) {
            $suggestions[] = 'Add more content: Aim for 300+ words.';
        }
        $suggestions[] = 'Include primary keyword in first paragraph.';
        $suggestions[] = 'Use short sentences for better readability.';
        if (!get_option('ai_optimizer_pro_active')) {
            $suggestions[] = 'Pro: Get AI-generated optimized title & meta.';
        }
        return $suggestions;
    }

    public function handle_upgrade_notice() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        // In real: integrate Freemius or payment gateway
        echo '<p>Redirecting to Pro checkout... (Demo: Set <code>update_option("ai_optimizer_pro_active", true);</code> in DB)</p>';
        wp_die();
    }
}

AIContentOptimizer::get_instance();

// Inline CSS
add_action('admin_head', function() {
    echo '<style>
    #ai-optimizer-form { max-width: 800px; }
    #content-input { width: 100%; height: 200px; margin-bottom: 10px; }
    #results { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
    .pro-feature { color: #0073aa; font-weight: bold; }
    </style>';
});

// Mock JS (self-contained, no external file)
add_action('admin_footer', function() {
    ?><script>
    jQuery(document).ready(function($) {
        $('#analyze-btn').click(function() {
            var content = $('#content-input').val();
            if (!content) return;

            $.post(ai_optimizer_ajax.ajax_url, {
                action: 'analyze_content',
                nonce: ai_optimizer_ajax.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<h3>Analysis Results</h3><p><strong>Readability Score:</strong> ' + res.readability_score + '/100</p>';
                    html += '<p><strong>Keyword Density:</strong> ' + res.keyword_density + '</p>';
                    html += '<ul>';
                    $.each(res.suggestions, function(i, sug) {
                        html += '<li>' + sug + '</li>';
                    });
                    html += '</ul>';
                    if (res.is_pro_needed) {
                        html += '<p class="pro-feature">Upgrade to Pro for full access!</p>';
                    }
                    $('#results').html(html);
                } else {
                    $('#results').html('<p class="error">' + response.data + '</p>');
                }
            });
        });

        $('#upgrade-pro').click(function(e) {
            e.preventDefault();
            $.post(ai_optimizer_ajax.ajax_url, {
                action: 'upgrade_to_pro',
                nonce: ai_optimizer_ajax.nonce
            });
        });
    });
    </script><?php
});