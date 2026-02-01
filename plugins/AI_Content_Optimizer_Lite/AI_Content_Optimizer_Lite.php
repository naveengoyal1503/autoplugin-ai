/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better readability and SEO with AI insights.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    const VERSION = '1.0.0';
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'handle_analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Lite',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('settings_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), self::VERSION, true);
        wp_localize_script('aco-admin', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'limit_reached' => $this->is_limit_reached()
        ));
    }

    public function admin_page() {
        $analyses = get_option('aco_analyses', array());
        $limit = 5;
        $used = count($analyses);
        $premium_nag = $used >= $limit;
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Lite', 'ai-content-optimizer'); ?></h1>
            <?php if ($premium_nag): ?>
            <div class="notice notice-warning">
                <p><?php printf(__('You\'ve reached the free limit of %d analyses/month. <a href="%s" target="_blank">Upgrade to Premium</a> for unlimited access!', 'ai-content-optimizer'), $limit, self::PREMIUM_URL); ?></p>
            </div>
            <?php endif; ?>
            <div id="aco-analyzer">
                <textarea id="aco-content" rows="10" cols="80" placeholder="Paste your post content here..."></textarea>
                <p class="submit"><button id="aco-analyze" class="button button-primary" <?php echo $premium_nag ? 'disabled' : ''; ?>>Analyze Content</button></p>
                <div id="aco-results"></div>
            </div>
            <div id="aco-history">
                <h2>Recent Analyses (<?php echo $used; ?>/<?php echo $limit; ?>)</h2>
                <ul>
                    <?php foreach (array_slice(array_reverse($analyses), 0, 10) as $analysis): ?>
                    <li><?php echo esc_html(date('Y-m-d H:i', $analysis['time'])); ?>: Score <?php echo esc_html($analysis['score']); ?>/100</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    public function handle_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if ($this->is_limit_reached()) {
            wp_send_json_error('Limit reached. Upgrade to premium.');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('Content is empty.');
        }

        // Simulate AI analysis with basic metrics (premium would use real API)
        $score = $this->calculate_score($content);
        $suggestions = $this->generate_suggestions($content);

        $analyses = get_option('aco_analyses', array());
        $analyses[] = array(
            'time' => time(),
            'score' => $score,
            'suggestions' => $suggestions
        );
        update_option('aco_analyses', array_slice($analyses, -20)); // Keep last 20

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    private function calculate_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence = $sentence_count ? $word_count / $sentence_count : 0;

        $score = 50; // Base
        if ($word_count > 300) $score += 10;
        if ($avg_sentence > 15 && $avg_sentence < 25) $score += 20;
        if (preg_match_all('/(https?:\/\/|www\.)\S+/i', $content)) $score += 10;
        if (preg_match_all('/<h[1-6]>/i', $content)) $score += 10;

        return min(100, $score);
    }

    private function generate_suggestions($content) {
        $suggestions = array();
        $word_count = str_word_count(strip_tags($content));
        if ($word_count < 300) {
            $suggestions[] = 'Add more content for better engagement (aim for 1000+ words).';
        }
        if (!preg_match('/<h2>/i', $content)) {
            $suggestions[] = 'Use H2 headings to improve structure and SEO.';
        }
        $suggestions[] = 'Premium: Get AI-powered keyword suggestions and auto-rewrites.';
        return $suggestions;
    }

    private function is_limit_reached() {
        $analyses = get_option('aco_analyses', array());
        $month_analyses = array_filter($analyses, function($a) {
            return date('Y-m', $a['time']) === date('Y-m');
        });
        return count($month_analyses) >= 5;
    }

    public function activate() {
        if (!get_option('aco_analyses')) {
            update_option('aco_analyses', array());
        }
    }
}

new AIContentOptimizerLite();

// Inline JS for simplicity (self-contained)
function aco_inline_js() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'ai-content-optimizer') return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function(e) {
            e.preventDefault();
            var content = $('#aco-content').val();
            if (!content) return alert('Please enter content');

            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var html = '<div class="aco-result"><h3>Score: ' + response.data.score + '/100</h3><ul>';
                    $.each(response.data.suggestions, function(i, sug) {
                        html += '<li>' + sug + '</li>';
                    });
                    html += '</ul></div>';
                    $('#aco-results').html(html);
                    location.reload();
                } else {
                    alert(response.data);
                }
            });
        });
    });
    </script>
    <style>
    #aco-results { margin-top: 20px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; }
    .aco-result h3 { color: #0073aa; }
    .aco-result ul { margin: 0; }
    </style>
    <?php
}
add_action('admin_footer', 'aco_inline_js');