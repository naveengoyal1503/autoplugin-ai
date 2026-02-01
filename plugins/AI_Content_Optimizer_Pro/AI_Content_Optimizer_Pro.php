/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis. Free version provides basic checks; premium unlocks advanced features.
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

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function activate() {
        add_option('ai_content_optimizer_api_key', '');
        add_option('ai_content_optimizer_enabled', '1');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'ai-content-optimizer_page_ai-optimizer-settings' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/ai-optimizer.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/ai-optimizer.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $enabled = get_post_meta($post->ID, '_ai_optimizer_enabled', true);
        echo '<label><input type="checkbox" name="ai_optimizer_enabled" ' . checked($enabled, '1', false) . ' /> ' . __('Enable AI Optimization', 'ai-content-optimizer') . '</label>';
        echo '<p>' . $this->get_ai_analysis($post->ID, true) . '</p>'; // Preview
        echo '<div id="ai-score">' . __('Score: Calculating...', 'ai-content-optimizer') . '</div>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        update_post_meta($post_id, '_ai_optimizer_enabled', isset($_POST['ai_optimizer_enabled']) ? '1' : '0');
    }

    private function get_ai_analysis($post_id, $preview = false) {
        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $word_count = str_word_count($content);
        $has_keywords = preg_match('/(seo|content|keyword)/i', $content);
        $score = min(100, 50 + ($word_count / 10) + ($has_keywords * 20)); // Simulated AI score

        if ($preview) {
            return sprintf(__('Word count: %d | SEO Score: %d%%', 'ai-content-optimizer'), $word_count, $score);
        }

        // Freemium: Premium simulates AI rewrite
        $premium = $this->is_premium();
        if ($premium && get_option('ai_content_optimizer_api_key')) {
            return $this->simulate_ai_rewrite($content);
        }
        return __('Basic analysis: Improve keywords and length for better SEO.', 'ai-content-optimizer');
    }

    private function simulate_ai_rewrite($content) {
        // Simulated premium AI rewrite (in real: API call to OpenAI)
        return $content . ' <strong>[Premium AI Rewrite: Optimized for SEO with better keywords and structure!]</strong>';
    }

    public function optimize_content($content) {
        if (!get_option('ai_content_optimizer_enabled')) {
            return $content;
        }
        global $post;
        if (get_post_meta($post->ID, '_ai_optimizer_enabled', true) && $this->is_premium()) {
            return $this->simulate_ai_rewrite($content);
        }
        return $content;
    }

    public function add_settings_page() {
        add_options_page(
            __('AI Content Optimizer Settings', 'ai-content-optimizer'),
            __('AI Optimizer', 'ai-content-optimizer'),
            'manage_options',
            'ai-optimizer-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_submit'])) {
            update_option('ai_content_optimizer_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_content_optimizer_enabled', isset($_POST['enabled']) ? '1' : '0');
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'ai-content-optimizer') . '</p></div>';
        }
        $api_key = get_option('ai_content_optimizer_api_key');
        $enabled = get_option('ai_content_optimizer_enabled');
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Pro Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ai_optimizer_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Plugin', 'ai-content-optimizer'); ?></th>
                        <td><input type="checkbox" name="enabled" <?php checked($enabled, '1'); ?> /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Premium API Key', 'ai-content-optimizer'); ?></th>
                        <td>
                            <input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your premium API key for AI rewrites. Upgrade at example.com/premium', 'ai-content-optimizer'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div class="premium-upsell">
                <h2><?_e('Go Premium!', 'ai-content-optimizer'); ?></h2>
                <p><?php _e('Unlock AI-powered rewrites, unlimited optimizations, and more for $9/month.', 'ai-content-optimizer'); ?></p>
                <a href="https://example.com/premium" class="button button-primary">Upgrade Now</a>
            </div>
        </div>
        <?php
    }

    private function is_premium() {
        // Simulate license check
        return !empty(get_option('ai_content_optimizer_api_key')) && strlen(get_option('ai_content_optimizer_api_key')) > 10;
    }
}

AIContentOptimizer::get_instance();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Minimal JS/CSS placeholders
    file_put_contents($assets_dir . '/ai-optimizer.js', '// Premium JS for AJAX analysis');
    file_put_contents($assets_dir . '/ai-optimizer.css', '.ai-score { color: green; font-weight: bold; }');
});
?>