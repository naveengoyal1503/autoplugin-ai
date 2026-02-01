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
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent direct access
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
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (!is_admin()) return;
        wp_register_style('aco-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_register_script('aco-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_style('aco-style');
        wp_enqueue_script('aco-script');
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-results"><button id="aco-analyze" class="button button-primary">Analyze Content</button><div id="aco-output"></div></div>';
        echo '<p><strong>Premium:</strong> Unlock bulk optimization and advanced AI suggestions for $9.99/mo. <a href="#" id="aco-upgrade">Upgrade Now</a></p>';
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $keywords = $this->extract_keywords($content);
        $premium = $this->is_premium();

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'keywords' => array_slice($keywords, 0, 5),
            'suggestions' => $premium ? $this->generate_ai_suggestions($content) : 'Upgrade to premium for AI-powered suggestions!',
            'is_premium' => $premium
        );
        wp_send_json_success($results);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/', $text);
        $sentence_count = max(1, $sentence_count);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - 1.015 * $asl - 84.6 * $asw;
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        preg_match_all('/[aeiouy]/', $text, $matches);
        return count($matches);
    }

    private function extract_keywords($text) {
        $words = explode(' ', strtolower($text));
        $word_freq = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($word_freq);
        return array_keys($word_freq);
    }

    private function generate_ai_suggestions($content) {
        // Simulated AI suggestions (in real plugin, integrate OpenAI API)
        return array(
            'Add more subheadings for better structure.',
            'Include 2-3 internal links to boost SEO.',
            'Target primary keyword in first 100 words.'
        );
    }

    private function is_premium() {
        // Simulate premium check (use options or license key in production)
        return get_option('aco_premium_active', false);
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer Settings', 'ACO Pro', 'manage_options', 'aco-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aco_premium_key'])) {
            update_option('aco_premium_key', sanitize_text_field($_POST['aco_premium_key']));
            update_option('aco_premium_active', true);
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <p><label>Enter Premium Key:</label> <input type="text" name="aco_premium_key" placeholder="Premium subscription key"></p>
                <p><?php submit_button(); ?></p>
            </form>
            <p>Subscribe at <strong>example.com/pricing</strong> for $9.99/month.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('aco_version', '1.0.0');
    }
}

AIContentOptimizer::get_instance();

// Dummy CSS
/*
#aco-results { margin: 10px 0; }
#aco-output { margin-top: 10px; padding: 10px; background: #f9f9f9; }
*/

// Dummy JS
/*
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        var content = $('#content').val() || tinyMCE.activeEditor.getContent();
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                var r = response.data;
                var out = '<strong>Words:</strong> ' + r.word_count + '<br>' +
                          '<strong>Readability:</strong> ' + r.readability + ' (90-100 very easy)<br>' +
                          '<strong>Top Keywords:</strong> ' + r.keywords.join(', ') + '<br>' +
                          '<strong>Suggestions:</strong> ' + (Array.isArray(r.suggestions) ? r.suggestions.join('<br>') : r.suggestions);
                $('#aco-output').html(out);
            }
        });
    });
    $('#aco-upgrade').click(function(e) {
        e.preventDefault();
        alert('Redirecting to premium signup...');
    });
});
*/
?>