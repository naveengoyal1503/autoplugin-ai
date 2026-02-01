/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content optimization for SEO, readability, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 8.0
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
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');

        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('save_post', array($this, 'save_meta'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('admin_menu', array($this, 'add_settings_page'));
            add_action('admin_init', array($this, 'settings_init'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        }

        // Pro upsell notice
        add_action('admin_notices', array($this, 'pro_upsell_notice'));
    }

    public function activate() {
        add_option('ai_content_optimizer_api_key', '');
        add_option('ai_content_optimizer_enabled', '1');
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
        $enabled = get_post_meta($post->ID, '_ai_optimizer_enabled', true);
        echo '<label><input type="checkbox" name="ai_optimizer_enabled" ' . checked($enabled, '1', false) . ' /> ' . __('Optimize with AI', 'ai-content-optimizer') . '</label><br>';
        echo '<p><small>' . __('Free: Basic suggestions. <a href="' . admin_url('admin.php?page=ai-content-optimizer') . '">Pro: Auto-optimize</a>', 'ai-content-optimizer') . '</small></p>';
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        if ($suggestions) {
            echo '<div style="margin-top:10px;"><strong>Suggestions:</strong><br>' . esc_html($suggestions) . '</div>';
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

        $enabled = isset($_POST['ai_optimizer_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_ai_optimizer_enabled', $enabled);

        if ($enabled && isset($_POST['post_content'])) {
            $suggestions = $this->generate_basic_suggestions($_POST['post_content']);
            update_post_meta($post_id, '_ai_optimizer_suggestions', $suggestions);
        }
    }

    private function generate_basic_suggestions($content) {
        // Free basic heuristic-based suggestions (simulates AI)
        $suggestions = [];
        $word_count = str_word_count(strip_tags($content));
        if ($word_count < 300) {
            $suggestions[] = 'Add more content (aim for 300+ words for better SEO).';
        }
        $sentences = preg_split('/[.!?]+/', strip_tags($content));
        $avg_sentence = $word_count / max(1, count($sentences));
        if ($avg_sentence > 25) {
            $suggestions[] = 'Shorten sentences (ideal: 15-20 words).';
        }
        if (stripos($content, 'href=') === false) {
            $suggestions[] = 'Add 2-3 internal/external links.';
        }
        return implode(' ', $suggestions);
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/ai-optimizer.js', array('jquery'), '1.0.0', true);
        }
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page_callback')
        );
    }

    public function settings_init() {
        register_setting('ai_content_optimizer', 'ai_content_optimizer_api_key');
        register_setting('ai_content_optimizer', 'ai_content_optimizer_enabled');
    }

    public function settings_page_callback() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_content_optimizer'); ?>
                <?php do_settings_sections('ai_content_optimizer'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('API Key (Pro)', 'ai-content-optimizer'); ?></th>
                        <td><input type="password" name="ai_content_optimizer_api_key" value="<?php echo esc_attr(get_option('ai_content_optimizer_api_key')); ?>" class="regular-text" /> <p class="description">Enter your Pro API key for advanced AI features.</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div style="background:#fff3cd;padding:15px;margin:20px 0;border-left:4px solid #ffeaa7;">
                <h3><?php _e('Upgrade to Pro', 'ai-content-optimizer'); ?></h3>
                <p><?php _e('Unlock auto-rewriting, bulk optimization, premium AI (GPT-4o), and analytics. Only $49/year!', 'ai-content-optimizer'); ?></p>
                <a href="https://example.com/pro" class="button button-primary button-large" target="_blank"><?php _e('Get Pro Now', 'ai-content-optimizer'); ?></a>
            </div>
        </div>
        <?php
    }

    public function pro_upsell_notice() {
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        if ('edit-post' === $screen->id || 'post' === $screen->id) {
            echo '<div class="notice notice-info is-dismissible"><p>' . sprintf(__('Supercharge with <strong>AI Content Optimizer Pro</strong>: Auto-optimize entire site! %sGet it now%s', 'ai-content-optimizer'), '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">', '</a>') . '</p></div>';
        }
    }

    public function add_action_links($links) {
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">' . __('Settings', 'ai-content-optimizer') . '</a>';
        $links[] = '<a style="color:#00a32a;font-weight:bold;" href="https://example.com/pro" target="_blank">Pro</a>';
        return $links;
    }
}

AIContentOptimizer::get_instance();

// Create assets dir placeholder (in real dist, include JS)
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets', 0755, true);
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/ai-optimizer.js', '// AI Optimizer JS\njQuery(document).ready(function($) { $(".ai-optimizer-pro").on("click", function() { alert("Pro feature unlocked!"); }); });');
}

?>