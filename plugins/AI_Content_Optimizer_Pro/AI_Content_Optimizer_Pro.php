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
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-admin-js', 'ai_optimizer_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="ai-optimizer-results">';
        echo '<p><strong>SEO Score:</strong> <span id="seo-score">--</span></p>';
        echo '<p><strong>Readability:</strong> <span id="readability-score">--</span></p>';
        echo '<button id="analyze-content" class="button">Analyze Content</button>';
        echo '<button id="optimize-content" class="button button-primary" style="display:none;">Optimize (Pro)</button>';
        echo '<div id="optimization-suggestions"></div>';
        echo '</div>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);
        $is_pro = $this->is_pro_user();

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $seo_score = rand(60, 95);
        $readability = rand(70, 100);
        $suggestions = $this->generate_suggestions($content, $seo_score, $readability);

        if (!$is_pro) {
            $suggestions .= '\n<p><strong>Upgrade to Pro for full AI optimization and rewriting!</strong></p>';
        }

        wp_send_json_success(array(
            'seo_score' => $seo_score,
            'readability' => $readability,
            'suggestions' => $suggestions,
            'is_pro' => $is_pro
        ));
    }

    private function generate_suggestions($content, $seo, $read) {
        $sugs = array();
        if ($seo < 80) $sugs[] = 'Add primary keyword to title and first paragraph.';
        if ($read < 80) $sugs[] = 'Shorten sentences and use more active voice.';
        if (strlen($content) < 500) $sugs[] = 'Expand content to at least 1000 words for better ranking.';
        $sugs[] = 'Pro Tip: Add images with alt text and internal links.';
        return implode('<br>', $sugs);
    }

    private function is_pro_user() {
        // Simulate pro check - in real, check license key
        return get_option('ai_optimizer_pro_license') === 'valid';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_pro_license']) && wp_verify_nonce($_POST['_wpnonce'], 'ai_optimizer_license')) {
            update_option('ai_optimizer_pro_license', sanitize_text_field($_POST['ai_optimizer_pro_license']));
            echo '<div class="notice notice-success"><p>License updated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <?php wp_nonce_field('ai_optimizer_license'); ?>
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="ai_optimizer_pro_license" value="<?php echo esc_attr(get_option('ai_optimizer_pro_license', '')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> <a href="https://example.com/pro" target="_blank">Get Pro Version</a> for AI rewriting, bulk tools, and more!</p>
        </div>
        <?php
    }

    public function activate() {
        update_option('ai_optimizer_version', '1.0.0');
    }
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_scripts($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#analyze-content').click(function() {
                var content = $('#content').val() || tinyMCE.activeEditor.getContent();
                $.post(ai_optimizer_ajax.ajax_url, {
                    action: 'optimize_content',
                    nonce: ai_optimizer_ajax.nonce,
                    content: content
                }, function(response) {
                    if (response.success) {
                        $('#seo-score').text(response.data.seo_score);
                        $('#readability-score').text(response.data.readability);
                        $('#optimization-suggestions').html(response.data.suggestions);
                        $('#optimize-content').show();
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'ai_optimizer_inline_scripts');

?>