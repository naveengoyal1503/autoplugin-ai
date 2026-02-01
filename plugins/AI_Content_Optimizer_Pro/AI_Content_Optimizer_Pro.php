/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = $this->analyze_readability($content);
        $density = $this->analyze_keyword_density($content, sanitize_text_field(get_post_meta($post->ID, '_ai_keyword', true)));
        echo '<p><strong>Readability Score:</strong> ' . esc_html($score) . '%</p>';
        echo '<p><strong>Keyword Density:</strong> ' . esc_html($density) . '%</p>';
        if (false && $this->is_premium()) { // Simulate premium
            echo '<p><strong>AI Suggestions:</strong> Premium feature unlocked!</p>';
        } else {
            echo '<p><em>Upgrade to Pro for AI optimization suggestions and auto-fixes.</em></p>';
            echo '<a href="https://example.com/premium" class="button button-primary" target="_blank">Go Pro</a>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['_ai_keyword'])) {
            update_post_meta($post_id, '_ai_keyword', sanitize_text_field($_POST['_ai_keyword']));
        }
    }

    private function analyze_readability($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = preg_match_all('/[.!?]+/', $content);
        if ($sentences == 0) return 0;
        $syl = $this->count_syllables(strip_tags($content));
        $asl = $words / $sentences;
        $asw = $syl / $words;
        $flesch = 206.835 - 1.015 * $asl - 84.6 * $asw;
        return max(0, min(100, round(206.835 - $flesch)));
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $vowels = '[aeiouy]';
        $syllables = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $matches = preg_match_all('/(' . $vowels . '+)/', $word, $m);
                $syllables += $matches ? count($m) : 1;
            }
        }
        return $syllables;
    }

    private function analyze_keyword_density($content, $keyword = '') {
        if (empty($keyword)) return 0;
        $words = str_word_count(strip_tags($content));
        $count = 0;
        $content_lower = strtolower(strip_tags($content));
        $count = substr_count($content_lower, strtolower($keyword));
        return $words > 0 ? round(($count * 100 / $words), 2) : 0;
    }

    public function is_premium() {
        return false; // Check license in premium version
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_init() {
        register_setting('ai_content_optimizer', 'ai_content_optimizer_options');
        add_settings_section(
            'ai_section',
            'Settings',
            null,
            'ai-content-optimizer'
        );
        add_settings_field(
            'api_key',
            'Premium API Key',
            array($this, 'api_key_render'),
            'ai-content-optimizer',
            'ai_section'
        );
    }

    public function api_key_render() {
        $options = get_option('ai_content_optimizer_options');
        echo '<input type="text" name="ai_content_optimizer_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" />';
        echo '<p class="description">Enter your premium API key for AI features.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_content_optimizer');
                do_settings_sections('ai-content-optimizer');
                submit_button();
                ?>
            </form>
            <p>Upgrade to Pro for full AI capabilities: <a href="https://example.com/premium">Get Premium</a></p>
        </div>
        <?php
    }

    public function add_action_links($links) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Settings</a>';
        return $links;
    }
}

AIContentOptimizer::get_instance();