/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Perplexity AI
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_ajax_ai_rewrite', array($this, 'ajax_ai_rewrite'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('save_post', array($this, 'save_meta'));
        }
    }

    public function enqueue_assets() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce')
        ));
    }

    public function admin_menu() {
        add_management_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'admin_page'));
    }

    public function admin_page() {
        $is_premium = get_option('ai_optimizer_premium', false);
        echo '<div class="wrap"><h1>AI Content Optimizer Pro</h1>';
        if (!$is_premium) {
            echo '<p><strong>Upgrade to Premium for AI rewriting and bulk tools!</strong> <a href="https://example.com/premium" target="_blank">Get Premium ($9/mo)</a></p>';
        }
        echo '</div>';
    }

    public function add_meta_box() {
        add_meta_box('ai-optimizer-meta', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-optimizer-meta', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_meta_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score : 'Not analyzed') . '%</p>';
        if ($suggestions) {
            echo '<ul>';
            foreach (unserialize($suggestions) as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul>';
        }
        echo '<button id="analyze-content" class="button">Analyze Now</button>';
        echo '<div id="premium-upsell" style="display:none;"><p>Unlock AI Rewrite with Premium!</p></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_meta_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_meta_nonce'], 'ai_optimizer_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count > 500 ? 20 : 0) + (strpos($content, 'keyword') !== false ? 30 : 0));
        $suggestions = array(
            $word_count < 300 ? 'Increase word count to 500+ for better SEO.' : 'Good length.',
            strpos($content, '<h2') === false ? 'Add H2 headings for structure.' : 'Good structure.',
            'Include target keywords naturally.'
        );

        update_post_meta($post_id, '_ai_optimizer_score', $score);
        update_post_meta($post_id, '_ai_optimizer_suggestions', serialize($suggestions));

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    public function ajax_ai_rewrite() {
        // Premium feature simulation (in real: API call to AI service)
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!get_option('ai_optimizer_premium', false)) {
            wp_send_json_error('Premium required for AI Rewrite.');
        }
        $content = sanitize_textarea_field($_POST['content']);
        $rewritten = $content . ' [AI Optimized Version - Premium Feature]'; // Placeholder
        wp_send_json_success(array('rewritten' => $rewritten));
    }

    public function activate() {
        add_option('ai_optimizer_premium', false);
    }
}

AIContentOptimizerPro::get_instance();

// Inline JS for demo (self-contained)
function ai_optimizer_inline_js() {
    if (is_admin()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#analyze-content').click(function() {
                var post_id = $(this).closest('.postbox').find('input[name="post_ID"]').val() || $('#post_ID').val();
                $.post(ai_optimizer_ajax.ajaxurl, {
                    action: 'optimize_content',
                    nonce: ai_optimizer_ajax.nonce,
                    post_id: post_id
                }, function(resp) {
                    if (resp.success) {
                        alert('Score: ' + resp.data.score + '%\nSuggestions: ' + resp.data.suggestions.join('\n'));
                        $('#premium-upsell').show();
                    }
                });
            });
        });
        <?php
    }
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
?>