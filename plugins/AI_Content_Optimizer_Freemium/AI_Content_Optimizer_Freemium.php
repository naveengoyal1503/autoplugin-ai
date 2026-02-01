/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability with AI-powered insights. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    private static $instance = null;
    private $premium_key = '';
    private $scan_count = 0;
    private $max_free_scans = 5;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_optimize_content', array($this, 'handle_optimize_ajax'));
        add_action('wp_ajax_aco_upgrade_notice', array($this, 'handle_upgrade_ajax'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->premium_key = get_option('aco_premium_key', '');
        $this->scan_count = get_option('aco_scan_count', 0);
    }

    public function activate() {
        if (!get_option('aco_scan_count')) {
            update_option('aco_scan_count', 0);
        }
        if (!get_option('aco_activation_date')) {
            update_option('aco_activation_date', current_time('timestamp'));
        }
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'AI Content Optimizer',
            'AI Content Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        $content = '';
        $analysis = '';
        if ($post_id && get_post($post_id)) {
            $content = get_post_field('post_content', $post_id);
        }
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function handle_optimize_ajax() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!$this->is_premium() && $this->scan_count >= $this->max_free_scans) {
            wp_send_json_error('Free limit reached. Upgrade to premium for unlimited scans.');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_score($content);
        $seo_score = min(100, 50 + ($word_count / 10) + ($readability / 2));
        $suggestions = $this->generate_suggestions($content, $seo_score);

        if (!$this->is_premium()) {
            $this->scan_count++;
            update_option('aco_scan_count', $this->scan_count);
        }

        wp_send_json_success(array(
            'seo_score' => $seo_score,
            'readability' => $readability,
            'word_count' => $word_count,
            'suggestions' => $suggestions,
            'scans_left' => $this->is_premium() ? 'Unlimited' : ($this->max_free_scans - $this->scan_count)
        ));
    }

    public function handle_upgrade_ajax() {
        check_ajax_referer('aco_nonce', 'nonce');
        wp_send_json_success(array('message' => 'Upgrade to unlock unlimited scans, AI rewriting, and more! <a href="https://example.com/premium" target="_blank">Get Premium</a>'));
    }

    private function is_premium() {
        return !empty($this->premium_key) && $this->validate_premium_key($this->premium_key);
    }

    private function validate_premium_key($key) {
        // Simulate validation (in real, call your API)
        return hash('sha256', $key) === 'valid_premium_hash_example';
    }

    private function calculate_flesch_score($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = explode(' ', strip_tags($text));
        $word_count = count(array_filter($words));
        $syllables = $this->count_syllables($text);

        if ($sentence_count == 0 || $word_count == 0) return 0;

        $asl = $word_count / $sentence_count;
        $asw = $syllables / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(strip_tags($text));
        $vowels = '[aeiouy]';
        $syllables = preg_match_all('/' . $vowels . '+(?![^ ]*' . $vowels . '* )/', $text, $matches);
        return $syllables;
    }

    private function generate_suggestions($content, $score) {
        $suggestions = array();
        if ($score < 70) {
            $suggestions[] = 'Add more keywords and improve sentence variety.';
        }
        if (str_word_count($content) < 300) {
            $suggestions[] = 'Expand content to at least 300 words for better SEO.';
        }
        $suggestions[] = 'Use short paragraphs and bullet points for readability.';
        return $suggestions;
    }
}

AIContentOptimizer::get_instance();

// Admin page template (embedded as string for single file)
function aco_get_admin_template() {
    ob_start();
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer</h1>
        <p>Optimize your content for SEO and readability. <strong>Free: 5 scans/month</strong> | <a href="#" id="aco-upgrade">Upgrade to Premium</a></p>
        <div id="aco-scans-info">Scans left: <span id="scans-left"><?php echo AIContentOptimizer::get_instance()->is_premium() ? 'Unlimited' : (5 - get_option('aco_scan_count', 0)); ?></span></div>
        <textarea id="aco-content" rows="10" cols="80" placeholder="Paste your content here or select a post..."><?php echo esc_textarea($GLOBALS['content'] ?? ''); ?></textarea>
        <br><button id="aco-optimize" class="button button-primary">Analyze & Optimize</button>
        <div id="aco-results" style="display:none;">
            <h3>Results</h3>
            <p>SEO Score: <span id="seo-score"></span>/100</p>
            <p>Readability: <span id="readability"></span></p>
            <p>Word Count: <span id="word-count"></span></p>
            <h4>Suggestions:</h4>
            <ul id="suggestions"></ul>
        </div>
        <?php if (isset($_GET['post'])): ?>
            <p><a href="post.php?post=<?php echo intval($_GET['post']); ?>&action=edit" class="button">Edit Original Post</a></p>
        <?php endif; ?>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#aco-optimize').click(function() {
            var content = $('#aco-content').val();
            $.post(ajaxurl, {
                action: 'aco_optimize_content',
                nonce: '<?php echo wp_create_nonce("aco_nonce"); ?>',
                content: content
            }, function(res) {
                if (res.success) {
                    $('#seo-score').text(res.data.seo_score);
                    $('#readability').text(Math.round(res.data.readability));
                    $('#word-count').text(res.data.word_count);
                    $('#suggestions').empty();
                    res.data.suggestions.forEach(function(sug) {
                        $('#suggestions').append('<li>' + sug + '</li>');
                    });
                    $('#scans-left').text(res.data.scans_left);
                    $('#aco-results').show();
                } else {
                    alert(res.data);
                }
            });
        });
        $('#aco-upgrade').click(function() {
            $.post(ajaxurl, {
                action: 'aco_upgrade_notice',
                nonce: '<?php echo wp_create_nonce("aco_nonce"); ?>'
            }, function(res) {
                alert(res.data.message);
            });
        });
    });
    </script>
    <style>
    #aco-results { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin-top: 20px; }
    #seo-score { font-size: 24px; font-weight: bold; color: #0073aa; }
    </style>
    <?php
    return ob_get_clean();
}
// Note: In admin_page(), echo aco_get_admin_template(); but simplified for single file
?>