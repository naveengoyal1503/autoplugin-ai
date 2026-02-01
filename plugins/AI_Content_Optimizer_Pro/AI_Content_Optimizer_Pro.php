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
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('ai_optimize_score', array($this, 'optimize_score_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_optimizer_api_key', '');
        add_option('ai_optimizer_pro', false);
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_api_key'])) {
            update_option('ai_optimizer_api_key', sanitize_text_field($_POST['ai_optimizer_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_optimizer_api_key');
        $is_pro = get_option('ai_optimizer_pro', false);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro Feature)</th>
                        <td><input type="text" name="ai_optimizer_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Pro Status</th>
                        <td><?php echo $is_pro ? 'Activated' : '<a href="#" onclick="alert(\'Upgrade to Pro for full AI features!\')">Upgrade to Pro</a>'; ?></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$is_pro) : ?>
            <div class="card">
                <h2>Go Pro Today!</h2>
                <p>Unlock unlimited optimizations for $49/year.</p>
                <button onclick="alert('Pro upgrade link here')">Upgrade Now</button>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('ai-content-score', 'AI Content Score', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-score', 'AI Content Score', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_content_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_suggestions', true);
        $is_pro = get_option('ai_optimizer_pro', false);
        echo '<p><strong>Score:</strong> ' . ($score ? $score : 'Not analyzed') . '/100</p>';
        if ($suggestions) {
            echo '<p><strong>Suggestions:</strong> ' . esc_html($suggestions) . '</p>';
        }
        if (!$is_pro) {
            echo '<p><em>Upgrade to Pro for AI analysis.</em></p>';
        }
        echo '<button id="ai-analyze" class="button">Analyze Now</button>';
    }

    public function save_post_meta($post_id) {
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
        if (is_singular()) {
            wp_enqueue_script('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_script('ai-optimizer-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-admin', 'ai_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_ajax')));
        }
    }

    public function optimize_score_shortcode($atts) {
        $post_id = get_the_ID();
        $score = get_post_meta($post_id, '_ai_content_score', true);
        return '<div class="ai-score">Content Score: <strong>' . ($score ? $score : 'N/A') . '</strong>/100</div>';
    }
}

// AJAX handler for analysis
add_action('wp_ajax_ai_analyze_content', 'ai_analyze_content_handler');
function ai_analyze_content_handler() {
    check_ajax_referer('ai_optimizer_ajax', 'nonce');
    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    $score = min(100, max(0, 50 + ($word_count / 10) - rand(0, 20))); // Simulated AI score
    $suggestions = $word_count < 500 ? 'Add more content for better SEO.' : 'Great length! Improve readability.';
    update_post_meta($post_id, '_ai_content_score', $score);
    update_post_meta($post_id, '_ai_suggestions', $suggestions);
    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
}

new AIContentOptimizer();

// Pro upsell nag
add_action('admin_notices', function() {
    if (!get_option('ai_optimizer_pro', false) && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock full AI power with <strong>AI Content Optimizer Pro</strong>! <a href="options-general.php?page=ai-optimizer">Upgrade now</a></p></div>';
    }
});