/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Freemium version with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;
    private $premium_key = '';

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->premium_key = get_option('aco_premium_key', '');
    }

    public function activate() {
        add_option('aco_analysis_count', 0);
        add_option('aco_install_date', current_time('mysql'));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function admin_menu() {
        add_submenu_page(
            'tools.php',
            'AI Content Optimizer',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('tools_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function admin_page() {
        $analysis_count = get_option('aco_analysis_count', 0);
        $limit = $this->is_premium() ? 'Unlimited' : '5 per month';
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer</h1>
            <div id="aco-status">
                <?php if (!$this->is_premium()): ?>
                    <p>Free plan: <strong><?php echo $analysis_count; ?>/5 analyses used this month.</strong> <a href="#" id="aco-upgrade">Upgrade to Premium</a></p>
                <?php else: ?>
                    <p><strong>Premium Active!</strong> Unlimited analyses.</p>
                <?php endif; ?>
            </div>
            <div id="aco-analyze-form">
                <textarea id="aco-content" placeholder="Paste your post content here..." rows="10" cols="80"></textarea>
                <br><button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>
            </div>
            <div id="aco-results"></div>
            <div id="aco-premium-promo">
                <h3>Unlock Premium Features</h3>
                <ul>
                    <li>Unlimited analyses</li>
                    <li>AI-powered rewrite suggestions</li>
                    <li>Advanced SEO keywords</li>
                    <li>Priority support</li>
                </ul>
                <p><a href="https://example.com/premium" target="_blank" class="button button-hero">Get Premium - $9/month</a></p>
            </div>
        </div>
        <?php
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!$this->is_premium() && $this->get_monthly_count() >= 5) {
            wp_send_json_error('Free limit reached. Upgrade to premium for unlimited access.');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('Content is empty.');
        }

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $seo_score = min(100, 50 + ($word_count / 1000) * 10 + ($readability / 5));
        $suggestions = $this->generate_suggestions($content);

        $this->increment_count();

        wp_send_json_success(array(
            'word_count' => $word_count,
            'readability_score' => round($readability, 2),
            'readability_grade' => $this->get_readability_grade($readability),
            'seo_score' => round($seo_score),
            'suggestions' => $suggestions,
            'is_premium' => $this->is_premium()
        ));
    }

    private function is_premium() {
        return !empty($this->premium_key) && hash('sha256', $this->premium_key) === 'premium_verified_hash';
    }

    private function get_monthly_count() {
        return get_option('aco_analysis_count', 0);
    }

    private function increment_count() {
        $count = $this->get_monthly_count() + 1;
        update_option('aco_analysis_count', $count);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/', $text);
        $sentence_count = max(1, $sentence_count);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);

        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;

        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy]/', $word) - preg_match_all('/e$/', $word);
        }
        return max(1, $syllables);
    }

    private function get_readability_grade($score) {
        if ($score > 90) return 'Very Easy';
        if ($score > 80) return 'Easy';
        if ($score > 70) return 'Fairly Easy';
        if ($score > 60) return 'Standard';
        if ($score > 50) return 'Fairly Difficult';
        if ($score > 30) return 'Difficult';
        return 'Very Difficult';
    }

    private function generate_suggestions($content) {
        $suggestions = array();
        $word_count = str_word_count($content);
        if ($word_count < 300) {
            $suggestions[] = 'Add more content: Aim for 800-1500 words for better SEO.';
        }
        $sentences = preg_match_all('/[.!?]+/', $content);
        if ($sentences < 10) {
            $suggestions[] = 'Use more varied sentence lengths for better flow.';
        }
        $suggestions[] = 'Include H2/H3 headings and bullet points for scannability.';
        $suggestions[] = 'Add internal and external links to improve authority.';
        if ($this->is_premium()) {
            $suggestions[] = 'Premium: Get AI-generated rewrite suggestions.';
        }
        return $suggestions;
    }
}

AIContentOptimizer::get_instance();

// Prevent direct access
if (!isset($content_width)) {
    $content_width = 600;
}

// Note: Create empty admin.js and admin.css files in plugin folder for full functionality
// admin.js example: jQuery basic AJAX handler
// admin.css: Basic styling
?>