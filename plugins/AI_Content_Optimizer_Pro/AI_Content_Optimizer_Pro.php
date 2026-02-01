/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better readability, SEO, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
            add_action('wp_ajax_aco_upgrade', array($this, 'handle_upgrade'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        }
    }

    public function activate() {
        add_option('aco_premium_active', false);
        add_option('aco_scan_count', 0);
    }

    public function deactivate() {}

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin-js', 'aco_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aco_nonce'),
                'is_premium' => get_option('aco_premium_active'),
                'scans_left' => 5 - get_option('aco_scan_count', 0)
            ));
        }
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze-btn" class="button button-primary">' . (get_option('aco_premium_active') ? 'AI Optimize' : 'Analyze (Free: ' . (5 - get_option('aco_scan_count', 0)) . ' left)') . '</button>';
        if (!get_option('aco_premium_active')) {
            echo '<p><a href="#" id="aco-upgrade-btn">Upgrade to Pro</a> for unlimited AI features!</p>';
        }
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!get_option('aco_premium_active') && get_option('aco_scan_count', 0) >= 5) {
            wp_die(json_encode(array('error' => 'Free scans exhausted. Upgrade to Pro!')));
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        if (!get_option('aco_premium_active')) {
            update_option('aco_scan_count', get_option('aco_scan_count', 0) + 1);
        }

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $readability = $word_count > 300 ? 'Good' : 'Improve length';
        $seo_score = min(100, 50 + (substr_count(strtolower($content), 'keyword') * 10)); // Simulated

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => array('Add more headings', 'Include keywords')
        );

        // Premium AI simulation (in real: API call to OpenAI or similar)
        if (get_option('aco_premium_active')) {
            $results['ai_rewrite'] = $this->simulate_ai_rewrite($content);
            $results['advanced_seo'] = array('Meta title suggestion', 'H1 optimization');
        }

        wp_die(json_encode($results));
    }

    private function simulate_ai_rewrite($content) {
        // Simulated AI rewrite - in production, use AI API
        return substr($content, 0, 200) . '... (AI Optimized)';
    }

    public function handle_upgrade() {
        check_ajax_referer('aco_nonce', 'nonce');
        // Simulate payment - in production, integrate Stripe/PayPal
        // For demo: fake success
        update_option('aco_premium_active', true);
        wp_die(json_encode(array('success' => 'Upgraded to Pro! Refresh page.')));
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=aco-settings">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

AIContentOptimizer::get_instance();

// Admin JS (embedded for single file)
function aco_embed_admin_js() {
    if (isset($_GET['page']) && $_GET['page'] === 'aco-settings') return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#aco-analyze-btn').click(function() {
            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                post_id: $('#post_ID').val()
            }, function(response) {
                $('#aco-results').html('<pre>' + response + '</pre>');
            });
        });
        $('#aco-upgrade-btn').click(function() {
            $.post(aco_ajax.ajax_url, {
                action: 'aco_upgrade',
                nonce: aco_ajax.nonce
            }, function(response) {
                alert(response);
                location.reload();
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'aco_embed_admin_js');
add_action('admin_footer-post-new.php', 'aco_embed_admin_js');
?>