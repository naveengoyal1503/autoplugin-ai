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
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'handle_ajax_analyze'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        add_option('aco_license_key', '');
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aco_license_key'])) {
            update_option('aco_license_key', sanitize_text_field($_POST['aco_license_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $license_key = get_option('aco_license_key');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="aco_license_key" value="<?php echo esc_attr($license_key); ?>" class="regular-text" /> <p>Enter key for premium features. <a href="https://example.com/pricing" target="_blank">Get Premium</a></p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock AI rewriting, advanced keywords, and unlimited scans for $4.99/month!</p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_aco_score', true);
        $is_premium = $this->is_premium();
        ?>
        <div id="aco-analysis">
            <?php if ($score): ?>
                <p><strong>SEO Score:</strong> <?php echo esc_html($score); ?>/100</p>
            <?php endif; ?>
            <p><button id="aco-analyze-btn" class="button button-primary" data-post-id="<?php echo $post->ID; ?>">Analyze Content</button></p>
            <div id="aco-results"></div>
            <?php if (!$is_premium): ?>
                <p><em>Upgrade to Pro for AI optimizations!</em></p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#aco-analyze-btn').click(function() {
                var postId = $(this).data('post-id');
                $('#aco-results').html('<p>Analyzing...</p>');
                $.post(ajaxurl, {
                    action: 'aco_analyze_content',
                    post_id: postId,
                    content: $('#content').val(),
                    nonce: '<?php echo wp_create_nonce('aco_analyze_nonce'); ?>'
                }, function(response) {
                    $('#aco-results').html(response);
                });
            });
        });
        </script>
        <?php
    }

    public function handle_ajax_analyze() {
        check_ajax_referer('aco_analyze_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = sanitize_textarea_field($_POST['content']);

        // Basic analysis (free)
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->calculate_readability($content);
        $score = min(100, (int)($word_count / 10 + $readability * 20));
        update_post_meta($post_id, '_aco_score', $score);

        $results = "<p><strong>Score:</strong> {$score}/100</p>";
        $results .= "<p>Words: {$word_count} | Readability: " . round($readability * 100) . "%</p>";

        if ($this->is_premium()) {
            // Premium AI features (mock - in real, integrate OpenAI API)
            $ai_suggestions = $this->mock_ai_suggestions($content);
            $results .= "<p><strong>AI Suggestions:</strong> {$ai_suggestions}</p>";
        } else {
            $results .= '<p><a href="https://example.com/pricing" target="_blank">Upgrade to Pro for AI rewriting & keywords</a></p>';
        }

        wp_die($results);
    }

    private function calculate_readability($content) {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count(strip_tags($content));
        if ($words == 0) return 0;
        $avg_sentence = $words / count($sentences);
        return min(1, 1 - ($avg_sentence - 15) / 50);
    }

    private function mock_ai_suggestions($content) {
        // Mock premium feature
        return 'Add keywords: SEO, WordPress. Rewrite: Make intro more engaging.';
    }

    private function is_premium() {
        $license = get_option('aco_license_key');
        return !empty($license) && hash('sha256', $license) === 'mock_premium_hash'; // Real: validate with API
    }

    public function enqueue_scripts() {
        if (is_singular(['post', 'page'])) {
            wp_enqueue_script('aco-frontend', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'settings_page_ai-content-optimizer' === $hook) {
            wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_analyze_nonce')));
        }
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell notice
function aco_freemium_notice() {
    if (!current_user_can('manage_options') && !AIContentOptimizer::get_instance()->is_premium()) {
        echo '<div class="notice notice-info"><p>Unlock AI Content Optimizer Pro for advanced features! <a href="https://example.com/pricing">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'aco_freemium_notice');

// Create JS files placeholders (in real plugin, include actual files)
// For single-file, inline JS
add_action('admin_head-post.php', function() {
    echo '<script>jQuery(document).ready(function($){ /* Inline admin JS here */ });</script>';
});

?>