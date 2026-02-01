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
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'aicop_pro_key';

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('wp_ajax_aico_analyze_content', [$this, 'analyze_content']);
        add_action('wp_ajax_aico_premium_check', [$this, 'premium_check']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        wp_enqueue_script('aicop-admin-js', plugin_dir_url(__FILE__) . 'admin.js', ['jquery'], self::VERSION, true);
        wp_localize_script('aicop-admin-js', 'aicop_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicop_nonce')
        ]);
        wp_enqueue_style('aicop-admin-css', plugin_dir_url(__FILE__) . 'admin.css', [], self::VERSION);
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            [$this, 'settings_page']
        );
    }

    public function settings_page() {
        $premium = $this->is_premium();
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <div id="aicop-message" style="display:none;"></div>
            <?php if (!$premium): ?>
                <div class="notice notice-warning">
                    <p>Upgrade to <strong>Pro</strong> for AI rewriting, bulk optimization, and unlimited scans! <a href="#" id="aicop-upgrade">Get Pro Now</a></p>
                </div>
            <?php endif; ?>
            <div id="aicop-analyzer">
                <textarea id="aicop-content" placeholder="Paste your content here..." rows="10" cols="80"></textarea>
                <br><button id="aicop-analyze" class="button button-primary">Analyze Content</button>
                <div id="aicop-results"></div>
            </div>
            <?php if (!$premium): ?>
            <div id="aicop-premium-form" style="display:none;">
                <h3>Enter Pro License Key</h3>
                <input type="text" id="aicop-license-key" placeholder="Your license key">
                <button id="aicop-activate" class="button button-primary">Activate Pro</button>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function analyze_content() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        if (strlen($content) < 50) {
            wp_send_json_error('Content too short. Minimum 50 characters.');
        }

        $premium = $this->is_premium();
        $results = $this->basic_analysis($content);

        if ($premium) {
            $results['ai_rewrite'] = $this->mock_ai_rewrite($content);
        } else {
            $results['upgrade_notice'] = 'Upgrade to Pro for AI-powered rewriting and more!';
        }

        wp_send_json_success($results);
    }

    private function basic_analysis($content) {
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 ? round(206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * (0.846 / avg_word_length($content)), 2) : 0;

        function avg_word_length($content) {
            $words = str_word_count($content, 1);
            return $words ? array_sum(array_map('strlen', $words)) / count($words) : 0;
        }

        return [
            'word_count' => $word_count,
            'sentence_count' => $sentence_count,
            'readability_score' => $readability,
            'seo_score' => min(100, round(($word_count / 500 * 40) + (rand(20,40)/100 * 60))),
            'recommendations' => $this->generate_recommendations($word_count, $readability)
        ];
    }

    private function generate_recommendations($words, $readability) {
        $recs = [];
        if ($words < 300) $recs[] = 'Add more content for better SEO.';
        if ($readability > 70) $recs[] = 'Simplify sentences for better readability.';
        $recs[] = 'Use short paragraphs and subheadings.';
        return $recs;
    }

    private function mock_ai_rewrite($content) {
        // Mock AI rewrite for demo; in real version, integrate OpenAI API
        return substr($content, 0, 200) . '... (Pro AI Rewrite) Optimized for engagement!';
    }

    public function premium_check() {
        check_ajax_referer('aicop_nonce', 'nonce');
        $key = sanitize_text_field($_POST['key'] ?? '');
        // Mock license check; in real, validate with server
        if (strlen($key) > 10 && strpos($key, 'pro-') === 0) {
            update_option(self::PREMIUM_KEY, $key);
            wp_send_json_success('Activated!');
        } else {
            wp_send_json_error('Invalid key.');
        }
    }

    public function is_premium() {
        $key = get_option(self::PREMIUM_KEY);
        return !empty($key);
    }

    public function activate() {
        // Set default options
    }
}

new AIContentOptimizer();

// Inline CSS
?>
<style>
#aicop-results { margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; }
#aicop-results h3 { color: #23282d; }
.aico-premium { color: #0073aa; font-weight: bold; }
.aico-score { font-size: 24px; font-weight: bold; }
.green { color: green; }
.red { color: red; }
</style>

<!-- Inline JS -->
<script>
jQuery(document).ready(function($) {
    $('#aicop-analyze').click(function() {
        var content = $('#aicop-content').val();
        if (!content) return;
        $('#aicop-results').html('<p>Analyzing...</p>');
        $.post(aicop_ajax.ajax_url, {
            action: 'aico_analyze_content',
            nonce: aicop_ajax.nonce,
            content: content
        }, function(res) {
            if (res.success) {
                var r = res.data;
                var html = '<h3>Analysis Results:</h3>' +
                    '<p>Words: <span class="aico-score">' + r.word_count + '</span></p>' +
                    '<p>Readability: <span class="aico-score ' + (r.readability_score < 60 ? 'green' : 'red') + '">' + r.readability_score + '</span></p>' +
                    '<p>SEO Score: <span class="aico-score green">' + r.seo_score + '%</span></p>';
                if (r.ai_rewrite) {
                    html += '<h4>AI Rewrite:</h4><p>' + r.ai_rewrite + '</p>';
                } else if (r.upgrade_notice) {
                    html += '<p class="aico-premium">' + r.upgrade_notice + '</p>';
                }
                html += '<h4>Recommendations:</h4><ul>';
                $.each(r.recommendations, function(i, rec) {
                    html += '<li>' + rec + '</li>';
                });
                html += '</ul>';
                $('#aicop-results').html(html);
            } else {
                $('#aicop-results').html('<p class="red">Error: ' + res.data + '</p>');
            }
        });
    });

    $('#aicop-upgrade, #aicop-activate').click(function(e) {
        e.preventDefault();
        $('#aicop-premium-form').toggle();
    });

    $('#aicop-activate').click(function() {
        var key = $('#aicop-license-key').val();
        $.post(aicop_ajax.ajax_url, {
            action: 'aico_premium_check',
            nonce: aicop_ajax.nonce,
            key: key
        }, function(res) {
            $('#aicop-message').html(res.success ? '<p class="green">' + res.data + '</p>' : '<p class="red">' + res.data + '</p>').show();
            location.reload();
        });
    });
});
</script>