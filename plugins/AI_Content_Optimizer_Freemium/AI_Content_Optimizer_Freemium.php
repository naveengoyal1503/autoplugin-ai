/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better readability, SEO, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'ai-content-optimizer_page_aco-settings' === $hook) {
            wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
            wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-analysis-results">';
        echo '<button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-results"></div>';
        echo '<p class="description">Free: Basic readability score. <strong>Premium:</strong> Full AI SEO suggestions, keyword optimization.</p>';
        echo '<p><a href="#" id="aco-upgrade" class="button button-secondary">Upgrade to Premium</a></p>';
        echo '</div>';
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate AI analysis (basic free version)
        $word_count = str_word_count($content);
        $readability = min(100, 50 + ($word_count / 100)); // Mock score
        $is_premium = $this->is_premium();

        if ($is_premium) {
            // Mock premium features
            $suggestions = array(
                'Improve SEO with keywords: "WordPress", "plugin"',
                'Shorten sentences for better engagement',
                'Add headings for structure'
            );
            $score = array('readability' => $readability, 'seo' => 75, 'engagement' => 82);
        } else {
            $suggestions = array('Upgrade for AI-powered suggestions!');
            $score = array('readability' => $readability);
        }

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    private function is_premium() {
        // Simulate license check (integrate with Freemius or Stripe in real)
        return get_option('aco_premium_license') === 'valid';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'aco-settings', array($this, 'settings_page_callback'));
    }

    public function settings_page_callback() {
        if (isset($_POST['aco_license_key'])) {
            update_option('aco_premium_license', sanitize_text_field($_POST['aco_license_key']));
            echo '<div class="notice notice-success"><p>License updated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="aco_license_key" value="<?php echo esc_attr(get_option('aco_premium_license')); ?>" class="regular-text" placeholder="Enter your premium key" />
                        <p class="description">Get premium at <a href="https://example.com/premium" target="_blank">example.com/premium</a> ($49/year).</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function activate() {
        add_option('aco_premium_license', '');
    }
}

AIContentOptimizer::get_instance();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Note: In production, include actual JS/CSS files here or inline them.
});

// Mock JS/CSS (in production, create separate files)
function aco_inline_assets() {
    echo '<style>
    #aco-analysis-results { padding: 10px; }
    #aco-results { margin-top: 10px; background: #f9f9f9; padding: 10px; border-radius: 4px; }
    </style>';
    echo '<script>
    jQuery(document).ready(function($) {
        $("#aco-analyze-btn").click(function(e) {
            e.preventDefault();
            $.post(aco_ajax.ajax_url, {
                action: "aco_analyze_content",
                nonce: aco_ajax.nonce,
                post_id: $("#post_ID").val()
            }, function(response) {
                if (response.success) {
                    let html = "<h4>Scores:</h4>" + JSON.stringify(response.data.score) + "<h4>Suggestions:</h4><ul>";
                    response.data.suggestions.forEach(function(s) {
                        html += "<li>" + s + "</li>";
                    });
                    html += "</ul>";
                    $("#aco-results").html(html);
                }
            });
        });
    });
    </script>';
}
add_action('admin_footer-post.php', 'aco_inline_assets');
add_action('admin_footer-post-new.php', 'aco_inline_assets');