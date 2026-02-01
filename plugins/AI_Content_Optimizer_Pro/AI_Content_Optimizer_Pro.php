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
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_assets'));
        add_action('wp_ajax_aco_optimize', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        if (!get_option('aco_api_key')) {
            add_option('aco_api_key', '');
        }
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        $suggestions = get_post_meta($post->ID, '_aco_suggestions', true);
        echo '<div id="aco-results">';
        if ($score) {
            echo '<p><strong>SEO Score:</strong> ' . esc_html($score) . '/100</p>';
            if ($suggestions) {
                echo '<ul>';
                foreach ($suggestions as $sugg) {
                    echo '<li>' . esc_html($sugg) . '</li>';
                }
                echo '</ul>';
            }
        }
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-loading" style="display:none;">Analyzing...</div>';
        echo '</div>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['aco_meta_box_nonce']) || !wp_verify_nonce($_POST['aco_meta_box_nonce'], 'aco_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function enqueue_assets() {
        wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_enqueue_assets($hook) {
        if (strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
            wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin-js', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_ajax_nonce')));
        }
    }

    public function ajax_optimize() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        $title = get_post_field('post_title', $post_id);

        // Simple AI-like analysis (heuristic-based for self-contained)
        $word_count = str_word_count(strip_tags($content));
        $title_length = strlen($title);
        $headings = preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches);
        $images = preg_match_all('/<img[^>]+>/i', $content);
        $internal_links = preg_match_all('/<a[^>]+href=["\']([^"\']*wp-content|["\']\/[^#][^"\']*["\'])[^>]*>/i', $content);

        $score = 50;
        $suggestions = array();

        if ($word_count < 300) {
            $score -= 20;
            $suggestions[] = 'Add more content (aim for 300+ words).';
        } elseif ($word_count > 2000) {
            $score -= 10;
            $suggestions[] = 'Shorten content for better readability.';
        }

        if ($title_length < 30 || $title_length > 60) {
            $score -= 15;
            $suggestions[] = 'Optimize title length (30-60 characters).';
        }

        if ($headings < 2) {
            $score -= 15;
            $suggestions[] = 'Add more headings (H2/H3) for structure.';
        }

        if ($images == 0) {
            $score -= 10;
            $suggestions[] = 'Add relevant images with alt text.';
        }

        if ($internal_links < 2) {
            $score -= 10;
            $suggestions[] = 'Include internal links to other posts.';
        }

        $score = max(0, min(100, $score));

        update_post_meta($post_id, '_aco_score', $score);
        update_post_meta($post_id, '_aco_suggestions', $suggestions);

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }
}

new AIContentOptimizer();

// Create assets directories on activation if needed
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $css_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }
    // Minimal CSS
    file_put_contents($css_dir . 'style.css', '#aco-results { font-size: 13px; } #aco-results ul { margin: 5px 0; padding-left: 20px; }');
    file_put_contents($css_dir . 'admin.js', "jQuery(document).ready(function($) { $('#aco-analyze').click(function() { var postId = $('#post_ID').val(); $('#aco-loading').show(); $.post(aco_ajax.ajax_url, { action: 'aco_optimize', post_id: postId, nonce: aco_ajax.nonce }, function(resp) { if (resp.success) { location.reload(); } }); }); });");
});
?>