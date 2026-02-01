/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better readability, SEO, and engagement.
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
    const PREMIUM_URL = 'https://example.com/premium-upgrade?from=wp-plugin';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_readability_score', true);
        $seo_score = get_post_meta($post->ID, '_ai_seo_score', true);
        $tips = $this->analyze_content($content);
        echo '<div id="ai-optimizer-results">';
        if ($score) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($score) . '/100</p>';
            echo '<p><strong>SEO Score:</strong> ' . esc_html($seo_score) . '/100</p>';
        }
        echo '<p>' . $tips . '</p>';
        echo '<button id="ai-analyze-btn" class="button button-secondary">Analyze Content</button>';
        echo $this->get_premium_upsell();
        echo '</div>';
    }

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 && $sentence_count > 0 ? min(100, max(0, 100 - ($word_count / $sentence_count * 2))) : 50;
        $keywords = $this->extract_keywords($content);
        $seo = count($keywords) > 3 ? 80 : 40;
        update_post_meta(get_the_ID(), '_ai_readability_score', $readability);
        update_post_meta(get_the_ID(), '_ai_seo_score', $seo);
        $tips = $readability < 70 ? 'Improve sentence length for better readability.' : 'Good readability!';
        $tips .= ' SEO: ' . ($seo < 70 ? 'Add more keywords.' : 'Solid SEO.');
        return $tips;
    }

    private function extract_keywords($content) {
        $words = explode(' ', strip_tags(strtolower($content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_keys(array_slice($counts, 0, 5, true));
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'premium_url' => self::PREMIUM_URL
        ));
    }

    public function add_action_links($links) {
        $links[] = '<a href="' . self::PREMIUM_URL . '" target="_blank">Go Pro</a>';
        return $links;
    }

    private function get_premium_upsell() {
        return '<div style="margin-top:10px;padding:10px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:4px;font-size:12px;">
            <p><strong>Go Pro for AI Rewriting & Advanced SEO!</strong></p>
            <p>Unlock AI-powered content rewriting, keyword research, and more for $4.99/mo.</p>
            <a href="' . self::PREMIUM_URL . '" class="button button-primary" target="_blank">Upgrade Now</a>
        </div>';
    }
}

AIContentOptimizer::get_instance();

// AJAX handler for analysis
add_action('wp_ajax_ai_analyze_content', function() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_die();
    }
    $content = get_post_field('post_content', $post_id);
    $optimizer = AIContentOptimizer::get_instance();
    $tips = $optimizer->analyze_content($content);
    wp_send_json_success(array('tips' => $tips));
});

// Note: JS file reference is placeholder; in production, include inline JS or bundle a JS file.
// Inline JS for self-contained:
function ai_optimizer_inline_js() {
    if (!wp_doing_ajax()) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#ai-analyze-btn').click(function(e) {
                e.preventDefault();
                var postId = $('#post_ID').val();
                $.post(ai_optimizer.ajax_url, {
                    action: 'ai_analyze_content',
                    post_id: postId,
                    nonce: ai_optimizer.nonce
                }, function(response) {
                    if (response.success) {
                        $('#ai-optimizer-results p').last().html(response.data.tips);
                    }
                });
            });
        });
        <?php
    }
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
?>