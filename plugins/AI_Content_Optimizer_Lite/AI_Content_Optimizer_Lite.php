/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Prevent direct access
define('AICOPT_VERSION', '1.0.0');
define('AICOPT_PLUGIN_FILE', __FILE__);
define('AICOPT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AICOPT_PATH', plugin_dir_path(__FILE__));
define('AICOPT_URL', plugin_dir_url(__FILE__));

class AI_Content_Optimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aicopt_analyze', array($this, 'ajax_analyze'));
        add_action('wp_ajax_aicopt_upgrade', array($this, 'ajax_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            $this->check_premium();
        }
    }

    public function add_admin_menu() {
        add_posts_page(
            __('AI Content Optimizer', 'ai-content-optimizer'),
            __('Content Optimizer', 'ai-content-optimizer'),
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'ai-content-optimizer_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('aicopt-admin-js', AICOPT_URL . 'assets/admin.js', array('jquery'), AICOPT_VERSION, true);
        wp_localize_script('aicopt-admin-js', 'aicopt_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicopt_nonce'),
            'is_premium' => $this->is_premium(),
            'free_limit' => 3
        ));
        wp_enqueue_style('aicopt-admin-css', AICOPT_URL . 'assets/admin.css', array(), AICOPT_VERSION);
    }

    public function admin_page() {
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        $post = get_post($post_id);
        if (!$post) {
            echo '<div class="notice notice-error"><p>' . __('No post selected.', 'ai-content-optimizer') . '</p></div>';
            return;
        }
        $content = $post->post_content;
        $analysis = get_post_meta($post_id, '_aicopt_analysis', true);
        $usage = get_option('aicopt_usage', array('count' => 0));
        include AICOPT_PATH . 'templates/admin-page.php';
    }

    public function ajax_analyze() {
        check_ajax_referer('aicopt_nonce', 'nonce');
        if (!$this->is_premium()) {
            $usage = get_option('aicopt_usage', array('count' => 0));
            if ($usage['count'] >= 3) {
                wp_send_json_error(__('Free limit reached. Upgrade to premium!', 'ai-content-optimizer'));
            }
            $usage['count']++;
            update_option('aicopt_usage', $usage);
        }

        $post_id = intval($_POST['post_id']);
        $content = sanitize_textarea_field($_POST['content']);

        // Simulated AI analysis (basic readability and SEO checks)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $sentence_count > 0 ? round(180 - ($word_count / $sentence_count * 15), 2) : 0; // Flesch approx
        $keywords = $this->extract_keywords($content);
        $density = $word_count > 0 ? round(($keywords['count'] * 100 / $word_count), 2) : 0;

        $analysis = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'keywords' => $keywords['top'],
            'density' => $density,
            'score' => min(100, round(($readability / 80 * 50) + ($density > 1 ? 50 : $density * 20))),
            'suggestions' => $this->get_suggestions($readability, $density)
        );

        update_post_meta($post_id, '_aicopt_analysis', $analysis);
        wp_send_json_success($analysis);
    }

    private function extract_keywords($content) {
        $words = explode(' ', preg_replace('/[^a-zA-Z\s]/', '', strtolower(strip_tags($content))));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array('top' => array_slice(array_keys($counts), 0, 5), 'count' => count($counts));
    }

    private function get_suggestions($readability, $density) {
        $sugs = array();
        if ($readability < 60) $sugs[] = __('Shorten sentences for better readability.', 'ai-content-optimizer');
        if ($density < 1) $sugs[] = __('Add more primary keywords.', 'ai-content-optimizer');
        if ($density > 3) $sugs[] = __('Reduce keyword density to avoid stuffing.', 'ai-content-optimizer');
        return $sugs;
    }

    public function ajax_upgrade() {
        check_ajax_referer('aicopt_nonce', 'nonce');
        // Freemius integration placeholder - replace with actual Freemius code
        echo '<p>' . __('Upgrade to premium for unlimited access!', 'ai-content-optimizer') . ' <a href="https://example.com/premium" target="_blank">' . __('Get Premium', 'ai-content-optimizer') . '</a></p>';
        wp_die();
    }

    private function is_premium() {
        // Check for premium license - integrate with Freemius
        return false; // Lite version
    }

    private function check_premium() {
        // Premium nag
        if (!$this->is_premium() && current_user_can('manage_options')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info"><p>' . sprintf(__('Upgrade %s to premium for unlimited scans and AI rewrites!', 'ai-content-optimizer'), '<strong>AI Content Optimizer</strong>') . ' <a href="https://example.com/premium" target="_blank">' . __('Upgrade Now', 'ai-content-optimizer') . '</a></p></div>';
            });
        }
    }

    public function activate() {
        update_option('aicopt_usage', array('count' => 0));
    }
}

new AI_Content_Optimizer();

// Create assets directories if needed
add_action('init', function() {
    $assets = AICOPT_PATH . 'assets/';
    if (!file_exists($assets)) wp_mkdir_p($assets);
    $templates = AICOPT_PATH . 'templates/';
    if (!file_exists($templates)) wp_mkdir_p($templates);
});

// Note: Create empty assets/admin.js, assets/admin.css, templates/admin-page.php files manually or add inline
// For demo: admin.js with AJAX calls, admin.css for styling, admin-page.php with analysis display and button.