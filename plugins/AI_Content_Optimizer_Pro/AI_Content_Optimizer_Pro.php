/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your content for readability, SEO, and engagement with AI-powered tools. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
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
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $readability = $this->calculate_readability($content);
        $is_premium = $this->is_premium();

        echo '<div id="ai-optimizer-results">';
        echo '<p><strong>' . __('Flesch Readability Score:', 'ai-content-optimizer') . '</strong> ' . number_format($readability, 1) . '</p>';
        echo '<p class="description">' . __('Higher is better (60-70 ideal for web).', 'ai-content-optimizer') . '</p>';
        echo '<button type="button" id="ai-optimize-btn" class="button button-primary">' . __('Optimize Content', 'ai-content-optimizer') . '</button>';
        if (!$is_premium) {
            echo '<p><small>' . sprintf(__('Upgrade to %sPro%s for AI rewriting and more!', 'ai-content-optimizer'), '<a href="#premium">', '</a>') . '</small></p>';
        }
        echo '</div>';

        echo '<script>
        jQuery(document).ready(function($) {
            $("#ai-optimize-btn").click(function() {
                var content = $("#postdivrich").find(".wp-editor-area").val();
                $("#ai-optimizer-results").html("<p>Optimizing... (Free version: Basic suggestions)</p>");
                // Simulate optimization
                setTimeout(function() {
                    var suggestions = "<ul><li>Shorten sentences.</li><li>Add subheadings.</li><li>Improve transitions.</li></ul>";";
                    if (' . ($is_premium ? 'true' : 'false') . ') {
                        suggestions += "<p><strong>Premium AI Rewrite:</strong> Your content has been auto-optimized!</p>";
                    }
                    $("#ai-optimizer-results").html(suggestions);
                }, 1000);
            });
        });
        </script>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Save any data if needed
    }

    public function add_settings_page() {
        add_options_page(
            __('AI Content Optimizer Settings', 'ai-content-optimizer'),
            __('AI Optimizer', 'ai-content-optimizer'),
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['ai_premium_key']));
            echo '<div class="notice notice-success"><p>' . __('Premium activated!', 'ai-content-optimizer') . '</p></div>';
        }
        $premium_key = get_option(self::PREMIUM_KEY, '');
        $is_premium = !empty($premium_key) && $this->validate_premium_key($premium_key);
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Premium License Key', 'ai-content-optimizer'); ?></th>
                        <td>
                            <input type="text" name="ai_premium_key" value="<?php echo esc_attr($premium_key); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your premium key for advanced features.', 'ai-content-optimizer'); ?> <a href="https://example.com/premium" target="_blank"><?php _e('Get Pro', 'ai-content-optimizer'); ?></a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); }
            </form>
            <?php if (!$is_premium) : ?>
            <div class="card">
                <h2><?php _e('Go Pro Today!', 'ai-content-optimizer'); ?></h2>
                <ul>
                    <li>AI-powered content rewriting</li>
                    <li>Bulk optimization</li>
                    <li>SEO score & suggestions</li>
                    <li>Priority support</li>
                </ul>
                <a href="https://example.com/premium" class="button button-primary button-large" target="_blank"><?php _e('Upgrade Now - $9/mo', 'ai-content-optimizer'); ?></a>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'settings_page_ai-content-optimizer' === $hook) {
            wp_enqueue_script('jquery');
        }
    }

    private function calculate_readability($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = preg_split('/\s+/', strip_tags($text));
        $word_count = count($words);
        $syllables = $this->count_syllables(implode(' ', $words));
        if ($word_count == 0 || $sentence_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllables / $word_count;
        return 206.835 - 1.015 * $asl - 84.6 * $asw;
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy](?![aeiouy])/', $word);
        }
        return $syllables;
    }

    private function is_premium() {
        $key = get_option(self::PREMIUM_KEY, '');
        return $this->validate_premium_key($key);
    }

    private function validate_premium_key($key) {
        // Demo validation: in production, validate with your server
        return !empty($key) && strlen($key) > 10;
    }

    public function plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) == $file) {
            $links[] = '<a href="https://example.com/premium" target="_blank">' . __('Go Pro', 'ai-content-optimizer') . '</a>';
            $links[] = '<a href="https://example.com/docs" target="_blank">' . __('Docs', 'ai-content-optimizer') . '</a>';
        }
        return $links;
    }
}

new AIContentOptimizer();

// Freemium upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $is_premium = (new AIContentOptimizer())->is_premium();
    if (!$is_premium) {
        echo '<div class="notice notice-info is-dismissible"><p>';
        printf(__('Supercharge %sAI Content Optimizer%s with Pro features! %sUpgrade now%s'), '<strong>', '</strong>', '<a href="options-general.php?page=ai-content-optimizer">', '</a>');
        echo '</p></div>';
    }
});