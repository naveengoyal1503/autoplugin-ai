/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for SEO and engagement.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium_active()
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>' . __('Analyze & Optimize Content', 'ai-content-optimizer') . '</strong></p>';
        echo '<button id="ai-analyze-btn" class="button button-primary">' . __('Analyze Now (Free)', 'ai-content-optimizer') . '</button>';
        echo '<div id="ai-results"></div>';
        if (!$this->is_premium_active()) {
            echo '<p class="premium-notice"><a href="https://example.com/premium" target="_blank">' . __('Upgrade to Pro for AI Rewriting & Bulk Tools', 'ai-content-optimizer') . '</a></p>';
        }
        echo '</div>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate basic free analysis (word count, readability score)
        $word_count = str_word_count(strip_tags($content));
        $readability_score = min(100, 50 + ($word_count / 100)); // Mock formula
        $seo_score = rand(60, 90); // Mock SEO score

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability_score, 1),
            'seo_score' => $seo_score,
            'suggestions' => array(
                'Add more headings',
                'Include keywords naturally',
                'Shorten sentences'
            )
        );

        if ($this->is_premium_active()) {
            // Premium: Mock AI rewrite
            $results['rewrite'] = $this->mock_ai_rewrite($content);
        }

        wp_send_json_success($results);
    }

    private function mock_ai_rewrite($content) {
        // Mock premium rewrite
        return substr($content, 0, 200) . '... (Premium AI Rewrite)';
    }

    private function is_premium_active() {
        // Check for premium license key in options
        $license = get_option('ai_optimizer_pro_license');
        return !empty($license) && $this->validate_license($license);
    }

    private function validate_license($license) {
        // Mock license validation
        return hash('sha256', $license) === 'mock_premium_hash';
    }

    public function add_action_links($links) {
        $settings_link = '<a href="options-general.php?page=ai-optimizer">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function activate() {
        add_option('ai_optimizer_version', '1.0.0');
    }
}

AIContentOptimizer::get_instance();

// Settings page
add_action('admin_menu', function() {
    add_options_page(
        'AI Content Optimizer Settings',
        'AI Optimizer',
        'manage_options',
        'ai-optimizer',
        function() {
            echo '<div class="wrap"><h1>AI Content Optimizer Pro Settings</h1>';
            echo '<form method="post" action="options.php">';
            settings_fields('ai_optimizer_settings');
            do_settings_sections('ai_optimizer_settings');
            submit_button();
            echo '</form>';
            echo '<p><strong>Premium License:</strong> Enter key for Pro features or <a href="https://example.com/premium" target="_blank">buy now ($9.99/mo)</a></p>';
            echo '</div>';
        }
    );
});

// Mock JS and CSS files would be separate, but for single-file, inline them
/* Inline JS */
function ai_optimizer_inline_js() {
    if (wp_script_is('ai-optimizer-js', 'enqueued')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-analyze-btn').click(function(e) {
                e.preventDefault();
                var postId = $('#post_ID').val();
                $.post(ai_optimizer.ajax_url, {
                    action: 'optimize_content',
                    post_id: postId,
                    nonce: ai_optimizer.nonce
                }, function(response) {
                    if (response.success) {
                        var res = response.data;
                        var html = '<ul>';
                        html += '<li>Words: ' + res.word_count + '</li>';
                        html += '<li>Readability: ' + res.readability + '%</li>';
                        html += '<li>SEO Score: ' + res.seo_score + '%</li>';
                        html += '<li>Suggestions: ' + res.suggestions.join(', ') + '</li>';
                        if (res.rewrite) {
                            html += '<li>AI Rewrite: ' + res.rewrite + '</li>';
                        }
                        html += '</ul>';
                        $('#ai-results').html(html);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'ai_optimizer_inline_js');

/* Inline CSS */
function ai_optimizer_inline_css() {
    echo '<style>#ai-optimizer-panel { padding: 10px; } #ai-results { margin-top: 10px; font-size: 12px; } .premium-notice { color: #0073aa; font-weight: bold; }</style>';
}
add_action('admin_head', 'ai_optimizer_inline_css');