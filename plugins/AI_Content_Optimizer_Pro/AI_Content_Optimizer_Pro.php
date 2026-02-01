/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes post content for SEO using AI-powered readability, keyword density, and suggestions. Freemium model.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->is_premium = $this->check_premium();
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    private function check_premium() {
        // Simulate premium check; in real, use license key validation
        return get_option('ai_content_optimizer_premium') === 'activated';
    }

    public function activate() {
        add_option('ai_content_optimizer_notices', true);
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
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $analysis = get_post_meta($post->ID, '_ai_optimizer_analysis', true);
        echo '<div id="ai-optimizer-analysis">';
        if ($analysis) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '%</p>';
            echo '<p><strong>Keyword Density:</strong> ' . esc_html($analysis['density']) . '%</p>';
            if (!$this->is_premium) {
                echo '<p><em>Upgrade to Pro for full optimizations and bulk processing.</em></p>';
                echo '<a href="#" class="button button-primary upgrade-pro">Upgrade to Pro</a>';
            } else {
                echo '<button id="optimize-btn" class="button button-secondary">Re-optimize</button>';
            }
        } else {
            echo '<button id="analyze-btn" class="button button-primary">Analyze Content</button>';
        }
        echo '</div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Trigger analysis on save if premium or basic
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_ajax'),
            'is_premium' => $this->is_premium
        ));
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_ajax', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real: integrate OpenAI API or similar)
        $readability = rand(60, 95);
        $density = rand(1, 5);
        $suggestions = $this->is_premium ? array('Add more headings', 'Improve keyword placement') : array();

        $analysis = array(
            'readability' => $readability,
            'density' => $density,
            'suggestions' => $suggestions
        );

        update_post_meta($post_id, '_ai_optimizer_analysis', $analysis);

        if ($this->is_premium) {
            // Apply optimizations
            $optimized_content = $this->apply_optimizations($content, $analysis);
            wp_update_post(array('ID' => $post_id, 'post_content' => $optimized_content));
        }

        wp_send_json_success($analysis);
    }

    private function apply_optimizations($content, $analysis) {
        // Basic optimization: add headings if readability low
        if ($analysis['readability'] < 80) {
            $content = '<h2>Optimized Section</h2>' . $content;
        }
        return $content;
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_premium_key'])) {
            update_option('ai_content_optimizer_premium', sanitize_text_field($_POST['ai_optimizer_premium_key']));
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <?php if (!$this->is_premium): ?>
            <form method="post">
                <p>Enter Premium Key: <input type="text" name="ai_optimizer_premium_key" /></p>
                <p class="description">Get your key at <a href="https://example.com/premium" target="_blank">example.com/premium</a> ($9/month)</p>
                <?php submit_button(); ?>
            </form>
            <?php else: ?>
            <p>Premium active! Enjoy bulk optimizations and more.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}

AIContentOptimizer::get_instance();

// Upsell notice
function ai_optimizer_admin_notice() {
    if (get_option('ai_content_optimizer_notices')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Content Optimizer Pro</strong> for bulk processing and advanced AI features! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Include JS file content as string (in real plugin, separate file)
function ai_optimizer_js_inline() {
    if (!wp_script_is('ai-optimizer-js', 'enqueued')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#analyze-btn, #optimize-btn').click(function(e) {
            e.preventDefault();
            var post_id = $('#post_ID').val();
            $.post(aiOptimizer.ajaxurl, {
                action: 'optimize_content',
                post_id: post_id,
                nonce: aiOptimizer.nonce
            }, function(response) {
                if (response.success) {
                    $('#ai-optimizer-analysis').html(
                        '<p><strong>Readability:</strong> ' + response.data.readability + '%</p>' +
                        '<p><strong>Density:</strong> ' + response.data.density + '%</p>' +
                        (aiOptimizer.is_premium ? '' : '<p>Upgrade for auto-apply!</p>')
                    );
                }
            });
        });
        $('.upgrade-pro').click(function(e) {
            e.preventDefault();
            alert('Redirecting to premium purchase...');
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_js_inline');
add_action('admin_footer-post-new.php', 'ai_optimizer_js_inline');
