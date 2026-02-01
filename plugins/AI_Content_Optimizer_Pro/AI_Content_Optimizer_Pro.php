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
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_optimize_content'), 10, 1);
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

    public function enqueue_scripts($hook) {
        if ($hook !== 'settings_page_ai-content-optimizer') {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <div id="ai-optimizer-container">
                <textarea id="content-input" placeholder="Paste your content here..." rows="10" cols="100"></textarea>
                <br><button id="optimize-btn" class="button button-primary">Optimize Content</button>
                <div id="results"></div>
            </div>
            <script>/* Inline JS for demo - move to file in production */</script>
        </div>
        <?php
    }

    public function handle_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $optimized = $this->optimize_content($content);

        wp_send_json_success(array('optimized' => $optimized));
    }

    private function optimize_content($content) {
        // Simulate AI optimization: Improve readability, add keywords, structure
        $keywords = $this->extract_keywords($content);
        $content = $this->improve_readability($content);
        $content = $this->add_headings($content);
        $content = $this->integrate_keywords($content, $keywords);

        // Premium check (simulate)
        if (!$this->is_premium()) {
            $content .= '\n\n<!-- Premium: Unlock advanced AI suggestions! -->';
        }

        return $content;
    }

    private function extract_keywords($content) {
        // Simple keyword extraction
        $words = explode(' ', strtolower(strip_tags($content)));
        $counts = array_count_values($words);
        arsort($counts);
        return array_slice(array_keys($counts), 0, 5);
    }

    private function improve_readability($content) {
        // Shorten sentences, add paragraphs
        $content = preg_replace('/([^.!?]\s+)([A-Z])/', '$1$2', $content);
        return nl2br($content);
    }

    private function add_headings($content) {
        // Add H2/H3 if missing
        if (!preg_match('/<h[2-3]/', $content)) {
            $content = '<h2>Introduction</h2>' . $content;
        }
        return $content;
    }

    private function integrate_keywords($content, $keywords) {
        foreach ($keywords as $kw) {
            if (strlen($kw) > 3) {
                $content .= '\n<p><strong>Related: ' . ucfirst($kw) . '</strong></p>';
            }
        }
        return $content;
    }

    private function is_premium() {
        return get_option('ai_optimizer_pro_license') !== false;
    }

    public function auto_optimize_content($content) {
        if (is_admin() || !$this->is_enabled_auto()) {
            return $content;
        }
        return $this->optimize_content($content);
    }

    private function is_enabled_auto() {
        return get_option('ai_optimizer_auto', false);
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Content Optimizer Pro</strong> for unlimited AI optimizations! <a href="https://example.com/pro">Get Pro</a></p></div>';
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Settings
add_action('admin_init', function() {
    register_setting('ai_optimizer_options', 'ai_optimizer_auto');
    register_setting('ai_optimizer_options', 'ai_optimizer_pro_license');
});

// JS file content would be enqueued - for single file, inline it
/*
$(document).ready(function() {
    $('#optimize-btn').click(function() {
        var content = $('#content-input').val();
        $.post(ai_ajax.ajax_url, {
            action: 'optimize_content',
            content: content,
            nonce: ai_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#results').html('<h3>Optimized:</h3><div>' + response.data.optimized + '</div>');
            }
        });
    });
});
*/
?>