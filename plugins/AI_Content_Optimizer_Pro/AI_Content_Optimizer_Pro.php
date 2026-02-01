/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upgrades.
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
    private $premium_key = '';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->premium_key = get_option('ai_content_optimizer_premium_key', '');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-admin-js', 'aiOptimizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_nonce'),
                'is_premium' => $this->is_premium()
            ));
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_meta_box_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        echo '<div id="ai-optimizer-results">';
        if ($score) {
            echo '<p><strong>SEO Score:</strong> ' . esc_html($score) . '%</p>';
            if ($suggestions) {
                echo '<ul>';
                foreach ($suggestions as $sugg) {
                    echo '<li>' . esc_html($sugg) . '</li>';
                }
                echo '</ul>';
            }
        }
        echo '<p><button id="analyze-content" class="button button-primary">Analyze Content</button></p>';
        if (!$this->is_premium()) {
            echo '<p><a href="#" id="upgrade-premium" class="button">Upgrade to Premium for AI Rewrite</a></p>';
        }
        echo '</div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_meta_box_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_meta_box_nonce'], 'ai_optimizer_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_premium_key'])) {
            update_option('ai_content_optimizer_premium_key', sanitize_text_field($_POST['ai_premium_key']));
            echo '<div class="notice notice-success"><p>Key saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="ai_premium_key" value="<?php echo esc_attr($this->premium_key); ?>" class="regular-text" placeholder="Enter your premium key" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$this->is_premium()) : ?>
            <p><strong>Go Premium:</strong> <a href="https://example.com/premium" target="_blank">Subscribe for $4.99/month</a> for AI rewriting, advanced keywords, and more!</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function is_premium() {
        return !empty($this->premium_key) && $this->validate_premium_key($this->premium_key);
    }

    private function validate_premium_key($key) {
        // Simulate validation - in real, call API
        return hash('sha256', $key) === 'valid_premium_hash_example';
    }

    public function ajax_analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count / 1000) * 20 + (substr_count($content, '</h') / 10));
        $suggestions = array();
        if ($word_count < 500) $suggestions[] = 'Add more content (aim for 1000+ words).';
        if (substr_count($content, 'href=') < 3) $suggestions[] = 'Add more internal/external links.';
        if (!$this->is_premium()) {
            $suggestions[] = 'Upgrade to premium for AI-powered keyword suggestions and rewriting.';
        }

        update_post_meta($post_id, '_ai_optimizer_score', round($score));
        update_post_meta($post_id, '_ai_optimizer_suggestions', $suggestions);

        wp_send_json_success(array('score' => round($score), 'suggestions' => $suggestions));
    }

    public function activate() {
        // Create assets dir if needed
        $upload_dir = plugin_dir_path(__FILE__) . 'assets/';
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
        // Note: Create empty JS files manually or via FTP for demo
    }
}

// AJAX handlers
add_action('wp_ajax_ai_analyze_content', array(AIContentOptimizer::get_instance(), 'ajax_analyze_content'));

AIContentOptimizer::get_instance();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!AIContentOptimizer::get_instance()->is_premium()) {
        echo '<div class="notice notice-info"><p>Unlock AI rewriting and advanced features with <a href="options-general.php?page=ai-content-optimizer">AI Content Optimizer Pro</a>!</p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Prevent direct access
if (!defined('ABSPATH')) exit;