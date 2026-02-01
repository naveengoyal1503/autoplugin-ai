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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->check_premium();
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
            'is_premium' => $this->is_premium()
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-results">';
        echo '<p><strong>Free Analysis:</strong> Click to analyze current content.</p>';
        if (!$this->is_premium()) {
            echo '<p><a href="https://example.com/premium" target="_blank" class="button button-primary">Upgrade to Premium for AI Suggestions</a></p>';
        }
        echo '<button id="analyze-content" class="button">Analyze Content</button>';
        echo '<div id="analysis-result"></div>';
        echo '</div>';
    }

    public function handle_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (basic free version)
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->calculate_readability($content);
        $seo_score = min(100, 50 + ($word_count / 100) + ($readability / 10));

        $result = array(
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'seo_score' => round($seo_score),
            'suggestions' => $this->is_premium() ? $this->generate_ai_suggestions($content) : array('Upgrade for detailed AI suggestions!')
        );

        wp_send_json_success($result);
    }

    private function calculate_readability($content) {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count(strip_tags($content));
        if ($sentence_count == 0) return 0;
        $words_per_sentence = $words / $sentence_count;
        return 206.835 - 1.015 * ($words / $sentence_count) - 84.6 * ($this->syllable_count(strip_tags($content)) / $words);
    }

    private function syllable_count($text) {
        $text = strtolower($text);
        $vowels = preg_match_all('/[aeiouy]/', $text);
        return $vowels;
    }

    private function generate_ai_suggestions($content) {
        // Premium feature simulation
        return array(
            'Add more keywords: Target "content optimization"',
            'Improve headings: Use H2/H3 for sections',
            'Shorten sentences for better readability'
        );
    }

    private function is_premium() {
        // Simulate license check - in real use, integrate with Freemius or similar
        return false; // Set to true for testing premium
    }

    private function check_premium() {
        if (!$this->is_premium()) {
            add_action('admin_notices', array($this, 'premium_notice'));
        }
    }

    public function premium_notice() {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> features: <a href="https://example.com/premium">Upgrade Now</a></p></div>';
    }

    public function add_action_links($links) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        $links[] = '<a href="https://example.com/docs">Docs</a>';
        return $links;
    }
}

AIContentOptimizer::get_instance();

// Inline CSS
add_action('admin_head-post.php', 'ai_optimizer_admin_styles');
add_action('admin_head-post-new.php', 'ai_optimizer_admin_styles');
function ai_optimizer_admin_styles() {
    echo '<style>
        #ai-optimizer-results { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto; }
        #ai-optimizer-results .score { font-size: 24px; font-weight: bold; }
        .premium-gate { background: #fff3cd; padding: 10px; border-left: 4px solid #ffeaa7; }
    </style>';
}

// JS file content would be enqueued, but for single file, inline a basic script
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
function ai_optimizer_inline_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#analyze-content').click(function() {
            var postId = $('#post_ID').val();
            $('#analysis-result').html('<p>Analyzing...</p>');
            $.post(ai_optimizer.ajax_url, {
                action: 'optimize_content',
                post_id: postId,
                nonce: ai_optimizer.nonce
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<div class="score">SEO Score: ' + res.seo_score + '/100</div>' +
                               '<p>Words: ' + res.word_count + '</p>' +
                               '<p>Readability: ' + res.readability + '</p>' +
                               '<ul>';
                    $.each(res.suggestions, function(i, sug) {
                        html += '<li>' + sug + '</li>';
                    });
                    html += '</ul>';
                    if (!ai_optimizer.is_premium) {
                        html += '<div class="premium-gate">Upgrade for full AI suggestions!</div>';
                    }
                    $('#analysis-result').html(html);
                }
            });
        });
    });
    </script>
    <?php
}

?>