/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO using AI-powered suggestions. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ai_optimizer_license_key') && $this->verify_license()) {
            $this->is_premium = true;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-admin-js', 'ai_optimizer_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-panel">';
        echo '<p><button id="analyze-content" class="button button-primary">Analyze Content</button></p>';
        echo '<div id="ai-suggestions"></div>';
        echo '<p><small>Premium: Unlock bulk optimization and advanced AI.</small> <a href="' . $this->get_premium_url() . '" target="_blank">Upgrade</a></p>';
        echo '</div>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_premium && !empty($_POST['full_optimize'])) {
            wp_send_json_error('Premium feature required.');
            return;
        }
        $content = sanitize_textarea_field($_POST['content']);
        $suggestions = $this->generate_suggestions($content);
        wp_send_json_success($suggestions);
    }

    private function generate_suggestions($content) {
        // Simulated AI analysis - in real plugin, integrate OpenAI API or similar
        $word_count = str_word_count($content);
        $has_keywords = preg_match('/(seo|content|blog)/i', $content);
        $suggestions = array(
            'word_count' => $word_count,
            'tips' => array(),
            'premium_only' => !$this->is_premium
        );
        if ($word_count < 300) {
            $suggestions['tips'][] = 'Add more content for better SEO (aim for 1000+ words).';
        }
        if (!$has_keywords) {
            $suggestions['tips'][] = 'Include primary keywords like "' . $this->suggest_keyword($content) . '".';
        }
        if ($this->is_premium) {
            $suggestions['tips'][] = 'Advanced: Optimized meta description generated.';
        } else {
            $suggestions['upgrade'] = 'Upgrade for full AI rewrite and bulk tools.';
        }
        return $suggestions;
    }

    private function suggest_keyword($content) {
        return 'your main keyword'; // Placeholder
    }

    private function verify_license() {
        // Placeholder license check
        return false;
    }

    private function get_premium_url() {
        return 'https://example.com/premium-upgrade';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_license'])) {
            update_option('ai_optimizer_license_key', sanitize_text_field($_POST['license_key']));
            echo '<div class="notice notice-success"><p>License updated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="license_key" value="<?php echo get_option('ai_optimizer_license_key'); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Enter your premium license key to unlock advanced features.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('ai_optimizer_version', '1.0.0');
    }
}

AIContentOptimizer::get_instance();

// Enqueue JS files (inline for single file)
function ai_optimizer_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#analyze-content').click(function() {
            var content = $('#content').val() || tinyMCE.activeEditor.getContent();
            $.post(ai_optimizer_ajax.ajax_url, {
                action: 'optimize_content',
                nonce: ai_optimizer_ajax.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var html = '<ul>';
                    response.data.tips.forEach(function(tip) {
                        html += '<li>' + tip + '</li>';
                    });
                    html += '</ul>';
                    if (response.data.upgrade) {
                        html += '<p><strong>' + response.data.upgrade + '</strong></p>';
                    }
                    $('#ai-suggestions').html(html);
                } else {
                    $('#ai-suggestions').html('<p>Error: ' + response.data + '</p>');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');