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
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

const $AICOP_VERSION = '1.0.0';
const $AICOP_PLUGIN_FILE = __FILE__;
const $AICOP_FREE_URL = 'https://example.com';

define('AICOP_VERSION', $AICOP_VERSION);
define('AICOP_PLUGIN_FILE', $AICOP_PLUGIN_FILE);
define('AICOP_FREE_URL', $AICOP_FREE_URL);

// Freemius integration (simulate for demo; replace with real Freemius SDK)
function aicop_freemius() {
    static $freemius;
    if (!isset($freemius)) {
        // Simulated Freemius
        $freemius = new stdClass();
        $freemius->is_premium = false; // Default to free
        $freemius->is_plan__pro__activated = function() { return false; };
        $freemius->has_active_valid_license = function() { return false; };
        $freemius->get_url = function($path) { return $AICOP_FREE_URL . $path; };
    }
    return $freemius;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'action_links'));

        // Freemius simulated upsell notice
        if (!aicop_freemius()->is_premium()) {
            add_action('admin_notices', array($this, 'premium_notice'));
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('aicop_save_meta', 'aicop_nonce');
        $score = get_post_meta($post->ID, '_aicop_score', true);
        $suggestions = get_post_meta($post->ID, '_aicop_suggestions', true);
        $fs = aicop_freemius();
        echo '<div id="aicop-results">';
        if ($score) {
            echo '<p><strong>SEO Score:</strong> ' . esc_html($score) . '%</p>';
            if ($suggestions) {
                echo '<ul>';
                foreach ($suggestions as $sugg) {
                    echo '<li>' . esc_html($sugg) . '</li>';
                }
                echo '</ul>';
            }
        } else {
            echo '<p>Click "Analyze Content" to get started.</p>';
        }
        echo '<p><button id="aicop-analyze" class="button button-primary">Analyze Content</button></p>';
        if (!$fs->is_premium()) {
            echo '<p><a href="' . $fs->get_url('/pricing/') . '" class="button button-upgrade" target="_blank">Upgrade to Pro for AI Rewrite & More</a></p>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aicop_nonce']) || !wp_verify_nonce($_POST['aicop_nonce'], 'aicop_save_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Analysis triggered via AJAX, data saved here
    }

    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        wp_enqueue_script('aicop-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), AICOP_VERSION, true);
        wp_localize_script('aicop-admin', 'aicop_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicop_ajax'),
            'is_premium' => aicop_freemius()->is_premium() ? '1' : '0'
        ));
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1><p>Configure your API keys and preferences here. <a href="' . aicop_freemius()->get_url('/account/') . '" target="_blank">Manage License</a></p></div>';
    }

    public function action_links($links) {
        $fs = aicop_freemius();
        $links[] = '<a href="' . $fs->get_url('/changelog/') . '" target="_blank">Changelog</a>';
        if (!$fs->is_premium()) {
            $links[] = '<a href="' . $fs->get_url('/pricing/') . '" class="thickbox open-plugin-details-modal" target="_blank" style="color: #1d2327; font-weight: 400;">Upgrade</a>';
        }
        return $links;
    }

    public function premium_notice() {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI-powered rewriting, advanced keyword research, and bulk optimization</strong> with <a href="' . aicop_freemius()->get_url('/pricing/') . '" target="_blank">AI Content Optimizer Pro</a>.</p></div>';
    }
}

// AJAX handler for analysis
add_action('wp_ajax_aicop_analyze', 'aicop_ajax_analyze');
function aicop_ajax_analyze() {
    check_ajax_referer('aicop_ajax', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_die();
    }
    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);

    // Simulated analysis (free version: basic rules-based)
    $word_count = str_word_count(strip_tags($content));
    $score = min(100, max(0, 50 + ($word_count > 500 ? 20 : 0) + (strpos($content, 'H2') !== false ? 15 : 0) + (strlen($content) > 2000 ? 15 : 0)));
    $suggestions = array();
    if ($word_count < 300) $suggestions[] = 'Add more content (aim for 500+ words).';
    if (substr_count($content, '</h2>') < 2) $suggestions[] = 'Use more H2 headings.';
    if (!$suggestions) $suggestions[] = 'Great! Content looks optimized.';

    update_post_meta($post_id, '_aicop_score', $score);
    update_post_meta($post_id, '_aicop_suggestions', $suggestions);

    $fs = aicop_freemius();
    wp_send_json_success(array(
        'score' => $score,
        'suggestions' => $suggestions,
        'is_premium' => $fs->is_premium(),
        'pro_msg' => $fs->is_premium() ? '' : 'Upgrade for AI-powered suggestions and auto-rewrite.'
    ));
}

new AIContentOptimizer();

// Note: For production, integrate real Freemius SDK and OpenAI API for premium AI features.
// admin.js content (inline for single file):
/*
jQuery(document).ready(function($) {
    $('#aicop-analyze').click(function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Analyzing...');
        $.post(aicop_ajax.ajax_url, {
            action: 'aicop_analyze',
            nonce: aicop_ajax.nonce,
            post_id: $("#post_ID").value
        }, function(res) {
            if (res.success) {
                var html = '<p><strong>SEO Score:</strong> ' + res.data.score + '%</p>';
                if (res.data.suggestions.length) {
                    html += '<ul>';
                    $.each(res.data.suggestions, function(i, s) { html += '<li>' + s + '</li>'; });
                    html += '</ul>';
                }
                if (res.data.pro_msg) html += '<p>' + res.data.pro_msg + '</p>';
                $('#aicop-results').html(html);
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Re-analyze');
        });
    });
});
*/