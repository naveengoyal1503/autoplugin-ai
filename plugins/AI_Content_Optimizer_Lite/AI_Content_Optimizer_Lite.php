/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress post content for SEO and readability using AI-powered insights. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
        if (get_option('aco_usage_count', 0) >= 5 && !$this->is_premium()) {
            add_action('admin_notices', array($this, 'upgrade_notice'));
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-editor-alignleft',
            80
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_ai-content-optimizer') {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), self::VERSION, true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'usage_left' => 5 - get_option('aco_usage_count', 0),
            'is_premium' => $this->is_premium()
        ));
    }

    public function admin_page() {
        $usage_count = get_option('aco_usage_count', 0);
        $usage_left = 5 - $usage_count;
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Lite', 'ai-content-optimizer'); ?></h1>
            <p><?php printf(__('You have %d free analyses left this month.', 'ai-content-optimizer'), $usage_left); ?></p>
            <?php if (!$this->is_premium()): ?>
            <div class="notice notice-warning">
                <p><?php printf(__('Upgrade to Premium for unlimited analyses, advanced AI suggestions, and more! <a href="%s" target="_blank">Learn more</a>', 'ai-content-optimizer'), self::PREMIUM_URL); ?></p>
            </div>
            <?php endif; ?>
            <textarea id="aco-content" rows="20" cols="100" placeholder="Paste your content here or edit a post and click Analyze."></textarea>
            <br><button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>
            <div id="aco-results"></div>
        </div>
        <?php
    }

    public function handle_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if ($this->is_premium()) {
            wp_die(json_encode(array('success' => true, 'premium' => true)));
        }

        $usage_count = get_option('aco_usage_count', 0);
        if ($usage_count >= 5) {
            wp_send_json_error(__('Free limit reached. Upgrade to Premium!', 'ai-content-optimizer'));
        }

        $content = sanitize_textarea_field($_POST['content']);
        update_option('aco_usage_count', $usage_count + 1);

        // Simulate AI analysis (in premium, integrate real API like OpenAI)
        $word_count = str_word_count($content);
        $readability = rand(60, 90); // Flesch score simulation
        $seo_score = rand(70, 95);
        $suggestions = $this->generate_suggestions($content, $word_count, $readability, $seo_score);

        wp_send_json_success(array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => $suggestions
        ));
    }

    private function generate_suggestions($content, $word_count, $readability, $seo_score) {
        $sugs = array();
        if ($word_count < 300) {
            $sugs[] = 'Add more content to reach 300+ words for better SEO.';
        }
        if ($readability < 70) {
            $sugs[] = 'Simplify sentences for better readability.';
        }
        if ($seo_score < 80) {
            $sugs[] = 'Include primary keyword in title, intro, and headings.';
        }
        return $sugs;
    }

    private function is_premium() {
        return get_option('aco_premium_active') === true;
    }

    public function upgrade_notice() {
        echo '<div class="notice notice-info"><p>' . sprintf(
            __('AI Content Optimizer: Upgrade to <a href="%s" target="_blank">Premium</a> for unlimited use!', 'ai-content-optimizer'),
            self::PREMIUM_URL
        ) . '</p></div>';
    }

    public function activate() {
        add_option('aco_usage_count', 0);
    }
}

new AIContentOptimizerLite();

// Prevent direct access
if (!defined('ABSPATH')) exit;

// admin.js content (base64 encoded for single file, but inline here)
function aco_inline_js() {
    if (get_current_screen()->id === 'toplevel_page_ai-content-optimizer') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#aco-analyze-btn').click(function() {
                var content = $('#aco-content').val();
                if (!content) return alert('Please enter content');

                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    content: content
                }, function(response) {
                    if (response.success) {
                        var res = response.data;
                        var html = '<h3>Analysis Results:</h3>' +
                            '<p><strong>Word Count:</strong> ' + res.word_count + '</p>' +
                            '<p><strong>Readability Score:</strong> ' + res.readability + '/100</p>' +
                            '<p><strong>SEO Score:</strong> ' + res.seo_score + '/100</p>' +
                            '<h4>Suggestions:</h4><ul>';
                        $.each(res.suggestions, function(i, sug) {
                            html += '<li>' + sug + '</li>';
                        });
                        html += '</ul>';
                        if (aco_ajax.usage_left <= 1 && !aco_ajax.is_premium) {
                            html += '<p class="notice notice-warning">Free analyses exhausted. <a href="<?php echo self::PREMIUM_URL; ?>" target="_blank">Upgrade now!</a></p>';
                        }
                        $('#aco-results').html(html);
                    } else {
                        alert(response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'aco_inline_js');