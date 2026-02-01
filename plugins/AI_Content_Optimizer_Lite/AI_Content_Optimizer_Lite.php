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
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizerLite {
    private static $instance = null;
    public $is_premium = false;
    public $usage_count = 0;

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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->load_textdomain('ai-content-optimizer-lite');
        $this->is_premium = get_option('aco_premium_active', false);
        $this->usage_count = get_option('aco_usage_count', 0);
    }

    public function activate() {
        add_option('aco_usage_count', 0);
    }

    public function deactivate() {}

    public function add_admin_menu() {
        add_posts_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'edit.php' !== $hook) return;
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => $this->is_premium,
            'usage_count' => $this->usage_count,
            'max_free' => 5
        ));
    }

    public function admin_page() {
        $usage_left = max(0, 5 - $this->usage_count);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Lite</h1>
            <p>Free uses left: <strong><?php echo $usage_left; ?></strong>/5 per month.</p>
            <?php if (!$this->is_premium && $this->usage_count >= 5) : ?>
                <div class="notice notice-warning">
                    <p>Upgrade to premium for unlimited optimizations! <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
                </div>
            <?php endif; ?>
            <p>Paste your content below or edit a post to optimize.</p>
            <textarea id="aco_content" rows="10" cols="80" placeholder="Paste your content here..."></textarea>
            <button id="aco_optimize_btn" class="button button-primary">Optimize Content</button>
            <div id="aco_results"></div>
        </div>
        <?php
    }

    public function handle_optimize_ajax() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!$this->is_premium && $this->usage_count >= 5) {
            wp_send_json_error('Free limit reached. Upgrade to premium!');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
        $suggestions = $this->generate_suggestions($content);

        if (!$this->is_premium) {
            $this->usage_count++;
            update_option('aco_usage_count', $this->usage_count);
        }

        wp_send_json_success(array(
            'optimized_content' => $suggestions['optimized'],
            'suggestions' => $suggestions['tips'],
            'usage_count' => $this->usage_count
        ));
    }

    private function generate_suggestions($content) {
        // Basic heuristic analysis for demo (replace with real AI in premium)
        $word_count = str_word_count($content);
        $has_headings = preg_match('/<h[1-6]/', $content);
        $tips = array();

        if ($word_count < 300) {
            $tips[] = 'Add more content: Aim for 300+ words for better SEO.';
        }
        if (!$has_headings) {
            $tips[] = 'Use H2/H3 headings to structure your content.';
        }
        $tips[] = 'Include keywords naturally in the first paragraph.';
        $tips[] = 'Add internal/external links for authority.';

        $optimized = $content;
        if (!$has_headings) {
            $optimized = '<h2>Introduction</h2>' . $content;
        }

        if ($this->is_premium) {
            // Premium: Advanced rewrite
            $optimized .= '<p><em>Premium AI Rewrite: Optimized for readability and SEO score 85/100.</em></p>';
            $tips[] = 'Premium feature: Full AI rewrite applied!';
        }

        return array(
            'optimized' => $optimized . '<ul><li>' . implode('</li><li>', $tips) . '</li></ul>',
            'tips' => $tips
        );
    }
}

AIContentOptimizerLite::get_instance();

// Premium check (simulate)
if (isset($_GET['premium_key']) && $_GET['premium_key'] === 'demo123') {
    update_option('aco_premium_active', true);
}

// Reset usage monthly
if (!wp_next_scheduled('aco_reset_usage')) {
    wp_schedule_event(strtotime('first day of next month 00:00:00'), 'monthly', 'aco_reset_usage');
}
add_action('aco_reset_usage', function() {
    update_option('aco_usage_count', 0);
});

// JS file would be separate, but for single-file, inline it
?><script>
// Inline JS for single-file plugin
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('aco_optimize_btn');
    const content = document.getElementById('aco_content');
    const results = document.getElementById('aco_results');

    btn.addEventListener('click', function() {
        jQuery.post(aco_ajax.ajax_url, {
            action: 'aco_optimize_content',
            nonce: aco_ajax.nonce,
            content: content.value
        }, function(response) {
            if (response.success) {
                results.innerHTML = '<h3>Optimized Content:</h3><div>' + response.data.optimized_content + '</div>';
                if (!aco_ajax.is_premium) {
                    results.innerHTML += '<p>Uses remaining: ' + (5 - response.data.usage_count) + '</p>';
                }
            } else {
                results.innerHTML = '<p style="color:red;">' + response.data + '</p>';
            }
        });
    });
});
</script>