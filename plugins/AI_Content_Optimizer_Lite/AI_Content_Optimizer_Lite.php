/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and readability with AI-powered suggestions. Freemium model.
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
    const MAX_FREE_SCANS = 5;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aco_analyze_content', [$this, 'handle_analyze_content']);
        add_action('admin_notices', [$this, 'check_usage_limit_notice']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function activate() {
        $usage = get_option('aco_usage_count', 0);
        if ($usage === false) {
            update_option('aco_usage_count', 0);
            update_option('aco_install_date', current_time('timestamp'));
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            [$this, 'admin_page'],
            'dashicons-editor-alignleft',
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_ai-content-optimizer') {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', ['jquery'], '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'max_free' => self::MAX_FREE_SCANS,
            'premium_url' => self::PREMIUM_URL
        ]);
    }

    public function admin_page() {
        $usage = get_option('aco_usage_count', 0);
        $remaining = self::MAX_FREE_SCANS - $usage;
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Lite</h1>
            <p>Free scans remaining: <strong><?php echo $remaining; ?></strong></p>
            <?php if ($remaining <= 0): ?>
                <div class="notice notice-warning"><p>Upgrade to premium for unlimited access! <a href="<?php echo self::PREMIUM_URL; ?>" target="_blank">Get Premium</a></p></div>
            <?php endif; ?>
            <textarea id="aco-content" rows="10" cols="80" placeholder="Paste your content here or enter Post ID"></textarea>
            <br><button id="aco-analyze" class="button button-primary">Analyze Content</button>
            <div id="aco-results"></div>
        </div>
        <?php
    }

    public function handle_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $usage = get_option('aco_usage_count', 0);
        if ($usage >= self::MAX_FREE_SCANS) {
            wp_send_json_error('Limit reached. <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade to premium</a>');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided');
        }

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $readability = 70 - ($word_count / 100); // Mock formula
        $seo_score = min(95, 50 + (substr_count($content, ' ') / 10)); // Mock SEO
        $suggestions = [
            'Use shorter sentences for better readability.',
            'Add more keywords related to your topic.',
            'Include a call-to-action at the end.'
        ];

        update_option('aco_usage_count', $usage + 1);

        wp_send_json_success([
            'word_count' => $word_count,
            'readability_score' => round($readability, 1) . '%',
            'seo_score' => round($seo_score, 1) . '%',
            'suggestions' => $suggestions,
            'premium_tease' => 'Premium: Auto-fix issues & advanced AI insights.'
        ]);
    }

    public function check_usage_limit_notice() {
        $screen = get_current_screen();
        if ($screen->id !== 'dashboard') {
            return;
        }
        $usage = get_option('aco_usage_count', 0);
        if ($usage >= self::MAX_FREE_SCANS) {
            echo '<div class="notice notice-info is-dismissible"><p>AI Content Optimizer: Upgrade for unlimited scans! <a href="' . self::PREMIUM_URL . '" target="_blank">Learn more</a></p></div>';
        }
    }
}

new AIContentOptimizer();

// Mock JS file content (in real plugin, separate file)
/*
function aco_admin_js() {
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function() {
            var content = $('#aco-content').val();
            if (!content) return;

            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    $('#aco-results').html(
                        '<h3>Results:</h3>' +
                        '<p>Words: ' + res.word_count + '</p>' +
                        '<p>Readability: ' + res.readability_score + '</p>' +
                        '<p>SEO Score: ' + res.seo_score + '</p>' +
                        '<ul>' + res.suggestions.map(s => '<li>' + s + '</li>').join('') + '</ul>' +
                        '<p>' + res.premium_tease + ' <a href="' + aco_ajax.premium_url + '" target="_blank">Upgrade</a></p>'
                    );
                } else {
                    $('#aco-results').html('<p class="error">' + response.data + '</p>');
                }
            });
        });
    });
}
*/
?>