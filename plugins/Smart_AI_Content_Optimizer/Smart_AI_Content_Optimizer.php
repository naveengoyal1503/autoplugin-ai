/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart AI Content Optimizer
 * Plugin URI: https://example.com/smart-ai-content-optimizer
 * Description: AI-powered plugin that analyzes and optimizes post content for SEO, readability, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-ai-content-optimizer
 * Requires at least: 5.0
 * Tested up to: 6.6
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAIContentOptimizer {
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
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->is_premium = get_option('saco_premium_active', false);
        $this->usage_count = get_option('saco_usage_count', 0);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('saco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('saco-admin-js', 'saco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('saco_nonce'),
            'is_premium' => $this->is_premium,
            'usage_count' => $this->usage_count,
            'max_free' => 5
        ));
        wp_enqueue_style('saco-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'saco-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['saco_premium_key'])) {
            update_option('saco_premium_active', true);
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Smart AI Content Optimizer', 'smart-ai-content-optimizer'); ?></h1>
            <p><?php _e('Free scans left this month: ', 'smart-ai-content-optimizer'); ?><?php echo max(0, 5 - $this->usage_count); ?></p>
            <form method="post">
                <p>
                    <label><?php _e('Enter Premium Key: ', 'smart-ai-content-optimizer'); ?></label>
                    <input type="text" name="saco_premium_key" placeholder="premium-key-123">
                    <input type="submit" class="button-primary" value="Activate Premium">
                </p>
            </form>
            <p><strong>Upgrade to Premium:</strong> <a href="https://example.com/premium" target="_blank">Subscribe for $9.99/mo</a> for unlimited optimizations and advanced features.</p>
        </div>
        <?php
    }

    public function handle_optimize_content() {
        check_ajax_referer('saco_nonce', 'nonce');

        if (!$this->is_premium && $this->usage_count >= 5) {
            wp_die(json_encode(array('error' => 'Free limit reached. Upgrade to premium!')));
        }

        $post_id = intval($_POST['post_id']);
        $content = sanitize_textarea_field($_POST['content']);

        if (!$this->is_premium) {
            $this->usage_count++;
            update_option('saco_usage_count', $this->usage_count);
        }

        // Simulate AI optimization (in real version, integrate OpenAI API or similar)
        $suggestions = $this->mock_ai_optimize($content);

        wp_die(json_encode(array(
            'optimized_content' => $suggestions['content'],
            'seo_score' => $suggestions['seo_score'],
            'readability_score' => $suggestions['readability'],
            'suggestions' => $suggestions['tips']
        )));
    }

    private function mock_ai_optimize($content) {
        // Mock AI logic: Add keywords, improve structure, etc.
        $word_count = str_word_count($content);
        $seo_score = min(95, 50 + ($word_count / 10));
        $readability = rand(70, 90);

        $optimized = $content;
        $optimized .= '\n\n<h2>Optimized Summary</h2><p>This content has been enhanced for better SEO and engagement.</p>';

        $tips = array(
            'Add more H2 headings for structure.',
            'Include target keywords naturally.',
            'Shorten sentences for readability.'
        );

        return array(
            'content' => $optimized,
            'seo_score' => $seo_score,
            'readability' => $readability,
            'tips' => $tips
        );
    }

    public function activate() {
        add_option('saco_usage_count', 0);
    }

    public function deactivate() {
        // Reset monthly count on deactivate for demo
        delete_option('saco_usage_count');
    }
}

// Add meta box to post editor
add_action('add_meta_boxes', function() {
    add_meta_box('saco-optimizer', 'AI Content Optimizer', 'saco_meta_box_callback', 'post', 'side');
});

function saco_meta_box_callback($post) {
    echo '<button id="saco-optimize-btn" class="button button-primary">Optimize Content with AI</button>';
    echo '<div id="saco-results"></div>';
    echo '<p><em>Free: 5/month | Premium: Unlimited</em></p>';
}

SmartAIContentOptimizer::get_instance();

// admin.js content (embedded as string for single file)
$js = "jQuery(document).ready(function($) { $('#saco-optimize-btn').click(function() { var content = tinyMCE.activeEditor.getContent(); $.post(saco_ajax.ajax_url, { action: 'optimize_content', post_id: " . get_the_ID() . ", content: content, nonce: saco_ajax.nonce }, function(response) { var data = JSON.parse(response); $('#saco-results').html('<p>SEO Score: ' + data.seo_score + '% | Readability: ' + data.readability_score + '%</p><ul>' + data.suggestions.map(function(s) { return '<li>' + s + '</li>'; }).join('') + '</ul><textarea rows="10" cols="50">' + data.optimized_content + '</textarea>'); }); }); });");

// For simplicity, enqueue inline JS
add_action('admin_footer-post.php', function() { ?>
<script><?php echo $js; ?></script>
<style>#saco-optimizer { background: #0073aa; color: white; }</style>
<?php });

// admin.css inline
add_action('admin_head-post.php', function() { ?>
<style>
#saco-results textarea { width: 100%; }
#saco-results { margin-top: 10px; }
</style>
<?php });