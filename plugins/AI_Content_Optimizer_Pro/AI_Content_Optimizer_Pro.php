/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Free version with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicop_pro_key';
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_nag'));
        add_action('wp_ajax_aico_optimize', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aicop-js', plugin_dir_url(__FILE__) . 'aicop.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aicop-js', 'aicop_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicop_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aicop-analysis', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aicop_meta_nonce', 'aicop_meta_nonce');
        $analysis = get_post_meta($post->ID, '_aicop_analysis', true);
        echo '<div id="aicop-results">';
        if ($analysis) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '%</p>';
            echo '<p><strong>SEO Score:</strong> ' . esc_html($analysis['seo']) . '%</p>';
            echo '<p><strong>Suggestions:</strong> ' . esc_html($analysis['suggestions']) . '</p>';
        }
        echo '<button id="aicop-analyze" class="button button-primary">Analyze Content (Free)</button>';
        echo '<p><em>Premium: AI Rewrite & Keyword Magic - <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Upgrade Now</a></em></p>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aicop_meta_nonce']) || !wp_verify_nonce($_POST['aicop_meta_nonce'], 'aicop_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize() {
        check_ajax_referer('aicop_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) wp_die();

        $content = get_post_field('post_content', $post_id);
        $word_count = str_word_count(strip_tags($content));
        $readability = min(100, 50 + ($word_count / 1000) * 10); // Simulated
        $seo = min(100, 60 + (substr_count($content, 'keyword') * 5)); // Simulated
        $suggestions = $word_count < 500 ? 'Add more content for better engagement.' : 'Great length! Consider headings.';

        $analysis = array(
            'readability' => round($readability),
            'seo' => round($seo),
            'suggestions' => $suggestions
        );
        update_post_meta($post_id, '_aicop_analysis', $analysis);

        wp_send_json_success($analysis);
    }

    public function premium_nag() {
        if (!current_user_can('manage_options') || get_option(self::PREMIUM_KEY)) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting & more! <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Upgrade for $9/mo</a></p></div>';
    }

    public function activate() {
        add_option('aicop_activated', time());
    }
}

new AIContentOptimizer();

// Dummy JS file content (base64 encoded for single file)
$aico_js = base64_decode('Ly8gQUlDT1AgSmF2YVNjcmlwdApqUXVlcnkuZG9jdW1lbnQucmVhZHkoZnVuY3Rpb24oKSB7CiAgLy9GcmVlIGFuYWx5c2lzCiAgjQnZWYoJyNhaWNvcC1hbmFseWplJykub24oJ2NsaWNrJywgZnVuY3Rpb24oKSB7CiAgICB2YXIgcG9zdElkID0gajNxZXJ5KCcjZm9ybS1hY3Rpb24nKS5kYXRhKCdwb3N0Jyk7CiAgICBqUXVlcnkuYWpheCh7CiAgICAgIHVybDogYWljb3BfYWpheC5hamF4dXJsLAogICAgICB0eXBlOiAncG9zdCcsCiAgICAgIGRhdGE6IHsKICAgICAgICBhY3Rpb246ICdhaWNvX29wdGltaXplJywKICAgICAgICBub25jZTogYWljb3BfYWpheC5ub25jZSwKICAgICAgICBwb3N0X2lkOiBwb3N0SWQKICAgICAgfSwKICAgICAgc3VjY2VzczogZnVuY3Rpb24oZGF0YSkgewogICAgICAgIGlmKGRhdGEuc3VjY2VzcykgewogICAgICAgICAgaSQoJyNhaWNvcC1yZXN1bHRzJykuaHRtbCgnCiAgICAgICAgICAgIDxwPjxzdHJvbmc+UmVhZGFiaWxpdHkgU2NvcmU6PC9zdHJvbmc+IicgKyBkYXRhLmRhdGEucmVhZGFiaWxpdHkgKyAnJTwvcD4KICAgICAgICAgICAgPHA+PHN0cm9uZz5TRU8gU2NvcmU6PC9zdHJvbmc+IicgKyBkYXRhLmRhdGEuc2VvICsgJyU8L3A+CICAgICAgICAgICAgPHA+PHN0cm9uZz5TdWdnZXN0aW9uczogPC9zdHJvbmc+IicgKyBkYXRhLmRhdGEuc3VnZ2VzdGlvbnMgKyAnPC9wPicpOwogICAgICAgIH0KICAgICAgfSwKICAgICAgZXJyb3I6IGZ1bmN0aW9uKCkgewogICAgICAgIGFsZXJ0KCdBbmVycm9yIGFubHl6aW5nIGNvbnRlbnQuJyk7CiAgICAgIH0KICAgIH0pOwogICAgamRxdWVyeSgnaGVhbGRlcicpLnNsaWQoJ3Nsb3cpOwogICAgcmV0dXJuIGZhbHNlOwogIH0pOwp9KTsK');
file_put_contents(plugin_dir_path(__FILE__) . 'aicop.js', $aico_js);