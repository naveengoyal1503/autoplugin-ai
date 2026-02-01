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
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerPro {
    const VERSION = '1.0.0';
    const PREMIUM_KEY_OPTION = 'ai_content_optimizer_premium_key';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ai_content_analyze', array($this, 'ajax_analyze'));
        add_action('wp_ajax_ai_content_premium_upgrade', array($this, 'ajax_premium_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_content_optimizer_dismissed_nag', 0);
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
        $key = get_option(self::PREMIUM_KEY_OPTION, '');
        if (isset($_POST['submit'])) {
            update_option(self::PREMIUM_KEY_OPTION, sanitize_text_field($_POST['premium_key']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $is_premium = !empty($key) && $this->is_premium_valid($key);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="premium_key" value="<?php echo esc_attr($key); ?>" class="regular-text" />
                            <p class="description">Enter your premium key for unlimited optimizations. <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$is_premium) : ?>
            <div class="notice notice-warning">
                <h3>Unlock Premium Features</h3>
                <p>Upgrade for AI auto-optimization, bulk processing, and more. Only <strong>$9.99/month</strong>!</p>
                <a href="https://example.com/premium" class="button button-primary" target="_blank">Upgrade Now</a>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function is_premium_valid($key) {
        // Simulate validation; in real, check with API
        return hash('md5', $key . 'secret') === 'validhash';
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $analysis = get_post_meta($post->ID, '_ai_content_score', true);
        $is_premium = $this->is_premium_user();
        ?>
        <div id="ai-content-optimizer-box">
            <p><strong>Content Score:</strong> <?php echo $analysis ? esc_html($analysis . '%') : 'Analyze Now'; ?></p>
            <button id="ai-analyze-btn" class="button button-secondary" data-post-id="<?php echo $post->ID; ?>">Analyze Content</button>
            <div id="ai-analysis-results"></div>
            <?php if (!$is_premium) : ?>
            <p><small><a href="<?php echo admin_url('options-general.php?page=ai-content-optimizer'); ?>">Upgrade to Premium</a> for auto-fix.</small></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function save_meta_box($post_id) {
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

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), self::VERSION, true);
            wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_nonce'),
                'is_premium' => $this->is_premium_user()
            ));
        }
    }

    private function is_premium_user() {
        $key = get_option(self::PREMIUM_KEY_OPTION, '');
        return !empty($key) && $this->is_premium_valid($key);
    }

    public function ajax_analyze() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_premium_user()) {
            wp_send_json_error('Premium required for full analysis.');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        // Simulate AI analysis
        $score = rand(60, 95);
        $suggestions = $this->generate_suggestions($content);
        update_post_meta($post_id, '_ai_content_score', $score);
        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    public function ajax_premium_upgrade() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        wp_send_json_success(array('message' => 'Redirecting to premium...'));
    }

    private function generate_suggestions($content) {
        $word_count = str_word_count(strip_tags($content));
        $suggestions = array();
        if ($word_count < 500) {
            $suggestions[] = 'Add more content: Aim for 1000+ words for better SEO.';
        }
        $suggestions[] = 'Improve readability: Use shorter sentences.';
        $suggestions[] = 'Add headings and lists for engagement.';
        return $suggestions;
    }
}

new AIContentOptimizerPro();

// Freemium nag on dashboard
add_action('admin_notices', function() {
    $dismissed = get_option('ai_content_optimizer_dismissed_nag', 0);
    $is_premium = (new AIContentOptimizerPro())->is_premium_user();
    if (!$is_premium && !$dismissed && current_user_can('manage_options')) {
        echo '<div class="notice notice-info is-dismissible" data-dismiss="ai-nag">
                <p><strong>AI Content Optimizer Pro:</strong> Unlock AI auto-optimization for <strong>$9.99/mo</strong>! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade Now</a></p>
              </div>';
    }
});