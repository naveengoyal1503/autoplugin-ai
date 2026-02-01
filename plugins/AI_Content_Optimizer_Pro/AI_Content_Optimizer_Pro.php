/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes your WordPress posts and pages for better SEO using AI-powered analysis. Free version provides basic checks; premium unlocks advanced features.
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
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
        if (is_admin()) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $premium = $this->is_premium();
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>SEO Score:</strong> ' . esc_html($score ?: 'Not analyzed') . '%</p>';
        if (!$premium) {
            echo '<p><a href="https://example.com/premium" target="_blank" class="button button-primary">Upgrade to Pro</a></p>';
            echo '<p>Free: Basic keyword analysis. Pro: AI suggestions & auto-optimize.</p>';
        } else {
            echo '<button id="ai-optimize-btn" class="button button-secondary">Optimize Now</button>';
        }
        echo '</div>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $score = $this->calculate_basic_score($post_id);
        update_post_meta($post_id, '_ai_optimizer_score', $score);
    }

    private function calculate_basic_score($post_id) {
        $post = get_post($post_id);
        $content = $post->post_title . ' ' . $post->post_content;
        $word_count = str_word_count(strip_tags($content));
        $has_title = strlen($post->post_title) > 10;
        $score = 50; // Base
        $score += $word_count > 300 ? 25 : 0;
        $score += $has_title ? 25 : 0;
        // Simulate basic keyword check
        if (preg_match('/(seo|content|wordpress)/i', $content)) {
            $score += 20;
        }
        return min(100, $score);
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page_html')
        );
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_optimizer_settings');
                do_settings_sections('ai_optimizer_settings');
                submit_button();
                ?>
            </form>
            <?php if (!$this->is_premium()): ?>
            <div class="notice notice-info">
                <p>Unlock <strong>AI-powered suggestions</strong>, auto-optimization, and more with Pro! <a href="https://example.com/premium" target="_blank">Get Pro Now</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting('ai_optimizer_settings', 'ai_optimizer_api_key');
        add_settings_section(
            'ai_optimizer_section',
            'API Settings',
            null,
            'ai_optimizer_settings'
        );
        add_settings_field(
            'ai_optimizer_api_key',
            'Premium API Key',
            array($this, 'api_key_field'),
            'ai_optimizer_settings',
            'ai_optimizer_section'
        );
    }

    public function api_key_field() {
        $key = get_option('ai_optimizer_api_key');
        echo '<input type="text" name="ai_optimizer_api_key" value="' . esc_attr($key) . '" class="regular-text" placeholder="Enter Pro API Key" />';
        echo '<p class="description">Get your key from <a href="https://example.com/account" target="_blank">your account</a>.</p>';
    }

    private function is_premium() {
        return !empty(get_option('ai_optimizer_api_key'));
    }

    public function activate() {
        // Create default options
        add_option('ai_optimizer_api_key', '');
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

AIContentOptimizer::get_instance();

// Inline assets for single file
/*
function ai_optimizer_assets() {
    echo '<style>
    #ai-optimizer-panel { padding: 10px; }
    #ai-optimize-btn { width: 100%; }
    </style>';
    echo '<script>
    jQuery(document).ready(function($) {
        $("#ai-optimize-btn").click(function() {
            alert("Premium feature: Content optimized!");
        });
    });
    </script>';
}
add_action("admin_footer", "ai_optimizer_assets");
*/
?>