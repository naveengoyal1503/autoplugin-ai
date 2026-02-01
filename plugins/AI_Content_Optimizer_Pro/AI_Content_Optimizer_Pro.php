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
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aico_analyze', array($this, 'ajax_analyze'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
        if (is_admin()) {
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'action_links'));
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aico-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), self::VERSION, true);
        wp_localize_script('aico-admin', 'aico_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aico_nonce')));
        wp_enqueue_style('aico-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), self::VERSION);
    }

    public function add_meta_box() {
        add_meta_box('aico-analysis', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
        add_meta_box('aico-analysis', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aico_meta_nonce', 'aico_meta_nonce');
        $score = get_post_meta($post->ID, '_aico_score', true);
        $premium = $this->is_premium();
        echo '<div id="aico-container">';
        echo '<p><strong>SEO Score:</strong> <span id="aico-score">' . esc_html($score ?: 'Not analyzed') . '</span></p>';
        echo '<button id="aico-analyze" class="button button-primary">Analyze Content</button>';
        if (!$premium) {
            echo '<p><a href="https://example.com/premium" target="_blank" class="button">Go Premium for AI Rewrite</a></p>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aico_meta_nonce']) || !wp_verify_nonce($_POST['aico_meta_nonce'], 'aico_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_analyze() {
        check_ajax_referer('aico_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Basic free analysis
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count ? $word_count / $sentence_count : 0;
        $score = min(100, max(0, 50 + ($word_count > 300 ? 20 : 0) + ($readability > 15 ? 20 : 0) + (substr_count($content, 'https?://') > 0 ? 10 : 0)));

        update_post_meta($post_id, '_aico_score', $score);

        if ($this->is_premium()) {
            // Simulate premium AI (replace with real API like OpenAI)
            $suggestions = $this->mock_ai_suggestions($content);
            wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
        } else {
            wp_send_json_success(array('score' => $score, 'message' => 'Upgrade for AI suggestions!'));
        }
    }

    private function mock_ai_suggestions($content) {
        return array(
            'Add more keywords',
            'Improve readability',
            'Include internal links'
        );
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function premium_notice() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock AI rewriting in <strong>AI Content Optimizer Pro</strong> - <a href="https://example.com/premium">Upgrade now</a> for $9/mo!</p></div>';
        }
    }

    public function action_links($links) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        return $links;
    }

    public function activate() {
        update_option(self::PREMIUM_KEY, 'trial');
    }
}

new AIContentOptimizer();

// admin.js content (base64 or inline, but for single file, echo in enqueue)
function aico_admin_js() {
    ?><script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#aico-analyze').click(function() {
            var postId = $('#post_ID').val();
            $.post(aico_ajax.ajaxurl, {
                action: 'aico_analyze',
                nonce: aico_ajax.nonce,
                post_id: postId
            }, function(response) {
                if (response.success) {
                    $('#aico-score').text(response.data.score);
                    if (response.data.suggestions) {
                        alert(response.data.suggestions.join('\n'));
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        });
    });
    </script><?php
}
add_action('admin_footer-post.php', 'aico_admin_js');
add_action('admin_footer-post-new.php', 'aico_admin_js');

// admin.css
function aico_admin_css() {
    ?><style>
    #aico-container { padding: 10px; }
    #aico-score { font-size: 24px; color: #0073aa; font-weight: bold; }
    </style><?php
}
add_action('admin_head-post.php', 'aico_admin_css');
add_action('admin_head-post-new.php', 'aico_admin_css');
