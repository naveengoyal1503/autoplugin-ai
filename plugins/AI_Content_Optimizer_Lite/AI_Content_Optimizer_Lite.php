/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better readability and SEO. Freemium version.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function add_admin_menu() {
        add_posts_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>AI Content Optimizer Lite</h1><p>Analyze your post content below.</p>';
        echo '<textarea id="content-input" rows="10" cols="80" placeholder="Paste your content here..."></textarea>';
        echo '<button id="analyze-btn" class="button button-primary">Analyze (Free: 3/day)</button>';
        echo '<div id="results"></div>';
        echo '<div class="premium-upsell"><p><strong>Upgrade to Pro:</strong> Unlimited scans, AI rewrite suggestions, SEO keywords. <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div></div>';
    }

    public function analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);
        $user_id = get_current_user_id();
        $today = date('Y-m-d');
        $trans_key = 'ai_optimizer_scans_' . $user_id . '_' . $today;
        $scans = get_user_meta($user_id, $trans_key, true) ?: 0;

        if ($scans >= 3) {
            wp_send_json_error('Free limit reached (3/day). Upgrade to premium for unlimited!');
        }

        // Simulate AI analysis (basic heuristics for lite version)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * (1 / $word_count * 100), 2) : 0;
        $seo_score = min(100, round(($word_count / 500) * 30 + (substr_count(strtolower($content), 'the') / $word_count * 100) * 20 + 50));

        update_user_meta($user_id, $trans_key, $scans + 1);

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'tips' => $this->generate_tips($readability, $seo_score)
        );

        wp_send_json_success($results);
    }

    private function generate_tips($readability, $seo_score) {
        $tips = array();
        if ($readability < 60) $tips[] = 'Use shorter sentences for better readability.';
        if ($seo_score < 70) $tips[] = 'Add more relevant keywords and headings.';
        $tips[] = 'Pro version offers AI-powered rewrites and keyword research.';
        return $tips;
    }

    public function activate() {
        // Reset daily counters on activation
    }
}

new AIContentOptimizerLite();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#analyze-btn').click(function() {
                var content = $('#content-input').val();
                if (!content) return alert('Please enter content');

                $.post(ai_optimizer.ajax_url, {
                    action: 'analyze_content',
                    content: content,
                    nonce: ai_optimizer.nonce
                }, function(response) {
                    if (response.success) {
                        var res = response.data;
                        var html = '<h3>Results:</h3><p><strong>Words:</strong> ' + res.word_count + '</p>' +
                                   '<p><strong>Readability (Flesch):</strong> ' + res.readability + '</p>' +
                                   '<p><strong>SEO Score:</strong> ' + res.seo_score + '%</p>' +
                                   '<h4>Tips:</h4><ul>';
                        res.tips.forEach(function(tip) {
                            html += '<li>' + tip + '</li>';
                        });
                        html += '</ul>';
                        $('#results').html(html);
                    } else {
                        alert(response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'ai_optimizer_inline_js');