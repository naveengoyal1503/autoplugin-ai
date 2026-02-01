/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Freemium model with premium features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerPro {
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
        add_action('wp_ajax_aco_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        }
    }

    public function activate() {
        add_option('aco_premium_active', false);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aco-frontend', plugin_dir_url(__FILE__) . 'assets/aco.js', array('jquery'), '1.0.0', true);
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'assets/aco-admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        }
    }

    public function add_meta_box() {
        add_meta_box('aco-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-results">';
        echo '<p><strong>Free Analysis:</strong></p>';
        echo '<button id="aco-analyze" class="button button-secondary">Analyze Content</button>';
        echo '<div id="aco-basic-score"></div>';
        if (!$this->is_premium()) {
            echo '<p><strong>Upgrade to Pro for AI Rewrite & Bulk Optimize!</strong></p>';
            echo '<a href="https://example.com/premium" target="_blank" class="button button-primary">Get Premium ($4.99/mo)</a>';
        } else {
            echo '<button id="aco-optimize" class="button button-primary" style="display:none;">AI Optimize</button>';
            echo '<div id="aco-premium-results"></div>';
        }
        echo '</div>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);
        $action = sanitize_text_field($_POST['action_type']);

        if ('analyze' === $action) {
            // Basic free analysis: word count, readability score (Flesch-Kincaid simulation)
            $word_count = str_word_count($content);
            $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
            $sentence_count = count($sentences);
            $readability = $this->calculate_readability($content);
            $seo_score = min(100, ($word_count / 10) + ($readability * 20)); // Simulated SEO score

            wp_send_json_success(array(
                'score' => round($seo_score),
                'words' => $word_count,
                'readability' => round($readability),
                'tips' => $this->get_basic_tips($seo_score)
            ));
        } elseif ('optimize' === $action && $this->is_premium()) {
            // Premium AI simulation: simple improvements
            $optimized = $this->simulate_ai_optimize($content);
            wp_send_json_success(array('optimized_content' => $optimized));
        } else {
            wp_send_json_error('Premium feature required.');
        }
    }

    private function calculate_readability($content) {
        $words = str_word_count($content, 1);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += substr_count(preg_replace('/[^aeiouy]/i', '', $word), '');
        }
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = max(1, count($sentences));
        return 206.835 - 1.015 * (count($words) / $sentences) - 84.6 * ($syllables / count($words));
    }

    private function get_basic_tips($score) {
        if ($score < 50) return 'Add more keywords and shorten sentences.';
        if ($score < 80) return 'Improve readability and add headings.';
        return 'Great! Content is optimized.';
    }

    private function simulate_ai_optimize($content) {
        // Simulated AI: add headings, bold keywords, improve structure
        $content = preg_replace('/(SEO|content|WordPress)/i', '<strong>$1</strong>', $content);
        if (strpos($content, '<h2>') === false) {
            $content = '<h2>Optimized Content</h2>' . $content;
        }
        return $content . '<p><em>AI enhanced for better SEO and engagement.</em></p>';
    }

    private function is_premium() {
        return get_option('aco_premium_active', false);
    }

    public function plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        }
        return $links;
    }
}

AIContentOptimizerPro::get_instance();

// Create assets directory placeholder (in real plugin, include JS files)
// For demo: simple JS would handle AJAX calls
?>