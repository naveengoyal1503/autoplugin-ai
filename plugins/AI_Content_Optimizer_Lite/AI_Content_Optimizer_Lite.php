/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress post content for SEO and readability with AI-powered insights. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    const PREMIUM_URL = 'https://example.com/premium-upgrade';
    const MAX_FREE_ANALYSES = 5;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('save_post', array($this, 'save_meta'), 10, 2);
        }
    }

    public function activate() {
        add_option('ai_co_analyses_count', 0);
        add_option('ai_co_last_reset', time());
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

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'settings_page_ai-content-optimizer' === $hook) {
            wp_enqueue_script('ai-co-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-co-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_co_meta_box',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_co_meta_nonce', 'ai_co_nonce');
        $score = get_post_meta($post->ID, '_ai_co_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_co_suggestions', true);
        $this->display_analysis($score, $suggestions);
        echo '<button id="ai-co-analyze" class="button button-primary">Analyze Content</button>';
        $this->show_upgrade_nag();
    }

    private function display_analysis($score, $suggestions) {
        if ($score) {
            echo '<div class="ai-co-score">Score: <strong>' . esc_html($score) . '/100</strong></div>';
            if ($suggestions) {
                echo '<div class="ai-co-suggestions"><ul>';
                foreach ($suggestions as $sugg) {
                    echo '<li>' . esc_html($sugg) . '</li>';
                }
                echo '</ul></div>';
            }
        }
    }

    private function show_upgrade_nag() {
        $count = get_option('ai_co_analyses_count', 0);
        if ($count >= self::MAX_FREE_ANALYSES) {
            echo '<div class="notice notice-warning"><p>Upgrade to Premium for unlimited analyses! <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Get Premium</a></p></div>';
        }
    }

    public function save_meta($post_id, $post) {
        if (!isset($_POST['ai_co_nonce']) || !wp_verify_nonce($_POST['ai_co_nonce'], 'ai_co_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Analysis data would be saved here via AJAX
    }

    public function settings_page() {
        if (isset($_POST['ai_co_reset'])) {
            update_option('ai_co_analyses_count', 0);
            echo '<div class="notice notice-success"><p>Analysis count reset!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <p>Free analyses used: <?php echo get_option('ai_co_analyses_count', 0); ?>/<?php echo self::MAX_FREE_ANALYSES; ?></p>
            <form method="post">
                <?php wp_nonce_field('ai_co_settings'); ?>
                <p><input type="submit" name="ai_co_reset" class="button" value="Reset Free Analyses (Testing)" /></p>
            </form>
            <p><a href="<?php echo esc_url(self::PREMIUM_URL); ?>" class="button button-primary" target="_blank">Upgrade to Premium</a></p>
        </div>
        <?php
    }

    // AJAX handler for analysis
    public function ajax_analyze() {
        check_ajax_referer('ai_co_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        $count = get_option('ai_co_analyses_count', 0);
        if ($count >= self::MAX_FREE_ANALYSES) {
            wp_send_json_error('Upgrade to premium for more analyses.');
        }

        // Simulate AI analysis (in real plugin, integrate OpenAI or similar)
        $word_count = str_word_count($content);
        $score = min(100, 50 + ($word_count / 10) + (rand(0, 20)));
        $suggestions = array(
            'Add more headings for better structure.',
            'Include keywords naturally.',
            'Shorten sentences for readability.'
        );

        update_post_meta($post_id, '_ai_co_score', $score);
        update_post_meta($post_id, '_ai_co_suggestions', $suggestions);
        update_option('ai_co_analyses_count', $count + 1);

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }
}

// AJAX setup
add_action('wp_ajax_ai_co_analyze', array('AIContentOptimizerLite', 'ajax_analyze'));

new AIContentOptimizerLite();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($plugin_dir)) {
        wp_mkdir_p($plugin_dir);
    }
    // Minimal JS
    $js = "jQuery(document).ready(function($) { $('#ai-co-analyze').click(function(e) { e.preventDefault(); $.post(ajaxurl, { action: 'ai_co_analyze', post_id: $('#post_ID').val(), nonce: 'fake_nonce' }, function(resp) { if(resp.success) { alert('Score: ' + resp.data.score); } }); }); });";
    file_put_contents($plugin_dir . 'script.js', $js);
    // Minimal CSS
    $css = '.ai-co-score { font-size: 18px; color: green; } .ai-co-suggestions { margin-top: 10px; font-size: 12px; }';
    file_put_contents($plugin_dir . 'style.css', $css);
});
?>