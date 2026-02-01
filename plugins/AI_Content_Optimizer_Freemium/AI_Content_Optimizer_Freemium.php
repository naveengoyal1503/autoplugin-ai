/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Freemium version with premium upsell.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
        $this->premium_key = get_option('ai_content_optimizer_premium_key', '');
    }

    public function activate() {
        add_option('ai_content_optimizer_usage_count', 0);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
    }

    public function enqueue_admin_scripts($hook) {
        if ('settings_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_style('ai-optimizer-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0.0');
    }

    public function settings_page() {
        $usage_count = get_option('ai_content_optimizer_usage_count', 0);
        $is_premium = !empty($this->premium_key) && $this->validate_premium_key($this->premium_key);
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer', 'ai-content-optimizer'); ?></h1>
            <div id="ai-optimizer-dashboard">
                <p><strong><?php _e('Free Usage This Month:', 'ai-content-optimizer'); ?></strong> <?php echo $usage_count; ?>/5</p>
                <?php if (!$is_premium) : ?>
                    <div class="notice notice-warning">
                        <p><?php _e('Upgrade to Premium for unlimited optimizations, advanced AI suggestions, and auto-optimization!', 'ai-content-optimizer'); ?></p>
                        <a href="https://example.com/premium" class="button button-primary" target="_blank"><?php _e('Get Premium ($9/mo)', 'ai-content-optimizer'); ?></a>
                    </div>
                <?php endif; ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ai_content_optimizer_settings');
                    do_settings_sections('ai_content_optimizer_settings');
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    private function validate_premium_key($key) {
        // Simulate premium validation (in real: API call)
        return strlen($key) > 10;
    }

    public function analyze_content($post_id) {
        $usage_count = get_option('ai_content_optimizer_usage_count', 0);
        $is_premium = !empty($this->premium_key) && $this->validate_premium_key($this->premium_key);

        if (!$is_premium && $usage_count >= 5) {
            return array('error' => __('Free limit reached. Upgrade to premium!', 'ai-content-optimizer'));
        }

        $post = get_post($post_id);
        $content = $post->post_content;
        $word_count = str_word_count(strip_tags($content));
        $readability_score = $this->calculate_readability($content);
        $seo_score = min(100, ($word_count / 10) + ($readability_score * 2));

        if (!$is_premium) {
            update_option('ai_content_optimizer_usage_count', $usage_count + 1);
        }

        return array(
            'word_count' => $word_count,
            'readability' => round($readability_score, 2),
            'seo_score' => round($seo_score, 2),
            'suggestions' => $is_premium ? $this->get_ai_suggestions($content) : array('Upgrade for detailed suggestions'),
            'is_premium' => $is_premium
        );
    }

    private function calculate_readability($content) {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count(strip_tags($content));
        if ($sentence_count == 0) return 0;
        $avg_sentence_length = $words / $sentence_count;
        return max(0, 100 - ($avg_sentence_length - 15) * 3);
    }

    private function get_ai_suggestions($content) {
        // Simulated AI suggestions (premium only)
        return array(
            'Add more subheadings',
            'Include keywords naturally',
            'Shorten some sentences'
        );
    }
}

// Add meta box to post editor
add_action('add_meta_boxes', function() {
    add_meta_box('ai-content-optimizer', 'AI Content Optimizer', 'ai_content_optimizer_meta_box', 'post', 'side');
});

function ai_content_optimizer_meta_box($post) {
    wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
    echo '<div id="ai-optimizer-results"></div>';
    echo '<button id="ai-analyze-btn" class="button">' . __('Analyze Content', 'ai-content-optimizer') . '</button>';
}

// AJAX handler
add_action('wp_ajax_ai_optimize_content', function() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_die();
    }
    $optimizer = AIContentOptimizer::get_instance();
    $results = $optimizer->analyze_content($post_id);
    wp_send_json($results);
});

// Enqueue assets (create empty assets dir in plugin folder)
AIContentOptimizer::get_instance();

// Reset usage monthly
add_action('wp', function() {
    if (current_time('n') == 1 && current_time('j') == 1) {
        update_option('ai_content_optimizer_usage_count', 0);
    }
});