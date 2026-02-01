/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyze and optimize your content for better readability, SEO, and engagement. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    const PREMIUM_LICENSE_KEY = 'premium_license_ai_co';
    const PREMIUM_FEATURES_URL = 'https://example.com/premium-upgrade?ref=plugin';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-co-admin', plugin_dir_url(__FILE__) . 'ai-co-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-co-admin', 'aiCoAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_co_nonce'),
            'isPremium' => $this->is_premium()
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side', 'high');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_co_meta_box', 'ai_co_nonce');
        $content = get_post_field('post_content', $post->ID);
        $analysis = get_post_meta($post->ID, '_ai_co_analysis', true);
        $is_premium = $this->is_premium();
        echo '<div id="ai-co-analysis">';
        if ($analysis) {
            echo '<p><strong>Previous Analysis:</strong><br>' . esc_html($analysis['summary']) . '</p>';
        }
        echo '<p><textarea id="ai-co-content" style="display:none;">' . esc_textarea($content) . '</textarea></p>';
        echo '<button id="ai-co-analyze" class="button button-primary" ' . (!$is_premium ? 'disabled' : '') . '>Analyze Content</button>';
        if (!$is_premium) {
            echo '<p><small>Premium feature. <a href="' . esc_url(self::PREMIUM_FEATURES_URL) . '" target="_blank">Upgrade to Pro</a></small></p>';
        }
        echo '</div>';
    }

    public function ajax_analyze_content() {
        check_ajax_referer('ai_co_nonce', 'nonce');
        if (!$this->is_premium()) {
            wp_send_json_error('Premium feature required.');
        }
        $content = sanitize_textarea_field($_POST['content']);
        $analysis = $this->perform_analysis($content);
        global $post;
        update_post_meta($post->ID, '_ai_co_analysis', $analysis);
        wp_send_json_success($analysis);
    }

    private function perform_analysis($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence_length = $sentence_count > 0 ? round($word_count / $sentence_count, 1) : 0;
        $readability = $avg_sentence_length > 25 ? 'Needs improvement' : 'Good';
        $headings = preg_match_all('/<h[1-6]/', $content);
        $seo_score = min(100, (50 + ($headings * 10) + min($word_count / 10, 50)));
        return array(
            'word_count' => $word_count,
            'readability' => $readability,
            'avg_sentence' => $avg_sentence_length,
            'seo_score' => round($seo_score),
            'summary' => "Words: $word_count | Readability: $readability | SEO: " . round($seo_score) . '%"
        );
    }

    private function is_premium() {
        return get_option(self::PREMIUM_LICENSE_KEY) === 'valid-license-key';
    }

    public function plugin_row_meta($links, $file) {
        if ($file === plugin_basename(__FILE__)) {
            $links[] = '<a href="' . esc_url(self::PREMIUM_FEATURES_URL) . '" target="_blank">Premium</a>';
        }
        return $links;
    }

    public function activate() {
        add_option(self::PREMIUM_LICENSE_KEY, '');
    }
}

new AIContentOptimizer();

// Dummy JS file content would be in ai-co-admin.js
// jQuery(document).ready(function($) {
//   $('#ai-co-analyze').click(function() {
//     var content = $('#ai-co-content').val();
//     $.post(aiCoAjax.ajaxurl, {
//       action: 'analyze_content',
//       nonce: aiCoAjax.nonce,
//       content: content
//     }, function(resp) {
//       if (resp.success) {
//         $('#ai-co-analysis').html('<p><strong>Analysis:</strong><br>' + resp.data.summary + '</p>');
//       } else {
//         alert(resp.data);
//       }
//     });
//   });
// });
// Note: In real plugin, include actual JS file. This is single-file sim.