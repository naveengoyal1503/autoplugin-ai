/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content with AI-powered SEO suggestions, readability scores, and meta enhancements.
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

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->check_premium();
        }
    }

    public function activate() {
        add_option('ai_content_optimizer_version', '1.0.0');
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function add_meta_boxes() {
        add_meta_box(
            'ai_content_optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $seo_score = get_post_meta($post->ID, '_ai_seo_score', true);
        $readability_score = get_post_meta($post->ID, '_ai_readability_score', true);
        echo '<p><strong>SEO Score:</strong> ' . esc_html($seo_score ?: 'Not optimized') . '</p>';
        echo '<p><strong>Readability:</strong> ' . esc_html($readability_score ?: 'Not analyzed') . '</p>';
        if (!$this->is_premium()) {
            echo '<p><a href="#" class="ai-upgrade-btn button button-primary">Upgrade to Pro for AI Analysis</a></p>';
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
        // Simulate basic optimization (premium would use real AI API)
        if ($this->is_premium()) {
            $content = get_post_field('post_content', $post_id);
            $seo_score = $this->calculate_seo_score($content);
            $readability_score = $this->calculate_readability($content);
            update_post_meta($post_id, '_ai_seo_score', $seo_score);
            update_post_meta($post_id, '_ai_readability_score', $readability_score);
        } else {
            update_post_meta($post_id, '_ai_seo_score', 'Free: Basic');
            update_post_meta($post_id, '_ai_readability_score', 'Free: Basic');
        }
    }

    private function calculate_seo_score($content) {
        // Mock AI calculation: word count, keywords, etc.
        $word_count = str_word_count(strip_tags($content));
        $has_h1 = preg_match('/<h1[^>]*>/i', $content);
        $score = min(100, 50 + ($word_count / 10) + ($has_h1 * 20));
        return round($score);
    }

    private function calculate_readability($content) {
        // Mock Flesch-Kincaid simplicity
        $sentences = preg_split('/[.!?]+/', strip_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count(strip_tags($content));
        $score = $words > 0 ? min(100, 100 - ($words / count($sentences))) : 0;
        return round($score);
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_license_key']) && wp_verify_nonce($_POST['ai_settings_nonce'], 'ai_settings_nonce')) {
            update_option('ai_premium_license', sanitize_text_field($_POST['ai_license_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $license = get_option('ai_premium_license');
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ai_settings_nonce', 'ai_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="ai_license_key" value="<?php echo esc_attr($license); ?>" class="regular-text" />
                            <p class="description">Enter your Pro license key for advanced AI features. <a href="https://example.com/pricing" target="_blank">Get Pro</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited AI optimizations, keyword suggestions, auto-meta generation. Only $4.99/month!</p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function is_premium() {
        return !empty(get_option('ai_premium_license'));
    }

    public function check_premium() {
        if (!$this->is_premium()) {
            add_action('admin_notices', array($this, 'premium_notice'));
        }
    }

    public function premium_notice() {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for advanced features! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade now</a></p></div>';
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell logic
function ai_optimizer_ajax_optimize() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    if (!AIContentOptimizer::get_instance()->is_premium()) {
        wp_send_json_error('Premium feature required.');
    }
    // Premium AI call simulation
    wp_send_json_success(array('seo_score' => 85, 'suggestions' => 'Add more keywords.'));
}
add_action('wp_ajax_ai_optimize', 'ai_optimizer_ajax_optimize');

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = $upload_dir['basedir'] . '/ai-optimizer-assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
});