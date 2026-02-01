/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability with AI insights. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Requires at least: 5.0
 * Tested up to: 6.6
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('aco_analysis_count', 0);
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-editor-alignleft',
            80
        );
    }

    public function enqueue_scripts($hook) {
        if ('toplevel_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function admin_page() {
        $analysis_count = get_option('aco_analysis_count', 0);
        $limit = 5;
        $remaining = max(0, $limit - $analysis_count);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Lite</h1>
            <p>Free analyses left this month: <strong><?php echo $remaining; ?></strong>/<?php echo $limit; ?></p>
            <?php if ($remaining == 0) : ?>
                <div class="notice notice-warning">
                    <p>Upgrade to Pro for unlimited analyses! <a href="#" class="button button-primary aco-upgrade">Upgrade Now</a></p>
                </div>
            <?php endif; ?>
            <textarea id="aco-content" rows="10" cols="80" placeholder="Paste your post content here..."></textarea>
            <br><button id="aco-analyze" class="button button-primary" <?php echo $remaining == 0 ? 'disabled' : ''; ?>>Analyze Content</button>
            <div id="aco-results"></div>
        </div>
        <?php
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $analysis_count = get_option('aco_analysis_count', 0);
        $limit = 5;

        if ($analysis_count >= $limit) {
            wp_send_json_error('Free limit reached. Upgrade to Pro!');
        }

        // Simulate AI analysis (in Pro, integrate real API like OpenAI)
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $keyword_density = $this->mock_keyword_density($content);
        $seo_score = min(100, 50 + ($word_count / 10) + ($readability / 2) + ($keyword_density * 10));

        update_option('aco_analysis_count', $analysis_count + 1);

        wp_send_json_success(array(
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'readability_grade' => $this->get_reading_ease_grade($readability),
            'keyword_density' => round($keyword_density * 100, 2) . '%',
            'seo_score' => round($seo_score),
            'suggestions' => $this->generate_suggestions($seo_score)
        ));
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/s', $text) ?: 1;
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $count = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            $vowels = preg_match_all('/[aeiouy]/', $word);
            $count += max(1, $vowels);
        }
        return $count;
    }

    private function mock_keyword_density($content) {
        // Mock: assume 'content' as primary keyword
        $keyword = 'content';
        $kw_count = substr_count(strtolower($content), $keyword);
        $word_count = str_word_count($content);
        return $word_count > 0 ? $kw_count / $word_count : 0;
    }

    private function get_reading_ease_grade($score) {
        if ($score > 90) return 'Very Easy';
        if ($score > 80) return 'Easy';
        if ($score > 70) return 'Fairly Easy';
        if ($score > 60) return 'Standard';
        if ($score > 50) return 'Fairly Difficult';
        if ($score > 30) return 'Difficult';
        return 'Very Confusing';
    }

    private function generate_suggestions($seo_score) {
        $suggestions = array(
            'Aim for 300-1000 words per post.',
            'Use short sentences for better readability.',
            'Include main keywords naturally.'
        );
        if ($seo_score < 70) {
            $suggestions[] = 'Upgrade to Pro for AI-powered auto-optimizations!';
        }
        return $suggestions;
    }
}

AIContentOptimizerLite::get_instance();

// Freemium upsell notice
function aco_freemium_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'toplevel_page_ai-content-optimizer') return;
    echo '<div class="notice notice-info"><p>Unlock unlimited AI optimizations with <a href="' . admin_url('admin.php?page=ai-content-optimizer') . '">AI Content Optimizer Pro</a>!</p></div>';
}
add_action('admin_notices', 'aco_freemium_notice');

// Enqueue dummy assets (in real plugin, include actual files)
function aco_enqueue_assets() {
    wp_register_script('aco-admin-js', '', array(), '', true);
    wp_register_style('aco-admin-css', '');
}
add_action('admin_enqueue_scripts', 'aco_enqueue_assets');