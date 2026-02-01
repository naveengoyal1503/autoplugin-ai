/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered content optimization for SEO, readability, and engagement. Freemium model.
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
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            $this->check_premium();
        }
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('aco-frontend', plugin_dir_url(__FILE__) . 'aco-frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('aco-admin-style', plugin_dir_url(__FILE__) . 'aco-admin.css', array(), '1.0.0');
        }
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('aco_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $api_key = get_option('aco_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AI API Key (Premium)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Free Features:</strong> Basic SEO score, readability analysis.</p>
            <p><strong>Premium (Upgrade for $49/year):</strong> AI rewriting, keyword suggestions, bulk optimization.</p>
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
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        $premium = $this->is_premium();
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score : 'Not analyzed') . '%</p>';
        echo '<p>Readability: ' . $this->get_readability($post->post_content) . '</p>';
        if ($premium) {
            echo '<button id="aco-optimize" class="button button-primary">AI Optimize (Premium)</button>';
        } else {
            echo '<p><a href="https://example.com/premium" target="_blank" class="button">Upgrade to Premium</a></p>';
        }
    }

    public function save_post($post_id) {
        if (!isset($_POST['aco_meta_box_nonce']) || !wp_verify_nonce($_POST['aco_meta_box_nonce'], 'aco_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $content = get_post_field('post_content', $post_id);
        $score = $this->calculate_seo_score($content);
        update_post_meta($post_id, '_aco_score', $score);
    }

    private function calculate_seo_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $title_length = strlen(get_the_title());
        $score = 50; // Base
        if ($word_count > 300) $score += 20;
        if ($title_length > 30 && $title_length < 60) $score += 15;
        if (preg_match_all('/<h[1-3]/', $content) > 1) $score += 15;
        return min(100, $score);
    }

    private function get_readability($content) {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count(strip_tags($content));
        $score = $words / count($sentences);
        if ($score < 15) return 'Easy';
        if ($score < 25) return 'Medium';
        return 'Hard';
    }

    private function is_premium() {
        return get_option('aco_premium_active', false);
    }

    private function check_premium() {
        // Simulate premium check
        if (!get_option('aco_premium_nag_dismissed')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info"><p>Unlock AI rewriting with <a href="https://example.com/premium">AI Content Optimizer Pro Premium</a> ($49/year).</p><a href="?aco_dismiss=1">Dismiss</a></div>';
            });
        }
    }

    public function activate() {
        add_option('aco_premium_nag_dismissed', false);
    }
}

AIContentOptimizer::get_instance();

// Frontend JS placeholder
function aco_frontend_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Basic frontend enhancement
        $('.post-content').prepend('<div class="aco-badge">Optimized</div>');
    });
    </script>
    <?php
}

// Admin JS and CSS placeholders
/*
aco-admin.js and aco-admin.css would be enqueued if files exist, but self-contained.
*/

?>