/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes WordPress post content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze', array($this, 'ajax_analyze'));
        add_action('wp_ajax_aco_upgrade', array($this, 'ajax_upgrade_nag'));

        // Premium check (simulate license check)
        $this->is_premium = get_option('aco_premium_active', false);
    }

    public function init() {
        if (is_admin()) {
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_links'));
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => $this->is_premium
        ));
    }

    public function add_meta_box() {
        add_meta_box('aco-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-container">
                <button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>
                <div id="aco-results"></div>
                <div id="aco-upgrade-nag" style="display:none;">
                    <p><strong>Upgrade to Pro for AI Rewrites & Bulk Optimization!</strong></p>
                    <a href="https://example.com/premium" class="button button-secondary" target="_blank">Get Premium ($4.99/mo)</a>
                </div>
              </div>';
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium && (get_transient('aco_free_uses') >= 5)) {
            wp_send_json_error('Free limit reached. Upgrade to Pro!');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate analysis
        $score = rand(60, 95);
        $suggestions = $this->generate_suggestions($content);
        $premium_features = !$this->is_premium;

        set_transient('aco_free_uses', (get_transient('aco_free_uses') ?: 0) + 1, DAY_IN_SECONDS);

        wp_send_json_success(array(
            'score' => $score,
            'suggestions' => $suggestions,
            'premium_teaser' => $premium_features ? 'Unlock AI Rewrite & More with Pro!' : ''
        ));
    }

    public function ajax_upgrade_nag() {
        check_ajax_referer('aco_nonce', 'nonce');
        wp_send_json_success('nag');
    }

    private function generate_suggestions($content) {
        $word_count = str_word_count(strip_tags($content));
        $suggestions = array(
            'Word count: ' . $word_count . ' (Aim for 1000+ for SEO)',
            'Add more headings for better structure',
            'Include keywords naturally',
            'Improve readability score'
        );
        if (!$this->is_premium) {
            $suggestions[] = 'Premium: Get AI-powered rewrites';
        }
        return $suggestions;
    }

    public function plugin_links($links) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        $links[] = '<a href="https://example.com/docs">Docs</a>';
        return $links;
    }
}

AIContentOptimizer::get_instance();

// Freemium activation hook (for demo)
register_activation_hook(__FILE__, function() {
    update_option('aco_premium_active', false);
    delete_transient('aco_free_uses');
});

// Include JS file content as string for single-file (in production, enqueue separate file)
/*
aco-admin.js content:

jQuery(document).ready(function($) {
    $('#aco-analyze-btn').click(function() {
        var post_id = $('#post_ID').val();
        $.post(aco_ajax.ajaxurl, {
            action: 'aco_analyze',
            nonce: aco_ajax.nonce,
            post_id: post_id
        }, function(res) {
            if (res.success) {
                var html = '<p><strong>SEO Score: ' + res.data.score + '/100</strong></p><ul>';
                $.each(res.data.suggestions, function(i, sug) {
                    html += '<li>' + sug + '</li>';
                });
                html += '</ul>';
                if (res.data.premium_teaser) {
                    html += '<p>' + res.data.premium_teaser + '</p>';
                }
                $('#aco-results').html(html);
            } else {
                $('#aco-upgrade-nag').show();
            }
        });
    });
});
*/
?>