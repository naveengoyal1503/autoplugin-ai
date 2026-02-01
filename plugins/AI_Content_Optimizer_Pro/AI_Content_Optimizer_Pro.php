/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Freemium model with premium upgrades.
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
    public $is_premium = false;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('wp_ajax_aco_upgrade', array($this, 'ajax_upgrade'));
        $this->check_premium();
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    private function check_premium() {
        // Simulate premium check (in real: integrate with Freemius or license key)
        $this->is_premium = false; // get_option('aco_premium_active', false);
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Basic analysis. <a href="#" id="aco-upgrade">Upgrade to Pro</a> for AI rewrite & more.</small></p>';
        echo '<script>var postContent = ' . json_encode($content) . ';</script>';
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['aco_license_key']) && wp_verify_nonce($_POST['aco_nonce'], 'aco_settings')) {
            update_option('aco_license_key', sanitize_text_field($_POST['aco_license_key']));
            $this->check_premium();
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <?php wp_nonce_field('aco_settings', 'aco_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="aco_license_key" value="<?php echo esc_attr(get_option('aco_license_key', '')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$this->is_premium) : ?>
            <div class="notice notice-info">
                <p>Unlock premium features: <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_ajax', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $keywords = $this->extract_keywords($content);

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'keywords' => array_slice($keywords, 0, 5),
            'score' => $this->get_seo_score($word_count, $readability),
            'premium_only' => false
        );

        if ($this->is_premium) {
            // Premium: Simulated AI rewrite
            $results['rewrite'] = $this->mock_ai_rewrite($content);
            $results['premium_only'] = true;
        } else {
            $results['upgrade_msg'] = 'Upgrade for AI-powered rewriting and bulk tools.';
        }

        wp_send_json_success($results);
    }

    public function ajax_upgrade() {
        check_ajax_referer('aco_ajax', 'nonce');
        wp_send_json(array('message' => 'Redirecting to premium upgrade...'));
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/', $text);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        if ($sentence_count == 0 || $word_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = preg_replace('/[^a-z]/i', '', $text);
        return preg_match_all('/[aeiouy]{2,}/', $text) + preg_match_all('/[^aeiouy][aeiouy]/', $text);
    }

    private function extract_keywords($text) {
        $words = explode(' ', preg_replace('/[^a-zA-Z\s]/', '', strtolower($text)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_keys($counts);
    }

    private function get_seo_score($word_count, $readability) {
        $score = 50;
        if ($word_count > 300) $score += 20;
        if ($readability > 60) $score += 20;
        if ($readability < 30) $score -= 10;
        return min(100, $score);
    }

    private function mock_ai_rewrite($content) {
        // Mock AI rewrite for demo (in real: integrate OpenAI API)
        return substr($content, 0, 200) . '... (Premium AI Rewrite) Optimized for SEO and engagement.';
    }
}

// Enqueue scripts
add_action('admin_enqueue_scripts', function($hook) {
    if (in_array($hook, array('post.php', 'post-new.php', 'settings_page_ai-content-optimizer'))) {
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_ajax')
        ));
    }
});

AIContentOptimizer::get_instance();

// Create JS file placeholder (in real plugin, include separate JS file)
/*
Placeholder for aco-admin.js:

jQuery(document).ready(function($) {
    $('#aco-analyze').click(function(e) {
        e.preventDefault();
        $('#aco-results').html('<p>Analyzing...</p>');
        $.post(aco_ajax.ajaxurl, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            content: postContent
        }, function(res) {
            if (res.success) {
                let html = '<p><strong>SEO Score: ' + res.data.score + '%</strong></p>' +
                          '<p>Words: ' + res.data.word_count + '</p>' +
                          '<p>Readability: ' + res.data.readability + '</p>' +
                          '<p>Top Keywords: ' + res.data.keywords.join(', ') + '</p>';
                if (res.data.rewrite) {
                    html += '<p><strong>AI Rewrite:</strong> ' + res.data.rewrite + '</p>';
                } else if (res.data.upgrade_msg) {
                    html += '<p>' + res.data.upgrade_msg + '</p>';
                }
                $('#aco-results').html(html);
            }
        });
    });

    $('#aco-upgrade').click(function(e) {
        e.preventDefault();
        $.post(aco_ajax.ajaxurl, {
            action: 'aco_upgrade',
            nonce: aco_ajax.nonce
        });
        alert('Upgrading to Pro... Visit https://example.com/premium');
    });
});
*/
?>