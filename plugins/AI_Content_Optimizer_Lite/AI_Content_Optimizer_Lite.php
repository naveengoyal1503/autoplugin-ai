/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered SEO content analysis and optimization for WordPress posts. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        $free_uses = get_option('aco_free_uses', 5);
        $api_key = get_option('aco_premium_api_key', '');
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Lite', 'ai-content-optimizer'); ?></h1>
            <p><?php printf(__('Free uses left this month: %d. <a href="#" id="upgrade-premium">Upgrade to Premium for unlimited access!</a>', 'ai-content-optimizer'), $free_uses); ?></p>
            <?php if ($api_key) : ?>
                <p><?php _e('Premium API Key set. Unlimited access enabled.', 'ai-content-optimizer'); ?></p>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('aco_settings');
                do_settings_sections('aco_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_content'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_content($post) {
        wp_nonce_field('aco_analyze_nonce', 'aco_nonce');
        $post_id = $post->ID;
        $content = get_post_field('post_content', $post_id);
        $free_uses = get_option('aco_free_uses', 5);
        $api_key = get_option('aco_premium_api_key', '');
        $is_premium = !empty($api_key);
        ?>
        <div id="aco-analyzer">
            <p><strong><?php _e('Free analyses left:', 'ai-content-optimizer'); ?> <span id="aco-uses-left"><?php echo $free_uses; ?></span></strong></p>
            <?php if (!$is_premium && $free_uses <= 0) : ?>
                <p><?php _e('Upgrade to premium for unlimited use!', 'ai-content-optimizer'); ?> <a href="#" id="aco-upgrade-btn" class="button button-primary">Upgrade Now</a></p>
            <?php else : ?>
                <button id="aco-analyze-btn" class="button button-secondary"><?php _e('Analyze Content', 'ai-content-optimizer'); ?></button>
                <div id="aco-results"></div>
            <?php endif; ?>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#aco-upgrade-btn').click(function(e) {
                    e.preventDefault();
                    alert('Upgrade at https://example.com/premium');
                });
            });
        </script>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_ajax_nonce'),
            'premium_url' => 'https://example.com/premium'
        ));
    }

    public function analyze_content() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = sanitize_textarea_field($_POST['content']);

        $free_uses = get_option('aco_free_uses', 5);
        $api_key = get_option('aco_premium_api_key');
        $is_premium = !empty($api_key);

        if (!$is_premium && $free_uses <= 0) {
            wp_send_json_error(__('Free uses exhausted. Upgrade to premium.', 'ai-content-optimizer'));
        }

        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
        $analysis = $this->mock_ai_analysis($content);

        if (!$is_premium) {
            $free_uses--;
            update_option('aco_free_uses', $free_uses);
        }

        wp_send_json_success($analysis);
    }

    private function mock_ai_analysis($content) {
        $word_count = str_word_count($content);
        $readability = rand(60, 90);
        $seo_score = rand(70, 95);
        $suggestions = array(
            'Add more keywords like "' . $this->extract_keywords($content) ?? 'your main topic' . '"',
            'Improve readability score (' . $readability . '/100) by shortening sentences.',
            'SEO Score: ' . $seo_score . '/100 - Add meta description and alt texts.'
        );
        return array(
            'score' => $seo_score,
            'readability' => $readability,
            'word_count' => $word_count,
            'suggestions' => $suggestions
        );
    }

    private function extract_keywords($content) {
        // Mock keyword extraction
        return array('WordPress', 'SEO', 'content');
    }

    public function activate() {
        add_option('aco_free_uses', 5);
    }

    public function deactivate() {
        // Reset monthly uses on deactivation? Optional.
    }
}

AIContentOptimizerLite::get_instance();

// Freemius integration placeholder for premium (in full version)
// require_once dirname(__FILE__) . '/freemius/start.php';

// Note: For production, add real AI API integration (e.g., OpenAI), CSS/JS files, and proper Freemius setup for payments[1][2]
?>