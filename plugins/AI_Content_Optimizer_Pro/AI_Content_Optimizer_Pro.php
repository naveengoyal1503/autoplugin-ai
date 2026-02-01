/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content with AI-powered SEO suggestions, readability scores, and keyword enhancements.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        add_option('ai_optimizer_limit', 5);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Pro',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_optimizer_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_optimizer_api_key', '');
        $limit = get_option('ai_optimizer_limit', 5);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (OpenAI)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Monthly Free Limit</th>
                        <td><?php echo $limit; ?>/month (Premium: Unlimited)</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock unlimited optimizations, advanced AI suggestions, and priority support for $9.99/month. <a href="https://example.com/upgrade" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'page',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        $score = get_post_meta($post->ID, '_ai_score', true);
        echo '<p>Readability Score: <strong>' . esc_html($score ?: 'Not analyzed') . '</strong></p>';
        echo '<p>Status: <strong>' . esc_html($optimized ? 'Optimized' : 'Pending') . '</strong></p>';
        echo '<button type="button" id="ai-optimize-btn" class="button button-primary" data-post-id="' . $post->ID . '">Optimize Content</button>';
        echo '<p class="description">Free users: ' . get_option('ai_optimizer_limit', 5) . ' optimizations/month. <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade for unlimited</a>.</p>';
    }

    public function save_meta_box_data($post_id) {
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

    public function enqueue_scripts() {
        if (is_single()) {
            wp_enqueue_script('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' == $hook || 'post-new.php' == $hook || 'settings_page_ai-content-optimizer' == $hook) {
            wp_enqueue_script('ai-optimizer-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-admin', 'ai_optimizer_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_ajax_nonce'),
                'limit' => get_option('ai_optimizer_limit', 5)
            ));
        }
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_ajax_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $limit = get_option('ai_optimizer_limit', 5);
        $used = get_option('ai_optimizer_used', 0);

        if ($used >= $limit) {
            wp_send_json_error('Free limit reached. Upgrade to Pro for unlimited access.');
        }

        $post = get_post($post_id);
        $content = $post->post_content;

        // Simulate AI optimization (in real: call OpenAI API)
        $api_key = get_option('ai_optimizer_api_key');
        if (empty($api_key)) {
            wp_send_json_error('Please set your OpenAI API key in settings.');
        }

        // Mock AI response for demo (replace with real cURL/OpenAI call)
        $suggestions = $this->mock_ai_optimize($content);

        update_post_meta($post_id, '_ai_optimized', true);
        update_post_meta($post_id, '_ai_score', $suggestions['score']);
        update_post_meta($post_id, '_ai_suggestions', $suggestions['tips']);

        update_option('ai_optimizer_used', $used + 1);

        wp_send_json_success($suggestions);
    }

    private function mock_ai_optimize($content) {
        // Simulate readability score (Flesch-Kincaid like)
        $score = 70 + (rand(0, 30) - 15); // 40-100
        $tips = array(
            'Use shorter sentences.',
            'Add more subheadings.',
            'Include keywords naturally.'
        );
        return array(
            'score' => $score,
            'tips' => $tips,
            'optimized_content' => $content . '\n\n<!-- AI Optimized -->'
        );
    }
}

AIContentOptimizerPro::get_instance();

// Create assets dir placeholder (in real plugin, include files)
// mkdir(plugin_dir_path(__FILE__) . 'assets');
// Create empty JS files if needed
?>