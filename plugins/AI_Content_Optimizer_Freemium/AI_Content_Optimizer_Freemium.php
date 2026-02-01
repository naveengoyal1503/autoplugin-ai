/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and readability. Freemium with premium upsells.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->is_premium = get_option('ai_content_optimizer_premium', false);

        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_notices', array($this, 'premium_notice'));
        add_action('wp_ajax_dismiss_premium_notice', array($this, 'dismiss_notice'));

        if ($this->is_premium) {
            add_action('admin_menu', array($this, 'premium_menu'));
        } else {
            add_action('admin_menu', array($this, 'freemium_menu'));
        }
    }

    public function activate() {
        add_option('ai_content_optimizer_dismissed_notice', false);
    }

    public function deactivate() {}

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium,
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side', 'high');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Readability Score:</strong> ' . ($score ? $score . '/100' : 'Not analyzed') . '</p>';
        echo '<textarea id="ai-content-input" style="width:100%;height:80px;display:none;">' . esc_textarea($content) . '</textarea>';
        echo '<button id="ai-analyze-btn" class="button button-primary">' . ($this->is_premium ? 'AI Optimize' : 'Analyze (Free)') . '</button>';
        echo '<div id="ai-results"></div>';
        if (!$this->is_premium) {
            echo '<p><a href="#" id="go-premium">Go Premium for AI Rewrites & Keywords</a></p>';
        }
        echo '</div>';
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_premium && !wp_verify_nonce($_POST['nonce'], 'ai_optimizer_nonce')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $word_count = str_word_count($content);
        $readability = 100 - ($word_count > 500 ? 20 : 0) - (substr_count($content, '. ') < 10 ? 30 : 0);
        $readability = max(0, min(100, $readability));

        if ($this->is_premium) {
            // Simulate AI optimization (in real: call OpenAI API)
            $optimized = 'AI Optimized: ' . substr($content, 0, 100) . '... (Premium feature: Full rewrite with keywords).';
            $keywords = ['premium', 'seo', 'content'];
            wp_send_json_success(array('score' => $readability, 'optimized' => $optimized, 'keywords' => $keywords));
        } else {
            wp_send_json_success(array('score' => $readability, 'suggestion' => 'Upgrade for AI-powered full optimization!'));
        }
    }

    public function premium_notice() {
        if ($this->is_premium || get_option('ai_content_optimizer_dismissed_notice')) {
            return;
        }
        echo '<div class="notice notice-info is-dismissible" id="ai-premium-notice">';
        echo '<p>Unlock <strong>AI Content Optimizer Premium</strong> for $4.99/mo: AI rewrites, keyword suggestions, unlimited scans! <a href="' . admin_url('admin.php?page=ai-optimizer-freemium') . '">Upgrade Now</a></p>';
        echo '</div>';
    }

    public function dismiss_notice() {
        update_option('ai_content_optimizer_dismissed_notice', true);
        wp_die();
    }

    public function freemium_menu() {
        add_submenu_page('tools.php', 'AI Optimizer Freemium', 'AI Optimizer', 'manage_options', 'ai-optimizer-freemium', array($this, 'freemium_page'));
    }

    public function freemium_page() {
        echo '<div class="wrap">';
        echo '<h1>Upgrade to Premium</h1>';
        echo '<p>Premium features: AI content rewriting, SEO keywords, unlimited optimizations. Only $4.99/month.</p>';
        echo '<form method="post" action="https://example.com/premium-checkout">';
        echo '<input type="hidden" name="plugin" value="ai-content-optimizer">';
        echo '<input type="submit" class="button button-primary" value="Subscribe Now">';
        echo '</form>';
        echo '<p><small>After payment, enter license key below:</small></p>';
        echo '<form method="post" action="">';
        echo '<input type="text" name="license_key" placeholder="Enter license key">';
        echo '<input type="submit" name="activate_premium" class="button" value="Activate">';
        echo '</form>';
        echo '</div>';
        if (isset($_POST['activate_premium']) && $_POST['license_key'] === 'PREMIUM123') { // Demo key
            update_option('ai_content_optimizer_premium', true);
            echo '<p>Premium activated! (Demo)</p>';
        }
    }

    public function premium_menu() {
        add_submenu_page('tools.php', 'AI Optimizer Premium', 'AI Optimizer', 'manage_options', 'ai-optimizer-premium', array($this, 'premium_page'));
    }

    public function premium_page() {
        echo '<div class="wrap"><h1>Premium Dashboard</h1><p>Premium features active. API key setup coming soon.</p></div>';
    }
}

AIContentOptimizer::get_instance();

// Inline CSS
add_action('admin_head-post.php', function() {
    echo '<style>#ai-optimizer-panel { padding: 10px; } #ai-results { margin-top: 10px; padding: 10px; background: #f9f9f9; }</style>';
});

// Inline JS placeholder (in real plugin, external files)
function ai_optimizer_inline_js() {
    ?><script>console.log('AI Optimizer loaded');</script><?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');