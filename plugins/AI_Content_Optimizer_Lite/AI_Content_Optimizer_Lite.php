/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO with AI-powered suggestions. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => false
        ));
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
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results">';
        echo '<p><strong>Free Features:</strong> Basic SEO score and keyword suggestions (limited to 3 scans/day).</p>';
        echo '<textarea id="aco-content" rows="5" cols="50" placeholder="Content will be analyzed here..."></textarea>';
        echo '<br><button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-output"></div>';
        echo '<p><a href="https://example.com/premium" target="_blank" class="button button-secondary">Upgrade to Premium</a></p>';
        echo '</div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        // Simulate daily limit (in production, use transients or user meta)
        $today = date('Y-m-d');
        $scans = get_transient('aco_scans_' . get_current_user_id());
        if (!$scans) {
            $scans = 0;
        }
        if ($scans >= 3) {
            wp_send_json_error('Free limit reached (3 scans/day). <a href="https://example.com/premium" target="_blank">Upgrade</a>');
        }
        set_transient('aco_scans_' . get_current_user_id(), $scans + 1, DAY_IN_SECONDS);

        // Basic analysis simulation (in premium, integrate real AI API like OpenAI)
        $word_count = str_word_count($content);
        $keywords = $this->extract_keywords($content);
        $score = min(100, 50 + ($word_count / 10) + (count($keywords) * 5));

        $suggestions = array(
            'Use more keywords: ' . implode(', ', array_slice($keywords, 0, 3)),
            'Aim for 500+ words for better SEO.',
            '<strong>SEO Score: ' . round($score, 1) . '%</strong>'
        );

        ob_start();
        echo '<ul>';
        foreach ($suggestions as $sugg) {
            echo '<li>' . $sugg . '</li>';
        }
        echo '</ul>';
        echo '<p><em>Premium: Auto-optimize, unlimited scans, advanced AI.</em></p>';
        $output = ob_get_clean();

        wp_send_json_success($output);
    }

    private function extract_keywords($content) {
        // Simple keyword extraction
        $words = explode(' ', strtolower(strip_tags($content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_keys(array_slice($counts, 0, 5));
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for unlimited AI optimizations! <a href="https://example.com/premium" target="_blank">Learn more</a></p></div>';
    }

    public function activate() {
        // Activation hook
    }
}

AIContentOptimizerLite::get_instance();

// Inline JS for simplicity (self-contained)
function aco_add_inline_script() {
    if (isset($_GET['post']) || isset($_GET['post_type'])) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function(e) {
                e.preventDefault();
                var content = $('#aco-content').val();
                if (!content) {
                    $('#aco-output').html('<p style="color:red;">Enter content first.</p>');
                    return;
                }
                $('#aco-output').html('<p>Analyzing...</p>');
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    content: content
                }, function(response) {
                    if (response.success) {
                        $('#aco-output').html(response.data);
                    } else {
                        $('#aco-output').html('<p style="color:red;">' + response.data + '</p>');
                    }
                });
            });
            $('#aco-content').on('input', function() {
                $(this).val($('#postdivrich').find('.wp-editor-area').val());
            });
        });
        <?php
    }
}
add_action('admin_footer-post.php', 'aco_add_inline_script');
add_action('admin_footer-post-new.php', 'aco_add_inline_script');
?>