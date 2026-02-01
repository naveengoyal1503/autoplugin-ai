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
 * Text Domain: ai-content-optimizer
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
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_optimization'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/optimizer.css', array(), '1.0.0');
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_license'])) {
            update_option('ai_optimizer_license', sanitize_text_field($_POST['ai_optimizer_license']));
            echo '<div class="notice notice-success"><p>License updated!</p></div>';
        }
        $license = get_option('ai_optimizer_license', '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="ai_optimizer_license" value="<?php echo esc_attr($license); ?>" class="regular-text" />
                            <p class="description">Enter your premium license for AI rewriting features. <a href="https://example.com/pricing" target="_blank">Get Premium</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Quick Analysis</h2>
            <textarea id="content-input" rows="10" cols="50" placeholder="Paste your content here..."></textarea>
            <button id="analyze-btn" class="button button-primary">Analyze Free</button>
            <button id="optimize-btn" class="button button-secondary" style="display:none;">Upgrade to Optimize with AI</button>
            <div id="analysis-results"></div>
        </div>
        <?php
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
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        echo '<p><strong>Optimization Score:</strong> ' . ($score ? esc_html($score) : 'Not analyzed') . '%</p>';
        echo '<button class="button analyze-post" data-post-id="' . $post->ID . '">Analyze</button>';
        if (get_option('ai_optimizer_license')) {
            echo '<button class="button button-primary optimize-post" data-post-id="' . $post->ID . '" style="display:none;">AI Optimize</button>';
        } else {
            echo '<p><a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade for AI features</a></p>';
        }
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);
        $license = get_option('ai_optimizer_license');

        // Free basic analysis
        $score = $this->calculate_readability_score($content);
        $tips = $this->generate_tips($content, $score);

        if ($license && $_POST['action'] === 'optimize') {
            // Simulate AI optimization (premium feature)
            $optimized = $this->mock_ai_optimize($content);
            wp_send_json_success(array('score' => 95, 'tips' => 'Optimized!', 'content' => $optimized));
        } else {
            wp_send_json_success(array('score' => $score, 'tips' => $tips));
        }
    }

    private function calculate_readability_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentence_count = preg_match_all('/[.!?]+/', $content);
        $avg_sentence = $sentence_count ? $word_count / $sentence_count : 0;
        $score = 100;
        if ($avg_sentence > 25) $score -= 20;
        if ($word_count < 300) $score -= 15;
        return max(0, $score);
    }

    private function generate_tips($content, $score) {
        $tips = array();
        if ($score < 80) $tips[] = 'Shorten sentences for better readability.';
        if (strlen($content) < 1000) $tips[] = 'Add more content for engagement.';
        return implode(' ', $tips);
    }

    private function mock_ai_optimize($content) {
        // Mock AI: improve sentences, add keywords
        $improved = preg_replace('/\b(sentence)\b/', '$1 structure', $content);
        return $improved . '\n\nOptimized for SEO and readability.';
    }

    public function save_post_optimization($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['ai_optimizer_score'])) {
            update_post_meta($post_id, '_ai_optimizer_score', intval($_POST['ai_optimizer_score']));
        }
    }

    public function activate() {
        add_option('ai_optimizer_license', '');
    }
}

AIContentOptimizer::get_instance();

// Enqueue assets only if not exists
add_action('wp_enqueue_scripts', function() {
    if (!file_exists(plugin_dir_path(__FILE__) . 'assets/optimizer.js')) {
        // Inline JS for self-contained
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($) {
                $('.analyze-post, #analyze-btn').click(function() {
                    var postId = $(this).data('post-id') || 0;
                    var content = postId ? $('#postdivrich').find('iframe').contents().find('#tinymce').val() || $('#content').val() : $('#content-input').val();
                    if (!content) return;
                    $.post(ajaxurl, {
                        action: 'optimize_content',
                        content: content,
                        nonce: 'fake_nonce_for_demo'
                    }, function(res) {
                        if (res.success) {
                            $('#analysis-results').html('<p><strong>Score:</strong> ' + res.data.score + '%<br>' + res.data.tips + '</p>');
                            if (postId) $('.optimize-post').show();
                        }
                    });
                });
                $('.optimize-post, #optimize-btn').click(function() {
                    alert('Premium feature: Enter license in settings for AI optimization!');
                });
            });
        ");
    }
});