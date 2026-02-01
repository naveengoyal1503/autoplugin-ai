/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered SEO content optimizer. Free version with basics; premium for advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
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
        if ($hook !== 'toplevel_page_ai-content-optimizer') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'isPremium' => $this->is_premium()
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_page() {
        $is_premium = $this->is_premium();
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <div id="optimizer-container">
                <?php if (!$is_premium): ?>
                <div class="notice notice-warning"><p><strong>Free Version:</strong> Basic SEO score. <a href="#" id="go-premium">Upgrade to Premium</a> for AI rewriting & bulk tools ($4.99/mo).</p></div>
                <?php endif; ?>
                <textarea id="content-input" placeholder="Paste your content here..." rows="10" cols="80"></textarea>
                <button id="optimize-btn" class="button button-primary">Optimize Content</button>
                <div id="results"></div>
            </div>
        </div>
        <?php
    }

    public function handle_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) wp_send_json_error('No content provided');

        $score = $this->calculate_seo_score($content);
        $suggestions = $this->get_basic_suggestions($content);

        if ($this->is_premium()) {
            $optimized = $this->ai_rewrite($content); // Premium feature simulation
        } else {
            $optimized = 'Premium: Upgrade for AI-optimized version.';
        }

        wp_send_json_success(array(
            'score' => $score,
            'suggestions' => $suggestions,
            'optimized' => $optimized,
            'isPremium' => $this->is_premium()
        ));
    }

    private function calculate_seo_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $has_title = preg_match('/<h1>/i', $content) || stripos($content, '# ') !== false;
        $has_keywords = preg_match('/(seo|content|optimize)/i', strtolower($content));
        $score = min(100, (int)(($word_count / 10) + ($has_title ? 20 : 0) + ($has_keywords ? 20 : 0)));
        return $score;
    }

    private function get_basic_suggestions($content) {
        $suggestions = array();
        if (str_word_count(strip_tags($content)) < 300) $suggestions[] = 'Add more content (aim for 300+ words).';
        if (!preg_match('/<h1>/i', $content) && stripos($content, '# ') === false) $suggestions[] = 'Include an H1 title.';
        return $suggestions;
    }

    private function ai_rewrite($content) {
        // Simulated AI rewrite for demo (in real: integrate OpenAI API)
        return preg_replace('/\b(word)\b/', 'optimized $1', $content) . '<p>Premium AI rewrite applied!</p>';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function activate() {
        add_option(self::PREMIUM_KEY, 'free');
    }
}

new AIContentOptimizer();

// Dummy JS/CSS files would be created separately, but for single-file, inline them
/* Inline JS */
function ai_optimizer_inline_js() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#optimize-btn').click(function() {
                var content = $('#content-input').val();
                if (!content) return alert('Enter content');
                $('#optimize-btn').prop('disabled', true).text('Optimizing...');
                $.post(aiOptimizer.ajaxurl, {
                    action: 'optimize_content',
                    nonce: aiOptimizer.nonce,
                    content: content
                }, function(res) {
                    if (res.success) {
                        var html = '<h3>SEO Score: ' + res.data.score + '/100</h3>';
                        html += '<h4>Suggestions:</h4><ul>';
                        res.data.suggestions.forEach(function(s) { html += '<li>' + s + '</li>'; });
                        html += '</ul><h4>Optimized:</h4><div>' + res.data.optimized + '</div>';
                        if (!res.data.isPremium) {
                            html += '<p><a href="#" id="go-premium">Upgrade Now</a></p>';
                        }
                        $('#results').html(html);
                    } else {
                        alert(res.data);
                    }
                    $('#optimize-btn').prop('disabled', false).text('Optimize Content');
                });
            });
            $('#go-premium').click(function(e) {
                e.preventDefault();
                alert('Redirect to premium checkout (integrate Stripe/PayPal).');
            });
        });
        <?php
    }
}
add_action('admin_footer', 'ai_optimizer_inline_js');

/* Inline CSS */
function ai_optimizer_inline_css() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
        ?>
        <style>
        #content-input { width: 100%; max-width: 800px; }
        #results { margin-top: 20px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; }
        #results h3 { color: green; }
        </style>
        <?php
    }
}
add_action('admin_head', 'ai_optimizer_inline_css');