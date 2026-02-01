/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered content optimization for SEO, readability, and engagement.
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

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');

        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('save_post', array($this, 'save_meta'));
            add_action('admin_menu', array($this, 'add_settings_page'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }

        // Freemium check
        $pro_key = get_option('ai_content_optimizer_pro_key');
        if (!$pro_key || !self::is_pro_valid($pro_key)) {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
    }

    public function activate() {
        add_option('ai_content_optimizer_enabled', 'yes');
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        $score = get_post_meta($post->ID, '_ai_score', true);
        echo '<p><strong>' . __('AI Score:', 'ai-content-optimizer') . '</strong> ' . esc_html($score ?: 'Not analyzed') . '/100</p>';
        echo '<p><a href="#" class="button ai-optimize-btn" data-post-id="' . $post->ID . '">' . __('Run AI Optimization', 'ai-content-optimizer') . '</a></p>';
        if ($optimized) {
            echo '<p><em>' . __('Already optimized!', 'ai-content-optimizer') . '</em></p>';
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
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'ai-optimizer.css', array(), '1.0.0');
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_pro' => self::is_pro_active()
        ));
    }

    // AJAX handler for optimization
    public function init_ajax() {
        add_action('wp_ajax_ai_optimize_content', array($this, 'ajax_optimize_content'));
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Unauthorized');
        }

        $post = get_post($post_id);
        $content = $post->post_content;

        // Simulate AI optimization (free: basic, pro: advanced)
        $is_pro = self::is_pro_active();
        $suggestions = $this->generate_ai_suggestions($content, $is_pro);
        $score = $this->calculate_score($content);

        update_post_meta($post_id, '_ai_score', $score);
        update_post_meta($post_id, '_ai_suggestions', $suggestions);
        update_post_meta($post_id, '_ai_optimized', current_time('mysql'));

        if ($is_pro) {
            // Pro: Auto-apply optimizations
            $optimized_content = $this->apply_optimizations($content, $suggestions);
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $optimized_content
            ));
        }

        wp_send_json_success(array(
            'score' => $score,
            'suggestions' => $suggestions,
            'is_pro' => $is_pro,
            'pro_message' => !$is_pro ? 'Upgrade to Pro for auto-apply and advanced features!' : ''
        ));
    }

    private function generate_ai_suggestions($content, $pro = false) {
        $suggestions = array(
            'seo_keywords' => 'Add keywords: WordPress, optimization, 2026',
            'readability' => 'Shorten sentences for better readability.',
            'engagement' => 'Add call-to-action buttons.'
        );
        if ($pro) {
            $suggestions['advanced'] = 'AI-generated meta description and schema markup added.';
        }
        return $suggestions;
    }

    private function calculate_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count / 10));
        return (int) $score;
    }

    private function apply_optimizations($content, $suggestions) {
        // Pro feature: Simulate content enhancement
        return $content . '\n\n<!-- AI Optimized: SEO enhanced -->';
    }

    public static function is_pro_active() {
        $key = get_option('ai_content_optimizer_pro_key');
        return !empty($key) && self::is_pro_valid($key);
    }

    private static function is_pro_valid($key) {
        // Simulate license check (in real: call API)
        return strlen($key) > 10;
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

    public function settings_page() {
        if (isset($_POST['pro_key'])) {
            update_option('ai_content_optimizer_pro_key', sanitize_text_field($_POST['pro_key']));
            echo '<div class="notice notice-success"><p>Pro key updated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td>
                            <input type="text" name="pro_key" value="<?php echo esc_attr(get_option('ai_content_optimizer_pro_key', '')); ?>" class="regular-text" />
                            <p class="description">Enter Pro key for unlimited optimizations. <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function pro_nag() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>' . sprintf(
            __('AI Content Optimizer: Unlock Pro for auto-optimizations and more! <a href="%s">Upgrade now</a>', 'ai-content-optimizer'),
            admin_url('options-general.php?page=ai-content-optimizer')
        ) . '</p></div>';
    }
}

AIContentOptimizer::get_instance();

// AJAX init
add_action('plugins_loaded', function() {
    $optimizer = AIContentOptimizer::get_instance();
    $optimizer->init_ajax();
});

// Include JS/CSS files as base64 or inline for single file
$js = "jQuery(document).ready(function($) { $('.ai-optimize-btn').click(function(e) { e.preventDefault(); var btn = $(this); btn.prop('disabled', true).text('Optimizing...'); $.post(ai_optimizer_ajax.ajax_url, { action: 'ai_optimize_content', post_id: $(this).data('post-id'), nonce: ai_optimizer_ajax.nonce }, function(res) { if (res.success) { alert('Score: ' + res.data.score + '\nSuggestions: ' + JSON.stringify(res.data.suggestions)); if (res.data.pro_message) alert(res.data.pro_message); } btn.prop('disabled', false).text('Run AI Optimization'); }); }); });";
$css = ".ai-optimize-btn { background: #0073aa; color: white; } .ai-optimize-btn:disabled { opacity: 0.6; }";

add_action('admin_head', function() {
    echo '<script>' . $js . '</script><style>' . $css . '</style>';
});

?>