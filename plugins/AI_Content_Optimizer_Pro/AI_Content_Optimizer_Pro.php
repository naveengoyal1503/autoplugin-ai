/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO using smart algorithms.
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
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_analyze_content', array($this, 'ajax_analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), self::VERSION, true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), self::VERSION);
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', __('AI Content Optimizer', 'ai-content-optimizer'), array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', __('AI Content Optimizer', 'ai-content-optimizer'), array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $analysis = get_post_meta($post->ID, '_ai_optimizer_analysis', true);
        $limit = $this->get_analysis_limit();
        echo '<div id="ai-optimizer-panel">';
        echo '<p>' . sprintf(__('Remaining analyses: %d', 'ai-content-optimizer'), $limit) . '</p>';
        echo '<button id="ai-analyze-btn" class="button button-primary">' . __('Analyze Content', 'ai-content-optimizer') . '</button>';
        if ($analysis) {
            echo '<div id="ai-analysis-result"><strong>Score:</strong> ' . esc_html($analysis['score']) . '%<br>';
            echo '<strong>Suggestions:</strong><ul>';
            foreach ($analysis['suggestions'] as $suggestion) {
                echo '<li>' . esc_html($suggestion) . '</li>';
            }
            echo '</ul></div>';
        }
        echo '<div id="ai-premium-upsell" style="display:none; margin-top:10px; padding:10px; background:#fff3cd; border:1px solid #ffeaa7;">
                <p><strong>Go Premium!</strong> Unlock unlimited analyses, AI rewrites & more for $49/year. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
             </div>';
        echo '</div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function ajax_analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->can_analyze()) {
            wp_die(json_encode(array('error' => 'Limit reached. Upgrade to premium!')));
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        $analysis = $this->perform_analysis($content);
        update_post_meta($post_id, '_ai_optimizer_analysis', $analysis);
        $this->decrement_analysis_count();
        wp_die(json_encode($analysis));
    }

    private function perform_analysis($content) {
        // Simulated AI analysis - in premium, integrate real AI API
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count / 20) + (substr_count($content, '<h') * 5));
        $suggestions = array(
            $word_count < 500 ? 'Increase word count to 500+ for better SEO.' : 'Good length!',
            substr_count($content, '<img') < 1 ? 'Add relevant images.' : 'Images detected.',
            'Use more subheadings (H2/H3).',
            'Premium: Get AI rewrite suggestions.'
        );
        return array('score' => $score, 'suggestions' => $suggestions);
    }

    private function get_analysis_limit() {
        $count = get_option('ai_optimizer_analysis_count', 0);
        $limit = $this->is_premium() ? 999 : 5;
        return $limit - $count;
    }

    private function can_analyze() {
        return $this->get_analysis_limit() > 0;
    }

    private function decrement_analysis_count() {
        if (!$this->is_premium()) {
            $count = get_option('ai_optimizer_analysis_count', 0) + 1;
            update_option('ai_optimizer_analysis_count', $count);
        }
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY, false);
    }

    public function add_settings_page() {
        add_options_page(__('AI Content Optimizer', 'ai-content-optimizer'), __('AI Optimizer', 'ai-content-optimizer'), 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['premium_key']));
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <p><label>Premium Key: <input type="text" name="premium_key" placeholder="Enter premium key"></label></p>
                <p class="description">Get your key at <a href="https://example.com/premium">example.com/premium</a> for $49/year.</p>
                <?php submit_button(); ?>
            </form>
            <p>Free analyses used: <?php echo get_option('ai_optimizer_analysis_count', 0); ?>/5 (resets monthly in full version).</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('ai_optimizer_analysis_count', 0);
    }
}

new AIContentOptimizer();

// Assets would be placed in /assets/ folder: script.js with AJAX call, style.css for styling.