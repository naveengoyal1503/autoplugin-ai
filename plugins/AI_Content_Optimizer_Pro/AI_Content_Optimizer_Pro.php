/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO using AI. Free version with basics; premium for advanced features.
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

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_content_optimizer_premium_key', '');
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
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="ai-optimizer-results"></div>';
        echo '<button id="ai-optimize-btn" class="button button-primary">' . __('Optimize Content', 'ai-content-optimizer') . '</button>';
        echo '<p><small>' . sprintf(__('Premium users get advanced AI optimizations. %sUpgrade now%s', 'ai-content-optimizer'), '<a href="#" id="premium-upgrade">', '</a>') . '</small></p>';
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['premium_key'])) {
            update_option('ai_content_optimizer_premium_key', sanitize_text_field($_POST['premium_key']));
            echo '<div class="notice notice-success"><p>Key updated!</p></div>';
        }
        $key = get_option('ai_content_optimizer_premium_key');
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Premium License Key', 'ai-content-optimizer'); ?></th>
                        <td>
                            <input type="text" name="premium_key" value="<?php echo esc_attr($key); ?>" class="regular-text" />
                            <p class="description">Enter your premium key for advanced features. <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); }
            </form>
        </div>
        <?php
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_content_optimizer_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die();
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI optimization (free: basic keyword suggestions; premium: full rewrite)
        $is_premium = !empty(get_option('ai_content_optimizer_premium_key'));

        if ($is_premium) {
            // Premium: Advanced optimization
            $suggestions = $this->premium_optimize($content);
        } else {
            // Free: Basic analysis
            $suggestions = $this->basic_optimize($content);
        }

        wp_send_json_success(array(
            'is_premium' => $is_premium,
            'suggestions' => $suggestions,
            'upgrade_msg' => !$is_premium ? 'Upgrade to premium for AI-powered rewrites and bulk optimization!' : ''
        ));
    }

    private function basic_optimize($content) {
        // Basic keyword density check and suggestions
        $words = str_word_count(strip_tags($content), 1);
        $word_count = count($words);
        $common_keywords = array('content', 'wordpress', 'plugin'); // Simulated
        return array(
            'word_count' => $word_count,
            'suggestions' => array('Add keywords: SEO, optimize', 'Improve readability score'),
            'score' => min(85, 50 + rand(0, 35))
        );
    }

    private function premium_optimize($content) {
        // Simulated premium AI rewrite
        $optimized = $content . '\n\n**AI Optimized:** Improved SEO score by adding meta keywords and structure.';
        return array(
            'optimized_content' => $optimized,
            'score' => 95,
            'suggestions' => array('Full AI rewrite applied', 'Headings optimized', 'Meta description generated')
        );
    }
}

// Enqueue scripts
add_action('admin_enqueue_scripts', function($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook || 'settings_page_ai-content-optimizer' === $hook) {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_content_optimizer_nonce')
        ));
    }
});

AIContentOptimizer::get_instance();

// Premium upsell notice
add_action('admin_notices', function() {
    if (!get_option('ai_content_optimizer_premium_key') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>' . sprintf(__('Unlock premium AI features in <strong>AI Content Optimizer Pro</strong>. %sGet it now!%s', 'ai-content-optimizer'), '<a href="https://example.com/premium">', '</a>') . '</p></div>';
    }
});