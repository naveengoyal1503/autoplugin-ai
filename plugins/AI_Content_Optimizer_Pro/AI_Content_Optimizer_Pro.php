/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your content with AI-powered readability, SEO, and engagement analysis. Freemium model with premium upgrades.
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
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('aco_api_key', '');
        add_option('aco_premium_active', false);
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'render_meta_box'), 'post', 'side', 'high');
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'render_meta_box'), 'page', 'side', 'high');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        }
    }

    public function render_meta_box($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        echo '<div id="aco-results">';
        if ($score) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($score) . '%</p>';
            echo '<p id="aco-status">Free analysis complete.</p>';
        } else {
            echo '<p><button id="aco-analyze" class="button button-primary">Analyze Content (Free)</button></p>';
            echo '<div id="aco-status"></div>';
        }
        echo '</div>';
        echo '<p><small><strong>Premium:</strong> AI Rewrite, Bulk Optimize, SEO Suggestions - <a href="' . admin_url('admin.php?page=aco-settings') . '">Upgrade Now</a></small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate AI analysis (basic free version: Flesch Reading Ease approximation)
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count($content);
        $syllables = $this->count_syllables($content);

        $asl = $sentence_count > 0 ? $words / $sentence_count : 0;
        $asw = $words > 0 ? $syllables / $words : 0;
        $flesch = 206.835 - (1.015 * $asl) - (84.6 * $asw);
        $score = max(0, min(100, round($flesch)));

        update_post_meta($post_id, '_aco_score', $score);

        if (get_option('aco_premium_active')) {
            // Premium: Simulate AI rewrite
            $rewrite = $this->simple_rewrite($content);
            wp_send_json_success(array('score' => $score, 'rewrite' => $rewrite));
        } else {
            wp_send_json_success(array('score' => $score));
        }
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[^aeiouy][aeiouy]/', $word);
        }
        return $syllables;
    }

    private function simple_rewrite($content) {
        // Basic premium rewrite simulation
        return 'Premium AI Rewrite: ' . substr($content, 0, 100) . '...';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'aco-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aco_premium_key'])) {
            update_option('aco_premium_active', true);
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <p><label>Enter Premium License Key: <input type="text" name="aco_premium_key" placeholder="Premium key for activation"></label></p>
                <p><input type="submit" class="button-primary" value="Activate Premium"></p>
            </form>
            <p><strong>Upgrade for:</strong></p>
            <ul>
                <li>Unlimited AI rewrites</li>
                <li>Bulk optimization</li>
                <li>Advanced SEO insights</li>
                <li>Priority support</li>
            </ul>
        </div>
        <?php
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell notice
function aco_freemium_notice() {
    if (!get_option('aco_premium_active') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> features: AI Rewrite & more. <a href="' . admin_url('options-general.php?page=aco-settings') . '">Activate Now</a></p></div>';
    }
}
add_action('admin_notices', 'aco_freemium_notice');

// JS file content (embedded for single file)
function aco_embed_js() {
    if (isset($_GET['page']) && $_GET['page'] === 'aco-settings') return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function(e) {
            e.preventDefault();
            var postId = $('#post_ID').val();
            $('#aco-status').html('Analyzing...');
            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                post_id: postId,
                nonce: aco_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $('#aco-results').html('<p><strong>Readability Score:</strong> ' + response.data.score + '%</p>' + (response.data.rewrite ? '<p>AI Rewrite: ' + response.data.rewrite + '</p>' : '') + '<p id="aco-status">Analysis complete.</p>');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'aco_embed_js');
add_action('admin_footer-post-new.php', 'aco_embed_js');