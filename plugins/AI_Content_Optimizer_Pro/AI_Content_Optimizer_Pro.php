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
 * Author URI: https://example.com
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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Content Score:</strong> <span id="ai-score">' . esc_html($score ?: 'Analyze') . '</span>/100</p>';
        if ($suggestions) {
            echo '<ul id="ai-suggestions">';
            foreach ($suggestions as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul>';
        }
        echo '<button type="button" id="ai-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p class="description">Pro version unlocks advanced AI suggestions and auto-optimization.</p>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_submit'])) {
            update_option('ai_optimizer_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $api_key = get_option('ai_optimizer_api_key', '');
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="api_key">Pro API Key</label></th>
                        <td><input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                        <p class="description">Enter your Pro license key for advanced features.</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock unlimited analyses, auto-optimization, and more for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        // Activation hook
    }
}

// AJAX handler
add_action('wp_ajax_ai_analyze_content', 'ai_analyze_content_handler');
function ai_analyze_content_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'ai_optimizer_nonce')) {
        wp_die('Security check failed');
    }

    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);

    // Simple heuristic analysis (free version)
    $word_count = str_word_count(strip_tags($content));
    $sentences = preg_split('/[.!?]+/', $content);
    $sentence_count = count(array_filter($sentences));
    $readability = $word_count > 0 ? min(100, ($sentence_count / $word_count) * 300) : 0; // Flesch-like
    $score = round(($readability * 0.4) + (min(100, $word_count / 10) * 0.6));

    $suggestions = array();
    if ($word_count < 300) {
        $suggestions[] = 'Add more content: Aim for 500+ words.';
    }
    if ($sentence_count < 5) {
        $suggestions[] = 'Use shorter sentences for better readability.';
    }
    if (substr_count(strtolower($content), 'keyword') === 0) {
        $suggestions[] = 'Incorporate primary keywords naturally.';
    }

    update_post_meta($post_id, '_ai_optimizer_score', $score);
    update_post_meta($post_id, '_ai_optimizer_suggestions', $suggestions);

    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
}

AIContentOptimizer::get_instance();

// Inline JS and CSS for simplicity (self-contained)
function ai_optimizer_inline_assets() {
    if (isset($_GET['post'])) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-analyze').click(function() {
                var postId = $('#post_ID').val();
                $.post(ajaxurl, {
                    action: 'ai_analyze_content',
                    post_id: postId,
                    nonce: $('#ai_optimizer_nonce').val()
                }, function(response) {
                    if (response.success) {
                        $('#ai-score').text(response.data.score);
                        var suggHtml = '<ul>';
                        $.each(response.data.suggestions, function(i, sugg) {
                            suggHtml += '<li>' + sugg + '</li>';
                        });
                        suggHtml += '</ul>';
                        $('#ai-suggestions').html(suggHtml);
                    }
                });
            });
        });
        </script>
        <style>
        #ai-optimizer-panel { padding: 10px; }
        #ai-score { font-size: 24px; color: #0073aa; font-weight: bold; }
        #ai-suggestions { margin: 10px 0; font-size: 12px; }
        </style>
        <?php
    }
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_assets');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_assets');
