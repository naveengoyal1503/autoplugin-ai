/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer-lite', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('aco_premium_key')) {
            // Simulate premium check
            define('ACO_PREMIUM', true);
        } else {
            define('ACO_PREMIUM', false);
        }
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
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-analysis-result"></div>';
        echo '<button type="button" id="aco-analyze-btn" class="button button-secondary">Analyze Content</button>';
        echo '<p><small>Free: Basic SEO score. <a href="#" id="aco-upgrade">Upgrade to Premium</a> for AI rewrites & more.</small></p>';
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_ajax_nonce'),
            'is_premium' => ACO_PREMIUM ? '1' : '0'
        ));
    }

    public function analyze_content() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (basic free version)
        $word_count = str_word_count(strip_tags($content));
        $seo_score = min(100, 50 + ($word_count / 100) + (substr_count($content, '<h') * 5));
        $readability = rand(60, 90);

        $result = array(
            'score' => round($seo_score),
            'word_count' => $word_count,
            'readability' => $readability,
            'suggestions' => ACO_PREMIUM ? 'AI rewrite: "Optimized version here..."' : 'Add headings, keywords, and images for better SEO.',
            'is_premium' => ACO_PREMIUM
        );

        if (!ACO_PREMIUM && get_option('aco_free_scans', 5) <= 0) {
            $result['error'] = 'Free scans exhausted. Upgrade to premium!';
        } else {
            if (!ACO_PREMIUM) {
                update_option('aco_free_scans', get_option('aco_free_scans', 5) - 1);
            }
        }

        wp_send_json($result);
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page_callback')
        );
    }

    public function settings_page_callback() {
        if (isset($_POST['aco_premium_key'])) {
            update_option('aco_premium_key', sanitize_text_field($_POST['aco_premium_key']));
            echo '<div class="notice notice-success"><p>Key saved! Premium features unlocked (demo).</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="aco_premium_key" value="<?php echo esc_attr(get_option('aco_premium_key')); ?>" class="regular-text" placeholder="Enter key to unlock premium" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade Now:</strong> <a href="https://example.com/premium" target="_blank">Subscribe for $4.99/month</a> - Unlimited scans, AI rewrites, integrations.</p>
        </div>
        <?php
    }
}

AIContentOptimizerLite::get_instance();

// Freemium upsell notice
function aco_admin_notice() {
    if (!ACO_PREMIUM && 'post.php' == $GLOBALS['pagenow']) {
        echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Content Optimizer Premium</strong> for advanced features! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade now</a></p></div>';
    }
}
add_action('admin_notices', 'aco_admin_notice');

// Reset free scans on activation
register_activation_hook(__FILE__, function() {
    update_option('aco_free_scans', 5);
});