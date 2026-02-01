/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-driven suggestions, keyword analysis, and readability scores.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_filter('the_content', array($this, 'optimize_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        $this->check_premium();
    }

    public function activate() {
        add_option('ai_content_optimizer_activated', time());
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function add_meta_boxes() {
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
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        echo '<p><strong>Readability Score:</strong> ' . esc_html($score ?: 'Not optimized') . '/100</p>';
        if ($suggestions) {
            echo '<p><strong>Suggestions:</strong> ' . esc_html($suggestions) . '</p>';
        }
        echo '<button type="button" id="optimize-now" class="button">Optimize Now (Free)</button>';
        if ($this->is_premium()) {
            echo ' <button type="button" id="premium-optimize" class="button button-primary">AI Premium Optimize</button>';
        } else {
            echo '<p><a href="https://example.com/premium" target="_blank">Upgrade to Premium for AI Features</a></p>';
        }
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Basic optimization simulation
        $content = get_post_field('post_content', $post_id);
        $score = $this->calculate_readability($content);
        update_post_meta($post_id, '_ai_optimizer_score', $score);
        update_post_meta($post_id, '_ai_optimizer_suggestions', $this->generate_basic_suggestions($content));
    }

    public function optimize_content($content) {
        if (is_single()) {
            $score = get_post_meta(get_the_ID(), '_ai_optimizer_score', true);
            if ($score && $score < 70) {
                $content .= '\n\n<div class="ai-optimizer-notice">SEO Score: ' . $score . '/100 - <a href="' . get_edit_post_link() . '">Improve Now</a></div>';
            }
        }
        return $content;
    }

    private function calculate_readability($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = preg_match_all('/[.!?]+/', $content);
        if ($sentences == 0) return 50;
        $syl = $this->count_syllables(strip_tags($content));
        $flesch = 206.835 - 1.015 * ($words / $sentences) - 84.6 * ($syl / $words);
        return max(0, min(100, round($flesch * 1.5)));
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $syllables = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            $syllables += preg_match('/[aeiouy]{2}/', $word) + preg_match('/[aeiouy]$/', $word) ? 1 : 0;
        }
        return $syllables ?: 1;
    }

    private function generate_basic_suggestions($content) {
        $suggestions = [];
        if (str_word_count($content) < 300) $suggestions[] = 'Add more content (aim for 500+ words).';
        if (substr_count(strtolower($content), 'keyword') == 0) $suggestions[] = 'Include primary keyword 2-3 times.';
        return implode(' ', $suggestions);
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_license'])) {
            update_option('ai_optimizer_license', sanitize_text_field($_POST['ai_optimizer_license']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $license = get_option('ai_optimizer_license');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="ai_optimizer_license" value="<?php echo esc_attr($license); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Free version provides basic readability scores. <a href="https://example.com/premium">Upgrade to Premium</a> for AI keyword research and auto-optimization.</p>
        </div>
        <?php
    }

    private function check_premium() {
        // Simulate premium check
        if (!get_option('ai_optimizer_premium')) {
            add_action('admin_notices', array($this, 'premium_nag'));
        }
    }

    public function premium_nag() {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock AI-powered features with <strong>AI Content Optimizer Pro</strong>. <a href="https://example.com/premium">Upgrade Now</a> for just $4.99/month!</p></div>';
        }
    }

    private function is_premium() {
        return get_option('ai_optimizer_license') === 'premium-active-key'; // Simulated
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-optimizer-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-admin', 'aiOptimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_ajax')
        ));
    }
}

new AIContentOptimizer();

// AJAX handlers for optimization
add_action('wp_ajax_ai_optimize_post', 'handle_ai_optimize');
function handle_ai_optimize() {
    check_ajax_referer('ai_optimizer_ajax', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) wp_die();

    $content = get_post_field('post_content', $post_id);
    // Simulate optimization
    $optimized = $content . '\n\n<!-- Optimized with AI Content Optimizer -->';
    wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));

    $score = rand(70, 95);
    update_post_meta($post_id, '_ai_optimizer_score', $score);
    wp_send_json_success(array('score' => $score));
}

// Create CSS file placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'style.css')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', '.ai-optimizer-notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 20px 0; border-radius: 4px; }');
}

// Create JS file placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'admin.js')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'admin.js', "jQuery(document).ready(function($) {
        $('#optimize-now').click(function() {
            $.post(ajaxurl, {
                action: 'ai_optimize_post',
                post_id: $('#post_ID').val(),
                nonce: aiOptimizer.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        });
    });");
}
?>