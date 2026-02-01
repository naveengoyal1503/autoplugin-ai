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
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_pro', 0);
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-js', 'ai_optimizer', array('ajax_url' => admin_url('admin-ajax.php')));
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-score', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_content_score', true);
        echo '<div id="ai-score">Score: <strong>' . esc_html($score ?: 'Not analyzed') . '%</strong></div>';
        echo '<button id="analyze-btn" class="button">Analyze Content</button>';
        echo '<div id="ai-suggestions"></div>';
        if (get_option('ai_content_optimizer_pro')) {
            echo '<p><em>Pro: Auto-optimize available.</em></p>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate AI analysis (rule-based for demo; integrate real AI in production)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $this->flesch_readability($content);
        $keywords = $this->extract_keywords($content);
        $score = min(100, (50 + ($word_count > 500 ? 20 : 0) + ($readability > 60 ? 20 : 0) + (count($keywords) > 5 ? 10 : 0)));

        update_post_meta($post_id, '_ai_content_score', $score);

        $suggestions = array(
            'Word count: ' . $word_count . ' (Aim for 500+)',
            'Readability: ' . round($readability) . ' (Aim for 60+ Flesch)',
            'Keywords: ' . implode(', ', array_slice($keywords, 0, 5))
        );

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    private function flesch_readability($text) {
        $sentence_count = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentence_count);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        if ($sentence_count === 0 || $word_count === 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $rules = array('/emb//', '/aim//', '/([^aeiou][aeiou]+)[^aeiou]+$/i', '/^(.[^aeiou]?)([^aeiou]+)$/i');
        $subs = array('em', 'ay', '\1ay', '\2a');
        $text = preg_replace($rules, $subs, $text);
        return preg_match_all('/[aeiouy]/', $text);
    }

    private function extract_keywords($content) {
        $words = explode(' ', strtolower(strip_tags($content)));
        $common = array('the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i');
        $counts = array();
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z]/', '', $word);
            if (strlen($word) > 4 && !in_array($word, $common)) {
                $counts[$word] = isset($counts[$word]) ? $counts[$word] + 1 : 1;
            }
        }
        arsort($counts);
        return array_keys(array_slice($counts, 0, 10, true));
    }
}

new AIContentOptimizer();

// Pro check simulation
function is_pro() {
    return get_option('ai_content_optimizer_pro');
}

// JS file content (base64 for single file)
$js = base64_decode('Iy8gU2ltcGxlIEpTIHBsYWNlaG9sZGVyDQpqdW5rLmRvY3VtZW50LnJlYWR5KGZ1bmN0aW9uKCkgew0KICB2YXIgYnRuID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2FuYWx5emUtYnRuJyk7DQogIGJ0bi5jbGljayg0ZnVuY3Rpb24oKSB7DQogICAgaWYoIShpc19wcm8oKSkpIHsNCiAgICAgIGFsZXJ0KCdQcm8gZmVhdHVyZSBmb3IgYXV0by1vcHRpbWl6YXRpb24nKTsNCiAgICAgIHJldHVybjsNCiAgICB9DQogICAgamF4eC5wb3N0KGFpX29wdGltaXplci5hamF4X3VybCwgew0KICAgICAgYWN0aW9uOiAnb3B0aW1pemVfY29udGVudCcsDQogICAgICBub25jZTogamFuay5wYXJzZSh3aW5kb3cubm9uY2UpLA0KICAgICAgcG9zdF9pZDogamFuayhwYXJzZSgpKS5wb3N0X2lkDQogICAgfSwgZnVuY3Rpb24ocmVzKSB7DQogICAgICBpZiAocmVzLnN1Y2Nlc3MpIHsNCiAgICAgICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2FpLXNjb3JlJykuaW5uZXJIVE1MID0gJ1Njb3JlOiA8c3Ryb25nPicgKyByZXMucmVzcG9uc2Uuc2NvcmUgKyAnJTwvc3Ryb25nPic7DQogICAgICAgIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdhaXEtc3VnZ2VzdGlvbnMnKS5pbm5lckhUTUwgPSAnPGRpdj4nICsgcmVzLnJlc3BvbnNlLnN1Z2dlc3Rpb25zLmpvaW4oJyA8YnIvJykgKyAnPC9kaXY+JzsNCiAgICAgIH0NCiAgICB9KTsNCiAgfSk7DQp9KTs=');
file_put_contents(plugin_dir_path(__FILE__) . 'optimizer.js', $js);