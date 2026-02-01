/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis for better readability, SEO, and engagement. Freemium with premium upsells.
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
    const PREMIUM_URL = 'https://example.com/premium-upgrade';
    const VERSION = '1.0.0';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
        $content = get_post_field('post_content', $post->ID);
        $analysis = $this->analyze_content($content);
        echo '<div id="ai-optimizer-results">';
        echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '%</p>';
        echo '<p><strong>SEO Score:</strong> ' . esc_html($analysis['seo']) . '%</p>';
        echo '<p><strong>Engagement Score:</strong> ' . esc_html($analysis['engagement']) . '%</p>';
        echo $this->get_premium_upsell();
        echo '</div>';
    }

    public function save_meta_box($post_id) {
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

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;

        // Simulated AI analysis (basic heuristics for free version)
        $readability = min(100, max(0, 100 - ($avg_sentence_length - 20) * 2));
        $seo = min(100, ($word_count > 300 ? 80 : 40) + (strpos($content, 'keyword') !== false ? 20 : 0));
        $engagement = min(100, 50 + (substr_count($content, '<p>') * 2));

        return array(
            'readability' => round($readability),
            'seo' => round($seo),
            'engagement' => round($engagement)
        );
    }

    private function get_premium_upsell() {
        return '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 4px;">
            <p><strong>Unlock Premium Features:</strong></p>
            <ul>
                <li>AI-powered rewrite suggestions</li>
                <li>Bulk optimize all posts</li>
                <li>Advanced SEO keyword research</li>
                <li>Priority support</li>
            </ul>
            <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank" class="button button-primary" style="margin-top: 10px;">Upgrade to Pro Now - $49/year</a>
        </div>';
    }

    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>AI Content Optimizer Settings</h1>';
        echo '<p>Free version active. ' . $this->get_premium_upsell() . '</p>';
        echo '</div>';
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), self::VERSION, true);
        }
    }

    public function plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $links[] = '<a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Premium</a>';
            $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
        }
        return $links;
    }
}

new AIContentOptimizer();

// Inline JS for dynamic re-analysis
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
function ai_optimizer_inline_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#content').on('keyup', function() {
            // Simulate live analysis (premium would use real AI API)
            var content = $(this).val();
            var wordCount = content.split(' ').length;
            $('#ai-optimizer-results .readability').text(Math.min(100, 100 - (wordCount / 100 - 20) * 2));
        });
    });
    </script>
    <?php
}
?>