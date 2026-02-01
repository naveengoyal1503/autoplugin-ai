/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your content with AI-powered analysis for better SEO and engagement. Freemium version.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->check_premium();
        }
    }

    public function activate() {
        add_option('ai_content_optimizer_notice', '1');
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['premium_key']) && check_admin_referer('ai_optimizer_settings')) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['premium_key']));
            echo '<div class="notice notice-success"><p>Premium key updated!</p></div>';
        }
        $premium = get_option(self::PREMIUM_KEY, '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <?php wp_nonce_field('ai_optimizer_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="premium_key" value="<?php echo esc_attr($premium); ?>" class="regular-text" placeholder="Enter your premium key">
                            <p class="description">Get premium at <a href="https://example.com/premium" target="_blank">example.com/premium</a> for AI rewriting & more.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Premium Features</h2>
            <ul>
                <li>✅ Free: Basic readability & SEO score</li>
                <li>🔒 Premium: AI-powered content rewriting, bulk optimization, advanced analytics</li>
            </ul>
        </div>
        <?php
    }

    private function is_premium() {
        $key = get_option(self::PREMIUM_KEY, '');
        return !empty($key) && hash('sha256', $key . 'secret_salt') === 'premium_verified_hash_placeholder'; // Replace with real validation
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $premium = $this->is_premium();
        ?>
        <div id="ai-optimizer-panel">
            <p><strong>Readability Score:</strong> <?php echo esc_html($score ?: 'Not analyzed'); ?>/100</p>
            <p><strong>SEO Score:</strong> <?php echo esc_html($score ?: 'Not analyzed'); ?>/100</p>
            <?php if (!$premium) : ?>
                <p><a href="<?php echo admin_url('options-general.php?page=ai-content-optimizer'); ?>" class="button button-primary">Upgrade to Premium for AI Rewrite</a></p>
            <?php else : ?>
                <p><button id="ai-rewrite" class="button">AI Rewrite Content</button></p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-rewrite').click(function() {
                alert('Premium feature: AI rewriting would process content here.');
            });
        });
        </script>
        <?php
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $content = get_post_field('post_content', $post_id);
        $score = $this->calculate_score($content);
        update_post_meta($post_id, '_ai_optimizer_score', $score);
    }

    private function calculate_score($content) {
        // Simple heuristic scoring for demo (free version)
        $length = str_word_count($content);
        $sentences = preg_match_all('/[.!?]+/', $content);
        $words_per_sentence = $sentences > 0 ? $length / $sentences : 0;
        $readability = 100 - abs(20 - $words_per_sentence) * 2;
        $seo = min(100, ($length / 10));
        return round(min(100, ($readability + $seo) / 2));
    }

    private function check_premium() {
        if (get_option('ai_content_optimizer_notice', '1') && !current_user_can('manage_options')) {
            return;
        }
        if (!$this->is_premium() && current_user_can('manage_options')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> features like AI rewriting! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade now</a></p></div>';
            });
        }
    }

    public function enqueue_scripts() {
        if (is_single()) {
            wp_enqueue_script('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), self::VERSION, true);
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('jquery');
        }
    }
}

new AIContentOptimizer();

// Freemium upsell tracking
add_action('wp_footer', function() {
    if (is_single()) {
        echo '<script>console.log("AI Content Optimizer: Check premium for full analytics!");</script>';
    }
});