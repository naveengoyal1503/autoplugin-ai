/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO using AI. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'handle_analyze_content'));
        add_action('wp_ajax_upgrade_to_premium', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_usage', 0);
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-editor-alignleft',
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_ai-content-optimizer') {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium(),
            'usage_limit' => 5,
            'premium_url' => 'https://example.com/premium-subscribe'
        ));
    }

    public function admin_page() {
        $is_premium = $this->is_premium();
        $usage = get_option('ai_content_optimizer_usage', 0);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <?php if (!$is_premium): ?>
                <div class="notice notice-warning">
                    <p><strong>Free version:</strong> Limited to <?php echo 5 - $usage; ?> analyses per week. <a href="#" id="upgrade-btn">Upgrade to Premium</a> for unlimited access!</p>
                </div>
            <?php endif; ?>
            <textarea id="content-input" rows="10" cols="80" placeholder="Paste your content here or enter Post ID"></textarea>
            <button id="analyze-btn" class="button button-primary">Analyze & Optimize</button>
            <div id="results"></div>
        </div>
        <?php
    }

    public function handle_analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium()) {
            $usage = get_option('ai_content_optimizer_usage', 0);
            if ($usage >= 5) {
                wp_send_json_error('Free limit reached. Upgrade to premium!');
            }
            update_option('ai_content_optimizer_usage', $usage + 1);
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
        $analysis = $this->mock_ai_analysis($content);

        wp_send_json_success($analysis);
    }

    private function mock_ai_analysis($content) {
        $word_count = str_word_count($content);
        $readability = rand(60, 90);
        $seo_score = rand(70, 95);
        $suggestions = array(
            "Improve readability score ($readability%): Shorten sentences.",
            "SEO Score: $seo_score%. Add keywords: " . $this->extract_keywords($content),
            "Optimized version: " . substr($content, 0, 200) . '... (Premium: Full rewrite)'
        );

        if ($this->is_premium()) {
            $suggestions[] = "Premium AI Rewrite: " . $this->mock_rewrite($content);
        }

        return array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => $suggestions
        );
    }

    private function extract_keywords($content) {
        $words = explode(' ', strtolower($content));
        $common = array('the', 'and', 'for', 'are', 'but', 'not');
        $keywords = array_diff($words, $common);
        return implode(', ', array_slice(array_unique($keywords), 0, 5));
    }

    private function mock_rewrite($content) {
        return ucwords(str_replace(' ', ' ', $content));
    }

    public function handle_upgrade() {
        // In real plugin, redirect to payment
        wp_send_json_success('Redirecting to premium subscription...');
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }
}

new AIContentOptimizer();

// Mock JS file content (in real, separate file)
/*
Add this to a separate optimizer.js file and enqueue properly:

jQuery(document).ready(function($) {
    $('#analyze-btn').click(function() {
        var content = $('#content-input').val();
        if (!content) return;

        $.post(ai_optimizer.ajax_url, {
            action: 'analyze_content',
            nonce: ai_optimizer.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                var res = response.data;
                var html = '<h3>Analysis Results:</h3>' +
                           '<p>Words: ' + res.word_count + '</p>' +
                           '<p>Readability: ' + res.readability + '%</p>' +
                           '<p>SEO Score: ' + res.seo_score + '%</p>' +
                           '<ul>';
                res.suggestions.forEach(function(s) { html += '<li>' + s + '</li>'; });
                html += '</ul>';
                $('#results').html(html);
            } else {
                alert(response.data);
            }
        });
    });

    $('#upgrade-btn').click(function() {
        $.post(ai_optimizer.ajax_url, {
            action: 'upgrade_to_premium',
            nonce: ai_optimizer.nonce
        }, function() {
            window.open(ai_optimizer.premium_url, '_blank');
        });
    });
});
*/
?>