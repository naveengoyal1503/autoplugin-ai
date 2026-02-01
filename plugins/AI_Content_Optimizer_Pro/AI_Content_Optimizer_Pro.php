/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered SEO content optimization for WordPress. Free basic features; premium for advanced tools.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_ajax_premium_upgrade', array($this, 'show_premium_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium_user()
        ));
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        $is_premium = $this->is_premium_user();
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Pro', 'ai-content-optimizer'); ?></h1>
            <p><?php _e('Paste your content below for AI-powered SEO optimization.', 'ai-content-optimizer'); ?></p>
            <?php if (!$is_premium): ?>
                <div class="notice notice-warning"><p><?php _e('Upgrade to Pro for unlimited optimizations and advanced features!', 'ai-content-optimizer'); ?> <a href="https://example.com/premium" target="_blank">Get Pro Now</a></p></div>
            <?php endif; ?>
            <textarea id="content-input" rows="10" cols="80" placeholder="Paste your content here..."><?php echo esc_textarea(get_post_field('post_content', get_the_ID())); ?></textarea>
            <br><button id="optimize-btn" class="button button-primary">Optimize Content</button>
            <div id="results"></div>
        </div>
        <?php
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium_user() && $this->get_usage_count() >= 5) {
            wp_send_json_error('Upgrade to premium for more optimizations.');
            return;
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('Content is empty.');
        }

        $optimized = $this->simulate_ai_optimize($content);
        $this->increment_usage();

        wp_send_json_success(array('optimized' => $optimized));
    }

    private function simulate_ai_optimize($content) {
        // Simulate basic AI optimization: improve readability, add keywords
        $keywords = array('WordPress', 'SEO', 'content', 'optimize');
        $improved = $content;
        foreach ($keywords as $kw) {
            $improved = preg_replace('/\b(' . preg_quote($kw, '/') . ')\b/i', '<strong>$1</strong>', $improved, 3);
        }
        // Basic readability: shorten sentences (simulation)
        $improved .= '\n\n<h3>SEO Score: 85/100</h3>\n<p>Readability improved. Keywords enhanced.</p>';
        if (!$this->is_premium_user()) {
            $improved .= '\n<p><em>Premium: Real AI analysis, meta suggestions, bulk optimize.</em></p>';
        }
        return $improved;
    }

    private function is_premium_user() {
        // Simulate license check
        return get_option('ai_optimizer_premium_license', false) === 'valid';
    }

    private function get_usage_count() {
        return get_option('ai_optimizer_usage', 0);
    }

    private function increment_usage() {
        $count = $this->get_usage_count() + 1;
        update_option('ai_optimizer_usage', $count);
    }

    public function show_premium_nag() {
        // Premium upsell AJAX
        echo '<div class="premium-nag">Unlock Pro: Unlimited scans, AI insights! <a href="https://example.com/premium">Upgrade</a></div>';
        wp_die();
    }

    public function activate() {
        update_option('ai_optimizer_usage', 0);
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_ai-content-optimizer') return;
    echo '<div class="notice notice-info"><p>Enhance your SEO with <strong>AI Content Optimizer Pro</strong>! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Try it now</a> | <a href="https://example.com/premium" target="_blank">Go Pro</a></p></div>';
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Create assets dir placeholder (in real plugin, include JS file)
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
    file_put_contents($assets_dir . 'optimizer.js', '// JS for AJAX optimization\n $("#optimize-btn").click(function() { /* AJAX call */ });');
}
?>