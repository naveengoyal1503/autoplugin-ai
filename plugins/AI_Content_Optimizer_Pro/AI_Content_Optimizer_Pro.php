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
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aico_analyze', array($this, 'ajax_analyze'));
        add_action('admin_notices', array($this, 'premium_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aico_activated', time());
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aico-admin', plugin_dir_url(__FILE__) . 'aico-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aico-admin', 'aico_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aico_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aico-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aico_meta_nonce', 'aico_meta_nonce');
        $score = get_post_meta($post->ID, '_aico_score', true);
        $recommendations = get_post_meta($post->ID, '_aico_recommendations', true);
        echo '<div id="aico-results">';
        if ($score) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($score) . '%</p>';
            if ($recommendations) {
                echo '<ul>';
                foreach ($recommendations as $rec) {
                    echo '<li>' . esc_html($rec) . '</li>';
                }
                echo '</ul>';
            }
        }
        echo '<p><button id="aico-analyze-btn" class="button button-primary">Analyze Content</button></p>';
        echo '<p><em>Premium: AI Rewrite & Keyword Suggestions</em></p>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aico_meta_nonce']) || !wp_verify_nonce($_POST['aico_meta_nonce'], 'aico_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function ajax_analyze() {
        check_ajax_referer('aico_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis: word count, sentence length, Flesch score simulation
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $avg_sentence = $word_count / max(1, count($sentences));
        $score = max(0, min(100, 100 - ($avg_sentence - 15) * 2));
        $recommendations = array();

        if ($word_count < 300) {
            $recommendations[] = 'Add more content (aim for 1000+ words for SEO).';
        }
        if ($avg_sentence > 25) {
            $recommendations[] = 'Shorten sentences for better readability.';
        }
        if ($score < 60) {
            $recommendations[] = 'Improve Flesch score with simpler words.';
        }

        update_post_meta($post_id, '_aico_score', round($score));
        update_post_meta($post_id, '_aico_recommendations', $recommendations);

        wp_send_json_success(array('score' => round($score), 'recommendations' => $recommendations));
    }

    public function premium_nag() {
        if (!current_user_can('manage_options') || get_option('aico_dismiss_nag')) {
            return;
        }
        if (get_option('aico_activated') && (time() - get_option('aico_activated')) > WEEK_IN_SECONDS) {
            echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting and premium features! <a href="https://example.com/premium" target="_blank">Upgrade now ($9.99/mo)</a></p></div>';
        }
    }
}

AIContentOptimizer::get_instance();

// Inline JS for simplicity (self-contained)
function aico_admin_js() {
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'post') { ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#aico-analyze-btn').click(function(e) {
        e.preventDefault();
        var post_id = $('#post_ID').val();
        $('#aico-results').html('<p>Analyzing...</p>');
        $.post(aico_ajax.ajax_url, {
            action: 'aico_analyze',
            nonce: aico_ajax.nonce,
            post_id: post_id
        }, function(response) {
            if (response.success) {
                var html = '<p><strong>Readability Score:</strong> ' + response.data.score + '%</p>';
                if (response.data.recommendations.length) {
                    html += '<ul>';
                    $.each(response.data.recommendations, function(i, rec) {
                        html += '<li>' + rec + '</li>';
                    });
                    html += '</ul>';
                }
                html += '<p><em>Premium: AI Rewrite & Keyword Suggestions</em></p>';
                $('#aico-results').html(html);
            }
        });
    });
});
</script>
    <?php }
}
add_action('admin_footer-post.php', 'aico_admin_js');
add_action('admin_footer-post-new.php', 'aico_admin_js');
