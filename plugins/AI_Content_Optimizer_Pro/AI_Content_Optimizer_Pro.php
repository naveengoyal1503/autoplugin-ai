/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes WordPress post content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->check_premium();
    }

    private function check_premium() {
        // Simulate premium check (in real, use license key or Freemius)
        $this->is_premium = get_option('ai_content_optimizer_premium', false);
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_license']) && check_admin_referer('ai_optimizer_settings')) {
            update_option('ai_content_optimizer_premium', true);
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer</h1>
            <form method="post">
                <?php wp_nonce_field('ai_optimizer_settings'); ?>
                <p><label>Enter Premium License Key: <input type="text" name="ai_optimizer_license" /></label></p>
                <p><?php submit_button(); ?></p>
            </form>
            <p><strong>Free Features:</strong> Basic SEO score and readability analysis.</p>
            <p><strong>Premium ($9/mo):</strong> AI rewrites, bulk optimization. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_meta_box_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $premium_msg = $this->is_premium ? '' : '<p><em>Upgrade to premium for AI rewrite.</em></p>';
        echo '<p><strong>SEO Score:</strong> ' . esc_html($score ?: 'Not analyzed') . '/100</p>';
        echo '<button id="ai-analyze" class="button">Analyze Now</button>';
        echo $premium_msg;
        echo '<div id="ai-results"></div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_meta_box_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_meta_box_nonce'], 'ai_optimizer_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function enqueue_scripts() {
        if (is_single()) {
            wp_enqueue_script('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-admin', 'ai_optimizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_ajax'),
                'is_premium' => $this->is_premium
            ));
        }
    }

    // AJAX handler for analysis
    public function init_ajax() {
        add_action('wp_ajax_ai_analyze_content', array($this, 'analyze_content'));
    }

    public function analyze_content() {
        check_ajax_referer('ai_optimizer_ajax', 'nonce');
        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_die();
        }

        $content = get_post_field('post_content', $post_id);
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count / 10) + rand(0, 30)); // Simulated basic analysis

        update_post_meta($post_id, '_ai_optimizer_score', $score);

        if ($this->is_premium) {
            // Simulated premium AI rewrite
            $rewrite = 'Premium AI Rewrite: ' . substr($content, 0, 100) . '...';
            wp_send_json_success(array('score' => $score, 'rewrite' => $rewrite));
        } else {
            wp_send_json_success(array('score' => $score, 'msg' => 'Upgrade for AI rewrite.'));
        }
    }

    public function activate() {
        // Activation hook
    }
}

AIContentOptimizer::get_instance();