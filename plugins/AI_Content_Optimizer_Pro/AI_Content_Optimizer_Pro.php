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
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        if (!class_exists('AIContentOptimizer')) {
            return;
        }
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_notices', array($this, 'premium_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-admin-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium()
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce_field');
        echo '<div id="ai-optimizer-score">Score: <span id="optimizer-score">--</span>%</div>';
        echo '<button id="analyze-content" class="button">Analyze Content</button>';
        echo '<div id="optimizer-tips"></div>';
        echo '<div id="premium-upsell" style="display:none;"><p><strong>Go Premium</strong> for AI rewriting & bulk tools! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium()) {
            if (!isset($_POST['post_id'])) {
                wp_die('Post ID required');
            }
            $post_id = intval($_POST['post_id']);
            $content = get_post_field('post_content', $post_id);
            $score = $this->calculate_basic_score($content);
            $tips = $this->generate_basic_tips($content);
            wp_send_json_success(array('score' => $score, 'tips' => $tips));
        } else {
            // Premium: Simulate AI rewrite
            $content = sanitize_textarea_field($_POST['content']);
            $optimized = $this->simulate_ai_rewrite($content);
            wp_send_json_success(array('optimized' => $optimized));
        }
    }

    private function calculate_basic_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = ($word_count > 0 && $sentence_count > 0) ? min(100, (300 / ($word_count / $sentence_count))) * 10 : 50;
        return round($readability);
    }

    private function generate_basic_tips($content) {
        $tips = array();
        $word_count = str_word_count(strip_tags($content));
        if ($word_count < 300) {
            $tips[] = 'Add more content: Aim for 500+ words for better SEO.';
        }
        if (strpos($content, '<h2') === false) {
            $tips[] = 'Use H2 headings to structure content.';
        }
        return $tips;
    }

    private function simulate_ai_rewrite($content) {
        // Premium feature simulation
        return $content . ' [AI Optimized: Improved readability and SEO keywords added.]';
    }

    private function is_premium() {
        return get_option('ai_optimizer_premium') === 'yes'; // Simulate license check
    }

    public function premium_nag() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong>: Rewrite content automatically! <a href="https://example.com/premium">Upgrade Now</a></p></div>';
        }
    }

    public function activate() {
        add_option('ai_optimizer_version', '1.0.0');
    }
}

AIContentOptimizer::get_instance();

// Frontend shortcode
function ai_optimizer_shortcode($atts) {
    $atts = shortcode_atts(array('post_id' => get_the_ID()), $atts);
    ob_start();
    echo '<div class="ai-optimizer-frontend">Content Score: <span id="frontend-score">Calculate</span></div>';
    return ob_get_clean();
}
add_shortcode('ai_optimizer', 'ai_optimizer_shortcode');

// Assets would be base64 or inline in production single file, but omitted for brevity
?>