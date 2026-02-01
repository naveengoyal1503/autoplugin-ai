/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO and engagement using AI analysis. Freemium model with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizerPro {
    private $is_premium = false;

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        // Simulate license check for premium
        $this->is_premium = get_option('ai_cop_license_key') !== false;

        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_notices', array($this, 'premium_notice'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function activate() {
        add_option('ai_cop_activated', true);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_cop_nonce', 'ai_cop_nonce');
        $score = get_post_meta($post->ID, '_ai_cop_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_cop_suggestions', true);
        echo '<p><strong>SEO Score:</strong> ' . ($score ?: 'Not analyzed') . '/100</p>';
        if ($suggestions) {
            echo '<ul style="font-size:12px;">';
            foreach ($suggestions as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul>';
        }
        if (!$this->is_premium) {
            echo '<p><a href="#" class="button button-primary" onclick="alert(\'Upgrade to Pro for AI rewriting! Visit example.com/pricing\')">Upgrade to Pro</a></p>';
        } else {
            echo '<p><a href="#" class="button button-primary ai-rewrite-btn">AI Rewrite Now</a></p>';
        }
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_cop_nonce']) || !wp_verify_nonce($_POST['ai_cop_nonce'], 'ai_cop_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $content = get_post_field('post_content', $post_id);
        $score = $this->calculate_seo_score($content);
        $suggestions = $this->generate_suggestions($content, $score);

        update_post_meta($post_id, '_ai_cop_score', $score);
        update_post_meta($post_id, '_ai_cop_suggestions', $suggestions);

        if ($this->is_premium && isset($_POST['ai_rewrite'])) {
            $rewritten = $this->ai_rewrite_content($content);
            wp_update_post(array('ID' => $post_id, 'post_content' => $rewritten));
        }
    }

    private function calculate_seo_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $has_title = preg_match('/<h1[^>]*>/i', $content);
        $has_keywords = substr_count(strtolower($content), 'keyword') > 0; // Demo
        $score = 50;
        if ($word_count > 300) $score += 20;
        if ($has_title) $score += 15;
        if ($has_keywords) $score += 15;
        return min(100, $score);
    }

    private function generate_suggestions($content, $score) {
        $suggestions = array();
        if ($score < 60) $suggestions[] = 'Add more content (aim for 1000+ words).';
        if ($score < 75) $suggestions[] = 'Include H1-H3 headings.';
        if ($score < 80) $suggestions[] = 'Incorporate target keywords naturally.';
        $suggestions[] = 'Upgrade to Pro for full AI optimization.';
        return $suggestions;
    }

    private function ai_rewrite_content($content) {
        // Demo AI rewrite (in real: integrate OpenAI API)
        return '<p><strong>Premium AI Rewritten:</strong> ' . strip_tags($content) . ' Optimized for SEO with better structure and keywords!</p>';
    }

    public function premium_notice() {
        if (!$this->is_premium && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> features: AI rewriting, bulk optimization. <a href="https://example.com/pricing">Upgrade now ($9.99/mo)</a></p></div>';
        }
    }

    public function enqueue_assets() {
        if (is_singular()) {
            wp_enqueue_script('ai-cop-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }
}

new AIContentOptimizerPro();

// Settings page
add_action('admin_menu', function() {
    add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-cop', function() {
        if (isset($_POST['ai_cop_license'])) {
            update_option('ai_cop_license_key', sanitize_text_field($_POST['license_key']));
            echo '<div class="updated"><p>License activated! (Demo)</p></div>';
        }
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1><form method="post"><p>License Key: <input type="text" name="license_key" placeholder="Enter Pro key"></p><p><input type="submit" name="ai_cop_license" class="button-primary" value="Activate Pro"></p></form></div>';
    });
});

// Demo assets dir (create manually)
// assets/frontend.js: console.log('AI Optimizer loaded on frontend.');
?>