/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO using AI-powered suggestions. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            $this->check_premium_status();
        }
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer Pro',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );

        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'content_meta_box'),
            'post',
            'side',
            'high'
        );
    }

    public function enqueue_scripts() {
        // Front-end styles if needed
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'settings_page_ai-content-optimizer' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'ai-optimizer.css', array(), '1.0.0');
        }
    }

    public function content_meta_box() {
        $post_id = get_the_ID();
        if (!$post_id) return;
        $content = get_post_field('post_content', $post_id);
        $analysis = get_post_meta($post_id, '_ai_optimizer_analysis', true);
        $is_premium = $this->is_premium();
        ?>
        <div id="ai-optimizer-box">
            <p><strong>SEO Score:</strong> <span id="seo-score"><?php echo esc_html($analysis['score'] ?? 'N/A'); ?>/100</span></p>
            <button type="button" id="analyze-content" class="button <?php echo $is_premium ? '' : 'free-only'; ?>"><?php echo $is_premium ? 'Re-Analyze (Unlimited)' : 'Analyze (3/day free)'; ?></button>
            <?php if (!$is_premium) : ?>
                <p class="description">Upgrade to premium for unlimited analyses and auto-optimization!</p>
                <a href="<?php echo $this->get_premium_url(); ?>" class="button button-primary" target="_blank">Go Premium</a>
            <?php endif; }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_submit'])) {
            update_option('ai_optimizer_license', sanitize_text_field($_POST['license_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $license = get_option('ai_optimizer_license');
        $is_premium = $this->is_premium();
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>License Key</th>
                        <td>
                            <input type="text" name="license_key" value="<?php echo esc_attr($license); ?>" class="regular-text" />
                            <p class="description">Enter your premium license key for unlimited features.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); }

    private function is_premium() {
        $license = get_option('ai_optimizer_license');
        return !empty($license) && hash('sha256', $license) === 'premium_verified_hash'; // Demo verification
    }

    private function check_premium_status() {
        if (!$this->is_premium()) {
            set_transient('ai_optimizer_free_uses', 3, DAY_IN_SECONDS);
        }
    }

    public function analyze_content($content) {
        $is_premium = $this->is_premium();
        if (!$is_premium) {
            $uses = get_transient('ai_optimizer_free_uses');
            if ($uses <= 0) {
                return array('error' => 'Free limit reached. Upgrade to premium!');
            }
            set_transient('ai_optimizer_free_uses', $uses - 1, DAY_IN_SECONDS);
        }

        // Mock AI analysis (in real version, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $has_keywords = preg_match('/(seo|content|wordpress)/i', $content);
        $score = min(100, 50 + ($word_count / 10) + ($has_keywords * 20));

        return array(
            'score' => (int)$score,
            'suggestions' => array(
                'Add more keywords like "WordPress SEO"',
                'Improve readability: Aim for 300+ words',
                $is_premium ? 'Auto-optimize available' : 'Upgrade for auto-fix'
            )
        );
    }

    private function get_premium_url() {
        return 'https://example.com/premium-upgrade';
    }

    public function activate() {
        // Activation logic
    }

    public function deactivate() {
        // Deactivation logic
    }
}

AIContentOptimizerPro::get_instance();

// AJAX handler
add_action('wp_ajax_ai_analyze_content', 'ai_handle_analyze');
function ai_handle_analyze() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);
    $optimizer = AIContentOptimizerPro::get_instance();
    $result = $optimizer->analyze_content($content);
    update_post_meta($post_id, '_ai_optimizer_analysis', $result);
    wp_send_json_success($result);
}

// Mock JS/CSS - in real plugin, create separate files
add_action('admin_head', function() {
    echo '<style>
        #ai-optimizer-box { padding: 10px; background: #f9f9f9; border: 1px solid #ddd; }
        .free-only { opacity: 0.7; }
    </style>';
    echo '<script>
        jQuery(document).ready(function($) {
            $("#analyze-content").click(function() {
                $.post(ajaxurl, {
                    action: "ai_analyze_content",
                    post_id: $("#post_ID").value,
                    nonce: ai_optimizer.nonce
                }, function(response) {
                    if (response.success) {
                        $("#seo-score").text(response.data.score);
                        alert("Analysis: Score " + response.data.score + "\nSuggestions: " + response.data.suggestions.join("\n"));
                    } else {
                        alert(response.data);
                    }
                });
            });
        });
    </script>';
    wp_localize_script('ai-optimizer-js', 'ai_optimizer', array('nonce' => wp_create_nonce('ai_optimizer_nonce')));
});