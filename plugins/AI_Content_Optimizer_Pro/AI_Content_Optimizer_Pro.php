/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_post_upgrade_premium', array($this, 'handle_upgrade'));
    }

    public function activate() {
        add_option('ai_content_optimizer_activated', time());
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_nonce', 'ai_content_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_optimize_score', true);
        $is_premium = $this->is_premium();
        echo '<div id="ai-optimizer-results">';
        if ($score) {
            echo '<p><strong>SEO Score:</strong> ' . $score . '%</p>';
        }
        echo '<p><a href="#" id="ai-analyze-btn" class="button">' . ($score ? 'Re-analyze' : 'Analyze Content') . '</a></p>';
        if (!$is_premium) {
            echo '<p><em>Upgrade to Premium for AI rewriting & unlimited scans!</em></p>';
            echo '<a href="' . admin_url('admin.php?page=ai-optimizer-settings') . '" class="button button-primary">Upgrade Now ($9.99/mo)</a>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_content_nonce']) || !wp_verify_nonce($_POST['ai_content_nonce'], 'ai_content_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function enqueue_scripts($hook) {
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-js', 'ai_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimize_nonce')));
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
        }
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimize_nonce', 'nonce');
        if (!$this->is_premium() && $this->get_usage_count() >= 5) {
            wp_die(json_encode(array('error' => 'Upgrade to premium for unlimited use!')));
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        $score = $this->calculate_score($content);
        update_post_meta($post_id, '_ai_optimize_score', $score);
        if ($this->is_premium()) {
            $suggestions = $this->generate_suggestions($content);
        } else {
            $suggestions = 'Basic analysis complete. Score: ' . $score . '%. Upgrade for AI-powered improvements.';
        }
        $this->increment_usage();
        wp_die(json_encode(array('score' => $score, 'suggestions' => $suggestions)));
    }

    private function calculate_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $word_count > 0 ? 100 - ($word_count / max(1, $sentence_count) * 10) : 0;
        $seo_score = min(100, ($word_count / 300) * 50 + ($readability / 2));
        return round($seo_score);
    }

    private function generate_suggestions($content) {
        // Simulated AI suggestions (in real: integrate OpenAI API)
        return "- Add more keywords.\n- Improve sentence variety.\n- Aim for 300+ words.";
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    private function get_usage_count() {
        return get_option('ai_optimizer_usage', 0);
    }

    private function increment_usage() {
        $count = $this->get_usage_count() + 1;
        update_option('ai_optimizer_usage', $count);
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['upgrade'])) {
            // Simulate payment (in real: integrate Stripe/PayPal)
            update_option(self::PREMIUM_KEY, 'activated');
            echo '<div class="notice notice-success"><p>Premium activated! (Demo - real integration needed)</p></div>';
        }
        echo '<div class="wrap">';
        echo '<h1>AI Content Optimizer Settings</h1>';
        if (!$this->is_premium()) {
            echo '<form method="post"><p>Upgrade to Premium for AI rewriting, keyword research, and unlimited optimizations.</p>';
            echo '<input type="submit" name="upgrade" class="button button-primary" value="Activate Premium ($9.99/mo - Demo)" />';
            echo '</form>';
        } else {
            echo '<p>Premium active! Enjoy unlimited features.</p>';
        }
        echo '<p>Usage: ' . $this->get_usage_count() . '/5 (free limit)</p>';
        echo '</div>';
    }

    public function handle_upgrade() {
        // Placeholder for real payment handling
    }
}

new AIContentOptimizer();

// Dummy JS/CSS placeholders (in real plugin, include files)
function ai_optimizer_assets() {
    // JS would handle AJAX call to analyze
    // CSS for styling meta box
}

?>