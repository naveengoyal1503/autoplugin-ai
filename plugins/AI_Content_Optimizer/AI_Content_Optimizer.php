/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes post readability and SEO with AI-powered suggestions. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Requires at least: 5.0
 * Tested up to: 6.6
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('AICOPT_VERSION', '1.0.0');
define('AICOPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICOPT_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Freemius integration (replace with your Freemius ID)
if (function_exists('freemius')) {
    // Initialize Freemius
    require_once AICOPT_PLUGIN_PATH . 'freemius-start.php';
} else {
    // Fallback: Simple nag for premium
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aicopt_analyze', array($this, 'ajax_analyze'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('aicopt-admin', AICOPT_PLUGIN_URL . 'assets/admin.js', array('jquery'), AICOPT_VERSION, true);
        wp_localize_script('aicopt-admin', 'aicopt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicopt_nonce'),
            'is_premium' => false // Set true for premium users
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'aicopt-meta',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aicopt_meta_nonce', 'aicopt_meta_nonce');
        echo '<div id="aicopt-results"></div>';
        echo '<button id="aicopt-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Readability & Keyword Score | <strong>Premium:</strong> AI Suggestions & Fixes</small></p>';
    }

    public function ajax_analyze() {
        check_ajax_referer('aicopt_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(($word_count / $sentence_count) / 15 * 100, 1) : 0; // Flesch approx
        $keywords = $this->extract_keywords($content);

        $results = array(
            'word_count' => $word_count,
            'readability_score' => min(100, max(0, 100 - $readability)), // Simplified score
            'keyword_density' => $keywords ? round(($keywords['count'] * 100 / $word_count), 1) : 0,
            'premium_nag' => 'Upgrade to Premium for AI-powered suggestions, auto-fixes, and bulk optimization!',
            'is_premium' => false
        );

        // Simulate premium (in real, check license)
        if (isset($_POST['premium_key']) && $_POST['premium_key'] === 'demo') {
            $results['ai_suggestions'] = array(
                'Shorten sentences under 20 words',
                'Add subheadings for scannability',
                'Include call-to-action at end'
            );
            $results['is_premium'] = true;
            $results['premium_nag'] = '';
        }

        wp_send_json_success($results);
    }

    private function extract_keywords($content) {
        $content = strtolower(strip_tags($content));
        preg_match_all('/\b\w+\b/', $content, $matches);
        $words = array_count_values($matches);
        arsort($words);
        $keywords = array();
        $i = 0;
        foreach ($words as $word => $count) {
            if (strlen($word) > 4 && $i < 5) {
                $keywords[] = array('word' => $word, 'count' => $count);
                $i++;
            }
        }
        return $keywords;
    }

    public function activate() {
        // Activation hook
    }
}

new AIContentOptimizer();

// Freemius placeholder (in real plugin, include Freemius SDK)
// require_once AICOPT_PLUGIN_PATH . 'freemius/includes/freemius.php';
// $freemius = fs_dynamic_init();