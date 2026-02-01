/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO using smart algorithms. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize_ajax'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
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

    public function enqueue_scripts($hook) {
        if ('settings_page' !== get_current_screen()->id && strpos($hook, 'post.php') === false && strpos($hook, 'post-new.php') === false) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), self::VERSION, true);
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'isPremium' => $this->is_premium(),
            'limitReached' => $this->is_limit_reached()
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), self::VERSION);
    }

    public function settings_page() {
        $is_premium = $this->is_premium();
        $usage = get_option('ai_optimizer_usage', 0);
        $limit = $is_premium ? 'Unlimited' : '5 per month';
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer', 'ai-content-optimizer'); ?></h1>
            <p><strong>Current Usage:</strong> <?php echo $usage; ?>/<?php echo $limit; ?> optimizations this month.</p>
            <?php if (!$is_premium && $this->is_limit_reached()): ?>
                <div class="notice notice-warning"><p><?php _e('Monthly limit reached. Upgrade to premium for unlimited access!', 'ai-content-optimizer'); ?></p></div>
            <?php endif; ?>
            <?php if (!$is_premium): ?>
                <div class="notice notice-info">
                    <h3>Go Premium!</h3>
                    <p>Unlock unlimited optimizations, advanced AI suggestions, and auto-fix for just <strong>$4.99/month</strong>.</p>
                    <a href="https://example.com/premium" class="button button-primary" target="_blank">Upgrade Now</a>
                </div>
            <?php endif; ?>
            <h2>Features:</h2>
            <ul>
                <li>Basic SEO analysis (free)</li>
                <?php if ($is_premium): ?>
                    <li>Advanced keyword suggestions</li>
                    <li>Auto-optimization</li>
                <?php endif; ?>
            </ul>
        </div>
        <?php
    }

    public function handle_optimize_ajax() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        if (!$this->is_premium() && $this->is_limit_reached()) {
            wp_send_json_error('Limit reached. Upgrade to premium.');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real version, integrate with OpenAI API or similar)
        $suggestions = $this->analyze_content($content);

        if (!$this->is_premium()) {
            $this->increment_usage();
        }

        wp_send_json_success($suggestions);
    }

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $has_headings = preg_match('/<h[1-6]/', $content);
        $suggestions = array(
            'word_count' => $word_count,
            'optimal_length' => '300-1500 words',
            'headings' => $has_headings ? 'Good' : 'Add H2/H3 tags',
            'keywords' => 'Suggest: "WordPress", "plugin", "SEO"',
            'readability' => $word_count > 100 ? 'Good' : 'Expand content',
            'premium_only' => $this->is_premium() ? array(
                'auto_fix' => 'Click to auto-optimize',
                'competitor_analysis' => 'Top keywords from competitors'
            ) : null
        );
        return $suggestions;
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    private function is_limit_reached() {
        if ($this->is_premium()) return false;
        $usage = get_option('ai_optimizer_usage', 0);
        $month_key = 'ai_optimizer_month_' . date('Y-m');
        $month_start = get_option($month_key, 0);
        return $usage >= 5;
    }

    private function increment_usage() {
        $usage = get_option('ai_optimizer_usage', 0) + 1;
        update_option('ai_optimizer_usage', $usage);
    }

    public function activate() {
        if (!get_option('ai_optimizer_usage')) {
            update_option('ai_optimizer_usage', 0);
        }
    }
}

// Add meta box to post editor
add_action('add_meta_boxes', function() {
    add_meta_box('ai-optimizer', 'AI Content Optimizer', 'ai_optimizer_meta_box', 'post', 'side');
});

function ai_optimizer_meta_box($post) {
    echo '<div id="ai-optimizer-box">
        <button id="optimize-btn" class="button button-secondary">Analyze Content</button>
        <div id="optimizer-results"></div>
        <input type="hidden" id="post-id" value="' . $post->ID . '" />
    </div>';
}

new AIContentOptimizer();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($plugin_dir . 'script.js')) {
        mkdir($plugin_dir, 0755, true);
        file_put_contents($plugin_dir . 'script.js', '// AI Optimizer JS placeholder\n console.log("AI Optimizer loaded");');
        file_put_contents($plugin_dir . 'style.css', '/* AI Optimizer CSS */ #ai-optimizer-box { margin: 10px 0; }');
    }
});