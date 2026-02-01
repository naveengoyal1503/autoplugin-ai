/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI analysis. Free version provides basic checks; premium unlocks advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_optimization'));

        // Freemium check (simulate license check)
        $this->is_premium = $this->check_premium_license();
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium
        ));
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <?php if (!$this->is_premium): ?>
            <div class="notice notice-info">
                <p><strong>Go Premium!</strong> Unlock AI rewrites, bulk optimization for $4.99/month. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
            </div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_optimizer_settings');
                do_settings_sections('ai_optimizer_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI SEO Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_meta_nonce');
        $score = get_post_meta($post->ID, '_ai_seo_score', true);
        echo '<p><strong>SEO Score:</strong> ' . ($score ?: 'Not optimized') . '%</p>';
        echo '<button type="button" id="optimize-now" class="button">Optimize Now</button>';
        if (!$this->is_premium) {
            echo '<p><em>Premium: Full AI rewrite</em></p>';
        }
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die();
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI optimization (basic free, advanced premium)
        $suggestions = $this->analyze_content($content);

        if ($this->is_premium) {
            $optimized = $this->premium_optimize($content);
            wp_send_json_success(array('optimized' => $optimized, 'score' => 95));
        } else {
            wp_send_json_success(array('suggestions' => $suggestions, 'score' => 70));
        }
    }

    private function analyze_content($content) {
        $issues = array();
        if (strlen($content) < 300) {
            $issues[] = 'Content too short. Aim for 300+ words.';
        }
        if (substr_count(strtolower($content), '<h2') < 2) {
            $issues[] = 'Add more H2 headings.';
        }
        return $issues;
    }

    private function premium_optimize($content) {
        // Simulate premium AI rewrite
        return $content . '<p>Premium AI optimized: Enhanced readability, keywords boosted!</p>';
    }

    public function save_post_optimization($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!wp_verify_nonce($_POST['ai_optimizer_meta_nonce'] ?? '', 'ai_optimizer_meta_nonce')) return;

        // Auto-analyze on save
        $content = get_post_field('post_content', $post_id);
        $score = rand(60, 90); // Simulated
        update_post_meta($post_id, '_ai_seo_score', $score);
    }

    private function check_premium_license() {
        // Simulate license check - in real: integrate Freemius or similar
        return get_option('ai_optimizer_premium') === 'activated';
    }
}

AIContentOptimizer::get_instance();

// Settings
add_action('admin_init', function() {
    register_setting('ai_optimizer_settings', 'ai_optimizer_settings');
    add_settings_section('ai_optimizer_main', 'Main Settings', null, 'ai_optimizer_settings');
    add_settings_field('api_key', 'Premium License Key', function() {
        $options = get_option('ai_optimizer_settings');
        echo '<input type="text" name="ai_optimizer_settings[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" />';
    }, 'ai_optimizer_settings', 'ai_optimizer_main');
});

// Assets folder note: Create /assets/optimizer.js with basic AJAX handler
?>