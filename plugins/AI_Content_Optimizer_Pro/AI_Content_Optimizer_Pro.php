/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('wp_ajax_aco_upgrade', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => $this->is_premium()
        ));
        wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box(
            'aco-content-analysis',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-analysis-result">';
        echo '<textarea id="aco-content" style="width:100%;height:100px;display:none;">' . esc_textarea($content) . '</textarea>';
        echo '<button id="aco-analyze-btn" class="button button-primary">' . __('Analyze Content', 'ai-content-optimizer') . '</button>';
        echo '<div id="aco-output"></div>';
        if (!$this->is_premium()) {
            echo '<div class="aco-upgrade-notice">';
            echo '<p><strong>' . __('Upgrade to Pro for AI Rewriting & Keyword Suggestions!', 'ai-content-optimizer') . '</strong></p>';
            echo '<button id="aco-upgrade-btn" class="button button-secondary">' . __('Go Pro Now', 'ai-content-optimizer') . '</button>';
            echo '</div>';
        }
        echo '</div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die();
        }

        $content = sanitize_textarea_field($_POST['content']);
        $analysis = $this->basic_analysis($content);

        if ($this->is_premium()) {
            $analysis['premium'] = $this->mock_ai_features($content);
        }

        wp_send_json_success($analysis);
    }

    private function basic_analysis($content) {
        $word_count = str_word_count(strip_tags($content));
        $readability = $word_count > 300 ? 'Good' : 'Improve length';
        $headings = preg_match_all('/<h[1-6]/', $content);
        $lists = preg_match_all('/<ul|<ol/', $content);
        $images = preg_match_all('/<img/', $content);

        return array(
            'word_count' => $word_count,
            'readability' => $readability,
            'headings' => $headings ? 'Present' : 'Add headings',
            'lists' => $lists ? 'Present' : 'Add lists',
            'images' => $images ? 'Present' : 'Add images',
            'score' => min(100, (int)($word_count / 5 + $headings * 10 + $lists * 10 + $images * 10))
        );
    }

    private function mock_ai_features($content) {
        // Mock AI - in real version, integrate OpenAI API or similar
        return array(
            'keywords' => array('wordpress', 'seo', 'content'),
            'rewrite_suggestion' => substr($content, 0, 100) . '... Optimized for engagement!',
            'seo_score' => rand(70, 95)
        );
    }

    private function is_premium() {
        // Simulate license check - in pro, use real API or option
        return get_option('aco_premium_license') === 'valid';
    }

    public function handle_upgrade() {
        check_ajax_referer('aco_nonce', 'nonce');
        // Simulate upgrade - redirect to payment page
        wp_send_json_success(array('url' => 'https://example.com/checkout?plugin=aco-pro'));
    }

    public function plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $links[] = '<a href="https://example.com/pricing" target="_blank">' . __('Go Pro', 'ai-content-optimizer') . '</a>';
            $links[] = '<a href="https://example.com/docs" target="_blank">' . __('Docs', 'ai-content-optimizer') . '</a>';
        }
        return $links;
    }

    public function activate() {
        update_option('aco_version', '1.0.0');
    }
}

AIContentOptimizer::get_instance();

// Admin CSS
/*
<style>
#aco-analysis-result { padding: 10px; }
.aco-upgrade-notice { background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px; }
.aco-score { font-size: 24px; font-weight: bold; color: #0073aa; }
</style>
*/

// Note: Create admin.css with above styles and admin.js with AJAX handlers for analyze and upgrade buttons.
// For full production, add OpenAI API integration in premium version, Stripe/PayPal for payments, and license server.