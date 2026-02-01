/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-driven analysis.
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
    public $api_key = '';
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->is_premium = get_option('aicop_pro_license_status') === 'valid';
        $this->api_key = get_option('aicop_api_key');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicop-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'ai-content-optimizer') !== false) {
            wp_enqueue_script('aicop-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aicop-admin', 'aicop_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicop_nonce')));
        }
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
        add_meta_box('aicop-optimizer', 'Optimize with AI', array($this, 'content_optimizer_meta_box'), 'post', 'side', 'high');
        add_meta_box('aicop-optimizer', 'Optimize with AI', array($this, 'content_optimizer_meta_box'), 'page', 'side', 'high');
    }

    public function content_optimizer_meta_box() {
        $post_id = get_the_ID();
        $content = get_post_field('post_content', $post_id);
        $score = get_post_meta($post_id, 'aicop_seo_score', true) ?: 0;
        echo '<div id="aicop-score">SEO Score: <strong>' . $score . '%</strong></div>';
        echo '<textarea id="aicop-content" style="width:100%;height:100px;display:none;">' . esc_textarea($content) . '</textarea>';
        echo '<button id="aicop-analyze" class="button">Analyze Content</button>';
        echo '<div id="aicop-results"></div>';
        if (!$this->is_premium) {
            echo '<p><em>Upgrade to Pro for unlimited analyses and AI rewrites.</em></p>';
        }
    }

    public function ajax_optimize_content() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!$this->is_premium && get_transient('aicop_free_uses') >= 5) {
            wp_send_json_error('Free limit reached. Upgrade to Pro.');
        }
        $content = sanitize_textarea_field($_POST['content']);
        $analysis = $this->analyze_content($content);
        if (is_wp_error($analysis)) {
            wp_send_json_error($analysis->get_error_message());
        }
        set_transient('aicop_free_uses', get_transient('aicop_free_uses') + 1 ?: 1, DAY_IN_SECONDS);
        wp_send_json_success($analysis);
    }

    private function analyze_content($content) {
        // Simulate AI analysis (in real version, integrate OpenAI or similar API)
        $word_count = str_word_count($content);
        $score = min(100, 50 + ($word_count / 1000) * 20 + rand(0, 30));
        $suggestions = array(
            'Add more keywords: ' . $this->suggest_keywords($content),
            'Improve readability: Aim for shorter sentences.',
            'Enhance engagement: Add questions or calls to action.'
        );
        if ($this->is_premium) {
            $suggestions[] = 'Pro: AI-generated rewrite available.';
        }
        return array(
            'score' => $score,
            'suggestions' => $suggestions,
            'premium_teaser' => !$this->is_premium
        );
    }

    private function suggest_keywords($content) {
        // Dummy keyword suggestion
        return 'content marketing, SEO, WordPress';
    }

    public function settings_page() {
        if (isset($_POST['aicop_api_key'])) {
            update_option('aicop_api_key', sanitize_text_field($_POST['aicop_api_key']));
        }
        if (isset($_POST['aicop_pro_license'])) {
            // Simulate license check
            update_option('aicop_pro_license_status', 'valid');
            $this->is_premium = true;
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key</th>
                        <td><input type="text" name="aicop_api_key" value="<?php echo esc_attr($this->api_key); ?>" /></td>
                    </tr>
                    <?php if (!$this->is_premium): ?>
                    <tr>
                        <th>Upgrade to Pro</th>
                        <td><button type="submit" name="aicop_pro_license" class="button-primary">Activate Pro ($9/mo)</button></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </form>
        </div>
        <?php
    }

    public function activate() {
        add_option('aicop_free_uses', 0);
    }

    public function deactivate() {}
}

AIContentOptimizer::get_instance();

// Frontend optimization display
function aicop_display_score($content) {
    if (is_single()) {
        $score = get_post_meta(get_the_ID(), 'aicop_seo_score', true) ?: 0;
        $content .= '<div style="background:#f0f0f0;padding:10px;margin:20px 0;">SEO Score: ' . $score . '% <em>Powered by AI Content Optimizer</em></div>';
    }
    return $content;
}
add_filter('the_content', 'aicop_display_score');

// Dummy JS assets would be in /assets/ but for single file, inline if needed
