/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Premium upgrade available.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    const PREMIUM_URL = 'https://example.com/premium-upgrade';
    private $scan_limit = 5;
    private $scans_today = 0;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_optimize', array($this, 'handle_optimize'));
        add_action('wp_ajax_aco_get_stats', array($this, 'get_scan_stats'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'aco-admin.css', array(), '1.0.0');
            wp_localize_script('aco-admin-js', 'aco_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aco_nonce'),
                'limit' => $this->scan_limit
            ));
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        $content = $post_id ? get_post_field('post_content', $post_id) : '';
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function handle_optimize() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $this->scans_today = get_option('aco_scans_today', 0);
        if ($this->scans_today >= $this->scan_limit) {
            wp_send_json_error('Daily limit reached. <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade to Premium</a> for unlimited scans.');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        $analysis = $this->analyze_content($content);
        update_option('aco_scans_today', $this->scans_today + 1);
        wp_send_json_success($analysis);
    }

    public function get_scan_stats() {
        check_ajax_referer('aco_nonce', 'nonce');
        $stats = array(
            'scans_today' => get_option('aco_scans_today', 0),
            'limit' => $this->scan_limit
        );
        wp_send_json_success($stats);
    }

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        $keywords = $this->extract_keywords($content);

        $score = min(100, 50 + ($word_count > 500 ? 20 : 0) + ($readability > 15 && $readability < 25 ? 30 : 0));

        return array(
            'score' => $score,
            'word_count' => $word_count,
            'avg_sentence_length' => round($readability, 1),
            'keywords' => $keywords,
            'tips' => $this->get_tips($score, $word_count, $readability)
        );
    }

    private function extract_keywords($content) {
        $words = explode(' ', strip_tags(strtolower($content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_slice(array_keys($counts), 0, 5);
    }

    private function get_tips($score, $words, $readability) {
        $tips = array();
        if ($score < 70) {
            if ($words < 500) $tips[] = 'Add more content for better engagement.';
            if ($readability > 25) $tips[] = 'Shorten sentences for improved readability.';
        }
        $tips[] = 'Premium: Get AI-powered rewrites and meta suggestions.';
        return $tips;
    }

    public function activate() {
        delete_option('aco_scans_today');
    }
}

new AIContentOptimizerLite();

// Inline styles and scripts for self-contained plugin
add_action('admin_head', function() {
    echo '<style>
    #aco-container { max-width: 800px; margin: 20px 0; }
    #aco-score { font-size: 48px; font-weight: bold; color: #0073aa; }
    .aco-premium-upsell { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 4px; }
    .aco-limit-warning { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px; }
    </style>';
});

// Minimal JS
add_action('admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function(e) {
            e.preventDefault();
            var content = $('#aco-content').val();
            if (!content) return;
            $('#aco-loading').show();
            $.post(aco_ajax.ajax_url, {
                action: 'aco_optimize',
                nonce: aco_ajax.nonce,
                content: content
            }, function(resp) {
                $('#aco-loading').hide();
                if (resp.success) {
                    $('#aco-results').html(buildResults(resp.data));
                } else {
                    $('#aco-results').html('<div class="notice notice-error">' + resp.data + '</div>');
                }
            });
        });

        function buildResults(data) {
            var html = '<div id="aco-score">' + data.score + '/100</div>';
            html += '<p><strong>Words:</strong> ' + data.word_count + '</p>';
            html += '<p><strong>Avg Sentence Length:</strong> ' + data.avg_sentence_length + '</p>';
            html += '<p><strong>Top Keywords:</strong> ' + data.keywords.join(', ') + '</p>';
            html += '<ul>';
            data.tips.forEach(function(tip) { html += '<li>' + tip + '</li>'; });
            html += '</ul>';
            html += '<div class="aco-premium-upsell">Upgrade to Premium for AI rewrites & unlimited scans! <a href="<?php echo AIContentOptimizerLite::PREMIUM_URL; ?>" target="_blank">Get Premium</a></div>';
            return html;
        }
    });
    </script>
    <?php
});