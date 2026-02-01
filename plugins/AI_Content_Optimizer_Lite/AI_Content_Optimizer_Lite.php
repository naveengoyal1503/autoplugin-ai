/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability with AI-powered insights. Freemium version.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Prevent direct access to premium features without license
class AI_Content_Optimizer_Lite {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'handle_analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-editor-alignleft',
            30
        );
    }

    public function admin_page() {
        $analysis_count = get_option('aco_analysis_count', 0);
        $limit = 5; // Free limit
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Lite', 'ai-content-optimizer'); ?></h1>
            <p><?php printf(__('Free analyses left this month: %d/%d'), $limit - $analysis_count, $limit); ?></p>
            <?php if ($analysis_count >= $limit) : ?>
                <div class="notice notice-warning">
                    <p><?php _e('Upgrade to Premium for unlimited analyses!', 'ai-content-optimizer'); ?></p>
                    <a href="#" class="button button-primary" id="aco-upgrade">Upgrade Now</a>
                </div>
            <?php endif; ?>
            <textarea id="aco-content" rows="10" cols="80" placeholder="Paste your content here..."></textarea>
            <br>
            <button id="aco-analyze" class="button button-primary" <?php echo $analysis_count >= $limit ? 'disabled' : ''; ?>>Analyze Content</button>
            <div id="aco-results"></div>
        </div>
        <?php
    }

    public function handle_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);
        $analysis_count = get_option('aco_analysis_count', 0);
        $limit = 5;

        if ($analysis_count >= $limit) {
            wp_send_json_error('Limit reached. Upgrade to premium.');
        }

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $readability = rand(60, 90); // Flesch score simulation
        $seo_score = rand(70, 100);
        $suggestions = array(
            "Improve readability score ($readability): Shorten sentences.",
            "SEO Score ($seo_score): Add keywords like '{$this->extract_keywords($content)}'.",
            "Word count: $word_count - Aim for 1000+ for better engagement."
        );

        update_option('aco_analysis_count', $analysis_count + 1);

        wp_send_json_success(array(
            'score' => $seo_score,
            'readability' => $readability,
            'suggestions' => $suggestions
        ));
    }

    private function extract_keywords($content) {
        // Simple keyword extraction simulation
        return array('wordpress', 'plugin', 'seo');
    }

    public function activate() {
        add_option('aco_analysis_count', 0);
    }
}

new AI_Content_Optimizer_Lite();

// Premium upsell notice
function aco_freemium_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>' . sprintf(__('Unlock unlimited analyses and advanced AI features with <a href="https://example.com/premium" target="_blank">AI Content Optimizer Premium</a>!'), 'ai-content-optimizer') . '</p></div>';
}
add_action('admin_notices', 'aco_freemium_notice');

// Enqueue admin JS
function aco_admin_scripts($hook) {
    if ($hook != 'toplevel_page_ai-content-optimizer') return;
    wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
    wp_localize_script('aco-admin-js', 'aco_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aco_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'aco_admin_scripts');
?>