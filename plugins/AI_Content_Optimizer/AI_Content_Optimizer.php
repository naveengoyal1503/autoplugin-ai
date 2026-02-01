/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered plugin that analyzes and optimizes post readability, SEO, and engagement. Free basic checks; premium for AI rewriting.
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
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_verify_premium_key', array($this, 'ajax_verify_key'));
        $this->premium_key = get_option('ai_co_premium_key', '');
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'ai-co_page_settings' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-co-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-co-admin', 'ai_co_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_co_nonce'),
            'is_premium' => $this->is_premium()
        ));
        wp_enqueue_style('ai-co-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_co_meta_box', 'ai_co_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        $analysis = get_post_meta($post->ID, 'ai_co_analysis', true);
        $is_premium = $this->is_premium();
        ?>
        <div id="ai-co-analysis">
            <p><strong>Readability Score:</strong> <span id="readability-score"><?php echo esc_html($analysis['readability'] ?? 'N/A'); ?></span></p>
            <p><strong>SEO Score:</strong> <span id="seo-score"><?php echo esc_html($analysis['seo'] ?? 'N/A'); ?></span></p>
            <p><strong>Engagement Score:</strong> <span id="engagement-score"><?php echo esc_html($analysis['engagement'] ?? 'N/A'); ?></span></p>
            <button id="analyze-content" class="button button-primary">Analyze Content</button>
            <?php if ($is_premium): ?>
                <button id="optimize-content" class="button button-secondary" style="margin-top: 5px;">AI Optimize (Premium)</button>
            <?php else: ?>
                <p style="color: orange; margin-top: 10px;"><a href="<?php echo admin_url('admin.php?page=ai-co-settings'); ?>">Upgrade to Premium for AI Rewriting</a></p>
            <?php endif; ?>
            <div id="analysis-results" style="margin-top: 10px;"></div>
        </div>
        <?php
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_co_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $action = sanitize_text_field($_POST['action_type']); // 'analyze' or 'optimize'
        $content = sanitize_textarea_field($_POST['content']);

        if ('analyze' === $action) {
            $analysis = $this->analyze_content($content);
            update_post_meta($post_id, 'ai_co_analysis', $analysis);
            wp_send_json_success($analysis);
        } elseif ('optimize' === $action && $this->is_premium()) {
            $optimized = $this->optimize_content($content);
            wp_send_json_success(array('optimized' => $optimized));
        } else {
            wp_send_json_error('Premium required for optimization');
        }
    }

    private function analyze_content($content) {
        // Simulated analysis based on simple heuristics
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        $readability = $avg_sentence_length > 20 ? 65 : 85; // Simple Flesch-like
        $headings = preg_match_all('/<h[1-6]/', $content);
        $seo = $headings > 2 ? 80 : 50;
        $engagement = $word_count > 500 ? 75 : 50;
        return array(
            'readability' => $readability,
            'seo' => $seo,
            'engagement' => $engagement
        );
    }

    private function optimize_content($content) {
        // Simulated AI optimization: improve sentences, add transitions
        $content = preg_replace('/\b(he|she|it|they)\s+was\b/', '$1 had been', $content);
        $content = preg_replace('/\b(very|really|just)\s+(\w+)/', '$2', $content, -1, $count);
        // Add premium placeholder
        if (mt_rand(1, 3) === 1) {
            $content .= '\n\n*Premium AI Optimization Applied*';
        }
        return $content;
    }

    private function is_premium() {
        $key = $this->premium_key;
        return !empty($key) && hash('sha256', $key) === 'verified_premium_key_hash'; // Simulated verification
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'ai-co-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['premium_key'])) {
            update_option('ai_co_premium_key', sanitize_text_field($_POST['premium_key']));
            echo '<div class="notice notice-success"><p>Key updated!</p></div>';
        }
        $key = get_option('ai_co_premium_key', '');
        $is_premium = $this->is_premium();
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="premium_key" value="<?php echo esc_attr($key); ?>" class="regular-text" placeholder="Enter your premium key" />
                            <p class="description">Get your premium key at <a href="https://example.com/premium" target="_blank">example.com/premium</a> for $4.99/month. Unlock AI rewriting!</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Status:</strong> <?php echo $is_premium ? 'Premium Active' : 'Free Version'; ?></p>
        </div>
        <?php
    }

    public function ajax_verify_key() {
        check_ajax_referer('ai_co_nonce', 'nonce');
        $key = sanitize_text_field($_POST['key']);
        // Simulate API call to your server
        $is_valid = hash('sha256', $key) === 'verified_premium_key_hash';
        if ($is_valid) {
            update_option('ai_co_premium_key', $key);
        }
        wp_send_json_success($is_valid);
    }
}

AIContentOptimizer::get_instance();

// Admin CSS
add_action('admin_head', function() {
    echo '<style>
    #ai-co-analysis { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
    #ai-co-analysis button { width: 100%; }
    #analysis-results { background: #f1f1f1; padding: 10px; border-radius: 4px; }
    </style>';
});

// Note: Create empty admin.js and admin.css files in plugin dir for production
// admin.js example:
/*
jQuery(document).ready(function($) {
    $('#analyze-content').click(function() {
        $.post(ai_co_ajax.ajax_url, {
            action: 'optimize_content',
            nonce: ai_co_ajax.nonce,
            post_id: $("#post_ID").value,
            content: $("#content").val(),
            action_type: 'analyze'
        }, function(res) {
            if (res.success) {
                $("#readability-score").text(res.data.readability);
                $("#seo-score").text(res.data.seo);
                $("#engagement-score").text(res.data.engagement);
            }
        });
    });
    // Similar for optimize
});
*/