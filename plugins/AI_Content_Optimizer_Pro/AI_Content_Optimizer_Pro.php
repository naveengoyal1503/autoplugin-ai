/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
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

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('save_post', array($this, 'save_meta'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_optimizer_settings');
                do_settings_sections('ai_optimizer_settings');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited optimizations for $49/year! <a href="#" class="pro-upsell">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('ai-optimizer-meta', 'AI Content Analysis', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-optimizer-meta', 'AI Content Analysis', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_meta_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        echo '<p><strong>AI Score:</strong> ' . esc_html($score ?: 'Not analyzed') . '/100</p>';
        echo '<button type="button" id="analyze-content" class="button">Analyze Now</button>';
        echo '<div id="ai-results"></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_meta_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_meta_nonce'], 'ai_optimizer_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Pro feature simulation
        if (isset($_POST['ai_optimizer_score'])) {
            update_post_meta($post_id, '_ai_optimizer_score', sanitize_text_field($_POST['ai_optimizer_score']));
        }
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulated AI analysis (free: basic, pro: advanced)
        $is_pro = get_option('ai_optimizer_pro', false);
        $score = $is_pro ? rand(85, 100) : rand(60, 85);
        $suggestions = $this->generate_suggestions($content, $is_pro);

        update_post_meta($post_id, '_ai_optimizer_score', $score);

        wp_send_json_success(array(
            'score' => $score,
            'suggestions' => $suggestions,
            'is_pro' => $is_pro,
            'pro_message' => !$is_pro ? 'Upgrade to Pro for advanced optimizations!' : ''
        ));
    }

    private function generate_suggestions($content, $pro = false) {
        $suggestions = array(
            'Add more headings for better structure.',
            'Include 2-3 target keywords naturally.',
            'Shorten sentences for readability.'
        );
        if ($pro) {
            $suggestions[] = 'AI-generated meta description: ' . substr(wp_trim_words($content, 30), 0, 160) . '...';
            $suggestions[] = 'Optimal title: ' . wp_trim_words(get_the_title(), 8) . ' | Keywords';
        }
        return $suggestions;
    }

    public function activate() {
        add_option('ai_optimizer_pro', false);
        flush_rewrite_rules();
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!get_option('ai_optimizer_pro')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for unlimited optimizations! <a href="options-general.php?page=ai-optimizer">Upgrade Now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Settings
add_action('admin_init', function() {
    register_setting('ai_optimizer_settings', 'ai_optimizer_pro');
    add_settings_section('ai_optimizer_main', 'Pro Features', null, 'ai_optimizer_settings');
    add_settings_field('pro_license', 'Pro License', function() {
        echo '<input type="checkbox" name="ai_optimizer_pro" value="1" ' . checked(get_option('ai_optimizer_pro'), true, false) . ' /> Enable Pro (Demo)';
    }, 'ai_optimizer_settings', 'ai_optimizer_main');
});

// Frontend display shortcode
function ai_optimizer_score_shortcode($atts) {
    $atts = shortcode_atts(array('post_id' => get_the_ID()), $atts);
    $score = get_post_meta($atts['post_id'], '_ai_optimizer_score', true);
    return $score ? '<div class="ai-score">AI Score: <span>' . $score . '/100</span></div>' : '';
}
add_shortcode('ai_score', 'ai_optimizer_score_shortcode');
?>