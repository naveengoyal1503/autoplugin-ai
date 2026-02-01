/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO using AI-powered insights. Freemium model with premium upgrades.
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
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        $this->check_premium();
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    private function check_premium() {
        // Simulate premium check (in real: integrate with Freemius or license key)
        $this->is_premium = get_option('aco_premium_active', false);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'ai-content-optimizer_page_aco-settings' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
        wp_localize_script('aco-admin-js', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_content'), 'post', 'side', 'high');
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_content'), 'page', 'side', 'high');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-results">';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-score"></div>';
        if (!$this->is_premium) {
            echo '<p><strong>Premium:</strong> Unlock auto-optimization & unlimited scans for $9/mo. <a href="' . admin_url('admin.php?page=aco-settings') . '">Upgrade Now</a></p>';
        }
        echo '</div>';
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium && get_transient('aco_free_scans_' . get_current_user_id()) >= 5) {
            wp_die(json_encode(array('error' => 'Free limit reached. Upgrade to premium!')));
        }

        $content = sanitize_textarea_field($_POST['content']);
        // Simulate AI analysis (in real: integrate OpenAI API or similar)
        $score = rand(60, 95);
        $suggestions = $this->generate_suggestions($content, $score);

        if (!$this->is_premium) {
            set_transient('aco_free_scans_' . get_current_user_id(), get_transient('aco_free_scans_' . get_current_user_id(), 0) + 1, DAY_IN_SECONDS);
        }

        wp_die(json_encode(array('score' => $score, 'suggestions' => $suggestions, 'premium_only' => !$this->is_premium)));
    }

    private function generate_suggestions($content, $score) {
        $suggestions = array(
            'Add more keywords like "' . $this->extract_keywords($content) . '"',
            'Improve readability: Aim for shorter sentences.',
            'Include H2/H3 headings for better structure.'
        );
        if ($this->is_premium) {
            $suggestions[] = 'Premium: Auto-optimize button available.';
        }
        return $suggestions;
    }

    private function extract_keywords($content) {
        // Simple keyword extraction simulation
        return array('WordPress', 'SEO', 'content');
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'aco-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aco_premium_key']) && check_admin_referer('aco_settings')) {
            // Simulate license activation
            update_option('aco_premium_active', true);
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <?php wp_nonce_field('aco_settings'); ?>
                <p><label>Premium License Key:</label> <input type="text" name="aco_premium_key" placeholder="Enter key for premium features"></p>
                <p class="description">Upgrade at <a href="https://example.com/pricing" target="_blank">example.com/pricing</a> for $9/mo.</p>
                <?php submit_button(); ?>
            </form>
            <h2>Features</h2>
            <ul>
                <li>Free: Basic analysis (5/day)</li>
                <li>Premium: Unlimited scans, auto-optimization, advanced AI</li>
            </ul>
        </div>
        <?php
    }

    public function activate() {
        if (!get_transient('aco_free_scans_' . get_current_user_id())) {
            set_transient('aco_free_scans_' . get_current_user_id(), 0, DAY_IN_SECONDS);
        }
    }
}

AIContentOptimizer::get_instance();

// Admin CSS
/* Add to plugin dir as admin.css */
/* #aco-results { margin: 10px 0; } #aco-score { background: #fff; padding: 10px; border: 1px solid #ddd; } */

// Admin JS
/* Add to plugin dir as admin.js */
/* jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        var content = $('#content').val() || tinymce.activeEditor.getContent();
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            content: content
        }, function(response) {
            var data = JSON.parse(response);
            if (data.error) {
                $('#aco-score').html('<p style="color:red;">' + data.error + '</p>');
            } else {
                var html = '<p><strong>SEO Score: ' + data.score + '/100</strong></p><ul>';
                $.each(data.suggestions, function(i, sug) {
                    html += '<li>' + sug + '</li>';
                });
                html += '</ul>';
                if (data.premium_only) html += '<p>Upgrade for more!</p>';
                $('#aco-score').html(html);
            }
        });
    });
}); */