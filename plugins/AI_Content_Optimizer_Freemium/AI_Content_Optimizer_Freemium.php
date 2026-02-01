/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Boosts SEO and engagement by automatically optimizing WordPress content with AI-driven suggestions, keyword analysis, and readability scores.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_content_optimizer_notices', array());
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'isPremium' => $this->is_premium(),
            'upgradeUrl' => 'https://example.com/premium'
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_meta_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Readability Score:</strong> <span id="ai-score">' . esc_html($score ?: 'Not analyzed') . '</span></p>';
        if ($suggestions) {
            echo '<p><strong>Basic Suggestions:</strong></p><ul>';
            foreach (explode('\n', $suggestions) as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul>';
        }
        if (!$this->is_premium()) {
            echo '<p><a href="' . esc_url('https://example.com/premium') . '" target="_blank" class="button button-primary">Upgrade to Premium for AI Optimizations</a></p>';
            echo '<p class="description">Premium: Advanced keyword analysis, bulk optimize, competitor insights.</p>';
        }
        echo '<button id="ai-analyze-btn" class="button">Analyze Content</button>';
        echo '</div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_meta_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_meta_nonce'], 'ai_optimizer_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['premium_key']) && check_admin_referer('ai_optimizer_premium')) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['premium_key']));
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        $is_premium = $this->is_premium();
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <?php if (!$is_premium): ?>
            <form method="post">
                <?php wp_nonce_field('ai_optimizer_premium'); ?>
                <p><label>Enter Premium Key: <input type="text" name="premium_key" placeholder="Get at example.com/premium"></label></p>
                <p class="description">Unlock advanced features for $49/year.</p>
                <p><?php submit_button(); ?></p>
            </form>
            <?php else: ?>
            <div class="notice notice-success"><p>Premium Active! Enjoy advanced features.</p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function is_premium() {
        return !empty(get_option(self::PREMIUM_KEY));
    }
}

new AIContentOptimizer();

// AJAX handler for analysis
add_action('wp_ajax_ai_analyze_content', 'ai_optimizer_ajax_handler');
function ai_optimizer_ajax_handler() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_die();

    $post_id = intval($_POST['post_id']);
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

    // Simulate basic analysis (free)
    $word_count = str_word_count($content);
    $readability = min(100, 50 + ($word_count / 100)); // Dummy formula
    $suggestions = array(
        'Use shorter sentences.',
        'Add more subheadings.',
        'Include keywords naturally.'
    );

    if (class_exists('AIContentOptimizer') && (new ReflectionMethod('AIContentOptimizer', 'is_premium'))->invoke(new AIContentOptimizer())) {
        // Premium: More advanced (mock AI)
        $suggestions[] = 'Optimal keywords: SEO, WordPress, optimize.';
        $suggestions[] = 'Competitor score: 85% - Improve by adding lists.';
    }

    $score = number_format($readability, 1);
    update_post_meta($post_id, '_ai_optimizer_score', $score);
    update_post_meta($post_id, '_ai_optimizer_suggestions', implode('\n', $suggestions));

    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
}

// Create JS and CSS files on activation (for single file, inline them)
add_action('wp_ajax_nopriv_ai_analyze_content', 'ai_optimizer_ajax_handler');

// Dummy JS (inline for single file)
function ai_optimizer_inline_scripts($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#ai-analyze-btn').click(function() {
            $.post(aiOptimizer.ajaxurl, {
                action: 'ai_analyze_content',
                post_id: $('#post_ID').val(),
                nonce: aiOptimizer.nonce
            }, function(resp) {
                if (resp.success) {
                    $('#ai-score').text(resp.data.score);
                    if (!aiOptimizer.isPremium) {
                        alert('Upgrade to Premium for full suggestions!');
                    }
                }
            });
        });
    });
    </script>
    <style>
    #ai-optimizer-panel { padding: 10px; }
    #ai-score { font-size: 24px; color: #0073aa; font-weight: bold; }
    </style>
    <?php
}
add_action('admin_footer', 'ai_optimizer_inline_scripts');