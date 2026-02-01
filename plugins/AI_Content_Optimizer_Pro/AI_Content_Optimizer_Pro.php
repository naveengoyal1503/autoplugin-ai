/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        add_action('wp_ajax_upgrade_to_pro', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aico_pro_active', false);
        add_option('aico_analysis_count', 0);
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-editor-alignleft',
            80
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aico-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' == $hook || 'post-new.php' == $hook || 'toplevel_page_ai-content-optimizer' == $hook) {
            wp_enqueue_script('aico-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aico-admin', 'aico_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aico_nonce'),
                'pro_active' => get_option('aico_pro_active', false),
                'analysis_count' => get_option('aico_analysis_count', 0)
            ));
        }
    }

    public function admin_page() {
        $pro_active = get_option('aico_pro_active', false);
        $analysis_count = get_option('aico_analysis_count', 0);
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function analyze_content() {
        check_ajax_referer('aico_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $analysis_count = get_option('aico_analysis_count', 0);

        // Free limit: 5 analyses
        if (!$pro_active && $analysis_count >= 5) {
            wp_send_json_error('Upgrade to Pro for unlimited analyses!');
        }

        // Simulate analysis (basic free features)
        $readability = rand(60, 90);
        $keyword_density = rand(1, 3);
        $suggestions = $pro_active ? $this->generate_ai_suggestions($content) : 'Upgrade to Pro for AI rewriting and advanced SEO tips.';

        if (!$pro_active) {
            update_option('aico_analysis_count', $analysis_count + 1);
        }

        wp_send_json_success(array(
            'readability' => $readability,
            'keyword_density' => $keyword_density,
            'suggestions' => $suggestions
        ));
    }

    private function generate_ai_suggestions($content) {
        // Simulate premium AI features
        return "AI Suggestion: Shorten sentences for better readability. Add H2 tags for SEO. Optimal keywords: 'WordPress', 'plugin'.";
    }

    public function handle_upgrade() {
        check_ajax_referer('aico_nonce', 'nonce');

        // Simulate Stripe/PayPal integration (in real: use API)
        // For demo: activate pro on 'payment'
        if (isset($_POST['payment_token'])) {
            update_option('aico_pro_active', true);
            wp_send_json_success('Pro activated!');
        } else {
            wp_send_json_error('Payment required.');
        }
    }
}

AIContentOptimizer::get_instance();

// Inline admin page template (self-contained)
function aico_admin_page_template() {
    ob_start(); ?>
<div class="wrap">
    <h1>AI Content Optimizer Pro</h1>
    <div id="aico-results"></div>
    <textarea id="aico-content" placeholder="Paste your content here..." rows="10" cols="80"></textarea>
    <button id="aico-analyze" class="button button-primary">Analyze Content</button>
    <?php if (!get_option('aico_pro_active', false)): ?>
    <div id="aico-upgrade">
        <p><strong>Upgrade to Pro:</strong> $4.99/month for AI rewriting, unlimited analyses, advanced SEO.</p>
        <button id="aico-upgrade-btn" class="button button-hero">Upgrade Now</button>
    </div>
    <?php endif; ?>
</div>
<?php
    return ob_get_clean();
}

// Hook admin page content
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
        echo aico_admin_page_template();
        exit;
    }
});

// Frontend button shortcode
add_shortcode('aico_button', function() {
    return '<button class="aico-frontend-btn button">Optimize This Page</button><div id="aico-frontend-results"></div>';
});

// Note: Create empty assets/ folders and JS files in real deployment.
// assets/admin.js: AJAX calls to analyze_content and upgrade.
// assets/frontend.js: Frontend optimization trigger.
?>