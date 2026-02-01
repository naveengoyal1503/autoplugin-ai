/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis for SEO and readability. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    const PREMIUM_URL = 'https://example.com/premium-upgrade?from=plugin';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'limit_msg' => 'Free version limited to 3 analyses per day. <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade to Premium</a> for unlimited!',
            'premium_url' => self::PREMIUM_URL
        ));
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Basic SEO/readability score. Premium: Full AI suggestions & rewrites.</small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        $word_count = str_word_count($content);

        // Simulate daily limit (in production, use transients/options)
        $today = date('Y-m-d');
        $uses = get_option('aco_free_uses_' . $today, 0);
        if ($uses >= 3) {
            wp_die(json_encode(array('error' => 'Limit reached')));
        }
        update_option('aco_free_uses_' . $today, $uses + 1);

        // Basic mock AI analysis
        $readability = 75 - ($word_count / 100); // Simple formula
        $seo_score = min(95, 50 + (substr_count(strtolower($content), 'keyword') * 5)); // Mock keyword check
        $suggestions = array(
            'Use shorter sentences for better readability.',
            'Add more headings (H2/H3).',
            '<a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade for AI rewrites</a>'
        );

        wp_die(json_encode(array(
            'readability' => round($readability),
            'seo_score' => round($seo_score),
            'suggestions' => $suggestions,
            'words' => $word_count
        )));
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock unlimited AI optimizations with <a href="' . self::PREMIUM_URL . '" target="_blank">AI Content Optimizer Premium</a>! Just $4.99/mo.</p></div>';
    }
}

new AIContentOptimizerLite();

// Inline JS for simplicity (self-contained)
function aco_add_inline_script() {
    if (isset($_GET['post'])) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function() {
                var postId = $('#post_ID').val();
                $('#aco-results').html('<p>Analyzing...</p>');
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    post_id: postId
                }, function(response) {
                    var data = JSON.parse(response);
                    if (data.error) {
                        $('#aco-results').html('<p class="error">' + aco_ajax.limit_msg + '</p>');
                    } else {
                        var html = '<p><strong>Readability:</strong> ' + data.readability + '%</p>' +
                                   '<p><strong>SEO Score:</strong> ' + data.seo_score + '%</p>' +
                                   '<p><strong>Words:</strong> ' + data.words + '</p>';
                        html += '<ul>';
                        $.each(data.suggestions, function(i, sug) {
                            html += '<li>' + sug + '</li>';
                        });
                        html += '</ul>';
                        $('#aco-results').html(html);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer-post.php', 'aco_add_inline_script');
add_action('admin_footer-post-new.php', 'aco_add_inline_script');
?>