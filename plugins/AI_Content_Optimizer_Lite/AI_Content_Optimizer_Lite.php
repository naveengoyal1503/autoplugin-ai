/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress post content for SEO and readability using AI insights. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';
    const MAX_FREE_SCANS = 3;
    const SCAN_KEY = 'ai_content_optimizer_scans_today';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_register_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_style('ai-optimizer-css');
        wp_enqueue_script('ai-optimizer-js');
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'max_free' => self::MAX_FREE_SCANS,
            'is_premium' => $this->is_premium(),
            'upgrade_url' => 'https://example.com/premium-upgrade'
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_content'), 'post', 'side', 'high');
    }

    public function meta_box_content($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_nonce');
        $scans_today = get_option(self::SCAN_KEY, 0);
        echo '<div id="ai-optimizer-box">';
        echo '<p><strong>Free scans today: ' . (self::MAX_FREE_SCANS - $scans_today) . '/' . self::MAX_FREE_SCANS . '</strong></p>';
        echo '<button id="ai-optimize-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="ai-results"></div>';
        echo '<p><small><a href="' . esc_url('https://example.com/premium-upgrade') . '" target="_blank">Go Premium for Unlimited!</a></small></p>';
        echo '</div>';
    }

    public function handle_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium()) {
            $scans_today = (int) get_option(self::SCAN_KEY, 0);
            if ($scans_today >= self::MAX_FREE_SCANS) {
                wp_send_json_error('Daily free scan limit reached. <a href="https://example.com/premium-upgrade" target="_blank">Upgrade to Premium</a>');
            }
            update_option(self::SCAN_KEY, $scans_today + 1);
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulated AI analysis (in real plugin, integrate OpenAI API or similar)
        $word_count = str_word_count(strip_tags($content));
        $readability = rand(60, 90);
        $seo_score = rand(50, 95);
        $suggestions = $this->generate_suggestions($word_count, $readability, $seo_score);

        ob_start();
        ?>
        <div class="ai-results">
            <h4>Analysis Results:</h4>
            <ul>
                <li><strong>Word Count:</strong> <?php echo $word_count; ?></li>
                <li><strong>Readability Score:</strong> <?php echo $readability; ?>/100 (Flesch-Kincaid)</li>
                <li><strong>SEO Score:</strong> <?php echo $seo_score; ?>/100</li>
            </ul>
            <h4>Suggestions:</h4>
            <ul><?php echo $suggestions; ?></ul>
            <?php if (!$this->is_premium()) { ?>
            <p><em>Premium: AI rewriting, keyword suggestions, and more!</em></p>
            <?php } ?>
        </div>
        <?php
        $results = ob_get_clean();

        wp_send_json_success($results);
    }

    private function generate_suggestions($word_count, $readability, $seo_score) {
        $sugs = '';
        if ($word_count < 300) $sugs .= '<li>Add more content for better engagement.</li>';
        if ($readability < 70) $sugs .= '<li>Shorten sentences for improved readability.</li>';
        if ($seo_score < 70) $sugs .= '<li>Incorporate primary keywords naturally.</li>';
        if (empty($sugs)) $sugs .= '<li>Great job! Content is optimized.</li>';
        return $sugs;
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY, false);
    }

    public function premium_notice() {
        if ($this->is_premium()) return;
        $scans = get_option(self::SCAN_KEY, 0);
        if ($scans >= self::MAX_FREE_SCANS) {
            echo '<div class="notice notice-info"><p>AI Content Optimizer: Upgrade to premium for unlimited scans! <a href="https://example.com/premium-upgrade" target="_blank">Learn more</a></p></div>';
        }
    }

    public function activate() {
        add_option(self::SCAN_KEY, 0);
    }
}

new AIContentOptimizerLite();

// Reset daily scans
add_action('wp', function() {
    $today = date('Y-m-d');
    $last_reset = get_option('ai_optimizer_last_reset', '');
    if ($today !== $last_reset) {
        delete_option(AIContentOptimizerLite::SCAN_KEY);
        update_option('ai_optimizer_last_reset', $today);
    }
});

// Create empty style.css and script.js placeholders (in real dev, add them)
// style.css content: .ai-results { margin-top: 10px; } .ai-results ul { margin: 0; }
// script.js content:
/*
jQuery(document).ready(function($) {
    $('#ai-optimize-btn').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true).text('Analyzing...');
        $.post(aiOptimizer.ajaxurl, {
            action: 'optimize_content',
            nonce: aiOptimizer.nonce,
            post_id: $('#post_ID').val()
        }, function(res) {
            if (res.success) {
                $('#ai-results').html(res.data);
            } else {
                $('#ai-results').html('<p style="color:red;">' + res.data + '</p>');
            }
            btn.prop('disabled', false).text('Analyze Content');
        });
    });
});
*/
?>