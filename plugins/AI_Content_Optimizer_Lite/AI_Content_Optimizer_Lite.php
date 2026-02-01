/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis for better readability, SEO, and engagement. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ai_content_analyze', array($this, 'analyze_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_api_key', '');
        add_option('ai_content_optimizer_premium', false);
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-content-optimizer', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-content-optimizer', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
            wp_localize_script('ai-content-optimizer', 'ai_optimizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_content_nonce'),
                'premium' => get_option('ai_content_optimizer_premium', false)
            ));
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_content_meta', 'ai_content_nonce');
        $score = get_post_meta($post->ID, '_ai_content_score', true);
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Readability Score:</strong> <span id="ai-score">' . esc_html($score ?: 'Not analyzed') . '</span></p>';
        echo '<button id="ai-analyze-btn" class="button">Analyze Content</button>';
        echo '<div id="ai-results"></div>';
        echo '<p class="description">Upgrade to premium for AI rewriting and unlimited analyses. <a href="#" id="upgrade-link">Learn more</a></p>';
        echo '</div>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['ai_content_nonce']) || !wp_verify_nonce($_POST['ai_content_nonce'], 'ai_content_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['ai_content_score'])) {
            update_post_meta($post_id, '_ai_content_score', sanitize_text_field($_POST['ai_content_score']));
        }
    }

    public function analyze_content() {
        check_ajax_referer('ai_content_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate AI analysis (basic heuristics for lite version)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        $readability = 100 - min(50, ($avg_sentence_length - 15) * 2);
        $score = max(0, min(100, round($readability)));

        // Premium check
        $premium = get_option('ai_content_optimizer_premium', false);
        $limit = $premium ? 999 : 5;
        $uses = get_option('ai_content_optimizer_uses', 0);

        if (!$premium && $uses >= $limit) {
            wp_send_json_error('Upgrade to premium for unlimited analyses.');
        }

        update_option('ai_content_optimizer_uses', $uses + 1);
        update_post_meta($post_id, '_ai_content_score', $score);

        $tips = array();
        if ($avg_sentence_length > 20) {
            $tips[] = 'Shorten sentences for better readability.';
        }
        if ($word_count < 300) {
            $tips[] = 'Add more content for better engagement.';
        }

        ob_start();
        echo '<p><strong>Score: ' . $score . '/100</strong></p>';
        if (!empty($tips)) {
            echo '<ul>';
            foreach ($tips as $tip) {
                echo '<li>' . esc_html($tip) . '</li>';
            }
            echo '</ul>';
        }
        if (!$premium) {
            echo '<p>Uses left: ' . ($limit - $uses - 1) . '</p>';
        }
        $results = ob_get_clean();

        wp_send_json_success(array('score' => $score, 'results' => $results));
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_optimizer_settings'); ?>
                <?php do_settings_sections('ai_optimizer_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Key (Premium)</th>
                        <td><input type="text" name="ai_content_optimizer_api_key" value="<?php echo esc_attr(get_option('ai_content_optimizer_api_key')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Premium</h2>
            <p>Unlock unlimited analyses, AI rewriting, and more. <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting('ai_optimizer_settings', 'ai_content_optimizer_api_key');
        register_setting('ai_optimizer_settings', 'ai_content_optimizer_premium');
    }
}

new AIContentOptimizerLite();

// Freemius integration stub (replace with real Freemius code)
// require_once dirname(__FILE__) . '/freemius/start.php';

// Asset placeholders - create empty folders/files
// assets/script.js: jQuery(document).ready(function($){ $('#ai-analyze-btn').click(function(){ $.post(ai_optimizer.ajax_url, {action:'ai_content_analyze', post_id: $(this).closest('.postbox').find('input[name*="post_ID"]').val(), nonce: ai_optimizer.nonce}, function(r){ if(r.success){ $('#ai-score').text(r.data.score); $('#ai-results').html(r.data.results); } }); }); });
// assets/style.css: #ai-optimizer-panel { padding: 10px; } #ai-analyze-btn { width: 100%; }
?>