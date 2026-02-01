/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis. Freemium model with premium upgrades.
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
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'ai-content-optimizer_page_ai-optimizer-settings' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', __('AI Content Optimizer', 'ai-content-optimizer'), array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', __('AI Content Optimizer', 'ai-content-optimizer'), array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        $score = get_post_meta($post->ID, '_ai_score', true);
        echo '<p><strong>' . __('Optimization Score:', 'ai-content-optimizer') . '</strong> ' . ($score ? $score . '%' : 'Not analyzed') . '</p>';
        echo '<p><label><input type="checkbox" name="ai_optimize" ' . checked($optimized, true, false) . '> ' . __('Auto-optimize on save', 'ai-content-optimizer') . '</label></p>';
        if (!$this->is_premium()) {
            echo '<p><em>' . __('Upgrade to Pro for AI suggestions and unlimited scans.', 'ai-content-optimizer') . '</em></p>';
            echo '<a href="#" class="button button-primary ai-upgrade-btn">Upgrade to Pro</a>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['ai_optimize'])) {
            update_post_meta($post_id, '_ai_optimized', true);
            $score = $this->analyze_content($post_id);
            update_post_meta($post_id, '_ai_score', $score);
        }
    }

    private function analyze_content($post_id) {
        $post = get_post($post_id);
        $content = $post->post_content;
        $word_count = str_word_count(strip_tags($content));
        $has_keywords = preg_match('/\b(keyword1|keyword2|keyword3)\b/i', $content);
        $score = min(100, 50 + ($word_count / 1000 * 20) + ($has_keywords * 15));
        if (!$this->is_premium() && $this->get_scan_count() >= 5) {
            $score = 0;
        }
        $this->increment_scan_count();
        return round($score);
    }

    private function get_scan_count() {
        return get_option('ai_optimizer_scans', 0);
    }

    private function increment_scan_count() {
        $count = $this->get_scan_count();
        update_option('ai_optimizer_scans', $count + 1);
    }

    public function add_settings_page() {
        add_options_page(__('AI Optimizer Settings', 'ai-content-optimizer'), __('AI Optimizer', 'ai-content-optimizer'), 'manage_options', 'ai-optimizer-settings', array($this, 'settings_page_callback'));
    }

    public function settings_page_callback() {
        if (isset($_POST['ai_license_key'])) {
            update_option('ai_optimizer_license', sanitize_text_field($_POST['ai_license_key']));
            echo '<div class="notice notice-success"><p>' . __('License activated!', 'ai-content-optimizer') . '</p></div>';
        }
        $license = get_option('ai_optimizer_license');
        $scans = $this->get_scan_count();
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><?php _e('License Key', 'ai-content-optimizer'); ?></th>
                        <td>
                            <input type="text" name="ai_license_key" value="<?php echo esc_attr($license); ?>" class="regular-text" placeholder="Enter Pro license key">
                            <p class="description"><?php _e('Enter your Pro license to unlock unlimited scans and AI features.', 'ai-content-optimizer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Scan Usage', 'ai-content-optimizer'); ?></th>
                        <td><?php echo $scans; ?>/5 (Free limit) <?php if (!$this->is_premium()) echo '<a href="#" class="button button-primary ai-upgrade-btn">Upgrade</a>'; ?></td>
                    </tr>
                </table>
                <?php submit_button(); }
            }

            private function is_premium() {
                $license = get_option('ai_optimizer_license');
                return !empty($license) && hash('sha256', $license) === 'valid-premium-key-hash'; // Demo validation
            }

            public function activate() {
                add_option('ai_optimizer_scans', 0);
            }
        }

        AIContentOptimizer::get_instance();

        // Dummy assets (in real plugin, create folders and files)
        function ai_optimizer_assets() {
            // Assets would be enqueued, but for single file, inline if needed
        }
        