/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your content with AI-powered analysis for SEO, readability, and engagement.
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
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        $license_key = get_option('ai_optimizer_license', '');
        $is_premium = $this->is_premium($license_key);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <p><strong>Free Features:</strong> Basic readability score and keyword density check.</p>
            <p><strong>Premium Features (<?php echo $is_premium ? 'Active' : 'Upgrade Required'; ?>):</strong> AI SEO suggestions, engagement score, bulk optimize.</p>
            <?php if (!$is_premium) : ?>
            <form method="post" action="https://example.com/upgrade">
                <input type="hidden" name="from_plugin" value="1">
                <p>Enter License Key: <input type="text" name="license_key" value="<?php echo esc_attr($license_key); ?>" /></p>
                <p><a href="https://example.com/pricing" class="button button-primary" target="_blank">Upgrade to Pro ($9.99/mo)</a></p>
                <?php submit_button('Activate License'); ?>
            </form>
            <?php endif; ?>
            <h2>Optimize Current Post</h2>
            <textarea id="content-input" rows="10" cols="80" placeholder="Paste your content here..."><?php echo esc_textarea(wp_strip_all_tags(get_post_field('post_content'))); ?></textarea>
            <button id="optimize-btn" class="button button-primary">Optimize Content</button>
            <div id="results"></div>
        </div>
        <?php
    }

    public function handle_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $license_key = get_option('ai_optimizer_license', '');
        $is_premium = $this->is_premium($license_key);

        // Simulate AI analysis (in real: integrate OpenAI API)
        $word_count = str_word_count($content);
        $readability = min(100, 50 + ($word_count / 10));
        $keywords = $this->extract_keywords($content);
        $density = count($keywords) > 0 ? round((array_count_values($keywords)[end($keywords)] ?? 0) / $word_count * 100, 2) : 0;

        $results = array(
            'readability_score' => $readability,
            'keyword_density' => $density,
            'word_count' => $word_count,
            'suggestions' => $is_premium ? array(
                'SEO: Add H2 tags and meta description.',
                'Engagement: Shorten sentences under 20 words.',
                'Optimized version: ' . $this->generate_optimized($content)
            ) : array('Upgrade to Pro for AI suggestions!')
        );

        wp_send_json_success($results);
    }

    private function is_premium($key) {
        // Simulate license check
        return !empty($key) && hash('md5', $key) === 'premium';
    }

    private function extract_keywords($content) {
        // Simple keyword extraction
        preg_match_all('/\b\w{4,}\b/', strtolower($content), $matches);
        return array_slice($matches, 0, 10);
    }

    private function generate_optimized($content) {
        // Basic optimization simulation
        return preg_replace('/\s+/', ' ', trim($content));
    }

    public function activate() {
        add_option('ai_optimizer_license', '');
    }
}

new AIContentOptimizer();

// Enqueue dummy assets (inline for single file)
function ai_optimizer_assets() {
    ?>
    <style>
    #results { margin-top: 20px; padding: 10px; background: #f9f9f9; }
    .premium { color: green; font-weight: bold; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('#optimize-btn').click(function() {
            $.post(ai_optimizer_ajax.ajax_url, {
                action: 'optimize_content',
                content: $('#content-input').val(),
                nonce: ai_optimizer_ajax.nonce
            }, function(res) {
                if (res.success) {
                    let html = '<h3>Results:</h3>' +
                        '<p>Readability: ' + res.data.readability_score + '%</p>' +
                        '<p>Keyword Density: ' + res.data.keyword_density + '%</p>' +
                        '<p>Word Count: ' + res.data.word_count + '</p>';
                    if (res.data.suggestions.length > 1 || !res.data.suggestions.includes('Upgrade')) {
                        html += '<h4>AI Suggestions:</h4><ul>';
                        res.data.suggestions.forEach(function(s) { html += '<li>' + s + '</li>'; });
                        html += '</ul>';
                    } else {
                        html += '<p class="premium">' + res.data.suggestions + '</p>';
                    }
                    $('#results').html(html);
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'ai_optimizer_assets');